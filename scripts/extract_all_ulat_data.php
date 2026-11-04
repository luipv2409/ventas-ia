<?php
declare(strict_types=1);

$knowledgeDir = __DIR__ . '/../knowledge/ULAT';
$docsDir = __DIR__ . '/../knowledge/ulat_docs';
$assetsDir = __DIR__ . '/../assets/ulat';
$mallasDir = $assetsDir . '/mallas';
$dataDir = __DIR__ . '/../data';

foreach ([$docsDir, $assetsDir, $mallasDir, $dataDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function cleanHtmlToMarkdown(string $html): string {
    // Strip script, style, comments
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
    $html = preg_replace('/<!--.*?-->/s', '', $html);

    // Remove navigation header and footer
    $posStart = strpos($html, 'Volver Atras');
    if ($posStart !== false) {
        $html = substr($html, $posStart + strlen('Volver Atras'));
    }
    $posEnd = strpos($html, 'latinoamericana@ulat.edu.bo');
    if ($posEnd !== false) {
        $html = substr($html, 0, $posEnd);
    }

    // Convert headers
    $html = preg_replace('/<h1[^>]*>(.*?)<\/h1>/is', "\n# $1\n", $html);
    $html = preg_replace('/<h2[^>]*>(.*?)<\/h2>/is', "\n## $1\n", $html);
    $html = preg_replace('/<h3[^>]*>(.*?)<\/h3>/is', "\n### $1\n", $html);
    $html = preg_replace('/<h4[^>]*>(.*?)<\/h4>/is', "\n#### $1\n", $html);
    $html = preg_replace('/<p[^>]*>(.*?)<\/p>/is', "\n$1\n", $html);
    $html = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "\n* $1", $html);
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Normalize newlines and spaces
    $lines = explode("\n", $text);
    $cleanLines = [];
    foreach ($lines as $line) {
        $l = trim(preg_replace('/\s+/', ' ', $line));
        if ($l !== '' && $l !== 'Volver Atras' && $l !== 'Inicio' && $l !== 'Malla Curricular' && $l !== 'Carreras destacadas' && !str_starts_with($l, ':root')) {
            $cleanLines[] = $l;
        }
    }
    return implode("\n\n", $cleanLines);
}

function extractMallaImage(string $html): ?string {
    if (preg_match('/<div[^>]+class=["\'][^"\']*vc_single_image-wrapper[^"\']*["\'][^>]*>.*?data:image\/(jpeg|jpg|png);base64,([A-Za-z0-9+\/=\s]+)/is', $html, $m)) {
        $clean = preg_replace('/\s+/', '', $m[2]);
        $bin = base64_decode($clean);
        $info = @getimagesizefromstring($bin);
        if ($info && $info[0] >= 400) {
            return $bin;
        }
    }
    // Fallback: search any image >= 400px width that is not 1920x600
    preg_match_all('/data:image\/(jpeg|jpg|png);base64,([A-Za-z0-9+\/=\s]+)/', $html, $mAll);
    foreach ($mAll[2] as $b64) {
        $clean = preg_replace('/\s+/', '', $b64);
        $bin = base64_decode($clean);
        $info = @getimagesizefromstring($bin);
        if ($info && $info[0] >= 400 && !($info[0] === 1920 && $info[1] === 600)) {
            return $bin;
        }
    }
    return null;
}

echo "========================================================\n";
echo "🚀 EXTRACCIÓN INTEGRAL DE CONOCIMIENTO Y ACTIVOS ULAT\n";
echo "========================================================\n\n";

$careersMap = [
    'medicina' => [
        'nombre' => 'Licenciatura en Medicina',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '5 años + 1 año de Internado Rotatorio',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'MEDICINA',
        'kw_malla' => 'MEDICINA'
    ],
    'odontologia' => [
        'nombre' => 'Licenciatura en Odontología',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'ODONTOLOG',
        'kw_malla' => 'ODONTOLOG'
    ],
    'enfermeria' => [
        'nombre' => 'Licenciatura en Enfermería',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '4 años y medio (9 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'ENFERMER',
        'kw_malla' => 'ENFERMER'
    ],
    'bioquimica-farmacia' => [
        'nombre' => 'Licenciatura en Bioquímica y Farmacia',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'BIOQU',
        'kw_malla' => 'BIOQU'
    ],
    'optica-optometria' => [
        'nombre' => 'Licenciatura en Óptica y Optometría',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '4 años (8 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'OPTOMETR',
        'kw_malla' => 'OPTOMETR'
    ],
    'protesis-dental' => [
        'nombre' => 'Técnico Superior en Prótesis Dental',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Técnico Superior',
        'duracion' => '3 años (6 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'TESIS DENTAL T.S',
        'kw_malla' => 'TESIS DENTAL'
    ],
    'ingenieria-de-sistemas' => [
        'nombre' => 'Ingeniería de Sistemas',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'SISTEMAS',
        'kw_malla' => 'SISTEMAS'
    ],
    'ingenieria-civil' => [
        'nombre' => 'Ingeniería Civil',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'CIVIL',
        'kw_malla' => 'CIVIL'
    ],
    'ingenieria-geodesia-topografia' => [
        'nombre' => 'Ingeniería en Geodesia y Topografía',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'GEODESIA',
        'kw_malla' => 'GEODESIA'
    ],
    'ingenieria-mecanica-automotriz' => [
        'nombre' => 'Ingeniería Mecánica Automotriz y Electrónica',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'AUTOMOTRIZ',
        'kw_malla' => 'AUTOMOTRIZ'
    ],
    'ingenieria-comercio-marketing' => [
        'nombre' => 'Ingeniería en Comercio, Marketing y Diseño Digital',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'COMERCIO',
        'kw_malla' => 'COMERCIO'
    ],
    'derecho' => [
        'nombre' => 'Licenciatura en Derecho',
        'facultad' => 'Facultad de Ciencias Económicas, Sociales y Jurídicas',
        'grado' => 'Licenciatura',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'DERECHO',
        'kw_malla' => 'DERECHO'
    ],
    'ciencias-de-la-educacion' => [
        'nombre' => 'Licenciatura en Ciencias de la Educación',
        'facultad' => 'Facultad de Ciencias Económicas, Sociales y Jurídicas',
        'grado' => 'Licenciatura',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'EDUCACI',
        'kw_malla' => 'EDUCACI'
    ],
    'auditoria' => [
        'nombre' => 'Licenciatura en Auditoría (Contaduría Pública)',
        'facultad' => 'Facultad de Ciencias Económicas, Sociales y Jurídicas',
        'grado' => 'Licenciatura',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'AUDITOR',
        'kw_malla' => 'AUDITOR'
    ],
    'contador-general' => [
        'nombre' => 'Técnico Superior Contador General',
        'facultad' => 'Facultad de Ciencias Económicas, Sociales y Jurídicas',
        'grado' => 'Técnico Superior',
        'duracion' => '3 años (6 semestres)',
        'modalidad' => 'Presencial',
        'kw_carrera' => 'CONTADOR GENERAL',
        'kw_malla' => 'CONTADUR'
    ],
];

$allHtml = glob($knowledgeDir . '/*.html');

function findHtmlExact(array $all, string $keyword, bool $isMalla): ?string {
    foreach ($all as $f) {
        $base = basename($f);
        $hasMalla = str_contains($base, 'MALLA');
        if ($isMalla && !$hasMalla) continue;
        if (!$isMalla && $hasMalla) continue;
        if (stripos($base, $keyword) !== false) {
            return $f;
        }
    }
    return null;
}

$processedCatalog = [];

echo "📚 [1/3] Procesando carreras y extrayendo mallas curriculares...\n";
foreach ($careersMap as $slug => $meta) {
    echo "   ▶ {$meta['nombre']} ($slug)...\n";
    
    // Find career HTML
    $careerFile = findHtmlExact($allHtml, $meta['kw_carrera'], false);
    $careerText = '';
    if ($careerFile && file_exists($careerFile)) {
        $raw = file_get_contents($careerFile);
        $careerText = cleanHtmlToMarkdown($raw);
    } else {
        echo "     ⚠ No se encontró HTML de carrera para: {$meta['kw_carrera']}\n";
    }

    // Find malla HTML & extract image
    $mallaFile = findHtmlExact($allHtml, $meta['kw_malla'], true);
    $mallaImgRelPath = '';
    if ($mallaFile && file_exists($mallaFile)) {
        $rawMalla = file_get_contents($mallaFile);
        $imgBin = extractMallaImage($rawMalla);
        if ($imgBin) {
            $imgName = "malla_{$slug}.jpg";
            file_put_contents($mallasDir . '/' . $imgName, $imgBin);
            $mallaImgRelPath = 'assets/ulat/mallas/' . $imgName;
            $info = getimagesizefromstring($imgBin);
            echo "     ✔ Malla imagen guardada: {$imgName} ({$info[0]}x{$info[1]}, " . round(strlen($imgBin)/1024) . " KB)\n";
        } else {
            echo "     ⚠ No se pudo extraer imagen de malla: {$meta['kw_malla']}\n";
        }
    } else {
        echo "     ⚠ No se encontró HTML de malla: {$meta['kw_malla']}\n";
    }

    $processedCatalog[$slug] = [
        'id' => $slug,
        'nombre' => $meta['nombre'],
        'facultad' => $meta['facultad'],
        'grado' => $meta['grado'],
        'duracion' => $meta['duracion'],
        'modalidad' => $meta['modalidad'],
        'malla_imagen' => $mallaImgRelPath,
        'descripcion' => $careerText,
    ];

    // Write individual markdown for RAG
    $mdDoc = "# Carrera: {$meta['nombre']}\n\n";
    $mdDoc .= "**Facultad:** {$meta['facultad']}\n";
    $mdDoc .= "**Grado Académico:** {$meta['grado']}\n";
    $mdDoc .= "**Duración:** {$meta['duracion']}\n";
    $mdDoc .= "**Modalidad:** {$meta['modalidad']}\n\n";
    $mdDoc .= "## Perfil Profesional y Campo Ocupacional\n\n";
    $mdDoc .= $careerText . "\n\n";
    $mdDoc .= "**Malla Curricular:** Disponible para visualización en el portal web de la ULAT.\n";

    file_put_contents($docsDir . "/carrera_{$slug}.md", $mdDoc);
}

// Save catalog JSON
file_put_contents($dataDir . '/ulat_catalog.json', json_encode($processedCatalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "   ✔ Catálogo guardado en data/ulat_catalog.json\n\n";

echo "🏛️  [2/3] Procesando páginas institucionales...\n";
$instPages = [
    'admisiones' => ['kw' => 'REQUISITOS DE ADMISI', 'title' => 'Requisitos de Admisión y Matriculación ULAT'],
    'becas' => ['kw' => 'PROGRAMA DE BECAS', 'title' => 'Programa de Becas ULAT (Excelencia, Deportiva, Convenio)'],
    'plan_uno_a_uno' => ['kw' => 'PLAN UNO A UNO', 'title' => 'Plan Uno a Uno ULAT (Descuento 50% por Recomendación)'],
    'mision_vision' => ['kw' => 'Misi', 'title' => 'Misión y Visión Institucional ULAT'],
    'historia' => ['kw' => 'Historia', 'title' => 'Historia y Resoluciones Ministeriales ULAT'],
    'mensaje_rector' => ['kw' => 'Mensaje del Rector', 'title' => 'Mensaje de Bienvenida del Rector ULAT'],
    'facultad_salud' => ['kw' => 'FACULTAD DE CIENCIAS DE LA SALUD', 'title' => 'Facultad de Ciencias de la Salud ULAT'],
    'facultad_tecnologia' => ['kw' => 'FACULTAD DE CIENCIAS Y TECNOLOG', 'title' => 'Facultad de Ciencia y Tecnología ULAT'],
    'facultad_economicas' => ['kw' => 'FACULTAD DE CIENCIAS ECON', 'title' => 'Facultad de Ciencias Económicas, Sociales y Jurídicas ULAT'],
];

foreach ($instPages as $key => $info) {
    $file = findHtmlExact($allHtml, $info['kw'], false);
    if ($file && file_exists($file)) {
        $text = cleanHtmlToMarkdown(file_get_contents($file));
        $md = "# {$info['title']}\n\n" . $text;
        file_put_contents($docsDir . "/institucional_{$key}.md", $md);
        echo "   ✔ Generado markdown: institucional_{$key}.md\n";
    }
}

echo "\n📄 [3/3] Copiando reglamentos oficiales para RAG...\n";
$pdfCount = 0;
foreach (glob($knowledgeDir . '/*.pdf') as $pdfPath) {
    $dest = $docsDir . '/' . basename($pdfPath);
    copy($pdfPath, $dest);
    $pdfCount++;
    echo "   ✔ Copiado PDF a ulat_docs: " . basename($pdfPath) . "\n";
}

echo "\n========================================================\n";
echo "🎉 EXTRACCIÓN COMPLETADA CON ÉXITO!\n";
echo "   - Carreras procesadas: " . count($processedCatalog) . "\n";
echo "   - Documentos Markdown generados: " . count(glob($docsDir . '/*.md')) . "\n";
echo "   - Mallas curriculares en imagen: " . count(glob($mallasDir . '/*.jpg')) . "\n";
echo "   - Reglamentos PDF vinculados: {$pdfCount}\n";
echo "========================================================\n";
