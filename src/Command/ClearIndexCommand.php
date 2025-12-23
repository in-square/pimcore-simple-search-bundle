<?php

namespace InSquare\PimcoreSimpleSearchBundle\Command;


use InSquare\PimcoreSimpleSearchBundle\Repository\SearchIndexRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'insquare:search:clear',
    description: 'Clear all data from search index'
)]
class ClearIndexCommand extends Command
{
    public function __construct(
        private readonly SearchIndexRepository $repository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->warning('This will delete ALL data from the search index!');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Are you sure you want to continue? [y/N] ',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $io->info('Operation cancelled');
            return Command::SUCCESS;
        }

        try {
            $this->repository->deleteAll();
            $io->success('Search index cleared successfully');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to clear index: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
