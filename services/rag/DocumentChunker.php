<?php
declare(strict_types=1);

namespace Services\Rag;

/**
 * Convierte el contenido de origen (Markdown de knowledge/ y productos) en fragmentos indexables.
 *
 * - Header injection: cada sección lleva el título del documento y de la sección
 *   ("Políticas Oficiales de la Tienda > Envíos y Tiempos de Entrega"), así el fragmento
 *   contiene sus propias palabras clave aunque el párrafo no las repita.
 * - Secciones largas se parten por líneas con 1 línea de solapamiento para no cortar ideas.
 */
class DocumentChunker
{
    /**
     * @return array<int, array{doc_id: string, title: string, section: string, content: string, embed_text: string}>
     */
    public static function chunkMarkdown(string $raw, string $filename, int $maxChars = 700): array
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        $docTitle = $base;
        if (preg_match('/^#\s+(.+)$/m', $raw, $m)) {
            $docTitle = trim($m[1]);
        }

        // Dividir por encabezados de segundo nivel (## Sección)
        $sections = preg_split('/(?=^##\s+)/m', $raw) ?: [];
        $chunks = [];

        foreach ($sections as $idx => $section) {
            $section = trim($section);
            if (!preg_match('/^##\s+(.+)$/m', $section, $m)) {
                continue; // Bloque del título H1 sin contenido propio
            }
            $sectionTitle = trim($m[1]);
            $body = trim((string)preg_replace('/^##\s+.+$/m', '', $section, 1));
            if ($body === '') {
                continue;
            }

            $header = "{$docTitle} > {$sectionTitle}";
            foreach (self::splitWithOverlap($body, $maxChars) as $part => $text) {
                $content = $header . "\n" . $text;
                $chunks[] = [
                    'doc_id'     => "{$base}_sec_{$idx}_{$part}",
                    'title'      => $header,
                    'section'    => $sectionTitle,
                    'content'    => $content,
                    // El título de la sección se repite para darle más peso en el vector
                    'embed_text' => $sectionTitle . "\n" . $content,
                ];
            }
        }

        return $chunks;
    }

    /**
     * @param array{id: int|string, nombre: string, descripcion: ?string, precio: float|string,
     *              stock: int|string, sku: ?string, categoria: ?string} $prod
     * @param float $tasa Tasa de cambio (ej: 9.96 para USD -> Bs)
     * @param string $simbolo Símbolo de moneda (ej: 'Bs')
     * @return array{doc_id: string, title: string, content: string, embed_text: string, metadata: array}
     */
    public static function productChunk(array $prod, float $tasa = 1.0, string $simbolo = 'Bs'): array
    {
        $categoria = $prod['categoria'] ?? 'General';
        $descripcion = $prod['descripcion'] ?: 'Sin descripción detallada';
        $factor = ($tasa > 0) ? $tasa : 1.0;
        $precioFinal = round(((float)$prod['precio']) * $factor, 2);
        $specsTexto = '';
        if (!empty($prod['especificaciones'])) {
            $decodedSpecs = is_array($prod['especificaciones']) ? $prod['especificaciones'] : json_decode((string)$prod['especificaciones'], true);
            if (is_array($decodedSpecs)) {
                $lineasSpecs = [];
                foreach ($decodedSpecs as $k => $v) {
                    if (is_string($v) && $v !== 'N/A') {
                        $lineasSpecs[] = ucfirst(str_replace('_', ' ', $k)) . ": " . $v;
                    } elseif (is_array($v)) {
                        $lineasSpecs[] = ucfirst(str_replace('_', ' ', $k)) . ": " . implode(', ', $v);
                    }
                }
                if (!empty($lineasSpecs)) {
                    $specsTexto = "\nEspecificaciones técnicas y ficha técnica:\n" . implode(" | ", $lineasSpecs);
                }
            }
        }

        $content = sprintf(
            "Artículo: %s | Categoría: %s | Precio: %s %.2f | Stock disponible: %d unidades | SKU: %s.\nDescripción: %s%s",
            $prod['nombre'],
            $categoria,
            $simbolo,
            $precioFinal,
            (int)$prod['stock'],
            $prod['sku'] ?? 'N/A',
            $descripcion,
            $specsTexto
        );

        return [
            'doc_id'     => 'prod_' . $prod['id'],
            'title'      => 'Producto: ' . $prod['nombre'],
            'content'    => $content,
            'embed_text' => "{$prod['nombre']}. {$prod['nombre']}. {$categoria}. {$descripcion} {$specsTexto} precio stock ficha tecnica procesador pantalla camara bateria",
            'metadata'   => [
                'tipo'            => 'producto',
                'producto_id'     => $prod['id'],
                'nombre'          => $prod['nombre'],
                'precio'          => $precioFinal,
                'precio_base_usd' => (float)$prod['precio'],
                'stock'           => (int)$prod['stock'],
                'categoria'       => $prod['categoria'] ?? null,
            ],
        ];
    }

    /**
     * Parte un texto por líneas en bloques de ~$maxChars, repitiendo la última línea
     * del bloque anterior al inicio del siguiente (solapamiento).
     * @return string[]
     */
    private static function splitWithOverlap(string $text, int $maxChars): array
    {
        if (mb_strlen($text) <= $maxChars) {
            return [$text];
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn($l) => $l !== ''));
        $parts = [];
        $current = [];
        $len = 0;

        foreach ($lines as $line) {
            if ($len + mb_strlen($line) > $maxChars && !empty($current)) {
                $parts[] = implode("\n", $current);
                $last = end($current);
                $current = [$last];
                $len = mb_strlen($last);
            }
            $current[] = $line;
            $len += mb_strlen($line);
        }
        if (!empty($current)) {
            $parts[] = implode("\n", $current);
        }

        return $parts;
    }
}
