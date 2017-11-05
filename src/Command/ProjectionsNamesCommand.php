<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ProjectionsNamesCommand extends Command
{
    protected $signature = 'event-store:projections:names
    {?filter : Filter by this string}
    {--r|regex : Enable regex syntax filtering}
    {--l|limit=20 : Limit the result set}
    {--o|offset=0 : Offset the result set}
    {--m|manager= : Only find names in the given projection manager}';

    protected $description = 'Show a list of all projection names, with the option to filter them';

    public function handle()
    {
        $managerNames = array_keys(config('event_store.projection_managers'));

        if ($requestedManager = $this->option('manager')) {
            $managerNames = array_filter($managerNames, function (string $managerName) use ($requestedManager) {
                return $managerName === $requestedManager;
            });
        }

        $filter = $this->argument('filter');
        $regex  = $this->option('regex');

        $this->line('Projection names');

        if ($filter) {
            $this->line(sprintf(' filter <highlight>%s</highlight>', $filter));
        }

        if ($regex) {
            $this->comment('Regex enabled');
            $method = 'fetchProjectionNamesRegex';
        } else {
            $method = 'fetchProjectionNames';
        }

        $names     = [];
        $offset    = (int) $this->option('offset');
        $limit     = (int) $this->option('limit');
        $maxNeeded = $offset + $limit;

        foreach ($managerNames as $managerName) {
            $projectionManager = app()->make('event_store.projection_managers.' . $managerName);

            if (count($names) > $offset) {
                $projectionNames = $projectionManager->$method($filter, $limit - (count($names) - $offset));
            } else {
                $projectionNames = $projectionManager->$method($filter);
            }

            foreach ($projectionNames as $projectionName) {
                $names[] = [$managerName, $projectionName];
            }

            if (count($names) >= $maxNeeded) {
                break;
            }
        }

        $names = array_slice($names, $offset, $limit);

        $this->table(['Projection Manager', 'Name'], $names);
    }
}
