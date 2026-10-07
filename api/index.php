<?php
header("Content-Type: application/json");
echo json_encode(["status" => "200","author" => "wissam333","message" => "Check https://github.com/wissam333/myinstants for documentation"], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>
