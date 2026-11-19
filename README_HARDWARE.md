# Lector de huella real (ESP32 + sensor R307 / AS608)

## Cómo funciona
```
Sensor R307/AS608 ──cable──> ESP32 ──WiFi──> PHP (XAMPP) ──> MySQL
```
- El **sensor** guarda las huellas en su propia memoria y reconoce a quién pertenece cada una (ranura 1, 2, 3...).
- El **ESP32** le avisa a la página qué ranura reconoció. La base de datos relaciona esa ranura con el estudiante (`estudiante.huella_id`).
- **Registrar**: en *Agregar* el sistema reserva una ranura, el ESP32 la recoge y te guía por pantalla (poner el dedo, retirarlo, ponerlo otra vez).
- **Identificar**: en el kiosco, cuando el dedo es reconocido, la página pasa sola a "¿Tienes excusa?".

## Materiales
- 1 ESP32 (DevKit) + cable micro-USB
- 1 sensor de huella R307 o AS608
- 4 cables dupont hembra-hembra

## Conexiones
| Sensor | ESP32 |
|---|---|
| VCC | 3V3 (AS608) o 5V/VIN (R307) |
| GND | GND |
| TX | GPIO16 |
| RX | GPIO17 |

Los colores de los cables cambian según el módulo: guíate por lo que dice el sensor o su datasheet, no por el color.

## Instalación
1. **Base de datos**: en phpMyAdmin importa `migration_hardware.sql` (una sola vez).
2. **Clave secreta**: en `config/hardware.php` cambia `HUELLA_API_KEY` por una clave larga y propia. Mientras tenga el valor por defecto, la API rechaza todo.
3. **Firmware**: en Arduino IDE instala el soporte de ESP32 y la librería *Adafruit Fingerprint Sensor Library*. Abre `firmware/huella_nova/huella_nova.ino` y edita `WIFI_SSID`, `WIFI_PASS`, `SERVER_BASE` (IP del computador con XAMPP) y `API_KEY` (la misma de la clave del paso 2). Sube el programa con la placa "ESP32 Dev Module".
4. **Red**: el ESP32 y el computador deben estar en el mismo WiFi. En Windows, permite Apache en el firewall (red privada). Prueba que `http://IP_DEL_PC/colegio_nova/` abra desde un celular en ese WiFi.
5. Abre el Monitor Serie (115200) del Arduino IDE: debe decir "Sensor de huella encontrado" y "WiFi conectado".

## Probar
1. Entra como coordinadora → **Agregar**, llena los datos y pulsa **Iniciar captura**.
2. Sigue los mensajes en pantalla: pon el dedo, retíralo, ponlo otra vez. Al terminar dice que la huella fue registrada.
3. Ve al kiosco (`index.php`) y pon el mismo dedo: debe pasar solo a "¿Tienes excusa?".

Prueba rápida de la API sin el ESP32 (cambia la clave y la IP):
```
curl -X POST -H "X-API-Key: TU_CLAVE" -d "accion=comando" http://localhost/colegio_nova/api/huella.php
```
Debe responder `NINGUNO`. Sin la cabecera responde `NO_AUTORIZADO`.

## Opciones en `config/hardware.php`
- `HUELLA_MODO`: `'sensor'` (real) o `'simulado'` (sin hardware, como antes).
- `HUELLA_PERMITIR_DOCUMENTO`: en `false` el kiosco solo acepta huella. Déjalo en `false` en producción; si no, cualquiera puede registrar la tardanza de otro estudiante escribiendo su documento.

## Limitaciones conocidas
- **Los estudiantes que ya existen** (los de prueba y los creados antes) no tienen huella en el sensor. Hay que registrarlos de nuevo con *Agregar* (primero elimínalos desde *Cambiar*) o pedir que se agregue un botón de "volver a registrar huella".
- Al eliminar un estudiante, su huella queda guardada en el sensor. Se sobrescribe cuando esa ranura se reutilice.
- Si cambias de sensor o lo borras, las ranuras de la base de datos dejan de coincidir con el sensor.
- La comunicación ESP32 ↔ servidor es HTTP sin cifrar. Para un colegio en red interna es aceptable; no lo expongas a internet.
- Los problemas de seguridad del resto del proyecto (restablecer_clave.php público, cambiar_clave.php sin login, clave `admin123` fija en login.php) siguen sin corregir.
