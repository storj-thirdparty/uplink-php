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
     * FFI with libuplink.so and header files loaded
     *
     * It's recommended to use the same instance of FFI for all Uplink objects.
     * Different instances create incompatible objects.
     */
    private FFI $ffi;

    /**
     * @internal use Uplink::create()
     */
    public function __construct(FFI $ffi)
    {
        $this->ffi = $ffi;
    }

    public static function create(): self
    {
        $root = realpath(__DIR__ . '/..');

        $arch = strtolower(php_uname('m'));
        $os = str_replace(' ', '_', strtolower(php_uname('s')));
        $extension = strpos($os, 'windows') !== false ? 'dll' : 'so';

        $ffi = FFI::cdef(
            file_get_contents($root . '/build/uplink-php.h'),
            "{$root}/build/libuplink-{$arch}-{$os}.{$extension}"
        );

        return new self($ffi);
    }

    /**
     * Parse a serialized access grant string.
     *
     * This should be the main way to instantiate an access grant for opening a project.
     * @see requestAccessWithPassphrase.
     *
     * @param string $accessString base58 encoded $accessString
     *
     * @throws UplinkException
     */
    public function parseAccess(string $accessString): Access
    {
        $accessResult = $this->ffi->uplink_parse_access($accessString);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_access_result($accessResult));

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
     * @param string $satelliteAddress including id and port, e.g.:
     *     12L9ZFwhzVpuEKMUNUqkaTLGzwY9G24tbiigLiXpmZWKwmcNDDs@eu1.storj.io:7777
     *     12EayRS2V1kEsWESU9QMRseFhdxYxKicsiFmxrsLZHeLUtdps3S@us1.storj.io:7777
     *     121RTSDpyNZVcEU84Ticf2L1ntiuUimbWgfATz21tuvgk3vzoA6@ap1.storj.io:7777
     * @param string $apiKey
     * @param string $passphrase
     * @param Config|null $config
     *
     * @throws UplinkException
     */
    public function requestAccessWithPassphrase(
        string $satelliteAddress,
        string $apiKey,
        string $passphrase,
        ?Config $config = null
    ): Access
    {
        $scope = new Scope();
        if ($config) {
            $cConfig = $config->toCStruct($this->ffi, $scope);
            $accessResult = $this->ffi->uplink_config_request_access_with_passphrase(
                $cConfig,
                $satelliteAddress,
                $apiKey,
                $passphrase
            );
        } else {
            $accessResult = $this->ffi->uplink_request_access_with_passphrase($satelliteAddress, $apiKey, $passphrase);
        }
        $scope->onExit(fn() => $this->ffi->uplink_free_access_result($accessResult));

        Util::throwIfErrorResult($accessResult);

        return new Access(
            $this->ffi,
            $accessResult->access,
            $scope
        );
    }

    /**
     * Derives a salted encryption key for passphrase using the salt.
     *
     * This function is useful for deriving a salted encryption key for users when
     * implementing multitenancy in a single app bucket.
     */
    public function deriveEncryptionKey(string $passphrase, string $salt): EncryptionKey
    {
        $encryptionKeyResult = $this->ffi->uplink_derive_encryption_key($passphrase, $salt, strlen($salt));
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_encryption_key_result($encryptionKeyResult));

        Util::throwIfErrorResult($encryptionKeyResult);

        return new EncryptionKey(
            $encryptionKeyResult->encryption_key,
            $scope
        );
    }
}
