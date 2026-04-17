<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

if (!function_exists('server_tag_render_attr')) {
    function server_tag_render_attr($key, $value)
    {
        if ($value === true) {
            return htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');
        }
        if ($value === false || $value === null) {
            return '';
        }
        return sprintf(
            '%s="%s"',
            htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
        );
    }
}

if (!function_exists('server_tag')) {
    function server_tag($remoteUrl, $cacheInterval = 300, $renderScript = null, $scriptAttributes = null)
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

        $attrs = ['async' => true];
        if (is_array($scriptAttributes)) {
            $attrs = array_merge($attrs, $scriptAttributes);
        }

        $parts = [];
        foreach ($attrs as $key => $value) {
            $rendered = server_tag_render_attr($key, $value);
            if ($rendered !== '') {
                $parts[] = $rendered;
            }
        }
        $attrString = implode(' ', $parts);
        $suffix = $attrString === '' ? '' : ' ' . $attrString;

        $scripts = array_map(function ($src) use ($suffix) {
            return sprintf(
                '<script src="%s"%s></script>',
                htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
                $suffix
            );
        }, $jsFiles);

        return implode("\n", $scripts);
    }
}

