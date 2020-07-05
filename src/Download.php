<?php

namespace Storj\Uplink;

use DateTimeImmutable;
use Exception;
use FFI;
use FFI\CData;
use Generator;
use IteratorAggregate;
use Storj\Uplink\Exception\IOException;
use Storj\Uplink\Exception\UplinkException;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * Handle to a remote object
 *
 * TODO: can the download be read only once? Should we protect against reading it twice?
 */
class Download
{
    /**
     * We seem to receive 7408 bytes per read
     */
    private const CHUNKSIZE = 8000;

    /**
     * With libuplink.so and header files loaded
     */
    private FFI $ffi;

    /**
     * The C struct of type Download
     */
    private CData $cDownload;

    /**
     * To free resources when this object is done
     */
    private Scope $scope;

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
        $objectResult = $this->ffi->download_info($this->cDownload);
        $scope = Scope::exit(fn() => $this->ffi->free_object_result($objectResult));

        Util::throwIfErrorResult($objectResult);

        return ObjectInfo::fromCStruct($objectResult->object);
    }

    /**
     * Low-level function to read a chunk of a download.
     * Probably you'll want to use one of the other methods
     *
     * @param CData|null $buffer !!!!! IF < $length YOU WILL SEGFAULT !!!!!
     *
     * @return string empty if the end has been reached
     *
     * @throws UplinkException
     */
    public function read(int $length = self::CHUNKSIZE, ?CData $buffer = null): string
    {
        if ($buffer === null) {
            $buffer = Util::createBuffer($length);
        }

        $readResult = $this->ffi->download_read($this->cDownload, $buffer, $length);
        $scope = Scope::exit(fn() => $this->ffi->free_read_result($readResult));

        if ($readResult->error !== null && $readResult->error->code === -1) {
            // done
            return '';
        }

        Util::throwIfErrorResult($readResult);

        return FFI::string($buffer, $readResult->bytes_read);
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
        set_error_handler('var_dump', 0);
        Scope::exit(fn() => restore_error_handler());

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
        $buffer = Util::createBuffer($chunkSize);

        while (true) {
            $chunk = $this->read($chunkSize, $buffer);
            if (strlen($chunk) === 0) {
                return;
            }

            yield $chunk;
        }
    }
}
