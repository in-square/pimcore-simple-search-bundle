CREATE TABLE IF NOT EXISTS insquare_search_index (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    type VARCHAR(16) NOT NULL,
    class_name VARCHAR(190) NULL,
    ext_id BIGINT UNSIGNED NOT NULL,
    locale VARCHAR(8) NOT NULL,
    site TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    content MEDIUMTEXT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_item (type, ext_id, locale, site),
    KEY idx_site_locale_pub (site, locale, is_published),
    FULLTEXT KEY ft_content (content)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
