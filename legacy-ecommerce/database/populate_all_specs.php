<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

// 1. Agregar Xiaomi Redmi Note 13 Pro+ a Electrónica si no existe
$stmtCheck = $pdo->prepare("SELECT id FROM productos WHERE nombre LIKE '%Redmi Note 13 Pro%'");
$stmtCheck->execute();
if (!$stmtCheck->fetch()) {
    $stmtIns = $pdo->prepare("
        INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, imagen_url, sku, activo)
        VALUES (1, 'Xiaomi Redmi Note 13 Pro+ 5G', 'Pantalla AMOLED Curva 1.5K 120Hz, cámara de 200MP con OIS, Dimensity 7200 Ultra, carga ultra rápida de 120W y protección IP68.', 410.00, 25, 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500&auto=format&fit=crop&q=60', 'ELEC-004', 1)
    ");
    $stmtIns->execute();
    echo "✔ Xiaomi Redmi Note 13 Pro+ agregado al catálogo de Electrónica.\n";
}

// 2. Especificaciones completas para todas las categorías
$specs = [
    'Xiaomi Redmi Note 13 Pro+ 5G' => [
        'tipo_dispositivo'    => 'Smartphone',
        'pantalla'            => 'AMOLED Curvo 6.67" 1.5K (1220x2712) 120Hz Dolby Vision (1.800 nits pico) Victus',
        'procesador'          => 'MediaTek Dimensity 7200-Ultra (4nm) Octa-Core hasta 2.8GHz con GPU Mali-G610',
        'ram'                 => '12 GB LPDDR5',
        'almacenamiento'      => '512 GB UFS 3.1 alta velocidad',
        'camara_principal'    => '200 MP Samsung HP3 f/1.65 OIS + 8 MP Gran Angular + 2 MP Macro (Video 4K@30fps)',
        'camara_frontal'      => '16 MP HDR',
        'bateria'             => '5.000 mAh con HyperCharge 120W (0 a 100% en 19 minutos)',
        'conectividad'        => '5G dual, Wi-Fi 6, Bluetooth 5.3, NFC, Infrarrojos',
        'resistencia'         => 'Certificación IP68 resistencia a inmersión en agua y polvo',
        'peso'                => '204 gramos',
        'antutu_score'        => '803.000 puntos',
        'kimovil_score'       => '9.2 / 10',
        'puntos_fuertes'      => ['Cámara astronómica de 200MP con zoom sin pérdida 4x', 'Carga ultrarrápida de 120W con cargador incluido', 'Certificación IP68 en gama media-alta'],
        'puntos_debiles'      => ['No admite tarjeta MicroSD externa'],
        'veredicto'           => 'Imbatible en carga rápida y resolución fotográfica de 200MP en su rango de precio. Excelente rival frente al Galaxy A55.'
    ],
    'Camiseta Polo Classic' => [
        'tipo_dispositivo'    => 'Ropa Casual',
        'material'            => '100% Algodón Peinado Premium 180 g/m²',
        'tallas'              => 'S, M, L, XL (Regular Fit)',
        'colores'             => 'Blanco Ártico, Negro Carbón, Azul Marino',
        'corte'               => 'Cuello camisero acanalado con tapeta de 3 botones reforzados',
        'temporada'           => 'Primavera / Verano / Todo el año',
        'cuidado'             => 'Lavable en máquina con agua fría, no usar blanqueador',
        'durabilidad'         => 'Costuras reforzadas en hombros y dobladillo antidesgaste',
        'score_categoria'     => '9.4 / 10',
        'puntos_fuertes'      => ['Algodón peinado hipoalergénico suave al tacto', 'Color firme resistente a múltiples lavadas', 'Corte versátil tanto casual como semi-formal'],
        'puntos_debiles'      => ['Requiere planchado a temperatura media si no se cuelga húmedo'],
        'veredicto'           => 'Un clásico infaltable en el armario masculino y femenino por su alta durabilidad y comodidad térmica.'
    ],
    'Zapatillas Runner Pro' => [
        'tipo_dispositivo'    => 'Calzado Deportivo',
        'material'            => 'Malla técnica Jacquard transpirable con termosellados TPU de sujeción',
        'tallas'              => '36 al 44 unisex',
        'suela'               => 'Entresuela de espuma EVA amortiguadora + piso de caucho antideslizante',
        'drop'                => 'Drop de 8 mm con soporte reforzado de talón',
        'peso'                => '235 gramos (Talla 41)',
        'temporada'           => 'Uso diario deportivo y urbano',
        'cuidado'             => 'Limpieza con cepillo de cerdas suaves y jabón neutro, secado a la sombra',
        'score_categoria'     => '9.1 / 10',
        'puntos_fuertes'      => ['Amortiguación suave que reduce el impacto en articulaciones', 'Plantilla acolchada extraíble antibacterial', 'Gran ligereza para correr o caminar todo el día'],
        'puntos_debiles'      => ['No diseñadas para trail running o senderismo de montaña rocosa'],
        'veredicto'           => 'Calzado deportivo ergonómico ideal para quienes buscan comodidad extrema en rutinas de gimnasio, running y caminatas.'
    ],
    'Licuadora 600W' => [
        'tipo_dispositivo'    => 'Electrodoméstico',
        'potencia'            => '600 Watts motor de cobre con ventilación forzada',
        'capacidad'           => 'Vaso de vidrio refractario Borosilicato de 1.5 Litros',
        'cuchillas'           => '4 aspas de acero inoxidable SUS304 tritura-hielo',
        'velocidades'         => '5 velocidades continuas + función de pulso / autolimpieza',
        'seguridad'           => 'Base antideslizante con bloqueo de seguridad para encendido',
        'score_categoria'     => '8.9 / 10',
        'puntos_fuertes'      => ['Vaso de vidrio resistente a choques térmicos frío/calor', 'Capacidad para picar hielo sin forzar el motor', 'Fácil desmontaje para lavado rápido'],
        'puntos_debiles'      => ['El vaso de vidrio añade peso en comparación a modelos de plástico'],
        'veredicto'           => 'Electrodoméstico duradero y confiable para licuados, jugos, salsas y preparaciones exigentes en el hogar.'
    ],
    'Juego de Sábanas Queen' => [
        'tipo_dispositivo'    => 'Blancos y Ropa de Cama',
        'composicion'         => '100% Microfibra cepillada ultrasuave de doble hilado (1.800 hilos)',
        'medidas'             => 'Tamaño Queen (1 sábana plana 228x259 cm, 1 ajustable 152x203+35 cm, 2 fundas 50x76 cm)',
        'ajuste'              => 'Elástico perimetral 360° para colchones de hasta 35 cm de alto',
        'colores'             => 'Gris Oxford, Blanco Perla, Azul Noche',
        'cuidado'             => 'Lavable en lavadora ciclo suave, apto para secadora a baja temperatura, antiarrugas',
        'score_categoria'     => '9.0 / 10',
        'puntos_fuertes'      => ['Tacto sedoso tipo durazno que no se desgasta con las lavadas', 'Resistente a decoloración y arrugas sin necesidad de plancha', 'Bolsillo profundo que evita que se salga del colchón'],
        'puntos_debiles'      => ['Se recomienda evitar lavado con prendas con velcros o cierres metálicos'],
        'veredicto'           => 'Excelente inversión para transformar el confort de la habitación con suavidad premium y máxima facilidad de lavado.'
    ]
];

$stmtUpd = $pdo->prepare("UPDATE productos SET especificaciones = :spec WHERE nombre = :nom");
foreach ($specs as $nom => $sp) {
    $stmtUpd->execute([
        ':spec' => json_encode($sp, JSON_UNESCAPED_UNICODE),
        ':nom'  => $nom
    ]);
    echo "✔ Especificaciones actualizadas para: $nom\n";
}

echo "🎉 Migración de especificaciones finalizada con éxito.\n";
