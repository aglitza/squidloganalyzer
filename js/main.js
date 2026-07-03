/**
 * Package: SGCorp Squid Logfile Analyzer
 * --------------------------------------
 * Main JavaScript File
 * --------------------------------------
 * This file contains the main JavaScript code for the SGCorp Squid Logfile Analyzer application.
 * --------------------------------------
 * @author    Axel Glitza <axel@glitza.eu>
 * @copyright 2021 - 2026 Axel Glitza
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

/*
******************************************************************************
* Name of code-block: websocket
* ----------------------------------------------------------------------------
* Description: Live client name resolve
******************************************************************************
*/
const ws = new WebSocket("wss://sla.sgcorp-development.local:9001");

ws.onopen = () => {
    console.log("WebSocket connection established.");
};
ws.onerror = (error) => {
    console.error("WebSocket error:", error);
    console.log("WebSocket readyState:", ws.readyState);
};

ws.onmessage = (event) => {
    try {
        const data = JSON.parse(event.data);
        const cell = document.querySelector(`#client-${data.ip}`);
        if (cell) {
            cell.innerHTML = data.message;
        }
    } catch (error) {
        console.error("Error parsing WebSocket message:", error);
    }
};

ws.onclose = () => {
    console.log("WebSocket connection closed.");
};

/*
******************************************************************************
* Name of code-block: spinner
* ----------------------------------------------------------------------------
* Description: Steering of loading spinner
******************************************************************************
*/
const spinnerWrapperEl = document.querySelector('.sgcorp-spinner-wrapper');
window.addEventListener('load', () => {
    spinnerWrapperEl.style.opacity = '0';
    setTimeout(() => {
        spinnerWrapperEl.style.display = 'none';
    }, 200);
});

/**
 * DIAGRAM: Method Usage Chart
 * ------------------------------------------------------
 * This section initializes and configures the pie chart that displays
 * the distribution of HTTP methods used in the Squid access logs.
 * -------------------------------------------------------------
 */
    const chartMethodData = {
            labels: chartMethodLabels,
            datasets: [{
                label: 'Anzahl der Hits pro Methode',
                data: chartMethodHitCounts,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(201, 203, 207, 0.7)',
                    'rgba(255, 205, 86, 0.7)'
                ]
            }]
        };

        const chartMethodConfig = {
            type: 'pie',
            data: chartMethodData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    }
                }
            }
        };

    new Chart(document.getElementById('methodUsageChart'), chartMethodConfig);

/**
 * DIAGRAM: User agents of the last X days
 * ------------------------------------------------------
 * This section initializes and configures the line chart that displays
 * the number of unique domains accessed over the last X days.
 * -------------------------------------------------------------
 */

        // Farben für die Segmente
        const colors = [
            'rgba(255, 99, 132, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)',
            'rgba(199, 199, 199, 0.6)',
            'rgba(255, 99, 71, 0.6)',
            'rgba(60, 179, 113, 0.6)',
            'rgba(100, 149, 237, 0.6)',
            'rgba(218, 112, 214, 0.6)',
            'rgba(244, 164, 96, 0.6)',
            'rgba(32, 178, 170, 0.6)',
            'rgba(210, 105, 30, 0.6)',
            'rgba(128, 0, 128, 0.6)',
            'rgba(0, 128, 128, 0.6)',
            'rgba(0, 255, 127, 0.6)',
            'rgba(70, 130, 180, 0.6)',
            'rgba(255, 215, 0, 0.6)',
            'rgba(46, 139, 87, 0.6)'
        ];

        const chartCanvas = document.getElementById('userAgentPie').getContext('2d');
        new Chart(chartCanvas, {
            type: 'pie',
            data: {
                labels: userAgentLabels,
                datasets: [{
                    label: 'Hits',
                    data: userAgentHits,
                    backgroundColor: colors,
                    borderColor: 'rgba(255, 255, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: 10
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            }
        });

/**
 * DIAGRAM: Domains of the last X days
 * ------------------------------------------------------
 * This section initializes and configures the line chart that displays
 * the number of unique domains accessed over the last X days.
 * -------------------------------------------------------------
 */

// Labels und Werte extrahieren
const chartDomainUsageXDaysLabels = [];
const chartDUXDaysData = [];

chartDomainUsageXDaysData.forEach(row => {
    if (row.hits >= 30) {   // nur Werte >= 10 übernehmen
        chartDomainUsageXDaysLabels.push(`${row.day} - ${row.domain}`);
        chartDUXDaysData.push(row.hits);
    }
});

// Chart.js Diagramm erstellen
const chartDomainUsageXDaysConfig = document.getElementById('domainsChart').getContext('2d');
new Chart(chartDomainUsageXDaysConfig, {
    type: 'bar', // Diagrammtyp: Balkendiagramm
    data: {
        labels: chartDomainUsageXDaysLabels,
        datasets: [{
            label: 'Hits pro Domain',
            data: chartDUXDaysData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function (context) {
                        return `${context.raw} Anzahl der URL-Hits am Tag`;
                    }
                }
            }
        },
        scales: {
            x: {
                title: {
                    display: false,
                    text: 'Domain-/URL-Aufrufe am Tag'
                },
                ticks: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Hits (kumuliert)'
                }
            }
        }
    }
});


/**
 * DIAGRAM: Domains of the last X days (Tops)
 * ------------------------------------------------------
 * This section initializes and configures the line chart that displays
 * the number of unique domains accessed over the last X days.
 * -------------------------------------------------------------
 */

        // Labels und Werte extrahieren
        const domainLabelsTops = domainsChartDataTops.map(entry => `${entry.day} - ${entry.domain}`);
        const domainHitsTops = domainsChartDataTops.map(entry => entry.hits);

        // Chart.js Diagramm erstellen
        const canvasContextTops = document.getElementById('domainsChartTops').getContext('2d');
        const chartInstanceTops = new Chart(canvasContextTops, {
            type: 'bar', // Diagrammtyp: Balkendiagramm
            data: {
                labels: domainLabelsTops,
                datasets: [{
                    label: 'Hits pro Domain',
                    data: domainHitsTops,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `${context.raw} Anzahl der URL-Hits am Tag`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: false,
                            text: 'Top 20 Domain-/URL-Aufrufe am Tag'
                        },
                        ticks: {
                            display: true
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hits (kumuliert)'
                        }
                    }
                }
            }
        });
