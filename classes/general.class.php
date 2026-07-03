<?php

/**
 * Package: SGCorp Squid Logfile Analyzer
 * --------------------------------------
 * General Class
 * --------------------------------------
 * This class extends the class "Database" and provides general functions
 * to interact with the Squid logfile database.
 * --------------------------------------
 * @author    Axel Glitza <axel@glitza.eu>
 * @copyright 2021 - 2026 Axel Glitza
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

class General extends Database
{
    /*
     ****************************************************************************************
     * Name of function: buildFilterClause
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $sFilterName (optional), $sFilterValue (optional), $sClause (optional)
     * Response of function: ARRAY
     * Description:
     * This private helper function builds a secure WHERE or AND clause for SQL queries
     * using a whitelist for filterable columns.
     ****************************************************************************************
     */
    private function buildFilterClause($sFilterName, $sFilterValue, $sClause = 'WHERE')
    {
        if (empty($sFilterName) || empty($sFilterValue)) {
            return ["", []];
        }

        $allowedFilters = ['log_time', 'agent', 'client_ip', 'method'];
        if (!in_array($sFilterName, $allowedFilters)) {
            // Optionally, throw an exception or log an error for invalid filter names
            return ["", []];
        }

        $sAdditionalFilter = "";
        $aParameters = [];

        if ($sFilterName === "log_time") {
            $sAdditionalFilter = " $sClause DATE(FROM_UNIXTIME(log_time)) = ?";
            $aParameters[] = $sFilterValue;
        } elseif ($sFilterName === "agent") {
            $sAdditionalFilter = " $sClause UPPER(user_agent) = ?";
            $aParameters[] = strtoupper($sFilterValue);
        } else {
            $sAdditionalFilter = " $sClause " . $sFilterName . " = ?";
            $aParameters[] = $sFilterValue;
        }
        return [$sAdditionalFilter, $aParameters];
    }

    /*
     ****************************************************************************************
     * Name of function: generateSortLink
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $column, $title, $currentSortColumn, $currentSortOrder
     * Response of function: STRING (HTML)
     * Description:
     * This private helper function generates an HTML link for a table header to enable sorting.
     * It preserves existing filter parameters.
     ****************************************************************************************
     */
    private function generateSortLink($column, $title, $currentSortColumn, $currentSortOrder, $fragment = '')
    {
        $newOrder = ($column === $currentSortColumn && $currentSortOrder === 'ASC') ? 'DESC' : 'ASC';

        // Preserve existing filter parameters
        $queryParams = [];
        if (isset($_GET['filter']) && isset($_GET['value'])) {
            $queryParams['filter'] = $_GET['filter'];
            $queryParams['value'] = $_GET['value'];
        }

        // Add sorting parameters
        $queryParams['sort'] = $column;
        $queryParams['order'] = $newOrder;

        $queryString = http_build_query($queryParams);

        $arrow = '';
        if ($column === $currentSortColumn) {
            if ($currentSortOrder === 'ASC') {
                $arrow = ' &uarr;'; // Up arrow
            } else {
                $arrow = ' &darr;'; // Down arrow
            }
        }

        $href = '?' . $queryString;
        if (!empty($fragment)) {
            $href .= '#' . ltrim($fragment, '#');
        }

        return '<a href="' . $href . '" class="text-warning">' . htmlspecialchars($title) . $arrow . '</a>';
    }

    /*
     ****************************************************************************************
     * Name of function: getDomainsPerDay
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $sFilterName (optional), $sFilterValue (optional)
     * Response of function: ARRAY
     * Description:
     * This function retrieves the number of hits, total size, and average size
     * of responses per domain per day from the Squid access log for the last 7 days. It is
     * possible to filter the results based on a specific field and value.
     ****************************************************************************************
     */
    public function getDomainsPerDay($sFilterName = null, $sFilterValue = null)
    {
        list($sAdditionalFilter, $aParameters) = $this->buildFilterClause($sFilterName, $sFilterValue, 'AND');

        // Get data from the database
        $sql = "SELECT DATE(FROM_UNIXTIME(log_time)) AS day, client_ip, SUBSTRING_INDEX(SUBSTRING_INDEX(url, '/', 3), '/', -1) AS domain, method, COUNT(*) AS hits, SUM(response_size) AS total_size, AVG(response_size) AS avg_size FROM squid_access_log WHERE log_time >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY)) $sAdditionalFilter  GROUP BY day, client_ip, domain, method ORDER BY day DESC, hits DESC;";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($aParameters);

        // Return the fetched data
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     ****************************************************************************************
     * Name of function: getDomainsPerDayTops
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $sFilterName (optional), $sFilterValue (optional)
     * Response of function: ARRAY
     * Description:
     * This function retrieves the top 20 domains per day based on the number of hits
     * from the Squid access log for the last 7 days. It is possible to filter the results
     * based on a specific field and value.
     ****************************************************************************************
     */
    public function getDomainsPerDayTops($sFilterName = null, $sFilterValue = null)
    {
        list($sAdditionalFilter, $aParameters) = $this->buildFilterClause($sFilterName, $sFilterValue, 'AND');

        // Get data from the database
        $sql = "SELECT DATE(FROM_UNIXTIME(log_time)) AS day, client_ip, SUBSTRING_INDEX(SUBSTRING_INDEX(url, '/', 3), '/', -1) AS domain, method, COUNT(*) AS hits, SUM(response_size) AS total_size, AVG(response_size) AS avg_size FROM squid_access_log WHERE log_time >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY)) $sAdditionalFilter GROUP BY day, client_ip, domain, method ORDER BY day DESC, hits DESC LIMIT 20;";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($aParameters);

        // Return the fetched data
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     ****************************************************************************************
     * Name of function: getHitsType
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $sFilterName (optional), $sFilterValue (optional)
     * Response of function: ARRAY
     * Description:
     * This function retrieves the number of hits per hit ratio type
     ****************************************************************************************
     */
    public function getHitsType($sFilterName = null, $sFilterValue = null, $sSortColumn = 'total_hits', $sSortOrder = 'DESC')
    {
        list($sAdditionalFilter, $aParameters) = $this->buildFilterClause($sFilterName, $sFilterValue);

        // Whitelist for sortable columns
        $allowedSortColumns = ['hitratio', 'total_hits'];
        if (!in_array($sSortColumn, $allowedSortColumns)) {
            $sSortColumn = 'total_hits';
        }

        // Whitelist for sort order
        $sSortOrder = strtoupper($sSortOrder) === 'ASC' ? 'ASC' : 'DESC';

        // Get data from the database
        $sql = "SELECT hitratio, COUNT(*) AS total_hits FROM squid_access_log " . $sAdditionalFilter . " GROUP BY hitratio ORDER BY " . $sSortColumn . " " . $sSortOrder . ";";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($aParameters);

        // Return the fetched data
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     ****************************************************************************************
     * Name of function: getMethods
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $sFilterName (optional), $sFilterValue (optional)
     * Response of function: ARRAY
     * Description:
     * This function retrieves the number of hits and total bytes transferred per HTTP method
     * from the Squid access log. It is possible to filter the results based on a specific
     * field and value.
     ****************************************************************************************
     */
    public function getMethods($sFilterName = null, $sFilterValue = null)
    {
        list($sAdditionalFilter, $aParameters) = $this->buildFilterClause($sFilterName, $sFilterValue);

        // Get data from the database
        $sql = "SELECT log_time, method, COUNT(*) AS total_hits, SUM(response_size) AS total_bytes FROM squid_access_log $sAdditionalFilter GROUP BY method ORDER BY total_hits DESC;";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($aParameters);

        // Return the fetched data
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     ****************************************************************************************
     * Name of function: getHTMLTableMethods
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $aTopDataPerClient
     * Response of function: VOID
     * Description:
     * This function generates an HTML table displaying the top methods
     * based on data volume from the provided data array. If filter parameters are
     * provided via GET request, they are applied to the table display.
     ****************************************************************************************
     */
    public function getHTMLTableAgents($aTopUserAgents, $sFilterInfo, $sSortColumn = 'hits', $sSortOrder = 'DESC')
    {
        // Headline
        echo '<h2 class="mb-3">Top 20 User Agents nach Hits der letzten 7 Tage' . $sFilterInfo . '</h2>';

        // Start building the HTML table
        echo '<table class="table table-dark table-bordered table-sm table-hover">';
        echo '    <thead class="table-dark">';
        echo '        <tr>';
        echo '            <th scope="col">' . $this->generateSortLink('user_agent', 'USER AGENT:', $sSortColumn, $sSortOrder, 'Top20AgentsTable') . '</th>';
        echo '            <th scope="col" class="text-end">' . $this->generateSortLink('total_size', 'DATEN (BYTES):', $sSortColumn, $sSortOrder, 'Top20AgentsTable') . '</th>';
        echo '            <th scope="col" class="text-end">' . $this->generateSortLink('hits', 'ANZAHL:', $sSortColumn, $sSortOrder, 'Top20AgentsTable') . '</th>';
        echo '        </tr>';
        echo '    </thead>';
        echo '    <tbody>';

        // Initialize total counters
        $iTotal_Hits = 0;
        $iTotal_Bytes = 0;
        $iOther_Agents = 0;
        $iOther_Agents_Bytes = 0;
        $iOther_Agents_Hits = 0;
        $iCounter = 0;

        // Loop through each row of data and display it in the table
        foreach ($aTopUserAgents as $row):
            if ($iCounter >= 20) {
                $iOther_Agents++;
                $iOther_Agents_Bytes += $row['total_size'];
                $iOther_Agents_Hits += $row['hits'];
                continue;
            }
            $iTotal_Hits += $row['hits'];
            $iTotal_Bytes += $row['total_size'];

            echo '        <tr>';
            echo '            <td style="max-width:400px; word-wrap:break-word;">';
            echo '<a href="/?filter=agent&value=' . urlencode($row['user_agent']) . '" class="text-warning">' . htmlspecialchars(urldecode($row['user_agent'] ?? 'N/A')) . '</a>';
            echo '            </td>';
            echo '            <td class="text-end">' . number_format($row['total_size'], 0, ',', '.') . '</td>';
            echo '            <td class="text-end">' . number_format($row['hits'], 0, ',', '.') . '</td>';
            echo '        </tr>';
            $iCounter++;
        endforeach;

        // Close the table and display total values
        echo '        <tr>';
        echo '            <td><strong>ANDERE AGENTS (' . $iOther_Agents . '):</strong></td>';
        echo '            <td class="text-end"><strong>' . number_format($iOther_Agents_Bytes, 0, ',', '.') . '</strong></td>';
        echo '            <td class="text-end"><strong>' . number_format($iOther_Agents_Hits, 0, ',', '.') . '</strong></td>';
        echo '        </tr>';
        echo '        <tr>';
        echo '            <td><strong>KUMULIERTE WERTE (GESAMT):</strong></td>';
        echo '            <td class="text-end"><strong>' . number_format($iTotal_Bytes, 0, ',', '.') . '</strong></td>';
        echo '            <td class="text-end"><strong>' . number_format($iTotal_Hits, 0, ',', '.') . '</strong></td>';
        echo '        </tr>';
        echo '    </tbody>';
        echo '</table>';
    }

    /*
     ****************************************************************************************
     * Name of function: getHTMLTableClients
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $aTopDataPerClient
     * Response of function: VOID
     * Description:
     * This function generates an HTML table displaying the top clients
     * based on data volume from the provided data array. If filter parameters are
     * provided via GET request, they are applied to the table display.
     ****************************************************************************************
     */
    public function getHTMLTableClients($aTopDataPerClient, $sFilterInfo, $sSortColumn = 'total_bytes', $sSortOrder = 'DESC')
    {
        // Headline
        echo '<h2 class="mb-3">Top Clients nach Datenvolumen der letzten 7 Tage' . $sFilterInfo . '</h2>';

        // Start building the HTML table
        echo '<table class="table table-dark table-bordered table-sm table-hover">';
        echo '    <thead class="table-dark">';
        echo '        <tr>';
        echo '            <th scope="col">' . $this->generateSortLink('client_ip', 'CLIENT-NAME (IP-ADRESSE):', $sSortColumn, $sSortOrder, 'TopClientsTable') . '</th>';
        echo '            <th scope="col" class="text-end">' . $this->generateSortLink('total_bytes', 'DATEN (BYTES):', $sSortColumn, $sSortOrder, 'TopClientsTable') . '</th>';
        echo '            <th scope="col" class="text-end">' . $this->generateSortLink('total_hits', 'ANZAHL:', $sSortColumn, $sSortOrder, 'TopClientsTable') . '</th>';
        echo '        </tr>';
        echo '    </thead>';
        echo '    <tbody>';

        // Initialize total counters
        $iTotal_Bytes = 0;
        $iTotal_Hits = 0;

        $oInternalNet = new InternalNetwork();

        // Loop through each row of data and display it in the table
        foreach ($aTopDataPerClient as $row):
            $hostname = $oInternalNet->resolveClientName($row['client_ip']);
            //$hostname = gethostbyaddr($row['client_ip']);
            if ($hostname === $row['client_ip']) {
                $hostname = $row['client_ip'];
            }
            $iTotal_Bytes += $row['total_bytes'];
            $iTotal_Hits += $row['total_hits'];
            echo '        <tr>';
            echo '            <td>';
            echo '<a href="/?filter=client_ip&value=' . $row['client_ip'] . '" class="text-warning">' . strtolower(htmlspecialchars($hostname)) . '&nbsp;(' . $row['client_ip'] . ')</a>';
            echo '            </td>';
            echo '            <td class="text-end">' . number_format($row['total_bytes'], 0, ',', '.') . '</td>';
            echo '            <td class="text-end">' . number_format($row['total_hits'], 0, ',', '.') . '</td>';
            echo '        </tr>';
        endforeach;

        // Close the table and display total values
        echo '        <tr>';
        echo '            <td><strong>KUMULIERTE WERTE (GESAMT):</strong></td>';
        echo '            <td class="text-end"><strong>' . number_format($iTotal_Bytes, 0, ',', '.') . '</strong></td>';
        echo '            <td class="text-end"><strong>' . number_format($iTotal_Hits, 0, ',', '.') . '</strong></td>';
        echo '        </tr>';
        echo '    </tbody>';
        echo '</table>';
    }

    /*
     ****************************************************************************************
     * Name of function: getHTMLTableDomainsPerDay
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $aDomainsPerDay, $iStartLoading, $sFilterInfo
     * Response of function: VOID
     * Description:
     * This function generates an HTML table displaying domains accessed per day
     * from the provided data array, along with performance measurement. If filter
     * parameters are provided via GET request, they are applied to the table display.
     ****************************************************************************************
     */
    public function getHTMLTableDomainsPerDay($aDomainsPerDay, $iStartLoading, $sFilterInfo)
    {
        // Headline
        echo '<h2 class="mb-3 sccorpScrolling">Domains der letzten 7 Tage - Neueste zuerst' . $sFilterInfo . '</h2>';

        // Start counting entries
        $iCounter = 1;
        $iDataVolume = 0;
        $iDataVolumeAvg = 0;
        $iHits = 0;

        // Start building the HTML table
        echo '<table class="table table-dark table-bordered table-sm table-hover">';
        echo '    <thead class="table-dark">';
        echo '        <tr>';
        echo '            <th scope="col" class="text-center">DATUM:</th>';
        echo '            <th scope="col">AUFGERUFENE DOMÄNE:</th>';
        echo '            <th scope="col" class="text-center">METHODE:</th>';
        echo '            <th scope="col" class="text-center">DATEN (BYTES):</th>';
        echo '            <th scope="col" class="text-center">DATEN (AVG):</th>';
        echo '            <th scope="col" class="text-center">ANZAHL:</th>';
        echo '            <th scope="col" class="text-center">CLIENT (IP):</th>';
        echo '        </tr>';
        echo '    </thead>';
        echo '    <tbody>';

        // Loop through each row of data and display it in the table
        foreach ($aDomainsPerDay as $row):
            echo '        <tr>';
            echo '            <td class="text-center">';
            echo '<a href="/?filter=log_time&value=' . $row['day'] . '" class="text-warning">' . htmlspecialchars(date("d.m.Y", strtotime($row['day']))) . '</a>';
            echo '            </td>';
            echo '            <td>';
            echo '<a href="https://' . htmlspecialchars($row['domain']) . '" class="text-warning" target="_blank">' . htmlspecialchars($row['domain']) . '</a>';
            echo '            </td>';
            echo '            <td class="text-center">';
            echo '<a href="/?filter=method&value=' . strtoupper($row['method']) . '" class="text-warning">' . htmlspecialchars($row['method'] ?? 'N/A') . '</a>';
            echo '            </td>';
            echo '            <td class="text-center">';
            echo htmlspecialchars(number_format($row['total_size'], 0, ',', '.') ?? 'N/A');
            echo '            </td>';
            echo '            <td class="text-center">';
            echo htmlspecialchars(number_format(floor($row['avg_size']), 0, ',', '.') ?? 'N/A');
            echo '            </td>';
            echo '            <td class="text-center">';
            echo htmlspecialchars(number_format($row['hits'], 0, ',', '.'));
            echo '            </td>';
            echo '            <td class="text-center">';
            echo '<a href="/?filter=client_ip&value=' . $row['client_ip'] . '" class="text-warning">' . htmlspecialchars($row['client_ip']) . '</a>';
            echo '            </td>';
            echo '        </tr>';

            // Count entries
            $iDataVolume += $row['total_size'];
            $iDataVolumeAvg += floor($row['avg_size']);
            $iHits += $row['hits'];
            $iCounter++;
        endforeach;

        // Calculate end time for performance measurement
        $iStopLoading = microtime(true);

        // Calculate duration
        $iDuration = $iStopLoading - $iStartLoading;

        // Close the table and display performance info
        echo '        <tr>';
        echo '            <td class="text-center"><strong>---</strong></td>';
        echo '            <td><strong>---</strong></td>';
        echo '            <td class="text-center"><strong>---</strong></td>';
        echo '            <td class="text-center"><strong>' . number_format($iDataVolume, 0, ',', '.') . '</strong></td>';
        echo '            <td class="text-center"><strong>' . number_format($iDataVolumeAvg, 0, ',', '.') . '</strong></td>';
        echo '            <td class="text-center"><strong>' . number_format($iHits, 0, ',', '.') . '</strong></td>';
        echo '            <td class="text-center"><strong>---</strong></td>';
        echo '        </tr>';
        echo '        <tr>';
        echo '            <td colspan="7" class="text-center">';
        echo '                <strong>';
        echo '                    Diese Liste enthält <b><span class="text-warning">' . $iCounter . '</span></b> Einträge&nbsp;-&nbsp;Seitenladezeit: ' . number_format($iDuration, 4) . ' Sekunden&nbsp;-&nbsp;<a href="#" class="text-warning">Nach oben</a>';
        echo '                </strong>';
        echo '            </td>';
        echo '        </tr>';
        echo '    </tbody>';
        echo '</table>';
    }

    /*
     ****************************************************************************************
     * Name of function: getHTMLTableHitRatio
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $aMethodHitType
     * Response of function: VOID
     * Description:
     * This function generates an HTML table displaying the top clients
     * based on data volume from the provided data array. If filter parameters are
     * provided via GET request, they are applied to the table display.
     ****************************************************************************************
     */
    public function getHTMLTableHitRatio($aMethodHitType, $sFilterInfo, $sSortColumn = 'total_bytes', $sSortOrder = 'DESC')
    {
        // Headline
        echo '<h2 class="mb-3">Treffer auf dem Proxy-Server der letzten 7 Tage' . $sFilterInfo . '</h2>';

        // Start building the HTML table
        echo '<table class="table table-dark table-bordered table-sm table-hover">';
        echo '    <thead class="table-dark">';
        echo '        <tr>';
        echo '            <th scope="col">' . $this->generateSortLink('hitratio', 'HIT METHODE:', $sSortColumn, $sSortOrder, 'HitRatio') . '</th>';
        echo '            <th scope="col" class="text-end">' . $this->generateSortLink('hitratio', 'PROZENT:', $sSortColumn, $sSortOrder, 'HitRatio') . '</th>';
        echo '            <th scope="col" class="text-end">' . $this->generateSortLink('total_hits', 'ANZAHL:', $sSortColumn, $sSortOrder, 'HitRatio') . '</th>';
        echo '        </tr>';
        echo '    </thead>';
        echo '    <tbody>';

        // Initialize total counters
        $iTotal_Hits = 0;
        $iOther_Hits = 0;
        $iGrandTotalHits = array_sum(array_column($aMethodHitType, 'total_hits'));
        $aHitMethods = [
            'HIT',
            'MISS',
            'REFRESH_HIT',
            'IMS_HIT',
            'TCP_HIT',
            'TCP_IMS_HIT',
            'UDP_HIT',
            'UDP_IMS_HIT',
            'OFFLINE_HIT',
            'STORE_HIT',
            'SYNCHRONOUS_HIT',
            'SYNCHRONOUS_IMS_HIT',
            'NEGATIVE_HIT',
            'DIRTY_HIT',
            'NONE_NONE',
            'TCP_MISS',
            'TCP_REFRESH_MISS',
            'TCP_REFRESH_MODIFIED',
            'TCP_INM_HIT',
            'TCP_REFRESH_UNMODIFIED'
        ];

        // Loop through each row of data and display it in the table
        foreach ($aMethodHitType as $row):
            $iTotal_Hits += $row['total_hits'];
            if (in_array($row['hitratio'], $aHitMethods)) {
                $fPercent = ($iGrandTotalHits > 0) ? ($row['total_hits'] / $iGrandTotalHits) * 100 : 0;
                echo '        <tr>';
                echo '            <td>';
                echo '<a href="/?filter=hitratio&value=' . $row['hitratio'] . '" class="text-warning">' . $row['hitratio'] . '</a>';
                echo '            </td>';
                echo '            <td class="text-end">' . number_format($fPercent, 2, ',', '.') . '%</td>';
                echo '            <td class="text-end">' . number_format($row['total_hits'], 0, ',', '.') . '</td>';
                echo '        </tr>';
            } else {
                $iOther_Hits += $row['total_hits'];
            }
        endforeach;

        if ($iOther_Hits > 0) {
            $fPercentOther = ($iGrandTotalHits > 0) ? ($iOther_Hits / $iGrandTotalHits) * 100 : 0;
            echo '        <tr>';
            echo '            <td><strong>Other</strong></td>';
            echo '            <td class="text-end">' . number_format($fPercentOther, 2, ',', '.') . '%</td>';
            echo '            <td class="text-end">' . number_format($iOther_Hits, 0, ',', '.') . '</td>';
            echo '        </tr>';
        }

        // Close the table and display total values
        echo '        <tr>';
        echo '            <td><strong>KUMULIERTE WERTE (GESAMT):</strong></td>';
        echo '            <td class="text-end"><strong>100,00%</strong></td>';
        echo '            <td class="text-end"><strong>' . number_format($iTotal_Hits, 0, ',', '.') . '</strong></td>';
        echo '        </tr>';
        echo '    </tbody>';
        echo '</table>';
    }

    /*
     ****************************************************************************************
     * Name of function: getHTMLTableMethods
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $aTopDataPerClient
     * Response of function: VOID
     * Description:
     * This function generates an HTML table displaying the top methods
     * based on data volume from the provided data array. If filter parameters are
     * provided via GET request, they are applied to the table display.
     ****************************************************************************************
     */
    public function getHTMLTableMethods($aUsedMethods, $sFilterInfo, $sSortColumn = 'total_hits', $sSortOrder = 'DESC')
    {
        // Headline
        echo '<h2 class="mb-3">Top Methoden nach Hits der letzten 7 Tage' . $sFilterInfo . '</h2>';

        // Start building the HTML table
        echo '<table class="table table-dark table-bordered table-sm table-hover">';
        echo '    <thead class="table-dark">';
        echo '        <tr>';
        echo '            <th scope="col">' . $this->generateSortLink('method', 'METHODE:', $sSortColumn, $sSortOrder, 'MethodsDaysTable') . '</th>';
        echo '            <th scope="col" class="text-end">' . $this->generateSortLink('total_hits', 'ANZAHL:', $sSortColumn, $sSortOrder, 'MethodsDaysTable') . '</th>';
        echo '        </tr>';
        echo '    </thead>';
        echo '    <tbody>';

        // Initialize total counters
        $iTotal_Hits = 0;

        // Loop through each row of data and display it in the table
        foreach ($aUsedMethods as $row):
            $iTotal_Hits += $row['total_hits'];
            echo '        <tr>';
            echo '            <td>';
            echo '<a href="/?filter=method&value=' . strtoupper($row['method']) . '" class="text-warning">' . htmlspecialchars($row['method'] ?? 'N/A') . '</a>';
            echo '            </td>';
            echo '            <td class="text-end">' . number_format($row['total_hits'], 0, ',', '.') . '</td>';
            echo '        </tr>';
        endforeach;

        // Close the table and display total values
        echo '        <tr>';
        echo '            <td><strong>KUMULIERTE WERTE (GESAMT):</strong></td>';
        echo '            <td class="text-end"><strong>' . number_format($iTotal_Hits, 0, ',', '.') . '</strong></td>';
        echo '        </tr>';
        echo '    </tbody>';
        echo '</table>';
    }

    /*
     ****************************************************************************************
     * Name of function: getTrafficPerClient
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $sFilterName (optional), $sFilterValue (optional)
     * Response of function: ARRAY
     * Description:
     * This function retrieves the total number of hits and total bytes transferred
     * per client IP address from the Squid access log. It is possible to filter the results
     * based on a specific field and value.
     ****************************************************************************************
     */
    public function getTrafficPerClient($sFilterName = null, $sFilterValue = null, $sSortColumn = 'total_bytes', $sSortOrder = 'DESC')
    {
        list($sAdditionalFilter, $aParameters) = $this->buildFilterClause($sFilterName, $sFilterValue);

        // Whitelist for sortable columns to prevent SQL injection
        $allowedSortColumns = ['client_ip', 'total_hits', 'total_bytes'];
        if (!in_array($sSortColumn, $allowedSortColumns)) {
            $sSortColumn = 'total_bytes'; // Default to a safe value
        }

        // Whitelist for sort order
        $sSortOrder = strtoupper($sSortOrder) === 'ASC' ? 'ASC' : 'DESC';


        // Get data from the database
        $sql = "SELECT log_time, method, client_ip, COUNT(*) AS total_hits, SUM(response_size) AS total_bytes FROM squid_access_log " . $sAdditionalFilter . " GROUP BY client_ip ORDER BY " . $sSortColumn . " " . $sSortOrder . ";";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($aParameters);

        // Return the fetched data
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     ****************************************************************************************
     * Name of function: getUsedUserAgents
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $sFilterName (optional), $sFilterValue (optional)
     * Response of function: ARRAY
     * Description:
     * This function retrieves the total number of hits and total bytes transferred
     * per client IP address from the Squid access log.
     ****************************************************************************************
     */
    public function getUsedUserAgents($sFilterName, $sFilterValue, $sSortColumn = 'hits', $sSortOrder = 'DESC')
    {
        list($sAdditionalFilter, $aParameters) = $this->buildFilterClause($sFilterName, $sFilterValue);

        // Whitelist for sortable columns
        $allowedSortColumns = ['user_agent', 'total_size', 'hits'];
        if (!in_array($sSortColumn, $allowedSortColumns)) {
            $sSortColumn = 'hits'; // Default
        }

        // Whitelist for sort order
        $sSortOrder = strtoupper($sSortOrder) === 'ASC' ? 'ASC' : 'DESC';

        // Get data from the database
        $sql = "SELECT log_time, method, user_agent, COUNT(*) AS hits, SUM(response_size) AS total_size, AVG(response_size) AS avg_size FROM squid_access_log $sAdditionalFilter GROUP BY user_agent ORDER BY $sSortColumn $sSortOrder;";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($aParameters);

        // Return the fetched data
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     ****************************************************************************************
     * Name of function: loadData
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * $sSourceFile
     * Response of function: VOID
     * Description:
     * This function loads data from a specified source file into the Squid access log
     * database table. It first deletes old entries older than 7 days before loading new data.
     ****************************************************************************************
     */
    public function loadData($sSourceFile)
    {
        // Check if the source file exists and create a database connection
        if (!file_exists($sSourceFile)) {
            throw new Exception("Die Datei $sSourceFile wurde nicht gefunden.");
        }
        // Delete old entries older than 7 days
        $sql = "DELETE FROM squid_access_log WHERE log_time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY));";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        echo date("Y-m-d H:i:s") . " Datenbereinigung (letzte 7 Tage): " . $stmt->rowCount() . " Einträge wurden gelöscht.\n";

        // Write new data to the table
        $sql = "LOAD DATA LOCAL INFILE '$sSourceFile' IGNORE INTO TABLE squid_access_log FIELDS TERMINATED BY ';' LINES TERMINATED BY '\n' (log_time, hitratio, client_ip, username, method, url, status_code, response_size, response_time, user_agent);";
        $this->getConnection()->exec($sql);
    }
}