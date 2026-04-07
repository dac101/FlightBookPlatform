# AWS Online Deployment

## Overview

FlightBookPlatform is configured for online deployment through Bitbucket Pipelines. The production deployment target is the live application at `https://quantumstackjobs.zentrianalytics.com/`.

The CI/CD pipeline in [bitbucket-pipelines.yml](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/bitbucket-pipelines.yml)  connects to a remote Linux server over SSH, updates the codebase, prepares the Laravel environment, builds assets, runs migrations, and restarts the required services.

## Production Deployment Target

| Item | Value |
|------|-------|
| Production URL | `https://quantumstackjobs.zentrianalytics.com/` |
| Deployment trigger | Push to `main` branch |
| CI/CD platform | Bitbucket Pipelines |
| Remote deploy method | SSH remote execution |
| Web stack | Nginx + PHP-FPM |
| Queue manager | Laravel Horizon |
| Cache / queue backend | Redis |
| Database | PostgreSQL |

## Deployment Workflow

The production deployment is defined under the `main` branch pipeline. When changes are pushed to `main`, Bitbucket Pipelines runs a deployment step named `Deploy to production`.

The remote deployment script performs the following actions:

1. Connects to the remote Ubuntu server over SSH.
2. Clones the repository on first deploy if it is not already present.
3. Moves into `/var/www/flightbookplatform`.
4. Sets storage and cache directory permissions.
5. Checks out `main`, fetches the latest code, and hard-resets to the branch head.
6. Writes the production `.env` file from Bitbucket deployment variables.
7. Installs Composer dependencies without development packages.
8. Installs frontend dependencies and builds production assets.
9. Unzips the airports CSV asset bundle.
10. Runs Laravel database migrations in force mode.
11. Publishes Telescope assets.
12. Clears and rebuilds Laravel caches.
13. Reloads `php8.4-fpm` and `nginx`.
14. Creates or refreshes the `horizon.service` systemd unit.
15. Gracefully terminates old Horizon workers and restarts Horizon.

## Environment Configuration

The pipeline writes the production environment file during deployment. The generated `.env` includes application, database, logging, mail, Redis, and AviationStack settings.

Important production variables referenced by the pipeline include:

- `APP_KEY`
- `APP_URL`
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `SLACK_WEBHOOK_URL`
- `MAIL_MAILER`
- `MAIL_FROM_ADDRESS`
- `AVIATIONSTACK_API_KEY`

These values are stored securely in Bitbucket deployment variables rather than committed into the repository.

## Laravel Services Managed In Production

### Web Application

The application is served through Nginx with PHP 8.4 FPM. During deployment, both services are reloaded so the newest code and built assets are served immediately.

### Queue Processing

Laravel Horizon is managed as a systemd service on the production server. The deployment script ensures the service definition exists, reloads systemd, gracefully terminates older workers, and restarts Horizon with the latest code.

### Database And Cache

The application uses PostgreSQL for persistent data and Redis for cache, session, and queue workloads.

