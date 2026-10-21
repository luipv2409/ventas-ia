<?php
declare(strict_types=1);

/**
 * ARCHIVO: includes/icons.php
 * PROPÓSITO: Resuelve y renderiza íconos minimalistas de línea fina (Lucide Icons, 1.75px)
 *            garantizando un aspecto moderno, sobrio y profesional estilo Apple / Linear.
 */

if (!function_exists('resolverIconoLucide')) {
    function resolverIconoLucide(string|null $iconoRaw = '', int $catId = 0): string {
        $iconoRaw = trim((string)$iconoRaw);

        // Mapeo curado de los departamentos principales
        $porId = [
            1 => 'smartphone',   // Celulares & Smartphones
            2 => 'tablet',       // Tablets
            3 => 'headphones',   // Audio & Auriculares
            4 => 'sport-shoe',   // Zapatillas & Calzado
            5 => 'shirt',        // Poleras & Camisetas
            6 => 'cooking-pot',  // Electrodomésticos de Cocina
            7 => 'bed-double',   // Ropa de Cama & Hogar
            8 => 'coffee',       // Café & Alimentos Gourmet
        ];

        if ($catId > 0 && isset($porId[$catId]) && (empty($iconoRaw) || str_contains($iconoRaw, 'fa-'))) {
            return $porId[$catId];
        }

        // Si ya es un identificador directo de Lucide
        if (!empty($iconoRaw) && !str_contains($iconoRaw, 'fa-') && !str_contains($iconoRaw, ' ')) {
            return $iconoRaw;
        }

        // Mapeo heurístico para clases existentes de FontAwesome o nuevas categorías
        $rawLower = strtolower($iconoRaw);
        $mapaFa = [
            'mobile'    => 'smartphone',
            'phone'     => 'smartphone',
            'tablet'    => 'tablet',
            'headphone' => 'headphones',
            'audio'     => 'headphones',
            'shoe'      => 'sport-shoe',
            'running'   => 'sport-shoe',
            'shirt'     => 'shirt',
            'blender'   => 'cooking-pot',
            'kitchen'   => 'cooking-pot',
            'cooking'   => 'cooking-pot',
            'pot'       => 'cooking-pot',
            'bed'       => 'bed-double',
            'pillow'    => 'bed-double',
            'mug'       => 'coffee',
            'coffee'    => 'coffee',
            'laptop'    => 'laptop',
            'computer'  => 'laptop',
            'tv'        => 'tv',
            'watch'     => 'watch',
            'camera'    => 'camera',
            'tag'       => 'tag'
        ];

        foreach ($mapaFa as $needle => $lucide) {
            if (str_contains($rawLower, $needle)) {
                return $lucide;
            }
        }

        return $porId[$catId] ?? 'tag';
    }
}

if (!function_exists('renderLucide')) {
    function renderLucide(string $iconName, string $extraClass = '', float $stroke = 1.75): string {
        return '<i data-lucide="' . htmlspecialchars($iconName) . '" class="' . htmlspecialchars($extraClass) . '" style="stroke-width:' . $stroke . 'px;"></i>';
    }
}
