# Proyecto: Sistema de Ventas con IA, RAG y Gestión Administrativa

Sistema de comercio electrónico interactivo asistido por IA con capacidades avanzadas de asesor humano ("Valeria"), filtrado inteligente en tiempo real, motor RAG v2 local y panel administrativo con asistente "Jarvis".

---

## 🔑 Credenciales de Acceso

### 1. Panel de Administración (Admin)
- **URL de Acceso:**
  - En Docker: [http://localhost:8085/admin/](http://localhost:8085/admin/)
  - En XAMPP / Local: [http://localhost/ventas-ia-main/admin/](http://localhost/ventas-ia-main/admin/)
- **Usuario:** `admin`
- **Contraseña:** `admin123`

### 2. Base de Datos / phpMyAdmin
- **phpMyAdmin (Docker):** [http://localhost:8082](http://localhost:8082)
- **Servidor:** `db` (o `localhost:3307` desde el host)
- **Usuario MySQL:** `root`
- **Contraseña MySQL:** `root`
- **Base de Datos:** `ventas_ia`

---

## 🌐 URLs del Sistema

| Servicio / Vista | URL (Docker) | URL (XAMPP / Local) |
|---|---|---|
| **Tienda y Catálogo** | [http://localhost:8085/](http://localhost:8085/) | [http://localhost/ventas-ia-main/](http://localhost/ventas-ia-main/) |
| **Panel Administrativo** | [http://localhost:8085/admin/](http://localhost:8085/admin/) | [http://localhost/ventas-ia-main/admin/](http://localhost/ventas-ia-main/admin/) |
| **Chat del Admin (Jarvis)** | [http://localhost:8085/admin/chat_admin.php](http://localhost:8085/admin/chat_admin.php) | [http://localhost/ventas-ia-main/admin/chat_admin.php](http://localhost/ventas-ia-main/admin/chat_admin.php) |
| **phpMyAdmin** | [http://localhost:8082](http://localhost:8082) | [http://localhost/phpmyadmin](http://localhost/phpmyadmin) |

---

## 🚀 Stack Tecnológico y Arquitectura
- **Backend:** PHP 8.2 (Apache / Docker y XAMPP)
- **Base de Datos:** MariaDB / MySQL (`ventas_ia`)
- **Motor de IA:** DeepSeek (`deepseek-chat`)
- **Motor RAG Local (Costo $0):**
  - Chunking semántico jerárquico con inyección de metadatos.
  - TF-IDF local con ponderación BM25 sobre corpus indexado.
  - Búsqueda híbrida vectorial + `FULLTEXT` en MariaDB.
- **Frontend:** HTML5, CSS3, JavaScript Vanilla, Bootstrap 5, FontAwesome, Chart.js.

---

## 🛠️ Comandos de Mantenimiento

### Reindexar la Base de Conocimiento RAG
Si agregas nuevos productos o editas políticas en `knowledge/`:
```bash
docker exec -i ventas_ia_app php scripts/index_rag.php
```

### Ejecutar Batería de Pruebas de Calidad
```bash
docker exec -i ventas_ia_app php tests/test_deep_scenarios.php
docker exec -i ventas_ia_app php tests/test_bi_and_comparator.php
```

---

## 🚀 Nuevas Funcionalidades Destacadas (v3.0)

- **⚖️ Comparador Estilo Kimovil (`/comparador.php`):**
  Comparativa técnica lado a lado de smartphones y gadgets (Pantalla 120Hz, AnTuTu v10 benchmarks, cámaras, batería, Kimovil score rating).
- **💼 Jarvis BI & Asesor Ejecutivo (`/admin/chat_admin.php`):**
  Historial persistente multisesión con barra lateral estilo ChatGPT/Claude, diagnóstico de rotación/stock, KPIs bimonetarios (Bs y USD) y generación de reportes descargables en CSV (Excel) y HTML ejecutivo.
- **✨ Rediseño Tech Premium & Glassmorphism:**
  Tipografía Outfit e Inter, paleta Slate profunda con acentos en índigo/cyan, micro-animaciones y dashboard SaaS de operaciones.