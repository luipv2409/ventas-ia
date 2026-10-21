# 🔐 GUÍA MAESTRA: CONFIGURACIÓN DE API KEYS Y VARIABLES DE ENTORNO

Esta guía detalla con precisión **dónde, cómo y qué API Keys** requiere el sistema **Ventas IA**, garantizando que tus claves privadas permanezcan seguras en tu máquina local y **nunca se expongan públicamente en GitHub**.

---

## 🛡️ 1. Política de Seguridad y `.gitignore`

El repositorio cuenta con un archivo `.gitignore` configurado en la raíz para ignorar automáticamente:
* `.env` (archivo donde residen tus credenciales secretas reales).
* Cualquier archivo de claves privadas (`*.key`, `*.pem`, credenciales de nube).

> [!IMPORTANT]
> **REGLA DE ORO:**  
> **NUNCA** coloques tus API Keys reales dentro del código fuente con seguimiento de Git (como `config.php` o archivos `.js`).  
> Colócalas **únicamente en tu archivo local `.env`**.

---

## 📍 2. ¿Dónde colocar las API Keys exactamente?

El proyecto utiliza un sistema de inyección desacoplado que lee las variables desde el archivo local `.env`.

### 📂 Ubicación del Archivo:
```text
c:\xampp\htdocs\ventas-ia-main\.env
```

### Paso a paso para activarlo:
1. En la raíz del proyecto encontrarás el archivo plantilla [`.env.example`](file:///c:/xampp/htdocs/ventas-ia-main/.env.example).
2. Si aún no existe tu archivo `.env`, crea una copia con el nombre `.env`:
   ```powershell
   copy .env.example .env
   ```
3. Abre tu archivo `.env` y pega tus claves en las variables correspondientes:

```env
# ============================================================
# CREDENCIALES DE INTELIGENCIA ARTIFICIAL
# ============================================================

# 1. DeepSeek (Motor principal para Valeria y Jarvis BI)
# Reemplaza con tu clave que inicia con "sk-..."
DEEPSEEK_API_KEY=sk-tu_clave_real_de_deepseek_aqui

# 2. Groq (Opcional - Motor Llama 3)
GROQ_API_KEY=gsk_tu_clave_de_groq_aqui

# 3. Google Gemini (Opcional - Motor Gemini Flash)
GEMINI_API_KEY=tu_clave_de_gemini_aqui

# 4. Google Client ID (Opcional - Login 1-click con cuenta Google)
GOOGLE_CLIENT_ID=tu_client_id_de_google_aqui
```

---

## 🔑 3. Inventario de API Keys y Cómo Obtenerlas

| Proveedor / Servicio | Variable en `.env` | Función en Ventas IA | Dónde obtenerla (100% Oficial) |
|---|---|---|---|
| **DeepSeek** *(Recomendado / Activo)* | `DEEPSEEK_API_KEY` | • Asesoría de compras con RAG ("Valeria")<br>• Asistente Ejecutivo BI ("Jarvis Admin") | [https://platform.deepseek.com/api_keys](https://platform.deepseek.com/api_keys) |
| **Groq (Llama 3)** | `GROQ_API_KEY` | • Fallback alternativo de velocidad ultra rápida | [https://console.groq.com/keys](https://console.groq.com/keys) |
| **Google AI Studio** | `GEMINI_API_KEY` | • Motor alternativo Gemini 1.5 Flash | [https://aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey) |
| **Google Cloud Console** | `GOOGLE_CLIENT_ID` | • Botón de Google Sign-In gratuito en login de clientes | [https://console.cloud.google.com/apis/credentials](https://console.cloud.google.com/apis/credentials) |
| **Telegram Bot** | `TELEGRAM_BOT_TOKEN`<br>`TELEGRAM_CHAT_ID` | • Alertas instantáneas al móvil cuando se confirma un pedido | Habla con `@BotFather` en Telegram |

---

## ⚙️ 4. ¿Cómo lee el sistema las claves según el entorno?

### A. Si ejecutas con Docker (Recomendado):
El archivo [`docker-compose.yml`](file:///c:/xampp/htdocs/ventas-ia-main/docker-compose.yml) inyecta automáticamente el valor de `.env` al contenedor PHP:
```yaml
environment:
  DEEPSEEK_API_KEY: ${DEEPSEEK_API_KEY:-}
```
*Cada vez que cambies una clave en `.env`, solo recarga los contenedores:*
```powershell
docker compose up -d
```

### B. Si ejecutas con XAMPP (Apache en Windows sin Docker):
El archivo [`config/config.php`](file:///c:/xampp/htdocs/ventas-ia-main/config/config.php) lee las variables de entorno de Windows mediante `getenv('DEEPSEEK_API_KEY')`.  
Si no usas variables del sistema operativo en Windows, puedes definirla temporalmente en PowerShell antes de iniciar tus pruebas:
```powershell
$env:DEEPSEEK_API_KEY="sk-tu_clave_real_de_deepseek_aqui"
```

---

## 🧪 5. ¿Cómo verificar que tu API Key está funcionando?

Ejecuta el script de prueba automatizado incluido en el proyecto:

### Desde Docker:
```powershell
docker exec -i ventas_ia_app php tests/test_deepseek_call.php
```

### Desde consola local (XAMPP / PHP):
```powershell
php test_ia.php
```

Si la clave es válida, recibirás un mensaje de confirmación con respuesta generada en tiempo real por el modelo.

---

## 🚀 6. Buenas Prácticas al Trabajar con Git y GitHub

1. **Antes de hacer `git add .`**, revisa siempre el estado con `git status`:
   - Verifica que `.env` **NO** figure en la lista de archivos a commitear.
   - Solo deben figurar `.env.example`, `GUIA_API_KEYS.md`, código fuente, etc.
2. Si por error agregaste `.env` al área de preparación de Git (stage), quítalo con:
   ```powershell
   git rm --cached .env
   ```
3. Si GitHub activa su protección (**Push Protection**), significa que un commit contiene una clave expuesta. Gracias al `.gitignore` y a esta guía, tus commits ahora serán 100% limpios y seguros.
