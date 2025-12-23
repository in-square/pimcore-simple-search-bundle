<?php

namespace InSquare\PimcoreSimpleSearchBundle\EventSubscriber;

use InSquare\PimcoreSimpleSearchBundle\Message\IndexElementMessage;
use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\ObjectExtractorRegistry;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class PimcoreIndexSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private ObjectExtractorRegistry $extractorRegistry,
        private LoggerInterface $logger,
        private bool $indexDocuments,
        private bool $indexObjects,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::POST_ADD => 'onDocumentSave',
            DocumentEvents::POST_UPDATE => 'onDocumentSave',
            DocumentEvents::POST_DELETE => 'onDocumentDelete',

            DataObjectEvents::POST_ADD => 'onObjectSave',
            DataObjectEvents::POST_UPDATE => 'onObjectSave',
            DataObjectEvents::POST_DELETE => 'onObjectDelete',
        ];
    }

    public function onDocumentSave(DocumentEvent $event): void
    {
        if (!$this->indexDocuments) {
            return;
        }

        $document = $event->getDocument();

        if (!$document instanceof Page) {
            return;
        }

        try {
            $this->bus->dispatch(new IndexElementMessage(
                type: 'document',
                id: $document->getId(),
                delete: false
            ));

            $this->logger->debug('Dispatched document for indexing', [
                'id' => $document->getId(),
                'path' => $document->getFullPath(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to dispatch document for indexing', [
                'id' => $document->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function onDocumentDelete(DocumentEvent $event): void
    {
        if (!$this->indexDocuments) {
            return;
        }

        $document = $event->getDocument();

        if (!$document instanceof Page) {
            return;
        }

        try {
            $this->bus->dispatch(new IndexElementMessage(
                type: 'document',
                id: $document->getId(),
                delete: true
            ));

            $this->logger->debug('Dispatched document for deletion from index', [
                'id' => $document->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to dispatch document for deletion', [
                'id' => $document->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function onObjectSave(DataObjectEvent $event): void
    {
        if (!$this->indexObjects) {
            return;
        }

        $object = $event->getObject();

        if (!$object instanceof Concrete) {
            return;
        }

        if (!$this->extractorRegistry->hasExtractorFor($object)) {
            return;
        }

        try {
            $this->bus->dispatch(new IndexElementMessage(
                type: 'object',
                id: $object->getId(),
                delete: false
            ));

            $this->logger->debug('Dispatched object for indexing', [
                'id' => $object->getId(),
                'class' => $object::class,
                'path' => $object->getFullPath(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to dispatch object for indexing', [
                'id' => $object->getId(),
                'class' => $object::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function onObjectDelete(DataObjectEvent $event): void
    {
        if (!$this->indexObjects) {
            return;
        }

        $object = $event->getObject();

        if (!$object instanceof Concrete) {
            return;
        }

        if (!$this->extractorRegistry->hasExtractorFor($object)) {
            return;
        }

        try {
            $this->bus->dispatch(new IndexElementMessage(
                type: 'object',
                id: $object->getId(),
                delete: true
            ));

            $this->logger->debug('Dispatched object for deletion from index', [
                'id' => $object->getId(),
                'class' => $object::class,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to dispatch object for deletion', [
                'id' => $object->getId(),
                'class' => $object::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
