<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Factory\Contracts;

use Closure;
use Prooph\EventStore\EventStore;

interface EventStoreFactory
{
    public function make(string $name): EventStore;

    public function addResolver(string $name, Closure $resolver): void;
}
