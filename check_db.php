<?php
$pdo = new PDO('sqlite:c:/xampp/htdocs/php_file_manager/database.sqlite');
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
echo "Instances:\n";
print_r($pdo->query('SELECT * FROM instances')->fetchAll());
echo "Company Info:\n";
print_r($pdo->query('SELECT * FROM company_info')->fetchAll());
?>
