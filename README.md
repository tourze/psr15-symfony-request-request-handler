# tourze/psr15-symfony-request-request-handler

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)]()
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A PSR-15 compatible HTTP request handler for integrating Symfony's HttpKernel and HttpFoundation with the PSR-7/PSR-15 ecosystem. This package enables seamless conversion between PSR-7 requests and Symfony requests, and provides robust handling for authentication headers, HTTPS forwarding, and error logging.

## Features

- PSR-15 `RequestHandlerInterface` implementation
- Converts PSR-7 ServerRequest to Symfony Request and vice versa
- Handles HTTPS proxy headers (e.g., Force-Https)
- Supports real IP forwarding (X-Real-IP)
- Handles Authorization headers (Basic, Digest, Bearer)
- Automatic error logging with exception details
- Designed for high compatibility with Symfony and PSR standards

## Installation

**Requirements:**

- PHP ^8.1
- Symfony 6.4+
- PSR-7, PSR-15 compatible packages

Install via Composer:

```bash
composer require tourze/psr15-symfony-request-request-handler
```

## Quick Start

```php
use Tourze\PSR15SymfonyRequestHandler\SymfonyRequestHandler;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

$handler = new SymfonyRequestHandler(
    $kernel, // instance of HttpKernelInterface
    new HttpFoundationFactory(),
    new PsrHttpFactory(...),
    $logger // optional PSR logger
);

$response = $handler->handle($psrRequest);
```

## Documentation

- Handles all HTTP methods supported by Symfony
- Converts cookies and headers for maximum compatibility
- Supports custom request/response injection for advanced scenarios
- Error handling: logs uncaught exceptions and returns error details in response

**Advanced Usage:**

- You can set/get the internal Symfony Request/Response via `setRequest()`, `getRequest()`, `setResponse()`, `getResponse()`
- Handles edge cases for Authorization and HTTPS headers

## Contributing

- Please submit issues and pull requests via GitHub
- Follow PSR coding standards
- Ensure all tests pass (`phpunit`)
- Run static analysis (`phpstan`)

## License

MIT License. See [LICENSE](LICENSE) for details.

## Changelog

See Git history for detailed changes. Major features and bugfixes are documented in release notes.
