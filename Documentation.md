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
- Start PHP-FPM

**The app will be available at: http://localhost**

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
```

---
