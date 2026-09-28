<?php

$publicPath = getcwd();
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$filePath = realpath($publicPath.$uri);
$buildAssetsPath = realpath($publicPath.'/build/assets');
$normalizedFilePath = $filePath ? str_replace('\\', '/', $filePath) : null;
$normalizedBuildAssetsPath = $buildAssetsPath ? rtrim(str_replace('\\', '/', $buildAssetsPath), '/') : null;
$isVersionedBuildAsset = $normalizedFilePath !== null
    && $normalizedBuildAssetsPath !== null
    && is_file($filePath)
    && str_starts_with($normalizedFilePath, $normalizedBuildAssetsPath.'/');

if ($isVersionedBuildAsset && in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $contentTypes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'map' => 'application/json; charset=UTF-8',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'ttf' => 'font/ttf',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];
    $etag = '"'.basename($filePath).'"';

    header('Cache-Control: public, max-age=31536000, immutable');
    header('ETag: '.$etag);
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($filePath)).' GMT');
    header('X-Content-Type-Options: nosniff');

    if (isset($contentTypes[$extension])) {
        header('Content-Type: '.$contentTypes[$extension]);
    }

    if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);

        return true;
    }

    header('Content-Length: '.filesize($filePath));

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        readfile($filePath);
    }

    return true;
}

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');
$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
