<?php
session_start();
require 'functions.php';

$user_uuid = getUserUUID();

$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['type']) ? $_GET['type'] : '';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Torrent Site</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
    /* Wyśrodkowanie pionowe i poziome formularza */
    .centered-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 40vh;
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Torrent Site</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Przełącz nawigację">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Usuń UUID</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (!empty($message)): ?>
        <div class="container mt-4">
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zamknij"></button>
            </div>
        </div>
    <?php endif; ?>

    <main class="mt-5 pt-4">
        <div class="container centered-container">
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header text-center">
                        <i class="fas fa-upload me-2"></i>Dodaj Torrent
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="torrent" class="form-label">Prześlij plik .torrent</label>
                                <input class="form-control" type="file" id="torrent" name="torrent" accept=".torrent">
                            </div>
                            <div class="mb-3">
                                <label for="magnet" class="form-label">Lub wprowadź link magnet</label>
                                <input type="text" class="form-control" id="magnet" name="magnet" placeholder="magnet:?xt=urn:btih:...">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-hover">Pobierz</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela na dole -->
        <div class="container mt-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-list me-2"></i>Twoje Torrenty
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="torrentsTable" class="table table-dark table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nazwa</th>
                                            <th>Seedów</th>
                                            <th>Peerów</th>
                                            <th>Postęp</th>
                                            <th>Status</th>
                                            <th>Pozostały czas</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody id="torrentsTableBody">
                                        <!-- Dane załadowane przez JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <footer class="bg-dark text-center text-white mt-5">
        <div class="container p-4">
            <p>&copy; <?php echo date('Y'); ?> Torrent Site. Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>
