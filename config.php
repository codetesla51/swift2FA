 <?php
 require __DIR__ . "/vendor/autoload.php";
 use Dotenv\Dotenv;

 // Ensure the correct path to the .env file
 $dotenv = Dotenv::createMutable(__DIR__);
 $dotenv->load();

