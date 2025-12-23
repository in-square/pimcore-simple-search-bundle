<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Resolver;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;

final readonly class SimpleLocaleResolver implements LocaleResolverInterface
{
    /**
     * @param array<string> $locales
     */
    public function __construct(
        private array $locales
    ) {}

    public function resolveForDocument(Page $document): ?string
    {
        $locale = $document->getProperty('language');

        if ($locale !== null && $this->isValidLocale($locale)) {
            return $locale;
        }

        return $this->locales[0] ?? null;
    }

    public function resolveForObject(Concrete $object): iterable
    {
        return $this->locales;
    }

    private function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->locales, true);
    }
}
