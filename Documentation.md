# FlightBookPlatform — Local Setup Guide

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

## Troubleshooting

### ❌ PostgreSQL — Full Incident Report

This was the most involved issue during setup. Documented in full so you
understand what was tried, why it failed, and what the final fix is.

---

#### Stage 1 — "directory exists but is not empty"

**Error:**
```
initdb: error: directory "/var/lib/postgresql/data" exists but is not empty
initdb: hint: Using a mount point directly as the data directory is not recommended.
```

**Attempt 1 — move data to a `pgdata/` subdirectory with a `.gitkeep`**

The original setup used a bind mount (`./docker-compose/storage/postgres`) to
keep data on the host. Postgres refused because the directory contained a
`.gitkeep` file. We moved to a `pgdata/` subdirectory and placed `.gitkeep`
there — same error.

**Attempt 2 — remove `.gitkeep`**

Deleted the file. The directory appeared completely empty (`ls -la` showed
nothing). Postgres still refused. Checking extended attributes revealed the
real culprit:

```bash
$ xattr -l ./docker-compose/storage/postgres/pgdata/
com.apple.provenance:
com.docker.grpcfuse.ownership: {"UID":70,"GID":0,"mode":10000}
```

macOS silently attaches extended attributes to directories. These are invisible
to `ls` but Postgres's `initdb` sees them and treats the directory as non-empty.

**Attempt 3 — strip the extended attributes**

Ran `xattr -c` to clear them. The `com.docker.grpcfuse.ownership` attribute was
removed, but `com.apple.provenance` persisted — it is system-protected and
macOS re-attaches it. Cannot be deleted.

**Root cause:** macOS bind mounts and PostgreSQL `initdb` are fundamentally
incompatible because of `com.apple.provenance`.

**Final fix — Docker named volume**

Replaced the bind mount with a Docker named volume:

```yaml
postgres:
  volumes:
    - postgres_data:/var/lib/postgresql/data   # named volume, no macOS xattrs

volumes:
  postgres_data:
    driver: local
```

Docker named volumes live entirely inside Docker's own filesystem (not on the
macOS host), so `initdb` sees a truly empty directory and initializes cleanly.

---

#### Stage 2 — "Role does not exist" / Password authentication failed

**Error:**
```
FATAL: password authentication failed for user "flightbookplatform"
DETAIL: Role "flightbookplatform" does not exist.
```

**What happened:**

The named volume was created during an earlier failed run when `.env.example`
still had `DB_USERNAME=root` and `DB_PASSWORD=` (empty). Postgres initialized
with those credentials. After updating the defaults to `flightbookplatform` /
`secret`, new credentials were passed to the container but Postgres skipped
re-initialization because the volume already contained a database — the old
`root` user remained the only valid role.

**Attempt 1 — `docker compose down -v`**

The restart loop meant Docker didn't fully release the volume before it was
recreated on the next `up`. The old data persisted.

**Attempt 2 — `docker compose down -v --remove-orphans` + conditional volume rm**

```bash
docker compose down -v --remove-orphans
docker volume rm flightbookplatform_postgres_data 2>/dev/null || true
docker compose up --build
```

`--remove-orphans` cleans up containers no longer defined in the compose file.
The `2>/dev/null || true` suppresses errors if the volume didn't exist. However
the volume still wasn't fully cleared because Docker had already queued it for
recreation before the remove completed — the containers were still in a restart
loop holding a reference to it.

**Final fix — stop all containers first, then manually delete the volume by name**

```bash
docker compose down
docker volume rm flightbookplatform_postgres_data
docker compose up --build
```

Postgres initializes fresh with the correct credentials.

> **Key rule:** Postgres credentials in `docker-compose.yml` only take effect
> the **first time** a volume is created. If you ever change them you must
> delete the volume (`docker volume rm flightbookplatform_postgres_data`) and
> let Postgres reinitialize.

---

#### DBeaver / SQL client credentials (after clean start)

| Field    | Value                |
|----------|----------------------|
| Host     | `localhost`          |
| Port     | `5432`               |
| Database | `flightbookplatform` |
| Username | `flightbookplatform` |
| Password | `secret`             |

---

### ❌ "Failed to load TypeScript" during Vite build

Caused by macOS `node_modules` being mounted into the Linux container. Native
binaries built for macOS don't work on Linux.

**The setup already handles this** by using a named Docker volume for
`node_modules` that shadows the host directory. If you see this error:

```bash
docker compose down
docker volume rm flightbookplatform_node_modules
docker compose up --build
```

---

### ❌ Frontend changes not reflected in the browser

The frontend is built once at container startup. After changing JS/Vue files,
rebuild the assets:

```bash
docker compose exec php npm run build
```

---

### ❌ Port already in use

If port `80`, `5432`, or `6379` is already used on your machine, override them
in a `.env` file before starting:

```env
APP_PORT=8080
DB_PORT_FORWARD=5433
REDIS_PORT_FORWARD=6380
```

---

## Running Artisan Commands

```bash
docker compose exec php php artisan <command>

# Examples
docker compose exec php php artisan migrate
docker compose exec php php artisan tinker
docker compose exec php php artisan route:list
```

## Running Composer / npm

```bash
docker compose exec php composer require <package>
docker compose exec php npm install <package>
```
