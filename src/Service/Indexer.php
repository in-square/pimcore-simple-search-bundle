<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service;

use InSquare\PimcoreSimpleSearchBundle\Repository\SearchIndexRepository;
use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\DocumentExtractorInterface;
use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\ObjectExtractorRegistry;
use InSquare\PimcoreSimpleSearchBundle\Service\Resolver\LocaleResolverInterface;
use InSquare\PimcoreSimpleSearchBundle\Service\Resolver\SiteResolverInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;
use Psr\Log\LoggerInterface;

final readonly class Indexer
{
    public function __construct(
        private SearchIndexRepository      $repository,
        private DocumentExtractorInterface $documentExtractor,
        private ObjectExtractorRegistry    $extractorRegistry,
        private LocaleResolverInterface    $localeResolver,
        private SiteResolverInterface      $siteResolver,
        private LoggerInterface            $logger,
        private int                        $maxContentLength
    ) {}

    public function indexDocument(Page $document): void
    {
        try {
            $site = $this->siteResolver->resolveForDocument($document);
            $locale = $this->localeResolver->resolveForDocument($document);

            if ($locale === null) {
                $this->logger->info('Skipping document without locale', [
                    'id' => $document->getId(),
                    'path' => $document->getFullPath(),
                ]);
                return;
            }

            $content = $this->documentExtractor->extractContent($document);

            $this->repository->upsert([
                'type' => 'document',
                'class_name' => null,
                'ext_id' => $document->getId(),
                'locale' => $locale,
                'site' => $site,
                'is_published' => $document->isPublished() ? 1 : 0,
                'content' => $this->truncateContent($content),
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            $this->logger->debug('Indexed document', [
                'id' => $document->getId(),
                'locale' => $locale,
                'site' => $site,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to index document', [
                'id' => $document->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function indexObject(Concrete $object): void
    {
        try {
            if (!$this->extractorRegistry->hasExtractorFor($object)) {
                $this->logger->debug('No extractor for object class', [
                    'id' => $object->getId(),
                    'class' => $object::class,
                ]);
                return;
            }

            $extractor = $this->extractorRegistry->getExtractorFor($object);
            $site = $this->siteResolver->resolveForObject($object);

            foreach ($this->localeResolver->resolveForObject($object) as $locale) {
                $content = $extractor->extractContent($object, $locale);

                $this->repository->upsert([
                    'type' => 'object',
                    'class_name' => $object::class,
                    'ext_id' => $object->getId(),
                    'locale' => $locale,
                    'site' => $site,
                    'is_published' => $object->isPublished() ? 1 : 0,
                    'content' => $this->truncateContent($content),
                    'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]);

                $this->logger->debug('Indexed object', [
                    'id' => $object->getId(),
                    'class' => $object::class,
                    'locale' => $locale,
                    'site' => $site,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to index object', [
                'id' => $object->getId(),
                'class' => $object::class,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function deleteElement(string $type, int $id): void
    {
        try {
            $this->repository->deleteOne($type, $id);

            $this->logger->info('Deleted from search index', [
                'type' => $type,
                'id' => $id,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete from index', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function truncateContent(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        $content = trim($content);

        if ($content === '') {
            return null;
        }

        if (mb_strlen($content) > $this->maxContentLength) {
            return mb_substr($content, 0, $this->maxContentLength);
        }

        return $content;
    }
}
