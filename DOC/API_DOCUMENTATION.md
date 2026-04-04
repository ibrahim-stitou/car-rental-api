# 🚗 GES-CARS 2026 — API Documentation

> **Base URL:** `/api/v1`  
> **Authentication:** JWT Bearer Token  
> **Content-Type:** `application/json`

---

## 📑 Table des matières

1. [Authentification](#1-authentification)
2. [Profil Utilisateur](#2-profil-utilisateur)
3. [Utilisateurs (Admin)](#3-utilisateurs-admin)
4. [Agences](#4-agences)
5. [Véhicules](#5-véhicules)
6. [Clients](#6-clients)
7. [Réservations](#7-réservations)
8. [Facturation / Billing](#8-facturation--billing)
9. [Assurances](#9-assurances)
10. [Maintenances](#10-maintenances)
11. [Visites Techniques](#11-visites-techniques)
12. [Vignettes](#12-vignettes)
13. [Notifications](#13-notifications)
14. [Rôles & Permissions](#14-rôles--permissions)
15. [Logs d'Audit](#15-logs-daudit)
16. [Format des réponses](#16-format-des-réponses)

---

## 📦 Format des réponses standard

### ✅ Succès
```json
{
  "success": true,
  "message": "Success",
  "data": { ... }
}
```

### ✅ Succès paginé
```json
{
  "success": true,
  "message": "Success",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "...?page=1",
    "last": "...?page=5",
    "prev": null,
    "next": "...?page=2"
  }
}
```

### ❌ Erreur
```json
{
  "success": false,
  "message": "Error message",
  "code": 400
}
```

### ❌ Erreur de validation (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  },
  "code": 422
}
```

---

## 1. Authentification

**Prefix:** `/auth`

| Méthode | Endpoint              | Auth | Description                     |
|---------|-----------------------|------|---------------------------------|
| POST    | `/auth/login`         | ❌   | Connexion utilisateur           |
| POST    | `/auth/register`      | ❌   | Inscription utilisateur         |
| POST    | `/auth/forgot-password` | ❌ | Demande de réinitialisation MDP |
| POST    | `/auth/reset-password`  | ❌ | Réinitialiser le mot de passe   |
| POST    | `/auth/logout`        | ✅   | Déconnexion                     |
| POST    | `/auth/refresh`       | ✅   | Rafraîchir le token JWT         |
| GET     | `/auth/me`            | ✅   | Infos utilisateur connecté      |

### POST `/auth/login`
**Body:**
```json
{
  "email": "admin@ges-cars.ma",
  "password": "password123"
}
```
**Réponse 200:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id": "uuid",
      "first_name": "Admin",
      "last_name": "User",
      "email": "admin@ges-cars.ma",
      "phone": "0600000000",
      "is_active": true,
      "roles": ["admin"],
      "agency": { ... }
    }
  }
}
```

### POST `/auth/register`
**Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "0612345678"
}
```

### POST `/auth/forgot-password`
**Body:**
```json
{
  "email": "user@example.com"
}
```

### POST `/auth/reset-password`
**Body:**
```json
{
  "email": "user@example.com",
  "token": "reset_token_here",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

### GET `/auth/me`
**Headers:** `Authorization: Bearer {token}`  
**Réponse:** Données de l'utilisateur connecté avec ses rôles, permissions et agence.

---

## 2. Profil Utilisateur

**Prefix:** `/profile` — **Auth:** ✅ JWT

| Méthode | Endpoint             | Description                     |
|---------|----------------------|---------------------------------|
| GET     | `/profile`           | Voir le profil connecté         |
| PUT     | `/profile`           | Modifier le profil              |
| PUT     | `/profile/password`  | Changer le mot de passe         |
| POST    | `/profile/avatar`    | Upload avatar                   |

### PUT `/profile`
**Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "0612345678",
  "email": "john@example.com"
}
```

### PUT `/profile/password`
**Body:**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

### POST `/profile/avatar`
**Content-Type:** `multipart/form-data`  
**Body:** `avatar` — image (jpeg, png, webp) max 2MB

---

## 3. Utilisateurs (Admin)

**Prefix:** `/users` — **Auth:** ✅ JWT — **Rôles:** `super-admin | admin`

| Méthode | Endpoint                       | Description                     |
|---------|--------------------------------|---------------------------------|
| GET     | `/users`                       | Liste des utilisateurs          |
| POST    | `/users`                       | Créer un utilisateur            |
| GET     | `/users/{id}`                  | Détails d'un utilisateur        |
| PUT     | `/users/{id}`                  | Modifier un utilisateur         |
| DELETE  | `/users/{id}`                  | Supprimer un utilisateur (soft) |
| PATCH   | `/users/{id}/toggle-active`    | Activer/Désactiver              |
| POST    | `/users/{id}/avatar`           | Upload avatar                   |
| POST    | `/users/{id}/assign-role`      | Assigner un rôle                |
| DELETE  | `/users/{id}/remove-role`      | Retirer un rôle                 |
| GET     | `/users/{id}/permissions`      | Voir rôles et permissions       |
| GET     | `/users/{id}/activity-logs`    | Logs d'activité                 |
| POST    | `/users/{id}/restore`          | Restaurer un utilisateur        |

### GET `/users` — Query Parameters
| Paramètre   | Type    | Description              |
|--------------|---------|--------------------------|
| `agency_id`  | string  | Filtrer par agence       |
| `is_active`  | boolean | Filtrer par statut actif |
| `search`     | string  | Recherche textuelle      |
| `per_page`   | integer | Éléments par page (def: 15) |

### POST `/users`
**Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "0612345678",
  "agency_id": "uuid",
  "role": "admin"
}
```

### POST `/users/{id}/assign-role`
**Body:**
```json
{
  "role": "admin"
}
```

### DELETE `/users/{id}/remove-role`
**Body:**
```json
{
  "role": "viewer"
}
```

---

## 4. Agences

**Prefix:** `/agencies` — **Auth:** ✅ JWT — **Rôles:** `super-admin | admin`

| Méthode | Endpoint                              | Description                     |
|---------|---------------------------------------|---------------------------------|
| GET     | `/agencies`                           | Liste des agences               |
| POST    | `/agencies`                           | Créer une agence                |
| GET     | `/agencies/{id}`                      | Détails d'une agence            |
| PUT     | `/agencies/{id}`                      | Modifier une agence             |
| DELETE  | `/agencies/{id}`                      | Supprimer une agence (soft)     |
| POST    | `/agencies/{id}/logo`                 | Upload logo                     |
| POST    | `/agencies/{id}/documents`            | Upload documents                |
| DELETE  | `/agencies/{id}/media/{mediaId}`      | Supprimer un média              |
| GET     | `/agencies/{id}/vehicles`             | Véhicules de l'agence           |
| POST    | `/agencies/{id}/restore`              | Restaurer une agence            |

### GET `/agencies` — Query Parameters
| Paramètre   | Type    | Description               |
|--------------|---------|---------------------------|
| `city`       | string  | Filtrer par ville         |
| `is_active`  | boolean | Filtrer par statut actif  |
| `search`     | string  | Recherche textuelle       |
| `sort_by`    | string  | Tri: `name`, `created_at` |
| `sort_dir`   | string  | Direction: `asc`, `desc`  |
| `per_page`   | integer | Éléments par page (def: 15) |

### POST `/agencies`
**Body:**
```json
{
  "name": "Agence Casablanca",
  "email": "casa@ges-cars.ma",
  "address": "123 Bd Mohamed V",
  "city": "Casablanca",
  "country": "Maroc",
  "phone": "+212522000000",
  "manager_id": "uuid"
}
```

### POST `/agencies/{id}/logo`
**Content-Type:** `multipart/form-data`  
**Body:** `logo` — image (jpeg, png, webp) max 5MB

### POST `/agencies/{id}/documents`
**Content-Type:** `multipart/form-data`  
**Body:** `documents[]` — fichiers max 10MB chacun

---

## 5. Véhicules

**Prefix:** `/vehicles` — **Auth:** ✅ JWT

| Méthode | Endpoint                                | Permission            | Description                    |
|---------|-----------------------------------------|-----------------------|--------------------------------|
| GET     | `/vehicles`                             | —                     | Liste des véhicules            |
| POST    | `/vehicles`                             | `create-vehicle`      | Créer un véhicule              |
| GET     | `/vehicles/{id}`                        | —                     | Détails d'un véhicule          |
| PUT     | `/vehicles/{id}`                        | `edit-vehicle`        | Modifier un véhicule           |
| DELETE  | `/vehicles/{id}`                        | `delete-vehicle`      | Supprimer un véhicule (soft)   |
| POST    | `/vehicles/{id}/photos`                 | —                     | Upload photos (max 10)         |
| POST    | `/vehicles/{id}/registration-card`      | —                     | Upload carte grise             |
| POST    | `/vehicles/{id}/documents`              | —                     | Upload documents               |
| DELETE  | `/vehicles/{id}/media/{mediaId}`        | —                     | Supprimer un média             |
| GET     | `/vehicles/{id}/media`                  | —                     | Lister tous les médias         |
| PATCH   | `/vehicles/{id}/status`                 | —                     | Modifier le statut             |
| GET     | `/vehicles/{id}/history`                | —                     | Historique des modifications    |
| GET     | `/vehicles/{id}/reservations`           | —                     | Réservations du véhicule       |
| POST    | `/vehicles/{id}/restore`                | —                     | Restaurer un véhicule          |

### GET `/vehicles` — Query Parameters
| Paramètre      | Type    | Description                                          |
|----------------|---------|------------------------------------------------------|
| `agency_id`    | string  | Filtrer par agence                                   |
| `status`       | string  | `available`, `rented`, `maintenance`, `out_of_service` |
| `category`     | string  | Catégorie du véhicule                                |
| `fuel_type`    | string  | Type de carburant                                    |
| `transmission` | string  | Type de transmission                                 |
| `is_active`    | boolean | Actif/Inactif                                        |
| `search`       | string  | Recherche textuelle                                  |
| `per_page`     | integer | Éléments par page (def: 15)                          |

### POST `/vehicles`
**Body:**
```json
{
  "agency_id": "uuid",
  "brand": "Toyota",
  "model": "Corolla",
  "year": 2024,
  "registration_number": "12345-A-67",
  "vin": "1HGBH41JXMN109186",
  "color": "Blanc",
  "category": "sedan",
  "fuel_type": "gasoline",
  "transmission": "automatic",
  "seats": 5,
  "daily_rate": 350.00,
  "deposit_amount": 2000.00,
  "mileage": 15000,
  "notes": "Véhicule neuf"
}
```

### PATCH `/vehicles/{id}/status`
**Body:**
```json
{
  "status": "available"
}
```
**Valeurs possibles :** `available`, `rented`, `maintenance`, `out_of_service`

---

## 6. Clients

**Prefix:** `/clients` — **Auth:** ✅ JWT

| Méthode | Endpoint                              | Permission       | Description                    |
|---------|---------------------------------------|------------------|--------------------------------|
| GET     | `/clients`                            | —                | Liste des clients              |
| POST    | `/clients`                            | `create-client`  | Créer un client                |
| GET     | `/clients/blacklisted`                | —                | Clients blacklistés            |
| GET     | `/clients/{id}`                       | —                | Détails d'un client            |
| PUT     | `/clients/{id}`                       | `edit-client`    | Modifier un client             |
| DELETE  | `/clients/{id}`                       | `delete-client`  | Supprimer un client (soft)     |
| PATCH   | `/clients/{id}/blacklist`             | —                | Blacklister un client          |
| PATCH   | `/clients/{id}/unblacklist`           | —                | Retirer du blacklist           |
| POST    | `/clients/{id}/id-document`           | —                | Upload pièce d'identité        |
| POST    | `/clients/{id}/driving-license`       | —                | Upload permis de conduire      |
| POST    | `/clients/{id}/selfie`                | —                | Upload selfie                  |
| POST    | `/clients/{id}/documents`             | —                | Upload documents divers        |
| DELETE  | `/clients/{id}/media/{mediaId}`       | —                | Supprimer un média             |
| GET     | `/clients/{id}/reservations`          | —                | Réservations du client         |
| POST    | `/clients/{id}/restore`               | —                | Restaurer un client            |

### GET `/clients` — Query Parameters
| Paramètre        | Type    | Description              |
|------------------|---------|--------------------------|
| `agency_id`      | string  | Filtrer par agence       |
| `is_blacklisted` | boolean | Filtrer blacklistés      |
| `city`           | string  | Filtrer par ville        |
| `search`         | string  | Recherche textuelle      |
| `per_page`       | integer | Éléments par page (def: 15) |

### POST `/clients`
**Body:**
```json
{
  "agency_id": "uuid",
  "first_name": "Ahmed",
  "last_name": "Benjelloun",
  "email": "ahmed@example.com",
  "phone": "+212600000000",
  "date_of_birth": "1990-05-15",
  "nationality": "Marocaine",
  "id_type": "CIN",
  "id_number": "AB123456",
  "id_expiry_date": "2030-01-01",
  "driving_license_number": "DL123456",
  "driving_license_category": "B",
  "driving_license_expiry": "2028-06-15",
  "address": "123 Rue Hassan II",
  "city": "Rabat",
  "country": "Maroc",
  "notes": "Client fidèle"
}
```

### PATCH `/clients/{id}/blacklist`
**Body:**
```json
{
  "reason": "Non-paiement récurrent"
}
```

---

## 7. Réservations

**Prefix:** `/reservations` — **Auth:** ✅ JWT

| Méthode | Endpoint                                   | Permission              | Description                     |
|---------|--------------------------------------------|-------------------------|---------------------------------|
| GET     | `/reservations`                            | —                       | Liste des réservations          |
| POST    | `/reservations`                            | `create-reservation`    | Créer une réservation           |
| GET     | `/reservations/calendar`                   | —                       | Vue calendrier                  |
| GET     | `/reservations/overdue`                    | —                       | Réservations en retard          |
| GET     | `/reservations/statistics`                 | —                       | Statistiques                    |
| GET     | `/reservations/{id}`                       | —                       | Détails d'une réservation       |
| PUT     | `/reservations/{id}`                       | `edit-reservation`      | Modifier une réservation        |
| DELETE  | `/reservations/{id}`                       | `delete-reservation`    | Supprimer (soft)                |
| PATCH   | `/reservations/{id}/confirm`               | —                       | Confirmer                       |
| PATCH   | `/reservations/{id}/activate`              | —                       | Activer (départ)                |
| PATCH   | `/reservations/{id}/complete`              | —                       | Compléter (retour)              |
| PATCH   | `/reservations/{id}/cancel`                | —                       | Annuler                         |
| POST    | `/reservations/{id}/contract`              | —                       | Upload contrat (PDF)            |
| POST    | `/reservations/{id}/pickup-photos`         | —                       | Upload photos départ            |
| POST    | `/reservations/{id}/return-photos`         | —                       | Upload photos retour            |
| POST    | `/reservations/{id}/damage-report`         | —                       | Upload rapport de dommages      |
| DELETE  | `/reservations/{id}/media/{mediaId}`       | —                       | Supprimer un média              |
| GET     | `/reservations/{id}/invoice`               | —                       | Générer facture                 |
| POST    | `/reservations/{id}/restore`               | —                       | Restaurer                       |

### GET `/reservations` — Query Parameters
| Paramètre         | Type    | Description                                                    |
|-------------------|---------|----------------------------------------------------------------|
| `agency_id`       | string  | Filtrer par agence                                             |
| `vehicle_id`      | string  | Filtrer par véhicule                                           |
| `client_id`       | string  | Filtrer par client                                             |
| `status`          | string  | `pending`, `confirmed`, `active`, `completed`, `cancelled`, `no_show` |
| `payment_status`  | string  | `pending`, `partial`, `paid`, `refunded`                       |
| `search`          | string  | Recherche textuelle                                            |
| `per_page`        | integer | Éléments par page (def: 15)                                    |

### GET `/reservations/calendar` — Query Parameters
| Paramètre    | Type   | Description            |
|--------------|--------|------------------------|
| `agency_id`  | string | Filtrer par agence     |
| `start_date` | string | Date début (Y-m-d)    |
| `end_date`   | string | Date fin (Y-m-d)      |

### POST `/reservations`
**Body:**
```json
{
  "agency_id": "uuid",
  "vehicle_id": "uuid",
  "client_id": "uuid",
  "pickup_date": "2026-04-10T09:00:00",
  "return_date": "2026-04-15T18:00:00",
  "pickup_location": "Agence Casablanca",
  "return_location": "Agence Casablanca",
  "daily_rate": 350.00,
  "discount_percentage": 10,
  "additional_fees": 0,
  "deposit_amount": 2000.00,
  "payment_method": "card",
  "notes": "Client VIP"
}
```

### PATCH `/reservations/{id}/activate`
**Body:**
```json
{
  "initial_mileage": 45000,
  "fuel_level_pickup": "full"
}
```
**Valeurs fuel:** `empty`, `quarter`, `half`, `three_quarters`, `full`

### PATCH `/reservations/{id}/complete`
**Body:**
```json
{
  "final_mileage": 45350,
  "fuel_level_return": "three_quarters",
  "additional_fees": 50.00
}
```

### PATCH `/reservations/{id}/cancel`
**Body:**
```json
{
  "reason": "Client a annulé"
}
```

---

## 8. Facturation / Billing

**Prefix:** `/billing` — **Auth:** ✅ JWT

| Méthode | Endpoint                                        | Permission         | Description                         |
|---------|-------------------------------------------------|--------------------|-------------------------------------|
| GET     | `/billing`                                      | —                  | Liste des documents                 |
| GET     | `/billing/datatable`                            | —                  | DataTable (server-side)             |
| POST    | `/billing`                                      | `create-billing`   | Créer un document                   |
| GET     | `/billing/statistics`                           | —                  | Statistiques facturation            |
| POST    | `/billing/from-reservation/{reservationId}`     | —                  | Créer depuis une réservation        |
| GET     | `/billing/{id}`                                 | —                  | Détails d'un document               |
| PUT     | `/billing/{id}`                                 | `edit-billing`     | Modifier un document                |
| DELETE  | `/billing/{id}`                                 | `delete-billing`   | Supprimer (soft)                    |
| POST    | `/billing/{id}/approve`                         | `approve-billing`  | Approuver un document               |
| POST    | `/billing/{id}/mark-paid`                       | —                  | Marquer comme payé                  |
| POST    | `/billing/{id}/pdf`                             | —                  | Upload PDF du document              |
| POST    | `/billing/{id}/attachments`                     | —                  | Upload pièces jointes               |
| DELETE  | `/billing/{id}/media/{mediaId}`                 | —                  | Supprimer un média                  |
| POST    | `/billing/{id}/restore`                         | —                  | Restaurer un document               |

### Types de documents
| Code | Nom               |
|------|--------------------|
| `BC` | Bon de Commande    |
| `BR` | Bon de Réception   |
| `BL` | Bon de Livraison   |
| `DV` | Devis              |
| `FA` | Facture            |
| `AV` | Avoir              |

### GET `/billing` — Query Parameters
| Paramètre        | Type    | Description                                |
|------------------|---------|--------------------------------------------|
| `type`           | string  | Type: `BC`, `BR`, `BL`, `DV`, `FA`, `AV`  |
| `status`         | string  | Statut du document                         |
| `agency_id`      | string  | Filtrer par agence                         |
| `reservation_id` | string  | Filtrer par réservation                    |
| `client_id`      | string  | Filtrer par client                         |
| `search`         | string  | Recherche textuelle                        |
| `per_page`       | integer | Éléments par page (def: 15)                |

### POST `/billing`
**Body:**
```json
{
  "type": "FA",
  "agency_id": "uuid",
  "reservation_id": "uuid",
  "client_id": "uuid",
  "client_name": "Ahmed Benjelloun",
  "client_address": "123 Rue Hassan II, Rabat",
  "client_phone": "+212600000000",
  "client_email": "ahmed@example.com",
  "issue_date": "2026-04-02",
  "due_date": "2026-04-30",
  "tax_rate": 20.00,
  "discount_percentage": 0,
  "payment_method": "card",
  "notes": "Facture location véhicule",
  "items": [
    {
      "description": "Location Toyota Corolla - 5 jours",
      "quantity": 5,
      "unit_price": 350.00,
      "total_price": 1750.00
    }
  ]
}
```

### POST `/billing/from-reservation/{reservationId}`
**Body:**
```json
{
  "type": "FA"
}
```
**Types possibles :** `BC`, `DV`, `FA`

### POST `/billing/{id}/mark-paid`
**Body:**
```json
{
  "payment_method": "card",
  "payment_reference": "TXN-123456"
}
```
**Méthodes de paiement :** `cash`, `card`, `bank_transfer`, `check`, `online`

---

## 9. Assurances

**Prefix:** `/insurances` — **Auth:** ✅ JWT

| Méthode | Endpoint                                   | Description                     |
|---------|--------------------------------------------|---------------------------------|
| GET     | `/insurances`                              | Liste des assurances            |
| POST    | `/insurances`                              | Créer une assurance             |
| GET     | `/insurances/expired`                      | Assurances expirées             |
| GET     | `/insurances/expiring-soon`                | Assurances expirant bientôt     |
| GET     | `/insurances/{id}`                         | Détails d'une assurance         |
| PUT     | `/insurances/{id}`                         | Modifier une assurance          |
| DELETE  | `/insurances/{id}`                         | Supprimer (soft)                |
| POST    | `/insurances/{id}/policy-document`         | Upload police d'assurance (PDF) |
| POST    | `/insurances/{id}/green-card`              | Upload carte verte              |
| POST    | `/insurances/{id}/attachments`             | Upload pièces jointes           |
| DELETE  | `/insurances/{id}/media/{mediaId}`         | Supprimer un média              |
| GET     | `/insurances/{id}/media`                   | Lister tous les médias          |

### GET `/insurances` — Query Parameters
| Paramètre    | Type    | Description                                      |
|--------------|---------|--------------------------------------------------|
| `vehicle_id` | string  | Filtrer par véhicule                             |
| `type`       | string  | `third_party`, `comprehensive`, `all_risk`       |
| `is_active`  | boolean | Filtrer actives uniquement                       |
| `per_page`   | integer | Éléments par page (def: 15)                      |

### GET `/insurances/expiring-soon` — Query Parameters
| Paramètre | Type    | Description                        |
|-----------|---------|------------------------------------|
| `days`    | integer | Nombre de jours (défaut: 30)       |
| `per_page`| integer | Éléments par page (def: 15)        |

### POST `/insurances`
**Body:**
```json
{
  "vehicle_id": "uuid",
  "insurance_company": "Wafa Assurance",
  "policy_number": "POL-2026-001",
  "type": "all_risk",
  "start_date": "2026-01-01",
  "end_date": "2027-01-01",
  "premium_amount": 5000.00,
  "deductible_amount": 1000.00,
  "coverage_details": {"liability": true, "collision": true, "theft": true},
  "agent_name": "Mohamed Alami",
  "agent_phone": "+212600111222",
  "notes": "Renouvellement annuel"
}
```

---

## 10. Maintenances

**Prefix:** `/maintenances` — **Auth:** ✅ JWT

| Méthode | Endpoint                                    | Description                     |
|---------|---------------------------------------------|---------------------------------|
| GET     | `/maintenances`                             | Liste des maintenances          |
| POST    | `/maintenances`                             | Créer une maintenance           |
| GET     | `/maintenances/scheduled`                   | Maintenances planifiées         |
| GET     | `/maintenances/overdue`                     | Maintenances en retard          |
| GET     | `/maintenances/{id}`                        | Détails d'une maintenance       |
| PUT     | `/maintenances/{id}`                        | Modifier une maintenance        |
| DELETE  | `/maintenances/{id}`                        | Supprimer (soft)                |
| PATCH   | `/maintenances/{id}/complete`               | Marquer comme terminée          |
| PATCH   | `/maintenances/{id}/cancel`                 | Annuler                         |
| POST    | `/maintenances/{id}/invoices`               | Upload factures (PDF)           |
| POST    | `/maintenances/{id}/photos-before`          | Upload photos avant             |
| POST    | `/maintenances/{id}/photos-after`           | Upload photos après             |
| DELETE  | `/maintenances/{id}/media/{mediaId}`        | Supprimer un média              |

### GET `/maintenances` — Query Parameters
| Paramètre    | Type    | Description                                           |
|--------------|---------|-------------------------------------------------------|
| `vehicle_id` | string  | Filtrer par véhicule                                  |
| `status`     | string  | `scheduled`, `in_progress`, `completed`, `cancelled`  |
| `priority`   | string  | `low`, `medium`, `high`, `urgent`                     |
| `type`       | string  | Type de maintenance                                   |
| `per_page`   | integer | Éléments par page (def: 15)                           |

### POST `/maintenances`
**Body:**
```json
{
  "vehicle_id": "uuid",
  "type": "oil_change",
  "description": "Vidange huile moteur + filtre",
  "maintenance_date": "2026-04-10",
  "mileage_at_service": 50000,
  "next_service_mileage": 60000,
  "next_service_date": "2026-10-10",
  "cost": 500.00,
  "service_provider": "Garage Atlas",
  "status": "scheduled",
  "priority": "medium"
}
```

### PATCH `/maintenances/{id}/complete`
**Body:**
```json
{
  "cost": 450.00
}
```

---

## 11. Visites Techniques

**Prefix:** `/technical-inspections` — **Auth:** ✅ JWT

| Méthode | Endpoint                                            | Description                     |
|---------|-----------------------------------------------------|---------------------------------|
| GET     | `/technical-inspections`                            | Liste des visites techniques    |
| POST    | `/technical-inspections`                            | Créer une visite technique      |
| GET     | `/technical-inspections/expired`                    | Visites expirées                |
| GET     | `/technical-inspections/expiring-soon`              | Visites expirant bientôt        |
| GET     | `/technical-inspections/{id}`                       | Détails                         |
| PUT     | `/technical-inspections/{id}`                       | Modifier                        |
| DELETE  | `/technical-inspections/{id}`                       | Supprimer (soft)                |
| POST    | `/technical-inspections/{id}/report`                | Upload rapport (PDF)            |
| POST    | `/technical-inspections/{id}/photos`                | Upload photos (max 5)           |
| DELETE  | `/technical-inspections/{id}/media/{mediaId}`       | Supprimer un média              |

### GET `/technical-inspections` — Query Parameters
| Paramètre    | Type    | Description                              |
|--------------|---------|------------------------------------------|
| `vehicle_id` | string  | Filtrer par véhicule                     |
| `result`     | string  | `passed`, `failed`, `pending`            |
| `per_page`   | integer | Éléments par page (def: 15)              |

### GET `/technical-inspections/expiring-soon` — Query Parameters
| Paramètre | Type    | Description                  |
|-----------|---------|------------------------------|
| `days`    | integer | Nombre de jours (défaut: 30) |
| `per_page`| integer | Éléments par page (def: 15)  |

### POST `/technical-inspections`
**Body:**
```json
{
  "vehicle_id": "uuid",
  "inspection_date": "2026-03-15",
  "expiry_date": "2027-03-15",
  "result": "passed",
  "inspection_center": "Centre Contrôle Casablanca",
  "inspector_name": "Karim Bennani",
  "observations": "RAS",
  "cost": 300.00,
  "next_inspection_date": "2027-03-15"
}
```

---

## 12. Vignettes

**Prefix:** `/vignettes` — **Auth:** ✅ JWT

| Méthode | Endpoint                                   | Description                     |
|---------|--------------------------------------------|---------------------------------|
| GET     | `/vignettes`                               | Liste des vignettes             |
| POST    | `/vignettes`                               | Créer une vignette              |
| GET     | `/vignettes/expired`                       | Vignettes expirées              |
| GET     | `/vignettes/unpaid`                        | Vignettes non payées            |
| GET     | `/vignettes/{id}`                          | Détails                         |
| PUT     | `/vignettes/{id}`                          | Modifier                        |
| DELETE  | `/vignettes/{id}`                          | Supprimer (soft)                |
| PATCH   | `/vignettes/{id}/mark-paid`                | Marquer comme payée             |
| POST    | `/vignettes/{id}/document`                 | Upload document vignette        |
| POST    | `/vignettes/{id}/payment-proof`            | Upload preuve de paiement       |
| DELETE  | `/vignettes/{id}/media/{mediaId}`          | Supprimer un média              |

### GET `/vignettes` — Query Parameters
| Paramètre    | Type    | Description              |
|--------------|---------|--------------------------|
| `vehicle_id` | string  | Filtrer par véhicule     |
| `is_paid`    | boolean | Filtrer payées/non       |
| `year`       | integer | Filtrer par année        |
| `per_page`   | integer | Éléments par page (def: 15) |

### POST `/vignettes`
**Body:**
```json
{
  "vehicle_id": "uuid",
  "year": 2026,
  "issue_date": "2026-01-01",
  "expiry_date": "2026-12-31",
  "amount": 700.00,
  "payment_method": "bank_transfer",
  "payment_reference": "VIG-2026-001"
}
```

### PATCH `/vignettes/{id}/mark-paid`
**Body:**
```json
{
  "payment_method": "cash",
  "payment_reference": "REC-001"
}
```
**Méthodes :** `cash`, `bank_transfer`, `online`

---

## 13. Notifications

**Prefix:** `/notifications` — **Auth:** ✅ JWT

| Méthode | Endpoint                         | Rôle requis           | Description                         |
|---------|----------------------------------|-----------------------|-------------------------------------|
| GET     | `/notifications`                 | —                     | Liste des notifications             |
| GET     | `/notifications/unread`          | —                     | Notifications non lues              |
| GET     | `/notifications/summary`         | —                     | Résumé (compteur badge)             |
| GET     | `/notifications/count`           | —                     | Nombre de non-lues                  |
| GET     | `/notifications/types`           | —                     | Types de notifications disponibles  |
| POST    | `/notifications/read-all`        | —                     | Tout marquer comme lu               |
| DELETE  | `/notifications/read`            | —                     | Supprimer toutes les lues           |
| GET     | `/notifications/{id}`            | —                     | Détail d'une notification           |
| PATCH   | `/notifications/{id}/read`       | —                     | Marquer comme lue                   |
| DELETE  | `/notifications/{id}`            | —                     | Supprimer une notification          |
| POST    | `/notifications/send`            | `super-admin\|admin`  | Envoyer manuellement                |

### GET `/notifications` — Query Parameters
| Paramètre  | Type    | Description                              |
|------------|---------|------------------------------------------|
| `per_page` | integer | Éléments par page (défaut: 20)           |
| `type`     | string  | Type de notification                     |
| `severity` | string  | `info`, `warning`, `critical`            |

### POST `/notifications/send` (Admin)
**Body:**
```json
{
  "title": "Maintenance urgente",
  "body": "Le véhicule XX-1234 nécessite une maintenance urgente",
  "user_ids": ["uuid1", "uuid2"],
  "agency_id": "uuid",
  "roles": ["admin", "manager"],
  "severity": "warning",
  "action_url": "/vehicles/uuid/maintenance"
}
```

---

## 14. Rôles & Permissions

### Rôles

**Prefix:** `/roles` — **Auth:** ✅ JWT — **Rôle:** `super-admin`

| Méthode | Endpoint                       | Description                  |
|---------|--------------------------------|------------------------------|
| GET     | `/roles`                       | Liste des rôles              |
| POST    | `/roles`                       | Créer un rôle                |
| GET     | `/roles/{id}`                  | Détails d'un rôle            |
| PUT     | `/roles/{id}`                  | Modifier un rôle             |
| DELETE  | `/roles/{id}`                  | Supprimer un rôle            |
| POST    | `/roles/{id}/permissions`      | Assigner des permissions     |
| DELETE  | `/roles/{id}/permissions`      | Révoquer des permissions     |

### POST `/roles`
**Body:**
```json
{
  "name": "manager",
  "guard_name": "api",
  "permissions": ["create-vehicle", "edit-vehicle", "create-reservation"]
}
```

### POST `/roles/{id}/permissions`
**Body:**
```json
{
  "permissions": ["create-vehicle", "edit-vehicle", "delete-vehicle"]
}
```

### Permissions

**Prefix:** `/permissions` — **Auth:** ✅ JWT — **Rôle:** `super-admin`

| Méthode | Endpoint              | Description                  |
|---------|-----------------------|------------------------------|
| GET     | `/permissions`        | Liste des permissions        |
| POST    | `/permissions`        | Créer une permission         |
| DELETE  | `/permissions/{id}`   | Supprimer une permission     |

### POST `/permissions`
**Body:**
```json
{
  "name": "manage-reports",
  "guard_name": "api"
}
```

---

## 15. Logs d'Audit

**Prefix:** `/logs` — **Auth:** ✅ JWT — **Permission:** `view-logs`

| Méthode | Endpoint                         | Rôle requis    | Description                    |
|---------|----------------------------------|----------------|--------------------------------|
| GET     | `/logs`                          | `view-logs`    | Liste des logs d'audit         |
| GET     | `/logs/{id}`                     | `view-logs`    | Détails d'un log               |
| GET     | `/logs/model/{type}/{id}`        | `view-logs`    | Logs par modèle                |
| GET     | `/logs/user/{userId}`            | `view-logs`    | Logs par utilisateur           |
| DELETE  | `/logs/{id}`                     | `super-admin`  | Supprimer un log               |

### GET `/logs` — Query Parameters
| Paramètre  | Type    | Description              |
|------------|---------|--------------------------|
| `per_page` | integer | Éléments par page (def: 15) |

### GET `/logs/model/{type}/{id}` — Types de modèle
| Type                  | Description        |
|-----------------------|--------------------|
| `agency`              | Agence             |
| `vehicle`             | Véhicule           |
| `client`              | Client             |
| `reservation`         | Réservation        |
| `maintenance`         | Maintenance        |
| `insurance`           | Assurance          |
| `vignette`            | Vignette           |
| `technical-inspection`| Visite technique   |
| `user`                | Utilisateur        |

---

## 16. Format des réponses

### Codes HTTP utilisés

| Code | Description                        |
|------|------------------------------------|
| 200  | Succès                             |
| 201  | Création réussie                   |
| 204  | Pas de contenu                     |
| 400  | Requête invalide                   |
| 401  | Non authentifié                    |
| 403  | Accès interdit                     |
| 404  | Ressource non trouvée             |
| 422  | Erreur de validation               |
| 500  | Erreur serveur interne             |

### Authentification
Toutes les requêtes authentifiées doivent inclure le header :
```
Authorization: Bearer {jwt_token}
```

### Upload de fichiers
Pour les endpoints d'upload, utiliser `Content-Type: multipart/form-data`.

**Limites de taille :**
- Images : max 5 MB (avatar: 2 MB)
- Documents PDF : max 10 MB (police assurance: 20 MB)
- Photos véhicule : max 10 fichiers
- Photos réservation : max 20 fichiers
- Photos visite technique : max 5 fichiers

### Rôles système
| Rôle          | Description                        |
|---------------|------------------------------------|
| `super-admin` | Accès total                        |
| `admin`       | Gestion agence + utilisateurs      |
| `manager`     | Gestion opérationnelle             |
| `agent`       | Opérations de location             |
| `viewer`      | Lecture seule (rôle par défaut)    |

---

> 📝 **Note :** La documentation Swagger interactive est disponible à `/api/documentation`  
> 📅 **Dernière mise à jour :** 2 Avril 2026

