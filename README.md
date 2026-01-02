# Pimcore Simple Search Bundle
Bundle for full-text search in Pimcore 11 using MySQL FULLTEXT index
### Install the bundle
```bash
   composer require in-square/pimcore-simple-search-bundle
```
### Create a configuration
```bash
   touch config/packages/in_square_pimcore_simple_search.yaml
```

#### Basic configuration
```yaml
#config/packages/insquare_pimcore_simple_search.yaml
in_square_pimcore_simple_search:
    table_name: 'insquare_search_index'
    locales: ['pl', 'en']
    sites: [0]
    index_documents: true
    index_objects: true
    max_content_length: 20000
    snippet_length: 220
    use_boolean_mode: true
    min_search_length: 3
```

### Register bundle in bundles.php
```php
<?php
// config/bundles.php
return [
    // other bundles
    InSquare\PimcoreSimpleSearchBundle\InSquarePimcoreSimpleSearchBundle::class => ['all' => true],
];
```

### Create object extractors
```php
<?php
// src/Search/Extractor/ProductExtractor.php

namespace App\Search\Extractor;

use InSquare\PimcoreSimpleSearchBundle\Service\Extractor\ObjectContentExtractorInterface;
use InSquare\PimcoreSimpleSearchBundle\Service\Text\TextNormalizer;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Product;

final readonly class ProductExtractor implements ObjectContentExtractorInterface
{
    public function getSupportedClass(): string
    {
        return Product::class;
    }

    public function extractContent(Concrete $object, string $locale): ?string
    {
        if (!$object instanceof Product) {
            return null;
        }

        return TextNormalizer::join([
            $object->getName($locale),
            $object->getDescription($locale),
            $object->getSku(),
        ]);
    }
}
```
#### Register extractor as a tagged service

```yaml
#config/services.yaml
services:
  App\Search\Extractor\ProductExtractor:
    tags:
      - { name: 'insquare.search.object_extractor' }
```

Bundle will automatically detect the extractor and assign it to the Product class.

### Run the installation command
```bash
 bin/console insquare:search:install
```

### Index all items
```bash
bin/console insquare:search:reindex
```

## Symfony Messenger

### Start the messenger worker
```php
bin/console messenger:consume async -vv
```

Route bundle message to async transport (recommended):

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    routing:
        InSquare\PimcoreSimpleSearchBundle\Message\IndexElementMessage: async
```

### Use in the controller

```php
use InSquare\PimcoreSimpleSearchBundle\Service\SearchService;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchService $searchService
    ) {}

    #[Route('/search')]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q');
        $locale = $request->getLocale();
        
        $results = $this->searchService->search(
            query: $query,
            locale: $locale,
            site: 0,
            limit: 20,
            offset: 0
        );
        
        return $this->render('search/results.html.twig', [
            'results' => $results,
            'query' => $query
        ]);
    }
}
```