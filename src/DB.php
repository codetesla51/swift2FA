<?php
require "../config.php";

/**
 * Handles database connection using PDO.
 */
class DB
{
  private string $DB_HOST; // Database host
  private string $DB_USER; // Database username
  private string $DB_PASS; // Database password
  private string $DB_NAME; // Database name
  private ?PDO $connection; // PDO connection instance

  /**
   * Initializes database parameters from environment variables.
   */
  public function __construct()
  {
    $this->DB_HOST = $_ENV["DB_HOST"];
    $this->DB_USER = $_ENV["DB_USER"];
    $this->DB_PASS = $_ENV["DB_PASS"];
    $this->DB_NAME = $_ENV["DB_NAME"];
    $this->connection = null;
  }

  /**
   * Connects to the database.
   *
   * @return PDO|null The PDO connection or null on failure.
   */
  public function connect(): ?PDO
  {
    if ($this->connection === null) {
      try {
        $dsn = "mysql:host={$this->DB_HOST};dbname={$this->DB_NAME}";
        $this->connection = new PDO($dsn, $this->DB_USER, $this->DB_PASS);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage();
        echo " Error Code: " . $e->getCode();
        return null;
      }
    }
    return $this->connection;
  }

  /**
   * Disconnects from the database.
   */
  public function disconnectDB(): void
  {
    $this->connection = null;
  }
}
?>
