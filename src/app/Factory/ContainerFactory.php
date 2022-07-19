<?php

namespace Hoanvv\App\Factory;

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

class ContainerFactory
{
    private string $appRootDir;

    private string $containerPath;

    public function __construct(string $rootDir)
    {
        $this->appRootDir = $rootDir;
        $this->containerPath = $this->appRootDir . '/config/container.php';
    }

    /**
     * Get container.
     *
     * @return ContainerInterface
     * @throws \Exception
     */
    public function createInstance(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        // Load env variables
        $this->loadEnv();
        // Load helper functions
        $this->loadHelperFunctions();
        // Load settings
        $this->loadSettings();
        // App individual containers
        $containerBuilder->addDefinitions($this->containerPath);
        // Common containers
        $containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
        // App container
        $this->loadAppContainer($containerBuilder);
        // Build PHP-DI Container instance
        return $containerBuilder->build();
    }

    protected function loadAppContainer(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            App::class => function (ContainerInterface $container) {
                $app = AppFactory::createFromContainer($container);

                // Register app individual routes
                $appRouteFile = $this->appRootDir . '/config/routes.php';
                if (file_exists($appRouteFile)) {
                    (require $appRouteFile)($app, $container);
                }

                // Register app individual middleware
                $appMiddlewareFile = $this->appRootDir . '/config/middleware.php';
                if (file_exists($appMiddlewareFile)) {
                    (require $appMiddlewareFile)($app);
                }

                // Register common middleware
                (require __DIR__ . '/../config/middleware.php')($app);

                return $app;
            },
        ]);
    }

    protected function loadHelperFunctions(): void
    {
        require_once __DIR__ . '/../Utils/helpers.php';
    }

    protected function loadSettings(): void
    {
        // Common settings
        require_once __DIR__ . '/../config/settings.php';

        // App individual settings
        $appSettingsFile = $this->appRootDir . '/config/settings.php';
        if (file_exists($appSettingsFile)) {
            require_once $appSettingsFile;
        }
    }

    protected function loadEnv(): void
    {
        $dotenv = Dotenv::createImmutable($this->appRootDir);
        if (file_exists($this->appRootDir . '/.env')) {
            $dotenv->safeLoad();
        }
    }
}
