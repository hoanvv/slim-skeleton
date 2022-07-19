<?php

namespace Hoanvv\App\Handler;

use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

/**
 * Error renderer for Json error messages
 *
 * @package Hoanvv\Slim\Error\Renderers
 */
class JsonErrorRenderer implements ErrorRendererInterface
{

    /**
     * @param Throwable $exception
     * @param bool $displayErrorDetails
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $error = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];

        if ($displayErrorDetails) {
            $error['exception'] = [];
            do {
                $error['exception'][] = $this->formatExceptionFragment($exception);
            } while ($exception = $exception->getPrevious());
        }

        return (string) json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param Throwable $exception
     * @return array<string|int>
     */
    private function formatExceptionFragment(Throwable $exception): array
    {
        return [
            'type' => get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }
}
