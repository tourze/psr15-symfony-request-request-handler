<?php

declare(strict_types=1);

namespace Tourze\PSR15SymfonyRequestHandler;

/**
 * 传统变量管理接口
 *
 * 为PSR-15请求处理器提供与传统PHP超全局变量的兼容性支持
 */
interface LegacyVariablesInterface
{
    /**
     * 设置查询参数
     *
     * @param array<string, mixed> $queryParams
     */
    public function setQueryParameters(array $queryParams): void;

    /**
     * 设置请求参数
     *
     * @param array<string, mixed> $requestParams
     */
    public function setRequestParameters(array $requestParams): void;

    /**
     * 设置Cookie参数
     *
     * @param array<string, mixed> $cookieParams
     */
    public function setCookieParameters(array $cookieParams): void;

    /**
     * 获取当前的POST参数
     *
     * @return array<string, mixed>
     */
    public function getPostParameters(): array;

    /**
     * 获取当前的Cookie参数
     *
     * @return array<string, mixed>
     */
    public function getCookieParameters(): array;

    /**
     * 解析Cookie头为数组
     *
     * @param string $cookieHeader
     * @return array<string, mixed>
     */
    public function parseCookieHeader(string $cookieHeader): array;
}
