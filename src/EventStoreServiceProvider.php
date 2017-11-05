<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package;

use Camuthig\EventStore\Package\Factory\EventStoreFactory;
use Camuthig\EventStore\Package\Factory\ProjectionManagerFactory;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Prooph\EventSourcing\Aggregate\AggregateRepository;
use Prooph\EventSourcing\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\Pdo\MariaDbEventStore;
use Prooph\EventStore\Pdo\MySqlEventStore;
use Prooph\EventStore\Pdo\PostgresEventStore;
use Prooph\EventStore\StreamName;
use Camuthig\EventStore\Package\Command;
use Camuthig\EventStore\Package\Factory\Contracts;

class EventStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfig();
        $this->registerStores();
        $this->registerRepositories();

        if ($this->app->runningInConsole()) {
            // Projections are only leveraged in console mode, so we can defer registration of these services
            // for the sake of performance
            $this->registerProjectionManagers();
            $this->registerProjections();
        }
    }

    public function boot(): void
    {
        $this->publishes(
            [$this->getConfigPath() => config_path('event_store.php')],
            'config'
        );

        $this->publishes(
            [$this->getMigrationsPath() => database_path('migrations')],
            'migrations'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                Command\ProjectionRunCommand::class,
                Command\ProjectionDeleteCommand::class,
                Command\ProjectionResetCommand::class,
                Command\ProjectionStateCommand::class,
                Command\ProjectionStopCommand::class,
                Command\ProjectionStreamsPositionCommand::class,
                Command\ProjectionsNamesCommand::class,
                Command\EventStoreCreateStreamCommand::class
            ]);
        }
    }

    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'event_store');
    }

    protected function getConfigPath(): string
    {
        return __DIR__ . '/../config/event_store.php';
    }

    protected function getMigrationsPath(): string
    {
        return __DIR__ . '/../migrations/';
    }

    protected function registerStores(): void
    {
        $this->app->singleton(Contracts\EventStoreFactory::class, function (Application $app) {
            return new EventStoreFactory($app);
        });

        $this->app->singleton('event_store', function (Application $app) {
            return new EventStoreManager($app->make(EventStoreFactory::class));
        });

        $this->app->alias('event_store', EventStoreManager::class);

        $this->app->singleton(EventStore::class, function (Application $app) {
            return $app->make('event_store')->store('default');
        });

        $this->app->singleton(MySqlEventStore::class, function (Application $app) {
            return $app->make('event_store')->store('mysql');
        });

        $this->app->singleton(MariaDbEventStore::class, function (Application $app) {
            return $app->make('event_store')->store('maria_db');
        });

        $this->app->singleton(PostgresEventStore::class, function (Application $app) {
            return $app->make('event_store')->store('postgres');
        });
    }

    protected function registerRepositories(): void
    {
        $repositoryConfigs = config('event_store.repositories');

        foreach ($repositoryConfigs as $name => $repositoryConfig) {
            $serviceId = $repositoryConfig['repository_class'];

            // Bind the repository to the class name
            $this->app->singleton($serviceId, function (Application $app) use ($repositoryConfig) {
                return $this->buildRepository($app, $repositoryConfig);
            });

            // Optionally bind the repository to an interface
            if (isset($repositoryConfig['repository_interface'])) {
                $this->app->alias($serviceId, $repositoryConfig['repository_interface']);
            }
        }
    }

    protected function registerProjectionManagers(): void
    {
        $this->app->singleton(Contracts\ProjectionManagerFactory::class, function (Application $app) {
            return new ProjectionManagerFactory($app);
        });
    }

    /**
     * Add easier to use aliases for the read models and projections based on the user friendly name.
     */
    protected function registerProjections(): void
    {
        $configs = config('event_store.projection_managers');

        foreach ($configs as $config) {
            $projectionConfigs = $config['projections'];

            foreach ($projectionConfigs as $name => $projectionConfig) {
                $this->app->alias($projectionConfig['read_model'], 'event_store.projection.' . $name . '.read_model');
                $this->app->alias($projectionConfig['projection'], 'event_store.projection.' . $name . '.projection');
            }
        }

    }

    protected function buildRepository(Application $app, array $config)
    {
        $repositoryClass = $config['repository_class'];

        if (! class_exists($repositoryClass)) {
            throw ConfigurationException::configurationError(sprintf('Repository class %s cannot be found', $repositoryClass));
        }

        if (! is_subclass_of($repositoryClass, AggregateRepository::class)) {
            throw ConfigurationException::configurationError(sprintf('Repository class %s must be a sub class of %s', $repositoryClass, AggregateRepository::class));
        }

        $eventStore = $app->make('event_store')->store($config['store']);

        if (is_array($config['aggregate_type'])) {
            $aggregateType = AggregateType::fromMapping($config['aggregate_type']);
        } else {
            $aggregateType = AggregateType::fromAggregateRootClass($config['aggregate_type']);
        }

        $aggregateTranslator = $app->make($config['aggregate_translator']);

        $snapshotStore = isset($config['snapshot_store']) ? $app->make($config['snapshot_store']) : null;

        $streamName = isset($config['stream_name']) ? new StreamName($config['stream_name']) : null;

        $oneStreamPerAggregate = (bool) ($config['one_stream_per_aggregate'] ?? false);

        return new $repositoryClass(
            $eventStore,
            $aggregateType,
            $aggregateTranslator,
            $snapshotStore,
            $streamName,
            $oneStreamPerAggregate
        );
    }
}
