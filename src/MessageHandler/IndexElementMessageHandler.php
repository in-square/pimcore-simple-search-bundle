<?php

namespace InSquare\PimcoreSimpleSearchBundle\MessageHandler;

use InSquare\PimcoreSimpleSearchBundle\Message\IndexElementMessage;
use InSquare\PimcoreSimpleSearchBundle\Service\Indexer;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document\Page;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class IndexElementMessageHandler
{
    public function __construct(
        private Indexer $indexer,
        private LoggerInterface $logger
    ) {}

    public function __invoke(IndexElementMessage $msg): void
    {
        try {
            if ($msg->delete) {
                $this->handleDelete($msg);
                return;
            }

            match ($msg->type) {
                'document' => $this->handleDocument($msg),
                'object' => $this->handleObject($msg),
                default => throw new \InvalidArgumentException(
                    sprintf('Unknown type "%s"', $msg->type)
                )
            };
        } catch (\Exception $e) {
            $this->logger->error('Failed to index element', [
                'type' => $msg->type,
                'id' => $msg->id,
                'delete' => $msg->delete,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function handleDelete(IndexElementMessage $msg): void
    {
        $this->indexer->deleteElement($msg->type, $msg->id);

        $this->logger->info('Deleted from search index', [
            'type' => $msg->type,
            'id' => $msg->id
        ]);
    }

    private function handleDocument(IndexElementMessage $msg): void
    {
        $doc = Page::getById($msg->id);

        if (!$doc instanceof Page) {
            $this->logger->warning('Document not found', [
                'id' => $msg->id
            ]);
            return;
        }

        $this->indexer->indexDocument($doc);

        $this->logger->debug('Indexed document', [
            'id' => $msg->id,
            'path' => $doc->getFullPath()
        ]);
    }

    private function handleObject(IndexElementMessage $msg): void
    {
        $obj = DataObject::getById($msg->id);

        if (!$obj instanceof DataObject\Concrete) {
            $this->logger->warning('Object not found or not concrete', [
                'id' => $msg->id
            ]);
            return;
        }

        $this->indexer->indexObject($obj);

        $this->logger->debug('Indexed object', [
            'id' => $msg->id,
            'class' => $obj::class,
            'path' => $obj->getFullPath()
        ]);
    }
}
