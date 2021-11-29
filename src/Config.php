<?php

namespace Storj\Uplink;

use FFI;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * Parameters when connecting
 */
class Config
{
    private string $userAgent = 'uplink-php';

    /**
     * How long the client should wait for establishing a connection to peers.
     */
    private int $dialTimeoutMilliseconds = 10_000;

    /**
     * Where to save data during downloads to use less memory.
     * Use "inmemory" to store in-memory
     */
    private string $tempDirectory;

    public function __construct()
    {
        $this->tempDirectory = sys_get_temp_dir();
    }

    public function withUserAgent(string $userAgent): self
    {
        $clone = clone $this;
        $clone->userAgent = $userAgent;
        return $clone;
    }

    public function withDialTimeoutMilliseconds(int $dialTimeoutMilliseconds): self
    {
        $clone = clone $this;
        $clone->dialTimeoutMilliseconds = $dialTimeoutMilliseconds;
        return $clone;
    }

    public function withTempDirectory(string $tempDirectory): self
    {
        $clone = clone $this;
        $clone->tempDirectory = $tempDirectory;
        return $clone;
    }

    /**
     * @internal
     */
    public function toCStruct(FFI $ffi, Scope $scope): FFI\CData
    {
        $cUserAgent = Util::createCString($this->userAgent, $scope);
        $cTempDirectory = Util::createCString($this->tempDirectory, $scope);

        $cConfig = $ffi->new('UplinkConfig');
        $cConfig->user_agent = $cUserAgent;
        $cConfig->dial_timeout_milliseconds = $this->dialTimeoutMilliseconds;
        $cConfig->temp_directory = $cTempDirectory;

        return $cConfig;
    }
}
