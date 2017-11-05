<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

use Camuthig\EventStore\Package\Factory\Contracts\ProjectionManagerFactory;
use Illuminate\Console\Command;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;
use Camuthig\EventStore\Package\Projection\Projection;
use Camuthig\EventStore\Package\Projection\ReadModelProjection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractProjectionCommand extends Command
{
    use FormatsOutput;

    /**
     * @var ProjectionManager
     */
    protected $projectionManager;

    /**
     * @var string
     */
    protected $projectionName;

    /**
     * @var ReadModel|null
     */
    protected $readModel;

    /**
     * @var ReadModelProjector|Projector
     */
    protected $projector;

    /**
     * @var Projection|ReadModelProjection
     */
    protected $projection;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->formatOutput();

        if (!$this->projectionName = $input->getArgument('name')) {
            throw new RuntimeException('Projection name must be provided');
        }

        $this->projectionManager = app()->make(ProjectionManagerFactory::class)->managerFor($this->projectionName);
        $this->projection        = app()->make('event_store.projection.' . $this->projectionName . '.projection');

        if ($this->projection instanceof ReadModelProjection) {
            $this->readModel = app()->make('event_store.projection.' . $this->projectionName . '.read_model');

            $this->projector = $this->projectionManager->createReadModelProjection($this->projectionName, $this->readModel);
        } else {
            $this->projector = $this->projectionManager->createProjection($this->projectionName);
        }
    }
}
