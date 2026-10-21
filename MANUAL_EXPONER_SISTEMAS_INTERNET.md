# GUÍA MAESTRA DE DESPLIEGUE, TÚNELES Y ACCESO A INTERNET
## Arquitectura Multi-Sistema, Enrutador NGINX Central y Protocolo de Aislamiento para Agentes de IA

> [!IMPORTANT]
> **AVISO CRÍTICO PARA AGENTES DE IA Y DESARROLLADORES:**  
> En esta máquina operan **múltiples sistemas en producción simultánea**.  
> Todos los sistemas comparten una **única URL fija permanente de Ngrok** gracias a un **Enrutador Inverso NGINX centralizado**.  
> Sigue estas reglas al pie de la letra para agregar un **Tercer (o N-ésimo) Sistema** sin romper los sistemas existentes, sin causar colisiones de puertos y sin dejar contenedores innecesarios o huérfanos.

---

## 1. Inventario de Credenciales y Recursos Activos

| Recurso | Identificador / Dominio | Detalle / Uso |
|---|---|---|
| **URL Pública Fija Maestra** | `https://chummy-conductor-wilt.ngrok-free.dev` | Dominio permanente con SSL asignado a Ngrok. **NUNCA CAMBIA**. |
| **Ngrok Service Activo** | Servicio systemd `ngrok.service` en `japon-server` | Túnel persistente apuntando al NGINX central en puerto 80. |
| **Enrutador Central (Router)** | NGINX 1.24 en `japon-server` (`100.100.167.18` / `192.168.1.124`) | Distribuye el tráfico entrante de Ngrok según la sub-ruta solicitada. |
| **Sub-ruta: Dilan 2026** | `https://chummy-conductor-wilt.ngrok-free.dev/` | Sitio Overclock Computers activo en la raíz. |
| **Sub-ruta: Ventas IA** | `https://chummy-conductor-wilt.ngrok-free.dev/ventas_ia/` | Enrutado mediante Reverse Proxy a `LUI-PC:8085` (Tailscale `100.96.196.41` / LAN `192.168.1.129`). También alias `/ventas/` y `/ventas-ia/`. |
| **Cloudflare Zero Trust** | ID: `6b985376-e04c-4fc1-b277-459bd46c38d1` | Túnel alternativo de respaldo con token corporativo. |
| **Cloudflare Quick Tunnels** | `*.trycloudflare.com` | **Solo para pruebas efímeras aisladas**. No dejar encendido si ya se usa el router central. |

---

## 2. Mapa Completo de Puertos y Servicios Activos

| Servicio / Proyecto | Puerto Interno | Puerto Host / Red | Sub-ruta en Ngrok | Estado |
|---|:---:|:---:|:---:|---|
| **Dilan 2026 (Overclock)** | `80` (PHP-FPM) | Local en `japon-server` | `/` (Raíz) | **ACTIVO (PRODUCCIÓN)** |
| **Sistema Ventas IA (Apache/PHP)** | `80` | `100.96.196.41:8085` (LUI-PC) | `/ventas_ia/`, `/ventas/` | **ACTIVO (PRODUCCIÓN)** |
| **Ventas IA DB (MariaDB 10.11)** | `3306` | `0.0.0.0:3307` (LUI-PC) | - | **ACTIVO (PRODUCCIÓN)** |
| **Ventas IA phpMyAdmin** | `80` | `0.0.0.0:8082` (LUI-PC) | - | **ACTIVO** |
| **Túnel Ngrok (japon-server)** | `4040` | Interno en `japon-server` | Expone NGINX Router | **ACTIVO (CRÍTICO)** |
| **Teleco Frontend** | `80` / `443` | `8080` / `8443` | `/teleco/` | **STANDBY / LOCAL** |
| **Teleco Backend** | `3000` | `3001` | - | **ACTIVO** |
| **Teleco TimescaleDB** | `5432` | `5432` | - | **ACTIVO** |
| **Consultorio Backend** | `3000` | `3000` | - | **ACTIVO** |

---

## 3. Arquitectura del Enrutador NGINX Central (Multi-Tenant Activa)

El tráfico entra por la única URL pública fija y el NGINX Router lo redirige transparentemente al sistema correspondiente:

```text
                                              ┌───> [ / ]            ───> Dilan 2026 (Overclock Computers local)
                                              │
[ https://chummy-...ngrok-free.dev ] ───> NGINX ──┼───> [ /ventas_ia/ ]  ───> Ventas IA (Tailscale: 100.96.196.41:8085)
                                              │
                                              └───> [ /teleco/ ]     ───> Teleco (Tailscale / Host: 8080)
```

---

## 4. Protocolo Paso a Paso: Incorporar un Tercer Sistema

Sigue estos 5 pasos exactos para integrar un nuevo sistema sin alterar los demás:

### Paso 1: Configurar el `docker-compose.yml` del Tercer Sistema
Elige un puerto host libre (por ejemplo `8090`) y **asigna prefijos únicos a todos los contenedores y volúmenes**:

```yaml
services:
  app:
    build: .
    container_name: sistema3_app        # <-- Nombre único, evita 'app' genérico
    restart: unless-stopped
    ports:
      - "8090:80"                      # <-- Puerto libre en el host
    networks:
      - red_sistema3

  db:
    image: postgres:15-alpine
    container_name: sistema3_db         # <-- Nombre único
    restart: unless-stopped
    ports:
      - "5433:5432"                    # <-- Puerto libre (evita 5432 y 3306)
    volumes:
      - sistema3_data:/var/lib/postgresql/data
    networks:
      - red_sistema3

volumes:
  sistema3_data:                       # <-- Volumen con prefijo propio

networks:
  red_sistema3:
    driver: bridge
```

Levanta el nuevo sistema en su propio directorio:
```powershell
docker compose up -d
```
Verifica que responda localmente en el puerto elegido:
```powershell
curl.exe -I http://localhost:8090
```

---

### Paso 2: Registrar el Tercer Sistema en el NGINX Router
En el archivo de configuración del router (`c:\xampp\htdocs\ingenieria-control\nginx\nginx.conf`), agrega el nuevo `upstream` y su bloque `location`:

```nginx
events { worker_connections 1024; }

http {
    # Upstream Sistema 1 (Punata)
    upstream vacunas_backend {
        server web:8000;
    }

    # Upstream Sistema 2 (Ventas IA)
    upstream ventas_backend {
        server host.docker.internal:8085;
    }

    # Upstream Sistema 3 (Nuevo Sistema)
    upstream sistema3_backend {
        server host.docker.internal:8090;  # El puerto configurado en el Paso 1
    }

    server {
        listen 80;
        server_name chummy-conductor-wilt.ngrok-free.dev localhost;

        # 1. Sistema 1: Vacunas Punata (Raíz)
        location / {
            proxy_pass http://vacunas_backend;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto https;
        }

        # 2. Sistema 2: Ventas IA
        location /ventas/ {
            proxy_pass http://ventas_backend/;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto https;
        }

        # 3. Sistema 3: Nuevo Sistema
        location /sistema3/ {
            proxy_pass http://sistema3_backend/;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto https;
        }
    }
}
```

> [!NOTE]
> La barra final `/` en `proxy_pass http://sistema3_backend/;` es crucial: indica a NGINX que debe remover el prefijo `/sistema3/` antes de reenviar la petición interna al contenedor si la aplicación fue escrita para la raíz.

---

### Paso 3: Recargar NGINX en Caliente (Zero Downtime)
**NO reinicies todo Docker ni detengas los otros sistemas**. Solo recarga la configuración de NGINX con este comando instantáneo:

```powershell
docker exec punata-vacuna-segura-router-1 nginx -s reload
```
*(O desde `c:\xampp\htdocs\ingenieria-control`: `docker compose exec router nginx -s reload`).*

---

### Paso 4: Validar Acceso por Internet
Prueba la respuesta directamente desde la URL pública permanente:
```powershell
curl.exe -I https://chummy-conductor-wilt.ngrok-free.dev/sistema3/
```
Debe devolver `HTTP/1.1 200 OK` (o redirección válida).

---

## 5. Política de Higiene: Cero Contenedores Innecesarios o Huérfanos

Para mantener la estabilidad y los recursos de la máquina:

### Reglas de Eliminación Segura:
1. **NO dejar túneles redundantes abiertos:**  
   Si un sistema fue integrado al Router NGINX central, **no debe tener contenedores de túneles adicionales** (como `cloudflared` efímero o un segundo `ngrok`).  
   Detenlos y bórralos inmediatamente:
   ```powershell
   docker stop <nombre_contenedor_tunel>
   docker rm <nombre_contenedor_tunel>
   ```
2. **Eliminar el servicio en `docker-compose.yml`:**  
   Retira el bloque `tunnel:` o `cloudflared:` del archivo compose del proyecto para evitar que vuelva a levantarse por accidente en el próximo `docker compose up`.
3. **⛔ PROHIBICIÓN ABSOLUTA DE PRUNE GENERAL:**  
   **NUNCA ejecutes:**
   - ❌ `docker system prune`
   - ❌ `docker system prune -a`
   - ❌ `docker volume prune`  
   *Ejecutar estos comandos destruiría las bases de datos y volúmenes de los sistemas Punata, Ventas IA y Teleco.*
4. **Limpieza quirúrgica de imágenes huérfanas:**  
   Si requieres liberar espacio tras un build fallido, usa únicamente:
   ```powershell
   docker image prune --filter "dangling=true"
   ```
   *(Este comando solo borra capas temporales sin nombre y no toca contenedores ni volúmenes).*

---

## 6. Variables de Entorno y Rutas en Aplicaciones Detrás de Sub-rutas

Cuando un sistema corre detrás de una sub-ruta (como `/ventas/` o `/sistema3/`), asegúrate de que el frontend y el backend manejen rutas relativas:

* **HTML / CSS / JS:**  
  Usa rutas relativas (`assets/css/estilo.css` en lugar de `/assets/css/estilo.css`) o etiqueta `<base href="/sistema3/">`.
* **Frameworks (Django / Laravel / FastAPI):**
  - Configura `FORCE_SCRIPT_NAME = '/sistema3'` o `root_path = "/sistema3"`.
  - En Django `settings.py`:
    ```python
    ALLOWED_HOSTS = ['*', 'localhost', '127.0.0.1', '.ngrok-free.dev']
    CSRF_TRUSTED_ORIGINS = ['https://*.ngrok-free.dev']
    SECURE_PROXY_SSL_HEADER = ('HTTP_X_FORWARDED_PROTO', 'https')
    USE_X_FORWARDED_HOST = True
    ```
* **Evitar la pantalla de bienvenida de Ngrok en llamadas API / microcontroladores:**  
  Envía siempre el encabezado HTTP:
  ```http
  ngrok-skip-browser-warning: true
  ```

---

## 7. Resumen de Comandos Rápidos para Agentes

| Acción | Comando PowerShell Exacto |
|---|---|
| **Ver todos los contenedores y puertos activos** | `docker ps` |
| **Probar respuesta local de un puerto** | `curl.exe -I http://localhost:<puerto>` |
| **Recargar NGINX Router sin downtime** | `docker exec punata-vacuna-segura-router-1 nginx -s reload` |
| **Probar URL pública del nuevo sistema** | `curl.exe -I https://chummy-conductor-wilt.ngrok-free.dev/<sub-ruta>/` |
| **Apagar contenedor innecesario de forma segura** | `docker stop <nombre_contenedor> ; docker rm <nombre_contenedor>` |
| **Ver logs del router NGINX si hay error 502** | `docker logs --tail=30 punata-vacuna-segura-router-1` |
