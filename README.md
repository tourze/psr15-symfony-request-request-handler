# tourze/psr15-symfony-request-request-handler

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)]()
[![Code Coverage](https://img.shields.io/badge/coverage-85%25-brightgreen)]()
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A PSR-15 compatible HTTP request handler for integrating Symfony's HttpKernel and HttpFoundation 
with the PSR-7/PSR-15 ecosystem. This package enables seamless conversion between PSR-7 requests 
and Symfony requests, providing robust handling for authentication headers, HTTPS proxy forwarding, 
real IP forwarding, and comprehensive error logging with backtrace details.

## Features

- ✅ Complete PSR-15 `RequestHandlerInterface` implementation
- ✅ Bidirectional conversion between PSR-7 ServerRequest and Symfony Request
- ✅ HTTPS proxy header handling (Force-Https)
- ✅ Real IP forwarding support (X-Real-IP)
- ✅ Authorization header processing (Basic, Digest, Bearer)
- ✅ Cookie parsing and $_GET/$_REQUEST global variable setup
- ✅ Comprehensive exception handling with detailed backtraces
- ✅ Internal request/response object access for advanced scenarios
- ✅ High compatibility with Symfony 6.4+ and modern PSR standards

## Dependencies

This package requires the following dependencies:

- **Core Requirements:**
  - `php: ^8.1` - PHP 8.1 or higher
  - `psr/http-message: ^1.0|^2.0` - PSR-7 HTTP message interfaces
  - `psr/http-server-handler: ^1.0` - PSR-15 request handler interfaces
  - `psr/log: ^1.0|^2.0|^3.0` - PSR-3 logger interface

- **Symfony Components:**
  - `symfony/http-kernel: ^6.4|^7.0` - Symfony HTTP kernel component
  - `symfony/http-foundation: ^6.4|^7.0` - Symfony HTTP foundation
  - `symfony/psr-http-message-bridge: ^6.4|^7.0` - PSR-7/Symfony bridge

- **Additional Dependencies:**
  - `tourze/backtrace-helper: ^1.0` - Enhanced exception tracing

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
    $kernel, // HttpKernelInterface or KernelInterface instance
    new HttpFoundationFactory(),
    new PsrHttpFactory(...),
    $logger // optional PSR-3 LoggerInterface
);

$response = $handler->handle($psrRequest);
```

## Key Features

### HTTP Processing
- Supports all HTTP methods compatible with Symfony
- Automatic cookie parsing and global variable population ($_GET, $_REQUEST, $_COOKIE)
- Header conversion for maximum PSR-7/Symfony compatibility

### Security & Proxy Support
- **HTTPS Proxy**: Automatically detects `Force-Https` header and sets HTTPS mode
- **Real IP Forwarding**: Supports `X-Real-IP` header for load balancer scenarios
- **Authorization Headers**: Comprehensive support for Basic, Digest, and Bearer authentication

### Advanced Usage

```php
// Access internal Symfony objects
$sfRequest = $handler->getRequest();
$sfResponse = $handler->getResponse();

// Custom injection for middleware scenarios
$handler->setRequest($customSymfonyRequest);
$handler->setResponse($customSymfonyResponse);
```

### Error Handling
- Automatic exception logging with detailed backtraces via `tourze/backtrace-helper`
- Returns formatted error details in response content
- PSR-3 logger integration for centralized error tracking

## Contributing

- Please submit issues and pull requests via GitHub
- Follow PSR coding standards
- Ensure all tests pass (`phpunit`)
- Run static analysis (`phpstan`)

## License

MIT License. See [LICENSE](LICENSE) for details.

## Changelog

See Git history for detailed changes. Major features and bugfixes are documented in release notes.
