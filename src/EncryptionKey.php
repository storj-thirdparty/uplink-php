<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Internal\Scope;

class EncryptionKey
{
    /**
     * With libuplink.so and header files loaded
     */
    private FFI $ffi;

    /**
     * The associated C struct of type UplinkEncryptionKey
     */
    private CData $cEncryptionKey;

    /**
     * To free memory when this object is done
     */
    private Scope $scope;

    public function __construct(FFI $ffi, CData $cEncryptionKey, Scope $scope)
    {
        $this->ffi = $ffi;
        $this->cEncryptionKey = $cEncryptionKey;
        $this->scope = $scope;
    }

    /**
     * @internal unsafe
     */
    public function getCStruct(): CData
    {
        return $this->cEncryptionKey;
    }
}
