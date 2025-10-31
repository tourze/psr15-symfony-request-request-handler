<?php

declare(strict_types=1);

namespace Tourze\PSR15SymfonyRequestHandler\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SfResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tourze\PSR15SymfonyRequestHandler\LegacyVariablesInterface;
use Tourze\PSR15SymfonyRequestHandler\SymfonyRequestHandler;
use Tourze\PSR15SymfonyRequestHandler\Tests\Exception\TestRequestHandlingException;
use Tourze\PSR15SymfonyRequestHandler\Tests\Helper\TestHttpMessageFactory;
use Tourze\PSR15SymfonyRequestHandler\Tests\Helper\TestLegacyVariables;
use Tourze\PSR15SymfonyRequestHandler\Tests\Helper\TestLogger;

/**
 * SymfonyRequestHandler 类的单元测试
 *
 * 使用专门的测试帮助类来避免复杂匿名类
 *
 * @internal
 */
#[CoversClass(SymfonyRequestHandler::class)]
final class SymfonyRequestHandlerTest extends TestCase
{
    /**
     * 创建普通的测试用HttpKernel
     */
    private function createTestHttpKernel(): HttpKernelInterface
    {
        return new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): SfResponse
            {
                return new SfResponse('Test response', 200);
            }
        };
    }

    /**
     * 创建会抛出异常的测试用HttpKernel
     */
    private function createExceptionHttpKernel(): HttpKernelInterface
    {
        return new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): SfResponse
            {
                throw new TestRequestHandlingException('Test exception');
            }
        };
    }

    /**
     * 创建测试用的HttpFoundationFactory
     */
    private function createTestHttpFoundationFactory(): HttpFoundationFactoryInterface
    {
        return new class implements HttpFoundationFactoryInterface {
            private ?Request $lastRequest = null;

            public function createRequest(ServerRequestInterface $psrRequest, bool $streamed = false): Request
            {
                $serverParams = $psrRequest->getServerParams();
                $request = new Request(
                    $psrRequest->getQueryParams(),
                    [], // POST params
                    [], // attributes
                    [], // cookies
                    [], // files
                    $serverParams,
                    $psrRequest->getBody()->getContents()
                );

                // 模拟Symfony的行为：当服务器参数中有PHP_AUTH_USER时，自动设置为头
                if (isset($serverParams['PHP_AUTH_USER'])) {
                    $request->headers->set('PHP_AUTH_USER', $serverParams['PHP_AUTH_USER']);
                }
                if (isset($serverParams['PHP_AUTH_PW'])) {
                    $request->headers->set('PHP_AUTH_PW', $serverParams['PHP_AUTH_PW']);
                }
                if (isset($serverParams['PHP_AUTH_DIGEST'])) {
                    $request->headers->set('PHP_AUTH_DIGEST', $serverParams['PHP_AUTH_DIGEST']);
                }

                $this->lastRequest = $request;

                return $request;
            }

            public function getLastRequest(): ?Request
            {
                return $this->lastRequest;
            }

            public function createResponse(ResponseInterface $psrResponse, bool $streamed = false): SfResponse
            {
                return new SfResponse('test response');
            }
        };
    }

    /**
     * 创建测试用的HttpMessageFactory
     */
    private function createTestHttpMessageFactory(): HttpMessageFactoryInterface
    {
        return new TestHttpMessageFactory();
    }

    /**
     * 创建测试用的Logger
     */
    private function createTestLogger(): TestLogger
    {
        return new TestLogger();
    }

    /**
     * 创建测试用的GlobalVariableManager
     */
    private function createTestGlobalVariableManager(): TestLegacyVariables
    {
        return new TestLegacyVariables();
    }

    /**
     * 测试handle方法
     */
    public function testHandle(): void
    {
        // 创建测试所需的对象
        $kernel = $this->createTestHttpKernel();
        $httpFoundationFactory = $this->createTestHttpFoundationFactory();
        $httpMessageFactory = $this->createTestHttpMessageFactory();
        $logger = $this->createTestLogger();
        $globalVariableManager = $this->createTestGlobalVariableManager();

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger,
            $globalVariableManager
        );

        // 创建请求
        $request = new ServerRequest('GET', 'https://example.com');

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * 测试基本请求处理
     */
    public function testBasicRequestHandling(): void
    {
        // 创建测试所需的对象
        $kernel = $this->createTestHttpKernel();
        $httpFoundationFactory = $this->createTestHttpFoundationFactory();
        $httpMessageFactory = $this->createTestHttpMessageFactory();
        $logger = $this->createTestLogger();
        $globalVariableManager = $this->createTestGlobalVariableManager();

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger,
            $globalVariableManager
        );

        // 创建请求
        $request = new ServerRequest('GET', 'https://example.com');

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
        $kernel = $this->createExceptionHttpKernel();
        $httpFoundationFactory = $this->createTestHttpFoundationFactory();
        $httpMessageFactory = $this->createTestHttpMessageFactory();
        $logger = $this->createTestLogger();

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger
        );

        // 创建请求
        $request = new ServerRequest('GET', 'https://example.com');

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        // @phpstan-ignore-next-line
        $this->assertTrue($logger->hasErrorLog('执行请求时发生未被捕捉的异常'));
    }

    /**
     * 测试请求头处理
     */
    public function testRequestHeaders(): void
    {
        // 创建测试所需的对象
        $kernel = $this->createTestHttpKernel();
        $httpFoundationFactory = $this->createTestHttpFoundationFactory();
        $httpMessageFactory = $this->createTestHttpMessageFactory();
        $logger = $this->createTestLogger();
        $globalVariableManager = $this->createTestGlobalVariableManager();

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger,
            $globalVariableManager
        );

        // 创建请求
        $request = new ServerRequest('GET', 'https://example.com');
        $request = $request->withHeader('Force-Https', 'true');
        $request = $request->withHeader('X-Real-IP', '192.168.1.1');
        $request = $request->withHeader('cookie', 'name=value; session=abc123');

        // 保存原始 $_COOKIE 以便后续恢复
        $originalCookie = $globalVariableManager->getCookieParameters();

        try {
            // 处理请求
            $response = $handler->handle($request);

            // 断言
            $this->assertInstanceOf(ResponseInterface::class, $response);

            // 断言 Force-Https 头已被处理
            // @phpstan-ignore-next-line
            $lastRequest = $httpFoundationFactory->getLastRequest();
            $this->assertNotNull($lastRequest);
            $this->assertEquals('on', $lastRequest->server->get('HTTPS'));

            // 断言 X-Real-IP 头已被处理
            $this->assertEquals('192.168.1.1', $lastRequest->server->get('REMOTE_ADDR'));

            // 断言 Cookie 已被解析
            // @phpstan-ignore-next-line
            $capturedCookies = $globalVariableManager->getCapturedCookieParams();
            $this->assertArrayHasKey('name', $capturedCookies);
            $this->assertEquals('value', $capturedCookies['name']);
            $this->assertArrayHasKey('session', $capturedCookies);
            $this->assertEquals('abc123', $capturedCookies['session']);
        } finally {
            // 恢复 $_COOKIE
            if ([] !== $originalCookie) {
                $globalVariableManager->setCookieParameters($originalCookie);
            }
        }
    }

    /**
     * 测试认证头处理
     */
    public function testAuthorizationHeaders(): void
    {
        $authTypes = ['Basic', 'Digest', 'Bearer'];
        $testedTypes = [];

        // 测试 Basic 认证
        $this->testBasicAuthorization();
        $testedTypes[] = 'Basic';

        // 测试 Digest 认证
        $this->testDigestAuthorization();
        $testedTypes[] = 'Digest';

        // 测试 Bearer 认证
        $this->testBearerAuthorization();
        $testedTypes[] = 'Bearer';

        // 验证所有认证类型都已测试
        $this->assertEquals($authTypes, $testedTypes, 'All authorization types should be tested');
    }

    /**
     * 测试 Basic 认证
     */
    private function testBasicAuthorization(): void
    {
        // 创建测试所需的对象
        $kernel = $this->createTestHttpKernel();
        $httpFoundationFactory = $this->createTestHttpFoundationFactory();
        $httpMessageFactory = $this->createTestHttpMessageFactory();
        $logger = $this->createTestLogger();
        $globalVariableManager = $this->createTestGlobalVariableManager();

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger,
            $globalVariableManager
        );

        // 创建带有 Basic 认证头的请求
        $request = new ServerRequest('GET', 'https://example.com');
        $auth = 'Basic ' . base64_encode('username:password');
        $request = $request->withHeader('Authorization', $auth);

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        // @phpstan-ignore-next-line
        $lastRequest = $httpFoundationFactory->getLastRequest();
        $this->assertNotNull($lastRequest);
        $this->assertEquals('username', $lastRequest->headers->get('PHP_AUTH_USER'));
        $this->assertEquals('password', $lastRequest->headers->get('PHP_AUTH_PW'));
    }

    /**
     * 测试 Digest 认证
     */
    private function testDigestAuthorization(): void
    {
        // 创建测试所需的对象
        $kernel = $this->createTestHttpKernel();
        $httpFoundationFactory = $this->createTestHttpFoundationFactory();
        $httpMessageFactory = $this->createTestHttpMessageFactory();
        $logger = $this->createTestLogger();
        $globalVariableManager = $this->createTestGlobalVariableManager();

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger,
            $globalVariableManager
        );

        // 创建带有 Digest 认证头的请求
        $request = new ServerRequest('GET', 'https://example.com');
        $auth = 'Digest username="Mufasa",realm="testrealm@host.com",nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",uri="/dir/index.html"';
        $request = $request->withHeader('Authorization', $auth);

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        // @phpstan-ignore-next-line
        $lastRequest = $httpFoundationFactory->getLastRequest();
        $this->assertNotNull($lastRequest);
        $this->assertEquals($auth, $lastRequest->headers->get('PHP_AUTH_DIGEST'));
    }

    /**
     * 测试 Bearer 认证
     */
    private function testBearerAuthorization(): void
    {
        // 创建测试所需的对象
        $kernel = $this->createTestHttpKernel();
        $httpFoundationFactory = $this->createTestHttpFoundationFactory();
        $httpMessageFactory = $this->createTestHttpMessageFactory();
        $logger = $this->createTestLogger();
        $globalVariableManager = $this->createTestGlobalVariableManager();

        // 创建处理器
        $handler = new SymfonyRequestHandler(
            $kernel,
            $httpFoundationFactory,
            $httpMessageFactory,
            $logger,
            $globalVariableManager
        );

        // 创建带有 Bearer 认证头的请求
        $request = new ServerRequest('GET', 'https://example.com');
        $auth = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';
        $request = $request->withHeader('Authorization', $auth);

        // 处理请求
        $response = $handler->handle($request);

        // 断言
        $this->assertInstanceOf(ResponseInterface::class, $response);
        // @phpstan-ignore-next-line
        $lastRequest = $httpFoundationFactory->getLastRequest();
        $this->assertNotNull($lastRequest);
        $this->assertEquals($auth, $lastRequest->headers->get('AUTHORIZATION'));
    }
}
