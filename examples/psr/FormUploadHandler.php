<?php

namespace App\Handler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Storj\Uplink\Access;

/**
 * Minimal example to upload a file via HTML form using PHP-FIG standards.
 * Verified working in Mezzio.
 */
class FormUploadHandler implements RequestHandlerInterface
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
        switch ($request->getMethod()) {
            case 'GET':
                return $this->handleGet($request);
            case 'POST':
                return $this->handlePost($request);
            default:
                return $this->responseFactory->createResponse(405);
        }
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write(<<<HTML
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="files[]" multiple />
                    <button>Submit</button>
                </form>
            HTML
        );

        return $response;
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $project = $this->access->openProject();
        $project->ensureBucket('psr');

        /** @var $uploadedFile UploadedFileInterface */
        foreach ($request->getUploadedFiles() as $uploadedFile) {
            $upload = $project->uploadObject('psr', $uploadedFile->getClientFilename());

            $upload->writeFromPsrStream($uploadedFile->getStream());

            if ($uploadedFile->getClientMediaType()) {
                $upload->setCustomMetadata([
                    'Content-Type' => $uploadedFile->getClientMediaType(),
                ]);
            }

            $upload->commit();
        }

        $nFiles = count($request->getUploadedFiles());
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write("
            <p>Your {$nFiles} files haves been uploaded to a global decentralized network.</p>
        ");

        return $response;
    }
}
