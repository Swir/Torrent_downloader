<?php
// download.php
require 'functions.php';
session_start();
requireUUID();

if (!isset($_GET['hash'])) {
    die('Nie podano hash torrenta.');
}

$hash = $_GET['hash'];
$user_uuid = getUserUUID();
$torrents = getTorrentsData();

if (!isset($torrents[$hash])) {
    die('Torrent nie istnieje.');
}

$torrent = $torrents[$hash];
if ($torrent['user_uuid'] !== $user_uuid) {
    die('Nie masz uprawnień do pobrania tego pliku.');
}

if (empty($torrent['zip_path']) || !file_exists($torrent['zip_path'])) {
    die('Plik ZIP nie jest dostępny.');
}

$zipPath = $torrent['zip_path'];
$zipName = basename($zipPath);

header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($zipPath);
exit;
?>
