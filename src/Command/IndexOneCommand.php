<?php

namespace InSquare\PimcoreSimpleSearchBundle\Command;

use InSquare\PimcoreSimpleSearchBundle\Message\IndexElementMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'insquare:search-index:index-one', description: 'Dispatch indexing of a single element by ID.')]
class IndexOneCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'document|object')
            ->addArgument('id', InputArgument::REQUIRED, 'Pimcore element ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = (string)$input->getArgument('type');
        $id = (int)$input->getArgument('id');

        if (!in_array($type, ['document', 'object'], true)) {
            $output->writeln('<error>Type must be document|object</error>');
            return Command::FAILURE;
        }

        $this->bus->dispatch(new IndexElementMessage($type, $id, false));
        $output->writeln('<info>Dispatched.</info>');

        return Command::SUCCESS;
    }
}
