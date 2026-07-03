<?php

/**
 * Package: SGCorp Squid Logfile Analyzer
 * --------------------------------------
 * Database Class
 * --------------------------------------
 * This class provides functions to connect to the Squid logfile database.
 * --------------------------------------
 * @author    Axel Glitza <axel@glitza.eu>
 * @copyright 2021 - 2026 Axel Glitza
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

class Database
{
    private $connection;
    private $host;
    private $username;
    private $password;
    private $dbname;

    /*
     ****************************************************************************************
     * Name of function: __construct
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * NONE
     * Response of function: NULL
     * Description:
     * Constructor of Class Database. Loads the database configuration and establishes
     * a connection to the database using PDO.
     ****************************************************************************************
     */
    public function __construct()
    {
        // Load the database configuration and credentials
        $this->loadConfiguration();

        // Connect to the database
        $this->connect();
    }

    /*
     ****************************************************************************************
     * Name of function: __construct
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * NONE
     * Response of function: NULL
     * Description:
     * Constructor of Class Database. Loads the database configuration and establishes
     * a connection to the database using PDO.
     ****************************************************************************************
     */
    private function loadConfiguration()
    {
        global $dbConfig;
        $this->host = $dbConfig['host'] ?? 'localhost';
        $this->username = $dbConfig['username'] ?? 'root';
        $this->password = $dbConfig['password'] ?? '';
        $this->dbname = $dbConfig['dbname'] ?? '';
    }

    /*
     ****************************************************************************************
     * Name of function: connect
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * NONE
     * Response of function: NULL
     * Description:
     * Establishes a connection to the database using PDO.
     ****************************************************************************************
     */
    private function connect()
    {
        try {
            // Erstelle die PDO-Verbindung
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->connection = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            ]);

            // Setze den Fehler-Modus auf Exception
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Fehler: Verbindung zur Datenbank fehlgeschlagen: " . $e->getMessage());
        }
    }

    /*
     ****************************************************************************************
     * Name of function: getConnection
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * NONE
     * Response of function: PDO
     * Description:
     * Returns the PDO database connection.
     ****************************************************************************************
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /*
     ****************************************************************************************
     * Name of function: closeConnection
     * ---------------------------------------------------------------------------------------
     * Input needed for function:
     * NONE
     * Response of function: VOID
     * Description:
     * Closes the database connection by setting the PDO object to null.
     ****************************************************************************************
     */
    public function closeConnection()
    {
        $this->connection = null;
    }
}