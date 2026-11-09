-- ============================================================================
-- ESQUEMA CONSOLIDADO DE PRODUCCIÓN — PORTAL Y CHATBOT ULAT
-- Archivo: database/production_schema.sql
-- Motor: MariaDB 10.11+ / MySQL 8.0+ (InnoDB, utf8mb4_unicode_ci)
--
-- NOTAS DE SEGURIDAD:
-- 1. No incluye tablas heredadas de e-commerce (productos, categorias, pedidos, etc.).
-- 2. No inserta cuentas administrativas con contraseñas por defecto. Tras levantar
--    el entorno por primera vez, crear el administrador por CLI:
--      docker compose exec app php scripts/create_admin.php admin "<CLAVE_SEGURA>" "Admin ULAT"
--    e indexar el corpus institucional ULAT:
--      docker compose exec app php scripts/index_rag_ulat.php
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Cuentas de administración del portal ULAT
CREATE TABLE IF NOT EXISTS `usuarios_admin` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `nombre` VARCHAR(100) DEFAULT NULL,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuarios_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Parámetros institucionales globales
CREATE TABLE IF NOT EXISTS `configuracion` (
    `clave` VARCHAR(50) NOT NULL,
    `valor` TEXT NOT NULL,
    PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion` (`clave`, `valor`) VALUES
    ('nombre_tienda', 'Universidad Latinoamericana (ULAT)'),
    ('nombre_asesor', 'Guía ULAT'),
    ('contacto_telefono', '(4) 4509488 - 4221004'),
    ('whatsapp_admisiones', '59179795778'),
    ('contacto_email', 'latinoamericana@ulat.edu.bo')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

-- 3. Telemetría y observabilidad conversacional (con session_id anonimizado vía HMAC)
CREATE TABLE IF NOT EXISTS `chat_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(100) NOT NULL COMMENT 'HMAC-SHA256 truncado de sesión (sid_...)',
    `pregunta` TEXT NOT NULL,
    `respuesta` TEXT NOT NULL,
    `tokens_usados` INT UNSIGNED DEFAULT NULL,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `latencia_ms` INT UNSIGNED DEFAULT NULL COMMENT 'Latencia total de respuesta en ms',
    `tokens_prompt` INT UNSIGNED DEFAULT NULL,
    `tokens_completado` INT UNSIGNED DEFAULT NULL,
    `tokens_cache_hit` INT UNSIGNED DEFAULT 0,
    `rag_score_max` DECIMAL(5,4) DEFAULT NULL,
    `rag_fuentes` TEXT DEFAULT NULL,
    `acciones_json` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_chat_logs_session` (`session_id`),
    KEY `idx_chat_logs_creado_en` (`creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Almacén vectorial e índice híbrido FULLTEXT para RAG institucional ULAT
CREATE TABLE IF NOT EXISTS `rag_documents` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `collection` VARCHAR(50) NOT NULL COMMENT 'admisiones, becas, carreras, institucional, reglamentos',
    `doc_id` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `metadata_json` JSON DEFAULT NULL,
    `embedding` JSON NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_collection_doc` (`collection`, `doc_id`),
    KEY `idx_collection` (`collection`),
    FULLTEXT KEY `ft_title_content` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Metadatos del corpus RAG (vocabulario IDF)
CREATE TABLE IF NOT EXISTS `rag_meta` (
    `meta_key` VARCHAR(64) NOT NULL,
    `meta_value` LONGTEXT NOT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Caché persistente de consultas RAG frecuentes
CREATE TABLE IF NOT EXISTS `rag_query_cache` (
    `query_hash` CHAR(64) NOT NULL,
    `query_raw` VARCHAR(255) NOT NULL,
    `rag_context` MEDIUMTEXT NOT NULL,
    `rag_chunks` JSON DEFAULT NULL,
    `score_max` DECIMAL(5,4) DEFAULT NULL,
    `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `expira_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`query_hash`),
    KEY `idx_expira` (`expira_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Ventanas atómicas de Rate Limiting por IP/remitente/acción
CREATE TABLE IF NOT EXISTS `chat_rate_limits` (
    `bucket` VARCHAR(190) NOT NULL,
    `window_start` INT UNSIGNED NOT NULL,
    `hits` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`bucket`, `window_start`),
    KEY `idx_window` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Memoria conversacional de canales externos (WhatsApp / Messenger / API)
CREATE TABLE IF NOT EXISTS `chat_externo_sesiones` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `canal` VARCHAR(32) NOT NULL DEFAULT 'whatsapp',
    `remitente` VARCHAR(128) NOT NULL,
    `rol` ENUM('user', 'assistant') NOT NULL,
    `mensaje` TEXT NOT NULL,
    `metadata` JSON DEFAULT NULL,
    `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_canal_remitente_fecha` (`canal`, `remitente`, `creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Hilos del asistente analítico administrativo (Jarvis BI ULAT)
CREATE TABLE IF NOT EXISTS `chat_admin_hilos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo` VARCHAR(255) NOT NULL,
    `admin_id` INT UNSIGNED NOT NULL,
    `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_chat_admin_hilos_owner` (`admin_id`, `actualizado_en`),
    CONSTRAINT `fk_chat_admin_hilos_admin`
        FOREIGN KEY (`admin_id`) REFERENCES `usuarios_admin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Mensajes de hilos administrativos
CREATE TABLE IF NOT EXISTS `chat_admin_mensajes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `hilo_id` INT UNSIGNED NOT NULL,
    `role` ENUM('user', 'assistant') NOT NULL,
    `content` TEXT NOT NULL,
    `tipo` VARCHAR(50) DEFAULT 'texto',
    `datos_json` TEXT DEFAULT NULL,
    `creado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_chat_admin_mensajes_hilo` (`hilo_id`),
    CONSTRAINT `fk_chat_admin_mensajes_hilo`
        FOREIGN KEY (`hilo_id`) REFERENCES `chat_admin_hilos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Prospectos y leads de Admisiones ULAT
CREATE TABLE IF NOT EXISTS `admisiones_prospectos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(150) NOT NULL,
    `telefono` VARCHAR(50) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `carrera_interes` VARCHAR(150) NOT NULL,
    `facultad` VARCHAR(150) DEFAULT NULL,
    `origen` ENUM('chatbot', 'formulario_web', 'whatsapp_directo') NOT NULL DEFAULT 'chatbot',
    `notas` TEXT DEFAULT NULL,
    `estado` ENUM('nuevo', 'contactado', 'en_seguimiento', 'matriculado', 'descartado') NOT NULL DEFAULT 'nuevo',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_carrera` (`carrera_interes`),
    KEY `idx_estado` (`estado`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

