<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

class ProjectionStopCommand extends AbstractProjectionCommand
{
    protected $signature = 'event-store:projection:stop
    {name : The name of the projection}';

    protected $description = 'Stop a projection';

    public function handle()
    {
        $this->line(sprintf('Stopping <highlight>%s</highlight> projection', $this->projectionName));

        $this->projectionManager->stopProjection($this->projectionName);

        $this->info(sprintf('Stopped %s projection', $this->projectionName));
    }
}
