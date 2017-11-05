<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

class ProjectionResetCommand extends AbstractProjectionCommand
{
    protected $signature = 'event-store:projection:reset
    {name : The name of the projection}';

    protected $description = 'Reset a projection';

    public function handle()
    {
        $this->warn(sprintf('Resetting %s projection', $this->projectionName));

        $this->projectionManager->resetProjection($this->projectionName);

        $this->info(sprintf('Reset %s projection', $this->projectionName));
    }
}
