<?php
# this is not part of tool. this is a mock web app.
# Notice: Not work with builtin server. because builtin server not worked parallely.
# use other process `php -S 127.0.0.1:5678 index.php`

$path = $_SERVER['REQUEST_URI'];
echo "<h1>" . htmlspecialchars($path, ENT_NOQUOTES) . "</h1>" . PHP_EOL;

// echo "<pre>";
// var_dump($_SERVER);
