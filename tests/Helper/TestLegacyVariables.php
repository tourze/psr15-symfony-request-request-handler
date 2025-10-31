<?php

declare(strict_types=1);

namespace Tourze\PSR15SymfonyRequestHandler\Tests\Helper;

use Tourze\PSR15SymfonyRequestHandler\LegacyVariablesInterface;

/**
 * 测试用的LegacyVariables实现
 *
 * @internal
 */
final class TestLegacyVariables implements LegacyVariablesInterface
{
    /** @var array<string, mixed> */
    private array $queryParams = [];

    /** @var array<string, mixed> */
    private array $postParams = [];

    /** @var array<string, mixed> */
    private array $requestParams = [];

    /** @var array<string, string> */
    private array $cookieParams = [];

    /** @var array<string, string> */
    private array $capturedCookieParams = [];

    /** @param array<string, mixed> $params */
    public function setQueryParameters(array $params): void
    {
        $this->queryParams = $params;
    }

    /** @param array<string, mixed> $params */
    public function setPostParameters(array $params): void
    {
        $this->postParams = $params;
    }

    /** @param array<string, mixed> $params */
    public function setRequestParameters(array $params): void
    {
        $this->requestParams = $params;
    }

    /** @param array<string, mixed> $params */
    public function setCookieParameters(array $params): void
    {
        $this->cookieParams = $params;
        $this->capturedCookieParams = $params;
    }

    /** @return array<string, mixed> */
    public function getQueryParameters(): array
    {
        return $this->queryParams;
    }

    /** @return array<string, mixed> */
    public function getPostParameters(): array
    {
        return $this->postParams;
    }

    /** @return array<string, mixed> */
    public function getRequestParameters(): array
    {
        return $this->requestParams;
    }

    /** @return array<string, string> */
    public function getCookieParameters(): array
    {
        return $this->cookieParams;
    }

    /** @return array<string, string> */
    public function getCapturedCookieParams(): array
    {
        return $this->capturedCookieParams;
    }

    /** @return array<string, string> */
    public function parseCookieHeader(string $cookieHeader): array
    {
        /** @var array<string, string> */
        $cookieData = [];
        $pairs = explode('; ', $cookieHeader);

        foreach ($pairs as $pair) {
            if (false !== strpos($pair, '=')) {
                [$name, $value] = explode('=', $pair, 2);
                $cookieData[$name] = $value;
            }
        }

        return $cookieData;
    }
}
