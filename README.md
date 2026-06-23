# EnergyLogix — Dynamic Commission Engine

## Overview
EnergyLogix is a full-stack Dynamic Commission Engine built to allow administrators to create and manage complex commission formulas for energy brokers without requiring any application code changes. 

The system supports creating custom formulas with math expressions, calculated intermediate variables, mathematical validation, cyclic dependency detection, and versioning. Administrators can simulate the impact of a new formula across all existing contracts before activating it, and every calculation generates a strict audit trail.

## Features & What It Does
1. **Formula Builder**: An intuitive UI to create commission formulas using base variables (Annual Usage, Contract Value, Contract Length, Risk Score) and custom intermediate variables. 
2. **Formula Versioning**: Support for multiple versions, ensuring historical data is preserved. Only one formula version can be active at a time.
3. **Commission Calculation**: Given a contract, the system securely evaluates the active mathematical expression, executing the calculation in the proper order and storing the result.
4. **Impact Simulation**: Before activating a new formula, a simulation dry-runs the formula across the database to compare current commissions vs new commissions, protecting revenue and preventing mistakes.
5. **Dependency Validation**: Automatically detects circular dependencies (e.g. `A = B`, `B = A`) using topological sorting to prevent infinite loops.
6. **Audit Trails**: Every calculation logs its date, the formula version used, all input values, and the step-by-step breakdown of how the final commission was calculated.

---

## Architecture

This repository contains two independent sub-projects that together form the EnergyLogix platform:

| Sub-project | Stack | Directory |
|---|---|---|
| **API** | Laravel 11, PHP 8.3, MySQL, PHPStan | [`api/`](./api/) |
| **Web** | Vue 3, Vite, Tailwind CSS, TanStack Query | [`web/`](./web/) |

### Backend Design (Laravel)
- **Data Transfer Objects (DTOs)**: Used to strictly type data payloads passing between the controller layer and the service/action layer.
- **Actions & Services**: Business logic is decoupled from controllers using single-responsibility Action classes and Service classes (`FormulaValidator`, `FormulaEvaluator`, `CommissionSimulator`).
- **Events & Queues**: Activating a formula triggers events that dispatch highly concurrent `CalculateCommission` jobs to the queue to recalculate millions of contracts quickly in the background.
- **Dependency Resolver**: Implements a Kahn's Algorithm topological sort to evaluate variable interdependencies safely.

### Frontend Design (Vue 3)
- Utilizes **TanStack Query (Vue Query)** for remote state management, caching, background-refreshing, and invalidating state queries automatically when formulas or contracts are mutated.
- Built utilizing **Tailwind CSS** for flat, minimal, responsive aesthetics.

---

## Getting Started

### 1. Start the API (Backend)
The backend is completely containerized utilizing Docker for MySQL. Ensure you have Docker running on your system.

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
```

To run the database and the backend server concurrently, simply run:
```bash
composer dev -- --force
```
*(This custom command automatically spins up the Docker container, waits for MySQL to be healthy, runs `migrate:fresh --seed` to give you a clean database, and boots the backend servers).*

The API will now be available at `http://localhost:8000`.

### 2. Start the Web (Frontend)

Open a **new terminal tab** and run:

```bash
cd web
npm install
npm run dev
```

The frontend will be available at `http://localhost:5173`. 
*(Note: The frontend Vite dev server proxies `/api/*` to `http://localhost:8000` automatically, so no CORS configuration is needed during development).*

---

## Testing & Static Analysis

The API is fully covered by Pest PHP tests and strictly analyzed with PHPStan Level 5.

```bash
cd api
composer test
```
*This single command will run Pint code-linting, PHPStan static analysis, and the Pest test suite.*

---

## API Reference

All endpoints are versioned under `/api/v1/`.

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/formula-versions` | List all formula versions |
| `POST` | `/api/v1/formula-versions` | Create a new formula version |
| `GET` | `/api/v1/formula-versions/{id}` | Get a single formula version |
| `PUT` | `/api/v1/formula-versions/{id}` | Update an inactive formula version |
| `POST` | `/api/v1/formula-versions/{id}/activate` | Activate a formula version |
| `POST` | `/api/v1/formula-versions/{id}/deactivate`| Deactivate a formula version |
| `POST` | `/api/v1/formula-versions/{id}/simulate` | Dry-run simulation across all contracts |
| `GET` | `/api/v1/contracts` | List all contracts |
| `POST` | `/api/v1/contracts/{id}/calculate` | Calculate commission manually |
| `GET` | `/api/v1/calculations` | List all commission calculations (audit trail) |
| `GET` | `/api/v1/calculations/{id}` | Get a single calculation with full step breakdown |
