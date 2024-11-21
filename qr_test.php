<?php

function generateQRCode($data): void
{
  $options = new QROptions([
    "version" => 5,
    "scale" => 5,
  ]);

  $qrcode = (new QRCode($options))->render($data);

  // Output QR code as an image
  printf('<img src="%s" alt="QR Code" />', $qrcode);
}
$data = "usman";
generateQRCode($data);

?>
