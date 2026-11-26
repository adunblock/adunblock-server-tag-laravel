# Server Tag Laravel Package

A Laravel package to fetch and render scripts from a remote URL with caching support. Perfect for server-side script loading and integration with third-party services.

## Features

- Server-side script fetching from remote URLs
- Built-in caching support to reduce external requests
- Customizable rendering with closures
- Automatic Laravel service provider registration
- Works seamlessly with Blade templates
- Support for Laravel 8, 9, 10, 11, and 12

## Requirements

- PHP 7.4 or higher
- Laravel 8.x, 9.x, 10.x, 11.x, or 12.x

## Installation

Install the package via Composer:

```bash
composer require adunblock/server-tag-laravel:^1.0
```

The package will be automatically registered via Laravel's package auto-discovery feature.

## Usage

### Basic Usage

In your Blade template, use the `server_tag()` helper function to fetch and render scripts from a remote URL:

```php
<!DOCTYPE html>
<html>
<head>
  <title>My Page</title>
  {!! server_tag('https://your-remote-url.com/scripts') !!}
</head>
<body>
  <h1>My Page</h1>
</body>
</html>
```

### With Caching

Specify a cache duration (in seconds) as the second parameter:

```php
{!! server_tag('https://your-remote-url.com/scripts', 300) !!}
```

This will cache the fetched scripts for 5 minutes (300 seconds), reducing external requests.

### Custom Script Attributes

Pass an associative array as the fourth parameter to render extra attributes (including `data-*`) on the generated `<script>` tag:

```php
{!!
  server_tag('https://your-remote-url.com/scripts', 300, null, [
    'data-code' => 'abc123',
    'data-source' => 'server-tag',
    'defer' => true,
  ])
!!}
```

Rendering rules:

- `true` → bare attribute (e.g. `defer`)
- `false` / `null` → omitted
- any other value → `key="value"` (HTML-escaped)

By default the tag includes `async`. Set `'async' => false` to disable it.

### The tag it prints

By default `server_tag()` prints one tag, for the first URL in the response (the preferred one):

```html
<script data-cfasync="false" nowprocket src="SCRIPT_URL" async data-code="ACCOUNT_ID"></script>
```

- `data-cfasync="false"` and `nowprocket` come **before** `src`: Cloudflare Rocket Loader skips a script only when `data-cfasync="false"` precedes `src`, and `nowprocket` keeps WP Rocket's Delay JavaScript Execution off it. Pass either as `false` to drop it.
- Your attributes (`data-code` and anything else) come after `src`.
- Only the first URL is rendered: the client must load once per page, since each copy runs its own adblock detection and asks for its own proxy session. A custom renderer still receives every URL.

### The site's domain

`server_tag()` sends your site's domain to the sources endpoint as `?domain=`, taken from `request()->getHost()`, or from the fifth argument:

```php
{!! server_tag('https://public.adunblocker.com/api/vendor_scripts', 300, null, ['data-code' => 'ACCOUNT_ID'], 'example.com') !!}
```

When an ad blocker rule targets one site, we publish a new script host for that site alone, and only a request naming the site receives it. A `domain` parameter already in your URL is kept as it is.

### Custom Rendering

You can provide a custom closure as the third parameter to control how script tags are rendered:

```php
{!!
  server_tag('https://your-remote-url.com/scripts', 300, function($scripts) {
    $scriptTags = array_map(function ($src) {
      return "<script src=\"{$src}\" defer></script>";
    }, $scripts);
    return implode("\n", $scriptTags);
  })
!!}
```

### Function Signature

```php
server_tag(string $url, int $cache_seconds = 300, ?callable $render_script = null, ?array $script_attributes = null, ?string $domain = null): string
```

**Parameters:**
- `$url` (string): The remote URL to fetch scripts from
- `$cache_seconds` (int, optional): Cache duration in seconds. Default is 300
- `$render_script` (callable, optional): Custom rendering function. Receives an array of script URLs and returns an HTML string
- `$script_attributes` (array, optional): Associative array of attributes to render on the `<script>` tag (e.g. `['data-code' => 'abc']`), after `src` and `async`
- `$domain` (string, optional): Your site's domain, sent as `?domain=`. Defaults to the current request's host

## How It Works

1. The helper fetches content from the specified remote URL using Laravel's HTTP client
2. If caching is enabled, the response is stored in Laravel's cache for the specified duration
3. The first script URL is rendered as a `<script>` tag (or every URL is passed to your custom renderer)
4. The HTML is returned and can be used directly in your Blade templates

## Development

```bash
composer install
composer test
```

## License

ISC

## Support

- Issues: [GitHub Issues](https://github.com/adunblock/adunblock-server-tag/issues)
- Homepage: [https://github.com/adunblock/adunblock-server-tag](https://github.com/adunblock/adunblock-server-tag)

## Author

AdUnblock - [support@ad-unblock.com](mailto:support@ad-unblock.com)
