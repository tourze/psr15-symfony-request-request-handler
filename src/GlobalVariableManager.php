<?php

declare(strict_types=1);

namespace Tourze\PSR15SymfonyRequestHandler;

/**
 * 全局变量管理器 - 封装对超全局变量的操作
 *
 * 这个类专门用于处理传统PHP应用中对超全局变量的依赖，
 * 为PSR-15请求处理提供向后兼容性支持
 */
class GlobalVariableManager implements LegacyVariablesInterface
{
    /**
     * 设置查询参数到全局变量
     *
     * @param array<string, mixed> $queryParams
     */
    public function setQueryParameters(array $queryParams): void
    {
        $GLOBALS['_GET'] = $queryParams;
    }

    /**
     * 设置请求参数到全局变量
     *
     * @param array<string, mixed> $requestParams
     */
    public function setRequestParameters(array $requestParams): void
    {
        $GLOBALS['_REQUEST'] = $requestParams;
    }

    /**
     * 设置Cookie参数到全局变量
     *
     * @param array<string, mixed> $cookieParams
     */
    public function setCookieParameters(array $cookieParams): void
    {
        $GLOBALS['_COOKIE'] = $cookieParams;
    }

    /**
     * 获取当前的POST参数
     *
     * @return array<string, mixed>
     */
    public function getPostParameters(): array
    {
        return $GLOBALS['_POST'] ?? [];
    }

    /**
     * 获取当前的Cookie参数
     *
     * @return array<string, mixed>
     */
    public function getCookieParameters(): array
    {
        return $GLOBALS['_COOKIE'] ?? [];
    }

    /**
     * 解析Cookie头为数组
     *
     * @param string $cookieHeader
     * @return array<string, mixed>
     */
    public function parseCookieHeader(string $cookieHeader): array
    {
        if ('' === $cookieHeader) {
            return [];
        }

        $cookieData = [];
        $pairs = \explode('; ', $cookieHeader);

        foreach ($pairs as $pair) {
            if (false !== \strpos($pair, '=')) {
                [$name, $value] = \explode('=', $pair, 2);
                $cookieData[\trim($name)] = \trim($value);
            }
        }

        return $cookieData;
    }
}
