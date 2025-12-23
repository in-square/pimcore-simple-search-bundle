<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service;

use Doctrine\DBAL\Connection;

readonly class SearchService
{
    public function __construct(
        private Connection $connection,
        private string     $tableName,
        private int        $snippetLength,
        private bool       $useBooleanMode,
        private int        $minSearchLength
    ) {}


    public function search(
        string $query,
        string $locale,
        int $site = 0,
        int $limit = 20,
        int $offset = 0,
        bool $publishedOnly = true
    ): array {
        if (mb_strlen($query) < $this->minSearchLength) {
            return [];
        }

        $query = $this->prepareQuery($query);

        $sql = sprintf(
            "SELECT
                id,
                type,
                class_name,
                ext_id,
                locale,
                site,
                is_published,
                MATCH(content) AGAINST(:query %s) AS relevance,
                %s AS snippet
            FROM %s
            WHERE
                MATCH(content) AGAINST(:query %s)
                AND locale = :locale
                AND site = :site
                %s
            ORDER BY relevance DESC, updated_at DESC
            LIMIT :limit OFFSET :offset",
            $this->useBooleanMode ? 'IN BOOLEAN MODE' : '',
            $this->getSnippetExpression(),
            $this->tableName,
            $this->useBooleanMode ? 'IN BOOLEAN MODE' : '',
            $publishedOnly ? 'AND is_published = 1' : ''
        );

        $params = [
            'query' => $query,
            'locale' => $locale,
            'site' => $site,
            'limit' => $limit,
            'offset' => $offset,
        ];

        $types = [
            'limit' => \PDO::PARAM_INT,
            'offset' => \PDO::PARAM_INT,
            'site' => \PDO::PARAM_INT,
        ];

        $results = $this->connection->fetchAllAssociative($sql, $params, $types);

        return $results;
    }

    public function count(
        string $query,
        string $locale,
        int $site = 0,
        bool $publishedOnly = true
    ): int {
        if (mb_strlen($query) < $this->minSearchLength) {
            return 0;
        }

        $query = $this->prepareQuery($query);

        $sql = sprintf(
            "SELECT COUNT(*) as total
            FROM %s
            WHERE
                MATCH(content) AGAINST(:query %s)
                AND locale = :locale
                AND site = :site
                %s",
            $this->tableName,
            $this->useBooleanMode ? 'IN BOOLEAN MODE' : '',
            $publishedOnly ? 'AND is_published = 1' : ''
        );

        $result = $this->connection->fetchAssociative($sql, [
            'query' => $query,
            'locale' => $locale,
            'site' => $site,
        ]);

        return (int) ($result['total'] ?? 0);
    }

    private function prepareQuery(string $query): string
    {
        $query = trim($query);

        if (!$this->useBooleanMode) {
            return $query;
        }

        $words = explode(' ', $query);
        $words = array_filter($words, fn($w) => mb_strlen($w) >= $this->minSearchLength);

        if (empty($words)) {
            return $query;
        }

        return implode(' ', array_map(fn($w) => "+{$w}*", $words));
    }

    private function getSnippetExpression(): string
    {
        return sprintf(
            "SUBSTRING(content, 1, %d)",
            $this->snippetLength
        );
    }

    private function cleanSnippet(string $snippet): string
    {
        $snippet = trim($snippet);

        if (mb_strlen($snippet) >= $this->snippetLength) {
            $snippet = mb_substr($snippet, 0, $this->snippetLength - 3) . '...';
        }

        return $snippet;
    }
}
