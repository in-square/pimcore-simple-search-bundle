<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Resolver;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;

interface LocaleResolverInterface
{
    /**
     * Resolve locale for a document
     */
    public function resolveForDocument(Page $document): ?string;

    /**
     * Resolve all locales for an object
     * @return iterable<string>
     */
    public function resolveForObject(Concrete $object): iterable;
}
