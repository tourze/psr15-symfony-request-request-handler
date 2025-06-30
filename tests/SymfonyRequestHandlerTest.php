<?php

declare(strict_types=1);

namespace Tourze\PSR15SymfonyRequestHandler\Tests;

use Tourze\PSR15SymfonyRequestHandler\Tests\Exception\TestRequestHandlingException;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response as SfResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tourze\PSR15SymfonyRequestHandler\SymfonyRequestHandler;

/**
 * SymfonyRequestHandler 类的单元测试
 *
 * 这个测试使用替身类而不是 MockObject，以避免 PHPUnit 的方法兼容性问题
 */
class SymfonyRequestHandlerTest extends TestCase
{
    /**
     * 测试基本请求处理
     */
    public function testBasicRequestHandling(): void
    {
        // 创建测试所需的对象
        $kernel = new TestHttpKernel();
        $httpFoundationFactory = new TestHttpFoundationFactory();
        $httpMessageFactory = new TestHttpMessageFactory();
        $logger = new TestLogger();

        // 创建请求
        $request = new ServerRequest('GET', 'https://example.com');

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger
        );

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * 测试异常处理
     */
    public function testExceptionHandling(): void
    {
        // 创建测试所需的对象
        $kernel = new TestExceptionHttpKernel();
        $httpFoundationFactory = new TestHttpFoundationFactory();
        $httpMessageFactory = new TestHttpMessageFactory();
        $logger = new TestLogger();

        // 创建请求
        $request = new ServerRequest('GET', 'https://example.com');

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger
        );

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($logger->hasErrorLog('执行请求时发生未被捕捉的异常'));
    }

    /**
     * 测试请求头处理
     */
    public function testRequestHeaders(): void
    {
        // 创建测试所需的对象
        $kernel = new TestHttpKernel();
        $httpFoundationFactory = new TestHttpFoundationFactory();
        $httpMessageFactory = new TestHttpMessageFactory();
        $logger = new TestLogger();

        // 创建请求
        $request = new ServerRequest('GET', 'https://example.com');
        $request = $request->withHeader('Force-Https', 'true');
        $request = $request->withHeader('X-Real-IP', '192.168.1.1');
        $request = $request->withHeader('cookie', 'name=value; session=abc123');

        // 保存原始 $_COOKIE 以便后续恢复
        $originalCookie = $_COOKIE;

        try {
            // 创建处理器
            $handler = new SymfonyRequestHandler(
                $kernel,
                $httpFoundationFactory,
                $httpMessageFactory,
                $logger
            );

            // 处理请求
            $response = $handler->handle($request);

            // 断言
            $this->assertInstanceOf(ResponseInterface::class, $response);

            // 断言 Force-Https 头已被处理
            $this->assertEquals('on', $httpFoundationFactory->getLastRequest()->server->get('HTTPS'));

            // 断言 X-Real-IP 头已被处理
            $this->assertEquals('192.168.1.1', $httpFoundationFactory->getLastRequest()->server->get('REMOTE_ADDR'));

            // 断言 Cookie 已被解析
            $this->assertArrayHasKey('name', $_COOKIE);
            $this->assertEquals('value', $_COOKIE['name']);
            $this->assertArrayHasKey('session', $_COOKIE);
            $this->assertEquals('abc123', $_COOKIE['session']);
        } finally {
            // 恢复 $_COOKIE
            $_COOKIE = $originalCookie;
        }
    }

    /**
     * 测试认证头处理
     */
    public function testAuthorizationHeaders(): void
    {
        // 测试 Basic 认证
        $this->testBasicAuthorization();

        // 测试 Digest 认证
        $this->testDigestAuthorization();

        // 测试 Bearer 认证
        $this->testBearerAuthorization();
    }

    /**
     * 测试 Basic 认证
     */
    private function testBasicAuthorization(): void
    {
        // 创建测试所需的对象
        $kernel = new TestHttpKernel();
        $httpFoundationFactory = new TestHttpFoundationFactory();
        $httpMessageFactory = new TestHttpMessageFactory();
        $logger = new TestLogger();

        // 创建带有 Basic 认证头的请求
        $request = new ServerRequest('GET', 'https://example.com');
        $auth = 'Basic ' . base64_encode('username:password');
        $request = $request->withHeader('Authorization', $auth);

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger
        );

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('username', $httpFoundationFactory->getLastRequest()->headers->get('PHP_AUTH_USER'));
        $this->assertEquals('password', $httpFoundationFactory->getLastRequest()->headers->get('PHP_AUTH_PW'));
    }

    /**
     * 测试 Digest 认证
     */
    private function testDigestAuthorization(): void
    {
        // 创建测试所需的对象
        $kernel = new TestHttpKernel();
        $httpFoundationFactory = new TestHttpFoundationFactory();
        $httpMessageFactory = new TestHttpMessageFactory();
        $logger = new TestLogger();

        // 创建带有 Digest 认证头的请求
        $request = new ServerRequest('GET', 'https://example.com');
        $auth = 'Digest username="Mufasa",realm="testrealm@host.com",nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",uri="/dir/index.html"';
        $request = $request->withHeader('Authorization', $auth);

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger
        );

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($auth, $httpFoundationFactory->getLastRequest()->headers->get('PHP_AUTH_DIGEST'));
    }

    /**
     * 测试 Bearer 认证
     */
    private function testBearerAuthorization(): void
    {
        // 创建测试所需的对象
        $kernel = new TestHttpKernel();
        $httpFoundationFactory = new TestHttpFoundationFactory();
        $httpMessageFactory = new TestHttpMessageFactory();
        $logger = new TestLogger();

        // 创建带有 Bearer 认证头的请求
        $request = new ServerRequest('GET', 'https://example.com');
        $auth = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';
        $request = $request->withHeader('Authorization', $auth);

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger
        );

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($auth, $httpFoundationFactory->getLastRequest()->headers->get('AUTHORIZATION'));
    }
}

/**
 * 测试用的 HttpKernel 替身
 */
class TestHttpKernel implements HttpKernelInterface
{
    public function handle($request, $type = self::MAIN_REQUEST, $catch = true): SfResponse
    {
        return new SfResponse('测试响应');
    }
}

/**
 * 测试用的抛出异常的 HttpKernel 替身
 */
class TestExceptionHttpKernel implements HttpKernelInterface
{
    public function handle($request, $type = self::MAIN_REQUEST, $catch = true): SfResponse
    {
        throw new TestRequestHandlingException('测试异常');
    }
}

/**
 * 测试用的 HttpFoundationFactory 替身
 */
class TestHttpFoundationFactory extends HttpFoundationFactory
{
    private $lastRequest;

    public function createRequest(ServerRequestInterface $psrRequest, bool $streamed = false): \Symfony\Component\HttpFoundation\Request
    {
        $sfRequest = new TestSfRequest();
        $sfRequest->headers = new TestHeaderBag();
        $sfRequest->server = new TestServerBag();
        $this->lastRequest = $sfRequest;
        return $sfRequest;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }
}

/**
 * 测试用的 PsrHttpFactory 替身
 */
class TestHttpMessageFactory extends PsrHttpFactory
{
    public function __construct()
    {
        // 空构造函数，不需要实际依赖
    }

    public function createResponse($response, $psrResponse = null): ResponseInterface
    {
        return new Response(200);
    }
}

/**
 * 测试用的 Logger 替身
 */
class TestLogger implements LoggerInterface
{
    private $logs = [];

    public function emergency($message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function hasErrorLog(string $message): bool
    {
        foreach ($this->logs as $log) {
            if ($log['level'] === 'error' && $log['message'] === $message) {
                return true;
            }
        }
        return false;
    }
}

/**
 * 测试用的 Symfony Request 替身
 */
class TestSfRequest extends \Symfony\Component\HttpFoundation\Request
{
    public $headers;
    public $server;

    public function __construct()
    {
        // 不调用父类构造函数，避免依赖问题
    }
}

/**
 * 测试用的 HeaderBag 替身
 */
class TestHeaderBag extends \Symfony\Component\HttpFoundation\HeaderBag
{
    protected $headers = [];

    public function set($key, $value, bool $replace = true): void
    {
        $this->headers[$key] = $value;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->headers[$key] ?? $default;
    }
}

/**
 * 测试用的 ServerBag 替身
 */
class TestServerBag extends \Symfony\Component\HttpFoundation\ServerBag
{
    protected $parameters = [];

    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }
}

 