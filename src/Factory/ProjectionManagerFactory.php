<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Factory;

use Camuthig\EventStore\Package\Factory\Contracts\ProjectionManagerFactory as ProjectionManagerFactoryContract;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Prooph\EventStore\EventStoreDecorator;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Pdo\MariaDbEventStore;
use Prooph\EventStore\Pdo\MySqlEventStore;
use Prooph\EventStore\Pdo\PostgresEventStore;
use Prooph\EventStore\Pdo\Projection\MariaDbProjectionManager;
use Prooph\EventStore\Pdo\Projection\MySqlProjectionManager;
use Prooph\EventStore\Pdo\Projection\PostgresProjectionManager;
use Prooph\EventStore\Projection\ProjectionManager;

class ProjectionManagerFactory implements ProjectionManagerFactoryContract
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var ProjectionManager[]
     */
    private $projectionManagers = [];

    /**
     * @var Closure[];
     */
    private $resolvers = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function make(string $name): ProjectionManager
    {
        if (!isset($this->projectionManagers[$name])) {
            $config = $this->app->make('config')->get('event_store.projection_managers.' . $name);

            if ($config === null) {
                throw new RuntimeException('Unable to find projection manager ' . $name);
            }

            if ($this->hasResolver($name)) {
                $this->projectionManagers[$name] = $this->resolvers[$name]($name, $config);

                return $this->projectionManagers[$name];
            }

            $this->projectionManagers[$name] = $this->buildPdoManager($name, $config);
        }

        return $this->projectionManagers[$name];
    }

    public function addResolver(string $name, Closure $resolver): void
    {
        $this->resolvers[$name] = $resolver;
    }

    public function managerFor(string $projection): ProjectionManager
    {
        foreach ($this->app->make('config')->get('event_store.projection_managers') as $name => $config) {
            if (array_key_exists($projection, $config['projections'])) {
                return $this->make($name);
            }
        }

        throw new RuntimeException('No projection manager for projection ' . $projection);
    }

    public function optionsFor(string $projection): array
    {
        foreach ($this->app->make('config')->get('event_store.projection_managers') as $name => $config) {
            if (array_key_exists($projection, $config['projections'])) {
                return $config['options'] ?? [];
            }
        }

        return [];
    }

    public function buildPdoManager(string $name, array $config): ProjectionManager
    {
        $eventStore = $this->app->make('event_store')->store($config['store']);

        $innerStore = $eventStore;

        while ($innerStore instanceof EventStoreDecorator) {
            $innerStore = $innerStore->getInnerEventStore();
        }

        $connection        = $config['connection'] ?? null;
        $eventStreamsTable = $config['event_streams_table'] ?? 'event_streams';
        $projectionsTable  = $config['projections_table'] ?? 'projections';

        if ($innerStore instanceof PostgresEventStore) {
            return new PostgresProjectionManager(
                $eventStore,
                app()->make('db')->connection($connection ?? 'pgsql')->getPdo(),
                $eventStreamsTable,
                $projectionsTable
            );
        }

        if ($innerStore instanceof MySqlEventStore) {
            return new MySqlProjectionManager(
                $eventStore,
                app()->make('db')->connection($connection ?? 'mysql')->getPdo(),
                $eventStreamsTable,
                $projectionsTable
            );
        }

        if ($innerStore instanceof MariaDbEventStore) {
            return new MariaDbProjectionManager(
                $eventStore,
                app()->make('db')->connection($connection ?? 'mysql')->getPdo(),
                $eventStreamsTable,
                $projectionsTable
            );
        }

        throw new RuntimeException('Unable to build projection manager ' . $name);
    }

    protected function hasResolver(string $name): bool
    {
        return isset($this->resolvers[$name]);
    }
}
