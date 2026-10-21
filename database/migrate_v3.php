<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "🚀 Ejecutando Migración v3: Persistencia Chat Admin + Especificaciones Técnicas (Kimovil)...\n";

try {
    $pdo = Database::getConnection();

    // 1. Agregar columna especificaciones a productos
    $cols = $pdo->query("SHOW COLUMNS FROM productos LIKE 'especificaciones'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE productos ADD COLUMN especificaciones TEXT NULL AFTER descripcion");
        echo "✔ Columna 'especificaciones' agregada a productos.\n";
    } else {
        echo "ℹ Columna 'especificaciones' ya existe.\n";
    }

    // 2. Crear tabla de hilos de conversación del admin
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_admin_hilos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            admin_id INT DEFAULT 1,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✔ Tabla 'chat_admin_hilos' creada o verificada.\n";

    // 3. Crear tabla de mensajes de conversación del admin
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_admin_mensajes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hilo_id INT NOT NULL,
            role ENUM('user', 'assistant') NOT NULL,
            content TEXT NOT NULL,
            tipo VARCHAR(50) DEFAULT 'texto',
            datos_json TEXT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hilo_id) REFERENCES chat_admin_hilos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✔ Tabla 'chat_admin_mensajes' creada o verificada.\n";

    // 4. Poblar especificaciones técnicas para celulares y productos clave (Estilo Kimovil)
    $specsMap = [
        'Smartphone Galaxy A55' => [
            'tipo_dispositivo'    => 'Smartphone',
            'pantalla'            => 'Super AMOLED 6.6" FHD+ (1080x2340) 120Hz HDR10+ Gorilla Glass Victus+',
            'procesador'          => 'Samsung Exynos 1480 Octa-Core 2.75GHz (4nm) con GPU AMD Xclipse 530',
            'ram'                 => '8 GB LPDDR5',
            'almacenamiento'      => '128 GB / 256 GB UFS 3.1 (Expandible MicroSD)',
            'camara_principal'    => '50 MP f/1.8 OIS + 12 MP Gran Angular + 5 MP Macro',
            'camara_frontal'      => '32 MP 4K@30fps',
            'bateria'             => '5.000 mAh con Carga Rápida 25W',
            'conectividad'        => '5G, Wi-Fi 6, Bluetooth 5.3, NFC, GPS dual',
            'resistencia'         => 'Certificación IP67 (1m agua por 30 min)',
            'peso'                => '213 gramos (Marco de metal)',
            'antutu_score'        => '713.400 puntos',
            'kimovil_score'       => '8.9 / 10',
            'puntos_fuertes'      => ['Pantalla AMOLED brillante 120Hz', 'Cámara principal con estabilización óptica OIS', 'Construcción premium en metal y cristal'],
            'puntos_debiles'      => ['No incluye cargador en caja', 'Carga máxima limitada a 25W'],
            'veredicto'           => 'El rey de la gama media premium. Excelente equilibrio entre pantalla de cine, cámara nítida y 4 años de soporte de software garantizados.'
        ],
        'iPhone 17 Pro Max' => [
            'tipo_dispositivo'    => 'Smartphone',
            'pantalla'            => 'Super Retina XDR OLED 6.9" ProMotion 120Hz Always-On (3.000 nits pico)',
            'procesador'          => 'Apple A19 Pro Bionic (3nm de 2ª gen) 6 núcleos CPU + 6 núcleos GPU Neural Engine',
            'ram'                 => '12 GB Unified RAM',
            'almacenamiento'      => '256 GB / 512 GB / 1 TB NVMe ultra rápido',
            'camara_principal'    => '48 MP Fusion Sensor-Shift OIS + 48 MP Ultra Wide + 48 MP Telefoto Periscopio 5x óptico',
            'camara_frontal'      => '24 MP TrueDepth HDR con FaceID',
            'bateria'             => '4.850 mAh con Carga rápida 35W + MagSafe 25W',
            'conectividad'        => '5G Gigabit, Wi-Fi 7, Bluetooth 5.4, Chip U2 Banda Ultraancha, SOS Satelital',
            'resistencia'         => 'IP68 Chasis de Titanio Grado 5 aeroespacial',
            'peso'                => '221 gramos',
            'antutu_score'        => '1.980.000 puntos',
            'kimovil_score'       => '9.8 / 10',
            'puntos_fuertes'      => ['Rendimiento brutal con el chip A19 Pro', 'Grabación de video en formato ProRes Log 4K 120fps', 'Batería con la mayor autonomía del mercado'],
            'puntos_debiles'      => ['Inversión premium de alto valor', 'Ecosistema de accesorios específico'],
            'veredicto'           => 'La cima de la tecnología móvil actual. Ideal para creadores de contenido profesional y usuarios que buscan la máxima potencia sin concesiones.'
        ],
        'Tablet Pro 10"' => [
            'tipo_dispositivo'    => 'Tablet',
            'pantalla'            => 'IPS LCD 10.4" 2K (2000x1200) 90Hz con protección visual TUV',
            'procesador'          => 'Qualcomm Snapdragon 680 Octa-Core 2.4GHz',
            'ram'                 => '6 GB RAM LPDDR4X',
            'almacenamiento'      => '128 GB UFS expandible hasta 1 TB',
            'camara_principal'    => '13 MP con enfoque automático y Flash',
            'camara_frontal'      => '8 MP Gran Angular con encuadre automático',
            'bateria'             => '7.500 mAh con Carga Rápida 18W',
            'conectividad'        => 'Wi-Fi Dual Band 2.4/5GHz, Bluetooth 5.1, USB-C 2.0',
            'resistencia'         => 'Cuerpo unibody de aleación de aluminio',
            'peso'                => '465 gramos',
            'antutu_score'        => '315.000 puntos',
            'kimovil_score'       => '8.3 / 10',
            'puntos_fuertes'      => ['Gran pantalla 2K para estudio y series', 'Batería de larga duración (más de 12 horas)', '4 altavoces estéreo con sonido envolvente'],
            'puntos_debiles'      => ['No apta para juegos 3D competitivos pesados', 'Cámara adecuada solo para videollamadas y documentos'],
            'veredicto'           => 'La compañera perfecta para estudiantes, trabajo de oficina y entretenimiento multimedia familiar con gran relación valor-precio.'
        ],
        'Auriculares Bluetooth X3' => [
            'tipo_dispositivo'    => 'Audio TWS',
            'pantalla'            => 'Estuche con indicador digital LED de batería restante',
            'procesador'          => 'Chip DSP de procesamiento acústico Hi-Fi con baja latencia (45ms)',
            'ram'                 => 'N/A',
            'almacenamiento'      => 'N/A',
            'camara_principal'    => 'N/A',
            'camara_frontal'      => 'N/A',
            'bateria'             => 'Hasta 36 horas totales (6h auriculares + 30h estuche de 400 mAh)',
            'conectividad'        => 'Bluetooth 5.3 alcance de 15 metros sin cortes',
            'resistencia'         => 'IPX5 resistente a lluvia y sudor intenso',
            'peso'                => '4.2 gramos por auricular',
            'antutu_score'        => 'N/A',
            'kimovil_score'       => '8.8 / 10',
            'puntos_fuertes'      => ['Cancelación de ruido ambiental ENC en llamadas', 'Bajos reforzados con drivers de 13mm', 'Comodidad ergonómica para horas de uso'],
            'puntos_debiles'      => ['No incluye cancelación de ruido activa ANC híbrida'],
            'veredicto'           => 'Sonido potente y conexión estable a un precio súper accesible. Ideales para el gimnasio, viajes diarios y llamadas claras.'
        ]
    ];

    $stmtUpd = $pdo->prepare("UPDATE productos SET especificaciones = :spec WHERE nombre = :nom");
    foreach ($specsMap as $nombre => $specs) {
        $stmtUpd->execute([
            ':spec' => json_encode($specs, JSON_UNESCAPED_UNICODE),
            ':nom'  => $nombre
        ]);
        echo "✔ Especificaciones asignadas para: $nombre\n";
    }

    echo "\n🎉 Migración v3 completada exitosamente.\n";

} catch (Throwable $e) {
    echo "❌ Error en migración: " . $e->getMessage() . "\n";
}
