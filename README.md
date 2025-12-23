# Pimcore Simple Search Bundle
Bundle for full-text search in Pimcore 11 using MySQL FULLTEXT index
1. Instalacja
Zainstaluj bundle
```bash
   composer require insquare/pimcore-simple-search-bundle
```
2. Stwórz konfigurację
```bash
   touch config/packages/insquare_pimcore_simple_search.yaml
```



Podstawowa konfiguracja ()
```yaml
#config/packages/insquare_pimcore_simple_search.yaml
in_square_pimcore_simple_search:
   table_name: 'search_index'
   locales: ['pl', 'en']
   sites: [0]
   index_documents: true
   index_objects: true
   max_content_length: 20000
   snippet_length: 220
   use_boolean_mode: true
   min_search_length: 3
```

3. Utwórz extractory
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
4. Zarejestruj extractor jak tagged service

```yaml
#config/services.yaml
services:
  App\Search\Extractor\ProductExtractor:
    tags:
      - { name: 'insquare.search.object_extractor' }
```
Bundle automatycznie wykryje extractor i przypisze go do klasy Product.

5. Uruchom komendę
```bash
 bin/console insquare:search:install
```

# Reindeksuj wszystko
bin/console insquare:search:reindex

# Uruchom workera messenger
bin/console messenger:consume async -vv

Messenger
Route bundle message to async transport (recommended):

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    routing:
      InSquare\PimcoreMysqlSearchIndexBundle\Message\IndexElementMessage: async
```

`* * * * * /usr/bin/php /path/bin/console messenger:consume async --time-limit=50 --memory-limit=256M >> /path/var/log/search-consume.log 2>&1
`

Użycie w kontrolerze
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