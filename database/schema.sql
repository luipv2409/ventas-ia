-- ============================================================
-- SISTEMA DE VENTAS CON IA Y TELEGRAM
-- Script SQL - FASE 1: Arquitectura y Base de Datos
-- Compatibilidad: MySQL 5.7+ / MariaDB 10.3+
-- Codificación: UTF-8 (utf8mb4)
-- ============================================================

-- Crear y seleccionar la base de datos
CREATE DATABASE IF NOT EXISTS `ventas_ia`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `ventas_ia`;

-- ============================================================
-- TABLA: categorias
-- Propósito: Clasificar los productos en grupos lógicos.
-- ============================================================
CREATE TABLE IF NOT EXISTS `categorias` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nombre`      VARCHAR(100)    NOT NULL,
    `descripcion` TEXT            NULL,
    `slug`        VARCHAR(120)    NOT NULL UNIQUE COMMENT 'URL amigable, ej: electronica',
    `activa`      TINYINT(1)      NOT NULL DEFAULT 1,
    `creado_en`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: productos
-- Propósito: Catálogo completo de artículos disponibles.
-- La IA usará esta tabla para construir su contexto de respuesta.
-- ============================================================
CREATE TABLE IF NOT EXISTS `productos` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `categoria_id`  INT UNSIGNED    NOT NULL,
    `nombre`        VARCHAR(200)    NOT NULL,
    `descripcion`   TEXT            NULL     COMMENT 'Descripción detallada para el prompt de la IA',
    `precio`        DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    `stock`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `imagen_url`    VARCHAR(500)    NULL     COMMENT 'Ruta relativa o URL de la imagen',
    `sku`           VARCHAR(80)     NULL     UNIQUE COMMENT 'Código único de producto (Stock Keeping Unit)',
    `activo`        TINYINT(1)      NOT NULL DEFAULT 1,
    `creado_en`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_categoria` (`categoria_id`),
    INDEX `idx_activo`    (`activo`),
    CONSTRAINT `fk_producto_categoria`
        FOREIGN KEY (`categoria_id`)
        REFERENCES `categorias` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: clientes
-- Propósito: Datos del comprador para el proceso de checkout.
-- ============================================================
CREATE TABLE IF NOT EXISTS `clientes` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nombre`      VARCHAR(150)    NOT NULL,
    `telefono`    VARCHAR(30)     NULL,
    `email`       VARCHAR(200)    NULL,
    `direccion`   TEXT            NULL,
    `creado_en`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: pedidos
-- Propósito: Cabecera de cada orden de compra.
-- Estados: pendiente → confirmado → enviado → entregado / cancelado
-- ============================================================
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `cliente_id`    INT UNSIGNED    NOT NULL,
    `total`         DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    `estado`        ENUM(
                        'pendiente',
                        'confirmado',
                        'enviado',
                        'entregado',
                        'cancelado'
                    )               NOT NULL DEFAULT 'pendiente',
    `notas`         TEXT            NULL     COMMENT 'Instrucciones especiales del cliente',
    `notificado_telegram` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = ya se envió notificación',
    `creado_en`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_cliente`  (`cliente_id`),
    INDEX `idx_estado`   (`estado`),
    CONSTRAINT `fk_pedido_cliente`
        FOREIGN KEY (`cliente_id`)
        REFERENCES `clientes` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: detalle_pedidos
-- Propósito: Líneas de detalle (ítems) de cada pedido.
-- Almacenamos precio_unitario al momento de la compra
-- para mantener historial aunque el precio cambie.
-- ============================================================
CREATE TABLE IF NOT EXISTS `detalle_pedidos` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `pedido_id`       INT UNSIGNED    NOT NULL,
    `producto_id`     INT UNSIGNED    NOT NULL,
    `cantidad`        INT UNSIGNED    NOT NULL DEFAULT 1,
    `precio_unitario` DECIMAL(10, 2)  NOT NULL COMMENT 'Precio en el momento de la compra (snapshot)',
    `subtotal`        DECIMAL(10, 2)  NOT NULL DEFAULT 0.00 COMMENT 'Calculado desde PHP: cantidad * precio_unitario',
    PRIMARY KEY (`id`),
    INDEX `idx_pedido`   (`pedido_id`),
    INDEX `idx_producto` (`producto_id`),
    CONSTRAINT `fk_detalle_pedido`
        FOREIGN KEY (`pedido_id`)
        REFERENCES `pedidos` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_detalle_producto`
        FOREIGN KEY (`producto_id`)
        REFERENCES `productos` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: chat_logs (Opcional — útil para auditoría de la IA)
-- Propósito: Guardar el historial de conversaciones con la IA.
-- ============================================================
CREATE TABLE IF NOT EXISTS `chat_logs` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `session_id`    VARCHAR(100)    NOT NULL COMMENT 'ID de sesión PHP del usuario',
    `pregunta`      TEXT            NOT NULL,
    `respuesta`     TEXT            NOT NULL,
    `tokens_usados` INT UNSIGNED    NULL,
    `creado_en`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE PRUEBA (SEED DATA)
-- Categorías y productos de ejemplo para desarrollo.
-- ============================================================

INSERT INTO `categorias` (`nombre`, `descripcion`, `slug`) VALUES
('Electrónica',    'Smartphones, tablets y accesorios tecnológicos',  'electronica'),
('Ropa',           'Prendas de vestir para hombre y mujer',            'ropa'),
('Hogar',          'Artículos para el hogar y decoración',             'hogar'),
('Alimentos',      'Productos alimenticios y bebidas',                 'alimentos');

INSERT INTO `productos` (`categoria_id`, `nombre`, `descripcion`, `precio`, `stock`, `sku`) VALUES
(1, 'Smartphone Galaxy A55',   'Pantalla AMOLED 6.6", 128GB, cámara 50MP, batería 5000mAh. Ideal para fotos y redes sociales.', 350.00, 20, 'ELEC-001'),
(1, 'Auriculares Bluetooth X3','Sonido HD inalámbrico, 30h de batería, cancelación de ruido activa, plegables.',                 45.00,  50, 'ELEC-002'),
(1, 'Tablet Pro 10"',          'Pantalla IPS 10 pulgadas, 64GB, WiFi, ideal para estudio y entretenimiento.',                   200.00, 15, 'ELEC-003'),
(2, 'Camiseta Polo Classic',   'Polo 100% algodón peinado, disponible en tallas S, M, L, XL. Colores: blanco, negro, azul.',    18.00,  100,'ROPA-001'),
(2, 'Zapatillas Runner Pro',   'Zapatillas deportivas ultralivianas, suela de goma antideslizante, tallas 36-44.',               65.00,  40, 'ROPA-002'),
(3, 'Licuadora 600W',          'Licuadora de 600W, vaso de vidrio 1.5L, 5 velocidades + pulso, cuchillas de acero inoxidable.', 55.00,  30, 'HOGA-001'),
(3, 'Juego de Sábanas Queen',  'Juego de sábanas 100% microfibra, tamaño Queen, 4 piezas, varios colores disponibles.',          35.00,  60, 'HOGA-002'),
(4, 'Café Molido Premium 500g','Café arábica molido, tostado medio, origen colombiano, 500 gramos.',                             12.00,  200,'ALIM-001');

-- ============================================================
-- FIN DEL SCRIPT - FASE 1 COMPLETADA
-- ============================================================
