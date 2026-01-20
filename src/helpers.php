<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

if (!function_exists('server_tag')) {
    function server_tag($remoteUrl, $cacheInterval = 300, $renderScript = null)
    {
        $jsFiles = Cache::remember($remoteUrl, $cacheInterval, function () use ($remoteUrl) {
            try {
                $response = Http::get($remoteUrl);
                $response->throw();
                $data = $response->json();
                // Ensure we return an array
                return is_array($data) ? $data : [];
            } catch (\Exception $e) {
                // Log the error or handle it as needed
                return [];
            }
        });

        if (is_callable($renderScript)) {
            return $renderScript($jsFiles);
        }

        $scripts = array_map(function ($src) {
            return sprintf('<script src="%s" async></script>', htmlspecialchars($src, ENT_QUOTES, 'UTF-8'));
        }, $jsFiles);

        return implode("\n", $scripts);
    }
}

