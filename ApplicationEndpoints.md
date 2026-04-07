## Application URL Endpoints

### Public Pages

`GET /`  
This is the public homepage for the platform. It introduces the application and gives guests access to sign in or register.

`GET /share/{token}`  
This opens a public shared trip page using a generated sharing token. It lets someone view a published itinerary without logging into the application.

### Authentication

`GET /login` and `POST /login`  
These routes display the login form and submit user credentials. They are used by returning users to start an authenticated session.

`POST /logout`  
This route signs the current user out of the application. It ends the session and returns the user to the public side of the platform.

`GET /register` and `POST /register`  
These routes display the registration form and create a new account. New users are created as standard users by default.

`GET /forgot-password` and `POST /forgot-password`  
These routes let a user request a password reset email. They are part of the account recovery flow for users who cannot sign in.

`GET /reset-password/{token}` and `POST /reset-password`  
These routes display the reset password form and save the new password. They complete the password reset flow after the user clicks the reset link.

`GET /email/verify`, `GET /email/verify/{id}/{hash}`, and `POST /email/verification-notification`  
These routes handle email verification for authenticated users. They show the verification notice, process the signed verification link, and resend the notification email when needed.

`PUT /password`  
This route updates the password for an authenticated user. It is used when a signed-in user wants to change their current password.

`GET /user/confirm-password`, `POST /user/confirm-password`, and `GET /user/confirmed-password-status`  
These routes support password confirmation for sensitive actions. They help Fortify verify that the authenticated user recently confirmed their password.

`GET /two-factor-challenge` and `POST /two-factor-challenge`  
These routes handle two-factor login challenges when a user has 2FA enabled. They are part of the authentication flow before full access is granted.

`POST /user/two-factor-authentication`, `DELETE /user/two-factor-authentication`, `POST /user/confirmed-two-factor-authentication`, `GET /user/two-factor-qr-code`, `GET /user/two-factor-secret-key`, `GET /user/two-factor-recovery-codes`, and `POST /user/two-factor-recovery-codes`  
These routes manage two-factor authentication settings and recovery codes. They allow users to enable, confirm, inspect, and disable 2FA on their account.

### Authenticated User Pages

`GET /dashboard`  
This is the main dashboard for authenticated and verified users. It acts as the main entry point after login.

`GET /help`  
This page shows tutorial or onboarding help content for signed-in users. It supports users who have not yet completed the guided introduction flow.

`POST /tutorial/seen`  
This route marks the onboarding tutorial as completed for the current user. It allows the rest of the application pages to become available after the tutorial gate is satisfied.

`GET /profile`, `PATCH /profile`, and `DELETE /profile`  
These routes display the user profile, update profile details, and delete the account. They support basic account management from the user side of the application.

`GET /trips`  
This page shows the user’s upcoming trips and booking history. It is focused on reviewing saved travel plans rather than building new ones.

`GET /trip-builder`  
This page opens the interactive trip builder workflow. It is used to search routes, choose flight combinations, and create new trips with minimal page refreshes.

`GET /flights`  
This page is the flight explorer for authenticated users. It lets users search, filter, and browse available flights before adding them to a trip.

`GET /airports/map`  
This page shows airport data on a map view. It is useful for browsing airport locations visually and understanding route geography.

`GET /settings`  
This page is the user settings hub. It provides a single place to access account preferences and related account-management tools.

`GET /trips/{trip}/map`  
This page displays a map view for a specific saved trip. It helps the user visualize the itinerary and the sequence of travel segments.

### Client SPA API Endpoints

`GET /client-api/trips`, `GET /client-api/trips/{trip}`, `PATCH /client-api/trips/{trip}`, and `DELETE /client-api/trips/{trip}`  
These endpoints power the signed-in user trips experience inside the SPA. They list a user’s trips, return a single trip with segments, update trip details, and delete a trip.

`POST /client-api/trips/from-flight`  
This endpoint creates a new trip directly from a selected flight. It reduces booking friction by letting users start a plan from the flight explorer.

`POST /client-api/trips/{trip}/segments/from-flight`  
This endpoint appends a flight to an existing trip. It is used when a user wants to extend a plan by adding another segment from a flight result.

`PUT /client-api/trips/{trip}/segments/replace-flight`  
This endpoint replaces the selected flight in a one-way trip. It supports low-friction editing when a user wants a different flight without rebuilding the whole plan.

`POST /client-api/trips/{trip}/make-public`  
This endpoint generates or returns a public share token for a trip. It allows the trip to be shared outside the authenticated application with a public URL.

`GET /client-api/flights`  
This endpoint powers the client-side flight explorer listing. It supports searching and filtering flights by route text, airline preference, price, and date-related criteria.

`GET /client-api/airports/search`  
This endpoint returns airport suggestions for search inputs. It supports city code, airport code, and general airport name lookup in the trip builder UI.

`GET /client-api/airports/map-data`  
This endpoint returns airport records with coordinate data for map rendering. It is used by the airport map and any UI that needs airport latitude and longitude.

`GET /client-api/airlines/options`  
This endpoint returns airline option data for filters and selectors. It is mainly used when a user wants to narrow results by one or more preferred airlines.

`POST /client-api/trip-builder/flights/search`  
This endpoint runs trip-builder flight searches for a specific leg or route request. It supports departure and arrival airport selection, nearby-airport expansion, filtering, and result sorting.

`POST /client-api/trip-builder/book`  
This endpoint finalizes a trip-builder booking request. It validates the legs, stores the trip and segments, and returns the booked itinerary data.

### Public REST API Endpoints

`GET /api/v1/airlines` and `GET /api/v1/airlines/{airline}`  
These endpoints expose airline records through the public JSON API. They are useful for integrations or frontend consumers that need airline listings and airline detail data.

`GET /api/v1/airports` and `GET /api/v1/airports/{airport}`  
These endpoints expose airport records through the public JSON API. They return airport details and related flight information for external or decoupled consumers.

`GET /api/v1/flights` and `GET /api/v1/flights/{flight}`  
These endpoints expose searchable flight data through the public JSON API. They support listing flights with filters and retrieving full flight details with relationships.

`GET /api/v1/trips`, `POST /api/v1/trips`, `GET /api/v1/trips/{trip}`, `PUT|PATCH /api/v1/trips/{trip}`, and `DELETE /api/v1/trips/{trip}`  
These endpoints provide authenticated trip management over the API using Sanctum. They allow external or decoupled clients to create, view, update, and delete the current user’s trips.

### Admin Pages And Admin API

`GET /admin`  
This is the admin dashboard page for authenticated administrators. It loads the admin SPA used to manage application data.

`GET /admin/api/dashboard/stats`  
This endpoint returns summary counts for users, airlines, airports, flights, and trips. It powers the admin dashboard overview cards and quick reporting widgets.

`GET /admin/api/users`, `POST /admin/api/users`, `GET /admin/api/users/{user}`, `PATCH /admin/api/users/{user}`, and `DELETE /admin/api/users/{user}`  
These endpoints provide full admin CRUD for user records. They are used to browse users, inspect details, create accounts, edit roles or profile data, and remove accounts.

`GET /admin/api/airlines/options`, `GET /admin/api/airlines`, `POST /admin/api/airlines`, `GET /admin/api/airlines/{airline}`, `PATCH /admin/api/airlines/{airline}`, and `DELETE /admin/api/airlines/{airline}`  
These endpoints provide airline management for administrators. They support both dropdown option loading and full CRUD operations inside the admin interface.

`GET /admin/api/airports/options`, `GET /admin/api/airports`, `POST /admin/api/airports`, `GET /admin/api/airports/{airport}`, `PATCH /admin/api/airports/{airport}`, and `DELETE /admin/api/airports/{airport}`  
These endpoints provide airport management for administrators. They allow admins to maintain airport master data, including codes, names, location details, and map coordinates.

`GET /admin/api/flights`, `POST /admin/api/flights`, `GET /admin/api/flights/{flight}`, `PATCH /admin/api/flights/{flight}`, and `DELETE /admin/api/flights/{flight}`  
These endpoints provide flight management for administrators. They are used to create, inspect, edit, search, and remove flight records from the admin side.

`GET /admin/api/trips`, `GET /admin/api/trips/{trip}`, `PATCH /admin/api/trips/{trip}`, and `DELETE /admin/api/trips/{trip}`  
These endpoints provide admin access to trip records across all users. They let administrators review trip details, adjust trip metadata, and delete trips when moderation or support work requires it.

`POST /admin/api/flight-imports`  
This endpoint uploads a raw flight data JSON file for a selected airport IATA code. It stores the file and dispatches a Horizon job so imported data can be processed asynchronously.

### Utility Endpoints

`GET /sanctum/csrf-cookie`  
This endpoint issues the CSRF cookie used by Sanctum and session-based SPA requests. It is commonly called before authenticated API requests from a browser client.
