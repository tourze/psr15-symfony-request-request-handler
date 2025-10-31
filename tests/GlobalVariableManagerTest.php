<?php

declare(strict_types=1);

namespace Tourze\PSR15SymfonyRequestHandler\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PSR15SymfonyRequestHandler\GlobalVariableManager;

/**
 * GlobalVariableManager 类的单元测试
 *
 * @internal
 */
#[CoversClass(GlobalVariableManager::class)]
final class GlobalVariableManagerTest extends TestCase
{
    private GlobalVariableManager $manager;

    protected function setUp(): void
    {
        $this->manager = new GlobalVariableManager();

        // 备份原始全局变量
        $this->originalGet = $GLOBALS['_GET'] ?? [];
        $this->originalRequest = $GLOBALS['_REQUEST'] ?? [];
        $this->originalCookie = $GLOBALS['_COOKIE'] ?? [];
    }

    /**
     * @var array<string, mixed>
     */
    private array $originalGet;

    /**
     * @var array<string, mixed>
     */
    private array $originalRequest;

    /**
     * @var array<string, mixed>
     */
    private array $originalCookie;

    protected function tearDown(): void
    {
        // 恢复原始全局变量
        $GLOBALS['_GET'] = $this->originalGet;
        $GLOBALS['_REQUEST'] = $this->originalRequest;
        $GLOBALS['_COOKIE'] = $this->originalCookie;
    }

    public function testSetQueryParameters(): void
    {
        $params = ['name' => 'test', 'id' => '123'];

        $this->manager->setQueryParameters($params);

        $this->assertEquals($params, $GLOBALS['_GET']);
    }

    public function testSetRequestParameters(): void
    {
        $params = ['name' => 'test', 'action' => 'submit'];

        $this->manager->setRequestParameters($params);

        $this->assertEquals($params, $GLOBALS['_REQUEST']);
    }

    public function testSetCookieParameters(): void
    {
        $cookies = ['session' => 'abc123', 'user' => 'john'];

        $this->manager->setCookieParameters($cookies);

        $this->assertEquals($cookies, $GLOBALS['_COOKIE']);
    }

    public function testGetPostParameters(): void
    {
        $postData = ['username' => 'test', 'password' => 'secret'];
        $GLOBALS['_POST'] = $postData;

        $result = $this->manager->getPostParameters();

        $this->assertEquals($postData, $result);
    }

    public function testGetPostParametersWhenEmpty(): void
    {
        unset($GLOBALS['_POST']);

        $result = $this->manager->getPostParameters();

        $this->assertEquals([], $result);
    }

    public function testGetCookieParameters(): void
    {
        $cookieData = ['theme' => 'dark', 'lang' => 'en'];
        $GLOBALS['_COOKIE'] = $cookieData;

        $result = $this->manager->getCookieParameters();

        $this->assertEquals($cookieData, $result);
    }

    public function testGetCookieParametersWhenEmpty(): void
    {
        unset($GLOBALS['_COOKIE']);

        $result = $this->manager->getCookieParameters();

        $this->assertEquals([], $result);
    }

    public function testParseCookieHeaderWithValidCookies(): void
    {
        $cookieHeader = 'session=abc123; user=john; theme=dark';

        $result = $this->manager->parseCookieHeader($cookieHeader);

        $this->assertArrayHasKey('session', $result);
        $this->assertEquals('abc123', $result['session']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('john', $result['user']);
        $this->assertArrayHasKey('theme', $result);
        $this->assertEquals('dark', $result['theme']);
    }

    public function testParseCookieHeaderWithEmptyString(): void
    {
        $result = $this->manager->parseCookieHeader('');

        $this->assertEquals([], $result);
    }

    public function testParseCookieHeaderWithSingleCookie(): void
    {
        $cookieHeader = 'session=abc123';

        $result = $this->manager->parseCookieHeader($cookieHeader);

        $this->assertArrayHasKey('session', $result);
        $this->assertEquals('abc123', $result['session']);
    }
}
