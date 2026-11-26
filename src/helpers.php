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

if (!function_exists('server_tag_render_attrs')) {
    function server_tag_render_attrs(array $attrs)
    {
        $parts = [];
        foreach ($attrs as $key => $value) {
            $rendered = server_tag_render_attr($key, $value);
            if ($rendered !== '') {
                $parts[] = $rendered;
            }
        }
        return implode(' ', $parts);
    }
}

if (!function_exists('server_tag_with_domain')) {
    /**
     * Adds ?domain=<site> to the sources URL. When a filter rule blocks our
     * script host on one site only, that site alone gets a new host, and only
     * a request naming it receives it. The URL is left as it is without a
     * domain, or when it already has a domain parameter.
     */
    function server_tag_with_domain($remoteUrl, $domain)
    {
        if ($domain === null || $domain === '') {
            return $remoteUrl;
        }
        $query = (string) parse_url($remoteUrl, PHP_URL_QUERY);
        parse_str($query, $params);
        if (array_key_exists('domain', $params)) {
            return $remoteUrl;
        }
        $fragment = '';
        $hashPos = strpos($remoteUrl, '#');
        if ($hashPos !== false) {
            $fragment = substr($remoteUrl, $hashPos);
            $remoteUrl = substr($remoteUrl, 0, $hashPos);
        }
        $separator = $query === '' ? (substr($remoteUrl, -1) === '?' ? '' : '?') : '&';
        return $remoteUrl . $separator . 'domain=' . rawurlencode($domain) . $fragment;
    }
}

if (!function_exists('server_tag_request_host')) {
    function server_tag_request_host()
    {
        try {
            $host = request()->getHost();
            return $host === '' ? null : $host;
        } catch (\Throwable $e) {
            // No request, or a host outside the trusted hosts: send no domain.
            return null;
        }
    }
}

if (!function_exists('server_tag')) {
    /**
     * Renders the AdUnblock script tag.
     *
     * The default output is one tag, for the first URL (the preferred one):
     * <script data-cfasync="false" nowprocket src="URL" async ...$scriptAttributes></script>
     * data-cfasync and nowprocket come before src: Cloudflare Rocket Loader
     * honours data-cfasync="false" only ahead of src, and nowprocket keeps WP
     * Rocket's Delay JavaScript Execution off the tag. The client must load
     * once per page, since each copy runs its own adblock detection and asks
     * for its own proxy session. $renderScript still receives every URL.
     *
     * $domain defaults to the current request's host.
     */
    function server_tag($remoteUrl, $cacheInterval = 300, $renderScript = null, $scriptAttributes = null, $domain = null)
    {
        $remoteUrl = server_tag_with_domain(
            $remoteUrl,
            $domain !== null ? $domain : server_tag_request_host()
        );

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

        $src = $jsFiles[0] ?? null;
        if (!is_string($src) || $src === '') {
            return '';
        }

        $leadingDefaults = ['data-cfasync' => 'false', 'nowprocket' => true];
        $leading = $leadingDefaults;
        $trailing = ['async' => true];
        if (is_array($scriptAttributes)) {
            foreach ($scriptAttributes as $key => $value) {
                if (array_key_exists($key, $leadingDefaults)) {
                    $leading[$key] = $value;
                } else {
                    $trailing[$key] = $value;
                }
            }
        }

        $before = server_tag_render_attrs($leading);
        $after = server_tag_render_attrs($trailing);

        return sprintf(
            '<script%s src="%s"%s></script>',
            $before === '' ? '' : ' ' . $before,
            htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
            $after === '' ? '' : ' ' . $after
        );
    }
}
