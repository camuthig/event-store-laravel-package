<?php

namespace Camuthig\EventStore\Package\Factory\Contracts;

use Closure;
use Prooph\EventStore\Projection\ProjectionManager;

interface ProjectionManagerFactory
{
    public function make(string $name): ProjectionManager;

    public function addResolver(string $name, Closure $resolver): void;

    public function managerFor(string $projection): ProjectionManager;
}
