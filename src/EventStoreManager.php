<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package;

use Camuthig\EventStore\Package\Factory\Contracts\EventStoreFactory;
use Prooph\EventStore\EventStore;

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

    /**
     * @param string $name
     *
     * @return EventStore
     */
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
