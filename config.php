<?php
// config.php

return [
    'transmission' => [
        'rpc_url' => 'http://127.0.0.1:9091/transmission/rpc',
        'username' => '', // Jeśli wymagane, wstaw dane
        'password' => '', // Jeśli wymagane, wstaw dane
    ],
    'paths' => [
        'download_dir' => '/var/lib/transmission-daemon/downloads', // Dostosuj
        'zip_dir' => __DIR__ . '/zips',
        'data_file' => __DIR__ . '/data/torrents.json',
        'users_file' => __DIR__ . '/data/users.json',
        'logs_dir' => __DIR__ . '/logs',
    ],
    'settings' => [
        'delete_after_hours' => 24,
    ],
];
?>
