<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputOption;

class ProjectionDeleteCommand extends AbstractProjectionCommand
{
    protected $signature = 'event-store:projection:delete
    {name : The name of the projection}
    {--w|with-emitted-events : Delete the emitted events with the projection}';

    protected $description = 'Delete a projection';

    public function handle()
    {
        $withEvents = (bool) $this->option('with-emitted-events');
        if ($withEvents) {
            $this->output->getFormatter()->setStyle('bold', new OutputFormatterStyle('yellow', null, ['bold']));
            $this->warn(sprintf('Deleting %s projection <bold>with emitted events</bold>', $this->projectionName));
        } else {
            $this->warn(sprintf('Deleting %s projection', $this->projectionName));
        }
        $this->projectionManager->deleteProjection($this->projectionName, $withEvents);
        $this->readModel->delete();

        $this->info(sprintf('Deleted %s projection', $this->projectionName));
    }
}
