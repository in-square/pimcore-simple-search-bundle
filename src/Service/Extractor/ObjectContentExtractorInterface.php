<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Extractor;

use Pimcore\Model\DataObject\Concrete;

interface ObjectContentExtractorInterface
{
    public function getSupportedClass(): string;
    public function extractContent(Concrete $object, string $locale): ?string;
}
