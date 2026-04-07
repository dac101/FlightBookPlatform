# Flight Book Platform — Trip Builder

A fully functional Laravel flight booking web application and API for building and navigating trips for a single passenger.

---

## Project Overview

This application allows users to search and book flights across airports, build multi-segment trips, and manage their bookings through a modern single-page application interface.

### Supported Trip Types

| Type | Description |
|---|---|
| **One-Way** | A flight getting from A to B |
| **Round-Trip** | A pair of one-ways: A → B then B → A |
| **Open-Jaw** | A pair of one-ways: A → B then C → A |
| **Multi-City** | Up to 5 one-ways: A → B → C → D → E |

### Business Rules

- A trip **must** depart no earlier than the time of creation
- A trip **must** depart no later than 365 days after creation
- Trip price = sum of all referenced flight prices
- Flights are priced per single passenger in a neutral currency

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 13 |
| Frontend | Vue 3, Inertia.js v3, Vite |
| Auth | Laravel Breeze + Fortify |
| Queue | Laravel Horizon (Redis) |
| Database | PostgreSQL 17 |
| Cache/Sessions | Redis 7 |
| Web Server | Nginx |
| Mail (dev) | Mailpit |
| Containerisation | Docker Compose |

---

## Quick Start

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Mac / Windows / Linux)
- Git

### 1. Clone the repository

```bash
git clone https://bitbucket.org/zentrianalytics/flightbookplatform.git
cd flightbookplatform
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` and set your AviationStack API key (free at [aviationstack.com](https://aviationstack.com)):

```
AVIATIONSTACK_API_KEY=your_key_here
```

### 3. Build and start containers

```bash
docker compose up --build
```

> First build takes a few minutes. Subsequent starts are fast.

The entrypoint script automatically:
- Installs Composer dependencies
- Installs npm dependencies and builds frontend assets
- Waits for PostgreSQL to be ready
- Runs database migrations
- Seeds demo users, airlines, airports, and sample flights on first boot
- Reprocesses bundled AviationStack JSON files on first boot

### 4. Open the application

| Service | URL |
|---|---|
| Application | http://localhost |
| Mailpit (test mail) | http://localhost:8025 |
| Horizon dashboard | http://localhost/horizon |

If you want live AviationStack imports to work, set a valid `AVIATIONSTACK_API_KEY` in `.env` before starting the containers.

---

## Default Credentials

| Role | Email | Password |
|---|---|---|
| Admin | admin@flightbookplatform.com | password |
| User | user@flightbookplatform.com | password |

---

## API Endpoints

All API endpoints are prefixed with `/api/v1`.

### Public

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/airlines` | List all airlines (paginated) |
| GET | `/api/v1/airlines/{airline}` | Show airline with flights |
| GET | `/api/v1/airports` | List all airports (paginated) |
| GET | `/api/v1/airports/{airport}` | Show airport with flights |
| GET | `/api/v1/flights` | List flights (filterable) |
| GET | `/api/v1/flights/{flight}` | Show flight details |

#### Flight filters (query string)

| Parameter | Example | Description |
|---|---|---|
| `departure` | `?departure=YUL` | Filter by departure airport IATA code |
| `arrival` | `?arrival=YVR` | Filter by arrival airport IATA code |
| `airline` | `?airline=AC` | Filter by airline IATA code |

### Authenticated (Sanctum)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/trips` | List authenticated user's trips |
| POST | `/api/v1/trips` | Create a new trip |
| GET | `/api/v1/trips/{trip}` | Show trip with segments |
| PUT/PATCH | `/api/v1/trips/{trip}` | Update trip |
| DELETE | `/api/v1/trips/{trip}` | Delete trip |

---

## Artisan Commands

```bash
# Fetch live flight data from AviationStack (runs automatically every 12 hours)
docker compose exec php php artisan flights:fetch

# Run database migrations
docker compose exec php php artisan migrate

# Seed sample data
docker compose exec php php artisan db:seed

# Monitor queues
docker compose exec php php artisan horizon
```

---

## Scheduled Jobs

| Schedule | Command | Description |
|---|---|---|
| Every 12 hours (06:00 & 18:00) | `flights:fetch` | Fetches live arrivals for 8 airports from AviationStack API, saves raw JSON to storage, and dispatches processing jobs to the `flight-imports` Horizon queue |

---

## Sample Data

The database seeder includes the following sample data from the assignment specification:

**Airlines:** Air Canada (AC), WestJet (WJ), Porter Airlines (PD)

**Airports:** YUL (Montreal), YVR (Vancouver), YYZ (Toronto), YYC (Calgary)

**Flights:**

| Flight | Route | Departure | Arrival | Price |
|---|---|---|---|---|
| AC301 | YUL → YVR | 07:35 | 10:05 | $273.23 |
| AC302 | YVR → YUL | 11:30 | 19:11 | $220.63 |
| AC101 | YUL → YYZ | 06:00 | 07:30 | $149.00 |
| AC201 | YYZ → YVR | 09:00 | 11:45 | $310.00 |
| WJ500 | YYC → YYZ | 08:15 | 12:30 | $195.00 |
| PD100 | YYZ → YUL | 14:00 | 15:45 | $120.00 |

---

## Project Structure

```
flightbookplatform/
├── app/
│   ├── Console/Commands/        # FetchFlightDataCommand
│   ├── Enums/                   # UserRole, TripType, TripStatus, SegmentType
│   ├── Http/Controllers/Api/    # REST API controllers
│   ├── Jobs/                    # ProcessAviationStackFlightsJob
│   ├── Models/                  # Airline, Airport, Flight, Trip, TripSegment, User
│   ├── Policies/                # TripPolicy (ownership authorization)
│   └── Services/                # AviationStackService
├── database/
│   ├── migrations/
│   └── seeders/
├── docker-compose/
│   └── services/
│       ├── nginx/               # Nginx config
│       └── php-84/build/        # Dockerfile + entrypoint.sh
├── routes/
│   ├── api.php                  # Versioned API routes
│   └── web.php
├── docker-compose.yml
└── Documentation.md             # Docker troubleshooting guide
```

---

## How to add a job to be queue
```bash
docker compose exec php php artisan tinker --execute '                                                                                                                                                   
App\Jobs\ProcessAviationStackFlightsJob::dispatch(                                                                                                                                                       
"aviation_stack/YUL/2026-04-04_18-42-03.json",                                                                                                                                                       
"YUL"                                                                                                                                                                                                
)->onQueue("flight-imports");                                                                                                                                                                            
'
```

## Troubleshooting

See [Documentation.md](Documentation.md) for a detailed guide covering:

- PostgreSQL startup issues on macOS
- Docker named volume setup
- Redis and queue configuration
- Common error messages and fixes
