# 🚗 PROMPT COMPLET — APPLICATION DE LOCATION DE VOITURES (Backend Laravel)

> **Destiné à : Claude Code**
> **Objectif : Génération complète d'un backend Laravel modulaire, production-ready**

---

## 📋 CONTEXTE DU PROJET

Construire un **backend complet** pour une application de **location de voitures** (Car Rental SaaS) en utilisant **Laravel 11** avec une **architecture modulaire**, des **API RESTful** documentées via **Swagger/OpenAPI 3.0**, une authentification **JWT**, et toutes les best practices d'un développeur senior.

---

## 🏗️ STACK TECHNIQUE

| Composant | Technologie |
|---|---|
| Framework | Laravel 11 |
| PHP | >= 8.2 |
| Base de données | MySQL 8.0 |
| Authentification | tymon/jwt-auth |
| Autorisation | spatie/laravel-permission |
| Media/Documents | spatie/laravel-medialibrary |
| Audit/Logs | owen-it/laravel-auditing |
| Documentation API | darkaonline/l5-swagger (OpenAPI 3.0) |
| Tests | PHPUnit + Pest |
| Code Quality | Laravel Pint, PHPStan |

---

## 📁 ARCHITECTURE MODULAIRE

### Structure des répertoires

```
app/
├── Modules/
│   ├── Agency/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Requests/
│   │   ├── Resources/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Policies/
│   │   └── routes/
│   │       └── api.php
│   ├── Vehicle/
│   ├── TechnicalInspection/
│   ├── Vignette/
│   ├── Insurance/
│   ├── Client/
│   ├── Reservation/
│   ├── Maintenance/
│   ├── User/
│   └── Role/
├── Core/
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── JwtMiddleware.php
│   │   │   ├── RoleMiddleware.php
│   │   │   └── LogRequestMiddleware.php
│   │   └── Controllers/
│   │       └── BaseController.php
│   ├── Traits/
│   │   ├── ApiResponse.php
│   │   └── HasUuid.php
│   └── Exceptions/
│       └── Handler.php
routes/
├── api/
│   ├── auth.php
│   ├── agencies.php
│   ├── vehicles.php
│   ├── technical-inspections.php
│   ├── vignettes.php
│   ├── insurances.php
│   ├── clients.php
│   ├── reservations.php
│   ├── maintenances.php
│   ├── users.php
│   └── roles.php
```

---

## 🔐 MODULE AUTH (JWT)

### Installation & Configuration

```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### Routes — `routes/api/auth.php`

```php
Route::prefix('auth')->group(function () {
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/logout',          [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh',         [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('/me',               [AuthController::class, 'me'])->middleware('auth:api');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});
```

### AuthController

Implémenter les méthodes suivantes avec JWT :
- `login()` : validation email/password, retourner `access_token`, `token_type`, `expires_in`, `user`
- `register()` : créer utilisateur, assigner rôle par défaut
- `logout()` : invalider le token JWT
- `refresh()` : régénérer le token
- `me()` : retourner les infos de l'utilisateur connecté avec ses rôles et permissions

### Middleware JWT — `app/Core/Http/Middleware/JwtMiddleware.php`

```php
// Vérifier le token JWT sur toutes les routes protégées
// Retourner 401 si token absent/invalide/expiré
// Format de réponse uniforme via ApiResponse trait
```

---

## 🏢 MODULE AGENCES

### Modèle `Agency`

**Champs :**
- `id` (UUID)
- `name` (string, required)
- `address` (text)
- `city` (string)
- `country` (string, default: 'MA')
- `phone` (string)
- `email` (string, unique)
- `is_active` (boolean, default: true)
- `manager_id` (FK → users)
- `created_at`, `updated_at`, `deleted_at` (SoftDeletes)

**Relations :**
- `hasMany(Vehicle::class)`
- `hasMany(Client::class)`
- `hasMany(Reservation::class)`
- `belongsTo(User::class, 'manager_id')`

**Media Collections (spatie/medialibrary) :**
- `logo` : image unique du logo de l'agence
- `documents` : documents administratifs (KBIS, licences, etc.)

### Routes — `routes/api/agencies.php`

```php
Route::middleware(['auth:api', 'role:super-admin|admin'])->prefix('agencies')->group(function () {
    Route::get('/',                  [AgencyController::class, 'index']);
    Route::post('/',                 [AgencyController::class, 'store']);
    Route::get('/{id}',              [AgencyController::class, 'show']);
    Route::put('/{id}',              [AgencyController::class, 'update']);
    Route::delete('/{id}',           [AgencyController::class, 'destroy']);
    Route::post('/{id}/logo',        [AgencyController::class, 'uploadLogo']);
    Route::post('/{id}/documents',   [AgencyController::class, 'uploadDocuments']);
    Route::delete('/{id}/media/{mediaId}', [AgencyController::class, 'deleteMedia']);
    Route::get('/{id}/vehicles',     [AgencyController::class, 'vehicles']);
    Route::post('/{id}/restore',     [AgencyController::class, 'restore']);
});
```

### Fonctionnalités du Controller

- Pagination (15 par page, configurable)
- Filtrage : `name`, `city`, `is_active`
- Tri : `name`, `created_at`
- Recherche full-text
- Upload logo via Media Library
- Upload documents multiples
- Soft delete + restore

---

## 🚗 MODULE VÉHICULES

### Modèle `Vehicle`

**Champs :**
- `id` (UUID)
- `agency_id` (FK → agencies)
- `brand` (string) — ex: Renault, Peugeot
- `model` (string) — ex: Clio, 208
- `year` (integer)
- `registration_number` (string, unique) — immatriculation
- `vin` (string, unique) — numéro de châssis
- `color` (string)
- `category` (enum: economy, compact, midsize, suv, luxury, van)
- `fuel_type` (enum: gasoline, diesel, electric, hybrid)
- `transmission` (enum: manual, automatic)
- `seats` (integer)
- `daily_rate` (decimal 10,2) — tarif journalier
- `deposit_amount` (decimal 10,2) — caution
- `mileage` (integer) — kilométrage actuel
- `status` (enum: available, rented, maintenance, out_of_service)
- `is_active` (boolean, default: true)
- `notes` (text, nullable)
- `created_at`, `updated_at`, `deleted_at` (SoftDeletes)

**Relations :**
- `belongsTo(Agency::class)`
- `hasMany(TechnicalInspection::class)`
- `hasMany(Vignette::class)`
- `hasMany(Insurance::class)`
- `hasMany(Reservation::class)`
- `hasMany(Maintenance::class)`

**Media Collections :**
- `photos` : photos du véhicule (multiple, max 10 images, 5MB chacune, jpeg/png/webp)
- `registration_card` : carte grise (PDF ou image, unique)
- `documents` : autres documents du véhicule

**Accessors/Mutators :**
- `getFullNameAttribute()` : `brand + model + year`
- `getIsAvailableAttribute()` : vérifier si `status === 'available'`

### Routes — `routes/api/vehicles.php`

```php
Route::middleware('auth:api')->prefix('vehicles')->group(function () {
    Route::get('/',                          [VehicleController::class, 'index']);
    Route::post('/',                         [VehicleController::class, 'store'])->middleware('permission:create-vehicle');
    Route::get('/{id}',                      [VehicleController::class, 'show']);
    Route::put('/{id}',                      [VehicleController::class, 'update'])->middleware('permission:edit-vehicle');
    Route::delete('/{id}',                   [VehicleController::class, 'destroy'])->middleware('permission:delete-vehicle');
    Route::post('/{id}/photos',              [VehicleController::class, 'uploadPhotos']);
    Route::post('/{id}/registration-card',   [VehicleController::class, 'uploadRegistrationCard']);
    Route::post('/{id}/documents',           [VehicleController::class, 'uploadDocuments']);
    Route::delete('/{id}/media/{mediaId}',   [VehicleController::class, 'deleteMedia']);
    Route::get('/{id}/media',                [VehicleController::class, 'getMedia']);
    Route::patch('/{id}/status',             [VehicleController::class, 'updateStatus']);
    Route::get('/{id}/history',              [VehicleController::class, 'history']); // audit log
    Route::get('/{id}/reservations',         [VehicleController::class, 'reservations']);
    Route::post('/{id}/restore',             [VehicleController::class, 'restore']);
});
```

---

## 🔧 MODULE VISITE TECHNIQUE

### Modèle `TechnicalInspection`

**Champs :**
- `id` (UUID)
- `vehicle_id` (FK → vehicles)
- `inspection_date` (date)
- `expiry_date` (date)
- `result` (enum: passed, failed, pending)
- `inspection_center` (string)
- `inspector_name` (string, nullable)
- `observations` (text, nullable)
- `cost` (decimal 10,2, nullable)
- `next_inspection_date` (date, nullable)
- `created_by` (FK → users)
- `created_at`, `updated_at`, `deleted_at`

**Relations :**
- `belongsTo(Vehicle::class)`
- `belongsTo(User::class, 'created_by')`

**Media Collections :**
- `inspection_report` : rapport de visite technique (PDF, unique, max 10MB)
- `photos` : photos de l'inspection (multiple, max 5 images)

**Scopes :**
- `scopeExpired()` : visites expirées
- `scopeExpiringSoon($days = 30)` : visites expirant dans X jours
- `scopeByVehicle($vehicleId)`

### Routes — `routes/api/technical-inspections.php`

```php
Route::middleware('auth:api')->prefix('technical-inspections')->group(function () {
    Route::get('/',                           [TechnicalInspectionController::class, 'index']);
    Route::post('/',                          [TechnicalInspectionController::class, 'store']);
    Route::get('/expired',                    [TechnicalInspectionController::class, 'expired']);
    Route::get('/expiring-soon',              [TechnicalInspectionController::class, 'expiringSoon']);
    Route::get('/{id}',                       [TechnicalInspectionController::class, 'show']);
    Route::put('/{id}',                       [TechnicalInspectionController::class, 'update']);
    Route::delete('/{id}',                    [TechnicalInspectionController::class, 'destroy']);
    Route::post('/{id}/report',               [TechnicalInspectionController::class, 'uploadReport']);
    Route::post('/{id}/photos',               [TechnicalInspectionController::class, 'uploadPhotos']);
    Route::delete('/{id}/media/{mediaId}',    [TechnicalInspectionController::class, 'deleteMedia']);
});
```

---

## 🏷️ MODULE VIGNETTE

### Modèle `Vignette`

**Champs :**
- `id` (UUID)
- `vehicle_id` (FK → vehicles)
- `year` (integer) — année fiscale
- `issue_date` (date)
- `expiry_date` (date)
- `amount` (decimal 10,2)
- `payment_method` (enum: cash, bank_transfer, online)
- `payment_reference` (string, nullable)
- `is_paid` (boolean, default: false)
- `paid_at` (timestamp, nullable)
- `created_by` (FK → users)
- `created_at`, `updated_at`, `deleted_at`

**Relations :**
- `belongsTo(Vehicle::class)`
- `belongsTo(User::class, 'created_by')`

**Media Collections :**
- `vignette_document` : document vignette officiel (PDF/image, unique, max 5MB)
- `payment_proof` : preuve de paiement (PDF/image, unique, max 5MB)

**Scopes :**
- `scopeCurrentYear()` : vignette de l'année en cours
- `scopeExpired()`
- `scopeUnpaid()`

### Routes — `routes/api/vignettes.php`

```php
Route::middleware('auth:api')->prefix('vignettes')->group(function () {
    Route::get('/',                           [VignetteController::class, 'index']);
    Route::post('/',                          [VignetteController::class, 'store']);
    Route::get('/expired',                    [VignetteController::class, 'expired']);
    Route::get('/unpaid',                     [VignetteController::class, 'unpaid']);
    Route::get('/{id}',                       [VignetteController::class, 'show']);
    Route::put('/{id}',                       [VignetteController::class, 'update']);
    Route::delete('/{id}',                    [VignetteController::class, 'destroy']);
    Route::patch('/{id}/mark-paid',           [VignetteController::class, 'markAsPaid']);
    Route::post('/{id}/document',             [VignetteController::class, 'uploadDocument']);
    Route::post('/{id}/payment-proof',        [VignetteController::class, 'uploadPaymentProof']);
    Route::delete('/{id}/media/{mediaId}',    [VignetteController::class, 'deleteMedia']);
});
```

---

## 🛡️ MODULE ASSURANCE

### Modèle `Insurance`

**Champs :**
- `id` (UUID)
- `vehicle_id` (FK → vehicles)
- `insurance_company` (string)
- `policy_number` (string, unique)
- `type` (enum: third_party, comprehensive, all_risk)
- `start_date` (date)
- `end_date` (date)
- `premium_amount` (decimal 10,2) — prime d'assurance
- `deductible_amount` (decimal 10,2, nullable) — franchise
- `coverage_details` (json, nullable) — détails couverture
- `is_active` (boolean, default: true)
- `agent_name` (string, nullable)
- `agent_phone` (string, nullable)
- `notes` (text, nullable)
- `created_by` (FK → users)
- `created_at`, `updated_at`, `deleted_at`

**Relations :**
- `belongsTo(Vehicle::class)`
- `belongsTo(User::class, 'created_by')`

**Media Collections :**
- `policy_document` : police d'assurance (PDF, unique, max 20MB)
- `green_card` : carte verte (PDF/image, unique, max 5MB)
- `attachments` : pièces jointes diverses (multiple, max 10MB chacune)

**Scopes :**
- `scopeActive()`
- `scopeExpired()`
- `scopeExpiringSoon($days = 30)`

### Routes — `routes/api/insurances.php`

```php
Route::middleware('auth:api')->prefix('insurances')->group(function () {
    Route::get('/',                           [InsuranceController::class, 'index']);
    Route::post('/',                          [InsuranceController::class, 'store']);
    Route::get('/expired',                    [InsuranceController::class, 'expired']);
    Route::get('/expiring-soon',              [InsuranceController::class, 'expiringSoon']);
    Route::get('/{id}',                       [InsuranceController::class, 'show']);
    Route::put('/{id}',                       [InsuranceController::class, 'update']);
    Route::delete('/{id}',                    [InsuranceController::class, 'destroy']);
    Route::post('/{id}/policy-document',      [InsuranceController::class, 'uploadPolicyDocument']);
    Route::post('/{id}/green-card',           [InsuranceController::class, 'uploadGreenCard']);
    Route::post('/{id}/attachments',          [InsuranceController::class, 'uploadAttachments']);
    Route::delete('/{id}/media/{mediaId}',    [InsuranceController::class, 'deleteMedia']);
    Route::get('/{id}/media',                 [InsuranceController::class, 'getMedia']);
});
```

---

## 👤 MODULE CLIENT

### Modèle `Client`

**Champs :**
- `id` (UUID)
- `agency_id` (FK → agencies)
- `first_name` (string)
- `last_name` (string)
- `email` (string, unique)
- `phone` (string)
- `date_of_birth` (date)
- `nationality` (string)
- `id_type` (enum: cin, passport, residence_permit) — type pièce d'identité
- `id_number` (string) — numéro pièce d'identité
- `id_expiry_date` (date)
- `driving_license_number` (string)
- `driving_license_category` (string) — B, BE, C, etc.
- `driving_license_expiry` (date)
- `address` (text, nullable)
- `city` (string, nullable)
- `country` (string, default: 'MA')
- `is_blacklisted` (boolean, default: false)
- `blacklist_reason` (text, nullable)
- `notes` (text, nullable)
- `created_by` (FK → users)
- `created_at`, `updated_at`, `deleted_at`

**Relations :**
- `belongsTo(Agency::class)`
- `belongsTo(User::class, 'created_by')`
- `hasMany(Reservation::class)`

**Accessors :**
- `getFullNameAttribute()` : `first_name + last_name`
- `getIsLicenseValidAttribute()` : vérifier si le permis n'est pas expiré

**Media Collections :**
- `id_document` : CIN ou passeport (PDF/image, unique, max 5MB, jpeg/png/pdf)
- `driving_license` : permis de conduire (PDF/image, unique, max 5MB)
- `selfie` : photo d'identité (image, unique, max 2MB, jpeg/png)
- `other_documents` : autres documents (multiple, max 10MB)

### Routes — `routes/api/clients.php`

```php
Route::middleware('auth:api')->prefix('clients')->group(function () {
    Route::get('/',                           [ClientController::class, 'index']);
    Route::post('/',                          [ClientController::class, 'store'])->middleware('permission:create-client');
    Route::get('/blacklisted',                [ClientController::class, 'blacklisted']);
    Route::get('/{id}',                       [ClientController::class, 'show']);
    Route::put('/{id}',                       [ClientController::class, 'update'])->middleware('permission:edit-client');
    Route::delete('/{id}',                    [ClientController::class, 'destroy'])->middleware('permission:delete-client');
    Route::patch('/{id}/blacklist',           [ClientController::class, 'blacklist']);
    Route::patch('/{id}/unblacklist',         [ClientController::class, 'unblacklist']);
    Route::post('/{id}/id-document',          [ClientController::class, 'uploadIdDocument']);
    Route::post('/{id}/driving-license',      [ClientController::class, 'uploadDrivingLicense']);
    Route::post('/{id}/selfie',               [ClientController::class, 'uploadSelfie']);
    Route::post('/{id}/documents',            [ClientController::class, 'uploadDocuments']);
    Route::delete('/{id}/media/{mediaId}',    [ClientController::class, 'deleteMedia']);
    Route::get('/{id}/reservations',          [ClientController::class, 'reservations']);
    Route::post('/{id}/restore',              [ClientController::class, 'restore']);
});
```

---

## 📅 MODULE RÉSERVATION

### Modèle `Reservation`

**Champs :**
- `id` (UUID)
- `reservation_number` (string, unique) — généré automatiquement ex: RES-2024-001234
- `agency_id` (FK → agencies)
- `vehicle_id` (FK → vehicles)
- `client_id` (FK → clients)
- `created_by` (FK → users)
- `pickup_date` (datetime)
- `return_date` (datetime)
- `actual_return_date` (datetime, nullable)
- `pickup_location` (string)
- `return_location` (string)
- `status` (enum: pending, confirmed, active, completed, cancelled, no_show)
- `daily_rate` (decimal 10,2) — tarif au moment de la réservation
- `total_days` (integer) — calculé automatiquement
- `subtotal` (decimal 10,2)
- `discount_percentage` (decimal 5,2, default: 0)
- `discount_amount` (decimal 10,2, default: 0)
- `additional_fees` (decimal 10,2, default: 0)
- `total_amount` (decimal 10,2)
- `deposit_amount` (decimal 10,2)
- `deposit_paid` (boolean, default: false)
- `deposit_paid_at` (timestamp, nullable)
- `payment_status` (enum: pending, partial, paid, refunded)
- `payment_method` (enum: cash, card, bank_transfer, online, nullable)
- `initial_mileage` (integer, nullable)
- `final_mileage` (integer, nullable)
- `fuel_level_pickup` (enum: empty, quarter, half, three_quarters, full)
- `fuel_level_return` (enum: empty, quarter, half, three_quarters, full, nullable)
- `notes` (text, nullable)
- `cancellation_reason` (text, nullable)
- `cancelled_at` (timestamp, nullable)
- `created_at`, `updated_at`, `deleted_at`

**Relations :**
- `belongsTo(Agency::class)`
- `belongsTo(Vehicle::class)`
- `belongsTo(Client::class)`
- `belongsTo(User::class, 'created_by')`
- `hasMany(Payment::class)`

**Media Collections :**
- `contract` : contrat de location signé (PDF, unique, max 10MB)
- `pickup_photos` : photos au départ (multiple, max 20 images)
- `return_photos` : photos au retour (multiple, max 20 images)
- `damage_reports` : rapports de dégâts (PDF/image, multiple)

**Méthodes :**
- `calculateTotal()` : calculer le montant total
- `generateReservationNumber()` : générer le numéro unique
- `isOverdue()` : vérifier si le retour est en retard

### Routes — `routes/api/reservations.php`

```php
Route::middleware('auth:api')->prefix('reservations')->group(function () {
    Route::get('/',                           [ReservationController::class, 'index']);
    Route::post('/',                          [ReservationController::class, 'store'])->middleware('permission:create-reservation');
    Route::get('/calendar',                   [ReservationController::class, 'calendar']);
    Route::get('/overdue',                    [ReservationController::class, 'overdue']);
    Route::get('/statistics',                 [ReservationController::class, 'statistics']);
    Route::get('/{id}',                       [ReservationController::class, 'show']);
    Route::put('/{id}',                       [ReservationController::class, 'update'])->middleware('permission:edit-reservation');
    Route::delete('/{id}',                    [ReservationController::class, 'destroy'])->middleware('permission:delete-reservation');
    Route::patch('/{id}/confirm',             [ReservationController::class, 'confirm']);
    Route::patch('/{id}/activate',            [ReservationController::class, 'activate']); // pickup
    Route::patch('/{id}/complete',            [ReservationController::class, 'complete']); // return
    Route::patch('/{id}/cancel',              [ReservationController::class, 'cancel']);
    Route::post('/{id}/contract',             [ReservationController::class, 'uploadContract']);
    Route::post('/{id}/pickup-photos',        [ReservationController::class, 'uploadPickupPhotos']);
    Route::post('/{id}/return-photos',        [ReservationController::class, 'uploadReturnPhotos']);
    Route::post('/{id}/damage-report',        [ReservationController::class, 'uploadDamageReport']);
    Route::delete('/{id}/media/{mediaId}',    [ReservationController::class, 'deleteMedia']);
    Route::get('/{id}/invoice',               [ReservationController::class, 'generateInvoice']); // PDF
    Route::post('/{id}/restore',              [ReservationController::class, 'restore']);
});
```

---

## 🔩 MODULE MAINTENANCE

### Modèle `Maintenance`

**Champs :**
- `id` (UUID)
- `vehicle_id` (FK → vehicles)
- `type` (enum: oil_change, tire_change, brake_service, engine_repair, body_repair, electrical, cleaning, other)
- `description` (text)
- `maintenance_date` (date)
- `completion_date` (date, nullable)
- `mileage_at_service` (integer, nullable)
- `next_service_mileage` (integer, nullable)
- `next_service_date` (date, nullable)
- `cost` (decimal 10,2, nullable)
- `service_provider` (string, nullable)
- `status` (enum: scheduled, in_progress, completed, cancelled)
- `priority` (enum: low, medium, high, urgent)
- `created_by` (FK → users)
- `created_at`, `updated_at`, `deleted_at`

**Relations :**
- `belongsTo(Vehicle::class)`
- `belongsTo(User::class, 'created_by')`

**Media Collections :**
- `invoices` : factures de maintenance (PDF, multiple, max 10MB chacune)
- `photos_before` : photos avant intervention (multiple, max 10 images)
- `photos_after` : photos après intervention (multiple, max 10 images)

### Routes — `routes/api/maintenances.php`

```php
Route::middleware('auth:api')->prefix('maintenances')->group(function () {
    Route::get('/',                            [MaintenanceController::class, 'index']);
    Route::post('/',                           [MaintenanceController::class, 'store']);
    Route::get('/scheduled',                   [MaintenanceController::class, 'scheduled']);
    Route::get('/overdue',                     [MaintenanceController::class, 'overdue']);
    Route::get('/{id}',                        [MaintenanceController::class, 'show']);
    Route::put('/{id}',                        [MaintenanceController::class, 'update']);
    Route::delete('/{id}',                     [MaintenanceController::class, 'destroy']);
    Route::patch('/{id}/complete',             [MaintenanceController::class, 'complete']);
    Route::patch('/{id}/cancel',               [MaintenanceController::class, 'cancel']);
    Route::post('/{id}/invoices',              [MaintenanceController::class, 'uploadInvoices']);
    Route::post('/{id}/photos-before',         [MaintenanceController::class, 'uploadPhotosBefore']);
    Route::post('/{id}/photos-after',          [MaintenanceController::class, 'uploadPhotosAfter']);
    Route::delete('/{id}/media/{mediaId}',     [MaintenanceController::class, 'deleteMedia']);
});
```

---

## 👥 MODULE USERS

### Modèle `User`

**Champs :**
- `id` (UUID)
- `agency_id` (FK → agencies, nullable)
- `first_name` (string)
- `last_name` (string)
- `email` (string, unique)
- `password` (string, hashed)
- `phone` (string, nullable)
- `is_active` (boolean, default: true)
- `last_login_at` (timestamp, nullable)
- `email_verified_at` (timestamp, nullable)
- `remember_token`
- `created_at`, `updated_at`, `deleted_at`

**Traits :**
- `HasApiTokens` (JWT)
- `HasRoles` (Spatie)
- `HasAuditingTrait` (owen-it)

**Media Collections :**
- `avatar` : photo de profil (image, unique, max 2MB, avec conversion 'thumb' 100x100)

### Routes — `routes/api/users.php`

```php
Route::middleware(['auth:api', 'role:super-admin|admin'])->prefix('users')->group(function () {
    Route::get('/',                         [UserController::class, 'index']);
    Route::post('/',                        [UserController::class, 'store']);
    Route::get('/{id}',                     [UserController::class, 'show']);
    Route::put('/{id}',                     [UserController::class, 'update']);
    Route::delete('/{id}',                  [UserController::class, 'destroy']);
    Route::patch('/{id}/toggle-active',     [UserController::class, 'toggleActive']);
    Route::post('/{id}/avatar',             [UserController::class, 'uploadAvatar']);
    Route::post('/{id}/assign-role',        [UserController::class, 'assignRole']);
    Route::delete('/{id}/remove-role',      [UserController::class, 'removeRole']);
    Route::get('/{id}/permissions',         [UserController::class, 'permissions']);
    Route::get('/{id}/activity-logs',       [UserController::class, 'activityLogs']);
    Route::post('/{id}/restore',            [UserController::class, 'restore']);
});

// Profile routes (authenticated user)
Route::middleware('auth:api')->prefix('profile')->group(function () {
    Route::get('/',                         [ProfileController::class, 'show']);
    Route::put('/',                         [ProfileController::class, 'update']);
    Route::put('/password',                 [ProfileController::class, 'changePassword']);
    Route::post('/avatar',                  [ProfileController::class, 'uploadAvatar']);
});
```

---

## 🔑 MODULE RÔLES & PERMISSIONS (Spatie)

### Installation

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### Rôles prédéfinis

```
super-admin    → accès total à tout
admin          → gestion complète d'une agence
manager        → gestion opérationnelle (réservations, véhicules)
agent          → création de réservations et gestion clients
viewer         → lecture seule
```

### Permissions par module

```php
// Agences
'view-agency', 'create-agency', 'edit-agency', 'delete-agency'

// Véhicules
'view-vehicle', 'create-vehicle', 'edit-vehicle', 'delete-vehicle', 'manage-vehicle-documents'

// Visite technique
'view-technical-inspection', 'create-technical-inspection', 'edit-technical-inspection', 'delete-technical-inspection'

// Vignette
'view-vignette', 'create-vignette', 'edit-vignette', 'delete-vignette'

// Assurance
'view-insurance', 'create-insurance', 'edit-insurance', 'delete-insurance'

// Clients
'view-client', 'create-client', 'edit-client', 'delete-client', 'blacklist-client'

// Réservations
'view-reservation', 'create-reservation', 'edit-reservation', 'delete-reservation',
'confirm-reservation', 'activate-reservation', 'complete-reservation', 'cancel-reservation'

// Maintenance
'view-maintenance', 'create-maintenance', 'edit-maintenance', 'delete-maintenance'

// Users
'view-user', 'create-user', 'edit-user', 'delete-user', 'manage-user-roles'

// Rôles
'view-role', 'create-role', 'edit-role', 'delete-role', 'assign-permission'

// Logs
'view-logs'
```

### Routes — `routes/api/roles.php`

```php
Route::middleware(['auth:api', 'role:super-admin'])->prefix('roles')->group(function () {
    Route::get('/',                              [RoleController::class, 'index']);
    Route::post('/',                             [RoleController::class, 'store']);
    Route::get('/{id}',                          [RoleController::class, 'show']);
    Route::put('/{id}',                          [RoleController::class, 'update']);
    Route::delete('/{id}',                       [RoleController::class, 'destroy']);
    Route::post('/{id}/permissions',             [RoleController::class, 'assignPermissions']);
    Route::delete('/{id}/permissions',           [RoleController::class, 'revokePermissions']);
});

Route::middleware(['auth:api', 'role:super-admin'])->prefix('permissions')->group(function () {
    Route::get('/',                              [PermissionController::class, 'index']);
    Route::post('/',                             [PermissionController::class, 'store']);
    Route::delete('/{id}',                       [PermissionController::class, 'destroy']);
});
```

---

## 📋 MODULE LOGS (Laravel Auditing)

### Installation

```bash
composer require owen-it/laravel-auditing
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="config"
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="migrations"
php artisan migrate
```

### Configuration

Ajouter l'interface `Auditable` et le trait `AuditingTrait` à tous les modèles :
- `Agency`, `Vehicle`, `TechnicalInspection`, `Vignette`, `Insurance`
- `Client`, `Reservation`, `Maintenance`, `User`

```php
// Dans chaque modèle
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Vehicle extends Model implements Auditable {
    use AuditableTrait;
    
    // Champs à exclure de l'audit
    protected $auditExclude = ['updated_at'];
}
```

### Routes — `routes/api/logs.php` (intégrer dans `routes/api/users.php` ou un fichier dédié)

```php
Route::middleware(['auth:api', 'permission:view-logs'])->prefix('logs')->group(function () {
    Route::get('/',                [AuditController::class, 'index']);
    Route::get('/{id}',            [AuditController::class, 'show']);
    Route::get('/model/{type}/{id}', [AuditController::class, 'byModel']); // logs d'un modèle spécifique
    Route::get('/user/{userId}',   [AuditController::class, 'byUser']);
    Route::delete('/{id}',         [AuditController::class, 'destroy'])->middleware('role:super-admin');
});
```

---

## 📦 MEDIA LIBRARY — CONFIGURATION GLOBALE

### Installation

```bash
composer require spatie/laravel-medialibrary
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

### Trait HasMediaCollections (partagé)

Créer `app/Core/Traits/HasMediaCollections.php` avec les méthodes helpers communes :

```php
trait HasMediaCollections
{
    // Upload un fichier dans une collection
    public function uploadMedia(UploadedFile $file, string $collection): Media
    {
        return $this->addMedia($file)
            ->usingFileName(Str::uuid() . '.' . $file->getClientOriginalExtension())
            ->toMediaCollection($collection);
    }

    // Retourner les médias d'une collection formatés
    public function getMediaByCollection(string $collection): array
    {
        return $this->getMedia($collection)->map(fn($media) => [
            'id'           => $media->id,
            'name'         => $media->name,
            'file_name'    => $media->file_name,
            'mime_type'    => $media->mime_type,
            'size'         => $media->size,
            'url'          => $media->getFullUrl(),
            'collection'   => $media->collection_name,
            'created_at'   => $media->created_at,
        ])->toArray();
    }
}
```

### Conversions d'images (exemple Vehicle)

```php
public function registerMediaConversions(Media $media = null): void
{
    $this->addMediaConversion('thumb')
         ->width(200)->height(150)->sharpen(5)->nonQueued();

    $this->addMediaConversion('medium')
         ->width(800)->height(600)->nonQueued();
}
```

---

## 🌐 CONFIGURATION DES ROUTES

### `bootstrap/app.php` ou `RouteServiceProvider`

```php
// Charger tous les fichiers de routes modulaires
$routeFiles = glob(base_path('routes/api/*.php'));
foreach ($routeFiles as $file) {
    Route::middleware('api')
         ->prefix('api/v1')
         ->group($file);
}
```

### Registrement dans `routes/api.php`

```php
<?php
// routes/api.php — fichier principal qui charge tous les modules

Route::prefix('v1')->middleware('api')->group(function () {
    require __DIR__ . '/api/auth.php';
    require __DIR__ . '/api/agencies.php';
    require __DIR__ . '/api/vehicles.php';
    require __DIR__ . '/api/technical-inspections.php';
    require __DIR__ . '/api/vignettes.php';
    require __DIR__ . '/api/insurances.php';
    require __DIR__ . '/api/clients.php';
    require __DIR__ . '/api/reservations.php';
    require __DIR__ . '/api/maintenances.php';
    require __DIR__ . '/api/users.php';
    require __DIR__ . '/api/roles.php';
    require __DIR__ . '/api/logs.php';
});
```

---

## 🔄 TRAITS & CLASSES CORE

### `app/Core/Traits/ApiResponse.php`

```php
trait ApiResponse
{
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    protected function error(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    protected function paginated($query, $resource, int $perPage = 15): JsonResponse
    protected function created($data, string $message = 'Created successfully'): JsonResponse
    protected function noContent(): JsonResponse  // 204
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    protected function validationError($errors): JsonResponse
}
```

### `app/Core/Http/Controllers/BaseController.php`

```php
abstract class BaseController extends Controller
{
    use ApiResponse;
    // Méthodes communes partagées entre les controllers
}
```

### `app/Core/Repositories/BaseRepository.php`

```php
abstract class BaseRepository
{
    protected Model $model;
    
    public function all(array $filters = [], array $relations = [])
    public function findById(string $id, array $relations = [])
    public function create(array $data)
    public function update(string $id, array $data)
    public function delete(string $id)
    public function restore(string $id)
    public function paginate(int $perPage = 15)
    public function findByField(string $field, $value)
}
```

---

## 📊 SWAGGER / OPENAPI DOCUMENTATION

### Installation

```bash
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

### Configuration `.env`

```env
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_UI_DOC_EXPANSION=list
L5_SWAGGER_CONST_HOST=http://localhost:8000/api/v1
```

### Annotations Swagger obligatoires

Sur **chaque Controller** (exemple complet pour VehicleController) :

```php
/**
 * @OA\Info(title="Car Rental API", version="1.0.0")
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 */

/**
 * @OA\Get(
 *   path="/vehicles",
 *   summary="Liste des véhicules",
 *   tags={"Vehicles"},
 *   security={{"bearerAuth":{}}},
 *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
 *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"available","rented","maintenance","out_of_service"})),
 *   @OA\Parameter(name="category", in="query", @OA\Schema(type="string")),
 *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
 *   @OA\Response(response=200, description="Success"),
 *   @OA\Response(response=401, description="Unauthenticated"),
 * )
 */

/**
 * @OA\Post(
 *   path="/vehicles/{id}/photos",
 *   summary="Upload photos du véhicule",
 *   tags={"Vehicles"},
 *   security={{"bearerAuth":{}}},
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\MediaType(
 *       mediaType="multipart/form-data",
 *       @OA\Schema(
 *         @OA\Property(property="photos[]", type="array", @OA\Items(type="string", format="binary"))
 *       )
 *     )
 *   ),
 *   @OA\Response(response=201, description="Photos uploaded")
 * )
 */
```

Documenter **tous les endpoints** de tous les modules avec leurs paramètres, request bodies, et réponses.

---

## 🗄️ MIGRATIONS

Créer les migrations dans l'ordre suivant :

1. `create_agencies_table`
2. `create_vehicles_table`
3. `create_technical_inspections_table`
4. `create_vignettes_table`
5. `create_insurances_table`
6. `create_clients_table`
7. `create_reservations_table`
8. `create_maintenances_table`

**Règles pour toutes les migrations :**
- Utiliser UUIDs comme clés primaires : `$table->uuid('id')->primary()`
- Ajouter `softDeletes()` sur toutes les tables
- Ajouter les index sur les FK et champs fréquemment filtrés
- Ajouter les contraintes `onDelete('restrict')` sur les FK critiques

---

## 🌱 SEEDERS

### `DatabaseSeeder.php`

```php
$this->call([
    PermissionSeeder::class,    // Créer toutes les permissions
    RoleSeeder::class,          // Créer les rôles et assigner les permissions
    AgencySeeder::class,        // 2 agences de test
    UserSeeder::class,          // super-admin, admin, agent
    VehicleSeeder::class,       // 10 véhicules
    ClientSeeder::class,        // 20 clients
    ReservationSeeder::class,   // 15 réservations dans différents états
]);
```

---

## ⚠️ GESTION DES ERREURS

### `app/Core/Exceptions/Handler.php`

Surcharger le `Handler` pour retourner des réponses JSON uniformes :

```php
// 401 Unauthenticated → { "success": false, "message": "Unauthenticated", "code": 401 }
// 403 AuthorizationException → { "success": false, "message": "Forbidden", "code": 403 }
// 404 ModelNotFoundException → { "success": false, "message": "Resource not found", "code": 404 }
// 422 ValidationException → { "success": false, "message": "Validation failed", "errors": {...}, "code": 422 }
// 500 Exception → { "success": false, "message": "Server Error", "code": 500 }
// JWT exceptions → TokenExpiredException, TokenInvalidException, JWTException
```

---

## ✅ FORM REQUESTS (Validation)

Créer un `FormRequest` dédié pour chaque action create/update de chaque module.

Exemple de règles pour `StoreVehicleRequest` :

```php
return [
    'agency_id'           => 'required|uuid|exists:agencies,id',
    'brand'               => 'required|string|max:50',
    'model'               => 'required|string|max:50',
    'year'                => 'required|integer|min:2000|max:' . (date('Y') + 1),
    'registration_number' => 'required|string|unique:vehicles,registration_number',
    'vin'                 => 'required|string|size:17|unique:vehicles,vin',
    'category'            => 'required|in:economy,compact,midsize,suv,luxury,van',
    'fuel_type'           => 'required|in:gasoline,diesel,electric,hybrid',
    'transmission'        => 'required|in:manual,automatic',
    'seats'               => 'required|integer|min:2|max:9',
    'daily_rate'          => 'required|numeric|min:0',
    'deposit_amount'      => 'required|numeric|min:0',
    'mileage'             => 'required|integer|min:0',
];
```

---

## 📤 API RESOURCES

Créer un `Resource` et un `ResourceCollection` pour chaque modèle avec transformation des données :

```php
// VehicleResource — inclure les URLs des médias
public function toArray($request): array
{
    return [
        'id'                  => $this->id,
        'agency'              => new AgencyResource($this->whenLoaded('agency')),
        'brand'               => $this->brand,
        'model'               => $this->model,
        'full_name'           => $this->full_name,
        'registration_number' => $this->registration_number,
        'status'              => $this->status,
        'daily_rate'          => $this->daily_rate,
        'photos'              => $this->getMediaByCollection('photos'),
        'registration_card'   => $this->getFirstMediaUrl('registration_card'),
        'documents_count'     => $this->getMedia('documents')->count(),
        'created_at'          => $this->created_at->toISOString(),
    ];
}
```

---

## 🧪 TESTS

Créer des tests Feature pour chaque module :

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   └── JwtRefreshTest.php
│   ├── Agency/
│   │   ├── AgencyCrudTest.php
│   │   └── AgencyMediaTest.php
│   ├── Vehicle/
│   │   ├── VehicleCrudTest.php
│   │   └── VehicleMediaUploadTest.php
│   ├── Reservation/
│   │   └── ReservationWorkflowTest.php
│   └── ...
```

---

## 🔧 FICHIERS DE CONFIGURATION

### `.env.example` — Variables requises

```env
APP_NAME="Car Rental API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=car_rental
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=
JWT_TTL=60
JWT_REFRESH_TTL=20160

MEDIA_DISK=public

L5_SWAGGER_GENERATE_ALWAYS=true
```

### `config/jwt.php`
- TTL: 60 minutes
- Refresh TTL: 14 jours
- Algorithme: HS256

---

## 📝 COMMANDES D'INSTALLATION COMPLÈTES

```bash
# 1. Créer le projet Laravel
composer create-project laravel/laravel car-rental-api

# 2. Packages requis
composer require tymon/jwt-auth
composer require spatie/laravel-permission
composer require spatie/laravel-medialibrary
composer require owen-it/laravel-auditing
composer require darkaonline/l5-swagger

# 3. Publier les configs
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="config"
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

# 4. Générer JWT secret
php artisan jwt:secret

# 5. Migrations & Seeders
php artisan migrate
php artisan db:seed

# 6. Générer doc Swagger
php artisan l5-swagger:generate

# 7. Storage link
php artisan storage:link
```

---

## 🎯 RÉSUMÉ DES LIVRABLES ATTENDUS

Claude Code doit générer :

- [ ] Structure de dossiers modulaire complète
- [ ] Tous les modèles avec relations, casts, scopes, media collections
- [ ] Toutes les migrations avec UUIDs, indexes, softDeletes
- [ ] Tous les Controllers avec CRUD complet + upload media
- [ ] Tous les Form Requests avec règles de validation
- [ ] Tous les API Resources (transformation des données)
- [ ] Tous les fichiers de routes séparés par module
- [ ] Tous les Services et Repositories
- [ ] Toutes les Policies Spatie par module
- [ ] Middleware JWT + RoleMiddleware
- [ ] Gestion centralisée des erreurs (Handler)
- [ ] Trait ApiResponse uniforme
- [ ] Seeders complets (permissions, rôles, données de test)
- [ ] Documentation Swagger complète sur tous les endpoints
- [ ] Tests Feature de base pour chaque module
- [ ] Fichier `.env.example` complet
- [ ] README.md avec guide d'installation

---

> **Note :** Respecter les best practices Laravel : Dependency Injection, Repository Pattern, Service Layer, Form Requests pour la validation, API Resources pour la transformation, Policies pour l'autorisation fine, et annotations Swagger sur chaque endpoint.
