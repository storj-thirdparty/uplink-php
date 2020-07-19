<?php

namespace Storj\Uplink\StreamResource;

use Storj\Uplink\CursoredDownload;

/**
 * Implementation of https://www.php.net/manual/en/class.streamwrapper.php
 */
class ReadProtocol
{
    protected const PROTOCOL = 'storj';

    /**
     * This is set by PHP when opening the stream.
     * It can be read with stream_context_get_options().
     *
     * @var resource
     */
    protected $context;

    protected CursoredDownload $download;

    /**
     * Convert a Storj download into a resource for usage with fread() etc
     *
     * @return resource
     */
    public static function createReadResource(CursoredDownload $download)
    {
        try {
            stream_wrapper_register(self::PROTOCOL, self::class);

            $context = stream_context_create([
                self::PROTOCOL => [
                    'download' => $download,
                ]
            ]);

            return fopen(self::PROTOCOL . '://', 'r', false, $context);
        } finally {
            stream_wrapper_unregister(self::PROTOCOL);
        }
    }

    protected function setDownloadFromContext(): void
    {
        $context = stream_context_get_options($this->context);

        $this->download = $context[self::PROTOCOL]['download'];
    }

    /*
     * Below are callbacks for PHP.
     * You should not call these methods directly.
     */

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->setDownloadFromContext();
        return true;
    }

    public function stream_read(int $count): string
    {
        return $this->download->read($count);
    }

    public function stream_eof(): bool
    {
        return $this->download->isDone();
    }

    /**
     * necessary for @see stream_copy_to_stream()
     */
    public function stream_stat(): array
    {
        $systemMetaData = $this->download->info()->getSystemMetadata();

        return [
            'size' => $systemMetaData->getContentLength(),
            'mtime' => $systemMetaData->getCreated()->format('U'),
        ];
    }
}