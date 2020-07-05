<?php

/**
 * Minimal example to upload a file via a HTML form
 *
 * Try it using the built-in webserver
 * `ACCESS=youraccesstring php -S 0.0.0.0:8080`
 * then visit http://localhost:8080/upload.php
 */

use Storj\Uplink\Uplink;

ini_set('display_errors', 1);

require '../../vendor/autoload.php';

$access = Uplink::create()->parseAccess(getenv('ACCESS'));

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo <<<HTML
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="files[]" multiple />
            <button>Submit</button>
        </form>
    HTML;
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project = $access->openProject();
    $project->ensureBucket('raw');

    $files = $_FILES['files'] ?? [];
    $nFiles = count($files['name'] ?? []);
    for ($i = 0; $i < $nFiles; $i++) {
        $upload = $project->uploadObject('raw', $files['name'][$i]);
        $upload->writeFromResource(fopen($files['tmp_name'][$i], 'r'));
        if ($files['type'][$i]) {
            $upload->setCustomMetadata([
                'Content-Type' => $files['type'][$i],
            ]);
        }
        $upload->commit();
    }

    echo "<p>Your {$nFiles} files haves been uploaded to a global decentralized network.</p>";
    return;
}

http_response_code(405);
