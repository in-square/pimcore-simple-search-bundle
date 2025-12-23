<?php

namespace InSquare\PimcoreSimpleSearchBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'insquare:search-index:install', description: 'Creates MySQL table + FULLTEXT for Pimcore MySQL Search Index.')]
class InstallCommand extends Command
{
    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Installing Search Index Table');

        try {
            $sql = file_get_contents(__DIR__ . '/../Resources/sql/mysql.sql');

            if ($sql === false) {
                $io->error('SQL file not found at: ' . __DIR__ . '/../Resources/sql/mysql.sql');
                return Command::FAILURE;
            }

            $this->connection->executeStatement($sql);
            $io->success('Search index table created successfully (or already exists)');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create table: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
