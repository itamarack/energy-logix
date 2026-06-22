# Dynamic Commission Engine

A Laravel 13 application that lets energy brokers define versioned commission formulas, validate them (syntax, undefined variables, circular dependencies), simulate their impact across all contracts before activation, and calculate per-contract commissions with a full audit trail.

---

## Prerequisites

- PHP 8.4+
- Composer
- Node.js 20+
- npm
- SQLite (default) or MySQL

---

## Local Setup

```bash
git clone <repo>
cd energyLogix
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

---

## Running the App

Start the full dev stack (server, queue, logs, Vite) in one command:

```bash
composer run dev
```

Or start the PHP server and Vite separately:

```bash
php artisan serve
npm run dev
```

---

## Building Frontend Assets

```bash
npm run build
```

---

## Running the Test Suite

```bash
php artisan test --compact
```

---

## API Overview

All endpoints are versioned under `/api/v1`.

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/formula-versions` | List all formula versions |
| `POST` | `/api/v1/formula-versions` | Create a new formula version (validates expression) |
| `GET` | `/api/v1/formula-versions/{id}` | Get a single formula version |
| `POST` | `/api/v1/formula-versions/{id}/activate` | Activate a formula version (deactivates all others) |
| `POST` | `/api/v1/formula-versions/{id}/simulate` | Dry-run simulation across all contracts |
| `GET` | `/api/v1/contracts` | List all contracts |
| `POST` | `/api/v1/contracts/{id}/calculate` | Calculate commission for a contract using the active formula |
| `GET` | `/api/v1/calculations` | List all calculation records (newest first) |
| `GET` | `/api/v1/calculations/{id}` | Get full audit record for a single calculation |

---

## Key Features

- **Formula builder with validation** — PascalCase variable references (`AnnualUsage`, `ContractValue`, `ContractLength`, `RiskScore`), custom recursive-descent parser, no `eval()`
- **Version control** — incrementing version numbers, at most one active version at any time
- **Dry-run simulation** — compare current vs. proposed formula totals across all contracts before activating
- **Per-contract calculation** — resolves intermediate variables in dependency order, evaluates the main expression, returns the result
- **Full audit trail** — every calculation persists input values, each intermediate step, and the final result; records are immutable

---

## Environment Variables

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Laravel application encryption key (set via `php artisan key:generate`) |
| `DB_CONNECTION` | Database driver — `sqlite` (default) or `mysql` |
| `DB_DATABASE` | Path to the SQLite file or MySQL database name |
