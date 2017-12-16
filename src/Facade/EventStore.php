<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Facade;

use Camuthig\EventStore\Package\EventStoreManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Prooph\EventStore\EventStore store(string $name = null)
 */
final class EventStore extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EventStoreManager::class;
    }
}
