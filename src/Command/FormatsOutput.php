<?php

declare(strict_types=1);

namespace Camuthig\EventStore\Package\Command;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

trait FormatsOutput
{
    /**
     * @var OutputInterface
     */
    protected $output;

    protected function formatOutput()
    {
        $formatter = $this->output->getFormatter();
        $formatter->setStyle('highlight', new OutputFormatterStyle('green', null, ['bold']));
    }
}
