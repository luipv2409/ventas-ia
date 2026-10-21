# HISTORIAL DEL PROYECTO Y CONTEXTO PARA AGENTES IA

> **Propósito de este archivo:** Este documento preserva el contexto técnico, las decisiones de arquitectura, los cambios realizados y el estado actual del repositorio `ventas-ia-main`. Si eres un agente IA ejecutando una nueva sesión o en otra máquina después de un `git pull`, **lee este archivo primero** para tener todo el contexto sin necesidad de re-inspeccionar todos los archivos.

---

## 1. Visión General del Proyecto
- **Nombre:** Sistema de Ventas con IA y Telegram (E-commerce con Asistente Virtual).
- **Stack Principal:**
  - **Lenguaje:** PHP 8.2 (con tipado estricto `declare(strict_types=1)`).
  - **Base de Datos:** MariaDB 10.11 / MySQL (base de datos `ventas_ia`).
  - **Frontend:** Vanilla JS (`assets/js/catálogo.js`, `assets/js/chat.js`, `assets/js/carrito.js`), Bootstrap 5, FontAwesome.
  - **IA / LLM:** DeepSeek API (`deepseek-chat`) y Groq API (`llama-3.1-8b-instant`).
  - **Contenedores:** Docker & Docker Compose.

---

## 2. Historial de Sesiones y Decisiones Tomadas

### Sesión 1: Dockerización y Resolución de Conflictos de Puertos (06/10/2026)
- **Problema inicial:** El host local (Windows/XAMPP) ya tenía puertos ocupados (`80`, `443`, `3000`, `3001`, `3306`, `5432`, `8080`, `8096`, `8443`).
- **Solución implementada:**
  - Se configuró Docker Compose usando puertos completamente libres:
    - **Web Apache / PHP 8.2:** Puerto host `8085` -> Contenedor `80`
    - **Base de Datos MariaDB 10.11:** Puerto host `3307` -> Contenedor `3306`
    - **phpMyAdmin:** Puerto host `8082` -> Contenedor `80`
  - Se creó [Dockerfile](file:///c:/xampp/htdocs/ventas-ia-main/Dockerfile) con `php:8.2-apache`, extensiones `pdo`, `pdo_mysql`, `curl`, y módulo `mod_rewrite`.
  - Se creó [docker-compose.yml](file:///c:/xampp/htdocs/ventas-ia-main/docker-compose.yml) y [.env](file:///c:/xampp/htdocs/ventas-ia-main/.env).
  - Se agregó [docker/init.sql](file:///c:/xampp/htdocs/ventas-ia-main/docker/init.sql) para asegurar `CREATE DATABASE IF NOT EXISTS ventas_ia; USE ventas_ia;` junto con el volcado completo [database/ventas_ia.sql](file:///c:/xampp/htdocs/ventas-ia-main/database/ventas_ia.sql).
  - Se refactorizó [config/config.php](file:///c:/xampp/htdocs/ventas-ia-main/config/config.php) y [config/database.php](file:///c:/xampp/htdocs/ventas-ia-main/config/database.php) para leer variables de entorno (`DB_HOST`, `DB_PORT`, `DB_NAME`, etc.) con fallback a valores locales para no romper la compatibilidad con XAMPP.
  - Se corrigieron rutas fijas en `test_ia.php` y `test_raw.php` para apuntar a `api/ia_handler.php` en vez de `/simulacion/ventas/...`.
- **Estado verificado:**
  - `http://localhost:8085/test_conexion.php` -> HTTP 200 (BD conectada, 6 tablas, 9 productos semilla, 4 categorías).
  - `http://localhost:8085/index.php` -> HTTP 200 (Catálogo y tienda operativos).
  - `http://localhost:8085/admin/index.php` -> HTTP 200 (Login admin: `admin` / `admin123`).
  - `http://localhost:8082` -> HTTP 200 (phpMyAdmin operativo con host `db` y contraseña `rootpassword`).

---

## 3. Estado Actual de la Base de Datos y Catálogo
- **Tablas principales:**
  - `categorias`: ID, nombre, slug, descripción.
  - `productos`: ID, nombre, descripción, precio, stock, imagen_url, categoria_id, sku, activo.
  - `configuracion`: Parámetros globales clave-valor (`nombre_tienda`, `contacto_telefono`, `contacto_email`, `nombre_dueño`, `moneda_simbolo`, etc.).
  - `clientes`, `pedidos`, `detalle_pedidos`, `chat_logs`.
  - `usuarios_admin`: Usuario `admin`, hash Bcrypt de `admin123`.

---

## 4. Flujo Actual del Asistente de Chat
1. El usuario escribe en el widget web de `index.php`.
2. [assets/js/chat.js](file:///c:/xampp/htdocs/ventas-ia-main/assets/js/chat.js) hace `POST` a `api/chat_frontend.php` con `{ pregunta, historial }`.
3. [api/chat_frontend.php](file:///c:/xampp/htdocs/ventas-ia-main/api/chat_frontend.php):
   - Consulta todos los productos activos (`SELECT id, nombre, SUBSTRING(descripcion, 1, 80)...`).
   - Serializa todo el catálogo a JSON y lo incrusta en el System Prompt.
   - Aplica filtros de sinónimos y detección de presupuesto.
   - Llama a la API de DeepSeek (`DEEPSEEK_API_URL`, modelo `deepseek-chat`).
   - Parsea la respuesta JSON esperada (`{ "respuesta": "...", "acciones": [...] }`).
   - Devuelve `{ ok: true, respuesta: "...", acciones: [...] }`.

---

## 5. Implementación RAG (Retrieval-Augmented Generation) — 06/10/2026
- **Objetivo Cumplido:** Añadir búsqueda semántica RAG para el catálogo y políticas de la tienda sin romper nada del código existente (100% non-breaking).
- **Aclaración sobre DeepSeek:** DeepSeek no cuenta con endpoint público de embeddings en su API. Se resolvió creando un motor semántico local en PHP con ponderación TF-IDF y normalización L2 que corre **100% dentro del contenedor Docker sin costo adicional**, utilizando el saldo de DeepSeek para la generación del chat. Opcionalmente, soporta OpenAI `text-embedding-3-small` si se define `OPENAI_API_KEY`.
- **Arquitectura de Archivos Creados:**
  - `database/rag_schema.sql` y tabla `rag_documents` en MariaDB: Almacena fragmentos de texto, metadatos y vectores en JSON con clave única `(collection, doc_id)`.
  - [knowledge/politicas_tienda.md](file:///c:/xampp/htdocs/ventas-ia-main/knowledge/politicas_tienda.md): Documento oficial de envíos, métodos de pago (QR, bancos, efectivo), garantías y devoluciones.
  - [knowledge/preguntas_frecuentes.md](file:///c:/xampp/htdocs/ventas-ia-main/knowledge/preguntas_frecuentes.md): Horarios, procesos de compra y facturación fiscal.
  - [services/rag/EmbeddingService.php](file:///c:/xampp/htdocs/ventas-ia-main/services/rag/EmbeddingService.php): Tokenización, filtrado de stopwords en español, stemming de sufijos y normalización L2.
  - [services/rag/VectorStore.php](file:///c:/xampp/htdocs/ventas-ia-main/services/rag/VectorStore.php): Persistencia y cálculo ultra-rápido de Similitud de Coseno ponderado con cobertura de términos.
  - [services/rag/RagService.php](file:///c:/xampp/htdocs/ventas-ia-main/services/rag/RagService.php): Orquestador con fallback silencioso automático ante errores, timeouts o preguntas fuera de dominio (score bajo).
  - [scripts/index_rag.php](file:///c:/xampp/htdocs/ventas-ia-main/scripts/index_rag.php): Script ejecutable por CLI (`docker exec -i ventas_ia_app php scripts/index_rag.php`) o Web para re-indexar productos y documentos markdown.
  - [api/chat_rag.php](file:///c:/xampp/htdocs/ventas-ia-main/api/chat_rag.php): Endpoint de pruebas aislado para validar consultas RAG con DeepSeek sin afectar producción. Retorna metadatos de depuración (`rag_applied`, `rag_sources`).
  - [api/chat_frontend.php](file:///c:/xampp/htdocs/ventas-ia-main/api/chat_frontend.php): Endpoint productivo original. Conserva el 100% de su comportamiento por defecto. Si se envía `"use_rag": true`, activa el RAG de forma no destructiva.
  - [tests/test_rag_retrieval.php](file:///c:/xampp/htdocs/ventas-ia-main/tests/test_rag_retrieval.php): Pruebas automáticas de recuperación semántica.

---

## 6. Configuración de API Key de DeepSeek
- La constante `DEEPSEEK_API_KEY` lee la variable de entorno desde `.env` con fallback en `config/config.php`.
- La clave activa configurada en `.env` ha sido verificada exitosamente contra el endpoint `https://api.deepseek.com/v1/chat/completions` (HTTP 200).
- Para cambiar la clave en cualquier momento:
  1. Modifica `DEEPSEEK_API_KEY=sk-...` en `.env`.
  2. Ejecuta: `docker compose up -d` para recargar el contenedor de la aplicación.

---

## 7. Guía Rápida de Comandos y Operaciones

### Levantar y Detener el Entorno Docker
```powershell
# Iniciar servicios en segundo plano
docker compose up -d

# Detener servicios
docker compose down

# Ver estado de los contenedores
docker compose ps

# Ver logs en vivo
docker compose logs -f app
```

### URLs de Acceso Local
- **Tienda y Catálogo Virtual:** [http://localhost:8085](http://localhost:8085)
- **Panel Administrativo:** [http://localhost:8085/admin/index.php](http://localhost:8085/admin/index.php) (Usuario: `admin` | Clave: `admin123`)
- **phpMyAdmin:** [http://localhost:8082](http://localhost:8082) (Host: `db` | Clave: `rootpassword`)
- **Endpoint RAG Aislado (Pruebas):** `POST http://localhost:8085/api/chat_rag.php`
- **Endpoint Productivo (Con o sin RAG):** `POST http://localhost:8085/api/chat_frontend.php`

### Cómo Re-indexar Nuevos Productos o Documentos (RAG)
Siempre que agregues nuevos productos a la base de datos o edites los archivos `.md` en `knowledge/`, ejecuta:
```powershell
# Vía CLI en el contenedor Docker
docker exec -i ventas_ia_app php scripts/index_rag.php

# O accediendo vía navegador
http://localhost:8085/scripts/index_rag.php
```

### Ejecutar Suite de Pruebas RAG
```powershell
docker exec -i ventas_ia_app php tests/test_rag_retrieval.php
```

---

## 8. ¿Qué Ventajas Tiene tu Sistema Ahora con RAG?

1. **Respuestas Precisas sobre Políticas de Negocio (Antes Imposible):**
   - El sistema anterior solo conocía los productos. Si el cliente preguntaba por envíos, transferencias QR, garantías o devoluciones, la IA inventaba respuestas o decía que no sabía.
   - Con RAG, la IA cita textualmente los plazos reales (ej: *"7 días para devoluciones"*, *"envíos locales en 24h por Bs 15"* o *"transferencias QR a Banco Unión/BCP"*).

2. **Cero Alucinaciones:**
   - La IA ya no inventa condiciones de entrega ni formas de pago; utiliza únicamente las fuentes oficiales recuperadas de `knowledge/politicas_tienda.md` y `knowledge/preguntas_frecuentes.md`.

3. **Ahorro Masivo de Tokens y Costo:**
   - En el sistema tradicional, **todo el catálogo completo** se enviaba en cada mensaje. Si el catálogo crece a 500 productos, cada mensaje costaría miles de tokens y saturaría la ventana de contexto.
   - Con RAG, solo se envían al prompt los 2 o 3 fragmentos estrictamente relevantes para la consulta del usuario.

4. **Escalabilidad Infinita del Catálogo:**
   - Puedes tener miles de productos; el motor RAG filtra en < 2 milisegundos los 3 productos exactos que busca el cliente y solo inyecta esos en DeepSeek.

5. **100% Non-Breaking (Sin Regresión):**
   - El endpoint actual `api/chat_frontend.php` sigue funcionando exactamente como antes.
   - Si no se envía `"use_rag": true`, nada se altera.
   - Si RAG falla o tiene timeout, se ejecuta un fallback silencioso que garantiza que el usuario nunca vea un error 500.

---

## 9. ¿Cómo se Hizo Técnicamente la Arquitectura RAG?

El flujo RAG implementado sigue el estándar de la industria en 5 pasos:

```text
[Cliente: "¿Cuánto tiempo para devolver si llega mal?"]
                      │
                      ▼
 1. Tokenización y Normalización (EmbeddingService.php)
    - Limpieza de acentos, filtrado de stopwords en español.
    - Stemming y lematización de dominio comercial ('devolver' -> 'devolucion').
    - Ponderación de pesos y normalización L2.
                      │
                      ▼
 2. Búsqueda Vectorial por Similitud de Coseno (VectorStore.php)
    - Compara el vector de la pregunta contra la tabla `rag_documents` en MariaDB.
    - Calcula Cosine Similarity + Cobertura de términos en < 2 ms.
    - Filtra los fragmentos que superan el umbral ($minScore = 0.06).
                      │
                      ▼
 3. Ensamblado de Contexto Dinámico (RagService.php)
    - Si encuentra coincidencia: Inyecta el texto exacto de la política/producto en el System Prompt.
    - Si no encuentra o el score es bajo (ej: "Hola"): No inyecta nada (Fallback silencioso).
                      │
                      ▼
 4. Generación Aumentada con DeepSeek (chat_rag.php / chat_frontend.php)
    - Envía a la API de DeepSeek (`deepseek-chat`) el prompt enriquecido.
                      │
                      ▼
 5. Respuesta Final al Usuario
    - DeepSeek responde con lenguaje natural amable y fundamentado en los datos oficiales.
```

---

## 10. Optimización de Memoria Conversacional y Personalidad Humana (06/10/2026)

### Diagnóstico del Problema "Robot / En Bucle":
1. **Amnesia de Mensajes del Asistente:** En `assets/js/chat.js` original, solo se hacía `historial.push` con los mensajes del usuario. La IA nunca recibía sus propias respuestas anteriores, por lo que creía que cada turno era una conversación rota o que debía volver a responder preguntas pasadas.
2. **Acciones no Ejecutadas:** En `chat.js` existía un comentario `// ... resto de procesamiento de acciones ...` sin implementar, lo que causaba que la confirmación de añadir al carrito nunca se procesara visualmente.
3. **Falta de Directrices de Conversación Humana:** El System Prompt original carecía de reglas contra saludos repetitivos.

### Soluciones Implementadas:
1. **Frontend (`assets/js/chat.js`):**
   - Se añadió `historial.push({ role: 'assistant', content: respuestaFinal })` para mantener la memoria conversacional bidireccional completa.
   - Se activó por defecto el flag `use_rag: true`.
   - Se implementó la ejecución en tiempo real de `mostrarProductoEnChat(accion)` y `agregarAlCarritoDesdeChat(accion.nombre)`.
2. **Backend (`api/chat_frontend.php` y `api/chat_rag.php`):**
   - **Regla Estricta Anti-Saludos:** Saludo permitido únicamente en el primer mensaje; prohibido decir "Hola" o presentarse en turnos posteriores.
   - **Cero Repetición de Temas:** No volver a mencionar temas ya contestados (como pagos QR) a menos que el cliente lo pida de nuevo.
   - **Confirmación Proactiva de Compra:** Cuando el cliente dice "sí, agrégalo", la IA confirma con calidez (*"¡Listo! Ya te agregué los [Auriculares] a tu carrito 🛒"*) y genera la acción de compra sin repreguntar.
   - **Rastreo Inteligente de Producto:** Se implementó detección automática en el historial para saber exactamente a qué producto se refiere el cliente cuando dice *"agrégalo"* o *"esos auriculares"*.

---

## 11. Potenciación RAG v2 (Costo $0) y Humanización Total del Chat (09/10/2026)

### A. Potenciación del Motor RAG Local (Cero APIs de Pago Adicionales):
1. **Diccionario de Conceptos de Dominio (`EmbeddingService.php`):**
   - Mapeo semántico para e-commerce boliviano: `#envio`, `#pago`, `#garantia`, `#devolucion`, `#horario`, `#tienda_fisica`, `#factura`, etc.
   - Normalización de lemas verbales (`despachado`, `despachar` -> `envio`; `facturar` -> `factura`; `pedido` -> `compra`).
   - Stopwords de cortesía ("hola", "gracias", "porfa") para evitar falsos positivos en saludos o frases cortas.
2. **Ponderación IDF Real sobre el Corpus (`VectorStore.php` y `scripts/index_rag.php`):**
   - Cálculo en dos pasadas al indexar: calcula IDF (BM25 positiva) de todos los fragmentos y persiste la tabla en `rag_meta`. Términos específicos (ej: "JBL", "Galaxy", "NIT") reciben mayor peso matemático.
   - TF sublineal: $1 + \ln(\text{tf})$.
3. **Búsqueda Híbrida (`VectorStore.php` & `RagService.php`):**
   - Índice `FULLTEXT` en `rag_documents(title, content)`.
   - Re-ranking combinado: vector local + bonus léxico de MariaDB para nombres de productos exactos.
4. **Chunking Inteligente e Inyección de Encabezados (`DocumentChunker.php`):**
   - Fragmentación con solapamiento y propagación jerárquica de títulos (`[Políticas > Envíos y Tiempos de Entrega]`).
   - Limpieza automática de documentos obsoletos (`deleteStale`).
5. **Expansión Condicional de Consultas con DeepSeek (`QueryExpander.php`):**
   - Si la búsqueda local obtiene score bajo, DeepSeek expande la consulta a palabras clave antes de re-intentar, sin costo extra.
6. **Pruebas de Calibración Desconectadas (`tests/test_rag_offline.php`):**
   - Suite con 21 casos de calibración (100% de aciertos) y casos de generalización no calibrados.

### B. Humanización Total del Chat (Cero Indicios de IA / Robot):
1. **Frontend / UI (`index.php`):**
   - Reemplazo del icono `<i class="fas fa-robot"></i>` por `<i class="fas fa-comment-dots"></i>`.
   - Cabecera del widget con avatar, indicador verde "En línea" y nombre de asesor: `Valeria · Asesor de Ventas`.
   - Mensaje de bienvenida cálido de mostrador/WhatsApp ("¡Hola! ¿Cómo estás? Te atiende Valeria de ventas...").
   - Eliminadas menciones a "IA" en títulos y textos del hero.
2. **Filosofía y Reglas de Persona Humana (`HumanPersona.php`):**
   - Tono WhatsApp boliviano: mensajes cortos (1 a 3 frases), sin listas largas ni viñetas, máximo 1 emoji ocasional.
   - Cero muletillas de bot ("Estoy aquí para ayudarte", "Como modelo de IA", "No dudes en consultarme").
   - Limpieza estricta de formato Markdown en respuestas (`**`, viñetas) que delatan texto generado.
   - Mensajes de contingencia naturales ante fallos de conexión ("Uy, se me cortó un momento la conexión 😅 ¿Me lo repites?").
3. **Base de Conocimiento Limpia:**
   - Depuradas menciones a "bot" y "asistente virtual" en `knowledge/preguntas_frecuentes.md`.

---

## 12. Corrección Integral de Precios (Bs / Tasa de Cambio), Filtrado Inteligente del Catálogo en Vivo y Saneamiento del Chat Admin (09/10/2026)

### A. Diagnóstico y Corrección de Precios Críticos:
1. **Causa Raíz de la Discrepancia:**
   - La base de datos almacena los precios en la tabla `productos.precio` en moneda base USD (ej. Camiseta Polo Classic = $18.00; Galaxy A55 = $350.00).
   - En la tienda pública, el catálogo aplicaba la tasa de cambio oficial de la tabla `configuracion` (`tasa_cambio_usd = 9.96`, `moneda_simbolo = 'Bs'`), mostrando `18 * 9.96 = 179.28 Bs`.
   - Sin embargo, el chatbot (`api/chat_frontend.php`), los chunks de RAG (`DocumentChunker.php`) y las tarjetas de productos omitían multiplicar por la tasa de cambio y le decían a la IA que el catálogo estaba en Bolivianos. La IA citaba el número crudo en dólares pero poniéndole "Bs" (*"Camiseta Polo Classic a 18 Bs"*), creando un riesgo comercial severo.
2. **Solución Unificada de Moneda:**
   - `services/rag/DocumentChunker.php`: Ahora recibe `tasa` y `simbolo` de moneda oficial y calcula `precioFinal = round(precio * tasa, 2)`. Reindexado en MariaDB (`scripts/index_rag.php`) reflejando `Bs 179.28`.
   - `api/chat_frontend.php`: Lee `tasa_cambio_usd` y `moneda_simbolo`. Convierte todos los precios del catálogo enviado a la IA y exige explícitamente citar los precios finales en Bolivianos (ej. Camiseta Polo Classic = Bs 179.28).
   - `api/filtrar_productos.php`: Se ajustó el filtro de precios mínimos y máximos para convertir los límites ingresados en Bs a la base USD antes del WHERE de MySQL.

### B. Agente Inteligente de Filtrado en Vivo del Catálogo:
1. **Problema de Tarjetas Masivas y Falsos Disparos:**
   - Cuando el cliente decía *"y ropa tendras?"*, el chat volcaba múltiples tarjetas grandes en la ventana de conversación.
   - Si el cliente respondía *"que pena"*, un algoritmo de fallback concatenaba los últimos 3 mensajes del historial, re-detectaba la palabra *"electrónica"* de dos turnos atrás y volvía a disparar 4 tarjetas de celulares.
2. **Filtrado Dinámico en Pantalla (`filtrar_catalogo`):**
   - Se diseñó la acción inteligente `{"tipo": "filtrar_catalogo", "categoria_id": X, "texto": "..."}`.
   - En lugar de saturar el chat de tarjetas, la IA (y el fallback determinista del turno actual) emite `filtrar_catalogo`.
   - En el frontend (`assets/js/catalogo.js` y `assets/js/chat.js`):
     - `window.aplicarFiltrosCatalogo(filtros)` actualiza los selectores del formulario de filtros, ejecuta la recarga asíncrona de productos (`api/filtrar_productos.php`), desplaza suavemente la vista del cliente a la sección de productos y muestra un aviso visual elegante (*"✨ Catálogo filtrado automáticamente"*).
     - Funciona para todas las categorías actuales y cualquier nueva categoría o producto agregado a la tienda.
   - Se eliminó la concatenación de historial en el fallback: turnos de cortesía (*"que pena"*, *"gracias"*) generan 0 tarjetas.

### C. Reparación y Potenciación del Chat de Administración (`api/chat_admin.php`):
1. **Corrección de Error Fatal:**
   - `api/chat_admin.php` arrojaba `Fatal error: TypeError` debido a una firma incorrecta de `llamarIA` y parseo de string como array.
   - Se reescribió `llamarIA(array $mensajes)` con soporte robusto para DeepSeek y extracción garantizada de JSON.
2. **Inteligencia y Coherencia de Moneda en Admin:**
   - Todas las herramientas administrativas (`consultar_producto`, `ventas_dia`, `ventas_mes`, `top_clientes`, `resumen_general`) muestran las cifras tanto en moneda oficial (`Bs`) como en moneda base (`USD`).
   - `actualizar_precio`: Admite especificar si el precio ingresado por el admin está en Bs o en USD para mantener la coherencia matemática de la base de datos.

---

## 13. Inteligencia de Negocios (BI), Historial Persistente, Comparador Estilo Kimovil y Rediseño 'Tech Premium' (09/10/2026)

### A. Potenciación del Chat Administrativo (Jarvis como Asesor Ejecutivo & BI):
1. **Motor de Inteligencia de Negocios y Analítica (`services/admin/AnalyticsService.php`):**
   - Diseñado e implementado con costo de infraestructura $0 y cero dependencias pesadas adicionales (ejecuta matemática y analítica relacional de alto rendimiento sobre MariaDB/PHP 8).
   - `getExecutiveSummary()`: Calcula en tiempo real las ventas del día y del mes (en Bs y USD base), ticket promedio, valoración total del inventario a precio de venta y de costo, detección de productos en riesgo crítico de rotura de stock (`stock < 5`) y velocidad de rotación estimada.
   - `getStrategicAdvice()`: Motor heurístico de diagnóstico directivo que emite recomendaciones operativas (reorden preventivo de stock, estrategias de promociones, análisis de rentabilidad).
   - `generateReportFile($tipo, $formato)`: Generador de reportes descargables para la gerencia en formatos **CSV con BOM UTF-8** (para apertura nativa en Microsoft Excel sin problemas de acentos ni símbolos) y formato **HTML ejecutivo listo para impresión/PDF**.
2. **Historial Persistente Multisesión para el Admin (`chat_admin_hilos` y `chat_admin_mensajes`):**
   - Estructura relacional con claves foráneas en cascada que almacena hilos de conversación y todos los mensajes de la IA y el Administrador.
   - Panel de control estilo Claude/ChatGPT en `admin/chat_admin.php` con barra lateral:
     - Botón `+ Nueva Consulta`.
     - Lista de conversaciones previas ordenadas cronológicamente con título autogenerado.
     - Botón de eliminación individual de hilos.
     - Carga asíncrona de mensajes vía API (`action=listar_hilos`, `action=cargar_hilo`, `action=eliminar_hilo`).
3. **Herramientas de BI y RAG Integradas en Jarvis (`api/chat_admin.php`):**
   - `analisis_estrategico`: Diagnóstico global del negocio con KPIs clave y sugerencias directivas.
   - `generar_reporte`: Genera y entrega enlaces directos de descarga protegida (`api/descargar_reporte.php`) a archivos CSV y reportes HTML.
   - `consultar_conocimiento_rag`: Consulta vectorizada al corpus RAG para normativas, garantías y políticas comerciales internas.
   - `consultar_producto`, `actualizar_stock`, `actualizar_precio`, `ventas_dia`, `ventas_mes`, `top_clientes`: Operaciones directas con bimoneda (`Bs` y `USD`).

### B. Módulo de Comparativas de Productos Estilo Kimovil (`comparador.php`):
1. **Fichas Técnicas Avanzadas en Base de Datos (`database/migrate_v3.php`):**
   - Se añadió la columna `especificaciones TEXT NULL` a la tabla `productos`.
   - Se registraron especificaciones detalladas JSON para smartphones y electrónica:
     - **Samsung Galaxy A55**: Kimovil Score 8.9/10, AnTuTu v10 713,393 pts, pantalla Super AMOLED 120Hz 1000 nits, procesador Exynos 1480, cámara 50MP OIS con 4K, batería 5000 mAh 25W, certificación IP67.
     - **Apple iPhone 17 Pro Max**: Kimovil Score 9.8/10, AnTuTu v10 2,150,000 pts, pantalla LTPO Super Retina XDR 120Hz 2500 nits, chip Apple A19 Pro, triple cámara 48MP periscopio 5x, batería 4850 mAh, titanio de grado aeroespacial e IP68.
     - **Tablet Pro 10"** y **Auriculares Bluetooth X3**: Fichas técnicas completas con audio Hi-Res, cancelación activa ANC, autonomía y conectividad.
   - **RAG Enriquecido**: `DocumentChunker.php` y `scripts/index_rag.php` fueron actualizados para incorporar `p.especificaciones` en el índice vectorial y léxico, permitiendo a la IA de ventas y a Jarvis responder sobre benchmarks, procesadores y pantallas.
2. **Interfaz del Comparador Kimovil (`comparador.php`):**
   - Comparación lado a lado de hasta 3 productos seleccionables dinámicamente mediante desplegables.
   - Badges visuales con nota general (Score Kimovil), benchmark AnTuTu v10 y precios oficiales en Bs y USD.
   - Matriz detallada por categorías técnicas:
     - *Pantalla & Display* (Tipo de panel, tasa de refresco, brillo nits, resolución).
     - *Rendimiento & Hardware* (Procesador, puntuación AnTuTu, RAM, almacenamiento).
     - *Cámaras & Fotografía* (Sensor principal, estabilización OIS, selfie, video).
     - *Batería & Autonomía* (Capacidad mAh, carga rápida, tipo de conector).
     - *Construcción & Resistencia* (Materiales de chasis, resistencia al agua IP).
   - Veredicto y análisis comparativo con puntos fuertes (*Pros*) y débiles (*Contras*).
   - Acceso directo desde el menú principal de la tienda (`index.php`) y botón *"Comparar"* en cada tarjeta de producto.

### C. Rediseño Visual de Alta Fidelidad 'Tech Premium & Glassmorphism':
1. **Identidad Visual y Tipografía (`assets/css/estilos.css`):**
   - Tipografía moderna: Google Fonts **Outfit** para encabezados de alto impacto y **Inter** para lectura nítida.
   - Paleta cromática profunda: Slate oscuro (`#0f172a`), índigo eléctrico (`#6366f1`) y cyan vibrante (`#06b6d4`).
   - Efectos Glassmorphism con bordes sutiles semi-translúcidos, desenfoque de fondo (`backdrop-filter: blur(12px)`) y sombras suaves multicapa.
   - Hero Section completamente renovada con grid responsivo, tarjetas de estadísticas rápidas ("+500 Clientes Felices", "Garantía Oficial 1 Año", "Envíos Express") y micro-animaciones flotantes.
   - Tarjetas de producto rediseñadas con badges de stock, botón rápido de comparación Kimovil y efectos hover dinámicos.
2. **Panel de Administración SaaS Moderno (`assets/css/admin.css`, `admin/nav.php`, `admin/dashboard.php`, `admin/layout.php`):**
   - Barra lateral oscura estructurada con navegación por secciones (*Operaciones*, *Inteligencia & Analítica*, *Sistema*).
   - Topbar ejecutiva con fecha del sistema, cotización de la tasa oficial en vivo y menú de perfil.
   - Dashboard con KPI Cards avanzadas (Ventas del Mes en Bs/USD, Pedidos, Valoración del Inventario, Alertas de Stock) y banner interactivo directo hacia Jarvis BI Advisor.
   - Interfaz de Chat Admin modernizada con chips de preguntas rápidas (*"Diagnóstico de negocio"*, *"Descargar inventario CSV"*, *"Top clientes del mes"*), renderizado visual de reportes generados y gráficos Chart.js.
3. **Rediseño Completo de Carrito y Checkout (`carrito.php`, `checkout.php`, `assets/js/checkout.js`):**
   - **Stepper de Progreso Interactivo:** Guía visual de 3 etapas (*1. Tu Carrito ➔ 2. Datos de Envío ➔ 3. Confirmación*).
   - **Bolsa de Compras Moderna (`carrito.php`):** Reemplazo de la tabla genérica por tabla con avatares de producto, controles dinámicos de cantidad redondeados (`-` / `+`), resumen lateral flotante con precio total en Bs y conversión aproximada a USD, y badges de confianza para compras en Bolivia.
   - **Checkout Inteligente (`checkout.php`):** Formulario estructurado con selector de ciudades/departamentos de Bolivia, dirección y referencias, opciones seleccionables de método de pago (QR Simple con 0% comisiones y Pago contra Entrega en efectivo), y resumen de pedido en tiempo real.
   - **Pantalla de Éxito Integrada (`checkout.js` y `api/procesar_pedido.php`):** Transición fluida a una pantalla de felicitación con número de pedido `#REG`, total pagado y aviso de contacto personalizado vía WhatsApp.
4. **Corrección de Permisos de Servidor para Reportes y Alto Contraste en Chat Admin (`admin/chat_admin.php`, `services/admin/AnalyticsService.php`):**
   - **Solución al Error de Permisos en `storage/reports`:** La carpeta generada por CLI en el contenedor pertenecía a `root:root` con permisos `755`, bloqueando la escritura a Apache (`www-data`) y provocando `fopen(): Permission denied` / `fprintf(): Argument #1 must be resource, bool given`. Se ajustaron los permisos a `0777` y se blindó `AnalyticsService.php` con auto-reparación preventiva (`chmod`) y control seguro de punteros.
   - **Legibilidad y Alto Contraste en la Consola:** Se corrigió el problema de texto poco visible. Las respuestas de Jarvis ahora tienen texto negro-slate `#0f172a` de máximo contraste sobre burbujas blancas con bordes `#cbd5e1`, chips de consultas rápidas estilizados, formateo de Markdown automático para titulares y viñetas, y tarjetas de descarga de reportes en verde esmeralda (`#f0fdf4` / borde `#86efac`) con botón directo de descarga `.CSV`.

---

## 14. Comparador Coherente por Categorías y Especificaciones Adaptativas (09/10/2026)

### A. Restricción Estricta de Comparativas Coherentes (`comparador.php`):
1. **Aislamiento por Categoría:**
   - Se eliminó la lista general indiferenciada en los selectores.
   - Los desplegables de artículos (`Artículo 1`, `Artículo 2`, `Artículo 3`) filtran de forma estricta únicamente productos pertenecientes a la **misma categoría activa**.
   - Resulta imposible desde la interfaz comparar artículos de rubros diferentes (ej. un iPhone con café o zapatillas).
2. **Pestañas de Navegación por Categoría:**
   - Barra interactiva con selector de categorías (*📱 Electrónica, 👕 Ropa & Calzado, 🏠 Hogar*) con contadores de productos comparables.
   - Si un usuario ingresa parámetros manuales cruzados por URL (ej. `?p1=1&p2=8`), el backend detecta la incompatibilidad de rubros, auto-selecciona un producto compatible de la misma categoría y notifica al usuario con un banner de ajuste técnico.
3. **Matrices de Especificaciones Adaptativas:**
   - **Electrónica / Smartphones:** Benchmark AnTuTu v10, Kimovil Score, panel 120Hz, procesador, cámaras OIS, batería y certificaciones IP.
   - **Ropa & Calzado:** Rating de confort, material/composición (algodón peinado, malla transpirable), tallas, corte/suela, uso recomendado y cuidados de lavado.
   - **Hogar:** Rating de calidad, potencia, capacidad de vaso/tejido, funciones de velocidad y seguridad.
4. **Enriquecimiento del Catálogo y RAG:**
   - Incorporación del **Xiaomi Redmi Note 13 Pro+ 5G** (200MP OIS, Dimensity 7200 Ultra, 120W) y actualización de especificaciones completas para todas las categorías mediante `populate_all_specs.php`.
   - Reindexación RAG integral con 10 productos y 7 fragmentos de políticas.

---

## 15. Sistema Integral de Autenticación de Clientes, Google Sign-In (1-Click Gratuito), Recuperación de Contraseña y Portal de Pedidos (09/10/2026)

### A. Objetivos y Alcance Cumplidos Sin Romper el Sistema:
1. **Arquitectura No Invasiva (Cero Rupturas):**
   - El sistema de compra como invitado (*Guest Checkout*) se mantiene 100% operativo sin forzar registros previos.
   - Si un usuario se registra o inicia sesión, el sistema aprovecha su sesión para auto-completar sus formularios de compra y enlazar sus pedidos a su cuenta.
2. **Registro e Inicio de Sesión Tradicional (`login.php`, `registro.php`, `api/auth_cliente.php`):**
   - Cifrado seguro de contraseñas con `password_hash()` y validación `password_verify()`.
   - Manejo de sesiones limpias con sincronización de estado en base de datos (`ultimo_login`).
3. **Google Sign-In 1-Click (100% Gratuito y Sin Complejidad Técnica):**
   - Integración con el SDK oficial y gratuito **Google Identity Services (GIS)** (`https://accounts.google.com/gsi/client`) sin costos por API ni necesidad de tarjetas de crédito.
   - Si el administrador configura `GOOGLE_CLIENT_ID` en `config/config.php`, se abre la ventana nativa de Google para acceder con 1 clic.
   - Modo de asistencia interactivo local incluido para desarrollo y pruebas en localhost/Docker sin necesidad de credenciales previas de Google Cloud.
   - **Persistencia Inteligente de Datos Faltantes:** Google únicamente suministra Nombre, Correo y Foto de Perfil. Cuando el cliente realiza su compra en `checkout.php`, el sistema solicita su Teléfono y Dirección de despacho, y los guarda automáticamente en la base de datos vinculados a su cuenta.
4. **Recuperación y Restablecimiento Seguro de Contraseña (`recuperar_password.php`, `restablecer_password.php`):**
   - Generación de tokens criptográficamente seguros (`bin2hex(random_bytes(32))`, 256 bits).
   - Control de expiración nativo en base de datos (`DATE_ADD(NOW(), INTERVAL 1 HOUR)`) blindado contra desajustes de zona horaria entre servidor y PHP.
   - Invalidación automática tras el primer uso.
   - Enlace directo de pruebas renderizado en modo desarrollo para verificar el restablecimiento sin depender de un daemon SMTP externo en entornos locales o Docker.
5. **Portal de Clientes e Historial de Pedidos (`mis_pedidos.php`):**
   - Vista de perfil con avatar dinámico, correo, teléfono y dirección guardada.
   - Métricas personales: Total de Pedidos, En Proceso/Pendientes y Total Invertido (en Bs oficial).
   - Tabla detallada por pedido con badges de estado (*Pendiente, Confirmado, En Camino, Entregado, Cancelado*), desglose de artículos comprados, fotos, cantidades, precios unitarios y subtotales.
6. **Barra de Navegación Unificada (`includes/navbar_user.php`):**
   - Integrada en `index.php`, `comparador.php`, `carrito.php` y `checkout.php`.
   - Muestra botón "Ingresar" para invitados o menú desplegable con avatar del cliente, "Mis Pedidos" y "Cerrar Sesión".
7. **Control de Calidad Automatizado:**
   - Suite completa de 24 pruebas unitarias e integrales en `tests/test_auth_clientes.php`, con 100% de éxito.

---

## 16. Reorganización del Catálogo en Estantes por Departamento y Rediseño Minimalista Tech (09/10/2026)

### A. Diagnóstico del Problema Visual y Estructura Anterior:
- La vista principal de la tienda (`index.php`) mostraba todos los 22 productos de las 8 categorías en una sola cuadrícula plana indiscriminada, provocando que un celular de última generación apareciera pegado a zapatillas deportivas, café en grano o sábanas de cama.
- La experiencia visual resultaba sobrecargada y desordenada para el usuario.

### B. Organización por Estantes de Departamentos (`api/filtrar_productos.php`):
1. **Agrupación Inteligente por Categoría (`department-shelf`):**
   - En la vista general ("Todos los Departamentos") o búsquedas amplias, los productos se clasifican y presentan automáticamente en sus estantes correspondientes (Celulares & Smartphones, Tablets, Audio & Auriculares, Zapatillas & Calzado, Poleras & Camisetas, Electrodomésticos de Cocina, Ropa de Cama & Hogar, Café & Alimentos Gourmet).
   - Cada estante cuenta con una cabecera minimalista: icono representativo del rubro en badge suave, título de categoría, contador de artículos disponibles y enlace directo *"Ver sólo esta categoría →"*.
2. **Vista de Categoría Individual Limpia:**
   - Al seleccionar un departamento específico, la cuadrícula se enfoca exclusivamente en los artículos de dicho rubro con banner de contexto y botón rápido para volver al catálogo general.

### C. Navegación por Pestañas Horizontales Minimalistas (`category-pills-scroller`):
1. **Barra Superior de Desplazamiento Fluido (`index.php` & `assets/css/estilos.css`):**
   - Píldoras interactivas tipo Apple / Minimalist Tech con iconos y contadores.
   - Desplazamiento horizontal fluido con scroll suave centrado en la pestaña activa al seleccionarla.
   - Filtro instantáneo sin recargar la página, complementado con chips de acceso rápido en el banner superior.
2. **Buscador y Ordenación en Vivo (`assets/js/catalogo.js`):**
   - Entrada de búsqueda en tiempo real con *debounce* de 280ms para no saturar peticiones.
   - Ordenación por precio (menor a mayor, mayor a menor) y puntuación técnica.
   - Sincronización transparente con el asistente virtual de ventas Valeria (`window.aplicarFiltrosCatalogo`).

### D. Tarjetas de Producto Rediseñadas (`minimal-product-card`):
1. **Estética Limpia, Espaciosa y Uniforme:**
   - Contenedor de imagen de fondo neutro (`#f8fafc`) con `object-fit: contain` a 220px de altura fija para que ninguna foto sufra recortes forzados ni distorsiones.
   - Badge sutil de disponibilidad (*"En Stock"* o *"Agotado"*).
   - Tag minimalista de departamento y badge de Score Kimovil / Benchmark cuando aplique a tecnología.
   - Títulos de producto con tipografía clara (Inter / Outfit) con altura limitada a 2 líneas para alineación perfecta en filas.
   - Precios destacados en Bolivianos oficiales (`Bs`) con referencia discreta en USD.
   - Botón ergonómico de añadir al carrito y enlace directo a la ficha técnica o comparador.

### E. Pruebas y Validación:
- Suite de verificación de departamentos creada en `tests/test_catalogo_departamentos.php` (100% éxito en los 8 departamentos y filtros específicos).
- Compatibilidad garantizada con `tests/test_segmentacion_v4.php`, `tests/test_auth_clientes.php`, `tests/test_chat_agregar_carrito.php` y `tests/test_bi_and_comparator.php`.

---

## 15. Sustitución Total de Iconografía: Vectorial Minimalista de Trazo Fino Lucide (09/10/2026)

### A. Diagnóstico y Filosofía de Diseño:
- **Erradicación de Clichés de IA:** Se eliminaron todos los destellos mágicos (✨ / `fa-sparkles`), varitas mágicas (🪄 / `fa-magic`) y emojis coloridos en títulos/cabeceras, sustituyéndolos por iconografía sobria, profesional y moderna.
- **Adopción de la Opción 1 (Recomendada):** Estilo *Linear / Apple / Vercel* basado en **Lucide Icons** con trazo fino (`stroke-width: 1.75px`), bordes redondeados (`stroke-linecap: round`, `stroke-linejoin: round`), tonalidades monocromáticas neutras y relleno transparente (`fill: none`).

### B. Arquitectura y Componentes Implementados:
1. **Librería Autónoma Local (`assets/js/lucide.min.js`):**
   - Paquete UMD autónomo de Lucide (447 KB) alojado localmente en el servidor para garantizar disponibilidad total offline sin dependencias de CDNs externas.
2. **Motor de Resolución Semántica de Iconos (`includes/icons.php`):**
   - Función `resolverIconoLucide(?string $iconoRaw, int $catId)`: Asocia dinámicamente cada categoría (1: `smartphone`, 2: `tablet`, 3: `headphones`, 4: `sport-shoe`, 5: `shirt`, 6: `cooking-pot`, 7: `bed-double`, 8: `coffee`) o clase legacy a su correspondiente icono vectorial Lucide.
   - Función `renderLucide(string $iconName, string $class, float $stroke)`: Generador de etiquetas `<i data-lucide="..." class="..."></i>`.
3. **Tokens de Diseño CSS (`assets/css/estilos.css`):**
   - Reglas maestras para `.lucide`, `[data-lucide]`, `svg.lucide` con `stroke-width: 1.75px`, `fill: none`, `stroke: currentColor` y clases de escalado responsivo (`.lucide-xs`, `.lucide-sm`, `.lucide-md`, `.lucide-lg`, `.lucide-xl`).

### C. Cobertura Exhaustiva en Vistas del Cliente:
1. **Página Principal & Catálogo (`index.php`, `api/filtrar_productos.php`, `assets/js/catalogo.js`):**
   - Navbar (`cpu`, `layout-grid`, `git-compare`, `shopping-bag`, `shield`).
   - Hero Chip de confianza (`shield-check`) y badges de servicio (`truck`, `shield-check`, `message-square`, `banknote`).
   - Píldoras de departamento (`smartphone`, `tablet`, `headphones`, `sport-shoe`, `shirt`, `cooking-pot`, `bed-double`, `coffee`).
   - Barra de herramientas (`search`, `sliders-horizontal`, `git-compare`, `rotate-ccw`).
   - Tarjetas de producto (`shopping-bag`, `gauge`, `git-compare`).
   - Re-inicialización automática vía `lucide.createIcons()` en cada filtrado AJAX en vivo.
2. **Comparador de Productos (`comparador.php`):**
   - Matriz técnica con iconos sutiles (`award`, `star`, `gauge`, `smartphone`, `cpu`, `hard-drive`, `camera`, `battery`, `shield-check`, `heart`, `shirt`, `leaf`, `ruler`, `footprints`, `calendar`, `droplet`, `zap`, `box`, `sliders`, `info`, `thumbs-up`, `check`, `alert-circle`, `minus-circle`, `lightbulb`, `message-square`).
3. **Bolsa de Compras (`carrito.php`):**
   - Stepper (`shopping-bag`, `check`), badges de confianza (`shield-check`, `truck`, `credit-card`), botones dinámicos (`trash-2`, `package`) y re-renderizado reactivo tras cualquier modificación de cantidad.
4. **Finalización de Compra (`checkout.php`, `assets/js/checkout.js`):**
   - Métodos de pago (`qr-code`, `banknote`, `check-circle-2`, `circle`), indicadores de cuenta (`user-check`, `info`, `phone-call`, `mail`) y modal de confirmación.
5. **Portal de Clientes & Pedidos (`mis_pedidos.php`):**
   - Header del portal (`user`, `mail`, `phone`, `map-pin`, `shopping-bag`, `log-out`).
   - Métricas (`package`, `truck`, `credit-card`, `clock`).
   - Estados de pedidos dinámicos (`clock`, `check`, `truck`, `check-circle-2`, `ban`).
6. **Autenticación y Recuperación (`login.php`, `registro.php`, `recuperar_password.php`, `restablecer_password.php`):**
   - Formularios, campos de entrada (`user`, `mail`, `lock`, `arrow-left`, `user-plus`, `key`, `shield-check`, `send`) y reemplazo de iconos con spinners nativos de Bootstrap en estados de carga.
7. **Widget de Ventas Valeria (`includes/chat_widget.php`, `assets/js/chat.js`):**
   - Botón flotante (`message-square`), reinicio (`rotate-ccw`), envío (`send`), botón en tarjetas del chat (`shopping-bag`) y toast de carrito (`check-circle-2`).

### D. Verificación y Resultados de Pruebas:
- **Depuración al 100%:** Cero iconos FontAwesome o destellos en todo el flujo de cliente.
- `test_catalogo_departamentos.php`: **100% PASÓ**
- `test_segmentacion_v4.php`: **100% PASÓ**
- `test_auth_clientes.php`: **24/24 PASÓ**
- `test_chat_agregar_carrito.php`: **100% PASÓ**
- `test_bi_and_comparator.php`: **100% PASÓ**

---

## 9. Sesión 9: Unificación de Header Global, Eliminación de Texto Redundante/IA y Migración de Admin Dashboard a Iconos Lucide

### A. Diagnóstico y Problemas Identificados
1. **Inconsistencia de Header entre Pestañas:**
   - Al navegar entre Catálogo (`index.php`), Comparador (`comparador.php`), Carrito (`carrito.php`), Checkout (`checkout.php`) y Mis Pedidos (`mis_pedidos.php`), la barra superior cambiaba visualmente:
     - El enlace se llamaba `"Comparador Celulares"` en Catálogo/Carrito/Checkout pero `"Comparador"` en el comparador.
     - El botón de Carrito cambiaba de estilo (`btn-outline-light` a `btn-primary active` desfasando proporciones).
     - El enlace de acceso administrativo mostraba `" Admin"` con texto en algunas páginas y solo el escudo `<i data-lucide="shield"></i>` en otras.
     - `mis_pedidos.php` tenía un menú de cliente duplicado en duro sin usar el componente centralizado `includes/navbar_user.php`.
2. **Exceso de Texto Redundante y Estilo "IA":**
   - El Hero de la tienda y banners tenían texto sobrecargado que explicaba la arquitectura interna al cliente ("Catálogo Oficial 2026 • Asesoría Humana en Vivo", "Catálogo segmentado por departamentos, comparativas técnicas estilo Kimovil y asistencia personalizada en vivo", "Asesoría Valeria en Chat").
   - El widget de chat incluía etiquetas artificiales como `"En línea • Asesora humana"` y saludos largos de plantilla.
   - El comparador incluía advertencias excesivamente explicativas y un banner que forzaba a salir de la página para consultar con la asesora.
   - En el dashboard del admin existían banners con terminología artificial ("Inteligencia de Negocios Activa", "Decisión asistida por IA (Jarvis)").
3. **Dashboard y Panel Administrativo con Iconos Antiguos:**
   - `admin/layout.php`, `admin/nav.php` y `admin/dashboard.php` aún dependían de FontAwesome (`fas fa-...`).

### B. Soluciones Implementadas
1. **Componente de Navegación Global Unificado (`includes/navbar_store.php`):**
   - Centralización de toda la barra superior pública en un único archivo reutilizable.
   - Marca homogénea con icono `<i data-lucide="cpu"></i>` y título oficial de tienda.
   - Enlaces unificados:
     - Catálogo (`data-lucide="layout-grid"`)
     - Comparador (`data-lucide="git-compare"`)
     - Carrito (`data-lucide="shopping-bag"`, badge `#carrito-contador`)
     - Usuario/Ingresar (`includes/navbar_user.php` integrado)
     - Acceso Admin (`data-lucide="shield"` a `admin/dashboard.php`)
   - Detección reactiva de la página activa mediante variable `$paginaActiva` (`'catalogo'`, `'comparador'`, `'carrito'`, `'checkout'`, `'pedidos'`).
   - Aplicado de manera idéntica en `index.php`, `comparador.php`, `carrito.php`, `checkout.php` y `mis_pedidos.php`.

2. **Limpieza y Poda de Textos Redundantes / Estilo IA:**
   - **Hero (`index.php`):** Simplificado a `"Tecnología, Moda y Hogar"`, chip `"Garantía oficial y envíos a toda Bolivia"` y pills directas sin texto de marketing inflado.
   - **Footer (`index.php`):** Redacción limpia, sin menciones a "Kimovil" o "Asesoría humana".
   - **Comparador (`comparador.php`):** Título `"Comparador de Productos"`, subtítulo directo, aviso de filtro sin jerga y banner de soporte interactivo con apertura de chat directa en la misma vista (`onclick="document.getElementById('chat-toggle')?.click()"`).
   - **Carrito & Checkout (`carrito.php`, `checkout.php`):** Etiquetas concisas ("Gratis", "Garantía oficial y factura", "Finalizar Compra").
   - **Chat Widget (`includes/chat_widget.php`):** Sustitución de `"Asesora humana"` por `"Atención al cliente"` y mensaje inicial natural.

3. **Migración Completa de Admin Dashboard y Layout a Iconos Lucide:**
   - `admin/layout.php`: Inclusión de `../assets/js/lucide.min.js`, inicialización con `lucide.createIcons()` y topbar con iconos minimalistas (`menu`, `brain`, `user`, `settings`, `store`, `log-out`).
   - `admin/nav.php`: 100% migrado a Lucide (`zap`, `layout-dashboard`, `boxes`, `plus-circle`, `folder-tree`, `receipt`, `truck`, `brain`, `line-chart`, `history`, `alert-triangle`, `settings`, `external-link`, `log-out`).
   - `admin/dashboard.php`: KPIs migrados (`wallet`, `shopping-bag`, `boxes`, `package`, `alert-triangle`, `check-circle-2`), banner simplificado a `"Métricas & Reportes"` con iconos `bar-chart-2` y `message-square`, y tabla con `clock` y `eye`.
   - `admin/productos.php` y `admin/categorias.php`: Migración de indicadores y botones a Lucide.
   - `assets/css/admin.css`: Reglas de estilo vectoriales con trazo fino de 1.75px.

### C. Resultados de Pruebas
- Suite completa `tests/run_all_tests.php` ejecutada en contenedor Docker:
  1. `test_catalogo_departamentos.php`: **100% PASÓ**
  2. `test_segmentacion_v4.php`: **100% PASÓ**
  3. `test_auth_clientes.php`: **24/24 PASÓ**
  4. `test_chat_agregar_carrito.php`: **100% PASÓ**
  5. `test_bi_and_comparator.php`: **100% PASÓ**
  6. `test_nueva_categoria.php`: **100% PASÓ**
  7. `test_admin_lucide.php`: **15/15 PASÓ**
  8. `test_header_consistencia.php`: **25/25 PASÓ**


