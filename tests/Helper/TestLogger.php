<?php

declare(strict_types=1);

namespace Tourze\PSR15SymfonyRequestHandler\Tests\Helper;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * 测试用的Logger实现
 *
 * @internal
 */
final class TestLogger implements LoggerInterface
{
    /** @var array<array{level: string, message: string, context: array<string, mixed>}> */
    private array $logs = [];

    /** @param array<mixed> $context */
    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /** @param array<mixed> $context */
    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /** @param array<mixed> $context */
    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /** @param array<mixed> $context */
    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /** @param array<mixed> $context */
    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /** @param array<mixed> $context */
    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /** @param array<mixed> $context */
    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /** @param array<mixed> $context */
    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /** @param array<mixed> $context */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    public function hasErrorLog(string $message): bool
    {
        foreach ($this->logs as $log) {
            if ('error' === $log['level'] && str_contains($log['message'], $message)) {
                return true;
            }
        }

        return false;
    }
}
