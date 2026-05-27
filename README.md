# ReportForge API

Backend REST API (Laravel 13) dla aplikacji [data-report-studio](../data-report-studio).

## Uruchomienie — cały backend w Dockerze (zalecane)

```powershell
cd report-forge-api
docker compose up -d --build
```

| Usługa | Adres |
|--------|-------|
| API Laravel | http://localhost:8000 |
| PostgreSQL (z hosta) | localhost:5433 |
| Health check | http://localhost:8000/api/health |

**Frontend** nadal lokalnie (hot-reload):

```powershell
cd ../data-report-studio
npm run dev
```

### Przydatne komendy

```powershell
# logi API
docker compose logs -f app

# migracje od zera + seed
docker compose exec app php artisan migrate:fresh --seed

# zatrzymanie
docker compose down
```

### Pierwsza konfiguracja

Upewnij się, że masz `.env` z `APP_KEY` (np. `php artisan key:generate` raz lokalnie, albo skopiuj z `.env.example`).

W Dockerze `DB_HOST` jest nadpisywany na `postgres` — nie musisz zmieniać `.env` ręcznie.

## Uruchomienie lokalne (bez Dockera dla API)

| Co | Komenda |
|----|---------|
| Baza Docker | `docker compose up -d postgres` |
| API | `php artisan serve` (w `.env`: `DB_PORT=5433`) |
| Frontend | `npm run dev` w `data-report-studio` |

## PostgreSQL w Dockerze

- Obraz: **postgres:18-alpine**
- Port na hoście: **5433** (nie koliduje z innym PG na 5432)
- Wolumen: `/var/lib/postgresql` (wymaganie PG 18+)

Błąd po upgrade obrazu 16→18:

```bash
docker compose down
docker volume rm report-forge-api_report_forge_pgdata -f
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
```

## Endpointy

| Metoda | Ścieżka | Opis |
|--------|---------|------|
| GET | `/api/health` | Status API |
| GET | `/api/dashboard` | Statystyki, wykresy, ostatnie raporty |
| GET | `/api/reports` | Lista raportów |
| GET | `/api/reports/{code}` | Szczegóły raportu |
| POST | `/api/reports` | Utworzenie raportu |
| POST | `/api/uploads` | Upload CSV |

## Auth (sesja + ciasteczka, bez tokenów JWT)

- `POST /api/register`, `POST /api/login`, `POST /api/logout`, `GET /api/user`
- Google (Socialite): `GET /auth/google`

## Frontend `.env`

```env
VITE_API_URL=http://localhost:8000/api
```
