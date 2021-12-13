<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Generator;
use Storj\Uplink\Exception\IOException;
use Storj\Uplink\Exception\UplinkException;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;
use Storj\Uplink\PsrStream\ReadStream;

/**
 * Handle to a remote object on the Storj network
 *
 * TODO: download can only be read once. Should we protect against reading it twice?
 */
class Download
{
    /**
     * We seem to receive 7408 bytes per read
     */
    protected const CHUNKSIZE = 8000;

    /**
     * With libuplink.so and header files loaded
     */
    private FFI $ffi;

    /**
     * The C struct of type UplinkDownload
     */
    private CData $cDownload;

    /**
     * To free resources when this object is done
     */
    private Scope $scope;

    /**
     * @internal
     */
    public function __construct(
        FFI $ffi,
        CData $cDownload,
        Scope $scope
    ) {
        $this->ffi = $ffi;
        $this->cDownload = $cDownload;
        $this->scope = $scope;
    }

    public function info(): ObjectInfo
    {
        $objectResult = $this->ffi->uplink_download_info($this->cDownload);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_object_result($objectResult));

        Util::throwIfErrorResult($objectResult);

        return ObjectInfo::fromCStruct($objectResult->object, true, true);
    }

    /**
     * Low-level function to read a chunk of a download.
     * Probably you'll want to use one of the other methods
     *
     * @param int $length this is a maximum, there may be more data even if it gave back less than this
     * @param string|null $buffer !!!!! IF < $length YOU WILL SEGFAULT !!!!!
     *
     * @return string empty if the end has been reached
     *
     * @throws UplinkException
     */
    public function read(int $length = self::CHUNKSIZE, ?string& $buffer = null): string
    {
        if ($buffer === null) {
            $buffer = str_repeat("\0", $length);
        }

        $readResult = $this->readChunkToString($buffer);

        if ($readResult->isEof()) {
            return $readResult->getBytes();
        }

        // No data was returned but there was no EOF. Keep reading.
        if ($readResult->getLength() === 0) {
            return $this->read($length, $buffer);
        }

        return $readResult->getBytes();
    }

    /**
     * Low-level function to read a chunk of a download.
     * Probably you'll want to use one of the other methods.
     *
     * It allocates a string to put the result, because it has no measurable impact on performance.
     *
     * @throws UplinkException
     */
    public function readChunkToString(string& $buffer): ReadResult
    {
        $cReadResult = $this->ffi->uplink_download_read($this->cDownload, $buffer, strlen($buffer));
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_read_result($cReadResult));

        $readResult = new ReadResult(
            substr($buffer, 0, $cReadResult->bytes_read),
            ($cReadResult->error !== null && $cReadResult->error->code === -1)
        );

        // If there is an error but still data, first return the data.
        // Return the error of the next call to uplink_download_read().
        if (!$readResult->isEof() && $readResult->getLength() === 0) {
            Util::throwIfErrorResult($cReadResult);
        }

        return $readResult;
    }

    /**
     * Read the entire download into a string.
     * If part was already read it will return the remainder.
     * You may run out of memory.
     *
     * @throws UplinkException
     */
    public function readAll(int $chunkSize = self::CHUNKSIZE): string
    {
        $result = '';

        foreach ($this->iterate($chunkSize) as $chunk) {
            $result .= $chunk;
        }

        return $result;
    }

    /**
     * @param resource $resource examples:
     *     - file created with fopen()
     *     - socket created with socket_create()
     *     - STDOUT
     *     - php://output to stream to the HTTP client
     * @param int $chunkSize
     *
     * @return int bytes written
     *
     * @throws UplinkException
     */
    public function readIntoResource($resource, int $chunkSize = self::CHUNKSIZE): int
    {
        Util::assertResource($resource);

        // Prevent fwrite() errors from being intercepted by the user's custom error handing.
        // We'll thrown an exception instead.
        // See https://www.php.net/manual/en/function.error-get-last.php#113518
        // This will never be called because of the 0.
        set_error_handler(null, 0);
        $scope = Scope::exit('restore_error_handler');

        $totalWritten = 0;

        foreach ($this->iterate($chunkSize) as $chunk) {
            $written = @fwrite($resource, $chunk);
            if ($written === false) {
                $error = error_get_last();
                throw new IOException($error['message'], $error['type']);
            }

            $totalWritten += $written;
        }

        return $totalWritten;
    }

    /**
     * Can be used in foreach to iterate over chunks of the object
     *
     * @return string[]|Generator<string>
     * @throws UplinkException
     */
    public function iterate(int $chunkSize = self::CHUNKSIZE): Generator
    {
        $buffer = str_repeat("\0", $chunkSize);

        while (true) {
            $readResult = $this->readChunkToString($buffer);
            if ($readResult->isEof()) {
                yield $readResult->getBytes();
                return;
            }

            yield $readResult->getBytes();
        }
    }

    public function cursored(): CursoredDownload
    {
        return new CursoredDownload($this->ffi, $this->cDownload, $this->scope);
    }

    public function toPsrStream(): ReadStream
    {
        return new ReadStream($this->cursored());
    }
}
