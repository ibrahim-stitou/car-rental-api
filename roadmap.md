# 🚗 ROADMAP — Car Rental API (GES-CARS-2026)

> **Stack:** Laravel 13 (latest) · PHP 8.3 · MySQL 8.0 · JWT Auth · Spatie Permission · Media Library · Auditing · Swagger · Yajra DataTables

---

## 📋 Checklist de développement

### Phase 1 — Fondations (Core) ✅ COMPLÈTE
- [x] Structure de dossiers modulaire
- [x] Trait `ApiResponse` (avec support DataTables)
- [x] Trait `HasUuid`
- [x] Trait `HasMediaCollections`
- [x] `BaseController`
- [x] `BaseRepository` (avec méthode `datatable()`)
- [x] Exception Handler (réponses JSON uniformes)
- [x] Middleware JWT (`JwtMiddleware`)
- [x] Middleware Role (`RoleMiddleware`)
- [x] Middleware Log (`LogRequestMiddleware`)
- [x] Configuration `bootstrap/app.php` (routes API, middleware)
- [x] Configuration `AppServiceProvider` (bindings repositories)

### Phase 2 — Migrations & Base de données ✅ COMPLÈTE
- [x] Migration `users` (UUID, champs complets)
- [x] Migration `agencies`
- [x] Migration `vehicles`
- [x] Migration `technical_inspections`
- [x] Migration `vignettes`
- [x] Migration `insurances`
- [x] Migration `clients`
- [x] Migration `reservations`
- [x] Migration `maintenances`
- [x] Migration `billing_documents` & `billing_document_items` (BC, BR, BL, DV, FA, AV)

### Phase 3 — Modèles (Relations, Casts, Scopes, Media) ✅ COMPLÈTE
- [x] Modèle `User`
- [x] Modèle `Agency`
- [x] Modèle `Vehicle`
- [x] Modèle `TechnicalInspection`
- [x] Modèle `Vignette`
- [x] Modèle `Insurance`
- [x] Modèle `Client`
- [x] Modèle `Reservation`
- [x] Modèle `Maintenance`
- [x] Modèle `BillingDocument` & `BillingDocumentItem`

### Phase 4 — Module Auth (JWT) ✅ COMPLÈTE
- [x] Routes `auth.php`
- [x] `AuthController` (login, register, logout, refresh, me, forgot/reset password)
- [x] Configuration JWT dans `config/auth.php`

### Phase 5 — Module Rôles & Permissions ✅ COMPLÈTE
- [x] `PermissionSeeder` (toutes les permissions + billing)
- [x] `RoleSeeder` (super-admin, admin, manager, agent, viewer)
- [x] Routes `roles.php`
- [x] `RoleController`
- [x] `PermissionController`

### Phase 6 — Module Agences ✅ COMPLÈTE
- [x] `StoreAgencyRequest` / `UpdateAgencyRequest`
- [x] `AgencyResource`
- [x] `AgencyRepository` (avec `datatable()`)
- [x] `AgencyService` (avec `datatable()`)
- [x] `AgencyPolicy`
- [x] `AgencyController` (CRUD + media + restore + datatable)
- [x] Routes `agencies.php`
- [x] `AgencyFactory` + `AgencySeeder`

### Phase 7 — Module Véhicules ✅ COMPLÈTE
- [x] `StoreVehicleRequest` / `UpdateVehicleRequest`
- [x] `VehicleResource`
- [x] `VehicleRepository` (avec `datatable()`)
- [x] `VehicleService` (avec `datatable()`)
- [x] `VehiclePolicy`
- [x] `VehicleController` (CRUD + media + status + history)
- [x] Routes `vehicles.php`
- [x] `VehicleFactory` + `VehicleSeeder`

### Phase 8 — Module Visite Technique ✅ COMPLÈTE
- [x] `StoreTechnicalInspectionRequest` / `UpdateTechnicalInspectionRequest`
- [x] `TechnicalInspectionResource`
- [x] `TechnicalInspectionRepository` (avec `datatable()`)
- [x] `TechnicalInspectionService` (avec `datatable()`)
- [x] `TechnicalInspectionPolicy`
- [x] `TechnicalInspectionController`
- [x] Routes `technical-inspections.php`
- [x] `TechnicalInspectionFactory`

### Phase 9 — Module Vignette ✅ COMPLÈTE
- [x] `StoreVignetteRequest` / `UpdateVignetteRequest`
- [x] `VignetteResource`
- [x] `VignetteRepository` (avec `datatable()`)
- [x] `VignetteService` (avec `datatable()`)
- [x] `VignettePolicy`
- [x] `VignetteController`
- [x] Routes `vignettes.php`
- [x] `VignetteFactory`

### Phase 10 — Module Assurance ✅ COMPLÈTE
- [x] `StoreInsuranceRequest` / `UpdateInsuranceRequest`
- [x] `InsuranceResource`
- [x] `InsuranceRepository` (avec `datatable()`)
- [x] `InsuranceService` (avec `datatable()`)
- [x] `InsurancePolicy`
- [x] `InsuranceController`
- [x] Routes `insurances.php`
- [x] `InsuranceFactory`

### Phase 11 — Module Client ✅ COMPLÈTE
- [x] `StoreClientRequest` / `UpdateClientRequest`
- [x] `ClientResource`
- [x] `ClientRepository` (avec `datatable()`)
- [x] `ClientService` (avec `datatable()`)
- [x] `ClientPolicy`
- [x] `ClientController` (CRUD + blacklist + media)
- [x] Routes `clients.php`
- [x] `ClientFactory` + `ClientSeeder`

### Phase 12 — Module Réservation ✅ COMPLÈTE
- [x] `StoreReservationRequest` / `UpdateReservationRequest`
- [x] `ReservationResource`
- [x] `ReservationRepository` (avec `datatable()`)
- [x] `ReservationService` (avec `datatable()`)
- [x] `ReservationPolicy`
- [x] `ReservationController` (CRUD + workflow + calendar + stats)
- [x] Routes `reservations.php`
- [x] `ReservationFactory` + `ReservationSeeder`

### Phase 13 — Module Maintenance ✅ COMPLÈTE
- [x] `StoreMaintenanceRequest` / `UpdateMaintenanceRequest`
- [x] `MaintenanceResource`
- [x] `MaintenanceRepository` (avec `datatable()`)
- [x] `MaintenanceService` (avec `datatable()`)
- [x] `MaintenancePolicy`
- [x] `MaintenanceController`
- [x] Routes `maintenances.php`
- [x] `MaintenanceFactory`

### Phase 14 — Module Users & Profile ✅ COMPLÈTE
- [x] `StoreUserRequest` / `UpdateUserRequest`
- [x] `UserResource`
- [x] `UserRepository` (avec `datatable()`)
- [x] `UserService` (avec `datatable()`)
- [x] `UserPolicy`
- [x] `UserController` (CRUD + roles + avatar + activity)
- [x] `ProfileController`
- [x] Routes `users.php`
- [x] `UserFactory` + `UserSeeder`

### Phase 15 — Module Logs (Auditing) ✅ COMPLÈTE
- [x] Routes `logs.php`
- [x] `AuditController`

### Phase 16 — Module Billing (Facturation) ✅ COMPLÈTE
- [x] Migration `billing_documents` & `billing_document_items`
- [x] Modèles `BillingDocument` & `BillingDocumentItem`
- [x] `BillingRepository` (avec `datatable()`)
- [x] `BillingService` (avec `datatable()` + `createFromReservation()`)
- [x] `BillingPolicy`
- [x] `StoreBillingDocumentRequest` / `UpdateBillingDocumentRequest`
- [x] `BillingDocumentResource` & `BillingDocumentItemResource`
- [x] `BillingController` (CRUD + approve + mark-paid + statistics)
- [x] Routes `billing.php`
- [x] Support des 6 types de documents: BC, BR, BL, DV, FA, AV

### Phase 17 — Yajra DataTables ✅ COMPLÈTE
- [x] Installation de `yajra/laravel-datatables-oracle`
- [x] Méthode `datatable()` dans `BaseRepository`
- [x] Méthode `datatableResponse()` dans trait `ApiResponse`
- [x] Méthode `datatable()` dans tous les Services
- [x] Endpoint `datatable` dans tous les Controllers

### Phase 18 — Swagger / OpenAPI Documentation ⚠️ EN COURS
- [x] Annotations Swagger sur la plupart des controllers
- [ ] Génération complète `l5-swagger:generate`

### Phase 19 — Seeders complets ✅ COMPLÈTE
- [x] `DatabaseSeeder` orchestration (corrigé)
- [x] Données de test réalistes

### Phase 20 — Policies ✅ COMPLÈTE
- [x] `AgencyPolicy`
- [x] `VehiclePolicy`
- [x] `ClientPolicy`
- [x] `ReservationPolicy`
- [x] `BillingPolicy`

### Phase 21 — Tests ⬜ À FAIRE
- [ ] Tests Auth (login, JWT refresh)
- [ ] Tests Agency CRUD
- [ ] Tests Vehicle CRUD + Media
- [ ] Tests Reservation workflow
- [ ] Tests Billing CRUD
- [ ] Tests par module

### Phase 22 — Finalisation ⬜ À FAIRE
- [ ] `.env.example` complet
- [ ] `README.md` guide d'installation
- [ ] Code quality (Pint, PHPStan)
- [ ] Documentation modules dans `DOC/`

---

## 🎯 Résumé de l'implémentation

### ✅ **TERMINÉ** (Phases 1-20)
- **Core complet** : BaseRepository, BaseController, Traits (ApiResponse, HasUuid, HasMediaCollections)
- **Authentification JWT** : Login, Register, Logout, Refresh, Me, Forgot/Reset Password
- **Rôles & Permissions** : 5 rôles (super-admin, admin, manager, agent, viewer), 50+ permissions
- **10 Modules fonctionnels** : Agency, Vehicle, TechnicalInspection, Vignette, Insurance, Client, Reservation, Maintenance, User, Billing
- **Yajra DataTables** : Intégration complète pour tous les modules
- **Module Billing** : Support complet des 6 types de documents (BC, BR, BL, DV, FA, AV)
- **Policies** : AgencyPolicy, VehiclePolicy, ClientPolicy, ReservationPolicy, BillingPolicy
- **Media Library** : Upload/gestion de fichiers pour tous les modules
- **Auditing** : Traçabilité complète des actions

### 📊 **Statistiques**
- **Modèles** : 11 (User, Agency, Vehicle, Client, Reservation, Insurance, TechnicalInspection, Vignette, Maintenance, BillingDocument, BillingDocumentItem)
- **Migrations** : 14
- **Controllers** : 15+
- **Services** : 10
- **Repositories** : 10
- **Requests** : 20+
- **Resources** : 12+
- **Routes API** : 12 fichiers
- **Permissions** : 50+
- **Rôles** : 5

### 🚀 **Prochaines étapes**
1. Exécuter les migrations : `php artisan migrate:fresh --seed`
2. Générer la documentation Swagger : `php artisan l5-swagger:generate`
3. Tester les endpoints avec Postman/Insomnia
4. Écrire les tests unitaires et fonctionnels
5. Finaliser la documentation

---

> ✅ = Terminé | ⬜ = À faire | ⚠️ = En cours
