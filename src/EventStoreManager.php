<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package;

use Camuthig\EventStore\Package\Factory\Contracts\EventStoreFactory;
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

class EventStoreManager
{
    /**
     * @var EventStoreFactory
     */
    private $factory;

    /**
     * @var EventStore[]
     */
    private $stores = [];

    public function __construct(EventStoreFactory $factory)
    {
        $this->factory = $factory;
    }

    public function store(string $name = null): EventStore
    {
        if ($name === 'default' || $name === null) {
            $name = config('event_store.stores.default');
        }

        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->factory->make($name);
        }

        return $this->stores[$name];
    }
}
