<?php

/**
 * Package: SGCorp Squid Logfile Analyzer
 * --------------------------------------
 * Main Page
 * --------------------------------------
 * @author    Axel Glitza <axel@glitza.eu>
 * @copyright 2021 - 2026 Axel Glitza
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

// Save start time for performance measurement
$iStartLoading = microtime(true);

// Load configuration file(s)
include __DIR__ . "/includes/configuration.includes.php";
include __DIR__ . "/classes-autoload.php";

// Initialize database and general objects and get database connection
$oDatabase = new Database();
$oGeneral = new General();
$oDatabase->getConnection();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGCorp: Squid Logfile Analyzer</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Internal Website CSS -->
    <link rel="stylesheet" href="css/general.css">
</head>

<body>
    <!-- Spinner -->
    <div class="sgcorp-spinner-wrapper">
        <div class="spinner-border text-primary sgcorp-spinner-border" role="status"></div>
        <div class="d-flex justify-content-center align-items-center text-light" style="height: 100vh;">
            &nbsp;Lade Informationen ...
        </div>
    </div>

    <!-- Navigation Bar -->
    <header class="p-3 text-bg-dark sticky-top">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
                <a href="/"><img class="sgcorpLogo" src="images/sgcorp_squid_sla_white.png" /></a>
                <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                    <li><a href="/" class="nav-link px-2 text-white"><i class="fa fa-arrow-circle-up"
                                aria-hidden="true"></i></a></li>
                    <li><a href="#TopClientsTable" class="nav-link px-2 text-white">Clients</a></li>
                    <li><a href="#Top20AgentsTable" class="nav-link px-2 text-white">Agents>A</a></li>
                    <li><a href="#UserAgentsChart" class="nav-link px-2 text-white">A>Chart</a></li>
                    <li><a href="#MethodsDaysTable" class="nav-link px-2 text-white">Methods>M</a></li>
                    <li><a href="#MethodsChart" class="nav-link px-2 text-white">M>Chart</a></li>
                    <li><a href="#Top20DomainsChart" class="nav-link px-2 text-white">Domains>D</a></li>
                    <li><a href="#DomainsDaysChart" class="nav-link px-2 text-white">D>Chart</a></li>
                    <li><a href="#DomainsDaysTable" class="nav-link px-2 text-white">D>List</a></li>
                    <li><a href="#EndOfPageContent" class="nav-link px-2 text-white"><i class="fa fa-arrow-circle-down"
                                aria-hidden="true"></i></a></li>
                </ul>
            </div>
        </div>
    </header>

    <h1 class="mb-4 sgcorpDisplayNone">SGCorp: Squid Logfile Analyzer</h1>

    <?php
    /**
     * Call necessary data from database and try to get data.
     * In case of an error the script terminates with error-message.
     */
    try {
        // Check for filter parameters in GET request
        if (isset($_GET['filter']) && isset($_GET['value'])) {
            $sFilterName = htmlspecialchars($_GET['filter']); // for display and logic
            $sFilterValue = $_GET['value']; // for db query
            $sDisplayFilterValue = htmlspecialchars(urldecode($sFilterValue)); // for display
            $sFilterInfo = " <br />(Filter aktiv: '" . $sFilterName . "' = '" . $sDisplayFilterValue . "')";
        } else {
            $sFilterName = null;
            $sFilterValue = null;
            $sFilterInfo = "";
        }

        // Handle sorting parameters
        $sSortColumn = $_GET['sort'] ?? 'total_bytes'; // Default sort column
        $sSortOrder = $_GET['order'] ?? 'DESC'; // Default sort order
    
        // Load necessary data from database
        // Pass sorting parameters to the relevant function
        $aTopUserAgents = $oGeneral->getUsedUserAgents($sFilterName, $sFilterValue, $sSortColumn, $sSortOrder);
        $aDomainsPerDay = $oGeneral->getDomainsPerDay($sFilterName, $sFilterValue);
        $aDomainsPerDayTops = $oGeneral->getDomainsPerDayTops($sFilterName, $sFilterValue);
        $aTopDataPerClient = $oGeneral->getTrafficPerClient($sFilterName, $sFilterValue, $sSortColumn, $sSortOrder);
        $aUsedMethods = $oGeneral->getMethods($sFilterName, $sFilterValue);
        $aMethodLabels = array_column($aUsedMethods, 'method');
        $aMethodHitCounts = array_column($aUsedMethods, 'total_hits');
        $aMethodHitType = $oGeneral->getHitsType($sFilterName, $sFilterValue, $sSortColumn, $sSortOrder);
    } catch (Exception $e) {
        echo "Fehler: " . $e->getMessage();
        die;
    }
    ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <!-- Call HTML table of top clients per data volume -->
                <div class="sccorpScrolling" id="TopClientsTable"></div>
                <?php echo $oGeneral->getHTMLTableClients($aTopDataPerClient, $sFilterInfo, $sSortColumn, $sSortOrder); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <!-- Call HTML table of top HIT RATIO -->
                <div class="sccorpScrolling" id="HitRatio"></div>
                <?php echo $oGeneral->getHTMLTableHitRatio($aMethodHitType, $sFilterInfo, $sSortColumn, $sSortOrder); ?>
            </div>
        </div>
        <div class="row">
            <!-- Chart.js Genutzte Agents -->
            <div class="col-md-6">
                <div class="sccorpScrolling" id="Top20AgentsTable"></div>
                <?php echo $oGeneral->getHTMLTableAgents($aTopUserAgents, $sFilterInfo, $sSortColumn, $sSortOrder); ?>
            </div>
            <!-- Chart.js Genutzte User Agents -->
            <div class="col-md-6">
                <div class="sccorpScrolling" id="UserAgentsChart"></div>
                <h2 class="mb-4 text-center">User Agents der letzten 7 Tage<?php echo $sFilterInfo; ?></h2>
                <canvas id="userAgentPie" width="400" height="100" style="max-width:1024px; max-height:400px;"></canvas>
            </div>
        </div>
        <hr>
        <div class="row">
            <!-- Chart.js Genutzte Methoden -->
            <div class="sccorpScrolling" id="MethodsDaysTable"></div>
            <div class="col-md-6">
                <div class="sccorpScrolling" id="MethodsDaysTable"></div>
                <?php echo $oGeneral->getHTMLTableMethods($aUsedMethods, $sFilterInfo, $sSortColumn, $sSortOrder); ?>
            </div>
            <div class="col-md-6">
                <div class="sccorpScrolling" id="MethodsChart"></div>
                <h2 class="mb-4 text-center">Verteilung der Methoden nach Hits<?php echo $sFilterInfo; ?></h2>
                <canvas id="methodUsageChart" width="400" height="100"
                    style="max-width:1024px; max-height:400px;"></canvas>
            </div>
        </div>
        <hr>

        <!-- Chart.js Domains Tops -->
        <div class="sccorpScrolling" id="Top20DomainsChart"></div>
        <h2 class="mb-4">Domains der letzten 7 Tage (Top 20) - Neueste zuerst<?php echo $sFilterInfo; ?></h2>
        <canvas id="domainsChartTops" width="400" height="200"></canvas>
        <hr>

        <!-- Chart.js Domains der letzten 7 Tage -->
        <div class="sccorpScrolling" id="DomainsDaysChart"></div>
        <h2 class="mb-4">Domains der letzten 7 Tage (gefiltert >= 30 Hits) - Neueste zuerst<?php echo $sFilterInfo; ?>
        </h2>
        <canvas id="domainsChart" width="400" height="200"></canvas>
        <hr>

        <!-- Call HTML table of top clients of the last 7 days -->
        <div class="sccorpScrolling" id="DomainsDaysTable"></div>
        <?php echo $oGeneral->getHTMLTableDomainsPerDay($aDomainsPerDay, $iStartLoading, $sFilterInfo); ?>

        <!-- End of Page Content -->
        <div class="sccorpScrolling" id="EndOfPageContent"></div>
    </div>

    <!--<div class="container">-->
    <footer class="py-3 my-4">
        <ul class="nav justify-content-center border-bottom pb-3 mb-3">
            <li class="nav-item"><a href="#" class="nav-link px-2 text-body-secondary"><i class="fa fa-arrow-circle-up"
                        aria-hidden="true"></i></a></li>
            <li class="nav-item"><a href="#TopClientsTable" class="nav-link px-2 text-body-secondary">Clients</a>
            </li>
            <li class="nav-item"><a href="#Top20AgentsTable" class="nav-link px-2 text-body-secondary">Agents|A</a>
            </li>
            <li class="nav-item"><a href="#UserAgentsChart" class="nav-link px-2 text-body-secondary">A>Chart</a>
            <li class="nav-item"><a href="#MethodsDaysTable" class="nav-link px-2 text-body-secondary">Methodes|M</a>
            <li class="nav-item"><a href="#MethodsChart" class="nav-link px-2 text-body-secondary">M>Chart</a>
            </li>
            <li class="nav-item"><a href="#Top20DomainsChart" class="nav-link px-2 text-body-secondary">Domains>D</a>
            </li>
            <li class="nav-item"><a href="#DomainsDaysChart" class="nav-link px-2 text-body-secondary">D>Chart</a>
            </li>
            <li class="nav-item"><a href="#DomainsDaysTable" class="nav-link px-2 text-body-secondary">D>List</a>
            </li>
            <li class="nav-item"><a href="#EndOfPageContent" class="nav-link px-2 text-body-secondary"><i
                        class="fa fa-arrow-circle-down" aria-hidden="true"></i></a>
            </li>
        </ul>
        <p class="text-center text-body-secondary">&copy; <?php echo date("Y"); ?> Schlossguide Corporation</p>
    </footer>
    <!--</div>-->

    <!-- Bootstrap JS -->
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js -->
    <script src="node_modules/chart.js/dist/chart.umd.js"></script>

    <!-- **************** DIAGRAM SECTION START **************** -->
    <script>
        /** DIAGRAM: "methods" */
        const chartMethodLabels = <?php echo json_encode($aMethodLabels); ?>;
        const chartMethodHitCounts = <?php echo json_encode($aMethodHitCounts, JSON_NUMERIC_CHECK); ?>;

        /** DIAGRAM: "domains of the last X days" */
        const chartDomainUsageXDaysData = <?= json_encode($aDomainsPerDay) ?>;

        /** DIAGRAM: "user agents of the last X days" */
        const userAgentLabels = <?= json_encode(array_column($aTopUserAgents, 'user_agent')) ?>;
        const userAgentHits = <?= json_encode(array_column($aTopUserAgents, 'hits')) ?>;

        /** DIAGRAM: "domains of the last X days (Tops)" */
        const domainsChartDataTops = <?= json_encode($aDomainsPerDayTops) ?>;
    </script>
    <!-- **************** DIAGRAM SECTION E N D **************** -->

    <!-- Main.js -->
    <script src="js/main.js"></script>

</body>

</html>

<?php
// Close the database connection
$oDatabase->closeConnection();
?>