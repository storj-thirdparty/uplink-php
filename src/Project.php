<?php

namespace Storj\Uplink;

use DateTimeImmutable;
use FFI;
use FFI\CData;
use Generator;
use Storj\Uplink\Exception\Bucket\BucketAlreadyExists;
use Storj\Uplink\Exception\Bucket\BucketNotEmpty;
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
     * The associated C struct of type UplinkProject
     */
    private CData $cProject;

    /**
     * To free memory when this object is done
     */
    private Scope $scope;

    /**
     * @internal
     */
    public function __construct(FFI $ffi, CData $cProject, Scope $scope)
    {
        $this->ffi = $ffi;
        $this->cProject = $cProject;
        $this->scope = $scope;
    }

    /**
     * Fetch information about a bucket
     *
     * @throws UplinkException
     */
    public function statBucket(string $bucketName): BucketInfo
    {
        $bucketResult = $this->ffi->uplink_stat_bucket($this->cProject, $bucketName);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_bucket_result($bucketResult));

        Util::throwIfErrorResult($bucketResult);

        $bucketType = $this->ffi->type('UplinkBucket');
        $cBucket = $this->ffi->cast($bucketType, $bucketResult->bucket[0]);

        return new BucketInfo($cBucket);
    }

    /**
     * @throws BucketAlreadyExists if it already exists
     * @throws UplinkException
     */
    public function createBucket(string $bucketName): BucketInfo
    {
        $bucketResult = $this->ffi->uplink_create_bucket($this->cProject, $bucketName);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_bucket_result($bucketResult));

        Util::throwIfErrorResult($bucketResult);

        $bucketType = $this->ffi->type('UplinkBucket');
        $cBucket = $this->ffi->cast($bucketType, $bucketResult->bucket[0]);

        return new BucketInfo($cBucket);
    }

    /**
     * Check that a bucket exists and create a new one if it doesn't.
     *
     * @throws UplinkException
     */
    public function ensureBucket(string $bucketName): BucketInfo
    {
        $bucketResult = $this->ffi->uplink_ensure_bucket($this->cProject, $bucketName);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_bucket_result($bucketResult));

        Util::throwIfErrorResult($bucketResult);

        $bucketType = $this->ffi->type('UplinkBucket');
        $cBucket = $this->ffi->cast($bucketType, $bucketResult->bucket[0]);

        return new BucketInfo($cBucket);
    }

    /**
     * @throws BucketNotEmpty if there are objects in the bucket
     * @throws UplinkException
     */
    public function deleteBucket(string $bucketName): BucketInfo
    {
        $bucketResult = $this->ffi->uplink_delete_bucket($this->cProject, $bucketName);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_bucket_result($bucketResult));

        Util::throwIfErrorResult($bucketResult);

        $bucketType = $this->ffi->type('UplinkBucket');
        $cBucket = $this->ffi->cast($bucketType, $bucketResult->bucket[0]);

        return new BucketInfo($cBucket);
    }

    /**
     * @throws UplinkException
     */
    public function deleteBucketWithObjects(string $bucketName): BucketInfo
    {
        $bucketResult = $this->ffi->uplink_delete_bucket_with_objects($this->cProject, $bucketName);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_bucket_result($bucketResult));

        Util::throwIfErrorResult($bucketResult);

        $bucketType = $this->ffi->type('UplinkBucket');
        $cBucket = $this->ffi->cast($bucketType, $bucketResult->bucket[0]);

        return new BucketInfo($cBucket);
    }

    /**
     * @param string|null $cursor bucket name after which to resume iteration
     * @return BucketInfo[]|Generator<BucketInfo>
     * @throws UplinkException
     */
    public function listBuckets(?string $cursor = null): Generator
    {
        $scope = new Scope();
        $listBucketOptions = $this->ffi->new('UplinkListBucketsOptions');
        if ($cursor) {
            $pChar = Util::createCString($cursor, $scope);

            $listBucketOptions->cursor = $pChar;
        }

        $pBucketIterator = $this->ffi->uplink_list_buckets($this->cProject, FFI::addr($listBucketOptions));
        $scope->onExit(fn() => $this->ffi->uplink_free_bucket_iterator($pBucketIterator));

        while ($this->ffi->uplink_bucket_iterator_next($pBucketIterator)) {
            $pBucket = $this->ffi->uplink_bucket_iterator_item($pBucketIterator);
            $innerScope = Scope::exit(fn() => $this->ffi->uplink_free_bucket($pBucket));

            yield new BucketInfo($pBucket[0]);
        }

        // how to trigger this?
        $pError = $this->ffi->uplink_bucket_iterator_err($pBucketIterator);
        $scope->onExit(fn() => $this->ffi->uplink_free_error($pError));

        Util::throwIfError($pError);
    }

    /**
     * Start a download from the specified key
     *
     * @throws UplinkException
     */
    public function downloadObject(string $bucketName, string $objectKey, ?DownloadOptions $downloadOptions = null): Download
    {
        $pDownloadOptions = null;
        if ($downloadOptions) {
            $cDownloadOptions = $downloadOptions->toCStruct($this->ffi);
            $pDownloadOptions = FFI::addr($cDownloadOptions);
        }

        $downloadResult = $this->ffi->uplink_download_object(
            $this->cProject,
            $bucketName,
            $objectKey,
            $pDownloadOptions
        );
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_download_result($downloadResult));

        Util::throwIfErrorResult($downloadResult);

        $cDownload = $downloadResult->download;

        $scope->onExit(
            function() use ($cDownload): void {
                $pError = $this->ffi->uplink_close_download($cDownload);
                Util::throwIfError($pError);
            }
        );

        return new Download(
            $this->ffi,
            $cDownload,
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
            $uploadOptions = $this->ffi->new('UplinkUploadOptions');
            $uploadOptions->expires = $expires->format('U');
        }

        $uploadResult = $this->ffi->uplink_upload_object($this->cProject, $bucketName, $objectKey, $uploadOptions);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_upload_result($uploadResult));

        Util::throwIfErrorResult($uploadResult);

        return new Upload($this->ffi, $uploadResult->upload, $scope);
    }

    /**
     * @throws UplinkException
     */
    public function deleteObject(string $bucketName, string $objectKey): ObjectInfo
    {
        $objectResult = $this->ffi->uplink_delete_object($this->cProject, $bucketName, $objectKey);
        $scope = Scope::exit(fn() => $this->ffi->uplink_free_object_result($objectResult));

        Util::throwIfErrorResult($objectResult);

        return new ObjectInfo($objectResult->object, true, true);
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

        $pObjectIterator = $this->ffi->uplink_list_objects($this->cProject, $bucketName, $pListObjectOptions);
        $scope->onExit(fn() => $this->ffi->uplink_free_object_iterator($pObjectIterator));

        while($this->ffi->uplink_object_iterator_next($pObjectIterator)) {
            $pObject = $this->ffi->uplink_object_iterator_item($pObjectIterator);
            $innerScope = Scope::exit(fn() => $this->ffi->uplink_free_object($pObject));

            // Why do we do [0] here but not when dereferencing ObjectResult::object?
            yield new ObjectInfo(
                $pObject[0],
                $listObjectsOptions->includeSystemMetadata(),
                $listObjectsOptions->includeCustomMetadata()
            );
        }

        $pError = $this->ffi->uplink_object_iterator_err($pObjectIterator);
        $scope->onExit(fn() => $this->ffi->uplink_free_error($pError));

        Util::throwIfError($pError);
    }
}
