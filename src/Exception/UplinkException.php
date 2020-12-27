<?php

namespace Storj\Uplink\Exception;

use Exception;

abstract class UplinkException extends Exception
{
    /**
     * extracted from header files
     */
    private const ERROR_CODES = [
        0x02 => InternalException::class,
        0x03 => Cancelled::class,
        0x04 => InvalidHandle::class,
        0x05 => TooManyRequests::class,
        0x06 => BandwidthLimitExceeded::class,

        0x10 => Bucket\InvalidBucketName::class,
        0x11 => Bucket\BucketAlreadyExists::class,
        0x12 => Bucket\BucketNotEmpty::class,
        0x13 => Bucket\BucketNotFound::class,

        0x20 => Object\InvalidObjectKey::class,
        0x21 => Object\ObjectNotFound::class,
        0x22 => Object\UploadDone::class,
    ];

    /**
     * @throws UplinkException
     */
    public static function throw(string $message, int $code): void
    {
        $class = self::ERROR_CODES[$code] ?? UnknownErrorCode::class;

        throw new $class($message, $code);
    }
}
