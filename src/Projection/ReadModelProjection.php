<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Projection;

use Prooph\EventStore\Projection\ReadModelProjector;

interface ReadModelProjection
{
    public function project(ReadModelProjector $projector): ReadModelProjector;
}
