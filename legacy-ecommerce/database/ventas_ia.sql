-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-06-2026 a las 23:46:47
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ventas_ia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas_stock`
--

CREATE TABLE `alertas_stock` (
  `id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `umbral` int(10) UNSIGNED NOT NULL DEFAULT 5,
  `notificado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `slug` varchar(120) NOT NULL COMMENT 'URL amigable, ej: electronica',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `slug`, `activa`, `creado_en`) VALUES
(1, 'Electrónica', 'Smartphones, tablets y accesorios tecnológicos', 'electronica', 1, '2026-05-13 14:52:34'),
(2, 'Ropa', 'Prendas de vestir para hombre y mujer', 'ropa', 1, '2026-05-13 14:52:34'),
(3, 'Hogar', 'Artículos para el hogar y decoración', 'hogar', 1, '2026-05-13 14:52:34'),
(4, 'Alimentos', 'Productos alimenticios y bebidas', 'alimentos', 1, '2026-05-13 14:52:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chat_logs`
--

CREATE TABLE `chat_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(100) NOT NULL COMMENT 'ID de sesión PHP del usuario',
  `pregunta` text NOT NULL,
  `respuesta` text NOT NULL,
  `tokens_usados` int(10) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `chat_logs`
--

INSERT INTO `chat_logs` (`id`, `session_id`, `pregunta`, `respuesta`, `tokens_usados`, `creado_en`) VALUES
(1, '45dlkek8riqbhmcc99m6h12vdg', '¿Cuánto cuesta el Galaxy A55?', 'El Smartphone Galaxy A55 cuesta $350.00.', NULL, '2026-05-13 15:08:45'),
(2, '45dlkek8riqbhmcc99m6h12vdg', '¿Cuánto cuesta el Galaxy A55?', 'El Smartphone Galaxy A55 cuesta $350.00.', NULL, '2026-05-13 15:09:00'),
(3, 'lqc75gaiuqd12g8gd7fobau326', 'Cuanto cuesta el galaxy 55?', 'El precio del Smartphone Galaxy A55 es de $350.00.', NULL, '2026-05-18 15:33:55'),
(4, 'lqc75gaiuqd12g8gd7fobau326', 'Que caracteristicas tiene?', 'Lo siento, necesito saber qué producto te interesa para proporcionarte información detallada sobre sus características. ¿Puedes mencionar el nombre del producto que te gustaría saber más sobre?', NULL, '2026-05-18 15:34:10'),
(5, 'lqc75gaiuqd12g8gd7fobau326', 'Que caracteristicas tiene el galaxy 55?', 'El Smartphone Galaxy A55 tiene las siguientes características:\n\n- Pantalla AMOLED de 6.6 pulgadas\n- Memoria interna de 128GB\n- Cámara principal de 50MP\n- Batería de 5000mAh\n\nEs ideal para fotos y redes sociales.', NULL, '2026-05-18 15:34:24'),
(6, 'lqc75gaiuqd12g8gd7fobau326', 'Cual es el mejor celular que tienes?', 'Lo siento, solo puedo ayudarte con información sobre nuestro catálogo de productos.', NULL, '2026-05-18 15:34:53'),
(7, 'lqc75gaiuqd12g8gd7fobau326', 'Que celulares vendes?', 'Lo siento, solo puedo ayudarte con información sobre nuestro catálogo de productos.', NULL, '2026-05-18 15:35:07'),
(8, 'lqc75gaiuqd12g8gd7fobau326', 'dame el catalogo completo', '¡Claro! A continuación, te presento el catálogo completo de productos de \"localhost\":\n\n**Alimentos**\n\n1. **Café Molido Premium 500g**\n - Precio: $12.00\n - Stock: 200 unidades\n - Descripción: Café arábica molido, tostado medio, origen colombiano, 500 gramos.\n\n**Electrónica**\n\n1. **Auriculares Bluetooth X3**\n - Precio: $45.00\n - Stock: 50 unidades\n - Descripción: Sonido HD inalámbrico, 30h de batería, cancelación de ruido activa, plegables.\n2. **Smartphone Galaxy A55**\n - Precio: $350.00\n - Stock: 20 unidades\n - Descripción: Pantalla AMOLED 6.6\", 128GB, cámara 50MP, batería 5000mAh. Ideal para fotos y redes sociales.\n3. **Tablet Pro 10\"**\n - Precio: $200.00\n - Stock: 15 unidades\n - Descripción: Pantalla IPS 10 pulgadas, 64GB, WiFi, ideal para estudio y entretenimiento.\n\n**Hogar**\n\n1. **Juego de Sábanas Queen**\n - Precio: $35.00\n - Stock: 60 unidades\n - Descripción: Juego de sábanas 100% microfibra, tamaño Queen, 4 piezas, varios colores disponibles.\n2. **Licuadora 600W**\n - Precio: $55.00\n - Stock: 30 unidades\n - Descripción: Licuadora de 600W, vaso de vidrio 1.5L, 5 velocidades + pulso, cuchillas de acero inoxidable.\n\n**Ropa**\n\n1. **Camiseta Polo Classic**\n - Precio: $18.00\n - Stock: 100 unidades\n - Descripción: Polo 100% algodón peinado, disponible en tallas S, M, L, XL. Colores: blanco, negro, azul.\n2. **Zapatillas Runner Pro**\n - Precio: $65.00\n - Stock: 40 unidades\n - Descripción: Zapatillas deportivas ultralivianas, suela de', NULL, '2026-05-18 15:35:17'),
(9, 'lqc75gaiuqd12g8gd7fobau326', 'que celus vendes?', 'Lo siento, solo puedo ayudarte con información sobre nuestro catálogo de productos.', NULL, '2026-05-18 15:37:44'),
(10, 'lqc75gaiuqd12g8gd7fobau326', 'que celus vendes?', 'Lamento informarte que no manejamos celulares en este momento. Sin embargo, tenemos estos productos que podrían interesarte:\n\n* Auriculares Bluetooth X3 por 45.00, con sonido HD inalámbrico y 30h de batería.\n* Camiseta Polo Classic por 18.00, disponible en diferentes tallas y colores.\n\n¿Te gustaría saber más sobre cualquiera de estos productos?', NULL, '2026-05-18 15:44:05'),
(11, 'lqc75gaiuqd12g8gd7fobau326', 'vendes ropa?', '¡Claro que sí! Vendemos una variedad de prendas atractivas. Nuestra gama de ropa incluye una impresionante \"Camiseta Polo Classic\" que cuenta con detalles como peinado 100% algodón y está disponible en tallas S, M, L y XL. Puedes elegir entre colores como blanco, negro y azul. ¿Te gustaría saber más sobre este producto o el precio?', NULL, '2026-05-18 15:44:21'),
(12, 'lqc75gaiuqd12g8gd7fobau326', 'tienes celulares?', 'Lamento informarte que no manejamos celulares en este momento. Sin embargo, tenemos estos productos que podrían interesarte:\n\n* Auriculares Bluetooth X3, por solo 45.00€, con sonido HD inalámbrico y 30h de batería.\n* Camiseta Polo Classic, por 18.00€, disponible en tallas S, M, L, XL y varios colores.\n\n¿Te gustaría saber más sobre alguno de estos productos?', NULL, '2026-05-18 15:44:38'),
(13, 'lqc75gaiuqd12g8gd7fobau326', 'catalogo de celulares', 'Lamento informarte que no manejamos celulares en este momento. Sin embargo, tenemos estos productos que podrían interesarte:\n\n* Auriculares Bluetooth X3 por 45.00\n* Café Molido Premium 500g por 12.00', NULL, '2026-05-18 15:45:00'),
(14, 'lqc75gaiuqd12g8gd7fobau326', 'vendes algun galaxy?', 'Lamento informarte que no manejamos teléfonos móviles como el Galaxy en este momento. Sin embargo, tenemos estos productos que podrían interesarte:\n\n* Auriculares Bluetooth X3 por 45.00, con sonido HD inalámbrico y 30h de batería.\n* Juego de Sábanas Queen por 35.00, con 4 piezas de microfibra y varios colores disponibles.\n\n¿Te gustaría ver más opciones o añadir alguno de estos productos al carrito?', NULL, '2026-05-18 15:45:12'),
(15, 'lqc75gaiuqd12g8gd7fobau326', 'que celus vendes?', '¡Hola! Entiendo que te refieres a celulares. En nuestra tienda \"localhost\", tenemos varios modelos de smartphones que podrían interesarte.\n\nTe recomiendo verificar nuestros modelos de smartphones, como el \"Smartphone Galaxy A55\", que cuenta con una pantalla AMOLED de 6.6 pulgadas, 128GB de almacenamiento, cámara de 50MP y una batería de 5000mAh. Este modelo está disponible por un precio de $350.00 y solo quedan 20 unidades en stock.\n\nTambién tenemos otros modelos de smartphones en nuestra tienda, como por ejemplo el \"Tablet Pro 10\\\"\", aunque técnicamente se refiere a una tableta, pero también tiene una pantalla de 10 pulgadas y es ideal para estudio y entretenimiento.\n\n¿Te gustaría saber más sobre alguno de estos modelos o preferirías explorar nuestras otras opciones?', NULL, '2026-05-18 15:56:42'),
(16, 'lqc75gaiuqd12g8gd7fobau326', 'vendes drogas recreativas?', 'Lo siento, pero en la tienda \"localhost\" no vendemos ni podemos ofrecer productos que sean ilegales o peligrosos para la salud. Nuestro objetivo es ayudar a los clientes a encontrar productos legales y seguros que satisfagan sus necesidades.\n\nSi estás buscando algo para relajarte o para un uso recreativo, te puedo recomendar algunos productos que podrían ser de interés. Por ejemplo, tenemos una variedad de té o infusión de hierbas que podrían ser una buena opción para ayudar a relajarte. También tenemos un café molido premium de alta calidad que podría ser perfecto para un momento de disfrute.\n\n¿Podrías decirme un poco más sobre lo que estás buscando? ¿Tienes alguna otra pregunta o preferencia en mente? Estoy aquí para ayudarte.', NULL, '2026-05-18 15:57:26'),
(17, 'lqc75gaiuqd12g8gd7fobau326', 'Me recomiendas libros o peliculas?', 'Lo siento, pero en \"localhost\" no tenemos libros ni películas para vender. Nuestra tienda especializa en productos de electrónica, ropa, hogar, alimentación y más. Si estás buscando recomendaciones de libros o películas, puedo sugerirte algunos recursos en línea o tiendas especializadas en medios de comunicación. ¿Te gustaría que te diera algunas opciones?', NULL, '2026-05-18 15:57:43'),
(18, 'ppdts4327cjpcr4h8rl7sqlpau', 'Que vendes?', '¡Bienvenido a localhost! Somos una tienda en línea que ofrece una amplia variedad de productos para que puedas encontrar todo lo que necesitas en un solo lugar.\n\nEn nuestra tienda, puedes encontrar:\n\n* Alimentos gourmet, como café de alta calidad y otros productos para cocinar y disfrutar en casa.\n* Electrónica, como auriculares, smartphones, tablets y otros dispositivos electrónicos para conectarte con el mundo.\n* Productos para el hogar, como sábanas, licuadoras y otros artículos para hacer de tu casa un lugar cómodo y acogedor.\n* Ropa y accesorios, como camisetas, zapatillas y otros productos para que te sientas confiado y cómodo.\n\n¿Estás buscando algo en particular? ¿Tienes una pregunta sobre nuestros productos? ¡Estoy aquí para ayudarte!', NULL, '2026-05-25 13:52:36'),
(19, 'ppdts4327cjpcr4h8rl7sqlpau', 'Dame la lista de electronicos que vendes?', '¡Claro! En nuestra tienda \"localhost\", tenemos una variedad de productos electrónicos que te pueden interesar. Aquí te presento nuestra lista de electrónicos disponibles:\n\n- Auriculares Bluetooth X3: sonido HD inalámbrico, 30h de batería, cancelación de ruido activa, plegables. Precio: 45.00, Stock: 50 unidades.\n- Smartphone Galaxy A55: pantalla AMOLED 6.6\\\", 128GB, cámara 50MP, batería 5000mAh. Ideal para fotos y redes sociales. Precio: 350.00, Stock: 20 unidades.\n- Tablet Pro 10\\\": pantalla IPS 10 pulgadas, 64GB, WiFi, ideal para estudio y entretenimiento. Precio: 200.00, Stock: 15 unidades.\n\n¿Te gustaría saber más sobre alguno de estos productos o necesitas ayuda para elegir?', NULL, '2026-05-25 13:53:02'),
(20, 'ih7do9dgfrndggvv3nrpl1jcvk', 'Hola, me cuentas un chiste?', 'Me alegra verte en la tienda \"localhost\"! Por supuesto, te contaré un chiste. ¿Qué sabes de un café que fue al psicólogo? (risas) Pues que estaba un poco molido. (risas) ¿Te pareció un poco amargo? (risas) Bueno, ya sabes, aquí en la tienda, siempre tenemos café molido para ofrecerte. ¿Quieres saber más sobre nuestros productos de café?', NULL, '2026-05-25 14:14:39'),
(21, 'ih7do9dgfrndggvv3nrpl1jcvk', 'me recomiendas peliculas ganadoras del oscar de este año?', 'Me parece que te has perdido un poco en la conversación. Estamos hablando de la tienda \"localhost\" y sus productos. Sin embargo, si te gustan las películas, puedo recomendarte algunos de nuestros productos que podrían ser interesantes para ti.\n\nSi estás buscando algo para ver en casa, podrías considerar nuestro \"Tablet Pro 10\\\"\", que es perfecto para reproducir películas y series en alta calidad. También tenemos una variedad de productos electrónicos que podrían ser útiles para disfrutar de tus contenidos favoritos.\n\n¿Quieres saber más sobre nuestros productos electrónicos?', NULL, '2026-05-25 14:15:05'),
(22, 'ih7do9dgfrndggvv3nrpl1jcvk', 'tienes tablets?', 'Sí, tenemos tablets. Tenemos una variedad de opciones, pero te recomiendo echar un vistazo a nuestra tablet \"Tablet Pro 10\\\". Tiene una pantalla IPS de 10 pulgadas, 64GB de almacenamiento y WiFi. Es ideal para estudio y entretenimiento. ¿Te gustaría saber más sobre esta opción? ¿Tienes alguna pregunta específica sobre esta tablet?', NULL, '2026-05-25 15:16:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `telefono`, `email`, `direccion`, `creado_en`) VALUES
(1, 'Gunnar Ludwing', '67520128', 'ludwinggunnarpv24@gmail.com', NULL, '2026-05-25 14:23:50'),
(4, 'Gunnar Ludwing', '67422223', 'ludwinggunnarpv24@gmail.com', NULL, '2026-05-25 16:31:15'),
(5, 'Gunnar Ludwing', '67422223', 'ludwinggunnarpv24@gmail.com', NULL, '2026-05-25 16:38:03'),
(6, 'Gunnar Ludwing', '67422223', 'ludwinggunnarpv24@gmail.com', NULL, '2026-05-25 16:38:35'),
(7, 'Gunnar Ludwing', '67422223', 'ludwinggunnarpv24@gmail.com', NULL, '2026-05-25 16:41:42'),
(26, 'Gunnar Ludwing', '67422223', 'ludwinggunnarpv24@gmail.com', NULL, '2026-05-26 17:13:12'),
(27, 'Gunnar Ludwing', '67422223', 'ludwinggunnarpv24@gmail.com', NULL, '2026-05-26 17:14:22'),
(29, 'Gunnar Ludwing', '67422223', 'ludwinggunnarpv24@gmail.com', NULL, '2026-05-26 17:22:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`clave`, `valor`) VALUES
('contacto_email', 'soporte@tiendaia.com'),
('contacto_telefono', '61234567'),
('moneda_codigo', 'BOB'),
('moneda_simbolo', 'Bs'),
('nombre_dueño', 'Carlos Pérez'),
('nombre_tienda', 'ICAL'),
('tasa_cambio_usd', '9.96'),
('umbral_stock', '5');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedidos`
--

CREATE TABLE `detalle_pedidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL COMMENT 'Precio en el momento de la compra (snapshot)',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Calculado desde PHP: cantidad * precio_unitario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_pedidos`
--

INSERT INTO `detalle_pedidos` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 8, 1, 12.00, 12.00),
(2, 1, 2, 1, 45.00, 45.00),
(3, 1, 7, 1, 35.00, 35.00),
(4, 1, 3, 1, 200.00, 200.00),
(5, 4, 2, 1, 45.00, 45.00),
(6, 4, 8, 1, 12.00, 12.00),
(7, 4, 3, 1, 1000.00, 1000.00),
(8, 5, 9, 8, 10000.00, 80000.00),
(9, 6, 9, 4, 10000.00, 40000.00),
(10, 7, 9, 4, 69600.00, 278400.00),
(11, 26, 2, 1, 448.20, 448.20),
(12, 27, 9, 4, 14940.00, 59760.00),
(13, 29, 9, 1, 14940.00, 14940.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_proveedores`
--

CREATE TABLE `mensajes_proveedores` (
  `id` int(10) UNSIGNED NOT NULL,
  `proveedor_id` int(10) UNSIGNED NOT NULL,
  `mensaje` text NOT NULL,
  `estado` enum('pendiente','enviado') DEFAULT 'pendiente',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','confirmado','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `notas` text DEFAULT NULL COMMENT 'Instrucciones especiales del cliente',
  `notificado_telegram` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = ya se envió notificación',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `cliente_id`, `total`, `estado`, `notas`, `notificado_telegram`, `creado_en`, `actualizado_en`) VALUES
(1, 1, 292.00, 'entregado', NULL, 0, '2026-05-25 14:23:50', '2026-05-27 14:26:27'),
(4, 4, 1057.00, 'entregado', NULL, 0, '2026-05-25 16:31:15', '2026-05-27 14:26:24'),
(5, 5, 80000.00, 'entregado', NULL, 0, '2026-05-25 16:38:03', '2026-05-27 14:25:59'),
(6, 6, 40000.00, 'cancelado', NULL, 0, '2026-05-25 16:38:35', '2026-05-27 14:20:16'),
(7, 7, 278400.00, 'entregado', NULL, 0, '2026-05-25 16:41:42', '2026-05-27 14:25:57'),
(26, 26, 448.20, 'entregado', NULL, 0, '2026-05-26 17:13:12', '2026-05-27 14:25:53'),
(27, 27, 59760.00, 'cancelado', NULL, 0, '2026-05-26 17:14:22', '2026-05-27 14:20:11'),
(29, 29, 14940.00, 'cancelado', NULL, 0, '2026-05-26 17:22:57', '2026-05-27 14:12:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED NOT NULL,
  `proveedor_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL COMMENT 'Descripción detallada para el prompt de la IA',
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `imagen_url` varchar(500) DEFAULT NULL COMMENT 'Ruta relativa o URL de la imagen',
  `sku` varchar(80) DEFAULT NULL COMMENT 'Código único de producto (Stock Keeping Unit)',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `proveedor_id`, `nombre`, `descripcion`, `precio`, `stock`, `imagen_url`, `sku`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 1, NULL, 'Smartphone Galaxy A55', 'Pantalla AMOLED 6.6\", 128GB, cámara 50MP, batería 5000mAh. Ideal para fotos y redes sociales.', 350.00, 20, 'https://www.peru-smart.com/wp-content/uploads/2025/02/CELU1046AZUL-256GB.jpg', 'ELEC-001', 1, '2026-05-13 14:52:34', '2026-05-25 16:17:19'),
(2, 1, NULL, 'Auriculares Bluetooth X3', 'Sonido HD inalámbrico, 30h de batería, cancelación de ruido activa, plegables.', 45.00, 49, 'https://http2.mlstatic.com/D_NQ_NP_2X_702152-MLA106890006606_022026-F.webp', 'ELEC-002', 1, '2026-05-13 14:52:34', '2026-05-26 17:13:12'),
(3, 1, NULL, 'Tablet Pro 10\"', 'Pantalla IPS 10 pulgadas, 64GB, WiFi, ideal para estudio y entretenimiento.', 1000.00, 15, 'https://www.tiendaamiga.com.bo/media/catalog/product/cache/deb88dadd509903c96aaa309d3e790dc/f/j/fjjffk.png', 'ELEC-003', 1, '2026-05-13 14:52:34', '2026-05-25 16:27:36'),
(4, 2, NULL, 'Camiseta Polo Classic', 'Polo 100% algodón peinado, disponible en tallas S, M, L, XL. Colores: blanco, negro, azul.', 18.00, 100, 'https://style-old-money.com/cdn/shop/files/33_2_32_49_45_2048x2048.jpg?v=1757926988', 'ROPA-001', 1, '2026-05-13 14:52:34', '2026-05-25 16:16:47'),
(5, 2, NULL, 'Zapatillas Runner Pro', 'Zapatillas deportivas ultralivianas, suela de goma antideslizante, tallas 36-44.', 65.00, 40, 'https://images.puma.com/image/upload/f_auto,q_auto,w_600,b_rgb:FAFAFA/global/310780/24/fnd/MEX/fmt/png', 'ROPA-002', 1, '2026-05-13 14:52:34', '2026-05-25 16:20:20'),
(6, 3, NULL, 'Licuadora 600W', 'Licuadora de 600W, vaso de vidrio 1.5L, 5 velocidades + pulso, cuchillas de acero inoxidable.', 55.00, 30, 'https://www.tiendaamiga.com.bo/media/catalog/product/cache/deb88dadd509903c96aaa309d3e790dc/l/i/licuadora_britania_600_w_negro_jarra_de_acr_lico_1.5_l_33102282.png', 'HOGA-001', 1, '2026-05-13 14:52:34', '2026-05-25 16:18:35'),
(7, 3, NULL, 'Juego de Sábanas Queen', 'Juego de sábanas 100% microfibra, tamaño Queen, 4 piezas, varios colores disponibles.', 35.00, 60, 'https://i5.walmartimages.com/asr/fec8cf6a-d6e8-4289-9b81-8edef01ee219.214678dba74db98bac16f9dbe3dcaaa8.jpeg?odnHeight=612&odnWidth=612&odnBg=FFFFFF', 'HOGA-002', 1, '2026-05-13 14:52:34', '2026-05-25 16:18:00'),
(8, 4, NULL, 'Café Molido Premium 500g', 'Café arábica molido, tostado medio, origen colombiano, 500 gramos.', 12.00, 200, 'https://farmacorp.com/cdn/shop/files/7896005800829_2000x2000.jpg?v=1714443323', 'ALIM-001', 1, '2026-05-13 14:52:34', '2026-05-25 16:16:08'),
(9, 1, NULL, 'iPhone 17 Pro Max', 'El iPhone 17 Pro Max es el buque insignia de Apple, destacando por su enorme pantalla OLED de 6.9 pulgadas (120 Hz), su procesador A19 Pro con 12 GB de RAM, y su gran batería', 1500.00, 1, 'https://resource.megaeletronicos.com/uploads/Product/new/1/5/7/4/3/0/157430/1768417711_1768417711.webp', NULL, 1, '2026-05-25 16:37:34', '2026-05-27 18:32:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `contacto`, `telefono`, `email`, `direccion`, `creado_en`) VALUES
(1, 'Samuel Pecho', 'ICAL', '79755532', 'samytopecho@gmail.com', 'C. Manuela Saavedra', '2026-05-27 13:59:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_admin`
--

CREATE TABLE `usuarios_admin` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios_admin`
--

INSERT INTO `usuarios_admin` (`id`, `username`, `password_hash`, `nombre`, `creado_en`) VALUES
(1, 'admin', '$2y$10$FE7LH06JH/wz3GT0RSb3JujFjB0s5yl9A4FnOcqmEmiUKRdR9rWW.', 'Administrador', '2026-05-25 14:32:21');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alertas_stock`
--
ALTER TABLE `alertas_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indices de la tabla `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedido` (`pedido_id`),
  ADD KEY `idx_producto` (`producto_id`);

--
-- Indices de la tabla `mensajes_proveedores`
--
ALTER TABLE `mensajes_proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente` (`cliente_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_proveedor` (`proveedor_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios_admin`
--
ALTER TABLE `usuarios_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alertas_stock`
--
ALTER TABLE `alertas_stock`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `chat_logs`
--
ALTER TABLE `chat_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `mensajes_proveedores`
--
ALTER TABLE `mensajes_proveedores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios_admin`
--
ALTER TABLE `usuarios_admin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alertas_stock`
--
ALTER TABLE `alertas_stock`
  ADD CONSTRAINT `alertas_stock_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensajes_proveedores`
--
ALTER TABLE `mensajes_proveedores`
  ADD CONSTRAINT `mensajes_proveedores_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_producto_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
