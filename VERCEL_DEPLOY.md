# NOVA en Vercel (PHP + MySQL)

Esta adaptación conserva las páginas PHP existentes y las ejecuta con FrankenPHP dentro del contenedor de Vercel. La base de datos debe ser externa y persistente; el contenedor no es un servidor MySQL.

## Archivos agregados/modificados

- `Dockerfile.vercel`: ejecuta PHP 8.4 + FrankenPHP y habilita PDO MySQL.
- `Caddyfile`: sirve el sitio y bloquea rutas internas (configuración, SQL, firmware y sesiones).
- `config/db.php`: obtiene conexión desde variables de entorno.
- `auth/database_session_handler.php` y `auth/session.php`: guardan sesiones en MySQL.
- `schema.sql`: agrega la tabla `php_sessions`.
- `config/hardware.php`: lee la clave del ESP32 desde variables de entorno.

## 1. Crear base de datos MySQL compatible

Se puede usar TiDB Cloud Starter en modo MySQL-compatible para pruebas dentro de su cuota gratuita. Al superar la cuota, el servicio puede bloquear nuevas conexiones; no actives consumo de pago si quieres mantener el gasto en cero.

Crear una instancia MySQL-compatible y copiar host, puerto, usuario, contraseña y certificado CA desde la sección Connect.

## 2. Importar el esquema

Desde el SQL Editor o un cliente compatible:

1. Ejecuta `schema.sql`.
2. Selecciona la base de datos creada.
3. Ejecuta `migration_hardware.sql` si aún no existe `huella_id`, `huella_comando` y `huella_evento`.
4. Ejecuta `migration_huella.sql` solo si la clave única `uq_estudiante_huella` todavía no existe.
5. Si ya tenías una base creada antes de esta adaptación, agrega a `excusas` las columnas `nombre_original VARCHAR(255) NULL`, `tipo_mime VARCHAR(100) NULL` y `contenido MEDIUMBLOB NULL`; en una base nueva ya vienen incluidas.

No cargues datos reales de estudiantes durante las pruebas.

## 3. Importar el repositorio en Vercel

- Root Directory: `./` (la carpeta donde está `Dockerfile.vercel`).
- Framework Preset: `Other` o `Container` si aparece.
- Build Command: vacío.
- Output Directory: vacío.
- Install Command: vacío.

Si Vercel no detecta `Dockerfile.vercel` automáticamente, renómbralo a `Dockerfile` o selecciona el preset Container desde la configuración del proyecto.

## 4. Variables de entorno en Vercel

Configura estas variables en Project Settings → Environment Variables y vuelve a desplegar:

- `DB_HOST`: host de MySQL/TiDB.
- `DB_PORT`: puerto indicado por el proveedor (TiDB suele usar 4000).
- `DB_NAME`: nombre de la base de datos.
- `DB_USER`: usuario de base de datos.
- `DB_PASS`: contraseña de base de datos.
- `DB_SSL_CA_BASE64`: certificado CA del proveedor codificado en Base64 (recomendado para TiDB). También se admite `DB_SSL_CA` como ruta a un archivo dentro del contenedor.
- `HUELLA_MODO`: `sensor` o `simulado`.
- `HUELLA_API_KEY`: clave larga y aleatoria para el ESP32.
- `HUELLA_PERMITIR_DOCUMENTO`: `false` para exigir huella.
- `HUELLA_MAX_SLOTS`: por ejemplo `127`.

## 5. Crear el primer usuario administrador

El formulario público de cambio de contraseña ya no crea usuarios ni permite restablecimientos anónimos. Crea el primer usuario desde un equipo con PHP CLI y conexión a la base de datos configurada:

```bash
DB_HOST="TU_HOST" DB_PORT="4000" DB_NAME="colegio_nova" DB_USER="TU_USUARIO" DB_PASS="TU_PASSWORD" NOVA_ADMIN_USER="coordinacion" NOVA_ADMIN_PASSWORD="UNA_CLAVE_LARGA_Y_UNICA" php scripts/crear_admin.php
```

En Windows PowerShell define esas variables con `$env:NOMBRE="valor"` antes de ejecutar `php scripts/crear_admin.php`. No publiques la contraseña ni la guardes en GitHub.

## 6. Lector ESP32

Cuando el sitio tenga dominio HTTPS, cambia `SERVER_BASE` en `firmware/huella_nova/huella_nova.ino` por la URL pública de Nova (sin barra final), y `API_KEY` por el mismo valor configurado en `HUELLA_API_KEY`. Vuelve a cargar el firmware en el ESP32. Comprueba que el dispositivo pueda conectarse por HTTPS con la librería HTTPClient usada por el firmware.

## Limitaciones que todavía requieren configuración

- Los archivos de excusas ahora se guardan como BLOB en MySQL (límite de 3 MB) para que persistan entre instancias. Esto aumenta el uso de almacenamiento de la base de datos; para muchos documentos conviene migrar a almacenamiento de objetos privado.
- Las sesiones ya se guardan en MySQL; asegúrate de ejecutar el nuevo `schema.sql` que crea `php_sessions`.
- No uses datos personales reales, huellas ni documentos de menores en un despliegue público de pruebas. Configura consentimiento y autorización institucional antes de cualquier uso real.
- Vercel y TiDB tienen límites de uso gratuitos; revisa los límites actuales. Gratis no significa capacidad ilimitada ni disponibilidad garantizada.
