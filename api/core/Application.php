<?php

namespace Core;

class Application
{
    private static ?Application $instance = null;
    private Container $container;

    private function __construct()
    {
        $this->container = new Container();
        $this->registerServices();
    }

    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    private function registerServices(): void
    {
        // Register database connection as singleton
        $this->container->singleton(\PDO::class, function (Container $container) {
            require_once __DIR__ . '/../config/database.php';
            return (new \Connection())->connect();
        });

        // Register models with proper DI
        $this->container->bind(\Models\User::class, function (Container $container) {
            return new \Models\User($container->resolve(\PDO::class));
        });
        
        $this->container->bind(\Models\Dashboard::class, function (Container $container) {
            return new \Models\Dashboard($container->resolve(\PDO::class));
        });
        
        $this->container->bind(\Models\Kpi::class, function (Container $container) {
            return new \Models\Kpi($container->resolve(\PDO::class));
        });
        
        $this->container->bind(\Models\KpiEntry::class, function (Container $container) {
            return new \Models\KpiEntry($container->resolve(\PDO::class));
        });
        
        $this->container->bind(\Models\ShareToken::class, function (Container $container) {
            return new \Models\ShareToken($container->resolve(\PDO::class));
        });

        // Register services (we'll create these next)
        $this->container->bind(\Services\AuthService::class, \Services\AuthService::class);
        $this->container->bind(\Services\UserService::class, \Services\UserService::class);
        $this->container->bind(\Services\DashboardService::class, \Services\DashboardService::class);
        $this->container->bind(\Services\KpiService::class, \Services\KpiService::class);
        $this->container->bind(\Services\KpiEntryService::class, \Services\KpiEntryService::class);
        $this->container->bind(\Services\ShareTokenService::class, \Services\ShareTokenService::class);
    }
}
