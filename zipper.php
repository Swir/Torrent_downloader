<?php
// zipper.php
require 'functions.php';

if ($argc < 2) {
    logError("Brak argumentu hash w zipper.php.");
    exit("Brak argumentu hash.\n");
}

$hash = $argv[1];
$torrents = getTorrentsData();

if (!isset($torrents[$hash])) {
    logError("Nie znaleziono torrenta o hashu: $hash w zipper.php.");
    exit("Nie znaleziono torrenta o hashu: $hash\n");
}

$torrent = &$torrents[$hash];

if ($torrent['status'] === 'completed' && !empty($torrent['zip_path']) && file_exists($torrent['zip_path'])) {
    logError("Torrent $hash już jest spakowany.");
    exit("Torrent już spakowany.\n");
}

$downloadPath = DOWNLOAD_DIR . '/' . $torrent['name'];

if (!file_exists($downloadPath)) {
    $torrent['status'] = 'error';
    saveTorrentsData($torrents);
    logError("Katalog pobierania nie istnieje: $downloadPath dla torrenta $hash.");
    exit("Katalog pobierania nie istnieje: $downloadPath\n");
}

if (!empty($torrent['zip_path']) && file_exists($torrent['zip_path'])) {
    $torrent['status'] = 'completed';
    saveTorrentsData($torrents);
    logError("Plik już skompresowany dla torrenta $hash: {$torrent['zip_path']}");
    exit("Plik jest już skompresowany.\n");
}

$safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $torrent['name']);
$zipName = $safeName . '_' . $hash . '.zip';
$zipPath = ZIP_DIR . '/' . $zipName;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($downloadPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($downloadPath) + 1);
            if (!$zip->addFile($filePath, $relativePath)) {
                logError("Nie można dodać pliku $filePath do ZIP dla torrenta $hash.");
            }
        }
    }

    $zip->close();

    if (file_exists($zipPath)) {
        $torrent['zip_path'] = $zipPath;
        $torrent['status'] = 'completed';
        $torrent['zip_created_at'] = time();
        saveTorrentsData($torrents);
        logError("Pomyślnie utworzono plik ZIP dla torrenta $hash: $zipPath");
    } else {
        $torrent['status'] = 'error';
        saveTorrentsData($torrents);
        logError("Nie udało się utworzyć pliku ZIP dla torrenta $hash.");
        exit("Nie udało się utworzyć pliku ZIP.\n");
    }
} else {
    $torrent['status'] = 'error';
    saveTorrentsData($torrents);
    logError("Nie można otworzyć pliku ZIP dla torrenta $hash.");
    exit("Nie można otworzyć pliku ZIP.\n");
}

// Usunięcie torrenta z Transmission po pakowaniu
if ($torrent['status'] === 'completed') {
    $data = [
        'method' => 'torrent-remove',
        'arguments' => [
            'ids' => [$hash],
            'delete-local-data' => true,
        ],
    ];
    $result = transmissionRpc($data);

    if ($result === null) {
        logError("Nie udało się usunąć torrenta o hashu: $hash po pakowaniu.");
    } else {
        $resultData = json_decode($result, true);
        if (isset($resultData['result']) && $resultData['result'] === 'success') {
            logError("Usunięto torrenta o hashu: $hash po pakowaniu.");
        } else {
            $errorMsg = 'Błąd usuwania torrenta po pakowaniu: ' . ($resultData['result'] ?? 'Nieznany błąd');
            logError($errorMsg);
        }
    }
}
?>
