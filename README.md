# PROYECTO COLEGIO NOVA - MANUAL DE CONFIGURACIÓN Y DESPLIEGUE

## 📋 Descripción del Sistema
El **Sistema NOVA de Control de Tardanzas** es una solución web integral desarrollada para la **Institución Educativa Los Andes**. Permite registrar las llegadas tarde de los estudiantes mediante biometría/documento, gestionar excusas, visualizar historiales, emitir alertas automáticas de compromisos al alcanzar 3 tardanzas y exportar reportes en PDF y Excel.

---

## 🛠️ Tecnologías Utilizadas
- **Backend**: PHP 8.x con extensión **PDO** (PHP Data Objects).
- **Base de Datos**: MySQL / MariaDB.
- **Frontend**: HTML5, CSS3, JavaScript nativo ES6.
- **Servidor Web recomendado**: Apache o Nginx (XAMPP, WAMP, Laragon, o Linux LAMP).

---

## 🔒 Medidas de Seguridad Implementadas (Requeridas)
1. **Consultas Preparadas contra Inyecciones SQL**: Todas las interacciones con la base de datos se ejecutan a través de PDO con binding explícito de parámetros (`PDO::prepare` y `execute`).
2. **Manejo Seguro de Sesiones**:
   - `session.cookie_httponly = 1` (Previene robo de cookies por XSS).
   - `session.use_only_cookies = 1`.
   - `session_regenerate_id(true)` en inicio de sesión y regeneración periódica cada 30 minutos.
   - Verificación de sesión mediante middleware `require_auth()`.
3. **Encriptación de Contraseñas**: Uso de `password_hash()` con el algoritmo BCRYPT y verificación con `password_verify()`.

---

## 🚀 Pasos para la Configuración e Instalación

### 1. Descompresión del Archivo
Extraiga el contenido del archivo ZIP en el directorio raíz de su servidor web (por ejemplo: `C:\xampp\htdocs\colegio_nova` o `/var/www/html/colegio_nova`).

### 2. Importación de la Base de Datos
1. Abra **phpMyAdmin** o su gestor MySQL preferido (MySQL Workbench, DBeaver, CLI).
2. Cree la base de datos llamada 'colegio_nova' o ejecute directamente el archivo 'schema.sql'.
CREATE DATABASE colegio_nova DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

3. Importe el archivo 'schema.sql' ubicado en la raíz del proyecto. Este archivo creará todas las tablas requeridas (coordinadora, grupos, estudiante, llegada_tarde, excusas, compromisos, alertas, exportacion) junto con los datos semilla iniciales.

### 3. Configuración de la Conexión (config/db.php)
Edite el archivo 'config/db.php' para ajustar las credenciales de su base de datos si es necesario:
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'colegio_nova');

---

## 🔑 Credenciales de Acceso por Defecto
- **Módulo de Coordinación**:
  - **URL**: http://localhost/colegio_nova/login.php
  - **Usuario**: admin
  - **Contraseña**: admin123

- **Módulo Kiosco de Estudiantes**:
  - **URL**: http://localhost/colegio_nova/index.php
  - **Estudiantes de Prueba**:
    - Documento '1089647435' (Pepito Pérez - Grado 10 B)
    - Documento '1002345678' (María Rodríguez - Grado 11 A)
    - Documento '1098765432' (Carlos Gómez - Grado 9 C)

---

## 📁 Estructura del Proyecto
colegio_nova/
├── assets/
│   ├── css/
│   │   └── style.css       # Estilos oficiales verde/amarillo NOVA
│   └── escudo.png          # Escudo de la institución
├── auth/
│   ├── login.php           # Procesamiento de login
│   ├── logout.php          # Cierre de sesión seguro
│   └── session.php         # Configuración y guardián de sesiones
├── config/
│   └── db.php              # Conexión PDO a MySQL
├── coordinacion/
│   ├── dashboard.php       # Menú principal (Buscar, Agregar, Cambiar, Excusa)
│   ├── buscar.php          # Búsqueda de estudiantes
│   ├── ficha.php           # Ficha básica del estudiante
│   ├── historial.php       # Historial de tardanzas y firmas de compromiso
│   ├── agregar.php         # Registro de nuevos estudiantes con huella
│   ├── cambiar.php         # Edición de datos de estudiantes
│   ├── excusa.php          # Carga de archivos de excusa
│   └── exportar.php       # Generador de reportes PDF/Excel
├── uploads/                # Archivos adjuntos de excusas
├── index.php               # Kiosco interactivo de registro de llegadas tarde
├── schema.sql              # Script SQL con estructura e información inicial
└── README.md               # Documentación y manual de instalación

---

## 📑 Cumplimiento de Requisitos Funcionales
- **RF-01 a RF-08**: Kiosco de registro con validación biométrica/documento, captura automática de fecha/hora, opción con/sin excusa, y alerta visual al acumular 3 tardanzas.
- **RF-09 a RF-15**: Búsqueda en panel de coordinación, ficha detallada, contador de tardanzas, alerta de compromiso, registro de firma y alerta de reincidencia.
- **RF-16 a RF-19**: Generación de alertas, exportación directa a formatos PDF y Excel CSV.

---

## 🎨 Capa visual (v3) y regla de excusas
- `assets/css/style.css` y `assets/js/nova.js`: diseño refinado con la paleta verde/amarillo original, botones sin bordes, tooltips (`data-tip="texto"` en cualquier botón o enlace; en pantallas táctiles, mantener pulsado) y transición diagonal entre páginas (View Transitions con respaldo en JS; respeta `prefers-reduced-motion`).
- **Subir excusa (`coordinacion/excusa.php`)**: solo se permite si el estudiante indicó **«Sí»** a «¿Tienes excusa?» en el kiosco (`llegada_tarde.excusa = 1`) y esa llegada aún no tiene archivo en la tabla `excusas`. Si respondió «No» o nunca presentó excusa, el sistema muestra un mensaje y no deja subir. La validación se repite en el servidor al enviar el archivo (no se puede saltar desde el navegador). Se aceptan PDF, PNG y JPG.

## 🕒 Fecha y hora
- `config/db.php` fija la zona horaria **America/Bogota** (PHP `date_default_timezone_set` y `SET time_zone = '-05:00'` en MySQL). Antes PHP usaba UTC y, desde las 7:00 p. m. hora Colombia, la fecha aparecía un día adelantada.
- Las llegadas tarde y los compromisos se guardan con esa fecha y hora. El kiosco y el panel muestran un reloj en vivo (`data-nova-clock`) basado en la hora del servidor.
- Los registros guardados **antes** de este cambio conservan la fecha/hora que tenían (UTC); no se modifican.


## 🧭 Flujo de entrada (v4)
- `index.php`: portada con el logo de NOVA, la descripción del proyecto y el botón **Iniciar**.
- `acceso.php`: dos opciones, **Administrador** (lleva a `login.php`) y **Estudiante** (lleva al kiosco de huella).
- `estudiante.php`: kiosco de huella (antes estaba en `index.php`). Ya no muestra el botón «Acceso Coordinación» arriba a la derecha.
