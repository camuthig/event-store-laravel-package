<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

class ProjectionStreamsPositionCommand extends AbstractProjectionCommand
{
    protected $signature = 'event-store:projection:positions
    {name : The name of the projection}';

    protected $description = 'List the positions of all projections in the manager';

    public function handle()
    {
        $rows = [];

        foreach ($this->projectionManager->fetchProjectionStreamPositions($this->projectionName) as $stream => $position) {
            $rows[] = [$stream, $position];
        }

        $this->table(['Stream', 'Position'], $rows);
    }
}
