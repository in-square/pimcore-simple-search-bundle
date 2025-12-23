<?php

namespace InSquare\PimcoreSimpleSearchBundle\Repository;

use Doctrine\DBAL\Connection;

final readonly class SearchIndexRepository
{
    private const ALLOWED_TYPES = ['document', 'object'];

    public function __construct(
        private Connection $connection,
        private string $tableName
    ) {}


    /**
     * @param array{
     *     type: string,
     *     class_name: string|null,
     *     ext_id: int,
     *     locale: string,
     *     site: int,
     *     is_published: int,
     *     content: string|null,
     *     updated_at: string
     * } $row
     * @throws Exception
     */
    public function upsert(array $row): void
    {
        $this->validateRow($row);

        $sql = sprintf(
            "INSERT INTO %s
              (type, class_name, ext_id, locale, site, is_published, content, updated_at)
             VALUES
              (:type, :class_name, :ext_id, :locale, :site, :is_published, :content, :updated_at)
             ON DUPLICATE KEY UPDATE
              class_name = VALUES(class_name),
              is_published = VALUES(is_published),
              content = VALUES(content),
              updated_at = VALUES(updated_at)",
            $this->tableName
        );

        $this->connection->executeStatement($sql, $row);
    }

    /**
     * Delete single element by type and ID
     * @throws Exception
     */
    public function deleteOne(string $type, int $extId): void
    {
        $this->validateType($type);

        $sql = sprintf(
            "DELETE FROM %s WHERE type = :type AND ext_id = :ext_id",
            $this->tableName
        );

        $this->connection->executeStatement($sql, [
            'type' => $type,
            'ext_id' => $extId
        ]);
    }

    /**
     * Delete all entries (for clear command)
     * @throws Exception
     */
    public function deleteAll(): void
    {
        $sql = sprintf("TRUNCATE TABLE %s", $this->tableName);
        $this->connection->executeStatement($sql);
    }

    /**
     * Get statistics about indexed content
     * @return array{total: int, documents: int, objects: int, published: int, unpublished: int}
     * @throws Exception
     */
    public function getStatistics(): array
    {
        $sql = sprintf(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN type = 'document' THEN 1 ELSE 0 END) as documents,
                SUM(CASE WHEN type = 'object' THEN 1 ELSE 0 END) as objects,
                SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as unpublished
            FROM %s",
            $this->tableName
        );

        $result = $this->connection->fetchAssociative($sql);

        return [
            'total' => (int) ($result['total'] ?? 0),
            'documents' => (int) ($result['documents'] ?? 0),
            'objects' => (int) ($result['objects'] ?? 0),
            'published' => (int) ($result['published'] ?? 0),
            'unpublished' => (int) ($result['unpublished'] ?? 0),
        ];
    }

    /**
     * Check if element exists in index
     */
    public function exists(string $type, int $extId, string $locale, int $site): bool
    {
        $this->validateType($type);

        $sql = sprintf(
            "SELECT 1 FROM %s
             WHERE type = :type AND ext_id = :ext_id AND locale = :locale AND site = :site
             LIMIT 1",
            $this->tableName
        );

        $result = $this->connection->fetchOne($sql, [
            'type' => $type,
            'ext_id' => $extId,
            'locale' => $locale,
            'site' => $site,
        ]);

        return $result !== false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function validateRow(array $row): void
    {
        $required = ['type', 'ext_id', 'locale', 'site', 'is_published', 'updated_at'];

        foreach ($required as $field) {
            if (!isset($row[$field])) {
                throw new \InvalidArgumentException(sprintf(
                    'Missing required field "%s" in row data',
                    $field
                ));
            }
        }

        $this->validateType($row['type']);

        if (!is_int($row['ext_id']) || $row['ext_id'] <= 0) {
            throw new \InvalidArgumentException('Field "ext_id" must be a positive integer');
        }

        if (!is_string($row['locale']) || $row['locale'] === '') {
            throw new \InvalidArgumentException('Field "locale" must be a non-empty string');
        }

        if (!is_int($row['site']) || $row['site'] < 0) {
            throw new \InvalidArgumentException('Field "site" must be a non-negative integer');
        }

        if (!in_array($row['is_published'], [0, 1], true)) {
            throw new \InvalidArgumentException('Field "is_published" must be 0 or 1');
        }
    }

    private function validateType(string $type): void
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid type "%s". Allowed types: %s',
                $type,
                implode(', ', self::ALLOWED_TYPES)
            ));
        }
    }
}
