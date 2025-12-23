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

        // Fallback to first configured site
        return $this->sites[0] ?? 0;
    }

    public function resolveForObject(Concrete $object): int
    {
        // For objects, return first configured site
        // Override this in your app if objects have site-specific logic
        return $this->sites[0] ?? 0;
    }

    private function isValidSite(int $siteId): bool
    {
        return in_array($siteId, $this->sites, true);
    }
}
