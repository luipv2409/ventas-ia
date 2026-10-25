<?php
declare(strict_types=1);

namespace Services\Chat;

/**
 * Perfil de dominio del chatbot (datos, no código).
 *
 * Aísla todo lo que es específico de un rubro (alias de productos, sinónimos, reglas de
 * variantes/tallas, bloques de prompt de venta cruzada) para que ChatEngine sea reutilizable.
 * Se carga desde config/domain_profile.json o desde DOMAIN_PROFILE_PATH.
 */
final class DomainProfile
{
    private static ?DomainProfile $default = null;

    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public static function default(): self
    {
        if (self::$default === null) {
            $path = getenv('DOMAIN_PROFILE_PATH') ?: (__DIR__ . '/../../config/domain_profile.json');
            self::$default = self::fromFile((string)$path);
        }
        return self::$default;
    }

    /** Solo para tests: fuerza o limpia el perfil por defecto. */
    public static function resetDefault(?self $profile = null): void
    {
        self::$default = $profile;
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            error_log("[DomainProfile] No existe el perfil '{$path}'; se usa un perfil vacío.");
            return new self();
        }
        $decoded = json_decode((string)file_get_contents($path), true);
        if (!is_array($decoded)) {
            error_log("[DomainProfile] JSON inválido en '{$path}': " . json_last_error_msg());
            return new self();
        }
        return new self($decoded);
    }

    /**
     * Sinónimos ordenados de la clave más larga a la más corta, para que "celulares"
     * se evalúe antes que "celular" (evita reemplazos parciales).
     *
     * @return array<string, string>
     */
    public function sinonimos(): array
    {
        $s = array_filter((array)($this->data['sinonimos'] ?? []), 'is_string');
        uksort($s, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        return $s;
    }

    /** @return string[] */
    public function prefijosIgnorables(): array
    {
        return array_values(array_filter((array)($this->data['prefijos_nombre_ignorables'] ?? []), 'is_string'));
    }

    /** @return array<string, string> alias (minúsculas) => nombre oficial de producto */
    public function aliasesModelos(): array
    {
        $out = [];
        foreach ((array)($this->data['aliases_modelos'] ?? []) as $alias => $oficial) {
            if (is_string($alias) && is_string($oficial) && $alias !== '' && $alias[0] !== '_') {
                $out[mb_strtolower($alias)] = $oficial;
            }
        }
        return $out;
    }

    /** @return array<int, string[]> id de categoría => términos */
    public function terminosCategoria(): array
    {
        $out = [];
        foreach ((array)($this->data['terminos_categoria'] ?? []) as $id => $terms) {
            if (is_numeric($id) && is_array($terms)) {
                $out[(int)$id] = array_values(array_filter($terms, 'is_string'));
            }
        }
        return $out;
    }

    /** @return array<int, array{regex: string, prefijo: string, mayusculas: bool}> */
    public function variantes(): array
    {
        $out = [];
        foreach ((array)($this->data['variantes'] ?? []) as $rule) {
            if (is_array($rule) && !empty($rule['regex']) && is_string($rule['regex'])) {
                $out[] = [
                    'regex'      => $rule['regex'],
                    'prefijo'    => (string)($rule['prefijo'] ?? ''),
                    'mayusculas' => (bool)($rule['mayusculas'] ?? false),
                ];
            }
        }
        return $out;
    }

    /** Bloque de prompt (lista de líneas en el JSON) como texto. Vacío si no está definido. */
    public function bloquePrompt(string $nombre): string
    {
        $lineas = $this->data['prompt_bloques'][$nombre] ?? [];
        if (is_string($lineas)) {
            return trim($lineas);
        }
        return is_array($lineas) ? trim(implode("\n", array_filter($lineas, 'is_string'))) : '';
    }
}
