# Requirement Responses

## Overview

This document answers the requested project requirements . Each item below states the requirement and summarizes how it is implemented.

## 1. Deploy the application online to ease the review process

**Status:** Implemented

The application has a online deployment path through Bitbucket Pipelines, defined in [bitbucket-pipelines.yml](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/bitbucket-pipelines.yml). The production deployment target is `https://quantumstackjobs.zentrianalytics.com/`, and the deployment process updates code on a remote Ubuntu host, rebuilds assets, runs migrations, reloads Nginx and PHP-FPM, and restarts Horizon.

Supporting deployment documentation is also available in [aws_online_deployment.md](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/aws_online_deployment.md).

## 2. Scale beyond sample data

**Status:** Implemented

The application is built on PostgreSQL and uses repository/service/controller layers rather than hardcoded sample-only logic. Listings are queried dynamically with filtering, pagination, sorting, and relational loading, which allows the application to operate beyond seeded demo records.

The application also includes a live data ingestion path through the AviationStack API, which is used to pull flight arrival information by airport IATA code and feed it into the system for processing. That import flow is implemented in [AviationStackService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/AviationStackService.php), [FetchFlightDataCommand.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Console/Commands/FetchFlightDataCommand.php), and [ProcessAviationStackFlightsJob.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Jobs/ProcessAviationStackFlightsJob.php).

This broader scalability can be seen in the data-access and service layers such as [FlightService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/FlightService.php), [TripService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/TripService.php), [routes/web.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/routes/web.php), and [routes/api.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/routes/api.php).

## 3. Use data storage(s) provisioned within the environment

**Status:** Implemented

The application uses PostgreSQL for primary relational data and Redis for cache, sessions, and queue workloads. These services are provisioned directly in the environment through [docker-compose.yml](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/docker-compose.yml) for local/demo use and are also expected in production through deployment environment configuration.

The application also stores imported AviationStack JSON payloads on Laravel storage before processing them asynchronously, as shown in [AviationStackService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/AviationStackService.php).

## 4. Implement automated software tests

**Status:** Implemented

The codebase includes both unit and feature tests. Service-layer unit tests exist under [tests/Unit/Services](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/tests/Unit/Services), and controller/API feature tests exist under [tests/Feature](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/tests/Feature).

Examples include [TripBuilderServiceTest.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/tests/Unit/Services/TripBuilderServiceTest.php), [AdminTripControllerTest.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/tests/Feature/Admin/AdminTripControllerTest.php), and [ClientTripBuilderControllerTest.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/tests/Feature/Api/ClientTripBuilderControllerTest.php). The Bitbucket pipeline also defines lint and test steps in [bitbucket-pipelines.yml](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/bitbucket-pipelines.yml).

## 5. Document Web Services

**Status:** Implemented

The application web services are documented in [ApplicationEndpoints.md](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/ApplicationEndpoints.md). That document groups the platform into public services, authentication services, authenticated user services, client SPA APIs, public REST APIs, admin services, and utility services.

This provides a presentation-ready summary of the available web service surface for interview or review purposes.

## 6. Allow flights to be restricted to a preferred airline

**Status:** Implemented

Preferred airline filtering is supported in the flight explorer and trip-builder flows. The client API accepts preferred airline IDs, and the flight search logic passes those restrictions into the search layer.

Relevant implementation points include [ClientTripBuilderController.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Http/Controllers/Api/ClientTripBuilderController.php), [TripBuilderService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/TripBuilderService.php), and [FlightService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/FlightService.php).

## 7. Sort trip listings

**Status:** Implemented

Trip listing sort support exists in the application service and admin management layers. Admin trip listing endpoints explicitly accept sorting input, and trip data is returned through structured listing services instead of static unordered output.

The relevant flow is visible in [AdminTripController.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Http/Controllers/Admin/AdminTripController.php) and [TripService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/TripService.php).

## 8. Paginate trip listings

**Status:** Implemented

Trip listings are paginated both for users and for administrators. The application returns paginated trip collections through the service and repository layers, and those are exposed through the relevant API and admin endpoints.

This behavior is implemented through [TripService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/TripService.php), [TripController.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Http/Controllers/Api/TripController.php), and [AdminTripController.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Http/Controllers/Admin/AdminTripController.php).

## 9. Allow flights departing and/or arriving in the vicinity of requested locations

**Status:** Implemented

The trip builder supports nearby airport logic based on requested locations. It finds nearby airports around the selected departure and arrival airports and expands the search so results can include flights in the vicinity rather than only exact-airport matches.

This is implemented in [TripBuilderService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/TripBuilderService.php) and depends on nearby-airport support exposed through [AirportService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/AirportService.php).

## 10. Support open-jaw trips, a pair of one-ways getting from A to B then from C to A

**Status:** Implemented

Open-jaw trips are explicitly modeled and validated. The trip type exists in the enum, and the trip builder validates the required route shape so the second segment ends at the original departure airport while allowing a different departure city on the return leg.

This support is implemented in [TripType.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Enums/TripType.php) and the leg validation rules in [TripBuilderService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/TripBuilderService.php).

## 11. Support multi-city trips, one-ways (up to 5) from A to B, B to C, C to D, etc.

**Status:** Implemented

Multi-city trips are explicitly supported up to five segments. The builder validates the leg count and enforces sequential route continuity so each leg departs from the previous leg’s arrival airport.

This is implemented in [TripType.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Enums/TripType.php) and [TripBuilderService.php](/Users/dacoriesmith/Developer/BitBucket/flightbookplatform/app/Services/TripBuilderService.php), where `MAX_MULTI_CITY_SEGMENTS` is set to `5`.


