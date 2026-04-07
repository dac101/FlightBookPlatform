# Document Web Services

## Overview

This document summarizes the web services exposed by FlightBookPlatform. The application provides a mix of public pages, authentication services, authenticated SPA endpoints, REST APIs, and admin management services.

The platform is built around flight discovery, trip building, booking management, and administration of core travel data such as users, airlines, airports, flights, and trips. The endpoint groups below are organized to support a clear interview walkthrough from public entry points through user workflows and finally into administrative services.

## 1. Public Web Services

### Homepage Service

`GET /`  
This is the public landing page for the application. It introduces the platform and gives guests access to sign in or create an account.

### Public Trip Sharing Service

`GET /share/{token}`  
This service displays a shared trip using a public token. It allows an itinerary to be viewed without requiring authentication.

## 2. Authentication Services

### Login Service

`GET /login`  
Displays the login form for existing users. It is the entry point into the authenticated application.

`POST /login`  
Submits user credentials and creates an authenticated session. It is used by standard users and admins to access protected features.

### Logout Service

`POST /logout`  
Ends the current authenticated session. It returns the user to the public side of the application.

### Registration Service

`GET /register`  
Displays the account registration form. It is used by new users joining the platform.

`POST /register`  
Creates a new user account. New registrations default to the Standard User role.

### Password Recovery Service

`GET /forgot-password`  
Displays the password reset request form. It allows a user to begin account recovery.

`POST /forgot-password`  
Sends a password reset link to the user’s email address. It supports users who cannot log in with their current password.

`GET /reset-password/{token}`  
Displays the password reset form for a valid reset token. It is the second step of the password recovery workflow.

`POST /reset-password`  
Stores the user’s new password after reset validation passes. It completes the password recovery process.

### Email Verification Service

`GET /email/verify`  
Displays the email verification notice for authenticated users. It reminds users to verify their email before accessing restricted features.

`GET /email/verify/{id}/{hash}`  
Processes the signed verification link. It marks the user’s email as verified when the request is valid.

`POST /email/verification-notification`  
Resends the email verification notification. It supports users who need a fresh verification link.

### Password Confirmation And Two-Factor Services

`PUT /password`  
Updates the password for the authenticated user. It is used inside account maintenance workflows.

`GET /user/confirm-password`, `POST /user/confirm-password`, `GET /user/confirmed-password-status`  
These services support password confirmation before sensitive operations. They are part of the Fortify security workflow.

`GET /two-factor-challenge`, `POST /two-factor-challenge`  
These services handle the two-factor authentication challenge during login. They add an additional verification step before full access is granted.

`POST /user/two-factor-authentication`, `DELETE /user/two-factor-authentication`, `POST /user/confirmed-two-factor-authentication`, `GET /user/two-factor-qr-code`, `GET /user/two-factor-secret-key`, `GET /user/two-factor-recovery-codes`, `POST /user/two-factor-recovery-codes`  
These services manage two-factor authentication setup and recovery. They support enabling, confirming, viewing, regenerating, and disabling 2FA for the current user.

## 3. Authenticated User Web Services

### User Dashboard Service

`GET /dashboard`  
Displays the main application dashboard for authenticated and verified users. It serves as the main entry point after login.

### Help And Tutorial Services

`GET /help`  
Displays tutorial or onboarding help content. It supports users who have not yet completed the tutorial flow.

`POST /tutorial/seen`  
Marks the tutorial as completed for the current user. This unlocks the rest of the gated application experience.

### Profile Service

`GET /profile`  
Displays the user profile page. It allows the user to review their personal information.

`PATCH /profile`  
Updates the user’s profile details. It supports changes such as name and email address.

`DELETE /profile`  
Deletes the authenticated user’s account. It completes account removal and ends the session.

### Trips And Planning Pages

`GET /trips`  
Displays the trips page with booking history and upcoming trips. It is focused on reviewing saved travel plans.

`GET /trip-builder`  
Displays the trip builder interface. It supports building one-way, round-trip, open-jaw, and multi-city itineraries.

`GET /flights`  
Displays the flight explorer page. It allows users to browse and filter flights before building or updating a trip.

`GET /trips/{trip}/map`  
Displays a map view for a specific trip. It helps users visualize the itinerary and route sequence.

`GET /airports/map`  
Displays airport information on a map. It supports geographic exploration of airport locations.

`GET /settings`  
Displays the settings hub page. It acts as a central place for account and preference-related options.

## 4. Client SPA API Services

These services support the no-refresh single-page user experience. They are consumed directly by the Vue frontend components.

### User Trip Management API

`GET /client-api/trips`  
Returns the authenticated user’s trip list. It supports the trips page and user account overview features.

`GET /client-api/trips/{trip}`  
Returns a single trip with its related segments. It is used when a user views itinerary details.

`PATCH /client-api/trips/{trip}`  
Updates trip details such as name, status, or departure information. It supports lightweight trip edits inside the SPA.

`DELETE /client-api/trips/{trip}`  
Deletes a trip owned by the authenticated user. It supports self-service trip cleanup.

### Quick Trip Creation And Editing API

`POST /client-api/trips/from-flight`  
Creates a new trip directly from a selected flight. It reduces friction by allowing users to start a plan from the flight explorer.

`POST /client-api/trips/{trip}/segments/from-flight`  
Appends a new segment to an existing trip. It supports growing a trip plan without restarting the booking flow.

`PUT /client-api/trips/{trip}/segments/replace-flight`  
Replaces the selected flight in a one-way trip. It supports quick changes when the user wants a better option.

`POST /client-api/trips/{trip}/make-public`  
Generates or returns a public sharing token for a trip. It enables public itinerary sharing through a shareable URL.

### Flight Search And Builder Support API

`GET /client-api/flights`  
Returns flight explorer results for the authenticated user. It supports filtering, sorting, and browsing flights in the SPA.

`GET /client-api/airports/search`  
Returns airport suggestions based on user input. It supports search by airport name, airport code, and city code.

`GET /client-api/airports/map-data`  
Returns airport data with coordinate information. It is used for map-based frontend features.

`GET /client-api/airlines/options`  
Returns airline options for selectors and filters. It supports preferred airline filtering in the booking flow.

`POST /client-api/trip-builder/flights/search`  
Searches flights for a trip-builder leg request. It supports route-based search, nearby airport expansion, filtering, sorting, and pagination.

`POST /client-api/trip-builder/book`  
Books and stores a trip from the trip-builder payload. It validates the itinerary and persists the trip with its segments.

## 5. Public REST API Services

These services provide JSON endpoints for external consumers and decoupled clients.

### Airline API

`GET /api/v1/airlines`  
Returns a list of airlines. It supports external consumers that need airline reference data.

`GET /api/v1/airlines/{airline}`  
Returns a single airline with related data. It is used when an integration needs airline detail information.

### Airport API

`GET /api/v1/airports`  
Returns a list of airports. It supports external consumers that need airport reference records.

`GET /api/v1/airports/{airport}`  
Returns a single airport with related information. It is useful for integrations that need airport detail data.

### Flight API

`GET /api/v1/flights`  
Returns a filtered list of flights. It supports external browsing and flight search use cases.

`GET /api/v1/flights/{flight}`  
Returns a single flight with its related records. It is used when a consumer needs complete flight details.

### Authenticated Trip API

`GET /api/v1/trips`  
Returns the authenticated user’s trips through the Sanctum-protected API. It supports external clients that need trip listing functionality.

`POST /api/v1/trips`  
Creates a new trip through the API. It supports programmatic or decoupled trip creation.

`GET /api/v1/trips/{trip}`  
Returns a single trip owned by the authenticated user. It enforces ownership rules through policy authorization.

`PUT /api/v1/trips/{trip}` and `PATCH /api/v1/trips/{trip}`  
Updates a trip through the API. It supports external editing workflows for trip details.

`DELETE /api/v1/trips/{trip}`  
Deletes a trip through the API. It allows an authenticated client to remove a user-owned trip.

## 6. Administrative Web Services

These services are restricted to authenticated administrators. They power the admin SPA and the backend data-management workflows.

### Admin Dashboard Service

`GET /admin`  
Displays the admin dashboard page. It is the SPA shell for administrative tools.

`GET /admin/api/dashboard/stats`  
Returns summary statistics for users, airlines, airports, flights, and trips. It powers dashboard widgets and high-level system reporting.

### Admin User Management Service

`GET /admin/api/users`, `POST /admin/api/users`, `GET /admin/api/users/{user}`, `PATCH /admin/api/users/{user}`, `DELETE /admin/api/users/{user}`  
These services provide full CRUD management for users. They support administration of accounts, roles, and account lifecycle operations.

### Admin Airline Management Service

`GET /admin/api/airlines/options`, `GET /admin/api/airlines`, `POST /admin/api/airlines`, `GET /admin/api/airlines/{airline}`, `PATCH /admin/api/airlines/{airline}`, `DELETE /admin/api/airlines/{airline}`  
These services manage airline records in the admin system. They support listing, lookup, option loading, creation, updating, and deletion.

### Admin Airport Management Service

`GET /admin/api/airports/options`, `GET /admin/api/airports`, `POST /admin/api/airports`, `GET /admin/api/airports/{airport}`, `PATCH /admin/api/airports/{airport}`, `DELETE /admin/api/airports/{airport}`  
These services manage airport master data. They support maintaining location, code, and mapping details for airports.

### Admin Flight Management Service

`GET /admin/api/flights`, `POST /admin/api/flights`, `GET /admin/api/flights/{flight}`, `PATCH /admin/api/flights/{flight}`, `DELETE /admin/api/flights/{flight}`  
These services manage flights within the admin application. They support search, inspection, creation, updates, and deletion of flight records.

### Admin Trip Management Service

`GET /admin/api/trips`, `GET /admin/api/trips/{trip}`, `PATCH /admin/api/trips/{trip}`, `DELETE /admin/api/trips/{trip}`  
These services let administrators inspect and manage trip records across all users. They support moderation, support, and data correction workflows.

### Admin Flight Import Service

`POST /admin/api/flight-imports`  
Uploads a raw JSON flight data file for processing. It stores the file and dispatches a background Horizon job to import the contained flight data.

## 7. Utility Service

### Sanctum CSRF Service

`GET /sanctum/csrf-cookie`  
Returns the CSRF cookie used by Sanctum and session-based SPA requests. It is typically called before authenticated browser-based API requests.

## 8. Interview Summary

FlightBookPlatform exposes web services across three main layers: public access, authenticated user workflows, and administrator management services. The architecture supports both server-rendered entry pages and SPA-style JSON endpoints, which makes it suitable for interactive travel booking flows while still exposing reusable REST services for integrations and external clients.
