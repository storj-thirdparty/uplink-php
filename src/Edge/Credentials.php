<?php

namespace Storj\Uplink\Edge;

/**
 * Gateway credentials in S3 format
 */
class Credentials
{
    /**
     * Is also used in the linksharing url path
     */
    private string $accessKeyId;

    private string $secretKey;

    /**
     * Base HTTP(S) URL to the gateway
     */
    private string $endpoint;

    public function __construct(
        string $accessKeyId,
        string $secretKey,
        string $endpoint
    ) {
        $this->accessKeyId = $accessKeyId;
        $this->secretKey = $secretKey;
        $this->endpoint = $endpoint;
    }

    public function getAccessKeyId(): string
    {
        return $this->accessKeyId;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
