# EnergyLogix — Dynamic Commission Engine

This repository contains two independent sub-projects that together form the EnergyLogix platform:

| Sub-project | Stack | Directory |
|---|---|---|
| **API** | Laravel 13, PHP 8.3, SQLite | [`api/`](./api/) |
| **Client** | Vue 3, Vite, TanStack Query | [`web/`](./web/) |

---

## Getting Started

### 1. Start the API

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve        # → http://localhost:8000
```

### 2. Start the Client

```bash
cd web
npm install
npm run dev              # → http://localhost:5173
```

The frontend dev server proxies `/api/*` to `http://localhost:8000` automatically — no CORS configuration needed during development.

---

## API Reference

All endpoints are versioned under `/api/v1/`.

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/formula-versions` | List all formula versions |
| `POST` | `/api/v1/formula-versions` | Create a new formula version |
| `GET` | `/api/v1/formula-versions/{id}` | Get a single formula version |
| `POST` | `/api/v1/formula-versions/{id}/activate` | Activate a formula version |
| `POST` | `/api/v1/formula-versions/{id}/simulate` | Dry-run simulation (no persisted records) |
| `GET` | `/api/v1/contracts` | List all contracts |
| `POST` | `/api/v1/contracts/{id}/calculate` | Calculate commission using the active formula |
| `GET` | `/api/v1/calculations` | List all commission calculations (audit trail) |
| `GET` | `/api/v1/calculations/{id}` | Get a single calculation with full step breakdown |

---

## Running Tests (API)

```bash
cd api
php artisan test --compact
```

91 tests, all passing.

---

## Production Environment Variables

### API (`api/.env`)

```env
APP_URL=https://your-api-domain.com
FRONTEND_URL=https://your-frontend-domain.com   # Used for CORS
DB_CONNECTION=sqlite
QUEUE_CONNECTION=sync
```

### Web (`web/.env`)

```env
VITE_API_BASE_URL=https://your-api-domain.com   # Leave empty to use the Vite proxy
```
