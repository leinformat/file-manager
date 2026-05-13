<?php
$pdo = new PDO('sqlite:c:/xampp/htdocs/php_file_manager/database.sqlite');
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table'");
print_r($stmt->fetchAll());
?>
