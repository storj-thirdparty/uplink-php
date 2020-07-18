<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Exception\IOException;
use Storj\Uplink\Exception\UplinkException;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

class Upload
{
    private const CHUNKSIZE = 8000;

    /**
     * With libuplink.so and header files loaded
     */
    private FFI $ffi;

    /**
     * The associated C struct of type Upload
     */
    private CData $cUpload;

    /**
     * To free memory when this object is done
     */
    private Scope $scope;

    public function __construct(FFI $ffi, CData $cUpload, Scope $scope)
    {
        $this->ffi = $ffi;
        $this->cUpload = $cUpload;
        $this->scope = $scope;
    }

    /**
     * @throws UplinkException
     */
    public function write(string $content): void
    {
        $writeResult = $this->ffi->upload_write($this->cUpload, $content, strlen($content));
        $scope = Scope::exit(fn() => $this->ffi->free_write_result($writeResult));

        Util::throwIfErrorResult($writeResult);
    }

    /**
     * @param resource $resource
     * @throws UplinkException
     */
    public function writeFromResource($resource, int $chunkSize = self::CHUNKSIZE): void
    {
        Util::assertResource($resource);

        // Prevent fread() errors from being intercepted by the user's custom error handing.
        // We'll thrown an exception instead.
        // See https://www.php.net/manual/en/function.error-get-last.php#113518
        // This will never be called because of the 0.
        set_error_handler('var_dump', 0);
        $scope = Scope::exit(fn() => restore_error_handler());

        while (!feof($resource)) {
            $content = @fread($resource, $chunkSize);
            if ($content === false) {
                $error = error_get_last();
                throw new IOException($error['message'], $error['type']);
            }
            if (strlen($content) === 0) {
                break;
            }
            $this->write($content);
        }
    }

    /**
     * @param string[] $strings
     * @throws UplinkException
     */
    public function writeFromIterator(iterable $strings): void
    {
        foreach ($strings as $string) {
            $this->write($string);
        }
    }

    /**
     * @throws UplinkException
     */
    public function commit(): void
    {
        $pError = $this->ffi->upload_commit($this->cUpload);
        $scope = Scope::exit(fn() => $this->ffi->free_error($pError));

        Util::throwIfError($pError);
    }

    /**
     * @throws UplinkException
     */
    public function abort(): void
    {
        $pError = $this->ffi->upload_abort($this->cUpload);
        $scope = Scope::exit(fn() => $this->ffi->free_error($pError));

        Util::throwIfError($pError);
    }

    /**
     * @throws UplinkException
     */
    public function info(): ObjectInfo
    {
        $objectResult = $this->ffi->upload_info($this->cUpload);
        $scope = Scope::exit(fn() => $this->ffi->free_object_result($objectResult));

        Util::throwIfErrorResult($objectResult);

        return ObjectInfo::fromCStruct($objectResult->object, true, true);
    }

    /**
     * @param string[] $customMetadata hash map
     *
     * @throws UplinkException
     */
    public function setCustomMetadata(array $customMetadata): void
    {
        $count = count($customMetadata);

        $entriesType = FFI::arrayType($this->ffi->type('CustomMetadataEntry'), [$count]);
        $cEntries = $this->ffi->new($entriesType, false);
        $scope = Scope::exit(fn() => FFI::free($cEntries));

        $i = 0;
        foreach ($customMetadata as $key => $value) {
            $cEntry = $cEntries[$i];

            $cKey = Util::createCString($key, $scope);
            $cValue = Util::createCString($value, $scope);

            $cEntry->key = $cKey;
            $cEntry->key_length = strlen($key);
            $cEntry->value = $cValue;
            $cEntry->value_length = strlen($value);

            $i++;
        }

        $cCustomMetadata = $this->ffi->new('CustomMetadata');
        $cCustomMetadata->count = $count;
        $cCustomMetadata->entries = $cEntries;

        $pError = $this->ffi->upload_set_custom_metadata($this->cUpload, $cCustomMetadata);
        $scope->onExit(fn() => $this->ffi->free_error($pError));

        Util::throwIfError($pError);
    }
}
