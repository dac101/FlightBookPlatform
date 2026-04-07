# FlightBookPlatform — Local Setup Guide

FlightBookPlatform integrates with the AviationStack API to fetch live arrival flight data by airport IATA code. The application stores the raw JSON response, then processes it asynchronously through Laravel Horizon so imported airlines, airports, and flights can power trip planning, search, and admin management features.

Commands to process the data

sudo docker compose exec php php artisan flights:fetch
sudo docker compose exec php php artisan flights:reprocess
## Stack

| Service    | Technology              | Port  |
|------------|-------------------------|-------|
| Web server | Nginx (alpine)          | 80    |
| App        | PHP 8.4 FPM             | —     |
| Queue      | Laravel Horizon (Redis) | —     |
| Scheduler  | Laravel Scheduler       | —     |
| Database   | PostgreSQL 17           | 5432  |
| Cache/Queue| Redis 7                 | 6379  |
| Mail (test)| Mailpit                 | 1025 / 8025 |

---

## Software Versions

The application runs on a Docker-based Laravel and Vue stack with PostgreSQL, Redis, and Nginx. The versions below reflect the container images and the current locked application dependencies in this repository.

### Infrastructure And Runtime Versions

| Component | Version |
|-----------|---------|
| PHP-FPM | `8.4` |
| Node.js | `22.x` |
| Nginx | `alpine` image |
| PostgreSQL | `17-alpine` |
| Redis | `7-alpine` |
| Mailpit | `latest` image |

### Backend Package Versions

| Package | Version |
|---------|---------|
| Laravel Framework | `13.3.0` |
| Inertia Laravel | `3.0.1` |
| Laravel Fortify | `1.36.2` |
| Laravel Horizon | `5.45.5` |
| Laravel Sanctum | `4.3.1` |
| Laravel Telescope | `5.19.0` |
| PHPUnit | `12.5.16` |

### Frontend Package Versions

| Package | Version |
|---------|---------|
| Vue | `3.5.32` |
| Inertia Vue 3 | `3.0.2` |
| Vite | `8.0.6` |
| Tailwind CSS | `4.2.2` |
| Laravel Vite Plugin | `3.0.1` |
| `@vitejs/plugin-vue` | `6.0.5` |

---

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running
- Git

That's it. PHP, Composer, and Node are all handled inside the containers.

---

## Quick Start

```bash
# 1. Clone the repo
git clone https://zentrianalytics-admin@bitbucket.org/zentrianalytics/flightbookplatform.git
cd flightbookplatform

# 2. Build and start all services
docker compose up --build


# 3. commands that should resolve most issues
docker compose up --build
sudo docker compose exec php npm install
sudo docker compose exec php npm run build
sudo docker compose exec php php artisan optimize  
sudo docker compose exec php php artisan flights:fetch 
sudo docker compose exec php php artisan migrate



```

On first boot the `php` container will automatically:
- Copy `.env.example` → `.env`
- Generate an `APP_KEY`
- Install Composer dependencies
- Install Node dependencies and build frontend assets
- Wait for PostgreSQL to be ready
- Run database migrations
- Seed demo users and sample flight data
- Reprocess bundled `aviation_stack` JSON files
- Start PHP-FPM

If you want live flight imports from AviationStack to work, set a valid `AVIATIONSTACK_API_KEY` in `.env` before the first `docker compose up --build`.

**The app will be available at: http://localhost**

** in the .env file replace AVIATIONSTACK_API_KEY=api_key with necessary key 
---

## Service URLs

| Service        | URL                        |
|----------------|----------------------------|
| Application    | http://localhost            |
| Mailpit (UI)   | http://localhost:8025       |

---

## Database Credentials (default)

Use these to connect with DBeaver, TablePlus, or any SQL client:

| Field    | Value                |
|----------|----------------------|
| Host     | `localhost`          |
| Port     | `5432`               |
| Database | `flightbookplatform` |
| Username | `flightbookplatform` |
| Password | `secret`             |

> To use custom credentials, set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`
> in a `.env` file in the project root **before** running `docker compose up` for the first time.

---

## Custom Environment Variables

Docker Compose reads your local `.env` file for these overrides:

| Variable          | Default              | Description                        |
|-------------------|----------------------|------------------------------------|
| `DB_DATABASE`     | `flightbookplatform` | Postgres database name             |
| `DB_USERNAME`     | `flightbookplatform` | Postgres username                  |
| `DB_PASSWORD`     | `secret`             | Postgres password                  |
| `APP_PORT`        | `80`                 | Host port for the web app          |
| `DB_PORT_FORWARD` | `5432`               | Host port forwarded to Postgres    |
| `REDIS_PORT_FORWARD` | `6379`            | Host port forwarded to Redis       |
| `MAIL_PORT_FORWARD` | `1025`             | Host port forwarded to Mailpit SMTP|
| `MAILPIT_UI_PORT` | `8025`               | Host port for Mailpit web UI       |

---

## Stopping the App

```bash
# Stop containers (keeps data)
docker compose down

# Stop and wipe ALL data (full reset)
docker compose down -v
 
docker compose up --build


```

---
