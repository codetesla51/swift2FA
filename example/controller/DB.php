<?php
require "../../config.php";

/**
 * Handles database connection using PDO.
 */
class DB
{
  private string $DB_HOST;
  private string $DB_USER;
  private string $DB_PASS;
  private string $DB_NAME;
  private ?PDO $connection = null; // Nullable PDO type

  /**
   * Initializes database parameters from environment variables.
   */
  public function __construct()
  {
    // Fetch environment variables
    $this->DB_HOST = $_ENV["DB_HOST"];
    $this->DB_USER = $_ENV["DB_USER"];
    $this->DB_PASS = $_ENV["DB_PASS"];
    $this->DB_NAME = $_ENV["DB_NAME"];
  }

  /**
   * Connects to the database.
   *
   * @return PDO|null The PDO connection or null on failure.
   * @throws PDOException If the connection fails.
   */
  public function connect(): ?PDO
  {
    if ($this->connection === null) {
      try {
        $dsn = "mysql:host={$this->DB_HOST};dbname={$this->DB_NAME}";
        $this->connection = new PDO($dsn, $this->DB_USER, $this->DB_PASS);
        $this->connection->setAttribute(
          PDO::ATTR_ERRMODE,
          PDO::ERRMODE_EXCEPTION
        );
        $this->connection->setAttribute(
          PDO::ATTR_DEFAULT_FETCH_MODE,
          PDO::FETCH_ASSOC
        );
      } catch (PDOException $e) {
        // Rethrow the exception to be caught higher up
        throw new PDOException(
          "Database connection failed: " . $e->getMessage(),
          (int) $e->getCode()
        );
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
