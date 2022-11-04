<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
$data = array_key_exists('data', $_GET) ? json_decode(base64_decode($_GET['data']), true) : [];

$key = $data['key'];
$zipUrl = $data['zipUrl'];
$authHeader = $data['authHeader'];
$dir = $data['dir'];

function getTempDir($folder, $key): string
{
    $dir = __DIR__ . '/tmp/';
    if (!is_dir($dir)) mkdir($dir);

    $dir = $dir . $key . '/';
    if (!is_dir($dir)) mkdir($dir);

    $dir = $dir . $folder . '/';
    if (!is_dir($dir)) mkdir($dir);

    return $dir;
}

function cleanUp($key)
{
    removeDir(__DIR__ . '/tmp/' . $key . '/');
}

function removeDir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    removeDir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

function unzip($zipFile, $dest)
{
    $zip = new \ZipArchive;
    $res = $zip->open($zipFile);
    if ($res !== true) return false;
    $zip->extractTo($dest);
    $zip->close();
    unlink($zipFile);
    return true;
}

function httpGetZip($url, $authorization, $target)
{
    $fp = fopen($target, 'w+');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: {$authorization}"));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'php-request');
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp;
}

function trailingslashit($string)
{
    return untrailingslashit($string) . '/';
}

function untrailingslashit($string)
{
    return rtrim($string, '/\\');
}

function Zip($source, $destination)
{
    $zip = new \ZipArchive();

    if ($zip->open($destination, \ZIPARCHIVE::CREATE) !== TRUE) {
        exit("cannot open zip\n");
    }

    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($source),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($source));
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
}

/**
 * fetch Zip
 */

$tempDirGet = getTempDir('tmp-updates-fetch', $key);
httpGetZip($zipUrl, $authHeader, $tempDirGet . $key . '.zip');
$unzip = unzip($tempDirGet . $key . '.zip', $tempDirGet . $key . '/');
$subDirs = glob($tempDirGet . $key . '/*', GLOB_ONLYDIR);
$packageDir = $subDirs[0];

/**
 * move package files
 */

$tempDirZip = getTempDir('tmp-updates-package', $key);
$target = $tempDirZip . $key;
if (is_dir($target)) removeDir($target);

$renamed = rename(
    trailingslashit($packageDir) . ($dir ? trailingslashit($dir) : ''),
    $target
);

/**
 * create new Zip
 */

$tempDirDone = getTempDir('tmp-updates-zip', $key);
$zipFileName = $key . '.zip';
$zipFileDir = $tempDirDone . '/' . $zipFileName;

Zip($tempDirZip, $zipFileDir);

header("Content-type: application/zip");
header("Content-Disposition: attachment; filename=$zipFileName");
header("Content-length: " . filesize($zipFileDir));
header("Pragma: no-cache");
header("Expires: 0");
header("Accept-Ranges: bytes");
readfile("$zipFileDir");

cleanUp($key);
