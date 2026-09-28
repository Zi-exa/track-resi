<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class StaticAssetCachingTest extends TestCase
{
    public function test_versioned_frontend_assets_are_cached_immutably(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
        $asset = $manifest['resources/css/app.css']['file'];
        $port = $this->availablePort();
        $router = file_exists(base_path('server.php'))
            ? base_path('server.php')
            : base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
        $server = new Process([PHP_BINARY, '-S', "127.0.0.1:{$port}", $router], public_path());
        $server->start();

        try {
            $headers = $this->waitForHeaders("http://127.0.0.1:{$port}/build/{$asset}");
            $cacheControl = collect($headers)
                ->first(fn (string $header) => str_starts_with(strtolower($header), 'cache-control:'));

            $this->assertNotNull($cacheControl, 'Versioned Vite assets must send a Cache-Control header.');
            $this->assertStringContainsString('public', strtolower($cacheControl));
            $this->assertStringContainsString('max-age=31536000', strtolower($cacheControl));
            $this->assertStringContainsString('immutable', strtolower($cacheControl));
        } finally {
            $server->stop();
        }
    }

    private function availablePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $this->assertNotFalse($socket, $errorMessage);
        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr(strrchr($address, ':'), 1);
    }

    /** @return list<string> */
    private function waitForHeaders(string $url): array
    {
        $deadline = microtime(true) + 5;

        do {
            $context = stream_context_create(['http' => [
                'method' => 'HEAD',
                'ignore_errors' => true,
                'timeout' => 0.5,
            ]]);
            @file_get_contents($url, false, $context);

            if (isset($http_response_header)) {
                return $http_response_header;
            }

            usleep(50_000);
        } while (microtime(true) < $deadline);

        $this->fail("Local PHP server did not respond for {$url}");
    }
}
