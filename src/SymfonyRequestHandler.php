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
    private readonly LegacyVariablesInterface $legacyVariables;

    public function __construct(
        private readonly HttpKernelInterface|KernelInterface $kernel,
        private readonly HttpFoundationFactoryInterface $httpFoundationFactory,
        private readonly HttpMessageFactoryInterface $httpMessageFactory,
        private readonly ?LoggerInterface $logger = null,
        ?LegacyVariablesInterface $legacyVariables = null,
    ) {
        $this->legacyVariables = $legacyVariables ?? new GlobalVariableManager();
    }

    private ?SfRequest $request = null;

    public function getRequest(): ?SfRequest
    {
        return $this->request;
    }

    public function setRequest(?SfRequest $request): void
    {
        $this->request = $request;
    }

    private ?SfResponse $response = null;

    public function getResponse(): ?SfResponse
    {
        return $this->response;
    }

    public function setResponse(?SfResponse $response): void
    {
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeGlobalVariables($request);

        $sfRequest = $this->createSymfonyRequest($request);
        $this->configureProxyHeaders($request, $sfRequest);
        $this->configureAuthorizationHeaders($request, $sfRequest);

        return $this->executeKernelRequest($sfRequest);
    }

    private function initializeGlobalVariables(ServerRequestInterface $request): void
    {
        // 初始化查询参数
        $queryParams = $request->getQueryParams();
        $this->legacyVariables->setQueryParameters($queryParams);

        // 初始化请求参数（组合GET和POST）
        $postParams = $this->legacyVariables->getPostParameters();
        $requestParams = array_merge($queryParams, $postParams);
        $this->legacyVariables->setRequestParameters($requestParams);

        // Cookie重新格式化写入
        $cookieHeader = $request->getHeaderLine('cookie');
        $cookieData = $this->legacyVariables->parseCookieHeader($cookieHeader);
        if ([] !== $cookieData) {
            $this->legacyVariables->setCookieParameters($cookieData);
        }
    }

    private function createSymfonyRequest(ServerRequestInterface $request): SfRequest
    {
        return $this->httpFoundationFactory->createRequest($request);
    }

    private function configureProxyHeaders(ServerRequestInterface $request, SfRequest $sfRequest): void
    {
        // 如果是Nginx ssl代理转发过来的话，我们需要声明一下我们是HTTPS
        if ($request->hasHeader('Force-Https') && '' !== $request->getHeaderLine('Force-Https')) {
            $sfRequest->server->set('HTTPS', 'on');
        }

        // TODO 真实IP透传，要注意这个可能会有漏洞
        if ($request->hasHeader('X-Real-IP')) {
            $sfRequest->server->set('REMOTE_ADDR', $request->getHeaderLine('X-Real-IP'));
        }
    }

    private function configureAuthorizationHeaders(ServerRequestInterface $request, SfRequest $sfRequest): void
    {
        if (!$request->hasHeader('Authorization')) {
            return;
        }

        $authorizationHeader = $request->getHeaderLine('Authorization');
        if ('' === $authorizationHeader) {
            return;
        }

        $appendHeaders = $this->parseAuthorizationHeader($authorizationHeader, $sfRequest);

        foreach ($appendHeaders as $k => $v) {
            $sfRequest->headers->set($k, $v);
        }
    }

    /**
     * @return array<string, string>
     */
    private function parseAuthorizationHeader(string $authorizationHeader, SfRequest $sfRequest): array
    {
        $appendHeaders = [];

        // copy from vendor/symfony/http-foundation/ServerBag.php
        if (0 === stripos($authorizationHeader, 'basic ')) {
            $appendHeaders = $this->parseBasicAuthorizationHeader($authorizationHeader);
        } elseif (null === $sfRequest->server->get('PHP_AUTH_DIGEST') && (0 === stripos($authorizationHeader, 'digest '))) {
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

        return $appendHeaders;
    }

    /**
     * @return array<string, string>
     */
    private function parseBasicAuthorizationHeader(string $authorizationHeader): array
    {
        // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
        $decoded = base64_decode(substr($authorizationHeader, 6), true);
        if (false === $decoded) {
            return [];
        }
        $exploded = explode(':', $decoded, 2);
        if (2 === \count($exploded)) {
            return [
                'PHP_AUTH_USER' => $exploded[0],
                'PHP_AUTH_PW' => $exploded[1],
            ];
        }

        return [];
    }

    private function executeKernelRequest(SfRequest $sfRequest): ResponseInterface
    {
        $sfResponse = new SfResponse('');
        try {
            $this->setRequest($sfRequest);
            $sfResponse = $this->kernel->handle($sfRequest);
        } catch (\Throwable $exception) {
            $sfResponse = $this->handleException($exception);
        } finally {
            $this->setResponse($sfResponse);
        }

        return $this->httpMessageFactory->createResponse($sfResponse);
    }

    private function handleException(\Throwable $exception): SfResponse
    {
        $fe = ExceptionPrinter::exception($exception);
        $this->logger?->error('执行请求时发生未被捕捉的异常', [
            'exception' => $fe,
        ]);

        $response = new SfResponse('');
        $response->setContent($fe);

        return $response;
    }
}
