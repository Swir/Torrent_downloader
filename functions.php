<?php
// functions.php

$config = include 'config.php';

define('TRANSMISSION_RPC_URL', $config['transmission']['rpc_url']);
define('TRANSMISSION_USERNAME', $config['transmission']['username'] ?? '');
define('TRANSMISSION_PASSWORD', $config['transmission']['password'] ?? '');
define('DOWNLOAD_DIR', $config['paths']['download_dir']);
define('ZIP_DIR', $config['paths']['zip_dir']);
define('DATA_FILE', $config['paths']['data_file']);
define('USERS_FILE', $config['paths']['users_file']);
define('LOGS_DIR', $config['paths']['logs_dir']);
define('DELETE_AFTER_HOURS', $config['settings']['delete_after_hours']);

logError("Konfiguracja RPC URL: " . TRANSMISSION_RPC_URL);
logError("Konfiguracja download_dir: " . DOWNLOAD_DIR);
logError("Konfiguracja zip_dir: " . ZIP_DIR);
logError("Konfiguracja data_file: " . DATA_FILE);
logError("Konfiguracja users_file: " . USERS_FILE);
logError("Konfiguracja logs_dir: " . LOGS_DIR);

$directories = [
    'zip_dir' => ZIP_DIR,
    'logs_dir' => LOGS_DIR,
    'data_dir' => dirname(DATA_FILE)
];

foreach ($directories as $key => $path) {
    if (!file_exists($path)) {
        logError("Próbuję utworzyć katalog '$key' z ścieżką: $path");
        if (!mkdir($path, 0755, true)) {
            logError("Nie można utworzyć katalogu '$key' z ścieżką: $path");
        } else {
            logError("Utworzono katalog '$key' z ścieżką: $path");
        }
    } else {
        logError("Katalog '$key' już istnieje: $path");
    }
}

function logError($message) {
    $logFile = LOGS_DIR . '/error.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

function generateUUID() {
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    $data = openssl_random_pseudo_bytes(16);
    assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function getUserUUID() {
    if (isset($_COOKIE['user_uuid'])) {
        return $_COOKIE['user_uuid'];
    } elseif (isset($_SESSION['user_uuid'])) {
        return $_SESSION['user_uuid'];
    } else {
        $uuid = generateUUID();
        setcookie('user_uuid', $uuid, [
            'expires' => time() + (365 * 24 * 60 * 60),
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        $_SESSION['user_uuid'] = $uuid;
        return $uuid;
    }
}

function getTorrentsData() {
    if (!file_exists(DATA_FILE)) {
        file_put_contents(DATA_FILE, json_encode([]));
    }
    $fp = fopen(DATA_FILE, 'r');
    if ($fp === false) {
        logError("Nie można otworzyć pliku torrents.json do odczytu.");
        return [];
    }
    if (flock($fp, LOCK_SH)) {
        $filesize = filesize(DATA_FILE);
        $json = $filesize > 0 ? fread($fp, $filesize) : '';
        flock($fp, LOCK_UN);
    } else {
        logError("Nie można zablokować pliku torrents.json do odczytu.");
        fclose($fp);
        return [];
    }
    fclose($fp);
    return json_decode($json, true) ?? [];
}

function saveTorrentsData($data) {
    $fp = fopen(DATA_FILE, 'w');
    if ($fp === false) {
        logError("Nie można otworzyć pliku torrents.json do zapisu.");
        return;
    }
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
    } else {
        logError("Nie można zablokować pliku torrents.json do zapisu.");
    }
    fclose($fp);
}

function getUsers() {
    if (!file_exists(USERS_FILE)) {
        file_put_contents(USERS_FILE, json_encode([]));
    }
    $json = file_get_contents(USERS_FILE);
    return json_decode($json, true) ?? [];
}

function saveUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function transmissionRpc($data) {
    static $session_id = null;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, TRANSMISSION_RPC_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $headers = ['Content-Type: application/json'];
    if ($session_id) {
        $headers[] = 'X-Transmission-Session-Id: ' . $session_id;
    }

    if (!empty(TRANSMISSION_USERNAME) && !empty(TRANSMISSION_PASSWORD)) {
        curl_setopt($ch, CURLOPT_USERPWD, TRANSMISSION_USERNAME . ':' . TRANSMISSION_PASSWORD);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = 'Błąd połączenia z Transmission: ' . curl_error($ch);
        logError($error);
        curl_close($ch);
        return null;
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    if ($http_code == 409) {
        if (preg_match('/X-Transmission-Session-Id: (\S+)/', $header, $matches)) {
            $session_id = trim($matches[1]);
            $headers = [
                'Content-Type: application/json',
                'X-Transmission-Session-Id: ' . $session_id,
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);

            if ($response === false) {
                $error = 'Błąd połączenia z Transmission po ponownym żądaniu: ' . curl_error($ch);
                logError($error);
                curl_close($ch);
                return null;
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
        } else {
            $error = 'Nie można uzyskać Session ID od Transmission.';
            logError($error);
            curl_close($ch);
            return null;
        }
    }

    curl_close($ch);

    $response_data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = 'Niepoprawny format JSON od Transmission: ' . json_last_error_msg();
        logError($error . " - Odpowiedź: " . $body);
        return null;
    }

    if (isset($response_data['result']) && $response_data['result'] !== 'success') {
        $error = 'Błąd Transmission RPC: ' . $response_data['result'];
        logError($error . " - Odpowiedź: " . $body);
        return null;
    }

    return $body;
}

function requireUUID() {
    if (!isset($_COOKIE['user_uuid']) && !isset($_SESSION['user_uuid'])) {
        header('Location: index.php');
        exit;
    }
}
?>
