# 🛠️ Prompt Maestro — Fase 6: Remediación Priorizada (`ventas-ia-main` → Portal ULAT)

> **Nota para Gunnar:** Pega esto completo como siguiente instrucción para el mismo agente que generó `QA_REPORT.md` (ya tiene contexto del repo). Dale acceso de escritura — esta fase sí modifica código, en rama separada.

---

## 1. Contexto y decisiones ya confirmadas

- Existe `QA_REPORT.md` en la raíz del repo con 28 hallazgos (10 Críticas, 10 Altas, 5 Medias, 3 Bajas), verdicto **NO LISTO PARA PRODUCCIÓN**.
- **Alcance confirmado:** el despliegue público será **únicamente el portal/chatbot de ULAT**. Los módulos de e-commerce (carrito, checkout, cuentas de cliente, CRUD de productos/categorías/pedidos/proveedores en `admin/`) **se retiran de producción**.
- **Entorno de despliegue confirmado:** Docker en un VPS Linux (`docker-compose.yml`/`Dockerfile` ya existen en el repo).
- **Proxy/WAF delante de PHP: aún no decidido.** El código NO debe asumir que existe Cloudflare/Nginx delante. Debe funcionar de forma segura tanto si hay proxy como si no.
- **Datos actuales en MySQL local: solo de prueba.** Puedes truncar/depurar tablas y colecciones RAG contaminadas sin necesidad de respaldo previo.

---

## 2. Rol

Sigues siendo el mismo Ingeniero QA/AppSec Senior de la Fase 1-5, pero ahora en modo remediación: escéptico contigo mismo, cada fix se valida con la misma evidencia (PoC, línea de código) con la que se detectó el problema. No se cierra un hallazgo solo porque "ya se tocó el archivo" — se cierra cuando se **reprodujo que el ataque ya no funciona**.

---

## 3. Objetivo

Dejar el sistema en estado **"Listo para producción"** según el checklist de la sección 7.5 de `QA_REPORT.md`, ejecutando los cambios en orden de severidad, en una rama separada, con un commit por hallazgo (o grupo de hallazgos relacionados) y verificación posterior de cada uno.

---

## 4. Paso 1 (obligatorio, antes de tocar código) — Re-triage de los 28 hallazgos bajo el nuevo alcance

Antes de arreglar nada, genera una tabla corta (agrégala como nueva sección al final de `QA_REPORT.md`, o en un archivo nuevo `REMEDIACION_TRIAGE.md`) clasificando cada uno de los 28 hallazgos en una de estas tres categorías, según el alcance confirmado (solo ULAT):

- **Se resuelve al archivar la tienda** — el hallazgo vivía en un archivo que ya no estará en producción.
- **Sigue aplicando, hay que corregirlo** — el hallazgo afecta código que permanece en producción (ULAT, infraestructura, autenticación admin, RAG, etc.).
- **Cambia de alcance** — aplica parcialmente (ej. un archivo mixto que sirve tanto a tienda como a ULAT y hay que separar su lógica en vez de archivarlo entero).

No asumas nada sin verificarlo en el código real — si un archivo que "suena" a tienda en realidad es usado también por el flujo ULAT (por ejemplo, `includes/icons.php` o partes de `admin/configuracion.php`), decláralo explícitamente como "cambia de alcance" y dilo en el triage.

---

## 5. Paso 2 — Archivar los módulos de e-commerce

Mueve (no borres de golpe; preferible `git mv` a una carpeta `legacy-ecommerce/` en la raíz, fuera del `DocumentRoot` de producción y excluida en `.dockerignore`) al menos:

- `admin/productos.php`, `admin/producto_form.php`, `admin/categorias.php`, `admin/categoria_form.php`, `admin/pedidos.php`, `admin/detalle_pedido.php`, `admin/proveedores.php`, `admin/proveedor_form.php`, `admin/historico_ventas.php`
- `carrito.php`, `checkout.php`, `comparador.php`
- `login.php`, `registro.php`, `recuperar_password.php`, `restablecer_password.php`, `mis_pedidos.php`
- `api/procesar_pedido.php`, `api/auth_cliente.php`, `api/historico_ventas.php`, `api/guardar_categoria.php`, `api/detalle_pedido.php`
- `api/chat_rag.php`, `api/ia_handler.php` (endpoints IA heredados que no son los que realmente sirve el widget ULAT — confírmalo antes de mover, y si efectivamente son código muerto, bórralos en vez de archivarlos)
- `includes/auth_cliente.php` (si nada de ULAT depende de sesión de "cliente")

**Qué se mantiene en `admin/`:** lo que sirva al portal ULAT — dashboard/métricas del chatbot, el asistente IA administrativo (`chat_admin`/`api/chat_admin.php`), gestión de leads/prospectos de admisiones, y configuración global que no sea específica de moneda/tienda. Decide esto con criterio propio tras leer cada archivo, no por el nombre de la carpeta.

Tras mover los archivos, confirma en `REMEDIACION_TRIAGE.md` cuáles de los 28 hallazgos quedaron efectivamente resueltos por esta reubicación (deberían ser varios de los Críticos y Altos: los relacionados a `procesar_pedido.php`, `auth_cliente.php`, `historico_ventas.php`, `guardar_categoria.php`, `detalle_pedido.php`).

---

## 6. Paso 3 — Remediar lo que queda, Crítica → Alta → Media → Baja

Para cada hallazgo que el triage marcó como "sigue aplicando" o "cambia de alcance", corrígelo siguiendo la recomendación ya escrita en `QA_REPORT.md` (sección 7.3, por número de hallazgo). No hace falta que repita aquí las recomendaciones — ya están documentadas con evidencia y están asignadas a esta fase del Plan de Remediación (sección 7.4).

### Prioridades que se elevan por las decisiones confirmadas arriba:

1. **Esquema SQL consolidado para arranque limpio en Docker (hallazgo #25):** dado que el despliegue será Docker/VPS, es bloqueante — si `docker-entrypoint-initdb.d` solo monta `ventas_ia.sql` y no `rag_schema.sql` ni las migraciones `v3`–`v10`, el contenedor arranca sin tablas de RAG, rate limiting ni leads. Consolida un único `database/production_schema.sql` limpio (sin datos semilla de clientes/hashes por defecto) y actualiza `docker-compose.yml` para montarlo.
2. **Diseño de IP de confianza configurable, no asumido (hallazgo #11):** como aún no está decidido si habrá Cloudflare/Nginx delante, implementa una variable de entorno `TRUSTED_PROXY_CIDRS` (vacía por defecto). Si está vacía, usa siempre `$_SERVER['REMOTE_ADDR']` e ignora `CF-Connecting-IP`/`X-Forwarded-For` por completo. Solo si se configura esa variable (el día que Gunnar decida poner un proxy), confía en esas cabeceras — y solo si la conexión TCP real viene de una IP dentro de esos rangos.
3. **Hardening de `docker-compose.yml`/`Dockerfile` (hallazgo #17):** sin puertos de BD/phpMyAdmin expuestos a `0.0.0.0`, sin bind-mount de `.env`/código completo en producción, usuario no-root.
4. **Limpieza RAG sin miramientos (hallazgo #12):** como los datos son de prueba, trunca directamente las colecciones `catalogo` y `politicas` de `rag_documents` y reindexa solo ULAT — no hace falta backup previo.
5. **Cabeceras HTTP de seguridad (hallazgo #24):** especialmente importante al no haber WAF confirmado — `CSP`, `X-Frame-Options`, `HSTS`, `nosniff` se vuelven la única línea de defensa de ese tipo si al final no se pone Cloudflare.

### El resto de hallazgos de `QA_REPORT.md` que sigan aplicando tras el triage (autenticación admin, CSRF, XSS en `ulat/`/`assets/js/`, sesiones, resiliencia frente a DeepSeek, privacidad en `chat_logs`/`ulat/api_lead.php`, `APP_DEBUG`, bugs funcionales del RAG como `QueryExpander.php:48`, inconsistencias de contenido ULAT) se corrigen en el orden de severidad ya establecido.

---

## 7. Reglas de trabajo

- Todo en una rama `fix/qa-remediacion`, un commit (o grupo) por hallazgo, referenciando el número: `fix: cierra hallazgo Crítico #2 — bypass de auth en admin/`.
- Nunca imprimas ni commitees secretos reales, aunque los encuentres.
- Después de cada fix, reproduce la misma prueba (PoC) que usaste para confirmarlo en la auditoría original, ahora esperando que falle/se bloquee. No marques un hallazgo como cerrado solo por inspección visual del código.
- No borres permanentemente código de tienda sin moverlo primero a `legacy-ecommerce/` — puede tener valor para otro proyecto.
- Si al triaguar un hallazgo descubres que el código real no coincide con lo que describe `QA_REPORT.md` (falso positivo, o el problema es distinto al descrito), dilo explícitamente en vez de forzar una corrección que no aplica.

---

## 8. Entregable final

1. Rama `fix/qa-remediacion` con todos los commits.
2. `REMEDIACION_TRIAGE.md` (o sección añadida a `QA_REPORT.md`) con la clasificación del Paso 1 y qué quedó resuelto por archivado vs. por corrección directa.
3. Checklist de la sección 7.5 de `QA_REPORT.md`, reescrito con el estado **actualizado** (✅/❌) después de la remediación, y una nota explícita sobre el punto de HTTPS/proxy: como no está decidido, indica qué recomiendas (ej. "Cloudflare gratis delante del VPS para TLS + protección básica anti-bot" o "mínimo Nginx + Let's Encrypt si no se usa Cloudflare") sin asumir cuál se eligió.
4. Un resumen final de qué quedó pendiente (si algo) y por qué, sin inflar el estado — si algo queda en "Parcial", dilo así.

---

## 9. Fuera del alcance de este prompt (acción tuya, no del agente)

- Rotar las claves reales expuestas (DeepSeek, Pinecone, Redis, webhook de WhatsApp) en las consolas de cada proveedor — el agente puede sacarlas del código, pero no puede regenerarlas por ti.
- Configurar un tope duro de gasto mensual (*Hard Spend Limit*) en el panel de DeepSeek Platform.
- Decidir finalmente si pones Cloudflare u otro proxy delante del VPS — el código quedará listo para ambos escenarios, pero la elección es tuya.
