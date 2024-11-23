<?php
function myAutoloader($class)
{
  $baseDir = __DIR__ . "/src/Swift2FA/";
  $class = str_replace("Swift2FA\\", "", $class);
  $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);

  $file = $baseDir . $class . ".php";
  if (file_exists($file)) {
    require $file;
  } else {
    echo "File not found: $file\n";
  }
}

spl_autoload_register("myAutoloader");

