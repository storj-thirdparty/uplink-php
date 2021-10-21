<?php

namespace Storj\Uplink\Edge;

use FFI;
use Storj\Uplink\Access;
use Storj\Uplink\Exception\Edge\DialFailed;
use Storj\Uplink\Exception\Edge\RegisterAccessFailed;
use Storj\Uplink\Exception\UplinkException;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;
use Storj\Uplink\Permission;

class Edge
{
    /**
     * With libuplink.so and header files loaded
     */
    private FFI $ffi;

    public function __construct(FFI $ffi)
    {
        $this->ffi = $ffi;
    }

    /**
     * Get credentials for the Storj-hosted Gateway and linkshare service.
     *
     * All files accessible under the Access are then also accessible via those services.
     *
     * If you call this function a lot, and the use case allows it,
     * please limit the lifetime of the credentials
     * by setting @see Permission::$notAfter when creating the Access.
     *
     * @throws DialFailed in case of network errors
     * @throws RegisterAccessFailed in case of server errors
     */
    public function registerAccess(
        Config $config,
        Access $access,
        bool $isPublic = false
    ): Credentials
    {
        $scope = new Scope();

        $cOptions = $this->ffi->new('EdgeRegisterAccessOptions');
        $cOptions->is_public = $isPublic;

        $cCredentialsResult = $this->ffi->edge_register_access(
            $config->toCStruct($this->ffi, $scope),
            $access->getNativeAccess(),
            FFI::addr($cOptions),
        );

        $scope->onExit(
            fn() => $this->ffi->edge_free_credentials_result($cCredentialsResult)
        );

        Util::throwIfErrorResult($cCredentialsResult);

        $cCredentials = $cCredentialsResult->credentials;

        return new Credentials(
            FFI::string($cCredentials->access_key_id),
            FFI::string($cCredentials->secret_key),
            FFI::string($cCredentials->endpoint),
        );
    }

    /**
     * JoinShareURL creates a linksharing URL from parts.
     * The existence or accessibility of the target is not checked, it might not exist or be inaccessible.
     *
     * @param string $baseUrl Linksharing service, e.g. https://link.storjshare.io
     * @param string $accessKeyId Can be obtained by calling RegisterAccess. It must be associated with public visibility.
     * @param string $bucket Optional, leave it blank to share the entire project.
     * @param string $key Optional, if empty shares the entire bucket. It can also be a prefix, in which case it must end with a "/".
     * @param bool $raw Whether to get a direct link to the data instead of a landing page
     *
     * @return string example https://link.storjshare.io/s/l5pucy3dmvzxgs3fpfewix27l5pq/mybucket/myprefix/myobject
     *
     * @throws UplinkException
     */
    public function joinShareUrl(
        string $baseUrl,
        string $accessKeyId,
        string $bucket = "",
        string $key = "",
        bool $raw = false
    ): string
    {
        $cOptions = $this->ffi->new('EdgeShareURLOptions');
        $cOptions->raw = $raw;

        $scope = new Scope();
        $cStringResult = $this->ffi->edge_join_share_url(
            $baseUrl,
            $accessKeyId,
            $bucket,
            $key,
            FFI::addr($cOptions)
        );

        $scope->onExit(
            fn() => $this->ffi->uplink_free_string_result($cStringResult)
        );

        Util::throwIfErrorResult($cStringResult);

        return FFI::string($cStringResult->string);
    }
}
