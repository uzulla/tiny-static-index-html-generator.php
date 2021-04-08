<?php
# Tiny tool for generate static index.html
# https://github.com/uzulla/tiny-static-index-html-generator.php
# AUTHOR: uzulla
# LICENSE: MIT

declare(strict_types=1);

// ==config==

define("DRY_RUN", false);
define("CREATE_DIR_IF_NOT_EXISTS", false);
define("REMOVE_EMPTY_DIR", false);
define("USER_NAME", 'super_admin_wow');
# please generate your hashed password by bellow code.
# php -r 'echo password_hash("default_password_1234!", PASSWORD_DEFAULT).PHP_EOL;'
define("HASHED_PASSWORD", '$2y$10$ZwXn8WyOOzakK3ARkJD/T.rotpzNZLE78Q9MXIUqWXI9KvAR5dutm'); // default_password_1234!
define("ALLOW_IP_LIST", "192.168.0.1,127.0.0.1");

$base_path = __DIR__;
$path_list = [
    "/ab",
    "/abc",
    "/abc/de",
];

// $base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === "on") ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$base_url = "http://localhost:4444";

// ==main==

check_basic_auth();

$mode ??= $_POST['mode'];
if ($mode === "create") {
    create_static($base_url, $base_path, $path_list);
} elseif ($mode === "purge") {
    purge_static($base_url, $base_path, $path_list);
} else {
    show_form($base_url, $base_path, $path_list);
}

exit;

// ==libs==

function check_basic_auth(): void
{
    ## IP address check
    $allow_ip_list = explode(',', ALLOW_IP_LIST);
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? false;
    # Please change here if you have Load brancer or some proxy.
    // $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? false;

    if ($client_ip === false || array_search($client_ip, $allow_ip_list) === false) {
        error_log("Access denied, from ip{$client_ip}");
        http_response_code(403);
        echo "Forbidden";
        exit;
    }

    ## Basic auth
    $input_username = $_SERVER['PHP_AUTH_USER'] ?? "";
    $input_password = $_SERVER['PHP_AUTH_PW'] ?? "";
    if (
        !password_verify($input_password, HASHED_PASSWORD) ||
        $input_username !== USER_NAME
    ) {
        error_log("Access denied, from ip{$client_ip} {$input_username}:{$input_password}");
        header('WWW-Authenticate: Basic realm="Basic Auth", charset="UTF-8"');
        echo "Need auth";
        exit;
    }
}

function show_form(string $base_url, string $base_path, array $path_list): void
{
    echo <<< "END"
    <html>
    <body>
    <h1>create static file, or purge</h1>
    <h2 style='color:red'>PLEASE BACKUP BEFORE IN USE, PLEASE DON'T FORGET.</h2>
    END;

    if (DRY_RUN) {
        echo "<h3 style='color:red'>DRY RUN MODE</h3>" . PHP_EOL;
    }

    echo <<< "END"
    <form method='post'>
        <input type='hidden' name='mode' value='create'>
        <button type='submit'>create</button>
    </form>
    <form method='post'>
        <input type='hidden' name='mode' value='purge'>
        <button type='submit'>purge</button>
    </form>
    <pre>
    <h3>List of target files.</h3>
    END;

    foreach ($path_list as $path) {
        $remote_url = $base_url . $path;
        $local_full_path = $base_path . $path;
        $local_full_path_index_html = $local_full_path . "/index.html";
        echo "config: {$remote_url} => {$local_full_path_index_html}" . PHP_EOL;
        if (file_exists($local_full_path_index_html)) {
            echo "file exists: {$local_full_path_index_html}" . PHP_EOL;
        } else {
            echo "file not exists: {$local_full_path_index_html}" . PHP_EOL;
        }

        // check dir exists
        if (!file_exists($local_full_path) || !is_dir($local_full_path)) {
            echo "WARN: target dir is not exists or not dir(ex:file) : {$local_full_path}" . PHP_EOL;
        }
    }
    echo "DONE" . PHP_EOL;
}

function create_static(string $base_url, string $base_path, array $path_list): void
{
    echo "<button onclick='history.back();'>back</button><br><h2>create static html</h2><pre>";
    foreach ($path_list as $path) {
        $remote_url = $base_url . $path;
        $local_full_path = $base_path . $path;
        $local_full_path_index_html = $local_full_path . "/index.html";
        echo "download {$remote_url} => {$local_full_path_index_html}" . PHP_EOL;

        $context = stream_context_create(['http' => ['ignore_errors' => true]]);
        $html = file_get_contents($remote_url, false, $context);

        if (strpos($http_response_header[0], '200') === false) {
            echo "ERROR ABORT: target url response not 200, get html failed: {$remote_url}" . PHP_EOL;
            exit;
        }

        if (CREATE_DIR_IF_NOT_EXISTS && !file_exists($local_full_path)) {
            echo "create dir: {$local_full_path}" . PHP_EOL;
            if (!DRY_RUN) {
                mkdir($local_full_path, 0777, true);
            }
        }

        // check dir exists
        if (!file_exists($local_full_path) || !is_dir($local_full_path)) {
            echo "ERROR ABORT: target dir is not exists or not dir(ex:file) : {$local_full_path} " . PHP_EOL;
            if (!DRY_RUN) {
                exit;
            }
        }

        if (!DRY_RUN) {
            file_put_contents($local_full_path_index_html, $html);
        }
    }
    echo "DONE" . PHP_EOL;
}

function purge_static(string $base_url, string $base_path, array $path_list): void
{
    echo "<button onclick='history.back();'>back</button><br><h2>purge static html</h2><pre>";
    foreach ($path_list as $path) {
        $remote_url = $base_url . $path;
        $local_full_path = $base_path . $path;
        $local_full_path_index_html = $local_full_path . "/index.html";
        echo "remove {$local_full_path_index_html}" . PHP_EOL;

        // check dir exists
        if (!file_exists($local_full_path_index_html) || !is_file($local_full_path_index_html)) {
            echo "ERROR: not found index.html {$local_full_path_index_html}" . PHP_EOL;
            continue;
        }

        if (!DRY_RUN) {
            unlink($local_full_path_index_html);
        }

        if (REMOVE_EMPTY_DIR) {
            echo "remove dir (if empty) {$local_full_path}" . PHP_EOL;
            @rmdir($local_full_path); // fail here, if not empty dir.
        }
    }
    echo "DONE" . PHP_EOL;
}
