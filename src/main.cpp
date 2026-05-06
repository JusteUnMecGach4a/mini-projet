#include <Arduino.h>
#include <WiFi.h>
#include <Wire.h>
#include <Adafruit_AHTX0.h>
#include <time.h>

#define MYSQL_DEBUG_PORT      Serial
#define _MYSQL_LOGLEVEL_      1
#include <MySQL_Generic.h>

// --- WiFi Credentials ---
const char* ssid = "SNIR";
const char* password = "";

// --- Database Credentials ---
char user[] = "user_IoT";
char pwd[]  = "iot_pwd_4";
char server[] = "CANOVA.local";
uint16_t server_port = 13306;

// --- Database Name and Query ---
char default_db[] = "bay_monitoring"; // Assuming this database name, please adjust if needed
char insert_query[] = "INSERT INTO mesures (date_mesure, temperature, humidite, id_capteur) VALUES ('%s', %.2f, %.2f, 1)";

// --- NTP Server ---
const char* ntpServer = "pool.ntp.org";
const long  gmtOffset_sec = 3600; // Adjust according to timezone (1h for CET)
const int   daylightOffset_sec = 3600; // +1h for DST

// --- Hardware Objects ---
Adafruit_AHTX0 aht;

WiFiClient client; // Handled by MySQL_MariaDB_Generic library

MySQL_Connection conn((Client *)&client);

// Variables
unsigned long previousMillis = 0;
const long interval = 10000; // 10 seconds interval

void setupWiFi() {
  Serial.print("Connecting to WiFi: ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected.");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
}

void setupNTP() {
  configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);
  struct tm timeinfo;
  if (!getLocalTime(&timeinfo)) {
    Serial.println("Failed to obtain time");
    return;
  }
  Serial.println(&timeinfo, "Time synchronized: %A, %B %d %Y %H:%M:%S");
}

void setup() {
  Serial.begin(9600);
  Serial.println("\n--- Server Bay Monitoring ---");

  while (!Serial) delay(10);

  // Initialize I2C for AHT20
  Wire.begin(21, 22);

  // Initialize AHT20
  if (!aht.begin()) {
    Serial.println("Could not find AHT? Check wiring");
  } else {
    Serial.println("AHT20 found");
  }

  setupWiFi();
  setupNTP();
}

void loop() {
  unsigned long currentMillis = millis();

  // Run the very first time immediately, then every interval
  if (currentMillis - previousMillis >= interval || previousMillis == 0) {
    previousMillis = currentMillis;

    sensors_event_t humidity, temp;
    aht.getEvent(&humidity, &temp);

    Serial.print("Temperature: "); Serial.print(temp.temperature); Serial.println(" C");
    Serial.print("Humidity: "); Serial.print(humidity.relative_humidity); Serial.println(" %");

    // Get current time
    struct tm timeinfo;
    char timeStringBuff[50];
    if (getLocalTime(&timeinfo)) {
      strftime(timeStringBuff, sizeof(timeStringBuff), "%Y-%m-%d %H:%M:%S", &timeinfo);
    } else {
      strcpy(timeStringBuff, "1970-01-01 00:00:00");
    }

    // Connect to MySQL and Insert
    Serial.print("Connecting to MySQL Server... ");
    if (conn.connect(server, server_port, user, pwd, default_db)) {
      Serial.println("Connected.");
      
      MySQL_Query query_obj = MySQL_Query(&conn);

      // Initialize Bays and Sensors
      query_obj.execute("INSERT IGNORE INTO baies (id_baie, nom_baie, emplacement) VALUES (1, 'Alpha', 'Salle Serveur A')");
      query_obj.execute("INSERT IGNORE INTO capteurs (id_capteur, reference, type_com, id_baie) VALUES (1, 'AHT20', 'I2C', 1)");
      
      query_obj.execute("INSERT IGNORE INTO baies (id_baie, nom_baie, emplacement) VALUES (2, 'Beta', 'Zone Test')");
      query_obj.execute("INSERT IGNORE INTO capteurs (id_capteur, reference, type_com, id_baie) VALUES (2, 'Virtuel', 'Simulé', 2)");

      query_obj.execute("INSERT IGNORE INTO baies (id_baie, nom_baie, emplacement) VALUES (3, 'Gamma', 'Salle Serveur C')");
      query_obj.execute("INSERT IGNORE INTO capteurs (id_capteur, reference, type_com, id_baie) VALUES (3, 'Simulateur', 'Random', 3)");

      query_obj.execute("INSERT IGNORE INTO baies (id_baie, nom_baie, emplacement) VALUES (4, 'Delta', 'Local Stockage')");
      query_obj.execute("INSERT IGNORE INTO capteurs (id_capteur, reference, type_com, id_baie) VALUES (4, 'Alerteur', 'Fixe', 4)");

      char query[256];
      
      // 1. Alpha (Real)
      sprintf(query, "INSERT INTO mesures (date_mesure, temperature, humidite, id_capteur) VALUES ('%s', %.2f, %.2f, 1)", 
              timeStringBuff, temp.temperature, humidity.relative_humidity);
      query_obj.execute(query);

      // 2. Beta (Toggle Hot/Normal)
      static bool betaHot = false;
      float betaTemp = betaHot ? 52.4 : 21.8;
      float betaHum = betaHot ? 85.5 : 45.0;
      sprintf(query, "INSERT INTO mesures (date_mesure, temperature, humidite, id_capteur) VALUES ('%s', %.2f, %.2f, 2)", 
              timeStringBuff, betaTemp, betaHum);
      query_obj.execute(query);
      betaHot = !betaHot;

      // 3. Gamma (Random variation)
      float gammaTemp = 24.0 + (random(-200, 200) / 100.0);
      float gammaHum = 50.0 + (random(-500, 500) / 100.0);
      sprintf(query, "INSERT INTO mesures (date_mesure, temperature, humidite, id_capteur) VALUES ('%s', %.2f, %.2f, 3)", 
              timeStringBuff, gammaTemp, gammaHum);
      query_obj.execute(query);

      // 4. Delta (Constant HIGH - Permanent Alert)
      sprintf(query, "INSERT INTO mesures (date_mesure, temperature, humidite, id_capteur) VALUES ('%s', 65.20, 92.10, 4)", 
              timeStringBuff);
      query_obj.execute(query);

      Serial.println("Alpha, Beta, Gamma, Delta updated.");
      conn.close();
    } else {
      Serial.println("Connection failed.");
    }
  }
}
