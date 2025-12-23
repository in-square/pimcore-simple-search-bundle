<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Resolver;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;
use Pimcore\Tool\Frontend;

final readonly class SimpleSiteResolver implements SiteResolverInterface
{
    /**
     * @param array<int> $sites
     */
    public function __construct(
        private array $sites
    ) {}

    public function resolveForDocument(Page $document): int
    {
        $siteId = Frontend::getSiteIdForDocument($document);

        if ($siteId !== null && $this->isValidSite($siteId)) {
            return $siteId;
        }

        return $this->sites[0] ?? 0;
    }

    public function resolveForObject(Concrete $object): int
    {
        return $this->sites[0] ?? 0;
    }

    private function isValidSite(int $siteId): bool
    {
        return in_array($siteId, $this->sites, true);
    }
}
