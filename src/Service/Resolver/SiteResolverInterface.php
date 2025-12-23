<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Resolver;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;

interface SiteResolverInterface
{
    public function resolveForDocument(Page $document): int;

    public function resolveForObject(Concrete $object): int;
}
