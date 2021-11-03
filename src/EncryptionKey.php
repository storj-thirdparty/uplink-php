<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Internal\Scope;

class EncryptionKey
{
    /**
     * The associated C struct of type UplinkEncryptionKey
     */
    private CData $cEncryptionKey;

    /**
     * To free memory when this object is done
     */
    private Scope $scope;

    /**
     * @internal
     */
    public function __construct(CData $cEncryptionKey, Scope $scope)
    {
        $this->cEncryptionKey = $cEncryptionKey;
        $this->scope = $scope;
    }

    /**
     * @internal not memory-safe
     */
    public function getCStruct(): CData
    {
        return $this->cEncryptionKey;
    }
}
