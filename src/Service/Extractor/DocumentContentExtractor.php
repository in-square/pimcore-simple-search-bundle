<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Extractor;

use InSquare\PimcoreSimpleSearchBundle\Service\Text\TextNormalizer;
use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Editable\Textarea;
use Pimcore\Model\Document\Editable\Wysiwyg;
use Pimcore\Model\Document\Page;

final readonly class DocumentContentExtractor implements DocumentExtractorInterface
{
    public function __construct(
        private int $maxContentLength
    ) {}

    public function extractContent(Page $doc): ?string
    {
        $text = [];

        foreach ($doc->getEditables() as $editable) {
            if($editable instanceof Wysiwyg || $editable instanceof Input || $editable instanceof Textarea){
                $text[] = TextNormalizer::normalize($editable->getText());
            }
        }

        $content = join(' ', $text);

        return $this->cut($content);
    }

    private function cut(string $s): string
    {
        if (mb_strlen($s) > $this->maxContentLength) {
            return mb_substr($s, 0, $this->maxContentLength);
        }
        return $s;
    }
}
