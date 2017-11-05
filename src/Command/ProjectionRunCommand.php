<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

class ProjectionRunCommand extends AbstractProjectionCommand
{
    protected $signature = 'event-store:projection:run
    {name : The name of the projection}
    {--o|run-once : Run the projection only once, then exit}';

    protected $description = 'Run a projection';

    public function handle()
    {
        $keepRunning = !$this->option('run-once');
        $this->line(
            sprintf(
                'Starting projection <highlight>%s</highlight>. Keep running: <highlight>%s</highlight>',
                $this->projectionName,
                $keepRunning === true ? 'enabled' : 'disabled'
            )
        );

        $projector = $this->projection->project($this->projector);

        $projector->run((bool)$keepRunning);

        $this->info(sprintf('Projection %s completed.', $this->projectionName));
    }
}
