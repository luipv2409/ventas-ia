<?php
declare(strict_types=1);

namespace Services\Rag;

use Throwable;

/**
 * Extractor nativo de texto plano desde archivos PDF (Zero-Dependencies).
 * 
 * Estrategias:
 * 1. CLI Fallback: Si el sistema tiene disponible 'pdftotext', lo utiliza de forma segura.
 * 2. PHP Pure Parser: Localiza streams /FlateDecode, descomprime con gzuncompress/gzinflate,
 *    parsea comandos de texto de PDF (BT...ET, Tj, TJ) y traduce secuencias octales a UTF-8.
 */
class PdfExtractor
{
    /**
     * Extrae texto plano de un archivo PDF.
     * Retorna string vacío si el archivo no existe, está corrupto o es un PDF solo de imágenes.
     */
    public static function extractText(string $filePath): string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return '';
        }

        // 1. Intentar pdftotext si está disponible en el entorno
        $cliText = self::extractWithCli($filePath);
        if ($cliText !== null && mb_strlen(trim($cliText)) > 20) {
            return self::cleanText($cliText);
        }

        // 2. Extractor nativo PHP
        $raw = file_get_contents($filePath);
        if ($raw === false || $raw === '') {
            return '';
        }

        $nativeText = self::extractNative($raw);
        return self::cleanText($nativeText);
    }

    /**
     * Intenta extraer texto con el comando pdftotext si está en el PATH del sistema.
     */
    private static function extractWithCli(string $filePath): ?string
    {
        try {
            // Verificar si pdftotext responde
            $testCmd = (stripos(PHP_OS, 'WIN') === 0) ? 'where pdftotext 2>NUL' : 'which pdftotext 2>/dev/null';
            $hasCmd = @shell_exec($testCmd);
            if (!$hasCmd) {
                return null;
            }

            $escaped = escapeshellarg($filePath);
            $output = @shell_exec("pdftotext -layout {$escaped} -");
            return is_string($output) ? $output : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Parser nativo en PHP que descomprime streams FlateDecode y decodifica operadores de texto.
     */
    private static function extractNative(string $pdfData): string
    {
        $extracted = '';

        // Buscar todos los bloques "stream ... endstream"
        $pos = 0;
        while (($start = strpos($pdfData, 'stream', $pos)) !== false) {
            // Avanzar después de la palabra 'stream' y su salto de línea (\r\n o \n)
            $streamStart = $start + 6;
            if (isset($pdfData[$streamStart]) && $pdfData[$streamStart] === "\r") {
                $streamStart++;
            }
            if (isset($pdfData[$streamStart]) && $pdfData[$streamStart] === "\n") {
                $streamStart++;
            }

            $end = strpos($pdfData, 'endstream', $streamStart);
            if ($end === false) {
                break;
            }

            $streamData = substr($pdfData, $streamStart, $end - $streamStart);
            $pos = $end + 9;

            // Intentar descomprimir con gzuncompress / gzinflate
            $decompressed = null;
            if ($streamData !== '') {
                $decompressed = @gzuncompress($streamData);
                if ($decompressed === false) {
                    $decompressed = @gzinflate($streamData);
                }
            }

            $textSource = ($decompressed !== false && $decompressed !== null) ? $decompressed : $streamData;

            // Extraer bloques de texto delimitados por BT (Begin Text) y ET (End Text)
            if (preg_match_all('/BT[\s\S]*?ET/s', $textSource, $btMatches)) {
                foreach ($btMatches[0] as $btBlock) {
                    $extracted .= self::parseBtBlock($btBlock) . "\n";
                }
            }
        }

        // Si no encontró streams comprimidos, buscar cadenas directas entre paréntesis (PDFs sin comprimir)
        if (mb_strlen(trim($extracted)) < 20) {
            if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $pdfData, $directMatches)) {
                $extracted = implode(' ', $directMatches[1]);
            }
        }

        return $extracted;
    }

    /**
     * Parsea un bloque BT...ET y extrae el texto de operadores Tj, TJ y comillas.
     */
    private static function parseBtBlock(string $btBlock): string
    {
        $blockText = '';

        // 1. Operador TJ con arreglos: [(Texto)-10( otro texto)] TJ
        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $btBlock, $tjMatches)) {
            foreach ($tjMatches[1] as $arrayContent) {
                if (preg_match_all('/\((.*?)\)/s', $arrayContent, $strings)) {
                    $blockText .= implode('', array_map([self::class, 'unescapeString'], $strings[1])) . ' ';
                }
            }
        }

        // 2. Operador Tj individual: (Texto) Tj
        if (preg_match_all('/\((.*?)\)\s*Tj/s', $btBlock, $tMatches)) {
            foreach ($tMatches[1] as $str) {
                $blockText .= self::unescapeString($str) . ' ';
            }
        }

        // 3. Operador de comillas (salto de línea + texto): ' o "
        if (preg_match_all('/[\'"]\s*\((.*?)\)/s', $btBlock, $quoteMatches)) {
            foreach ($quoteMatches[1] as $str) {
                $blockText .= self::unescapeString($str) . "\n";
            }
        }

        return $blockText;
    }

    /**
     * Decodifica secuencias de escape de caracteres y códigos octales en formato PDF.
     */
    private static function unescapeString(string $str): string
    {
        // Reemplazar códigos octales (\040 -> espacio, etc.)
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr((int)octdec($m[1]));
        }, $str);

        // Escapes comunes en PDF
        $replaces = [
            '\\n'  => "\n",
            '\\r'  => "\r",
            '\\t'  => "\t",
            '\\b'  => "\x08",
            '\\f'  => "\x0C",
            '\\('  => '(',
            '\\)'  => ')',
            '\\\\' => '\\',
        ];

        return strtr((string)$str, $replaces);
    }

    /**
     * Limpieza final del texto extraído: normaliza saltos de línea y elimina basura no imprimible.
     */
    public static function cleanText(string $text): string
    {
        // Convertir a UTF-8 si viene en otra codificación
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1, Windows-1252, ASCII');
        }

        // Eliminar caracteres de control excepto salto de línea y tabulaciones
        $text = (string)preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        // Reducir espacios múltiples
        $text = (string)preg_replace('/[ \t]+/', ' ', $text);
        // Reducir saltos de línea excesivos
        $text = (string)preg_replace('/\n\s*\n\s*\n+/', "\n\n", $text);

        return trim($text);
    }
}
