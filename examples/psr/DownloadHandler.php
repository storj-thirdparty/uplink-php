<?php

namespace App\Handler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Storj\Uplink\Access;
use Storj\Uplink\Exception\Object\ObjectNotFound;

/**
 * Minimal example to download a file using PHP-FIG standards.
 * Verified working in Mezzio.
 */
class DownloadHandler implements RequestHandlerInterface
{
    private Access $access;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(Access $access, ResponseFactoryInterface $responseFactory)
    {
        $this->access = $access;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $filename = basename($path); // or get a route parameter via $request->getAttribute()

        $project = $this->access->openProject();
        try {
            $download = $project->downloadObject('psr-uploads', $filename);
        } catch (ObjectNotFound $e) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('<p>file not found</p>');
            return $response;
        }

        $response = $this->responseFactory->createResponse(200);
        $response = $response->withBody($download->toPsrStream());

        $contentType = $download->info()->getCustomMetadata()['Content-Type'] ?? null;
        if ($contentType) {
            $response = $response->withHeader('Content-Type', $contentType);
        }

        return $response;
    }
}