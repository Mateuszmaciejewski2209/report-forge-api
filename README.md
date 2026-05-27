# ReportForge API

Backend REST API (Laravel 13) dla aplikacji [data-report-studio](../data-report-studio).

## Wymagania

- PHP 8.3+
- Composer
- SQLite (domyślnie) lub MySQL/PostgreSQL

## Uruchomienie

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

API: `http://localhost:8000/api`

## Endpointy

| Metoda | Ścieżka | Opis |
|--------|---------|------|
| GET | `/api/health` | Status API |
| GET | `/api/dashboard` | Statystyki, wykresy, ostatnie raporty |
| GET | `/api/reports` | Lista raportów (`?status=&search=`) |
| GET | `/api/reports/{code}` | Szczegóły raportu |
| POST | `/api/reports` | Utworzenie raportu |
| POST | `/api/uploads` | Upload CSV (multipart `file`) |

## CORS

Ustaw `FRONTEND_URL` w `.env` (domyślnie `http://localhost:8080`).

## Frontend

W `data-report-studio` ustaw:

```env
VITE_API_URL=http://localhost:8000/api
```
