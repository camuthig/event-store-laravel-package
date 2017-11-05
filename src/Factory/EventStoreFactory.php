<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Factory;

use Camuthig\EventStore\Package\Factory\Contracts\EventStoreFactory as EventStoreFactoryContract;
use Closure;
use Illuminate\Foundation\Application;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherAggregate;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Prooph\EventStore\Pdo\MariaDbEventStore;
use Prooph\EventStore\Pdo\MySqlEventStore;
use Prooph\EventStore\Pdo\PostgresEventStore;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Prooph\EventStore\TransactionalEventStore;

class EventStoreFactory implements EventStoreFactoryContract
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var Closure
     */
    private $resolvers = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function make(string $name): EventStore
    {
        $config = config('event_store.stores.' . $name);

        if ($this->hasResolver($name)) {
            $eventStore = $this->resolvers[$name]($name, $config);
        } else {
            $eventStore = $this->storeFromArray($name, $config);
        }

        $wrapActionEventEmitter = $config['wrap_action_event_emitter'] ?? true;

        if ($wrapActionEventEmitter === false) {
            return $eventStore;
        }

        $actionEventEmittingEventStore = $this->wrapInActionEventEmitter($eventStore, $config);

        $this->addPlugins($actionEventEmittingEventStore, $config);

        $this->addMetadataEnrichers($actionEventEmittingEventStore, $config);

        return $actionEventEmittingEventStore;
    }

    public function addResolver(string $name, Closure $resolver): void
    {
        $this->resolvers[$name] = $resolver;
    }

    protected function hasResolver(string $name): bool
    {
        return isset($this->resolvers[$name]);
    }

    protected function storeFromArray($name, $config): EventStore
    {
        switch ($name) {
            case 'mysql':
                $storeClass     = MySqlEventStore::class;
                $connectionName = 'mysql';
                break;
            case 'maria_db':
                $storeClass     = MariaDbEventStore::class;
                $connectionName = 'mysql';
                break;
            case 'postgres':
                $storeClass     = PostgresEventStore::class;
                $connectionName = 'pgsql';
                break;
            default:
                throw new RuntimeException('Unable to get connection name for ' . $name);
        }

        $connection = app()->get('db')->connection($connectionName)->getPdo();
        $messageFactory = array_key_exists('message_factory', $config) ? app()->get($config['message_factory']) : new FQCNMessageFactory();
        $persistenceStrategy = new $config['persistence_strategy']();

        return new $storeClass(
            $messageFactory,
            $connection,
            $persistenceStrategy,
            isset($config['load_batch_size']) ? $config['load_batch_size'] : 1000,
            isset($config['event_streams_table']) ? $config['event_stream'] : 'event_streams',
            isset($config['disable_transaction_handling']) ? $config['disable_transaction_handling'] : false
        );
    }

    protected function wrapInActionEventEmitter(EventStore $eventStore, array $config) {
        $actionEventEmitter = $config['action_event_emitter'] ?? ProophActionEventEmitter::class;

        if (! in_array(ActionEventEmitter::class, class_implements($actionEventEmitter))) {
            throw new \RuntimeException(sprintf(
                                            'ActionEventEmitter "%s" must implement "%s"',
                                            $actionEventEmitter,
                                            ActionEventEmitter::class
                                        ));
        }
        if ($eventStore instanceof TransactionalEventStore) {
            if (! $eventStore instanceof TransactionalEventStore) {
                throw new \RuntimeException(sprintf(
                                                'Eventstore "%s" must implement "%s"',
                                                get_class($eventStore),
                                                TransactionalEventStore::class
                                            ));
            }

            return new TransactionalActionEventEmitterEventStore(
                $eventStore,
                new $actionEventEmitter(TransactionalActionEventEmitterEventStore::ALL_EVENTS)
            );
        }

        return new ActionEventEmitterEventStore($eventStore, new $actionEventEmitter(ActionEventEmitterEventStore::ALL_EVENTS));
    }

    protected function addPlugins(ActionEventEmitterEventStore $store, array $config): void
    {
        $plugins = array_merge(config('event_store.plugins', []), $config['plugins'] ?? []);

        foreach ($plugins as $pluginAlias) {
            // Use `make` here instead of `get` to support autowiring
            $plugin = app()->make($pluginAlias);

            if (! $plugin instanceof Plugin) {
                throw new RuntimeException(
                    sprintf('Plugin %s does not implement the Plugin interface', $pluginAlias)
                );
            }

            $plugin->attachToEventStore($store);
        }
    }

    protected function addMetadataEnrichers(ActionEventEmitterEventStore $store, array $config): void
    {
        $enricherIds = array_merge(config('event_store.metadata_enrichers', []), $config['metadata_enrichers'] ?? []);

        if (empty($enricherIds)) {
            return;
        }

        $enrichers = [];

        foreach ($enricherIds as $enricherId) {
            $enricher = app()->make($enricherId);

            if (! $enricher instanceof MetadataEnricher) {
                throw new RuntimeException(
                    sprintf('Plugin %s does not implement the MetadataEnricher interface', $enricherId)
                );
            }

            $enrichers[] = $enricher;
        }

        $enricherPlugin = new MetadataEnricherPlugin(new MetadataEnricherAggregate($enrichers));

        $enricherPlugin->attachToEventStore($store);
    }
}
