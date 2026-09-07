<div align="center">

# 🌐 Torrent Downloader — Web Interface

**PHP web interface for managing torrent downloads**  
**Webowy panel PHP do zarządzania pobieraniem torrentów**

![PHP](https://img.shields.io/badge/PHP-Web-777BB4?logo=php&logoColor=white)
![Web](https://img.shields.io/badge/UI-HTML%2FCSS%2FJS-E34F26)
![Status](https://img.shields.io/badge/Project-Legacy%20Version-orange)

</div>

---

## 🇵🇱 Polski

`Torrent_downloader` to wcześniejsza wersja webowego panelu do obsługi pobierania torrentów. Repozytorium zawiera osobne moduły PHP odpowiedzialne m.in. za interfejs, przesyłanie plików, monitorowanie statusu, pobieranie gotowych danych oraz tworzenie archiwów ZIP.

### 🧩 Struktura projektu
- `index.php` — główny interfejs
- `upload.php` — obsługa przesyłania danych wejściowych
- `monitor.php` — podgląd i monitorowanie procesu
- `status.php` — informacje o aktualnym stanie
- `download.php` — obsługa pobierania gotowych plików
- `zipper.php` — tworzenie archiwów ZIP
- `functions.php` — funkcje wspólne
- `config.php` — konfiguracja aplikacji
- `css/`, `js/` — warstwa interfejsu
- `data/`, `logs/` — dane robocze i logi

### 🚀 Uruchomienie
Projekt wymaga serwera WWW z obsługą PHP oraz poprawnej konfiguracji usług używanych przez aplikację. Przed wdrożeniem sprawdź `config.php`, uprawnienia katalogów roboczych i konfigurację serwera.

### 🔐 Bezpieczeństwo
Nie wystawiaj panelu bez uwierzytelnienia i ograniczeń sieciowych. Katalogi robocze, logi oraz pliki konfiguracyjne nie powinny ujawniać danych wrażliwych. Używaj aplikacji wyłącznie do pobierania treści, do których masz prawo dostępu.

> Nowsza wersja projektu znajduje się również w repozytorium `Torrent_downloaderv2`.

---

## 🇬🇧 English

`Torrent_downloader` is an earlier version of a PHP-based web panel for managing torrent downloads. The repository separates the workflow into PHP modules for the main interface, uploads, monitoring, status reporting, completed-file downloads and ZIP archive creation.

### 🧩 Project structure
- `index.php` — main interface
- `upload.php` — input/upload handling
- `monitor.php` — process monitoring
- `status.php` — current status endpoint
- `download.php` — completed-file downloads
- `zipper.php` — ZIP archive creation
- `functions.php` — shared helpers
- `config.php` — application configuration
- `css/`, `js/` — frontend assets
- `data/`, `logs/` — working data and logs

### 🚀 Running
The project requires a PHP-capable web server and correctly configured supporting services. Review `config.php`, directory permissions and your web-server configuration before deployment.

### 🔐 Security
Do not expose the panel publicly without authentication and network restrictions. Protect configuration, logs and working directories from unintended access. Use the application only for content you are legally authorized to download.

> A newer iteration is available in the `Torrent_downloaderv2` repository.

---

## 👤 Maintainer / Opiekun repozytorium
**Swir**
