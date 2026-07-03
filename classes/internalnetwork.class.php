<?php

/**
 * Package: SGCorp Squid Logfile Analyzer
 * --------------------------------------
 * General Class
 * --------------------------------------
 * This class extends the class "Database" and provides general functions
 * to interact with the Squid logfile database.
 * --------------------------------------
 * Precondition (Installed Package)
 * ---> Samba (apt install samba)
 * ---> mDNS / Bonjour Lookup (apt install avahi-utils)
 * --------------------------------------
 * @author    Axel Glitza <axel@glitza.eu>
 * @copyright 2021 - 2026 Axel Glitza
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */


class InternalNetwork
{
    /**
     * Öffentliche Hauptfunktion:
     * Führt alle Lookup-Methoden in sinnvoller Reihenfolge aus.
     */
    public function resolveClientName(string $ip): string
    {
        // 0. Erreichbarkeit prüfen
        if (!$this->isReachable($ip)) {
            return $ip; // Keine Zeit verschwenden
        }

        // 1. DNS
        if ($dns = $this->getDnsName($ip)) {
            return $dns;
        }

        // 2. DHCP (Windows Server)
        if ($dhcp = $this->getDhcpHostname($ip)) {
            return $dhcp;
        }

        // 3. NetBIOS
        if ($nb = $this->getNetbiosName($ip)) {
            return $nb;
        }

        // 4. mDNS (Apple-Geräte)
        if ($mdns = $this->getMdnsName($ip)) {
            return $mdns;
        }

        // 5. Fallback
        return $ip;
    }

    /**
     * Prüft, ob der Client erreichbar ist (Ping + optional ARP)
     */
    private function isReachable(string $ip): bool
    {
        // 1. ARP (funktioniert auch bei iPhones im Standby)
        $arp = shell_exec("arping -c 1 -w 1 $ip 2>/dev/null");
        if ($arp && strpos($arp, "1 packets received") !== false) {
            return true;
        }

        // 2. ICMP Ping als Fallback
        $ping = shell_exec("ping -c 1 -W 1 $ip 2>/dev/null");
        if ($ping && strpos($ping, "1 received") !== false) {
            return true;
        }

        return false;
    }

    private function status(string $ip, string $msg): void
    {
        $data = json_encode(['ip' => $ip, 'message' => $msg]);
        $opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $data]];
        @file_get_contents("http://localhost:9002/push", false, stream_context_create($opts));
    }

    private function getDnsName(string $ip): ?string
    {
        $host = gethostbyaddr($ip);
        return ($host !== $ip) ? $host : null;
    }


    private function getDhcpHostname(string $ip): ?string
    {
        $cmd = 'powershell -Command "'
            . 'try { '
            . '  $lease = Get-DhcpServerv4Lease -IPAddress ' . $ip . '; '
            . '  if ($lease -ne $null) { $lease.HostName } '
            . '} catch { }'
            . '"';

        $output = trim(shell_exec($cmd));

        return $output !== "" ? $output : null;
    }


    private function getNetbiosName(string $ip): ?string
    {
        $cmd = "nmblookup -A $ip 2>/dev/null";
        $output = shell_exec($cmd);

        if (!$output) {
            return null;
        }

        if (preg_match('/^\s*([A-Za-z0-9\-\_]+)\s+<00>.*UNIQUE/im', $output, $m)) {
            return trim($m[1]);
        }

        return null;
    }


    private function getMdnsName(string $ip): ?string
    {
        $cmd = "avahi-resolve-address $ip 2>/dev/null";
        $output = trim(shell_exec($cmd));

        if (!$output) {
            return null;
        }

        $parts = preg_split('/\s+/', $output);

        return $parts[1] ?? null;
    }
}

