<?php

namespace InSquare\PimcoreSimpleSearchBundle\Command;

use InSquare\PimcoreSimpleSearchBundle\Message\IndexElementMessage;
use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\ObjectExtractorRegistry;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Listing as DocumentListing;
use Pimcore\Model\DataObject\Listing as DataObjectListing;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'insquare:search:reindex',
    description: 'Reindex all documents and objects for search'
)]
class ReindexCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly ObjectExtractorRegistry $extractorRegistry,
        #[Autowire('%in_square_pimcore_simple_search.index_documents%')]
        private readonly bool $indexDocuments,
        #[Autowire('%in_square_pimcore_simple_search.index_objects%')]
        private readonly bool $indexObjects,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'documents-only',
                'd',
                InputOption::VALUE_NONE,
                'Index only documents'
            )
            ->addOption(
                'objects-only',
                'o',
                InputOption::VALUE_NONE,
                'Index only objects'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_REQUIRED,
                'Batch size for progress reporting',
                100
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Search Reindexing');

        $documentsOnly = $input->getOption('documents-only');
        $objectsOnly = $input->getOption('objects-only');

        $shouldIndexDocuments = !$objectsOnly && $this->indexDocuments;
        $shouldIndexObjects = !$documentsOnly && $this->indexObjects;

        if (!$shouldIndexDocuments && !$shouldIndexObjects) {
            $io->warning('Nothing to index. Check your configuration or command options.');
            return Command::SUCCESS;
        }

        if ($shouldIndexDocuments) {
            $this->indexDocuments($io);
        }

        if ($shouldIndexObjects) {
            $this->indexObjects($io);
        }

        $io->newLine();
        $io->success([
            'Reindex messages dispatched successfully!',
        ]);

        return Command::SUCCESS;
    }

    private function indexDocuments(SymfonyStyle $io): void
    {
        $io->section('Indexing Documents');

        $listing = new DocumentListing();
        $listing->setCondition('type = :type', ['type' => 'page']);
        $listing->setUnpublished(true);

        $count = $listing->getTotalCount();
        $io->info(sprintf('Found %d documents', $count));

        if ($count === 0) {
            return;
        }

        $progressBar = $io->createProgressBar($count);
        $progressBar->start();

        foreach ($listing as $document) {
            $this->bus->dispatch(new IndexElementMessage(
                type: 'document',
                id: $document->getId(),
                delete: false
            ));
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);
    }

    private function indexObjects(SymfonyStyle $io): void
    {
        $io->section('Indexing Objects');

        $supportedClasses = $this->extractorRegistry->getSupportedClasses();

        if (empty($supportedClasses)) {
            $io->warning([
                'No object extractors registered.',
                'Create extractors implementing ObjectContentExtractorInterface',
                'and tag them with "insquare.search.object_extractor"'
            ]);
            return;
        }

        $io->info(sprintf(
            'Registered extractors for: %s',
            implode(', ', array_map(fn($c) => basename(str_replace('\\', '/', $c)), $supportedClasses))
        ));

        $classDefinitions = [];
        $missingDefinitions = [];
        $total = 0;

        foreach ($supportedClasses as $supportedClass) {
            $className = basename(str_replace('\\', '/', $supportedClass));
            $definition = ClassDefinition::getByName($className);

            if (!$definition instanceof ClassDefinition) {
                $missingDefinitions[] = $supportedClass;
                continue;
            }

            $classDefinitions[] = $definition;

            $listing = new DataObjectListing();
            $listing->setUnpublished(false);
            $listing->setCondition('o_classId = :classId', [
                'classId' => $definition->getId(),
            ]);

            $total += $listing->getTotalCount();
        }

        if (!empty($missingDefinitions)) {
            $io->warning(sprintf(
                'Missing class definitions for: %s',
                implode(', ', $missingDefinitions)
            ));
        }

        $io->info(sprintf('Found %d objects in supported classes', $total));

        if ($total === 0) {
            return;
        }

        $progressBar = $io->createProgressBar($total);
        $progressBar->start();

        $indexed = 0;
        $skipped = 0;

        foreach ($classDefinitions as $definition) {
            $listing = new DataObjectListing();
            $listing->setUnpublished(false);
            $listing->setCondition('o_classId = :classId', [
                'classId' => $definition->getId(),
            ]);

            foreach ($listing as $object) {
                if (!$object instanceof Concrete) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $this->bus->dispatch(new IndexElementMessage(
                    type: 'object',
                    id: $object->getId(),
                    delete: false
                ));
                $indexed++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $io->newLine(2);
        $io->info(sprintf('Dispatched: %d objects, Skipped: %d', $indexed, $skipped));
    }
}
