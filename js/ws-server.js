/*
 *******************************************************
 * Verwendete Zertifikate:
 * /home/sgcorp-development/SGCorpAPS02CertStore/sla.pem
 * /home/sgcorp-development/SGCorpAPS02CertStore/sla.key
 *******************************************************
 */

// Bessere Methode: Pfade aus Umgebungsvariablen laden
// In Ihrer Shell setzen:
// export WS_CERT_PATH='/home/sgcorp-development/SGCorpAPS02CertStore/sla.pem'
// export WS_CERT_KEY_PATH='/home/sgcorp-development/SGCorpAPS02CertStore/sla.key'
const wsCert =
  process.env.WS_CERT_PATH ||
  "/home/sgcorp-development/SGCorpAPS02CertStore/sla.pem";
const wsCertKey =
  process.env.WS_CERT_KEY_PATH ||
  "/home/sgcorp-development/SGCorpAPS02CertStore/sla.key";

/* ***************************************************************************** */

import { WebSocketServer } from "ws";
import https from "https";
import fs from "fs";

// SSL/TLS Zertifikate laden
const server = https.createServer({
  cert: fs.readFileSync(wsCert), // Pfad zum SSL-Zertifikat
  key: fs.readFileSync(wsCertKey), // Pfad zum privaten Schlüssel
});

const wss = new WebSocketServer({ server });

wss.on("connection", (ws) => {
  console.log("Client connected");

  ws.on("message", (message) => {
    console.log(`Received message: ${message}`);

    // FIX: Sende JSON statt reinem Text, damit main.js es parsen kann
    const response = {
      ip: "server-echo", // Beispiel-Wert, da main.js data.ip erwartet
      message: `Server received: ${message}`,
    };

    ws.send(JSON.stringify(response));
  });

  ws.on("close", () => {
    console.log("Client disconnected");
  });
});

server.listen(9001, "0.0.0.0", () => {
  console.log(
    "WebSocket server running on wss://sla.sgcorp-development.local:9001",
  );
});
