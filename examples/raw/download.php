<?php

/**
 * Minimal example to download a file
 * Assumes the last part of the path is the file name
 *
 * Try it using the built-in webserver
 * `ACCESS=youraccesstring php -S 0.0.0.0:8080`
 * then visit http://localhost:8080/download.php
 */

use Storj\Uplink\Exception\Object\ObjectNotFound;
use Storj\Uplink\ListObjectsOptions;
use Storj\Uplink\Uplink;

ini_set('display_errors', 1);

require '../../vendor/autoload.php';

$project = Uplink::create()->parseAccess(getenv('ACCESS'))->openProject();
$project->ensureBucket('raw');

$filename = trim($_SERVER['PATH_INFO'] ?? '', '/');
if ($filename) {
    try {
        $download = $project->downloadObject('raw', $filename);
    } catch (ObjectNotFound $e) {
        http_response_code(404);
        echo "<p>File not found</p>";
        return;
    }

    $type = $download->info()->getCustomMetadata()['Content-Type'];
    header("Content-Type: $type");
    $download->readIntoResource(fopen('php://output', 'w'));
    return;
}

$nFiles = 0;
$listHtml = '<ul>';
foreach ($project->listObjects('raw', (new ListObjectsOptions())->withSystemMetadata()) as $objectInfo) {
    $nFiles += 1;
    $name = htmlspecialchars($objectInfo->getKey());
    $size = $objectInfo->getSystemMetadata()->getContentLength();
    $listHtml .= <<<HTML
        <li>
            <a href="download.php/$name">$name</a> ($size bytes)
        </li>
    HTML;
}
$listHtml .= '</ul>';
echo "<p>Found {$nFiles} files</p>";
echo $listHtml;