-- Tabla para almacenar fragmentos (chunks) y vectores RAG
CREATE TABLE IF NOT EXISTS `rag_documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `collection` VARCHAR(50) NOT NULL COMMENT 'catalogo, politicas, faqs',
    `doc_id` VARCHAR(100) NOT NULL COMMENT 'ID de producto o slug de documento',
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL COMMENT 'Texto del fragmento para inyectar en prompt',
    `metadata_json` JSON NULL,
    `embedding` JSON NOT NULL COMMENT 'Vector de pesos y dimensiones flotantes',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_collection_doc` (`collection`, `doc_id`),
    INDEX `idx_collection` (`collection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
