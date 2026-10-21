<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "🚀 Iniciando Migración v4: Segmentación Profunda de Productos y Categorías...\n";

$pdo = Database::getConnection();

// Desactivar temporalmente revisión de claves foráneas para reorganizar categorías
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// 1. Recrear / Actualizar Categorías Específicas
$categorias = [
    1 => ['nombre' => 'Celulares & Smartphones',    'slug' => 'celulares-smartphones', 'descripcion' => 'Smartphones Android y iPhone con pantallas OLED y cámaras de alta resolución'],
    2 => ['nombre' => 'Tablets',                   'slug' => 'tablets',               'descripcion' => 'Tablets para productividad, estudio, diseño digital y multimedia'],
    3 => ['nombre' => 'Audio & Auriculares',        'slug' => 'audio-auriculares',     'descripcion' => 'Auriculares TWS inalámbricos, cancelación de ruido y sonido Hi-Res'],
    4 => ['nombre' => 'Zapatillas & Calzado',       'slug' => 'zapatillas-calzado',    'descripcion' => 'Calzado deportivo, running urbano y zapatillas de confort'],
    5 => ['nombre' => 'Poleras & Camisetas',        'slug' => 'poleras-camisetas',     'descripcion' => 'Poleras polo, camisetas de algodón peinado y prendas casuales'],
    6 => ['nombre' => 'Electrodomésticos de Cocina','slug' => 'electrodomesticos',     'descripcion' => 'Licuadoras, freidoras de aire y cafeteras para el hogar'],
    7 => ['nombre' => 'Ropa de Cama & Hogar',       'slug' => 'ropa-de-cama',          'descripcion' => 'Sábanas de microfibra, edredones y textiles para el descanso'],
    8 => ['nombre' => 'Café & Alimentos Gourmet',   'slug' => 'cafe-alimentos',        'descripcion' => 'Café de altura, granos selectos y productos alimenticios premium']
];

$stmtCat = $pdo->prepare("
    INSERT INTO categorias (id, nombre, slug, descripcion) 
    VALUES (:id, :nom, :slug, :des) 
    ON DUPLICATE KEY UPDATE nombre = :nom2, slug = :slug2, descripcion = :des2
");

foreach ($categorias as $id => $cat) {
    $stmtCat->execute([
        ':id'    => $id,
        ':nom'   => $cat['nombre'],
        ':slug'  => $cat['slug'],
        ':des'   => $cat['descripcion'],
        ':nom2'  => $cat['nombre'],
        ':slug2' => $cat['slug'],
        ':des2'  => $cat['descripcion']
    ]);
}
echo "✔ 8 Categorías segmentadas registradas con éxito.\n";

// 2. Reasignar productos existentes a sus categorías precisas
$reasignaciones = [
    'Smartphone Galaxy A55'        => 1,
    'iPhone 17 Pro Max'            => 1,
    'Xiaomi Redmi Note 13 Pro+ 5G' => 1,
    'Tablet Pro 10"'               => 2,
    'Auriculares Bluetooth X3'     => 3,
    'Zapatillas Runner Pro'        => 4,
    'Camiseta Polo Classic'        => 5,
    'Licuadora 600W'               => 6,
    'Juego de Sábanas Queen'       => 7,
    'Café Molido Premium 500g'     => 8,
];

$stmtReasig = $pdo->prepare("UPDATE productos SET categoria_id = :cid WHERE nombre = :nom");
foreach ($reasignaciones as $nom => $cid) {
    $stmtReasig->execute([':cid' => $cid, ':nom' => $nom]);
}
echo "✔ Productos existentes reasignados a sus categorías específicas.\n";

// 3. Catálogo enriquecido: nuevos productos por categoría para garantizar comparativas múltiples
$nuevosProductos = [
    // --- CELULARES (Cat 1) ---
    [
        'categoria_id' => 1,
        'nombre' => 'Motorola Edge 50 Pro',
        'descripcion' => 'Pantalla pOLED Curva 6.7" 144Hz 1.5K, Snapdragon 7 Gen 3, cámara triple 50MP con teleobjetivo 3x óptico y carga TurboPower 125W con certificación IP68.',
        'precio' => 450.00,
        'stock' => 18,
        'imagen_url' => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500&auto=format&fit=crop&q=60',
        'sku' => 'ELEC-CEL-004',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Smartphone',
            'pantalla'            => 'pOLED 6.7" Super HD (1220p) 144Hz HDR10+ (2.000 nits pico) con Pantone Validated',
            'procesador'          => 'Qualcomm Snapdragon 7 Gen 3 (4nm) Octa-Core hasta 2.63GHz con GPU Adreno 720',
            'ram'                 => '12 GB LPDDR4X',
            'almacenamiento'      => '512 GB UFS 2.2',
            'camara_principal'    => '50 MP f/1.4 OIS + 13 MP Ultra Wide/Macro + 10 MP Teleobjetivo 3x OIS',
            'camara_frontal'      => '50 MP f/1.9 con Autofocus y video 4K',
            'bateria'             => '4.500 mAh con TurboPower 125W por cable y 50W inalámbrica',
            'conectividad'        => '5G dual, Wi-Fi 6E, Bluetooth 5.4, NFC, Ready For',
            'resistencia'         => 'Certificación IP68 sumergible con acabado en cuero vegano',
            'peso'                => '186 gramos ultraligero',
            'antutu_score'        => '840.000 puntos',
            'kimovil_score'       => '9.3 / 10',
            'puntos_fuertes'      => ['Teleobjetivo óptico 3x real poco común en este rango', 'Carga ultra veloz de 125W con cargador en caja', 'Pantalla calibrada por Pantone a 144Hz'],
            'puntos_debiles'      => ['Batería de 4500 mAh algo más contenida'],
            'veredicto'           => 'Diseño exquisito y versatilidad fotográfica excelente con teleobjetivo 3x real. Una alternativa premium de gran equilibrio.'
        ]
    ],

    // --- TABLETS (Cat 2) ---
    [
        'categoria_id' => 2,
        'nombre' => 'iPad Air 11" Chip M2',
        'descripcion' => 'Pantalla Liquid Retina 11" True Tone, chip Apple M2 con arquitectura de 8 núcleos, compatible con Apple Pencil Pro y Magic Keyboard.',
        'precio' => 600.00,
        'stock' => 12,
        'imagen_url' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=500&auto=format&fit=crop&q=60',
        'sku' => 'ELEC-TAB-002',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Tablet',
            'pantalla'            => 'Liquid Retina 11" LED IPS (2360x1640) 500 nits, gama P3, revestimiento antirreflejos',
            'procesador'          => 'Apple M2 (CPU de 8 núcleos + GPU de 10 núcleos + Neural Engine de 16 núcleos)',
            'ram'                 => '8 GB Unified Memory',
            'almacenamiento'      => '128 GB / 256 GB NVMe ultra rápido',
            'camara_principal'    => '12 MP Gran Angular f/1.8 con Smart HDR 4 y video 4K@60fps',
            'camara_frontal'      => '12 MP Ultra Gran Angular en borde horizontal con Encuadre Centrado',
            'bateria'             => 'Hasta 10 horas de navegación web y video por Wi-Fi (28.93 Wh)',
            'conectividad'        => 'Wi-Fi 6E (802.11ax), Bluetooth 5.3, USB-C 3.1 Gen 2 (10 Gb/s)',
            'resistencia'         => 'Chasis unibody de aluminio 100% reciclado',
            'peso'                => '462 gramos',
            'antutu_score'        => '1.580.000 puntos',
            'kimovil_score'       => '9.6 / 10',
            'puntos_fuertes'      => ['Potencia descomunal para edición de video 4K y diseño', 'Soporte para Apple Pencil Pro con sensor de presión', 'Pantalla nítida con calibración cromática profesional'],
            'puntos_debiles'      => ['Accesorios oficiales (lápiz y teclado) se venden por separado'],
            'veredicto'           => 'La mejor tablet para estudiantes universitarios, diseñadores gráficos y profesionales creativos que necesitan potencia de portátil en formato tablet.'
        ]
    ],
    [
        'categoria_id' => 2,
        'nombre' => 'Samsung Galaxy Tab S9 FE',
        'descripcion' => 'Pantalla 10.9" WQXGA 90Hz, procesador Exynos 1380, incluye S-Pen en la caja con resistencia IP68 al agua y polvo.',
        'precio' => 450.00,
        'stock' => 15,
        'imagen_url' => 'https://images.unsplash.com/photo-1585790050230-5dd28404ccb9?w=500&auto=format&fit=crop&q=60',
        'sku' => 'ELEC-TAB-003',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Tablet',
            'pantalla'            => 'TFT LCD 10.9" WQXGA (2304x1440) 90Hz Vision Booster exterior',
            'procesador'          => 'Samsung Exynos 1380 Octa-Core hasta 2.4GHz (5nm) con GPU Mali-G68',
            'ram'                 => '6 GB / 8 GB RAM',
            'almacenamiento'      => '128 GB expandible con MicroSD hasta 1 TB',
            'camara_principal'    => '8 MP con autoenfoque y video 4K@30fps',
            'camara_frontal'      => '12 MP Ultra Gran Angular para videollamadas claras',
            'bateria'             => '8.000 mAh con Carga Rápida de 45W',
            'conectividad'        => 'Wi-Fi 6, Bluetooth 5.3, USB-C 2.0, Samsung DeX integrado',
            'resistencia'         => 'Certificación IP68 tanto en la tablet como en el lápiz S-Pen',
            'peso'                => '523 gramos en marco metálico',
            'antutu_score'        => '580.000 puntos',
            'kimovil_score'       => '8.9 / 10',
            'puntos_fuertes'      => ['Lápiz S-Pen de alta precisión incluido en caja sin costo extra', 'Resistencia IP68 al agua y polvo única en su segmento', 'Gran batería de 8000 mAh con modo Samsung DeX'],
            'puntos_debiles'      => ['Panel TFT en lugar de AMOLED'],
            'veredicto'           => 'Fantástica tablet para tomar apuntes a mano, ilustrar y ver series con la tranquilidad de que el lápiz viene incluido y resiste accidentes con agua.'
        ]
    ],

    // --- AUDIO & AURICULARES (Cat 3) ---
    [
        'categoria_id' => 3,
        'nombre' => 'Sony WH-1000XM5 Hi-Res',
        'descripcion' => 'Audífonos de diadema con la mejor cancelación activa de ruido del mercado (ANC), 8 micrófonos, procesador V1, audio LDAC y 30 horas de autonomía.',
        'precio' => 350.00,
        'stock' => 14,
        'imagen_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop&q=60',
        'sku' => 'ELEC-AUD-002',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Audífonos Over-Ear',
            'pantalla'            => 'Controles táctiles capacitivos en auricular derecho',
            'procesador'          => 'Procesador Integrado V1 + Procesador HD QN1 con 8 micrófonos dedicados',
            'ram'                 => 'N/A',
            'almacenamiento'      => 'N/A',
            'camara_principal'    => 'N/A',
            'camara_frontal'      => 'N/A',
            'bateria'             => 'Hasta 30 horas con ANC activo (40 horas sin ANC) y carga rápida (3 min = 3 horas)',
            'conectividad'        => 'Bluetooth 5.2 con códecs LDAC Hi-Res, AAC, SBC y conexión Multipunto dual',
            'resistencia'         => 'Cuerpo ultraligero de tacto suave con almohadillas de cuero sintético acolchado',
            'peso'                => '250 gramos ergonómicos',
            'antutu_score'        => 'N/A',
            'kimovil_score'       => '9.7 / 10',
            'puntos_fuertes'      => ['Cancelación de ruido líder absoluta en aviones y oficinas', 'Audio de alta fidelidad con escalado DSEE Extreme por IA', 'Micrófonos con reducción de viento de claridad cristalina'],
            'puntos_debiles'      => ['Estructura no plegable (ocupa más espacio en estuche)'],
            'veredicto'           => 'La cúspide del aislamiento sonoro y confort para viajes largos, trabajo concentrado y melómanos exigentes.'
        ]
    ],
    [
        'categoria_id' => 3,
        'nombre' => 'Apple AirPods Pro 2da Gen (USB-C)',
        'descripcion' => 'Cancelación activa de ruido 2x superior, audio espacial personalizado, modo transparencia adaptativa y estuche MagSafe USB-C con altavoz de rastreo.',
        'precio' => 240.00,
        'stock' => 20,
        'imagen_url' => 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?w=500&auto=format&fit=crop&q=60',
        'sku' => 'ELEC-AUD-003',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Auriculares In-Ear TWS',
            'pantalla'            => 'Estuche con indicador LED y altavoz para Find My',
            'procesador'          => 'Chip Apple H2 en auriculares + Chip Apple U1 en estuche de carga',
            'ram'                 => 'N/A',
            'almacenamiento'      => 'N/A',
            'camara_principal'    => 'N/A',
            'camara_frontal'      => 'N/A',
            'bateria'             => 'Hasta 6 horas con ANC (30 horas totales con estuche de carga USB-C)',
            'conectividad'        => 'Bluetooth 5.3 con cambio automático entre dispositivos Apple',
            'resistencia'         => 'IP54 resistente al sudor, agua y polvo en auriculares y estuche',
            'peso'                => '5.3 gramos por auricular (Estuche 50.8 g)',
            'antutu_score'        => 'N/A',
            'kimovil_score'       => '9.5 / 10',
            'puntos_fuertes'      => ['Modo Transparencia Adaptativa insuperable en naturalidad', 'Audio espacial con seguimiento dinámico de cabeza', 'Control táctil deslizante de volumen en el vástago'],
            'puntos_debiles'      => ['Aprovechamiento al 100% únicamente con dispositivos Apple'],
            'veredicto'           => 'Los mejores auriculares TWS para el ecosistema iPhone/Mac por comodidad, cancelación y calidad de llamadas.'
        ]
    ],

    // --- ZAPATILLAS & CALZADO (Cat 4) ---
    [
        'categoria_id' => 4,
        'nombre' => 'Nike Air Zoom Pegasus 40',
        'descripcion' => 'Zapatillas de running con espuma Nike React de alta amortiguación, dos unidades Zoom Air en talón y antepié, y malla técnica transpirable.',
        'precio' => 130.00,
        'stock' => 25,
        'imagen_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500&auto=format&fit=crop&q=60',
        'sku' => 'CALZ-ZAP-002',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Calzado Deportivo / Running',
            'material'            => 'Malla de ingeniería técnica de una sola capa optimizada para flujo de aire',
            'tallas'              => '37, 38, 39, 40, 41, 42, 43, 44 unisex',
            'suela'               => 'Goma con patrón tipo gofre de tracción multidireccional',
            'drop'                => '10 mm con doble unidad Zoom Air (delantera y trasera)',
            'peso'                => '288 gramos (Talla 42)',
            'temporada'           => 'Todo el año para entrenamiento y pavimento',
            'cuidado'             => 'Limpiar en seco o con paño húmedo y jabón suave. No lavar en máquina.',
            'score_categoria'     => '9.5 / 10',
            'puntos_fuertes'      => ['Respuesta reactiva con rebote suave gracias a la espuma React', 'Ajuste seguro en el mediopié con banda de sujeción rediseñada', 'Gran durabilidad para acumular más de 800 km'],
            'puntos_debiles'      => ['No recomendadas para carreras de velocidad extrema en pista'],
            'veredicto'           => 'El estándar de oro en zapatillas de entrenamiento diario para corredores de cualquier nivel.'
        ]
    ],
    [
        'categoria_id' => 4,
        'nombre' => 'Adidas Ultraboost Light',
        'descripcion' => 'Zapatillas ultraligeras con cápsulas Light Boost 30% más ligeras, tejido Primeknit+ adaptable al pie y suela Continental de agarre superior.',
        'precio' => 150.00,
        'stock' => 20,
        'imagen_url' => 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=500&auto=format&fit=crop&q=60',
        'sku' => 'CALZ-ZAP-003',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Calzado Deportivo / Running & Casual',
            'material'            => 'Tejido Primeknit+ con 50% de hilo reciclado Parley Ocean Plastic',
            'tallas'              => '38, 39, 40, 41, 42, 43, 44',
            'suela'               => 'Caucho Continental Better Rubber de máximo agarre en suelo seco y mojado',
            'drop'                => '10 mm con sistema Linear Energy Push (LEP) para mayor rigidez torsional',
            'peso'                => '293 gramos',
            'temporada'           => 'Todo el año',
            'cuidado'             => 'Limpieza manual suave, secado al aire libre bajo sombra',
            'score_categoria'     => '9.4 / 10',
            'puntos_fuertes'      => ['Comodidad legendaria que se siente como caminar en nubes', 'Tracción insuperable en pavimento mojado con suela Continental', 'Diseño moderno que luce espectacular tanto corriendo como con ropa casual'],
            'puntos_debiles'      => ['Precio de gama alta en calzado deportivo'],
            'veredicto'           => 'Comodidad inigualable para quienes pasan muchas horas de pie o buscan un rodaje suave y protegido.'
        ]
    ],

    // --- POLERAS & CAMISETAS (Cat 5) ---
    [
        'categoria_id' => 5,
        'nombre' => 'Polera Tech Dry-Fit Training',
        'descripcion' => 'Camiseta deportiva de secado rápido con tecnología antitranspirante, costuras planas anti-rozaduras y protección UV UPF 40+.',
        'precio' => 22.00,
        'stock' => 60,
        'imagen_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=60',
        'sku' => 'ROPA-POL-002',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Polera Deportiva',
            'material'            => '100% Poliéster técnico micro-perforado transpirable (140 g/m²)',
            'tallas'              => 'S, M, L, XL, XXL',
            'colores'             => 'Gris Jaspeado, Negro Mate, Azul Eléctrico',
            'corte'               => 'Athletic Fit ergonómico que acompaña los movimientos sin apretar',
            'temporada'           => 'Primavera / Verano / Entrenamiento indoor',
            'cuidado'             => 'Lavable en lavadora ciclo rápido, secado ultra rápido en 20 minutos',
            'durabilidad'         => 'Tratamiento antibacteriano que neutraliza olores de sudoración',
            'score_categoria'     => '9.2 / 10',
            'puntos_fuertes'      => ['Secado casi instantáneo durante el entrenamiento', 'Ligereza absoluta que evita el efecto de camiseta empapada', 'Protección solar para actividades al aire libre'],
            'puntos_debiles'      => ['No recomendada para planchado a altas temperaturas'],
            'veredicto'           => 'La prenda ideal para gimnasio, fútbol, running o climas cálidos por su ventilación superior.'
        ]
    ],
    [
        'categoria_id' => 5,
        'nombre' => 'Polera Oversized Urban Streetwear',
        'descripcion' => 'Polera corte ancho Oversized de algodón pesado 240g, cuello cerrado reforzado y hombros caídos de tendencia urbana.',
        'precio' => 26.00,
        'stock' => 45,
        'imagen_url' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?w=500&auto=format&fit=crop&q=60',
        'sku' => 'ROPA-POL-003',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Polera Casual Urbana',
            'material'            => '100% Algodón Cardado Heavyweight 240 g/m² de alto gramaje',
            'tallas'              => 'S, M, L, XL (Corte holgado Boxy)',
            'colores'             => 'Negro Vintage, Beige Arena, Verde Oliva',
            'corte'               => 'Oversized Boxy Fit con hombros caídos y mangas amplias',
            'temporada'           => 'Otoño / Invierno / Primavera',
            'cuidado'             => 'Lavar al revés con agua fría para mantener la caída y el color mate',
            'durabilidad'         => 'Cuello grueso acanalado de 2.5 cm que no se deforma con el uso',
            'score_categoria'     => '9.3 / 10',
            'puntos_fuertes'      => ['Caída pesada estructurada muy favorecedora y moderna', 'Tejido grueso premium que no trasluce en ningún color', 'Estilo streetwear minimalista muy combinable'],
            'puntos_debiles'      => ['Si prefieres ropa ajustada, requiere pedir una talla menos'],
            'veredicto'           => 'Tendencia y calidad textil pura para outfits urbanos relajados con presencia y personalidad.'
        ]
    ],

    // --- ELECTRODOMÉSTICOS (Cat 6) ---
    [
        'categoria_id' => 6,
        'nombre' => 'Freidora de Aire Digital 5.5L',
        'descripcion' => 'Air Fryer de 1700W con tecnología de circulación de aire 360°, panel táctil LED con 8 programas preestablecidos y canasta antiadherente libre de BPA.',
        'precio' => 85.00,
        'stock' => 22,
        'imagen_url' => 'https://images.unsplash.com/photo-1584269600519-112d071b35e6?w=500&auto=format&fit=crop&q=60',
        'sku' => 'HOGA-ELEC-002',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Electrodoméstico de Cocina',
            'potencia'            => '1.700 Watts con resistencia blindada de calentamiento rápido',
            'capacidad'           => 'Canasta cuadrada de 5.5 Litros (ideal para 4 a 6 porciones)',
            'temperatura'         => 'Control digital de 80°C a 200°C con temporizador de 60 minutos',
            'funciones'           => '8 preajustes: Papas, Pollo, Carne, Pescado, Camarones, Repostería, Descongelar y Mantener caliente',
            'seguridad'           => 'Apagado automático al retirar la cesta y protección contra sobrecalentamiento',
            'score_categoria'     => '9.3 / 10',
            'puntos_fuertes'      => ['Reduce hasta un 85% el uso de aceite manteniendo textura crocante', 'Canasta cuadrada espaciosa que cabe un pollo entero pequeño', 'Recubrimiento antiadherente fácil de limpiar en lavavajillas'],
            'puntos_debiles'      => ['Requiere espacio libre en la mesada de la cocina'],
            'veredicto'           => 'Transforma la cocina del día a día haciendo comidas saludables, rápidas y deliciosas sin olor a fritura.'
        ]
    ],

    // --- ROPA DE CAMA (Cat 7) ---
    [
        'categoria_id' => 7,
        'nombre' => 'Plumón Edredón Nórdico Reversible Queen',
        'descripcion' => 'Edredón térmico de microfibra tacto pluma, diseño acolchado en cuadros para evitar desplazamiento del relleno y reversible bicolor.',
        'precio' => 50.00,
        'stock' => 30,
        'imagen_url' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=500&auto=format&fit=crop&q=60',
        'sku' => 'HOGA-CAMA-002',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Blancos y Ropa de Cama',
            'composicion'         => 'Funda de microfibra cepillada extrasuave con relleno de fibra siliconada hueca 350 g/m²',
            'medidas'             => 'Queen (220 x 240 cm)',
            'ajuste'              => 'Costuras acolchadas en diamantes de 25 cm para distribución térmica uniforme',
            'colores'             => 'Reversible Gris Claro / Gris Plomo',
            'cuidado'             => 'Lavable en lavadora de gran carga con agua fría, no se apelmace',
            'score_categoria'     => '9.2 / 10',
            'puntos_fuertes'      => ['Aislamiento térmico superior sin el peso sofocante de mantas pesadas', 'Relleno hipoalergénico que no genera alergias ni ácaros', 'Diseño reversible que permite renovar el look de la cama al instante'],
            'puntos_debiles'      => ['En lavadoras pequeñas de menos de 8 kg puede quedar muy justo'],
            'veredicto'           => 'El edredón definitivo para dormir abrigado en noches frías con la ligereza y suavidad de un hotel de lujo.'
        ]
    ],

    // --- CAFÉ & ALIMENTOS (Cat 8) ---
    [
        'categoria_id' => 8,
        'nombre' => 'Café en Grano Yungas Especial 500g',
        'descripcion' => 'Café boliviano arábica de altura (1.650 msnm), cultivo bajo sombra en Caranavi - Yungas, tueste medio con notas a chocolate y cítricos.',
        'precio' => 15.00,
        'stock' => 100,
        'imagen_url' => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=500&auto=format&fit=crop&q=60',
        'sku' => 'ALIM-CAF-002',
        'especificaciones' => [
            'tipo_dispositivo'    => 'Alimento Gourmet',
            'origen'              => 'Caranavi, Los Yungas de La Paz - Bolivia (1.650 m.s.n.m.)',
            'variedad'            => '100% Arábica (Typica y Caturra) de estricta altura',
            'tueste'              => 'Medio artesanal con fecha de tostado reciente',
            'notas_cata'          => 'Chocolate negro, caramelo artesanal y sutil acidez cítrica brillante',
            'presentacion'        => 'Bolsa trilaminada de 500g con válvula desgasificadora unidireccional y cierre zip',
            'score_categoria'     => '9.5 / 10',
            'puntos_fuertes'      => ['Café de especialidad de origen local boliviano premiado internacionalmente', 'Grano entero para conservar los aceites esenciales hasta la molienda', 'Tueste fresco que garantiza un aroma intenso y crema persistente'],
            'puntos_debiles'      => ['Requiere contar con molinillo de café manual o eléctrico'],
            'veredicto'           => 'Una joya cafetalera nacional de altísimo nivel para amantes del buen café expreso o filtrado.'
        ]
    ]
];

// Insertar productos nuevos
$stmtInsProd = $pdo->prepare("
    INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, imagen_url, sku, especificaciones, activo)
    VALUES (:cid, :nom, :des, :pre, :stk, :img, :sku, :spe, 1)
    ON DUPLICATE KEY UPDATE 
        categoria_id = :cid2,
        descripcion = :des2,
        precio = :pre2,
        stock = :stk2,
        imagen_url = :img2,
        especificaciones = :spe2,
        activo = 1
");

foreach ($nuevosProductos as $prod) {
    $speJson = json_encode($prod['especificaciones'], JSON_UNESCAPED_UNICODE);
    $stmtInsProd->execute([
        ':cid'  => $prod['categoria_id'],
        ':nom'  => $prod['nombre'],
        ':des'  => $prod['descripcion'],
        ':pre'  => $prod['precio'],
        ':stk'  => $prod['stock'],
        ':img'  => $prod['imagen_url'],
        ':sku'  => $prod['sku'],
        ':spe'  => $speJson,
        ':cid2' => $prod['categoria_id'],
        ':des2' => $prod['descripcion'],
        ':pre2' => $prod['precio'],
        ':stk2' => $prod['stock'],
        ':img2' => $prod['imagen_url'],
        ':spe2' => $speJson
    ]);
    echo "✔ Producto registrado/actualizado: {$prod['nombre']}\n";
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "🎉 Migración v4 finalizada con éxito! Todas las categorías tienen productos comparables con fichas completas.\n";
