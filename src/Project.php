<?php

namespace Storj\Uplink;

use DateTimeImmutable;
use FFI;
use FFI\CData;
use Generator;
use Storj\Uplink\Exception\UplinkException;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

class Project
{
    /**
     * With libuplink.so and header files loaded
     */
    private FFI $ffi;

    /**
     * The associated C struct of type Project
     */
    private CData $cProject;

    /**
     * To free memory when this object is done
     */
    private Scope $scope;

    public function __construct(FFI $ffi, CData $cProject, Scope $scope)
    {
        $this->ffi = $ffi;
        $this->cProject = $cProject;
        $this->scope = $scope;
    }

    /**
     * @throws UplinkException
     */
    public function statBucket(string $bucketName): BucketInfo
    {
        $bucketResult = $this->ffi->stat_bucket($this->cProject, $bucketName);
        $scope = Scope::exit(fn() => $this->ffi->free_bucket_result($bucketResult));

        Util::throwIfErrorResult($bucketResult);

        $bucketType = $this->ffi->type('Bucket');
        $bucket = $this->ffi->cast($bucketType, $bucketResult->bucket[0]);

        return new BucketInfo(
            FFI::string($bucket->name),
            DateTimeImmutable::createFromFormat('U', $bucket->created)
        );
    }

    /**
     * @throws UplinkException
     */
    public function createBucket(string $bucketName): BucketInfo
    {
        $bucketResult = $this->ffi->create_bucket($this->cProject, $bucketName);
        $scope = Scope::exit(fn() => $this->ffi->free_bucket_result($bucketResult));

        Util::throwIfErrorResult($bucketResult);

        $bucketType = $this->ffi->type('Bucket');
        $bucket = $this->ffi->cast($bucketType, $bucketResult->bucket[0]);

        return new BucketInfo(
            FFI::string($bucket->name),
            DateTimeImmutable::createFromFormat('U', $bucket->created)
        );
    }

    /**
     * @throws UplinkException
     */
    public function ensureBucket(string $bucketName): BucketInfo
    {
        $bucketResult = $this->ffi->ensure_bucket($this->cProject, $bucketName);
        $scope = Scope::exit(fn() => $this->ffi->free_bucket_result($bucketResult));

        Util::throwIfErrorResult($bucketResult);

        $bucketType = $this->ffi->type('Bucket');
        $buck = $this->ffi->cast($bucketType, $bucketResult->bucket[0]);

        return new BucketInfo(
            FFI::string($buck->name),
            DateTimeImmutable::createFromFormat('U', $buck->created)
        );
    }

    /**
     * @throws UplinkException
     */
    public function deleteBucket(string $bucketName): BucketInfo
    {
        $bucketResult = $this->ffi->delete_bucket($this->cProject, $bucketName);
        $scope = Scope::exit(fn() => $this->ffi->free_bucket_result($bucketResult));

        Util::throwIfErrorResult($bucketResult);

        $bucketType = $this->ffi->type('Bucket');
        $buck = $this->ffi->cast($bucketType, $bucketResult->bucket[0]);

        return new BucketInfo(
            FFI::string($buck->name),
            DateTimeImmutable::createFromFormat('U', $buck->created)
        );
    }

    /**
     * @param string|null $cursor bucket name after which to resume iteration
     * @return BucketInfo[]|Generator<BucketInfo>
     * @throws UplinkException
     */
    public function listBuckets(?string $cursor = null): Generator
    {
        $scope = new Scope();
        $listBucketOptions = $this->ffi->new('ListBucketsOptions');
        if ($cursor) {
            $pChar = Util::createCString($cursor, $scope);

            $listBucketOptions->cursor = $pChar;
        }

        $pBucketIterator = $this->ffi->list_buckets($this->cProject, FFI::addr($listBucketOptions));
        $scope->onExit(fn() => $this->ffi->free_bucket_iterator($pBucketIterator));

        while ($this->ffi->bucket_iterator_next($pBucketIterator)) {
            $pBucket = $this->ffi->bucket_iterator_item($pBucketIterator);
            $innerScope = Scope::exit(fn() => $this->ffi->free_bucket($pBucket));

            yield new BucketInfo(
                FFI::string($pBucket[0]->name),
                DateTimeImmutable::createFromFormat('U', $pBucket[0]->created)
            );
        }

        // how to trigger this?
        $pError = $this->ffi->bucket_iterator_err($pBucketIterator);
        $scope->onExit(fn() => $this->ffi->free_error($pError));

        Util::throwIfError($pError);
    }

    /**
     * @throws UplinkException
     */
    public function downloadObject(string $bucketName, string $objectKey, ?DownloadOptions $downloadOptions = null): Download
    {
        $pDownloadOptions = null;
        if ($downloadOptions) {
            $cDownloadOptions = $downloadOptions->toCStruct($this->ffi);
            $pDownloadOptions = FFI::addr($cDownloadOptions);
        }

        $downloadResult = $this->ffi->download_object(
            $this->cProject,
            $bucketName,
            $objectKey,
            $pDownloadOptions
        );
        $scope = Scope::exit(fn() => $this->ffi->free_download_result($downloadResult));

        Util::throwIfErrorResult($downloadResult);

        return new Download(
            $this->ffi,
            $downloadResult->download,
            $scope
        );
    }

    /**
     * Start an upload
     *
     * @throws UplinkException
     */
    public function uploadObject(string $bucketName, string $objectKey, ?DateTimeImmutable $expires = null): Upload
    {
        $uploadOptions = null;
        if ($expires) {
            $uploadOptions = $this->ffi->new('UploadOptions');
            $uploadOptions->expires = $expires->format('U');
        }

        $uploadResult = $this->ffi->upload_object($this->cProject, $bucketName, $objectKey, $uploadOptions);
        $scope = Scope::exit(fn() => $this->ffi->free_upload_result($uploadResult));

        Util::throwIfErrorResult($uploadResult);

        return new Upload($this->ffi, $uploadResult->upload, $scope);
    }

    /**
     * @throws UplinkException
     */
    public function deleteObject(string $bucketName, string $objectKey): void
    {
        $objectResult = $this->ffi->delete_object($this->cProject, $bucketName, $objectKey);
        Scope::exit(fn() => $this->ffi->free_object_result($objectResult));

        Util::throwIfErrorResult($objectResult);

        // TODO: there seems to be some garbage in this object
        // But maybe it is only the metadata
        //return ObjectInfo::fromCStruct($objectResult->object);
    }

    /**
     * @param string $bucketName
     * @return ObjectInfo[]|Generator<ObjectInfo>
     */
    public function listObjects(string $bucketName, ?ListObjectsOptions $listObjectsOptions = null): Generator
    {
        $scope = new Scope();
        $pListObjectOptions = null;

        // ListObjectsOptions is technically optional to the C interface
        // but we need to know the options in order to parse the response
        $listObjectsOptions = $listObjectsOptions ?? new ListObjectsOptions();
        $cListObjectOptions = $listObjectsOptions->toCStruct($this->ffi, $scope);
        $pListObjectOptions = FFI::addr($cListObjectOptions);

        $pObjectIterator = $this->ffi->list_objects($this->cProject, $bucketName, $pListObjectOptions);
        $scope->onExit(fn() => $this->ffi->free_object_iterator($pObjectIterator));

        while($this->ffi->object_iterator_next($pObjectIterator)) {
            $pObject = $this->ffi->object_iterator_item($pObjectIterator);
            $innerScope = Scope::exit(fn() => $this->ffi->free_object($pObject));

            // Why do we do [0] here but not when dereferencing ObjectResult::object?
            yield ObjectInfo::fromCStruct(
                $pObject[0],
                $listObjectsOptions->includeSystemMetadata(),
                $listObjectsOptions->includeCustomMetadata()
            );
        }

        $pError = $this->ffi->object_iterator_err($pObjectIterator);
        $scope->onExit(fn() => $this->ffi->free_error($pError));

        Util::throwIfError($pError);
    }
}
