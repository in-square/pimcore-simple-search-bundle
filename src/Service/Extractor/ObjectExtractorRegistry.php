<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Extractor;

use Pimcore\Model\DataObject\Concrete;

class ObjectExtractorRegistry
{
    /** @var array<string, ObjectContentExtractorInterface> */
    private array $extractorsByClass = [];

    /** @var array<string, ObjectContentExtractorInterface> */
    private array $extractorsById;

    /**
     * @param iterable<ObjectContentExtractorInterface> $extractors
     */
    public function __construct(iterable $extractors)
    {
        $this->extractorsById = $extractors instanceof \Traversable
            ? iterator_to_array($extractors)
            : $extractors;

        $this->buildClassMapping();
    }

    public function getExtractorFor(Concrete $object): ObjectContentExtractorInterface
    {
        $class = $object::class;

        if (!isset($this->extractorsByClass[$class])) {
            throw new \RuntimeException(sprintf(
                'No extractor registered for class "%s". ' .
                'Create a class implementing ObjectContentExtractorInterface ' .
                'and tag it with "insquare.search.object_extractor".',
                $class
            ));
        }

        return $this->extractorsByClass[$class];
    }

    public function hasExtractorFor(Concrete $object): bool
    {
        return isset($this->extractorsByClass[$object::class]);
    }

    /**
     * @return array<string> List of supported DataObject FQCNs
     */
    public function getSupportedClasses(): array
    {
        return array_keys($this->extractorsByClass);
    }

    /**
     * @return array<ObjectContentExtractorInterface>
     */
    public function getAllExtractors(): array
    {
        return array_values($this->extractorsByClass);
    }

    private function buildClassMapping(): void
    {
        foreach ($this->extractorsById as $extractor) {
            $supportedClass = $extractor->getSupportedClass();

            if (isset($this->extractorsByClass[$supportedClass])) {
                throw new \RuntimeException(sprintf(
                    'Multiple extractors registered for class "%s": %s and %s',
                    $supportedClass,
                    $this->extractorsByClass[$supportedClass]::class,
                    $extractor::class
                ));
            }

            $this->extractorsByClass[$supportedClass] = $extractor;
        }
    }
}
