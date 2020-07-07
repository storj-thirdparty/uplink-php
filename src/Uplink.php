<?php

namespace Storj\Uplink;

use FFI;
use Storj\Uplink\Exception\UplinkException;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * Entry point of the Storj Uplink library
 */
class Uplink
{
    /**
     * With libuplink.so and header files loaded
     */
    private FFI $ffi;

    public function __construct(FFI $ffi)
    {
        $this->ffi = $ffi;
    }

    public static function create(): self
    {
        $root = realpath(__DIR__ . '/..');

        $ffi = FFI::cdef(
            file_get_contents($root . '/build/uplink-php.h'),
            $root . '/build/libuplink.so'
        );

        return new self($ffi);
    }

    /**
     * Parse a serialized access grant string.
     *
     * This should be the main way to instantiate an access grant for opening a project.
     * @see requestAccessWithPassphrase.
     *
     * @param string base58 encoded $accessString
     *
     * @throws UplinkException
     */
    public function parseAccess(string $accessString): Access
    {
        $accessResult = $this->ffi->parse_access($accessString);
        $scope = Scope::exit(fn() => $this->ffi->free_access_result($accessResult));

        Util::throwIfErrorResult($accessResult);

        return new Access(
            $this->ffi,
            $accessResult->access,
            $scope
        );
    }

    /**
     * Generate a new access grant using a passhprase.
     * It must talk to the Satellite provided to get a project-based salt for
     * deterministic key derivation.
     *
     * This is a CPU-heavy function that uses a password-based key derivation function
     * (Argon2). This should be a setup-only step. Most common interactions with the library
     * should be using a serialized access grant through ->parseAccess().
     *
     * @param string $satellite_address including port, e.g.:
     *     us-central-1.tardigrade.io:7777
     *     europe-west-1.tardigrade.io:7777
     *     asia-east-1.tardigrade.io:7777
     * @param string $api_key
     * @param string $passphrase
     * @param Config|null $config
     *
     * @throws UplinkException
     */
    function requestAccessWithPassphrase(
        string $satellite_address,
        string $api_key,
        string $passphrase,
        ?Config $config = null
    ): Access
    {
        $scope = new Scope();
        if ($config) {
            $cConfig = $config->toCStruct($this->ffi, $scope);
            $accessResult = $this->ffi->config_request_access_with_passphrase(
                $cConfig,
                $satellite_address,
                $api_key,
                $passphrase
            );
            unset($configScope);
        } else {
            $accessResult = $this->ffi->request_access_with_passphrase($satellite_address, $api_key, $passphrase);
        }
        $scope->onExit(fn() => $this->ffi->free_access_result($accessResult));

        Util::throwIfErrorResult($accessResult);

        return new Access(
            $this->ffi,
            $accessResult->access,
            $scope
        );
    }
}
