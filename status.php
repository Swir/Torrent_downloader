<?php
// status.php
require 'functions.php';
session_start();
requireUUID();

$user_uuid = getUserUUID();
header('Content-Type: application/json');

$torrentsData = getTorrentsData();

$userTorrents = [];
foreach ($torrentsData as $torrent) {
    if ($torrent['user_uuid'] === $user_uuid) {
        $torrent['zip_exists'] = (!empty($torrent['zip_path']) && file_exists($torrent['zip_path']));
        $timeLeft = DELETE_AFTER_HOURS * 3600 - (time() - $torrent['added_at']);
        $torrent['time_left'] = $timeLeft > 0 ? $timeLeft : 0;
        $userTorrents[] = $torrent;
    }
}

echo json_encode($userTorrents);
?>
