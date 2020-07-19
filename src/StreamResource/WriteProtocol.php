<?php

namespace Storj\Uplink\StreamResource;

use Storj\Uplink\Upload;

/**
 * Implementation of https://www.php.net/manual/en/class.streamwrapper.php
 */
class WriteProtocol
{
    protected const PROTOCOL = 'storj';

    /**
     * This is set by PHP when opening the stream.
     * It can be read with stream_context_get_options().
     *
     * @var resource
     */
    protected $context;

    protected Upload $upload;

    /**
     * Convert a Storj upload into resource for usage with fwrite() and fclose() etc
     *
     * @return resource
     */
    public static function createWriteResource(Upload $upload)
    {
        try {
            stream_wrapper_register(self::PROTOCOL, self::class);

            $context = stream_context_create([
                self::PROTOCOL => [
                    'upload' => $upload,
                ]
            ]);

            return fopen(self::PROTOCOL . '://', 'w', false, $context);
        } finally {
            stream_wrapper_unregister(self::PROTOCOL);
        }
    }

    protected function setUploadFromContext(): void
    {
        $context = stream_context_get_options($this->context);

        $this->upload = $context[self::PROTOCOL]['upload'];
    }

    /*
     * Below are callbacks for PHP.
     * You should not call these methods directly.
     */

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->setUploadFromContext();
        return true;
    }

    public function stream_write(string $data): int
    {
        $this->upload->write($data);

        return strlen($data);
    }

    public function stream_close(): void
    {
        $this->upload->commit();
    }
}