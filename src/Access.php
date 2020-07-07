<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Exception\UplinkException;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * Access grant
 */
class Access
{
    /**
     * With libuplink.so and header files loaded
     */
    private FFI $ffi;

    /**
     * The associated C struct of type Access
     */
    private CData $cAccess;

    /**
     * To free memory when this object is done
     */
    private Scope $scope;

    public function __construct(
        FFI $ffi,
        CData $cAccess,
        Scope $scope
    ) {
        $this->ffi = $ffi;
        $this->cAccess = $cAccess;
        $this->scope = $scope;
    }

    /**
     * @throws UplinkException
     */
    public function openProject(?Config $config = null): Project
    {
        $scope = new Scope();
        if ($config) {
            $cConfig = $config->toCStruct($this->ffi, $scope);
            $projectResult = $this->ffi->config_open_project($cConfig, $this->cAccess);
            unset($configScope);
        } else {
            $projectResult = $this->ffi->open_project($this->cAccess);
        }
        $scope->onExit(fn() => $this->ffi->free_project_result($projectResult));

        Util::throwIfErrorResult($projectResult);

        return new Project(
            $this->ffi,
            $projectResult->project,
            $scope
        );
    }

    /**
     * Create base58 encoded string for later use with @see Uplink::parseAccess()
     *
     * @throws UplinkException
     */
    public function serialize(): string
    {
        $stringResult = $this->ffi->access_serialize($this->cAccess);
        $scope = Scope::exit(fn() => $this->ffi->free_string_result($stringResult));

        Util::throwIfErrorResult($stringResult);

        return FFI::string($stringResult->string);
    }

    /**
     * @param Permission $permission
     * @param SharePrefix[] $sharePrefixes
     *
     * @throws UplinkException
     */
    public function share(Permission $permission, array $sharePrefixes): Access
    {
        $scope = new Scope();
        $cPermission = $permission->toCStruct($this->ffi);
        $cSharePrefixes = SharePrefix::toCStructArray($this->ffi, $sharePrefixes, $scope);

        $accessResult = $this->ffi->access_share($this->cAccess, $cPermission, $cSharePrefixes, count($sharePrefixes));
        $scope->onExit(fn() => $this->ffi->free_access_result($accessResult));
        unset($prefixScope);

        Util::throwIfErrorResult($accessResult);

        return new Access(
            $this->ffi,
            $accessResult->access,
            $scope
        );
    }
}
