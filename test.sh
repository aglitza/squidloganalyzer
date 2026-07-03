#!/bin/bash
# squid-log-analyse.sh
# Beispielskript zur Auswertung eines Squid-Access-Logs

LOGFILE="/home/sgcorp-development/domains/sla.sgcorp-development.local/public_html/files/import/test2_access.log"

echo "=== Squid Log Analyse ==="
echo "Logfile: $LOGFILE"
echo

# 1. Top-Domains
echo ">> Top 10 Domains:"
awk '{print $7}' "$LOGFILE" | \
    awk -F/ '{if ($3!="") print $3}' | \
    sort | uniq -c | sort -nr | head -10
echo

# 2. Cache-Hit-Ratio
echo ">> Cache-Hit-Ratio:"
TOTAL=$(wc -l < "$LOGFILE")
HITS=$(grep -c "TCP_HIT" "$LOGFILE")
MISSES=$(grep -c "TCP_MISS" "$LOGFILE")
echo "Gesamt: $TOTAL"
echo "Hits:   $HITS"
echo "Misses: $MISSES"
echo "Hit-Ratio: $(echo "scale=2; $HITS*100/$TOTAL" | bc)%"
echo

# 3. Traffic pro Client-IP
echo ">> Traffic pro Client-IP (Top 10):"
awk '{print $3}' "$LOGFILE" | sort | uniq -c | sort -nr | head -10
