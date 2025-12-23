<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Extractor;

use Pimcore\Model\Document\Page;

interface DocumentExtractorInterface
{
    public function extractContent(Page $doc): ?string;
}
