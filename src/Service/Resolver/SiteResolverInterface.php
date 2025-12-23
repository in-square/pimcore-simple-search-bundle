<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Resolver;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;

interface SiteResolverInterface
{
    /**
     * Resolve site ID for a document
     */
    public function resolveForDocument(Page $document): int;

    /**
     * Resolve site ID for an object
     */
    public function resolveForObject(Concrete $object): int;
}
