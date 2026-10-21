Listed directory ventas-ia-main
Listed directory api
Listed directory services
Listed directory rag
Listed directory chat
Listed directory config
Listed directory database
Listed directory knowledge
Listed directory admin
Listed directory scripts
Listed directory includes
Viewed config.php:1-91
Viewed .env.example:1-37
Viewed ia_handler.php:1-211
Viewed chat_frontend.php:1-180
Viewed chat_frontend.php:181-360
Viewed chat_frontend.php:361-527
Viewed HumanPersona.php:1-69
Viewed chat_rag.php:1-164
Viewed RagService.php:1-175
Viewed EmbeddingService.php:1-180
Viewed VectorStore.php:1-180
Viewed VectorStore.php:181-270
Viewed DocumentChunker.php:1-153
Viewed index_rag.php:1-150
Viewed chat_admin.php:1-100
Viewed chat_admin.php:101-250
Viewed schema.sql:1-169
Viewed rag_schema.sql:1-15
Viewed diccionario_bd.json:1-91
Viewed migrate_v6_auth_clientes.php:1-51
Searched for "chat_admin_hilos"
Viewed migrate_v3.php:15-60
Listed directory admin
Listed directory assets
Listed directory js
Listed directory tests
Viewed test_deepseek_call.php:1-34
Viewed QueryExpander.php:1-107

# INFORME TÉCNICO DE AUDITORÍA DE SOFTWARE E INTELIGENCIA ARTIFICIAL

**Proyecto:** Tienda Virtual con Chatbot de Ventas (DeepSeek + RAG)  
**Entorno auditado:** `c:\xampp\htdocs\ventas-ia-main`  
**Rol:** Arquitecto de Software Senior & Ingeniero de IA  
**Estado:** Inspección estática completada (sin modificaciones en el código base)

---

## 1. Stack Tecnológico y Estructura

### Lenguaje, Framework y Gestor de Paquetes
* **Lenguaje Backend:** **PHP 8.1+** con tipado estricto (`declare(strict_types=1);`).
* **Framework Backend:** **PHP Vanilla (Nativo)**. No se utiliza ningún framework MVC como Laravel o Symfony; la arquitectura está organizada mediante controladores API procedimentales/REST planos (`api/`) y servicios modulares orientados a objetos con Namespaces (`services/`).
* **Frontend:** **Vanilla JavaScript (ES6+)**, HTML5 semántico y CSS modular con variables nativas. Se apoya en librerías estáticas locales (sin bundlers): `lucide.min.js` (iconografía) y DataTables en el panel de administración.
* **Gestor de Paquetes:** **Ninguno**. No existen archivos `composer.json` ni `package.json` en la raíz. El proyecto opera bajo una filosofía "cero dependencias de terceros en runtime", dependiendo exclusivamente de extensiones nativas de PHP (`ext-pdo`, `ext-curl`, `ext-mbstring`, `ext-json`).

### Árbol de Directorios Resumido (Lógica Core del Bot y RAG)
```text
ventas-ia-main/
├── api/                             # Endpoints HTTP consumidos por el cliente y admin
│   ├── chat_frontend.php            # Endpoint del Chatbot cliente (Valeria) con catálogo y RAG
│   ├── chat_rag.php                 # Endpoint de prueba/inspección RAG aislado
│   ├── chat_admin.php               # Endpoint del Asistente Ejecutivo (Jarvis BI) con 16 pseudo-tools
│   └── ia_handler.php               # Endpoint legacy v3 (soporte anterior Groq/Gemini)
├── config/                          # Configuración centralizada
│   ├── config.php                   # Constantes de entorno, APIs de IA, base de datos y credenciales
│   └── database.php                 # Singleton PDO para conexión a MariaDB/MySQL
├── services/                        # Capa de dominio y servicios del sistema
│   ├── chat/
│   │   └── HumanPersona.php         # Reglas de identidad, estilo humano, tono boliviano y filtros
│   ├── rag/                         # Motor RAG híbrido
│   │   ├── DocumentChunker.php      # Chunking de Markdown (knowledge/) y Catálogo (MySQL)
│   │   ├── EmbeddingService.php     # Vectorizador dual (TF-IDF local L2 + OpenAI fallback)
│   │   ├── VectorStore.php          # Persistencia en MySQL y similitud coseno en memoria
│   │   ├── QueryExpander.php        # Expansión de preguntas ambiguas vía DeepSeek
│   │   └── RagService.php           # Orquestador RAG, umbrales y re-ranking léxico
│   └── admin/
│       └── AnalyticsService.php     # Métricas de negocio para el chatbot administrativo
├── database/                        # Esquemas y migraciones SQL
│   ├── schema.sql                   # Esquema base de la tienda
│   ├── rag_schema.sql               # Esquema de tablas vectoriales (rag_documents)
│   └── migrate_v3.php ... v6.php    # Migraciones incrementales de base de datos
├── knowledge/                       # Corpus estático de conocimiento RAG
│   ├── politicas_tienda.md          # Políticas de envíos, garantías y pagos
│   └── preguntas_frecuentes.md      # FAQs comerciales
└── scripts/
    └── index_rag.php                # Script CLI/Web de indexación y cálculo IDF
```

### Librerías Clave Instaladas
* **Inteligencia Artificial:** **Ningún SDK oficial** (sin `deepseek-php`, `openai-php` ni similares). La comunicación se realiza de forma directa mediante la extensión nativa **cURL de PHP** (`curl_init`, `curl_setopt_array`, `curl_exec`).
* **Base de Datos:** Driver nativo **PDO MySQL** (`PDO::ATTR_ERRMODE`, prepared statements).
* **Lectura de Archivos / PDFs:** **No existen librerías de lectura o extracción de PDFs** (no están instaladas `smalot/pdfparser`, `spatie/pdf-to-text` ni extensiones como `pdftotext`). La lectura de documentos se limita a archivos Markdown/texto plano mediante `file_get_contents()` y `glob()`.
* **Llamadas HTTP:** Cliente nativo **cURL de PHP**.

---

## 2. Integración Actual con DeepSeek

### Modelo y Cliente HTTP
* **Modelo Exacto:** Configurado como `deepseek-chat` (por variable de entorno `DEEPSEEK_MODEL` o fallback a `'deepseek-chat'` en [config.php](file:///c:/xampp/htdocs/ventas-ia-main/config/config.php#L50)).
* **Endpoint API:** `https://api.deepseek.com/v1/chat/completions`.
* **Cliente HTTP:** Cliente cURL síncrono nativo configurado con headers `Authorization: Bearer DEEPSEEK_API_KEY` y `Content-Type: application/json`, con timeouts entre 15 y 22 segundos según el controlador.
*(Nota: En [config.php](file:///c:/xampp/htdocs/ventas-ia-main/config/config.php) persisten constantes de Groq y Gemini de fases previas, pero los controladores principales apuntan a DeepSeek).*

### Estructura del System Prompt
El prompt se construye dinámicamente mediante interpolación de cadenas `HEREDOC`:
1. **Identidad y Persona Humana:** Inyectada mediante [HumanPersona::reglas()](file:///c:/xampp/htdocs/ventas-ia-main/services/chat/HumanPersona.php#L23): Asesora de ventas ("Valeria"), lenguaje cercano de e-commerce boliviano, prohibición expresa de muletillas de bot ("¡Excelente pregunta!", "Como asistente virtual...") y política de transparencia obligatoria (no negar que es una IA si se le pregunta formalmente).
2. **Contexto de Tienda:** Nombre del negocio, tasa de cambio oficial (USD a Bs) y datos de contacto/WhatsApp.
3. **Contexto RAG:** Bloque condicional inyectado bajo `### DATOS OFICIALES DE LA TIENDA PARA ESTA CONSULTA:` con fragmentos recuperados.
4. **Catálogo Embebido:** En [chat_frontend.php](file:///c:/xampp/htdocs/ventas-ia-main/api/chat_frontend.php#L173), se inyecta en el prompt la serialización JSON completa de todos los productos activos con stock (`$catalogoJSON`).
5. **Directivas de Formato y Pseudo-Tools:** Instrucciones estrictas de responder en JSON con acciones (`filtrar_catalogo`, `mostrar_producto`, `agregar_carrito` en frontend; o 16 herramientas ejecutivas de BI en admin).

### Streaming, Memoria y Function Calling
* **Streaming:** **NO implementado**. Todas las peticiones esperan la respuesta completa mediante `curl_exec()` con `CURLOPT_RETURNTRANSFER = true`. No hay Server-Sent Events (SSE) ni WebSockets.
* **Historial de Conversación (Memoria):**
  * *Chat Frontend:* **Stateless en backend**. El historial vive en la memoria/LocalStorage del cliente JavaScript. En cada mensaje, el frontend envía un array `historial` y el backend recorta los últimos 6 a 8 mensajes (`array_slice($historial, -8)`) para enviarlos como objetos `role: user|assistant` en el payload.
  * *Chat Admin:* **Stateful en Base de Datos**. Posee tablas dedicadas (`chat_admin_hilos` y `chat_admin_mensajes`) que persisten cada turno y cargan los últimos 6 mensajes del hilo activo.
* **Tool / Function Calling:** **NO utiliza la API nativa de Tool Calling de DeepSeek** (no se usa el parámetro `tools` / `tool_choice`). En su lugar, usa **Prompt-based Action Calling**: se instruye al modelo en texto para que devuelva un objeto JSON estructurado (`{"respuesta": "...", "acciones": [...]}` o `{"accion": "...", "parametros": {...}}`). El backend luego procesa la respuesta mediante regex de saneamiento (`preg_replace`) y `json_decode`, apoyándose en un fallback determinista si el LLM no genera el JSON esperado.

---

## 3. Estado Actual del Sistema RAG

### Procesamiento de Documentos / PDFs
* **Lectura de PDFs:** **Inexistente**. No hay ningún parser ni pipeline para ingestar PDFs.
* **Fuentes Procesadas:**
  1. Documentos Markdown en `knowledge/*.md` ([politicas_tienda.md](file:///c:/xampp/htdocs/ventas-ia-main/knowledge/politicas_tienda.md), [preguntas_frecuentes.md](file:///c:/xampp/htdocs/ventas-ia-main/knowledge/preguntas_frecuentes.md)).
  2. Catálogo de productos en MySQL (tabla `productos`).
* **Chunking y Overlap ([DocumentChunker.php](file:///c:/xampp/htdocs/ventas-ia-main/services/rag/DocumentChunker.php)):**
  * *Markdown:* División semántica por encabezados H2 (`## Sección`). Si una sección excede `$maxChars = 700` caracteres, se subdivide por líneas con un **solapamiento de 1 línea** (`overlap = 1 línea física`). Aplica *Header Injection* anteponiendo `"Documento > Sección"` a cada fragmento.
  * *Productos:* 1 fragmento único por producto (`productChunk()`) que sintetiza nombre, categoría, precio oficial, stock, SKU, descripción y ficha técnica JSON.

### Modelo de Embeddings
El sistema implementa una arquitectura híbrida en [EmbeddingService.php](file:///c:/xampp/htdocs/ventas-ia-main/services/rag/EmbeddingService.php):
* **Modo por Defecto (Zero-Cost / Local):** Generador propio en PHP puro. No usa una red neuronal externa. Genera vectores dispersos (*sparse vectors*) normalizados en $L_2$ mediante:
  * TF sublineal ($1 + \ln(tf)$).
  * Ponderación IDF global calculada durante la indexación vía [scripts/index_rag.php](file:///c:/xampp/htdocs/ventas-ia-main/scripts/index_rag.php).
  * N-gramas (unigramas con peso 1.0 y bigramas con peso 1.5).
  * Mapeo de conceptos semánticos de e-commerce en español (peso 0.8) y lista de stopwords comerciales.
* **Modo OpenAI (Opcional):** Si se define `OPENAI_API_KEY` en el entorno, conmuta a `text-embedding-3-small` (vector denso).
* **Rol de DeepSeek en RAG:** DeepSeek **no** genera embeddings. Se utiliza en [QueryExpander.php](file:///c:/xampp/htdocs/ventas-ia-main/services/rag/QueryExpander.php) como reescritor/expansor de consultas cuando la similitud local es baja (`score < 0.40`).

### Almacenamiento y Búsqueda Vectorial
* **Motor de Almacenamiento:** **MySQL / MariaDB local** (no se utiliza Supabase, pgvector ni Pinecone).
* **Estructura de Almacenamiento:** Tabla `rag_documents` con columna `embedding JSON` (almacena el diccionario asociativo `{término: peso}` o lista de floats) y `rag_meta` para persistir la tabla global de frecuencias IDF.
* **Mecanismo de Búsqueda:**
  1. **In-Memory en PHP:** Ejecuta un `SELECT id, collection, doc_id, title, content, metadata_json, embedding FROM rag_documents`, levantando todos los registros a la memoria de PHP.
  2. **Similitud Coseno:** Calcula el producto punto de los vectores en PHP ([VectorStore::rankRows](file:///c:/xampp/htdocs/ventas-ia-main/services/rag/VectorStore.php#L229)).
  3. **Re-Ranking Léxico:** Consulta complementaria con el índice `FULLTEXT` de MySQL (`MATCH(title, content) AGAINST(:q IN NATURAL LANGUAGE MODE)`) que aplica una bonificación léxica (`LEXICAL_BOOST = 0.08`).
  4. **Filtrado:** Umbral mínimo de score `DEFAULT_MIN_SCORE = 0.22` y poda relativa al 45% del mejor resultado.

---

## 4. Manejo de Base de Datos y Estado

### Base de Datos Principal
* **Motor:** **MySQL 5.7+ / MariaDB 10.3+** (ejecutado bajo XAMPP o Docker Compose en el puerto 3306/3307), base de datos `ventas_ia`.

### Esquema de Tablas Relevantes (Chat, Usuarios, Documentos y Catálogo)
Consultando los esquemas SQL y diccionarios del proyecto:

| Tabla | Columnas Principales | Propósito y Relación con el Bot |
| :--- | :--- | :--- |
| **`rag_documents`** | `id`, `collection`, `doc_id`, `title`, `content`, `metadata_json`, `embedding` (JSON), `created_at`, `updated_at` | Vector store del sistema. Almacena chunks indexados y sus vectores. Llave única `(collection, doc_id)` e índice `FULLTEXT(title, content)`. |
| **`rag_meta`** | `meta_key` (PK), `meta_value` (LONGTEXT), `updated_at` | Metadatos globales RAG (almacena el vocabulario e IDFs del corpus). |
| **`chat_logs`** | `id`, `session_id`, `pregunta`, `respuesta`, `tokens_usados`, `creado_en` | Registro histórico de auditoría de preguntas del chat de clientes. |
| **`chat_admin_hilos`** | `id`, `titulo`, `admin_id`, `creado_en`, `actualizado_en` | Hilos de conversación persistentes para el Asistente BI del administrador. |
| **`chat_admin_mensajes`** | `id`, `hilo_id` (FK), `role` (`user`/`assistant`), `content`, `tipo`, `datos_json`, `creado_en` | Mensajes individuales con sus payloads de herramientas de cada hilo admin. |
| **`clientes`** | `id`, `nombre`, `telefono`, `email`, `direccion`, `password_hash`, `google_id`, `avatar_url`, `token_recuperacion`, `token_expira`, `ultimo_login`, `activo`, `creado_en` | Usuarios de la tienda (autenticación tradicional y Google OAuth). |
| **`productos`** | `id`, `categoria_id` (FK), `proveedor_id` (FK), `nombre`, `descripcion`, `especificaciones` (JSON/TEXT), `precio`, `stock`, `imagen_url`, `sku`, `activo` | Catálogo de productos. Se inyecta en el prompt y se indexa en RAG. |
| **`categorias`** | `id`, `nombre`, `descripcion`, `slug`, `activa`, `creado_en` | Categorías de la tienda usadas por el bot para filtrar la interfaz en vivo. |
| **`configuracion`** | `clave` (PK), `valor` | Variables del negocio (`tasa_cambio_usd`, `moneda_simbolo`, `nombre_tienda`, `nombre_asesor`). |

---

## 5. Herramientas MCP Activas en el Entorno

En este momento, en la sesión de Antigravity están activos **4 servidores MCP** que exponen un total de **44 herramientas**:

1. **`chrome-devtools-mcp` (32 herramientas):**  
   Control, automatización y auditoría de navegación web:  
   `click`, `close_page`, `drag`, `emulate`, `evaluate_script`, `fill`, `fill_form`, `get_console_message`, `get_css_styles`, `get_network_request`, `handle_dialog`, `hover`, `lighthouse_audit`, `list_console_messages`, `list_network_requests`, `list_pages`, `navigate_page`, `new_page`, `performance_analyze_insight`, `performance_start_trace`, `performance_stop_trace`, `press_key`, `resize_page`, `screencast_start`, `screencast_stop`, `select_page`, `take_heapsnapshot`, `take_screenshot`, `take_snapshot`, `type_text`, `upload_file`, `wait_for`.
2. **`parallel-search` (2 herramientas):**  
   Búsqueda y extracción web en vivo:  
   `web_search`, `web_fetch`.
3. **`pinecone-mcp-server` (9 herramientas):**  
   Gestión de índices y operaciones vectoriales en Pinecone:  
   `search-docs`, `list-indexes`, `describe-index`, `describe-index-stats`, `create-index-for-model`, `upsert-records`, `search-records`, `rerank-documents`, `cascading-search`.
4. **`sequential-thinking` (1 herramienta):**  
   Razonamiento dinámico estructurado y resolución de problemas complejos:  
   `sequentialthinking`.

*(Nota Técnica: No hay ningún servidor MCP conectado para consultar directamente el motor MySQL/MariaDB; las consultas de esquema se realizaron a través de la inspección de archivos de migración, esquemas SQL y diccionarios del proyecto).*

---

## 6. Puntos Débiles y Cuellos de Botella Detectados

Tras la auditoría de arquitectura, se identifican las siguientes **3 mayores limitaciones técnicas críticas**:

### 1. Búsqueda Vectorial RAG In-Memory No Escalable ($O(N)$ en PHP)
* **Diagnóstico:** En [VectorStore.php](file:///c:/xampp/htdocs/ventas-ia-main/services/rag/VectorStore.php#L200-L218), el método `searchSimilar()` hace un `SELECT * FROM rag_documents` sin límite, volcando **toda la base de vectores a la memoria RAM de PHP** para calcular la similitud del coseno fila por fila mediante bucles `foreach` en CPU.
* **Impacto:** Con el volumen actual (~20 a 50 fragmentos) funciona en milisegundos, pero es un diseño con complejidad temporal y espacial $O(N)$. Si el catálogo supera los 1,000 productos o documentos extensos, agotará el `memory_limit` de PHP, bloqueará el hilo de ejecución e incrementará drásticamente la latencia. No aprovecha índices vectoriales aproximados (ANN como HNSW o IVFFlat) ni una base de datos vectorial especializada como Pinecone (disponible en tus MCPs).

### 2. Ausencia de Streaming (Latencia Percibida y Riesgo de Timeouts HTTP)
* **Diagnóstico:** Los controladores ([chat_frontend.php](file:///c:/xampp/htdocs/ventas-ia-main/api/chat_frontend.php#L230-L245) y [chat_admin.php](file:///c:/xampp/htdocs/ventas-ia-main/api/chat_admin.php#L226-L240)) operan llamadas HTTP cURL estrictamente síncronas (`CURLOPT_RETURNTRANSFER = true`).
* **Impacto:** DeepSeek puede demorar entre 3 y 8 segundos en generar una respuesta completa con razonamiento y acciones JSON. Durante ese tiempo, el usuario en el frontend ve un spinner estático sin ningún indicador de progreso (Time-To-First-Token infinito). En conexiones móviles o periodos de alta carga de la API, esto expone la aplicación a timeouts de Apache/PHP-FPM (504 Gateway Timeout).

### 3. Saturación de Context Window e Inyección Bruta de Catálogo (Pseudo-Tool Calling Frágil)
* **Diagnóstico:** En lugar de dejar que el sistema RAG recupere únicamente los 3 o 4 productos relevantes, [chat_frontend.php](file:///c:/xampp/htdocs/ventas-ia-main/api/chat_frontend.php#L174) serializa y **vuelca todo el catálogo completo de productos en el System Prompt** en cada turno conversacional. Además, no se utiliza el Function Calling nativo de la API de DeepSeek, sino que se fuerza la salida en JSON mediante instrucciones textuales.
* **Impacto:**
  * **Costo y Límites de Contexto:** A medida que crezca el inventario, el tamaño del prompt superará los límites de tokens o disparará exponencialmente el costo por inferencia en DeepSeek.
  * **Fragilidad del Parser:** Se requiere una capa excesiva de regex y decenas de líneas de código de saneamiento y fallback heurístico determinista porque los LLMs frecuentemente rompen la sintaxis JSON o filtran fragmentos markdown, lo que debilita la confiabilidad de las acciones automáticas (como agregar al carrito o filtrar la página).

*(Observación adicional: El sistema no dispone de ingesta real de PDFs ni observabilidad centralizada de latencias y costos de tokens mediante herramientas como OpenTelemetry o Langfuse).*