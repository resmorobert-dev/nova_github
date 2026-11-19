# NOVA preparado para Vercel (Docker + FrankenPHP)

## Importante
Este paquete prepara el contenedor PHP para Vercel. No crea ni aloja la base de datos MySQL.
Debes contratar/configurar un servidor MySQL externo y cargar `database/schema.sql`.

## Variables de entorno
Configurar en Vercel > Project > Settings > Environment Variables:
- DB_HOST: hostname del servidor MySQL externo (no localhost)
- DB_PORT: puerto MySQL (normalmente 3306)
- DB_USER: usuario MySQL
- DB_PASS: contraseña MySQL
- DB_NAME: nombre de la base de datos
- HUELLA_MODO: sensor o simulado
- HUELLA_API_KEY: clave secreta larga, también configurada en el firmware ESP32
- HUELLA_PERMITIR_DOCUMENTO: true o false
- HUELLA_MAX_SLOTS: 127

Después de cambiar variables, hacer un nuevo deployment.

## Despliegue
Importar la raíz de este repositorio en Vercel. Debe detectar el Dockerfile y usar el preset Container.

## Limitaciones que aún requieren almacenamiento externo
Vercel no garantiza que los archivos escritos en el contenedor sobrevivan reinicios ni que sean compartidos entre instancias. Por eso `uploads/` y las sesiones PHP basadas en archivos no son aptas para producción multi-instancia sin adaptar el código a almacenamiento persistente/compartido (por ejemplo Blob para archivos y Redis para sesiones).

## Seguridad
No subir archivos `.env` ni contraseñas al repositorio. Cambiar la contraseña inicial del administrador antes de usar datos reales. No publicar datos personales o biométricos reales hasta completar almacenamiento, sesiones y controles de seguridad.
