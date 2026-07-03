# Squid Log Analyzer

Ein webbasierter Analysator für Squid-Proxy-Logdateien, der detaillierte Einblicke in den Netzwerkverkehr bietet. Die Anwendung visualisiert Daten durch interaktive Tabellen und Diagramme und bietet Funktionen zur Filterung und Sortierung.

<details>
  <summary>📸 Beispiel Screenshots</summary>
  <br>  
  ![Beispiel-Screenshot](/images/example/example-1.png)
  ![Beispiel-Screenshot](/images/example/example-2.png)
  ![Beispiel-Screenshot](/images/example/example-3.png)
  ![Beispiel-Screenshot](/images/example/example-4.png)
  ![Beispiel-Screenshot](/images/example/example-5.png)
  ![Beispiel-Screenshot](/images/example/example-6.png)
</details>
---

## ✨ Features

- **Dashboard-Übersicht:** Zeigt die wichtigsten Kennzahlen und Diagramme auf einen Blick.
- **Detaillierte Tabellen:**
  - Top-Clients nach Datenvolumen und Anfragen.
  - Top-User-Agents.
  - Meistbesuchte Domains.
  - Verwendete HTTP-Methoden (GET, POST, CONNECT, etc.).
  - Analyse des Proxy-Cache-Verhaltens (HIT/MISS-Ratios).
- **Interaktive Diagramme:** Visualisierung der Verteilung von HTTP-Methoden, User-Agents und Domain-Aufrufen.
- **Filterung & Sortierung:** Alle Tabellen können dynamisch gefiltert und sortiert werden, um spezifische Daten zu analysieren.
- **Echtzeit-Client-Auflösung:** Löst IP-Adressen im internen Netzwerk über verschiedene Methoden (DNS, DHCP, NetBIOS, mDNS) in Hostnamen auf und zeigt den Status live über WebSockets an.
- **Automatischer Datenimport:** Ein Skript zur Automatisierung des Imports von Squid-Logdateien in die Datenbank.

## 🛠️ Tech Stack

- **Backend:** PHP 8+, Node.js (für WebSocket-Server)
- **Datenbank:** MySQL / MariaDB
- **Frontend:** HTML5, Bootstrap 5, JavaScript (ES6), Chart.js
- **Server:** Apache oder Nginx mit PHP-Unterstützung

## ⚙️ Voraussetzungen

- Webserver (z.B. Apache, Nginx)
- PHP >= 8.0 mit den Erweiterungen `pdo_mysql`
- MySQL oder MariaDB
- Node.js und npm
- Git
- Für die vollständige Client-Namensauflösung (`internalnetwork.class.php`):
  - `samba` (für `nmblookup`)
  - `avahi-utils` (für `avahi-resolve-address`)
  - `arping`

## 🚀 Installation

1.  **Repository klonen:**

    ```bash
    git clone https://github.com/aglitza/squidloganalyzer.git
    cd squidloganalyzer
    ```

2.  **Datenbank einrichten:**
    - Erstellen Sie eine neue Datenbank in MySQL/MariaDB.
    - Importieren Sie die Tabellenstruktur. Eine `schema.sql`-Datei sollte hierfür erstellt und bereitgestellt werden.
      _(Hinweis: Aktuell muss die Struktur manuell angelegt werden. Siehe `loadData`-Funktion in `classes/general.class.php` für die Spalten.)_

3.  **Konfiguration (Backend):**
    - Kopieren Sie die Beispiel-Konfigurationsdatei:

    ```bash
    cp includes/configuration.includes.php.example includes/configuration.includes.php
    ```

    - Öffnen Sie `includes/configuration.includes.php` und tragen Sie Ihre Datenbank-Zugangsdaten ein.

4.  **WebSocket-Server einrichten:**
    - Installieren Sie die Node.js-Abhängigkeiten:

    ```bash
    npm install
    ```

    - **SSL-Zertifikate:** Der WebSocket-Server benötigt SSL-Zertifikate. Passen Sie die Pfade in `js/ws-server.js` an oder, besser noch, verwenden Sie Umgebungsvariablen:

    ```bash
    export WS_CERT_PATH='/pfad/zu/ihrem/zertifikat.pem'
    export WS_CERT_KEY_PATH='/pfad/zu/ihrem/schluessel.key'
    ```

    - Starten Sie den Server. Für den Dauerbetrieb wird ein Prozess-Manager wie `pm2` empfohlen:

    ```bash
    # Installation von pm2 (falls noch nicht geschehen)
    npm install -g pm2

    # Server starten
    pm2 start js/ws-server.js --name squidlog-ws
    ```

## 🖥️ Benutzung

### 1. Logdateien importieren

Die Anwendung liest Daten aus der Datenbank. Sie müssen Ihre Squid-Logdateien regelmäßig importieren.

- **Log-Format:** Stellen Sie sicher, dass Ihre Squid-Logs in einem Format vorliegen, das von der `loadData`-Funktion verarbeitet werden kann (Semikolon-getrennt).
- **Cronjob einrichten:** Erstellen Sie ein PHP-Skript, das die `loadData`-Funktion aufruft, und führen Sie dieses Skript per Cronjob aus (z.B. alle 5 Minuten).

  **Beispiel für ein Import-Skript (`import.php`):**

  ```php
  <?php
  require_once 'includes/config.php'; // Pfad anpassen
  require_once 'classes/database.class.php';
  require_once 'classes/general.class.php';

  $sLogFile = '/var/log/squid/access.log.processed'; // Pfad zur Logdatei

  try {
      $oGeneral = new General();
      $oGeneral->loadData($sLogFile);
      echo "Daten erfolgreich importiert.\n";
  } catch (Exception $e) {
      die("Fehler beim Import: " . $e->getMessage() . "\n");
  }
  ?>
  ```

  **Beispiel für einen Cronjob:**

  ```crontab
  */5 * * * * /usr/bin/php /pfad/zu/squidloganalyzer/import.php > /dev/null 2>&1
  ```

### 2. Web-Interface aufrufen

Öffnen Sie die URL Ihrer Installation im Browser, um das Analyse-Dashboard zu sehen.

## 📜 Lizenz

Dieses Projekt steht unter der **GNU General Public License v3.0**. Die genauen Lizenzbedingungen finden Sie in der LICENSE-Datei.

---

Entwickelt von Axel Glitza.
