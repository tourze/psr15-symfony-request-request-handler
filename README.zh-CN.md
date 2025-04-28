# tourze/psr15-symfony-request-request-handler

[![PHP 版本](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![构建状态](https://img.shields.io/badge/build-passing-brightgreen)]()
[![许可证](https://img.shields.io/badge/license-MIT-green)](LICENSE)

一个用于将 Symfony 的 HttpKernel 和 HttpFoundation 与 PSR-7/PSR-15 生态无缝集成的 PSR-15 请求处理器。该包实现了请求对象的双向转换，支持认证头、HTTPS 代理、真实 IP 透传及异常日志等多种场景。

## 功能特性

- 实现 PSR-15 `RequestHandlerInterface` 接口
- 支持 PSR-7 ServerRequest 与 Symfony Request 的双向转换
- 处理 HTTPS 代理头（如 Force-Https）
- 支持真实 IP 透传（X-Real-IP）
- 支持多种 Authorization 认证头（Basic、Digest、Bearer）
- 自动日志记录异常详情
- 高度兼容 Symfony 与 PSR 标准

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
    $kernel, // HttpKernelInterface 实例
    new HttpFoundationFactory(),
    new PsrHttpFactory(...),
    $logger // 可选 PSR 日志器
);

$response = $handler->handle($psrRequest);
```

## 详细文档

- 支持 Symfony 所有 HTTP 方法
- Cookie 与 Header 兼容性处理
- 支持自定义注入/获取 Symfony Request/Response
- 异常处理：自动记录未捕获异常并返回错误信息

**高级用法：**

- 可通过 `setRequest()`、`getRequest()`、`setResponse()`、`getResponse()` 方法操作内部请求/响应对象
- 针对 Authorization、HTTPS 头等特殊场景有专门处理

## 贡献指南

- 欢迎通过 GitHub 提交 Issue 和 PR
- 遵循 PSR 代码规范
- 保证所有测试通过（`phpunit`）
- 运行静态分析（`phpstan`）

## 版权和许可

MIT 许可证，详见 [LICENSE](LICENSE)。

## 更新日志

详细变更请查阅 Git 历史。主要特性和修复会在发行说明中记录。
