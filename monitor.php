<?php
// monitor.php
require 'functions.php';
session_start();

$torrents = getTorrentsData();
$updated = false;

if (empty($torrents)) {
    exit;
}

$data = [
    'method' => 'torrent-get',
    'arguments' => [
        'fields' => [
            'id', 'hashString', 'status', 'percentDone', 'name',
            'peersConnected', 'peersSendingToUs', 'peersGettingFromUs',
            'metadataPercentComplete'
        ]
    ]
];

$response = transmissionRpc($data);
if ($response === null) {
    logError("Błąd połączenia z Transmission podczas monitorowania torrentów.");
    exit;
}

$responseData = json_decode($response, true);
if (!isset($responseData['arguments']['torrents'])) {
    logError("Brak danych torrentów z Transmission.");
    exit;
}

$transmissionTorrents = [];
foreach ($responseData['arguments']['torrents'] as $t) {
    $transmissionTorrents[$t['hashString']] = $t;
}

foreach ($torrents as $hash => &$torrent) {
    if (!isset($transmissionTorrents[$hash])) {
        if (!empty($torrent['zip_path']) && file_exists($torrent['zip_path'])) {
            if ($torrent['status'] !== 'completed') {
                $torrent['status'] = 'completed';
                $updated = true;
                logError("Torrent $hash został usunięty z Transmission, ale ZIP jest dostępny.");
            }
        } else {
            if ($torrent['status'] !== 'error') {
                $torrent['status'] = 'error';
                $updated = true;
                logError("Torrent $hash został usunięty z Transmission, ale ZIP nie istnieje.");
            }
        }
        continue;
    }

    $t = $transmissionTorrents[$hash];

    if ($torrent['name'] !== $t['name']) {
        logError("Aktualizacja nazwy torrenta $hash z '{$torrent['name']}' na '{$t['name']}'");
        $torrent['name'] = $t['name'];
        $updated = true;
    }

    if (isset($t['metadataPercentComplete']) && $t['metadataPercentComplete'] < 1) {
        if ($torrent['status'] !== 'waiting_for_metadata') {
            logError("Torrent $hash oczekuje na metadane.");
            $torrent['status'] = 'waiting_for_metadata';
            $updated = true;
        }
        continue;
    }

    $status = $t['status'];
    $percentDone = isset($t['percentDone']) ? $t['percentDone'] : 0;
    $seeders = isset($t['peersConnected']) ? $t['peersConnected'] : 0;
    $peers = (isset($t['peersSendingToUs']) ? $t['peersSendingToUs'] : 0) + (isset($t['peersGettingFromUs']) ? $t['peersGettingFromUs'] : 0);

    $torrent['seeds'] = $seeders;
    $torrent['peers'] = $peers;
    $torrent['percentDone'] = $percentDone;

    if ($status === 6 || $percentDone >= 1) {
        $downloadPath = DOWNLOAD_DIR . '/' . $torrent['name'];
        if (!file_exists($downloadPath)) {
            if ($torrent['status'] !== 'error') {
                $torrent['status'] = 'error';
                $updated = true;
            }
            continue;
        }

        if (!empty($torrent['zip_path']) && file_exists($torrent['zip_path'])) {
            if ($torrent['status'] !== 'completed') {
                $torrent['status'] = 'completed';
                $updated = true;
                logError("Torrent $hash został skompresowany: {$torrent['zip_path']}");
            }
        } else {
            if ($torrent['status'] !== 'zipping') {
                $torrent['status'] = 'zipping';
                $updated = true;
                $command = "/usr/bin/php " . escapeshellarg(__DIR__ . "/zipper.php") . " " . escapeshellarg($hash) . " > /dev/null 2>&1 &";
                exec($command);
                logError("Uruchomiono pakowanie dla torrenta $hash.");
            }
        }
    } else {
        if ($torrent['status'] !== 'downloading') {
            $torrent['status'] = 'downloading';
            $updated = true;
        }
    }

    if ($torrent['status'] === 'completed' && isset($torrent['zip_created_at']) && $torrent['zip_created_at'] !== null) {
        $currentTime = time();
        $zipAge = $currentTime - $torrent['zip_created_at'];
        if ($zipAge >= DELETE_AFTER_HOURS * 3600) {
            if (file_exists($torrent['zip_path'])) {
                if (unlink($torrent['zip_path'])) {
                    logError("Usunięto plik ZIP dla torrenta $hash: {$torrent['zip_path']}");
                } else {
                    logError("Nie można usunąć pliku ZIP dla torrenta $hash: {$torrent['zip_path']}");
                }
            }

            unset($torrents[$hash]);
            $updated = true;
            logError("Usunięto torrent $hash z torrents.json po 24 godzinach.");
        }
    }
}

if ($updated) {
    saveTorrentsData($torrents);
}
?>
