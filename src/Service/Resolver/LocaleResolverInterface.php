<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Resolver;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;

interface LocaleResolverInterface
{
    public function resolveForDocument(Page $document): ?string;

    /**
     * @return iterable<string>
     */
    public function resolveForObject(Concrete $object): iterable;
}
