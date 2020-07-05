<?php

namespace Storj\Uplink\Internal;

use FFI;
use FFI\CData;
use Generator;
use Storj\Uplink\Exception\IOException;
use Storj\Uplink\Exception\UplinkException;

/**
 * @internal
 */
class Util
{
    /**
     * Throw exception if a result pair returned by a golang function has the error set
     *
     * @param CData $golangResult
     * @throws UplinkException
     */
    public static function throwIfErrorResult(CData $golangResult): void
    {
        $pError = $golangResult->error;
        if ($pError === null) {
            return;
        }

        self::throwError($pError[0]);
    }

    /**
     * @throws UplinkException
     */
    public static function throwError(CData $error): void
    {
        $message = 'Golang error without message';
        if ($error->message !== null) {
            $message = FFI::string($error->message);
        }

        UplinkException::throw($message, $error->code);
    }

    /**
     * @param CData|null $pError of C type Error*
     * @throws UplinkException
     */
    public static function throwIfError(?CData $pError): void
    {
        if ($pError !== null) {
            self::throwError($pError[0]);
        }
    }

    /**
     * Create a null-terminated char*
     * The memory must be unmanaged (owned=false) so it can be assigned to C struct members
     * Therefore also make a scope to ensure it is freed
     */
    public static function createCString(string $content): array
    {
        $content .= "\0";
        $length = strlen($content);

        $type = FFI::arrayType(FFI::type('char'), [$length]);
        $pChar = FFI::new($type, false);
        $scope = Scope::exit(fn() => FFI::free($pChar));

        FFI::memcpy($pChar, $content, $length);

        return [$pChar, $scope];
    }

    /**
     * Create a char*
     * Memory is managed by PHP reference counting (owned=true)
     */
    public static function createBuffer(int $length): CData
    {
        $type = FFI::arrayType(FFI::type('char'), [$length]);
        return FFI::new($type);
    }

    /**
     * @param mixed $var
     * @throws UplinkException
     */
    public static function assertResource($var): void
    {
        if (!is_resource($var)) {
            throw new IOException('Expected a resource, got ' . Util::printType($var));
        }
    }

    public static function printType($var): string
    {
        if (is_object($var)) {
            return get_class($var);
        }

        if (is_resource($var)) {
            return get_resource_type($var);
        }

        if (is_bool($var)) {
            if ($var) {
                return 'TRUE';
            } else {
                return 'FALSE';
            }
        }

        return gettype($var);
    }

    /**
     * Helper to replace
     *     for ($i = 0; $i < $n; $i++)
     * by
     *     foreach (Util::it($n) as $i)
     *
     * @return int[]|Generator<int>
     */
    public static function it(int $count): Generator
    {
        $i = 0;

        while ($i < $count) {
            yield $i;
            $i += 1;
        }
    }
}
