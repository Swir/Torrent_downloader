// js/scripts.js

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('uploadForm');
    const torrentInput = document.getElementById('torrent');
    const magnetInput = document.getElementById('magnet');

    uploadForm.addEventListener('submit', (e) => {
        // Prosta walidacja: przynajmniej jedno z pól musi być wypełnione
        if (torrentInput.files.length === 0 && magnetInput.value.trim() === '') {
            e.preventDefault();
            showAlert('Proszę przesłać plik .torrent lub wprowadzić link magnet.', 'warning');
        }

        // Jeśli przesyłany jest plik torrent, sprawdzamy rozszerzenie i rozmiar, ale już bez limitu długości magnet
        if (torrentInput.files.length > 0) {
            const file = torrentInput.files[0];
            const fileExt = file.name.split('.').pop().toLowerCase();
            if (fileExt !== 'torrent') {
                e.preventDefault();
                showAlert('Dozwolone są tylko pliki .torrent.', 'danger');
            }

            const maxFileSize = 10 * 1024 * 1024; // 10 MB
            if (file.size > maxFileSize) {
                e.preventDefault();
                showAlert('Plik .torrent jest zbyt duży. Maksymalny rozmiar to 10MB.', 'danger');
            }
        }

        // Usuwamy całkowicie sprawdzanie długości linku magnet - brak warunku typu "if (magnetInput.value.trim().length > ...)"
    });

    function showAlert(message, type) {
        const toastContainer = document.querySelector('.toast-container') || createToastContainer();
        const toastElement = document.createElement('div');
        toastElement.className = `toast align-items-center text-bg-${type} border-0 animate__animated animate__fadeInDown`;
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        toastElement.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Zamknij"></button>
            </div>
        `;
        toastContainer.appendChild(toastElement);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }

    const dataTable = $('#torrentsTable').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/pl-PL.json"
        },
        "autoWidth": false,
        "responsive": true
    });

    function updateTorrentTable() {
        fetch('status.php')
            .then(response => response.json())
            .then(data => {
                const tableBody = document.getElementById('torrentsTableBody');
                tableBody.innerHTML = '';

                if (data.length === 0) {
                    const emptyRow = document.createElement('tr');
                    const emptyCell = document.createElement('td');
                    emptyCell.colSpan = 7;
                    emptyCell.className = 'text-center';
                    emptyCell.innerHTML = '<i class="fas fa-exclamation-circle text-warning"></i> Brak aktywnych torrentów.';
                    emptyRow.appendChild(emptyCell);
                    tableBody.appendChild(emptyRow);
                    dataTable.clear().draw();
                    return;
                }

                data.forEach(torrent => {
                    const row = document.createElement('tr');

                    const nameCell = document.createElement('td');
                    nameCell.innerHTML = `<i class="fas fa-file-alt me-2"></i>${torrent.name}`;
                    row.appendChild(nameCell);

                    const seedsCell = document.createElement('td');
                    seedsCell.innerHTML = `<i class="fas fa-seedling me-1 text-success"></i>${torrent.seeds}`;
                    row.appendChild(seedsCell);

                    const peersCell = document.createElement('td');
                    peersCell.innerHTML = `<i class="fas fa-user-friends me-1 text-info"></i>${torrent.peers}`;
                    row.appendChild(peersCell);

                    const progressCell = document.createElement('td');
                    let percent = Math.round((torrent.percentDone || 0) * 100);
                    if (percent < 0 || isNaN(percent)) {
                        percent = 0;
                    }

                    if (torrent.status === 'completed') {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-success';
                        badge.innerHTML = '<i class="fas fa-check-circle me-1"></i> 100%';
                        progressCell.appendChild(badge);
                    } else if (torrent.status === 'zipping') {
                        const progressDiv = document.createElement('div');
                        progressDiv.className = 'progress';
                        progressDiv.style.height = '20px';

                        const progressBar = document.createElement('div');
                        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-info';
                        progressBar.style.width = '100%';
                        progressBar.innerHTML = '<i class="fas fa-file-archive me-1"></i> Pakowanie...';

                        progressDiv.appendChild(progressBar);
                        progressCell.appendChild(progressDiv);
                    } else if (torrent.status === 'waiting_for_metadata') {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-secondary';
                        badge.innerHTML = '<i class="fas fa-hourglass-start me-1"></i> Oczekiwanie na metadane';
                        progressCell.appendChild(badge);
                    } else {
                        const progressDiv = document.createElement('div');
                        progressDiv.className = 'progress';
                        progressDiv.style.height = '20px';

                        const progressBar = document.createElement('div');
                        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-warning';
                        progressBar.style.width = percent + '%';
                        progressBar.setAttribute('aria-valuenow', percent);
                        progressBar.setAttribute('aria-valuemin', '0');
                        progressBar.setAttribute('aria-valuemax', '100');
                        progressBar.innerHTML = `<i class="fas fa-spinner fa-pulse me-1"></i> ${percent}%`;

                        progressDiv.appendChild(progressBar);
                        progressCell.appendChild(progressDiv);
                    }
                    row.appendChild(progressCell);

                    const statusCell = document.createElement('td');
                    if (torrent.status === 'completed') {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-success';
                        badge.innerHTML = '<i class="fas fa-check-circle me-1"></i> Zakończone';
                        statusCell.appendChild(badge);
                    } else if (torrent.status === 'zipping') {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-primary';
                        badge.innerHTML = '<i class="fas fa-file-archive me-1"></i> Pakowanie';
                        statusCell.appendChild(badge);
                    } else if (torrent.status === 'error') {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-danger';
                        badge.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Błąd';
                        statusCell.appendChild(badge);
                    } else if (torrent.status === 'waiting_for_metadata') {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-secondary';
                        badge.innerHTML = '<i class="fas fa-hourglass-half me-1"></i> Oczekiwanie';
                        statusCell.appendChild(badge);
                    } else {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-warning text-dark';
                        badge.innerHTML = '<i class="fas fa-spinner fa-pulse me-1"></i> Pobieranie';
                        statusCell.appendChild(badge);
                    }
                    row.appendChild(statusCell);

                    const timeLeftCell = document.createElement('td');
                    if (torrent.time_left > 0) {
                        const hours = Math.floor(torrent.time_left / 3600);
                        const minutes = Math.floor((torrent.time_left % 3600) / 60);
                        const seconds = torrent.time_left % 60;
                        timeLeftCell.innerHTML = `<i class="fas fa-clock me-1"></i> ${hours}h ${minutes}m ${seconds}s`;
                    } else if (torrent.time_left === 0 && torrent.status !== 'error') {
                        timeLeftCell.innerHTML = '<i class="fas fa-trash-alt text-danger me-1"></i> Usunięty';
                    } else {
                        timeLeftCell.innerHTML = '-';
                    }
                    row.appendChild(timeLeftCell);

                    const actionsCell = document.createElement('td');
                    if (torrent.status === 'completed' && torrent.zip_exists) {
                        const downloadLink = document.createElement('a');
                        downloadLink.href = 'download.php?hash=' + encodeURIComponent(torrent.hash);
                        downloadLink.className = 'btn btn-success btn-sm me-2';
                        downloadLink.innerHTML = '<i class="fas fa-download me-1"></i>Pobierz ZIP';
                        actionsCell.appendChild(downloadLink);
                    }

                    if (torrent.status === 'zipping') {
                        const disabledButton = document.createElement('button');
                        disabledButton.className = 'btn btn-primary btn-sm me-2';
                        disabledButton.disabled = true;
                        disabledButton.innerHTML = '<i class="fas fa-spinner fa-pulse me-1"></i> Pakowanie';
                        actionsCell.appendChild(disabledButton);
                    }

                    if (torrent.status !== 'completed') {
                        const disabledButton = document.createElement('button');
                        disabledButton.className = 'btn btn-secondary btn-sm';
                        disabledButton.disabled = true;
                        disabledButton.innerHTML = '<i class="fas fa-file-archive me-1"></i>Niedostępne';
                        actionsCell.appendChild(disabledButton);
                    }

                    row.appendChild(actionsCell);

                    tableBody.appendChild(row);
                });

                dataTable.clear().rows.add($(tableBody).find('tr')).draw();
            })
            .catch(error => {
                console.error('Błąd podczas pobierania danych:', error);
                showAlert('Błąd podczas aktualizacji danych. Sprawdź logi.', 'danger');
            });
    }

    setInterval(updateTorrentTable, 5000);
    updateTorrentTable();
});
