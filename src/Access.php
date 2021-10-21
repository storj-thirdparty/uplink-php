<?php

namespace Storj\Uplink;

use FFI;
use FFI\CData;
use Storj\Uplink\Exception\UplinkException;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * An Access Grant contains everything to access a project and specific buckets.
 * It includes a potentially-restricted API Key, a potentially-restricted set of
 * encryption information, and information about the Satellite responsible for
 * the project's metadata.
 */
class Access
{
    /**
     * With libuplink.so and header files loaded
     */
    private FFI $ffi;

    /**
     * The associated C struct of type UplinkAccess
     */
    private CData $cAccess;

    /**
     * To free memory when this object is done
     */
    private Scope $scope;

    /**
     * @internal
     */
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
     * Open project with the specific access grant
     *
     * @throws UplinkException
     */
    public function openProject(?Config $config = null): Project
    {
        if ($config === null) {
            // Initialize default config so that we set a user-agent.
            $config = new Config();
        }
        $innerScope = new Scope();
        $cConfig = $config->toCStruct($this->ffi, $innerScope);
        $projectResult = $this->ffi->uplink_config_open_project($cConfig, $this->cAccess);

        $scope = Scope::exit(fn() => $this->ffi->uplink_free_project_result($projectResult));

        Util::throwIfErrorResult($projectResult);

        return new Project(
            $this->ffi,
            $projectResult->project,
            $scope
        );
    }

    /**
     * Return the satellite node URL for this access grant
     *
     * @throws UplinkException
     */
    public function satteliteAddress(): string
    {
        $stringResult = $this->ffi->uplink_access_satellite_address($this->cAccess);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_string_result($stringResult));

        Util::throwIfErrorResult($stringResult);

        return FFI::string($stringResult->string);
    }

    /**
     * Create base58 encoded string for later use with @see Uplink::parseAccess() or other tools
     *
     * @throws UplinkException
     */
    public function serialize(): string
    {
        $stringResult = $this->ffi->uplink_access_serialize($this->cAccess);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_string_result($stringResult));

        Util::throwIfErrorResult($stringResult);

        return FFI::string($stringResult->string);
    }

    /**
     * Share creates a new access grant with specific permissions.
     *
     * Access grants can only have their existing permissions restricted,
     * and the resulting access grant will only allow for the intersection
     * of all previous Share calls in the access grant construction chain.
     *
     * Prefixes, if provided, restrict the access grant (and internal encryption information)
     * to only contain enough information to allow access to just those object key prefixes.
     *
     * @param Permission $permission
     * @param SharePrefix ...$sharePrefixes
     *
     * @return Access
     * @throws UplinkException
     */
    public function share(Permission $permission, SharePrefix ...$sharePrefixes): Access
    {
        $scope = new Scope();
        $cPermission = $permission->toCStruct($this->ffi);
        $cSharePrefixes = SharePrefix::toCStructArray($this->ffi, $sharePrefixes, $scope);

        $accessResult = $this->ffi->uplink_access_share($this->cAccess, $cPermission, $cSharePrefixes, count($sharePrefixes));
        $scope->onExit(fn() => $this->ffi->uplink_free_access_result($accessResult));

        Util::throwIfErrorResult($accessResult);

        return new Access(
            $this->ffi,
            $accessResult->access,
            $scope
        );
    }

    /**
     * Override the root encryption key for the prefix in bucket with encryptionKey.
     *
     * This function is useful for overriding the encryption key in user-specific
     * access grants when implementing multitenancy in a single app bucket.
     *
     * Looks like this mutates the underlying UplinkAccess struct
     *
     * @throws UplinkException
     */
    public function overrideEncryptionKey(string $bucket, string $prefix, EncryptionKey $encryptionKey): void
    {
        $pError = $this->ffi->uplink_access_override_encryption_key(
            $this->cAccess,
            $bucket,
            $prefix,
            $encryptionKey->getCStruct()
        );

        $scope = Scope::exit(fn() => $this->ffi->uplink_free_error($pError));
        Util::throwIfError($pError);
    }

    /**
     * @internal
     */
    public function getNativeAccess(): FFI\CData
    {
        return $this->cAccess;
    }
}
