/*
 * NOVA · Los Andes — lector de huella (ESP32 + sensor R307 / AS608)
 *
 * Conexiones (ESP32 DevKit):
 *   Sensor VCC  -> 3V3 (AS608)  o  5V/VIN (R307)
 *   Sensor GND  -> GND
 *   Sensor TX   -> GPIO16 (RX2 del ESP32)
 *   Sensor RX   -> GPIO17 (TX2 del ESP32)
 *   (si algún cable es verde/blanco, revisa el datasheet de TU módulo: los colores varían)
 *
 * Librería necesaria (Gestor de librerías del Arduino IDE):
 *   "Adafruit Fingerprint Sensor Library"
 * Placa: "ESP32 Dev Module"
 *
 * Qué hace:
 *   - Cada 2 s pregunta a la página si hay que registrar una huella nueva.
 *   - El resto del tiempo lee el sensor; si reconoce una huella, avisa a la página
 *     con el número de ranura (slot) y la página identifica al estudiante.
 */
#include <WiFi.h>
#include <HTTPClient.h>
#include <Adafruit_Fingerprint.h>

// ====== CONFIGURA ESTO ======
const char* WIFI_SSID   = "TU_WIFI";
const char* WIFI_PASS   = "TU_CLAVE_WIFI";
// IP del computador donde corre XAMPP (sin barra al final). Ej: http://192.168.1.50/colegio_nova
const char* SERVER_BASE = "http://192.168.1.50/colegio_nova";
// Debe ser IGUAL a HUELLA_API_KEY en config/hardware.php
const char* API_KEY     = "CAMBIAR_ESTA_CLAVE_LARGA_Y_SECRETA";
// ============================

HardwareSerial sensorSerial(2);
Adafruit_Fingerprint finger(&sensorSerial);
unsigned long ultimaConsulta = 0;

String urlenc(const String& s) {
  String o;
  char buf[4];
  for (size_t i = 0; i < s.length(); i++) {
    unsigned char c = s[i];
    if (isalnum(c) || c == '-' || c == '_' || c == '.' || c == '~') {
      o += (char)c;
    } else {
      snprintf(buf, sizeof(buf), "%%%02X", c);
      o += buf;
    }
  }
  return o;
}

// Envía un POST a la API y devuelve la respuesta ("" si falló)
String llamar(const String& datos) {
  if (WiFi.status() != WL_CONNECTED) return "";
  HTTPClient http;
  http.begin(String(SERVER_BASE) + "/api/huella.php");
  http.addHeader("X-API-Key", API_KEY);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  int code = http.POST(datos);
  String r = (code == 200) ? http.getString() : "";
  if (code != 200) {
    Serial.printf("HTTP %d en %s\n", code, datos.c_str());
  }
  http.end();
  r.trim();
  return r;
}

void progreso(int id, const String& msg) {
  llamar("accion=progreso&id=" + String(id) + "&mensaje=" + urlenc(msg));
}

void terminar(int id, bool ok, const String& msg) {
  llamar("accion=resultado&id=" + String(id) + "&estado=" + (ok ? "ok" : "error") + "&mensaje=" + urlenc(msg));
}

// Espera a que haya (presente=true) o no haya (presente=false) un dedo sobre el sensor
bool esperarDedo(bool presente, unsigned long ms) {
  unsigned long t = millis();
  while (millis() - t < ms) {
    uint8_t p = finger.getImage();
    if (presente && p == FINGERPRINT_OK) return true;
    if (!presente && p == FINGERPRINT_NOFINGER) return true;
    delay(100);
  }
  return false;
}

void enrolar(int id, int slot) {
  Serial.printf("Registrando huella en ranura %d (comando %d)\n", slot, id);

  progreso(id, "Coloque el dedo en el sensor");
  if (!esperarDedo(true, 30000)) { terminar(id, false, "Tiempo agotado: no se detectó el dedo."); return; }
  if (finger.image2Tz(1) != FINGERPRINT_OK) { terminar(id, false, "Lectura no válida. Intente de nuevo."); return; }

  progreso(id, "Retire el dedo");
  esperarDedo(false, 10000);

  progreso(id, "Coloque el mismo dedo otra vez");
  if (!esperarDedo(true, 30000)) { terminar(id, false, "Tiempo agotado: no se detectó el dedo."); return; }
  if (finger.image2Tz(2) != FINGERPRINT_OK) { terminar(id, false, "Lectura no válida. Intente de nuevo."); return; }

  if (finger.createModel() != FINGERPRINT_OK) { terminar(id, false, "Las dos lecturas no coinciden. Intente de nuevo."); return; }
  if (finger.storeModel(slot) != FINGERPRINT_OK) { terminar(id, false, "No se pudo guardar la huella en el sensor."); return; }

  terminar(id, true, "Huella registrada");
  Serial.println("Huella guardada.");
}

void identificar() {
  if (finger.getImage() != FINGERPRINT_OK) return;
  if (finger.image2Tz() != FINGERPRINT_OK) return;

  if (finger.fingerFastSearch() == FINGERPRINT_OK) {
    Serial.printf("Huella reconocida: ranura %d (confianza %d)\n", finger.fingerID, finger.confidence);
    llamar("accion=identificado&slot=" + String(finger.fingerID));
  } else {
    Serial.println("Huella no reconocida.");
  }

  // Esperar a que retire el dedo para no leer dos veces
  unsigned long t = millis();
  while (finger.getImage() != FINGERPRINT_NOFINGER && millis() - t < 5000) delay(100);
}

void conectarWifi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Conectando a WiFi");
  unsigned long t = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t < 20000) {
    delay(500);
    Serial.print(".");
  }
  Serial.println(WiFi.status() == WL_CONNECTED ? "\nWiFi conectado: " + WiFi.localIP().toString() : "\nSin WiFi (reintentando...)");
}

void setup() {
  Serial.begin(115200);
  sensorSerial.begin(57600, SERIAL_8N1, 16, 17);
  finger.begin(57600);

  if (finger.verifyPassword()) {
    Serial.println("Sensor de huella encontrado.");
  } else {
    Serial.println("NO se encontró el sensor. Revisa cables TX/RX y alimentación.");
    while (true) delay(1000);
  }
  conectarWifi();
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    conectarWifi();
    return;
  }

  if (millis() - ultimaConsulta > 2000) {
    ultimaConsulta = millis();
    String r = llamar("accion=comando");
    if (r.startsWith("ENROLAR|")) {
      int p1 = r.indexOf('|');
      int p2 = r.indexOf('|', p1 + 1);
      int id   = r.substring(p1 + 1, p2).toInt();
      int slot = r.substring(p2 + 1).toInt();
      enrolar(id, slot);
      return;
    }
  }

  identificar();
  delay(50);
}
