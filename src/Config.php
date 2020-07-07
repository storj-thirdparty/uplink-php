<?php

namespace Storj\Uplink;

use FFI;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * Optional parameters when connecting
 */
class Config
{
    private string $userAgent;

    /**
     * Timeout to establish connection to peers
     */
    private int $dialTimeoutMilliseconds;

    /**
     * Where to save data during downloads to use less memory.
     * Use "inmemory" to store in-memory
     */
    private string $tempDirectory;

    public function __construct(?string $userAgent, int $dialTimeoutMilliseconds, ?string $tempDirectory)
    {
        $this->userAgent = $userAgent ?? 'uplink-php';
        $this->dialTimeoutMilliseconds = $dialTimeoutMilliseconds;
        $this->tempDirectory = $tempDirectory ?? sys_get_temp_dir();
    }

    public function toCStruct(FFI $ffi, Scope $scope): FFI\CData
    {
        $cUserAgent = Util::createCString($this->userAgent, $scope);
        $cTempDirectory = Util::createCString($this->tempDirectory, $scope);

        $cConfig = $ffi->new('Config');
        $cConfig->user_agent = $cUserAgent;
        $cConfig->dial_timeout_milliseconds = $this->dialTimeoutMilliseconds;
        $cConfig->temp_directory = $cTempDirectory;

        return $cConfig;
    }
}
