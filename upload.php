<?php
// upload.php
require 'functions.php';

session_start();
requireUUID();

$user_uuid = getUserUUID();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $torrentMetainfo = '';
    $torrentName = '';
    $isMagnet = false;

    if (isset($_FILES['torrent']) && $_FILES['torrent']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['torrent']['tmp_name'];
        $originalName = basename($_FILES['torrent']['name']);
        $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
        if (strtolower($fileExt) !== 'torrent') {
            showAlertAndRedirect('Dozwolone są tylko pliki .torrent.', 'danger');
        }

        $maxFileSize = 10 * 1024 * 1024;
        if ($_FILES['torrent']['size'] > $maxFileSize) {
            showAlertAndRedirect('Plik .torrent jest zbyt duży. Maksymalny rozmiar to 10MB.', 'danger');
        }

        $torrentContent = file_get_contents($tmpName);
        if ($torrentContent === false) {
            showAlertAndRedirect('Nie można odczytać pliku torrent.', 'danger');
        }
        $torrentMetainfo = base64_encode($torrentContent);
        $torrentName = $originalName;
    } elseif (isset($_POST['magnet']) && !empty(trim($_POST['magnet']))) {
        $magnet = trim($_POST['magnet']);
        // Sprawdzamy tylko, czy zaczyna się od 'magnet:'
        if (strpos($magnet, 'magnet:') !== 0) {
            showAlertAndRedirect('Nieprawidłowy link magnet.', 'danger');
        }
        $torrentMetainfo = $magnet;
        $torrentName = 'Magnet Link';
        $isMagnet = true;
    } else {
        showAlertAndRedirect('Nie przesłano pliku .torrent ani linku magnet.', 'warning');
    }

    $data = [
        'method' => 'torrent-add',
        'arguments' => []
    ];

    if ($isMagnet) {
        $data['arguments']['filename'] = $torrentMetainfo;
    } else {
        $data['arguments']['metainfo'] = $torrentMetainfo;
    }

    $response = transmissionRpc($data);

    if ($response === null) {
        showAlertAndRedirect('Błąd połączenia z Transmission.', 'danger');
    }

    $responseData = json_decode($response, true);
    if ($responseData['result'] === 'success') {
        if (isset($responseData['arguments']['torrent-added'])) {
            $torrentHash = $responseData['arguments']['torrent-added']['hashString'];
            $torrentName = $responseData['arguments']['torrent-added']['name'];
        } elseif (isset($responseData['arguments']['torrent-duplicate'])) {
            $torrentHash = $responseData['arguments']['torrent-duplicate']['hashString'];
            $torrentName = $responseData['arguments']['torrent-duplicate']['name'];
            showAlertAndRedirect('Torrent jest już dodany.', 'info');
            exit;
        } else {
            showAlertAndRedirect('Nie można uzyskać hash torrenta.', 'danger');
        }

        $torrents = getTorrentsData();
        if (isset($torrents[$torrentHash])) {
            showAlertAndRedirect('Torrent już istnieje.', 'info');
            exit;
        }

        $torrents[$torrentHash] = [
            'name' => $torrentName,
            'hash' => $torrentHash,
            'status' => 'downloading',
            'seeds' => 0,
            'peers' => 0,
            'percentDone' => 0,
            'added_at' => time(),
            'zip_path' => '',
            'zip_created_at' => null,
            'user_uuid' => $user_uuid
        ];
        saveTorrentsData($torrents);

        showAlertAndRedirect('Torrent został dodany pomyślnie.', 'success');
    } else {
        $errorMsg = 'Błąd dodawania torrenta: ' . ($responseData['result'] ?? 'Nieznany błąd');
        showAlertAndRedirect($errorMsg, 'danger');
    }
} else {
    header('Location: index.php');
    exit;
}

function showAlertAndRedirect($message, $type) {
    header('Location: index.php?message=' . urlencode($message) . '&type=' . urlencode($type));
    exit;
}
?>
