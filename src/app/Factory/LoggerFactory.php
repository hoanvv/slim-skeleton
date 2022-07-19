<?php

namespace Hoanvv\App\Factory;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Factory.
 *
 * @phpstan-type Level Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY
 * @phpstan-type LevelName 'DEBUG'|'INFO'|'NOTICE'|'WARNING'|'ERROR'|'CRITICAL'|'ALERT'|'EMERGENCY'
 */
class LoggerFactory
{
    private string $path;

    private int $level;

    /**
     * @var array<StreamHandler> Handler
     */
    private array $handler = [];

    /**
     * The constructor.
     *
     * @param array<mixed> $settings The settings
     */
    public function __construct(array $settings)
    {
        $this->path = (string)$settings['path'];
        $this->level = (int)$settings['level'];
    }

    /**
     * Build the logger.
     *
     * @param string|null $name The logging channel
     *
     * @return LoggerInterface The logger
     */
    public function createLogger(string $name = null): LoggerInterface
    {
        $logger = new Logger($name ?: uniqid('', true));

        foreach ($this->handler as $handler) {
            $logger->pushHandler($handler);
        }

        $this->handler = [];

        return $logger;
    }

    /**
     * Add rotating file logger handler.
     *
     * @param string $filename The filename
     * @param int|null $level The level (optional)
     *
     * @phpstan-param Level $level
     *
     * @return self The logger factory
     */
    public function addFileHandler(string $filename, int $level = null): self
    {
        $filename = sprintf('%s/%s', $this->path, $filename);
        // TODO Check if phpstan bug is solved
        /** @phpstan-ignore-next-line */
        $rotatingFileHandler = new StreamHandler($filename, $level ?? $this->level, true, 0777);

        // The last "true" here tells monolog to remove empty []'s
        $rotatingFileHandler->setFormatter(new LineFormatter(null, null, false, true));

        $this->handler[] = $rotatingFileHandler;

        return $this;
    }

    /**
     * Add a console logger.
     *
     * @param int|null $level The level (optional)
     *
     * @phpstan-param Level $level
     *
     * @return self The logger factory
     */
    public function addConsoleHandler(int $level = null): self
    {
        // TODO Check if phpstan bug is solved
        /** @phpstan-ignore-next-line */
        $streamHandler = new StreamHandler('php://stdout', $level ?? $this->level);
        $streamHandler->setFormatter(new LineFormatter(null, null, false, true));

        $this->handler[] = $streamHandler;

        return $this;
    }

    /**
     * @return StreamHandler[]
     */
    public function getHandlers(): array
    {
        return $this->handler;
    }
}
