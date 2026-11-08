<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}


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
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
    $html = preg_replace('/<!--.*?-->/s', '', $html);

    $posStart = strpos($html, 'Volver Atras');
    if ($posStart !== false) {
        $html = substr($html, $posStart + strlen('Volver Atras'));
    }
    $posEnd = strpos($html, 'latinoamericana@ulat.edu.bo');
    if ($posEnd !== false) {
        $html = substr($html, 0, $posEnd);
    }

    $html = preg_replace('/<h1[^>]*>(.*?)<\/h1>/is', "\n# $1\n", $html);
    $html = preg_replace('/<h2[^>]*>(.*?)<\/h2>/is', "\n## $1\n", $html);
    $html = preg_replace('/<h3[^>]*>(.*?)<\/h3>/is', "\n### $1\n", $html);
    $html = preg_replace('/<h4[^>]*>(.*?)<\/h4>/is', "\n#### $1\n", $html);
    $html = preg_replace('/<p[^>]*>(.*?)<\/p>/is', "\n$1\n", $html);
    $html = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "\n* $1", $html);
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
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
    // Buscar cualquier imagen >= 600px de ancho y >= 400px de alto que no sea el banner 1920x600
    preg_match_all('/data:image\/(jpeg|jpg|png);base64,([A-Za-z0-9+\/=\s]+)/', $html, $mAll);
    foreach ($mAll[2] as $b64) {
        $clean = preg_replace('/\s+/', '', $b64);
        $bin = base64_decode($clean);
        $info = @getimagesizefromstring($bin);
        if ($info && $info[0] >= 600 && $info[1] >= 400 && !($info[0] === 1920 && $info[1] === 600)) {
            return $bin;
        }
    }
    return null;
}

echo "========================================================\n";
echo "🚀 EXTRACCIÓN INTEGRAL DE CONOCIMIENTO Y MALLAS ULAT\n";
echo "========================================================\n\n";

$careersMap = [
    'medicina' => [
        'nombre' => 'Licenciatura en Medicina',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '6 años (10 semestres académicos + 2 semestres de Internado Rotatorio)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Nº 590/07',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'MEDICINA',
        'kw_malla' => 'MEDICINA',
        'semestres' => [
            '1º Semestre' => ['Anatomía Humana I', 'Histología I', 'Embriología y Genética I', 'Salud Pública I', 'Idiomas I (Quechua-Castellano)', 'Soporte Básico de Vida'],
            '2º Semestre' => ['Anatomía Humana II', 'Histología II', 'Embriología y Genética II', 'Salud Pública II', 'Idiomas II (Quechua-Castellano)'],
            '3º Semestre' => ['Fisiología I', 'Microbiología I', 'Parasitología I', 'Bioquímica I', 'Ética Médica'],
            '4º Semestre' => ['Fisiología II', 'Microbiología II', 'Parasitología II', 'Bioquímica II', 'Psicología Médica'],
            '5º Semestre' => ['Farmacología y Terapéutica I', 'Semiología I', 'Fisiopatología I', 'Técnica Quirúrgica I', 'Patología General', 'Imagenología'],
            '6º Semestre' => ['Farmacología y Terapéutica II', 'Semiología II', 'Fisiopatología II', 'Técnica Quirúrgica II', 'Patología Especial', 'Nutrición'],
            '7º Semestre' => ['Cirugía I', 'Medicina Interna I (Inmunología)', 'Epidemiología', 'Urología', 'Medicina Interna I (Nefrología)', 'Traumatología y Ortopedia'],
            '8º Semestre' => ['Cirugía II', 'Medicina Interna II (Cardiología)', 'Medicina Interna II (Neumología)', 'Medicina Interna II (Reumatología)', 'Psiquiatría', 'Medicina Legal'],
            '9º Semestre' => ['Pediatría I', 'Ginecología', 'Medicina Interna III (Gastroenterología)', 'Neurología y Neurocirugía', 'Medicina Interna III (Endocrinología)', 'Medicina Interna III (Hematología)'],
            '10º Semestre' => ['Pediatría II', 'Obstetricia', 'Medicina Interna IV (Dermatología)', 'Medicina Interna IV (Infectología)', 'Otorrinolaringología', 'Oftalmología', 'Administración Hospitalaria'],
            '11º Semestre (Internado Rotatorio)' => ['Medicina Interna', 'Cirugía', 'Pediatría'],
            '12º Semestre (Internado Rotatorio)' => ['Ginecología y Obstetricia', 'Medicina Social y Familiar (Servicio Social Rural Obligatorio)'],
        ]
    ],
    'odontologia' => [
        'nombre' => 'Licenciatura en Odontología',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'ODONTOLOG',
        'kw_malla' => 'ODONTOLOG',
        'semestres' => [
            '1º Semestre' => ['Anatomía Humana I', 'Embriología y Genética I', 'Histología I', 'Anatomía Dentaria I', 'Salud Pública'],
            '2º Semestre' => ['Anatomía Humana II', 'Embriología y Genética II', 'Histología II', 'Anatomía Dentaria II', 'Bioquímica'],
            '3º Semestre' => ['Fisiología General', 'Patología I', 'Semiología General I', 'Microbiología', 'Materiales Dentales I', 'Técnicas de Laboratorio I'],
            '4º Semestre' => ['Fisiología Estomatológica y de la Oclusión', 'Patología II', 'Semiología General II', 'Materiales Dentales II', 'Técnicas de Laboratorio II'],
            '5º Semestre' => ['Cirugía I', 'Farmacología y Terapéutica', 'Operatoria I', 'Patología Estomatológica', 'Semiología Estomatológica', 'Radiología e Imagenología'],
            '6º Semestre' => ['Cirugía II', 'Periodoncia I', 'Operatoria II', 'Endodoncia I', 'Prostodoncia Fija I', 'Prostodoncia Removible I', 'Radiología e Imagenología Clínica'],
            '7º Semestre' => ['Cirugía III', 'Periodoncia II', 'Odontopediatría I', 'Ortodoncia I', 'Prostodoncia Fija III', 'Metodología de la Investigación', 'Endodoncia II', 'Prostodoncia Removible II'],
            '8º Semestre' => ['Cirugía IV', 'Periodoncia III', 'Odontopediatría II', 'Ortodoncia II', 'Gestión y Administración Clínica'],
            '9º Semestre' => ['Ortodoncia Clínica', 'Deontología Odontológica', 'Medicina Interna en Odontología', 'Odontología Legal y Forense', 'Psicología'],
            '10º Semestre' => ['Clínica Integral del Niño', 'Clínica Integral del Adulto'],
        ]
    ],
    'enfermeria' => [
        'nombre' => 'Licenciatura en Enfermería',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '5 años (10 semestres incluye Internado Rotatorio)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Nº 590/07',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'ENFERMER',
        'kw_malla' => 'ENFERMER',
        'semestres' => [
            '1º Semestre' => ['Anatomía y Fisiología I', 'Fundamentos de Enfermería I', 'Bioquímica', 'Salud Comunitaria I', 'Soporte Básico de Vida', 'Idiomas I'],
            '2º Semestre' => ['Anatomía y Fisiología II', 'Fundamentos de Enfermería II', 'Nutrición y Dietología', 'Salud Comunitaria II', 'Psicología General', 'Idiomas II'],
            '3º Semestre' => ['Semiología', 'Técnica Quirúrgica', 'Médico Quirúrgico I', 'Salud Comunitaria III', 'Farmacología I'],
            '4º Semestre' => ['Parasitología', 'Microbiología', 'Médico Quirúrgico II', 'Salud Comunitaria IV', 'Farmacología II'],
            '5º Semestre' => ['Bioestadística y Demografía', 'Gineco Obstetricia I', 'Pediatría I', 'Salud Comunitaria V', 'Psicología Clínica'],
            '6º Semestre' => ['Epidemiología', 'Gineco Obstetricia II', 'Pediatría II', 'Salud Comunitaria VI', 'Enfermería Geriátrica'],
            '7º Semestre' => ['Terapia Intensiva I', 'Urgencias Médicas I', 'Ética y Legislación', 'Salud Comunitaria VII', 'Psiquiatría'],
            '8º Semestre' => ['Terapia Intensiva II', 'Urgencias Médicas II', 'Pruebas Diagnósticas', 'Administración Hospitalaria', 'Metodología de la Investigación'],
            '9º y 10º Semestre (Internado Rotatorio)' => ['Médico Quirúrgico', 'Salud Comunitaria', 'Gineco Obstetricia', 'Pediatría'],
        ]
    ],
    'bioquimica-farmacia' => [
        'nombre' => 'Licenciatura en Bioquímica y Farmacia',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '4 años y medio (9 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'BIOQU',
        'kw_malla' => 'BIOQU',
        'semestres' => [
            '1º Semestre' => ['Biología Molecular y Celular', 'Química General e Inorgánica', 'Matemáticas Aplicadas', 'Introducción a la Bioquímica y Farmacia', 'Física Aplicada', 'Comunicación y Redacción Científica'],
            '2º Semestre' => ['Anatomía y Fisiología Humana', 'Química Orgánica I', 'Bioestadística', 'Microbiología General', 'Bioética y Legislación', 'Idiomas'],
            '3º Semestre' => ['Química Orgánica II', 'Bioquímica I', 'Genética Humana', 'Fisiopatología General', 'Farmacología I', 'Parasitología Clínica'],
            '4º Semestre' => ['Bioquímica II', 'Química Analítica y Técnicas Instrumentales', 'Inmunología', 'Farmacología II', 'Química Farmacéutica I', 'Biología Molecular Avanzada'],
            '5º Semestre' => ['Toxicología', 'Farmacognosia', 'Bioquímica Clínica I', 'Farmacotecnia I', 'Tecnología Farmacéutica', 'Epidemiología y Salud Pública'],
            '6º Semestre' => ['Química Farmacéutica II', 'Farmacotecnia II', 'Bioquímica Clínica II', 'Gestión y Administración Farmacéutica', 'Investigación Clínica'],
            '7º Semestre' => ['Farmacia Hospitalaria y Comunitaria', 'Biofarmacia', 'Química Clínica', 'Bromatología', 'Legislación y Administración Farmacéutica / Control de Calidad', 'Biotecnología Aplicada'],
            '8º Semestre' => ['Industria Farmacéutica y Tecnología de Medicamentos', 'Oncología Farmacéutica', 'Farmacia Verde', 'Farmacoeconomía', 'Seminarios Avanzados en Bioquímica y Farmacia', 'Control de Calidad de Medicamentos'],
            '9º Semestre (Prácticas Profesionales)' => ['Prácticas en Laboratorio Clínico', 'Prácticas en Farmacia', 'Prácticas en Industria Farmacéutica'],
        ]
    ],
    'optica-optometria' => [
        'nombre' => 'Licenciatura en Óptica y Optometría',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Licenciatura',
        'duracion' => '4 años y medio (9 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'OPTOMETR',
        'kw_malla' => 'OPTOMETR',
        'semestres' => [
            '1º Semestre' => ['Matemáticas', 'Física', 'Química', 'Anatomía y Fisiología I', 'Quechua I'],
            '2º Semestre' => ['Óptica Física', 'Óptica Geométrica', 'Materiales Ópticos y Lentes de Contacto', 'Anatomía y Fisiología II', 'Quechua II'],
            '3º Semestre' => ['Óptica Oftálmica I', 'Práctica de Óptica Oftálmica I', 'Instrumentos Ópticos Optométricos', 'Anatomía y Fisiología Ocular', 'Inglés I Básico'],
            '4º Semestre' => ['Óptica Oftálmica II', 'Práctica de Óptica Oftálmica II', 'Gestión y Administración de Empresas', 'Ética y Legislación', 'Inglés II Intermedio'],
            '5º Semestre' => ['Óptica Oftálmica Avanzada', 'Óptica Fisiológica', 'Psicología Aplicada', 'Neurofisiología', 'Inglés III Avanzado'],
            '6º Semestre' => ['Contactología I', 'Neurofisiología de la Visión', 'Psicobiología de la Visión', 'Salud Comunitaria I'],
            '7º Semestre' => ['Optometría I', 'Contactología II', 'Semiología Ocular', 'Campo Visual', 'Salud Comunitaria II'],
            '8º Semestre' => ['Optometría II', 'Patología y Farmacología Ocular', 'Visión Binocular', 'Prótesis Ocular', 'Baja Visión'],
            '9º Semestre' => ['Optometría y Contactología Clínica', 'Optometría Pediátrica y Geriátrica', 'Terapia y Rehabilitación Visual', 'Optometría en Cirugía Oftálmica', 'Metodología de la Investigación'],
        ]
    ],
    'protesis-dental' => [
        'nombre' => 'Técnico Superior en Prótesis Dental',
        'facultad' => 'Facultad de Ciencias de la Salud',
        'grado' => 'Técnico Superior',
        'duracion' => '2 años y medio (5 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'TESIS DENTAL T.S',
        'kw_malla' => 'TESIS DENTAL',
        'semestres' => [
            '1º Semestre' => ['Anatomía Dental I', 'Escultura Dental I', 'Materiales Dentales', 'Práctica de Anatomía Dental I', 'Práctica de Escultura Dental I', 'Práctica de Materiales Dentales'],
            '2º Semestre' => ['Anatomía Dental II', 'Escultura Dental II', 'Prótesis Parcial Removible I', 'Práctica de Anatomía Dental II', 'Práctica de Escultura Dental II', 'Anatomía Humana'],
            '3º Semestre' => ['Prótesis Fija I', 'Prótesis Parcial Removible II', 'Prótesis Total I', 'Práctica de Prótesis Fija I', 'Práctica de Prótesis Parcial Removible II', 'Práctica de Prótesis Total I'],
            '4º Semestre' => ['Prótesis Fija II', 'Ortodoncia Preventiva', 'Prótesis Total II', 'Práctica de Prótesis Fija II', 'Práctica de Ortodoncia Preventiva', 'Práctica de Prótesis Total II', 'Organización de Laboratorio'],
            '5º Semestre' => ['Cromo Cobalto', 'Ortodoncia', 'Práctica de Cromo Cobalto', 'Práctica de Ortodoncia', 'Práctica Isofit', 'Proyecto de Grado'],
        ]
    ],
    'ingenieria-de-sistemas' => [
        'nombre' => 'Ingeniería de Sistemas',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '4 años y medio (9 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'SISTEMAS',
        'kw_malla' => 'SISTEMAS',
        'semestres' => [
            '1º Semestre' => ['Cálculo I', 'Introducción a la Programación', 'Álgebra I', 'Inglés I', 'Teoría de la Informática'],
            '2º Semestre' => ['Cálculo II', 'Programación Orientada a Objetos (POO) y Estructura de Datos', 'Álgebra II', 'Inglés II', 'Física II'],
            '3º Semestre' => ['Matemáticas Diferenciales', 'Taller de Programación', 'Electrónica Digital', 'Estadística I', 'Contabilidad Básica'],
            '4º Semestre' => ['Sistemas de Información I', 'Bases de Datos I', 'Arquitectura de Computadoras', 'Estadística II', 'Costo para Toma de Decisiones'],
            '5º Semestre' => ['Sistemas de Información II', 'Bases de Datos II', 'Sistemas Operativos', 'Programación en Internet I', 'Ingeniería Económica'],
            '6º Semestre' => ['Investigación Operativa I', 'Taller de Base de Datos', 'Programación en Internet II', 'Mercadotecnia'],
            '7º Semestre' => ['Investigación Operativa II', 'Ingeniería de Software', 'Taller de Programación en Internet', 'Preparación y Evaluación de Proyectos'],
            '8º Semestre' => ['Simulación y Modelaje', 'Taller de Ingeniería de Software', 'Telecomunicaciones I', 'E-Business (Comercio Electrónico)', 'Inteligencia Artificial'],
            '9º Semestre' => ['Metodología de la Investigación', 'Evaluación y Auditoría de Sistemas', 'Telecomunicaciones II', 'Desarrollo de E-Learning', 'Sistemas Expertos'],
        ]
    ],
    'ingenieria-civil' => [
        'nombre' => 'Ingeniería Civil',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'CIVIL',
        'kw_malla' => 'CIVIL',
        'semestres' => [
            '1º Semestre' => ['Química General', 'Física I', 'Álgebra I', 'Cálculo I', 'Geometría Analítica Plana y del Espacio'],
            '2º Semestre' => ['Dibujo Técnico Computarizado', 'Física II', 'Álgebra II', 'Cálculo II', 'Geometría Descriptiva'],
            '3º Semestre' => ['Computación para Ingeniería', 'Electrotecnia', 'Análisis Vectorial y Tensorial', 'Cálculo III', 'Geología General'],
            '4º Semestre' => ['Estructuras Isostáticas', 'Materiales de Construcción', 'Métodos Geodésicos', 'Economía Política', 'Análisis Numérico', 'Idiomas'],
            '5º Semestre' => ['Probabilidad y Estadística', 'Hidráulica I', 'Geodesia y Fotogrametría', 'Geografía y Defensa de Recursos Naturales', 'Resistencia de Materiales I', 'Sistemas de Ingeniería'],
            '6º Semestre' => ['Hidrología', 'Hidráulica II', 'Geomática', 'Transportes y Comunicaciones', 'Resistencia de Materiales II', 'Mecánica de Suelos I'],
            '7º Semestre' => ['Ingeniería Sanitaria I', 'Estructuras Hiperestáticas', 'Estructuras de Madera', 'Hormigón Armado I', 'Ingeniería Económica', 'Mecánica de Suelos II'],
            '8º Semestre' => ['Ingeniería Sanitaria II', 'Estructuras Metálicas', 'Maquinaria y Equipo de Construcción', 'Hormigón Armado II', 'Carreteras I', 'Mecánica de Suelos Aplicada'],
            '9º Semestre' => ['Obras Hidráulicas I', 'Construcción de Edificios', 'Ingeniería Ambiental', 'Dirección de Obras y Valuaciones', 'Fundaciones I', 'Mecánica del Medio Continuo'],
            '10º Semestre (Menciones)' => ['Metodología de la Investigación', 'Mención Estructuras (Diseño de Estructuras Especiales, Puentes, Hormigón Preesforzado)', 'Mención Hidráulica (Obras Hidráulicas II, Centrales Hidroeléctricas)', 'Mención Sanitaria (Plantas de Tratamiento de Agua Potable y Residual)', 'Mención Vialidad (Carreteras II, Aeropuertos, Ferrocarriles)', 'Mención Geotecnia (Fundaciones II, Mecánica de Rocas, Túneles)'],
        ]
    ],
    'ingenieria-geodesia-topografia' => [
        'nombre' => 'Ingeniería en Geodesia y Topografía',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '5 años (10 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'GEODESIA',
        'kw_malla' => 'GEODESIA',
        'semestres' => [
            '1º Semestre' => ['Dibujo Técnico', 'Física I', 'Álgebra I', 'Cálculo I', 'Geometría Analítica Plana y del Espacio', 'Trigonometría Plana'],
            '2º Semestre' => ['Geología General', 'Física II', 'Trigonometría Esférica', 'Cálculo II', 'Dibujo Topográfico', 'Cartografía'],
            '3º Semestre' => ['Geometría Descriptiva', 'Estadística', 'Topografía I', 'Topografía de Campo I', 'Tecnología Instrumental', 'Dibujo Computarizado'],
            '4º Semestre' => ['Topografía II', 'Topografía de Campo II', 'Mecánica de Suelos', 'Teoría de Errores', 'Hidrometría e Hidráulica', 'Ingeniería Legal'],
            '5º Semestre' => ['Probabilidad y Estadística', 'Hidráulica I', 'Geodesia y Fotogrametría', 'Geografía y Defensa de Recursos Naturales', 'Resistencia de Materiales'],
            '6º Semestre' => ['Topografía Aplicada', 'Planificación y Costos Topográficos', 'Geodesia Geométrica I', 'Catastro y Avalúo', 'Percepción Remota', 'Mensuras Subterráneas'],
            '7º Semestre' => ['Ingeniería Sanitaria I', 'Estructuras Hiperestáticas', 'Estructuras de Madera', 'Tecnología del Hormigón', 'Seguridad Industrial'],
            '8º Semestre' => ['Geodesia Vertical', 'Geodesia Física', 'Geodesia Astronómica', 'Rectificación y Restitución Fotogramétrica', 'Geografía y Recursos Naturales'],
            '9º Semestre' => ['Sistemas de Información Geográfica (SIG)', 'Saneamiento Ambiental', 'Geodesia Satelital', 'Administración y Organización de Empresas'],
            '10º Semestre' => ['Metodología de la Investigación', 'Modalidad de Graduación'],
        ]
    ],
    'ingenieria-mecanica-automotriz' => [
        'nombre' => 'Ingeniería Mecánica Automotriz y Electrónica',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '4 años (8 semestres)',
        'modalidad' => 'Presencial (Horarios Diurno y Nocturno)',
        'resolucion' => 'R.M. Nº 0140/25',
        'sede' => 'Campus Norte (Av. Uyuni Nº 1889 casi Av. Circunvalación) y Sede Central',
        'kw_carrera' => 'AUTOMOTRIZ',
        'kw_malla' => 'AUTOMOTRIZ',
        'semestres' => [
            '1º Semestre' => ['Motores a Gasolina I', 'Electricidad Automotriz I', 'Matemática Automotriz', 'Metrología Automotriz', 'Dibujo Técnico Automotriz I', 'Química Automotriz (Combustibles y Lubricantes)'],
            '2º Semestre' => ['Motores a Gasolina II', 'Electricidad Automotriz II', 'Higiene, Seguridad Industrial y Medio Ambiente', 'Física', 'Dibujo Técnico Automotriz II', 'Inglés Técnico I'],
            '3º Semestre' => ['Motores a Gasolina III', 'Electricidad Automotriz III', 'Motores Diésel I', 'Electrónica Automotriz I', 'Termodinámica', 'Transmisiones I'],
            '4º Semestre' => ['Motores a Gasolina IV', 'Inyección a Gasolina I', 'Motores Diésel II', 'Electrónica Automotriz II', 'Neumática', 'Transmisiones II'],
            '5º Semestre' => ['Inyección Electrónica Diésel I', 'Inyección a Gasolina II', 'Laboratorio Diésel I', 'Electrónica Automotriz III', 'Hidráulica', 'Transmisiones III'],
            '6º Semestre' => ['Inyección Electrónica Diésel II', 'Inyección a Gasolina III', 'Laboratorio Diésel II', 'Energías Alternativas', 'Transmisiones IV'],
            '7º Semestre' => ['Tecnología de Materiales Vehiculares', 'Mecánica de Fluidos', 'Mantenimiento de Equipos', 'Administración de Producción', 'Control de Calidad', 'Metodología de la Investigación'],
            '8º Semestre (4 Menciones Especializadas)' => ['Mención Autotrónica', 'Mención Maquinaria Pesada', 'Mención en Cajas Automáticas y Mecánicas', 'Mención en Programación con Llaves con Chip y Módulos Computarizados'],
        ]
    ],
    'ingenieria-comercio-marketing' => [
        'nombre' => 'Ingeniería en Comercio, Marketing y Diseño Digital',
        'facultad' => 'Facultad de Ciencias y Tecnología',
        'grado' => 'Licenciatura en Ingeniería',
        'duracion' => '4 años y medio (9 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'COMERCIO',
        'kw_malla' => 'COMERCIO',
        'semestres' => [
            '1º Semestre' => ['Fundamentos del Comercio Digital', 'Introducción al Marketing Digital', 'Fundamentos del Diseño Gráfico', 'Comunicación y Redacción para Medios Digitales', 'Taller de Creatividad e Innovación Digital', 'Idiomas I'],
            '2º Semestre' => ['Economía Digital y Modelos de Negocio', 'Estrategias de Marketing de Contenidos', 'Diseño de Interfaces (UI)', 'Principios de Diseño Web HTML/CSS', 'Seminario de Investigación I', 'Idiomas II'],
            '3º Semestre' => ['Comercio Electrónico y Marketplaces', 'Publicidad Digital y SEM', 'Diseño de Experiencia de Usuario (UX)', 'Programación Front-End y JavaScript', 'Taller de Emprendimiento Digital'],
            '4º Semestre' => ['Marketing en Redes Sociales', 'Producción Audiovisual para Medios Digitales', 'Gestión de Proyectos Digitales', 'Diseño y Desarrollo de Proyectos Web', 'Seminario de Investigación II'],
            '5º Semestre' => ['Analítica Digital y Data Science', 'Optimización para Motores de Búsqueda (SEO)', 'Gestión de Marca Digital y Branding', 'Diseño y Desarrollo de Aplicaciones Móviles', 'Materia Integradora I'],
            '6º Semestre' => ['Estrategias de Comercio Internacional', 'Inteligencia Artificial Aplicada al Marketing y Diseño', 'Realidad Aumentada y Virtual en Entornos Digitales', 'Gestión de la Reputación Online', 'Taller de Innovación en Comercio y Marketing Digital'],
            '7º Semestre' => ['Automatización de Marketing y CRM', 'Diseño de Videojuegos y Gamificación', 'Modelado y Animación 3D', 'Legislación y Protección de Datos en Entornos Digitales', 'Taller de Diseño de Experiencias Digitales Inmersivas'],
            '8º Semestre' => ['Estrategias Avanzadas de Publicidad Programática', 'Gestión Financiera para Proyectos Digitales', 'Técnicas de Narrativa Digital y Storytelling', 'Seguridad y Ciberseguridad en Plataformas Digitales', 'Materia Integradora II (Proyecto Final I)'],
            '9º Semestre' => ['Transformación Digital y Futuro del Comercio', 'Tendencias Tecnológicas en Marketing y Diseño Digital', 'Taller de Innovación y Disrupción Tecnológica', 'Práctica Profesional', 'Materia Integradora III (Proyecto Final II)'],
        ]
    ],
    'derecho' => [
        'nombre' => 'Licenciatura en Derecho',
        'facultad' => 'Facultad de Ciencias Económicas, Sociales y Jurídicas',
        'grado' => 'Licenciatura',
        'duracion' => '4 años y medio (9 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'DERECHO',
        'kw_malla' => 'DERECHO',
        'semestres' => [
            '1º Semestre' => ['Introducción al Derecho', 'Psicología Jurídica', 'Derecho Romano', 'Sociología General', 'Economía Política', 'Comunicación Escrita y Oratoria Forense'],
            '2º Semestre' => ['Derecho Civil I (Personas)', 'Criminología', 'Teorías del Estado', 'Sociología Jurídica', 'Derecho Procesal y Organización Judicial'],
            '3º Semestre' => ['Derecho Civil II (Reales)', 'Derecho Penal I (Parte General)', 'Derecho Constitucional I', 'Ciencia Política', 'Derecho Administrativo'],
            '4º Semestre' => ['Derecho Civil III (Contratos)', 'Derecho Penal II (Parte General)', 'Derecho Constitucional II', 'Derecho Financiero y Tributario', 'Medicina Legal'],
            '5º Semestre' => ['Derecho Civil IV (Obligaciones)', 'Derecho Penal III (Parte Especial)', 'Derecho del Trabajo', 'Derecho Comercial I', 'Derecho Minero y Petrolero', 'Derechos Humanos'],
            '6º Semestre' => ['Derecho Civil V (Sucesiones)', 'Derecho Penal IV (Parte Especial)', 'Seguridad Social', 'Derecho Comercial II', 'Derecho Notarial y Registro Civil', 'Filosofía Jurídica'],
            '7º Semestre' => ['Derecho Procesal Civil I y Práctica Forense Civil I', 'Derecho Procesal Penal I y Práctica Forense Penal I', 'Derecho Internacional Público', 'Arbitraje y Conciliación', 'Derecho Aduanero', 'Derecho de Familia y del Menor I'],
            '8º Semestre' => ['Derecho Procesal Civil II y Práctica Forense Civil II', 'Derecho Procesal Penal II y Práctica Forense Penal II', 'Derecho Internacional Privado', 'Derecho Agrario', 'Derecho de Familia y del Menor II'],
            '9º Semestre' => ['Procedimientos Especiales', 'Derecho de Integración', 'Derecho Municipal', 'Derecho Ambiental y sus Procedimientos', 'Metodología en la Investigación'],
        ]
    ],
    'ciencias-de-la-educacion' => [
        'nombre' => 'Licenciatura en Ciencias de la Educación',
        'facultad' => 'Facultad de Ciencias Económicas, Sociales y Jurídicas',
        'grado' => 'Licenciatura',
        'duracion' => '4 años y medio (9 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Oficial ULAT',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'EDUCACI',
        'kw_malla' => 'EDUCACI',
        'semestres' => [
            '1º Semestre' => ['Pedagogía General I', 'Fundamentos Biológicos de la Educación', 'Fundamentos Psicológicos de la Educación', 'Sociología General', 'Fundamentos de la Filosofía', 'Lenguaje'],
            '2º Semestre' => ['Pedagogía General II', 'Psicología Evolutiva I', 'Estadística Educativa I', 'Sociología de la Educación', 'Filosofía de la Educación I', 'Quechua I'],
            '3º Semestre' => ['Pedagogía Comparada', 'Psicología Evolutiva II', 'Estadística Educativa II', 'Sociología de la Educación II', 'Filosofía de la Educación II', 'Quechua II'],
            '4º Semestre' => ['Políticas Educativas', 'Psicología de la Educación I', 'Gestión y Administración Educativa I', 'Historia General de la Educación', 'Investigación Educativa I'],
            '5º Semestre' => ['Investigación Educativa II', 'Psicología de la Educación II', 'Gestión y Administración Educativa II', 'Realidad Boliviana', 'Historia de la Educación Boliviana', 'Antropología'],
            '6º Semestre' => ['Investigación Educativa III', 'Didáctica General', 'Legislación Educativa', 'Epistemología', 'Proyectos Sociales y Educativos'],
            '7º Semestre' => ['Diseño Curricular', 'Psicopedagogía y Educación Especial', 'Educación Alternativa', 'Orientación Educativa', 'Evaluación Institucional y Educativa'],
            '8º Semestre' => ['Taller de Diseño y Evaluación Curricular', 'Taller de Psicopedagogía y Educación Especial', 'Taller de Educación Alternativa', 'Taller de Orientación Educativa', 'Programas Educativos Computarizados'],
            '9º Semestre (Menciones)' => ['Práctica Pedagógica', 'Metodologías de la Investigación', 'Mención en Diseño y Evaluación Curricular', 'Mención en Psicopedagogía y Educación Especial', 'Mención en Educación Alternativa'],
        ]
    ],
    'auditoria' => [
        'nombre' => 'Licenciatura en Auditoría (Contaduría Pública)',
        'facultad' => 'Facultad de Ciencias Económicas, Sociales y Jurídicas',
        'grado' => 'Licenciatura',
        'duracion' => '4 años y medio (9 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Nº 241/02',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'AUDITOR',
        'kw_malla' => 'AUDITOR',
        'semestres' => [
            '1º Semestre' => ['Contabilidad Básica', 'Administración General', 'Economía General', 'Álgebra', 'Comunicación Escrita', 'Derecho Comercial'],
            '2º Semestre' => ['Contabilidad Intermedia', 'Sistemas Administrativos', 'Microeconomía', 'Cálculo', 'Matemática Financiera', 'Derecho del Trabajo'],
            '3º Semestre' => ['Contabilidad Bancaria', 'Contabilidad de Sociedades', 'Macroeconomía', 'Estadística I', 'Administración de Personal', 'Derecho Tributario'],
            '4º Semestre' => ['Contabilidad de Costos I', 'Contabilidad de Servicios', 'Informática I', 'Estadística II', 'Matemática Actuarial', 'Organización y Métodos'],
            '5º Semestre' => ['Contabilidad de Costos II', 'Contabilidad Petrolera y Minera', 'Informática II', 'Contabilidad Agropecuaria', 'Contabilidad Fiscal'],
            '6º Semestre' => ['Taller de Costos', 'Gabinete de Contabilidad', 'Análisis e Interpretación de Estados Financieros', 'Economía Fiscal', 'Comercio Internacional', 'Metodología de la Investigación'],
            '7º Semestre' => ['Auditoría I', 'Auditoría Tributaria I', 'Administración Financiera', 'Presupuestos Fiscales', 'Preparación y Evaluación de Proyectos', 'Presupuestos Empresariales'],
            '8º Semestre' => ['Auditoría II', 'Auditoría Tributaria II', 'Seminario Superior', 'Auditoría Operativa', 'Auditoría Forense'],
            '9º Semestre (Menciones)' => ['Gestión Administrativa Tributaria', 'Fiscalización y Medios de Impugnación Tributaria', 'Gestión de Riesgo y Mora Tributaria', 'Gabinete de Tributación', 'Gabinete de Auditoría', 'Menciones: Gestión Tributaria, Consultoría Contable, Control Gubernamental, Auditoría Financiera'],
        ]
    ],
    'contador-general' => [
        'nombre' => 'Técnico Superior Contador General',
        'facultad' => 'Facultad de Ciencias Económicas, Sociales y Jurídicas',
        'grado' => 'Técnico Superior',
        'duracion' => '3 años (6 semestres)',
        'modalidad' => 'Presencial',
        'resolucion' => 'R.M. Nº 241/02',
        'sede' => 'Campus Central (Calle Uruguay #E-836)',
        'kw_carrera' => 'CONTADOR GENERAL',
        'kw_malla' => 'CONTADUR',
        'semestres' => [
            '1º Semestre' => ['Contabilidad Básica', 'Administración General', 'Economía General', 'Álgebra', 'Comunicación Escrita', 'Derecho Comercial'],
            '2º Semestre' => ['Contabilidad Intermedia', 'Sistemas Administrativos', 'Microeconomía', 'Cálculo', 'Matemática Financiera', 'Derecho del Trabajo'],
            '3º Semestre' => ['Contabilidad Bancaria', 'Contabilidad de Sociedades', 'Macroeconomía', 'Estadística I', 'Administración de Personal', 'Derecho Tributario'],
            '4º Semestre' => ['Contabilidad de Costos I', 'Contabilidad de Servicios', 'Informática I', 'Estadística II', 'Matemática Actuarial', 'Organización y Métodos'],
            '5º Semestre' => ['Contabilidad de Costos II', 'Contabilidad Petrolera y Minera', 'Informática II', 'Contabilidad Agropecuaria', 'Contabilidad Fiscal'],
            '6º Semestre' => ['Taller de Costos', 'Gabinete de Contabilidad', 'Análisis e Interpretación de Estados Financieros', 'Economía Fiscal', 'Comercio Internacional', 'Metodología de la Investigación'],
        ]
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

echo "📚 [1/3] Procesando carreras, perfiles y mallas curriculares completas...\n";
foreach ($careersMap as $slug => $meta) {
    echo "   ▶ {$meta['nombre']} ($slug)...\n";
    
    $careerFile = findHtmlExact($allHtml, $meta['kw_carrera'], false);
    $careerText = '';
    if ($careerFile && file_exists($careerFile)) {
        $raw = file_get_contents($careerFile);
        $careerText = cleanHtmlToMarkdown($raw);
    }

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
        }
    }

    // Formatear texto de malla curricular semestre por semestre
    $mallaTextoMd = "## Plan de Estudios y Malla Curricular Oficial ({$meta['duracion']})\n\n";
    foreach ($meta['semestres'] as $semNombre => $materias) {
        $mallaTextoMd .= "### {$semNombre}\n";
        foreach ($materias as $mat) {
            $mallaTextoMd .= "- {$mat}\n";
        }
        $mallaTextoMd .= "\n";
    }

    $processedCatalog[$slug] = [
        'id' => $slug,
        'nombre' => $meta['nombre'],
        'facultad' => $meta['facultad'],
        'grado' => $meta['grado'],
        'duracion' => $meta['duracion'],
        'modalidad' => $meta['modalidad'],
        'resolucion' => $meta['resolucion'],
        'sede' => $meta['sede'],
        'malla_imagen' => $mallaImgRelPath,
        'semestres' => $meta['semestres'],
        'descripcion' => $careerText,
    ];

    // Write individual markdown for RAG including the full curriculum
    $mdDoc = "# Carrera: {$meta['nombre']}\n\n";
    $mdDoc .= "**Facultad:** {$meta['facultad']}\n";
    $mdDoc .= "**Grado Académico:** {$meta['grado']}\n";
    $mdDoc .= "**Duración Oficial:** {$meta['duracion']}\n";
    $mdDoc .= "**Modalidad:** {$meta['modalidad']}\n";
    $mdDoc .= "**Resolución Ministerial:** {$meta['resolucion']}\n";
    $mdDoc .= "**Sede / Campus:** {$meta['sede']}\n\n";
    $mdDoc .= "## Perfil Profesional y Campo Ocupacional\n\n";
    $mdDoc .= $careerText . "\n\n";
    $mdDoc .= $mallaTextoMd;

    file_put_contents($docsDir . "/carrera_{$slug}.md", $mdDoc);
}

file_put_contents($dataDir . '/ulat_catalog.json', json_encode($processedCatalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "   ✔ Catálogo enriquecido guardado en data/ulat_catalog.json\n\n";

echo "🏛️  [2/3] Procesando páginas institucionales y cronograma oficial...\n";
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

// Documento adicional con Sedes, Teléfonos Oficiales, Cronograma II-2026 y Postgrado extraídos de los banners oficiales
$mdExtra = <<<MD
# Información Oficial de Sedes, Contactos, Cronograma II-2026 y Postgrado ULAT

## Sedes y Campus en Cochabamba
- **Sede Central:** Calle Uruguay #E-836 entre 16 de Julio y Oquendo, Cochabamba, Bolivia.
- **Campus Norte (Especializado en Ingeniería Mecánica Automotriz y Electrónica):** Av. Uyuni Nº 1889 casi Av. Circunvalación, Cochabamba.
- **Teléfonos Fijos Centrales:** (4) 4509488 - (4) 4221004
- **WhatsApp Inscripciones en Línea y Admisiones:** 79795778 (también disponible atención en +591 4509488)
- **Correo Institucional:** latinoamericana@ulat.edu.bo

## Cronograma Académico e Inscripciones (Gestión II-2026)
- **Modalidades de Plan de Estudios:** Plan Regular y Plan S.O.S.
- **Inicio de Inscripciones y Pago de Matrículas:**
  - **1ra Opción (Estudiantes Nuevos y Antiguos):** Del 06 de julio al 01 de agosto.
  - **2da Opción (Rezagados de la 1ra opción):** Del 03 al 08 de agosto.
  - **3ra Opción (Casos especiales):** Del 10 al 15 de agosto.
- **Inicio de Clases (Gestión II-2026):** Lunes 03 de agosto.
- **Fecha Límite de Toma de Materias:** Sábado 15 de agosto.

## Programas de Postgrado y Diplomados ULAT
Contactos exclusivos de Postgrado: 79787897 - 77905372 - 79971164 - 70740610.
1. **Diplomado en Auditoría de Confiabilidad:** Duración de 5 meses, modalidad Presencial.
2. **Diplomado en Protocolos de Atención y Marco Normativo sobre Víctimas de Violencia Intrafamiliar, de Género y Trata y Tráfico de Personas:** Duración de 4 meses, modalidad Presencial.
3. **Diplomado en Investigación Científica:** Duración de 5 meses, modalidad Semipresencial.
MD;
file_put_contents($docsDir . '/institucional_cronograma_sedes_postgrado.md', $mdExtra);
echo "   ✔ Generado markdown: institucional_cronograma_sedes_postgrado.md\n";

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
echo "========================================================\n";
