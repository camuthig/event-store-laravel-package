<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

class ProjectionStateCommand extends AbstractProjectionCommand
{
    protected $signature = 'event-store:projection:state
    {name : The name of the projection}';

    protected $description = 'Show the state of a projection';

    public function handle()
    {
        $state = json_encode($this->projectionManager->fetchProjectionState($this->projectionName), JSON_PRETTY_PRINT);

        $this->info(sprintf('%s projection state:', $this->projectionName));

        $this->line($state);
    }
}
