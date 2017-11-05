<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

use ArrayIterator;
use Camuthig\EventStore\Package\EventStoreManager;
use Illuminate\Console\Command;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;

class EventStoreCreateStreamCommand extends Command
{
    protected $signature = 'event-store:event-store:create-stream
    {store : The name of the event store}
    {name=event_stream : The name of the stream}';

    protected $description = 'Create an event stream for a given store. Useful for single stream strategy.';

    public function handle(): void
    {
        /** @var EventStore $store */
        $store = app()->make(EventStoreManager::class)->make($this->argument('store'));
        $name  = $this->argument('name');

        $store->create(new Stream(new StreamName($name), new ArrayIterator()));

        $this->info(sprintf('Created stream %s', $name));
    }
}
