# tourze/psr15-symfony-request-request-handler

[English](README.md) | [中文](README.zh-CN.md)

[![PHP 版本](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![构建状态](https://img.shields.io/badge/build-passing-brightgreen)]()
[![代码覆盖率](https://img.shields.io/badge/coverage-85%25-brightgreen)]()
[![许可证](https://img.shields.io/badge/license-MIT-green)](LICENSE)

一个用于将 Symfony 的 HttpKernel 和 HttpFoundation 与 PSR-7/PSR-15 生态无缝集成的 PSR-15 请求处理器。
该包实现了 PSR-7 请求与 Symfony 请求的双向转换，提供认证头处理、HTTPS 代理转发、真实 IP 透传以及包含详细调用栈的异常日志等强大功能。

## 功能特性

- ✅ 完整实现 PSR-15 `RequestHandlerInterface` 接口
- ✅ PSR-7 ServerRequest 与 Symfony Request 双向转换
- ✅ HTTPS 代理头处理（Force-Https）
- ✅ 真实 IP 透传支持（X-Real-IP）
- ✅ Authorization 认证头处理（Basic、Digest、Bearer）
- ✅ Cookie 解析和 $_GET/$_REQUEST 全局变量设置
- ✅ 包含详细调用栈的异常处理
- ✅ 内部请求/响应对象访问，支持高级场景
- ✅ 高度兼容 Symfony 6.4+ 和现代 PSR 标准

## 依赖需求

此包需要以下依赖：

- **核心依赖：**
  - `php: ^8.1` - PHP 8.1 或更高版本
  - `psr/http-message: ^1.0|^2.0` - PSR-7 HTTP 消息接口
  - `psr/http-server-handler: ^1.0` - PSR-15 请求处理器接口
  - `psr/log: ^1.0|^2.0|^3.0` - PSR-3 日志器接口

- **Symfony 组件：**
  - `symfony/http-kernel: ^6.4|^7.0` - Symfony HTTP 内核组件
  - `symfony/http-foundation: ^6.4|^7.0` - Symfony HTTP 基础组件
  - `symfony/psr-http-message-bridge: ^6.4|^7.0` - PSR-7/Symfony 桥接器

- **额外依赖：**
  - `tourze/backtrace-helper: ^1.0` - 增强异常追踪

## 安装说明

**环境要求：**

- PHP ^8.1
- Symfony 6.4+
- PSR-7、PSR-15 兼容组件

通过 Composer 安装：

```bash
composer require tourze/psr15-symfony-request-request-handler
```

## 快速开始

```php
use Tourze\PSR15SymfonyRequestHandler\SymfonyRequestHandler;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

$handler = new SymfonyRequestHandler(
    $kernel, // HttpKernelInterface 或 KernelInterface 实例
    new HttpFoundationFactory(),
    new PsrHttpFactory(...),
    $logger // 可选的 PSR-3 LoggerInterface
);

$response = $handler->handle($psrRequest);
```

## 核心特性

### HTTP 处理
- 支持 Symfony 兼容的所有 HTTP 方法
- 自动解析 Cookie 和填充全局变量（$_GET、$_REQUEST、$_COOKIE）
- Header 转换以实现 PSR-7/Symfony 最大兼容性

### 安全与代理支持
- **HTTPS 代理**：自动检测 `Force-Https` 头并设置 HTTPS 模式
- **真实 IP 透传**：支持 `X-Real-IP` 头用于负载均衡场景
- **认证头**：全面支持 Basic、Digest 和 Bearer 认证

### 高级用法

```php
// 访问内部 Symfony 对象
$sfRequest = $handler->getRequest();
$sfResponse = $handler->getResponse();

// 中间件场景的自定义注入
$handler->setRequest($customSymfonyRequest);
$handler->setResponse($customSymfonyResponse);
```

### 错误处理
- 通过 `tourze/backtrace-helper` 自动记录包含详细调用栈的异常日志
- 在响应内容中返回格式化的错误详情
- PSR-3 日志器集成，支持集中化错误跟踪

## 贡献指南

- 欢迎通过 GitHub 提交 Issue 和 PR
- 遵循 PSR 代码规范
- 保证所有测试通过（`phpunit`）
- 运行静态分析（`phpstan`）

## 版权和许可

MIT 许可证，详见 [LICENSE](LICENSE)。

## 更新日志

详细变更请查阅 Git 历史。主要特性和修复会在发行说明中记录。
