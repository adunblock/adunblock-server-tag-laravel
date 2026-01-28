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
server_tag(string $url, int $cache_seconds = 0, ?callable $render_script = null): string
```

**Parameters:**
- `$url` (string): The remote URL to fetch scripts from
- `$cache_seconds` (int, optional): Cache duration in seconds. Default is 0 (no caching)
- `$render_script` (callable, optional): Custom rendering function. Receives an array of script URLs and returns an HTML string

## How It Works

1. The helper fetches content from the specified remote URL using Laravel's HTTP client
2. If caching is enabled, the response is stored in Laravel's cache for the specified duration
3. The fetched scripts are rendered as `<script>` tags (or using your custom renderer)
4. The HTML is returned and can be used directly in your Blade templates

## License

ISC

## Support

- Issues: [GitHub Issues](https://github.com/adunblock/adunblock-server-tag/issues)
- Homepage: [https://github.com/adunblock/adunblock-server-tag](https://github.com/adunblock/adunblock-server-tag)

## Author

AdUnblock - [support@ad-unblock.com](mailto:support@ad-unblock.com)
