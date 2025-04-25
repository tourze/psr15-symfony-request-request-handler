<?php

declare(strict_types=1);

namespace Tourze\PSR15SymfonyRequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\HttpFoundation\Response as SfResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\BacktraceHelper\ExceptionPrinter;

/**
 * 统一的HTTP请求处理
 */
class SymfonyRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly HttpKernelInterface|KernelInterface $kernel,
        private readonly HttpFoundationFactoryInterface $httpFoundationFactory,
        private readonly HttpMessageFactoryInterface $httpMessageFactory,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    private SfRequest $request;

    public function getRequest(): SfRequest
    {
        return $this->request;
    }

    public function setRequest(SfRequest $request): void
    {
        $this->request = $request;
    }

    private SfResponse $response;

    public function getResponse(): SfResponse
    {
        return $this->response;
    }

    public function setResponse(SfResponse $response): void
    {
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $_GET = $request->getQueryParams();
        $_REQUEST = [...$_GET];

        // Cookie重新格式化写入
        \parse_str(\str_replace('; ', '&', $request->getHeaderLine('cookie')), $_COOKIE);

        // $this->output->writeln("<comment>{$this->worker->name}-{$this->worker->id}</comment> <info>{$request->getMethod()}</info> {$request->getUri()->getPath()}");

        $sfRequest = $this->httpFoundationFactory->createRequest($request);

        // 如果是Nginx ssl代理转发过来的话，我们需要声明一下我们是HTTPS
        if ($request->hasHeader('Force-Https') && $request->getHeaderLine('Force-Https')) {
            $sfRequest->server->set('HTTPS', 'on');
        }
        // TODO 更多负载均衡规则

        // TODO 真实IP透传，要注意这个可能会有漏洞
        if ($request->hasHeader('X-Real-IP')) {
            $sfRequest->server->set('REMOTE_ADDR', $request->getHeaderLine('X-Real-IP'));
        }

        $appendHeaders = [];

        // 默认情况下，symfony没对header中的Authorization做处理，貌似是依赖了nginx、php-fpm他们的处理，我们需要做一次兜底处理咯
        if ($request->hasHeader('Authorization')) {
            $authorizationHeader = $request->getHeaderLine('Authorization');
            if ($authorizationHeader) {
                // copy from vendor/symfony/http-foundation/ServerBag.php
                if (0 === stripos($authorizationHeader, 'basic ')) {
                    // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
                    if (2 == \count($exploded)) {
                        [$appendHeaders['PHP_AUTH_USER'], $appendHeaders['PHP_AUTH_PW']] = $exploded;
                    }
                } elseif (empty($this->parameters['PHP_AUTH_DIGEST']) && (0 === stripos($authorizationHeader, 'digest '))) {
                    // In some circumstances PHP_AUTH_DIGEST needs to be set
                    $appendHeaders['PHP_AUTH_DIGEST'] = $authorizationHeader;
                } elseif (0 === stripos($authorizationHeader, 'bearer ')) {
                    /*
                     * XXX: Since there is no PHP_AUTH_BEARER in PHP predefined variables,
                     *      I'll just set $headers['AUTHORIZATION'] here.
                     *      https://php.net/reserved.variables.server
                     */
                    $appendHeaders['AUTHORIZATION'] = $authorizationHeader;
                }
            }
        }

        // 在一些业务中，我们实际是需要在请求发生前，就生成一个 request id，这种情况才能更加好地打印整个日志
        // $appendHeaders['Request-Id'] = Uuid::v4()->toRfc4122();

        foreach ($appendHeaders as $k => $v) {
            $sfRequest->headers->set($k, $v);
        }

        try {
            $this->setRequest($sfRequest);
            $sfResponse = $this->kernel->handle($sfRequest);
        } catch (\Throwable $exception) {
            $fe = ExceptionPrinter::exception($exception);
            $this->logger?->error('执行请求时发生未被捕捉的异常', [
                'exception' => $fe,
            ]);
            $sfResponse = new SfResponse($fe);
        } finally {
            $this->setResponse($sfResponse);
        }

        // TODO 类似 http_cache 这种服务，应该需要再处理的，详细看 \Symfony\Component\HttpKernel\HttpCache\HttpCache::__construct

        return $this->httpMessageFactory->createResponse($sfResponse);
    }
}
