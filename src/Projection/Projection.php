<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Projection;

use Prooph\EventStore\Projection\Projector;

interface Projection
{
    public function project(Projector $projector): Projector;
}
