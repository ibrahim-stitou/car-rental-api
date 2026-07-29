-- =============================================================================
-- MIGRATION DES DONNEES — Ancienne application (NestJS/TypeORM, base "cars")
--                         vers la nouvelle application (Laravel)
-- =============================================================================
--
-- ⚠️  A LIRE AVANT EXECUTION ⚠️
--
-- 1. FAITES UNE SAUVEGARDE DE LA BASE CIBLE AVANT TOUTE CHOSE :
--       mysqldump -u root -p <nom_de_la_base_nouvelle_appli> > backup_avant_migration.sql
--    Ce script n'est PAS transactionnel de bout en bout (des instructions DDL
--    comme ALTER/CREATE TABLE provoquent un commit implicite en MySQL), donc un
--    rollback global n'est pas possible : la sauvegarde est votre seul filet.
--
-- 2. PRE-REQUIS : importez le dump SQL de l'ANCIENNE base (celle exportée depuis
--    "cars") DIRECTEMENT DANS LA MEME BASE que la nouvelle application, par ex :
--       mysql -u root -p <nom_de_la_base_nouvelle_appli> < ancien_dump_cars.sql
--    Aucune table de l'ancienne base n'entre en collision avec celles de la
--    nouvelle (agency/agencies, car/vehicles, client/clients, contrat/reservations,
--    user/users, document/(pas de table "documents"), notification/notifications...),
--    donc les deux jeux de tables peuvent cohabiter le temps de la migration.
--
-- 3. Le schéma de la nouvelle application doit déjà être en place
--    (php artisan migrate déjà exécuté), y compris les rôles Spatie
--    (super-admin, admin, manager, agent, viewer) déjà en base.
--
-- 4. Ce script NE MIGRE PAS les fichiers (scans CIN/permis, cartes grises,
--    photos, factures PDF) : le serveur de fichiers de l'ancienne application
--    n'était pas accessible au moment de l'analyse. Seules les DONNEES sont
--    migrées. Voir le rapport final (section 13) et la documentation du plan
--    pour la liste des champs complétés par une valeur par défaut faute de
--    donnée source, à corriger manuellement après coup.
--
-- 5. Mot de passe temporaire commun donné aux comptes migrés : GesCars2026!
--    (hash bcrypt déjà généré ci-dessous). Chaque utilisateur migré devra le
--    changer (page "mot de passe oublié" ou après connexion).
--
-- 6. Le script est IDEMPOTENT : il peut être ré-exécuté sans dupliquer les
--    données déjà migrées (grâce à la colonne technique "legacy_id" ajoutée
--    temporairement, et conservée ensuite pour la traçabilité/support).
--
-- 7. La suppression des anciennes tables (section 14) est VOLONTAIRE et
--    SEPAREE : ne l'exécutez qu'après avoir vérifié le résultat de la migration
--    dans l'application elle-même.
--
-- =============================================================================

SET @migration_started_at = NOW();
SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'NO_ZERO_DATE', ''));
SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'STRICT_TRANS_TABLES', ''));

-- Mot de passe temporaire commun (GesCars2026!) — hash bcrypt Laravel valide.
SET @legacy_temp_password_hash = '$2y$12$VRGXXRoRQgCVuuiBBz4npO3xsv7Y.QB9/MZM8ljnyOgUb7YF5aX5i';


-- =============================================================================
-- SECTION 1 — Préparation : collation + colonnes techniques de correspondance
-- =============================================================================
-- L'ancienne application (NestJS/TypeORM) utilise par défaut la collation
-- utf8mb4_general_ci, alors que la nouvelle application (Laravel) utilise
-- utf8mb4_unicode_ci. Sans cette normalisation, toute comparaison de chaînes
-- entre une ancienne et une nouvelle table échoue avec une erreur
-- "Illegal mix of collations". On aligne donc les anciennes tables au démarrage.

ALTER TABLE agency               CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE agency_users_user    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE user                 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE client                CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE car                   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE contrat                CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE document               CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE file                   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE email                  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE fax                    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE telephone               CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE legal_documents          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE legal_document_lines      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Colonnes techniques de correspondance ancien-id -> nouvel-UUID (ajoutées
-- uniquement si absentes, script ré-exécutable sans erreur).

-- NB : MySQL (contrairement à MariaDB) ne supporte pas "ADD COLUMN IF NOT EXISTS"
-- ni "CREATE INDEX IF NOT EXISTS". On vérifie donc via information_schema et on
-- exécute l'ALTER dynamiquement uniquement si la colonne n'existe pas déjà —
-- portable sur toute installation MySQL 8, sans DELIMITER ni procédure stockée.

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE agencies ADD COLUMN legacy_id INT NULL, ADD INDEX idx_legacy_id (legacy_id)', 'SELECT 1') FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'agencies' AND column_name = 'legacy_id');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE users ADD COLUMN legacy_id INT NULL, ADD INDEX idx_legacy_id (legacy_id)', 'SELECT 1') FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'legacy_id');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE clients ADD COLUMN legacy_id INT NULL, ADD INDEX idx_legacy_id (legacy_id)', 'SELECT 1') FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'clients' AND column_name = 'legacy_id');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE vehicles ADD COLUMN legacy_id INT NULL, ADD INDEX idx_legacy_id (legacy_id)', 'SELECT 1') FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'vehicles' AND column_name = 'legacy_id');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE reservations ADD COLUMN legacy_id INT NULL, ADD INDEX idx_legacy_id (legacy_id)', 'SELECT 1') FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'reservations' AND column_name = 'legacy_id');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE billing_documents ADD COLUMN legacy_id INT NULL, ADD INDEX idx_legacy_id (legacy_id)', 'SELECT 1') FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'billing_documents' AND column_name = 'legacy_id');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE insurances ADD COLUMN legacy_id INT NULL, ADD INDEX idx_legacy_id (legacy_id)', 'SELECT 1') FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'insurances' AND column_name = 'legacy_id');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE vignettes ADD COLUMN legacy_id INT NULL, ADD INDEX idx_legacy_id (legacy_id)', 'SELECT 1') FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'vignettes' AND column_name = 'legacy_id');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE technical_inspections ADD COLUMN legacy_id INT NULL, ADD INDEX idx_legacy_id (legacy_id)', 'SELECT 1') FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'technical_inspections' AND column_name = 'legacy_id');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- =============================================================================
-- SECTION 2 — Agences  (agency -> agencies)
-- =============================================================================

INSERT INTO agencies (id, name, address, city, country, phone, email, is_active, legacy_id, created_at, updated_at)
SELECT
    UUID(),
    TRIM(a.name),
    NULLIF(TRIM(a.adresse), ''),
    NULL,
    'Maroc',
    (SELECT t.value FROM telephone t WHERE t.agenceId = a.id ORDER BY t.id LIMIT 1),
    COALESCE(
        (SELECT e.value FROM email e WHERE e.agenceId = a.id ORDER BY e.id LIMIT 1),
        CONCAT('agence', a.id, '@a-completer.local')
    ),
    1,
    a.id,
    NOW(), NOW()
FROM agency a
WHERE NOT EXISTS (SELECT 1 FROM agencies x WHERE x.legacy_id = a.id);


-- =============================================================================
-- SECTION 3 — Utilisateurs (user -> users) + rôles Spatie
-- =============================================================================
-- Mot de passe : hash temporaire commun (voir en-tête). Rôle : Admin -> super-admin,
-- AgenceAdmin -> admin (les deux rôles doivent déjà exister dans la table `roles`).

INSERT INTO users (id, first_name, last_name, email, password, phone, is_active, legacy_id, created_at, updated_at)
SELECT
    UUID(),
    TRIM(u.firstname),
    TRIM(u.lastname),
    LOWER(TRIM(u.email)),
    @legacy_temp_password_hash,
    NULLIF(TRIM(u.telephone), ''),
    1, -- réactivés par défaut ; l'ancien "isActive" ne reflétait pas fidèlement l'usage réel
    u.id,
    NOW(), NOW()
FROM user u
WHERE NOT EXISTS (SELECT 1 FROM users x WHERE x.legacy_id = u.id)
  AND NOT EXISTS (SELECT 1 FROM users x2 WHERE x2.email = LOWER(TRIM(u.email)) COLLATE utf8mb4_unicode_ci); -- évite les collisions d'email avec un compte déjà existant

-- Attribution des rôles (table pivot Spatie model_has_roles)
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', nu.id
FROM user u
JOIN users nu ON nu.legacy_id = u.id
JOIN roles r ON r.name = CASE u.role
    WHEN 'Admin' THEN 'super-admin'
    WHEN 'AgenceAdmin' THEN 'admin'
    ELSE 'agent'
END
WHERE NOT EXISTS (
    SELECT 1 FROM model_has_roles m
    WHERE m.model_id = nu.id AND m.model_type = 'App\\Models\\User' AND m.role_id = r.id
);


-- =============================================================================
-- SECTION 4 — Pivot Utilisateur <-> Agence (agency_users_user -> agency_user)
-- =============================================================================

INSERT INTO agency_user (id, agency_id, user_id, created_at, updated_at)
SELECT UUID(), na.id, nu.id, NOW(), NOW()
FROM agency_users_user pivot
JOIN agencies na ON na.legacy_id = pivot.agencyId
JOIN users nu ON nu.legacy_id = pivot.userId
WHERE NOT EXISTS (SELECT 1 FROM agency_user x WHERE x.agency_id = na.id AND x.user_id = nu.id);

-- Correctif signalé dans le plan : l'utilisatrice "Hajar" (AgenceAdmin) n'avait
-- aucune agence liée dans l'ancien système. Rattachée à "ITIBAN RENT CAR" par
-- déduction de son adresse email (hajar@itibanrentcar.com). A vérifier manuellement.
INSERT INTO agency_user (id, agency_id, user_id, created_at, updated_at)
SELECT UUID(), na.id, nu.id, NOW(), NOW()
FROM user u
JOIN users nu ON nu.legacy_id = u.id
JOIN agency a ON a.name LIKE 'ITIBAN%'
JOIN agencies na ON na.legacy_id = a.id
WHERE u.email = 'hajar@itibanrentcar.com'
  AND NOT EXISTS (SELECT 1 FROM agency_user x WHERE x.agency_id = na.id AND x.user_id = nu.id);


-- =============================================================================
-- SECTION 5 — Clients (client -> clients)
-- =============================================================================
-- L'ancien système ne liait pas directement un client à une agence (voir section 6).
-- id_type toujours 'cin' car l'ancien système ne gérait que la CIN.

INSERT INTO clients (
    id, first_name, last_name, phone, date_of_birth, birth_place,
    id_type, id_number, id_expiry_date,
    driving_license_number, driving_license_expiry, license_issue_place,
    address, country, legacy_id, created_at, updated_at
)
SELECT
    UUID(),
    TRIM(c.firstname),
    TRIM(c.lastname),
    TRIM(c.telephone),
    DATE(c.birthday),
    NULLIF(TRIM(c.lieuNaissance), ''),
    'cin',
    NULLIF(TRIM(c.cin), ''),
    IF(c.dateCin = '0000-00-00 00:00:00' OR c.dateCin IS NULL, NULL, DATE(c.dateCin)),
    NULLIF(TRIM(c.permis), ''),
    IF(c.datePermis = '0000-00-00 00:00:00' OR c.datePermis IS NULL, NULL, DATE(c.datePermis)),
    NULLIF(TRIM(c.villePermis), ''),
    NULLIF(TRIM(c.adresse), ''),
    'Maroc',
    c.id,
    NOW(), NOW()
FROM client c
WHERE NOT EXISTS (SELECT 1 FROM clients x WHERE x.legacy_id = c.id);


-- =============================================================================
-- SECTION 6 — Pivot Client <-> Agence (reconstitué depuis l'historique des contrats)
-- =============================================================================
-- L'ancien "Client" n'avait pas de lien direct vers une agence : on déduit les
-- agences d'un client à partir de toutes celles où il a eu au moins un contrat
-- (comme client OU comme chauffeur).

-- NB : "SELECT DISTINCT UUID(), ..." ne dédoublonnerait jamais (UUID() génère
-- une valeur différente à chaque ligne) — la déduplication doit se faire dans
-- une sous-requête AVANT de générer l'UUID.
INSERT INTO agency_client (id, agency_id, client_id, created_at, updated_at)
SELECT UUID(), pairs.agency_id, pairs.client_id, NOW(), NOW()
FROM (
    SELECT DISTINCT na.id AS agency_id, ncl.id AS client_id
    FROM contrat co
    JOIN agencies na ON na.legacy_id = co.agenceId
    JOIN clients ncl ON ncl.legacy_id = co.clientId
    WHERE co.clientId IS NOT NULL
) pairs
WHERE NOT EXISTS (SELECT 1 FROM agency_client x WHERE x.agency_id = pairs.agency_id AND x.client_id = pairs.client_id);

INSERT INTO agency_client (id, agency_id, client_id, created_at, updated_at)
SELECT UUID(), pairs.agency_id, pairs.client_id, NOW(), NOW()
FROM (
    SELECT DISTINCT na.id AS agency_id, ncl.id AS client_id
    FROM contrat co
    JOIN agencies na ON na.legacy_id = co.agenceId
    JOIN clients ncl ON ncl.legacy_id = co.chauffeurId
    WHERE co.chauffeurId IS NOT NULL
) pairs
WHERE NOT EXISTS (SELECT 1 FROM agency_client x WHERE x.agency_id = pairs.agency_id AND x.client_id = pairs.client_id);


-- =============================================================================
-- SECTION 7 — Véhicules (car -> vehicles)
-- =============================================================================
-- Champs absents de l'ancien système et complétés par défaut (voir le tableau du
-- plan) : year, vin, category, transmission, daily_rate. Tous signalés dans
-- `notes` par le texte "MIGRATION:" pour permettre une recherche/correction ciblée.
--
-- Immatriculation : TRIM() pour corriger l'écart d'espace observé sur un doublon
-- (même agence, même modèle) ; la 2e occurrence identique reçoit un suffixe
-- "-DUP<ancien_id>" pour ne pas violer la contrainte d'unicité — à fusionner
-- manuellement, il s'agit très probablement du même véhicule saisi deux fois.

INSERT INTO vehicles (
    id, agency_id, brand, model, year, registration_number, vin,
    category, fuel_type, transmission, daily_rate, status, description, notes,
    legacy_id, created_at, updated_at
)
SELECT
    UUID(),
    na.id,
    TRIM(c.marque),
    TRIM(c.model),
    YEAR(CURDATE()),
    CASE WHEN dup.matricule_norm IS NOT NULL THEN CONCAT(dup.matricule_norm, '-DUP', c.id) ELSE TRIM(c.matricule) END,
    CONCAT('LEGACY-', c.id),
    'sedan',
    CASE c.carburant WHEN 'Diesel' THEN 'diesel' WHEN 'Essence' THEN 'gasoline' ELSE 'diesel' END,
    'manual',
    COALESCE(
        (SELECT ROUND(AVG(co.price / GREATEST(DATEDIFF(co.endAt, co.satrtAt), 1)), 2)
         FROM contrat co WHERE co.carId = c.id AND co.price > 0),
        300.00
    ),
    CASE c.statut WHEN 'Disponible' THEN 'available' WHEN 'Panne' THEN 'maintenance' WHEN 'Reserved' THEN 'rented' ELSE 'available' END,
    NULLIF(TRIM(c.description), '.'),
    CONCAT(
        'MIGRATION: année, catégorie, transmission et immatriculation dupliquée éventuelle à vérifier. ',
        'Tarif journalier estimé depuis l''historique des contrats (ou valeur par défaut si aucun historique). ',
        'Ancien véhicule #', c.id, '.'
    ),
    c.id,
    NOW(), NOW()
FROM car c
JOIN agencies na ON na.legacy_id = c.agenceId
LEFT JOIN (
    SELECT TRIM(matricule) AS matricule_norm, MIN(id) AS first_id
    FROM car
    GROUP BY TRIM(matricule)
    HAVING COUNT(*) > 1
) dup ON dup.matricule_norm = TRIM(c.matricule) AND dup.first_id <> c.id
WHERE NOT EXISTS (SELECT 1 FROM vehicles x WHERE x.legacy_id = c.id);


-- =============================================================================
-- SECTION 8 — Réservations (contrat -> reservations)
-- =============================================================================
-- reservation_number = référence de l'ancien contrat quand elle existe (format
-- 'YY-XXXX', ex. '26-0009' — l'ancien système ne l'attribuait qu'à partir de
-- 2026), sinon l'id numérique brut de l'ancien contrat (c'est exactement ce que
-- l'ancien frontend affichait par défaut en absence de référence : voir
-- reservation-list.component.ts, `displayId: item.reference ? item.reference : item.id`).
-- La génération des nouveaux numéros (app\Models\Reservation::generateReservationNumber)
-- reprend elle aussi le format 'YY-XXXX' et continue automatiquement la même
-- séquence à partir du max déjà présent en base après cette migration.
-- `notes` mentionne explicitement l'ancien contrat, comme demandé.
-- Chèque -> bank_transfer (le nouvel enum de paiement de réservation n'a pas de
-- valeur "chèque" ; le plus proche disponible est utilisé).
-- backAt à '0000-00-00 00:00:00' (date zéro MySQL) traité comme NULL.

INSERT INTO reservations (
    id, reservation_number, agency_id, vehicle_id, client_id, second_driver_id,
    pickup_date, return_date, actual_return_date,
    pickup_location, return_location, actual_return_location,
    daily_rate, total_days, subtotal, discount_percentage, discount_amount,
    additional_fees, total_amount,
    payment_method, payment_status, status,
    notes, legacy_id, created_at, updated_at
)
SELECT
    UUID(),
    COALESCE(NULLIF(TRIM(co.reference), ''), CAST(co.id AS CHAR)),
    na.id,
    nv.id,
    ncl.id,
    ndr.id,
    co.satrtAt,
    co.endAt,
    IF(co.backAt = '0000-00-00 00:00:00' OR co.backAt IS NULL, NULL, co.backAt),
    NULLIF(TRIM(co.startPlace), ''),
    NULLIF(TRIM(co.endPlace), ''),
    IF(co.backAt = '0000-00-00 00:00:00' OR co.backAt IS NULL, NULL, NULLIF(TRIM(co.endPlace), '')),
    ROUND(co.price / GREATEST(DATEDIFF(co.endAt, co.satrtAt), 1), 2),
    GREATEST(DATEDIFF(co.endAt, co.satrtAt), 1),
    co.price,
    0, 0, 0,
    co.price,
    CASE co.paiement WHEN 'Espece' THEN 'cash' WHEN 'Cheque' THEN 'bank_transfer' ELSE 'cash' END,
    CASE co.statut WHEN 'Cloture' THEN 'paid' ELSE 'pending' END,
    CASE co.statut WHEN 'Pending' THEN 'pending' WHEN 'Encore' THEN 'active' WHEN 'Cloture' THEN 'completed' ELSE 'pending' END,
    CONCAT(
        'Migré depuis l''ancien système (contrat #', co.id,
        IF(co.reference IS NOT NULL AND co.reference <> '', CONCAT(', référence ', co.reference), ''),
        ').',
        IF(co.observation IS NOT NULL AND TRIM(co.observation) <> '', CONCAT(' Observation d''origine : ', TRIM(co.observation)), '')
    ),
    co.id,
    co.creatAt, co.creatAt
FROM contrat co
JOIN agencies na ON na.legacy_id = co.agenceId
JOIN vehicles nv ON nv.legacy_id = co.carId
JOIN clients ncl ON ncl.legacy_id = co.clientId
LEFT JOIN clients ndr ON ndr.legacy_id = co.chauffeurId
WHERE NOT EXISTS (SELECT 1 FROM reservations x WHERE x.legacy_id = co.id);


-- =============================================================================
-- SECTION 8b — Correction statut : vieux contrats "en retard" (2025 et avant)
-- =============================================================================
-- L'ancien système n'avait pas de fonctionnalité pour clôturer une réservation
-- avant 2026 : son seul statut "en cours" était "Encore" (-> 'active' ci-dessus),
-- qu'un contrat ait réellement été rendu ou non. La nouvelle application, elle,
-- calcule automatiquement "en retard" pour toute réservation 'active' dont la
-- date de retour est dépassée (voir Reservation::scopeOverdue / isOverdue()).
-- Sans correction, tous ces vieux contrats de 2025 et avant remonteraient donc
-- à tort comme des retards actifs. On les reclasse en 'completed', faute de
-- pouvoir déterminer leur date de retour réelle.
--
-- NB : payment_status reste inchangé par cette correction (probablement
-- 'pending' pour ces lignes, voir section 9) — à vérifier manuellement si
-- besoin, la migration ne peut pas déduire si ces vieux contrats ont été
-- réellement payés.

UPDATE reservations
SET status = 'completed'
WHERE legacy_id IS NOT NULL
  AND status = 'active'
  AND return_date < CURDATE()
  AND YEAR(pickup_date) <= 2025;


-- =============================================================================
-- SECTION 9 — Paiements synthétiques pour les contrats historiquement clôturés
-- =============================================================================
-- L'ancien système ne suivait pas les paiements séparément (juste un montant
-- global sur le contrat). Pour les contrats "Cloture" (terminés), on considère
-- qu'ils ont été intégralement réglés et on crée un paiement correspondant, afin
-- que le solde affiché dans la nouvelle application reste cohérent.

INSERT INTO reservation_payments (id, reservation_id, amount, payment_method, payment_date, notes, created_at, updated_at)
SELECT
    UUID(),
    nr.id,
    nr.total_amount,
    nr.payment_method,
    DATE(COALESCE(NULLIF(nr.actual_return_date, '0000-00-00'), nr.return_date)),
    'Paiement reconstitué automatiquement lors de la migration (contrat historique clôturé, montant non détaillé dans l''ancien système).',
    NOW(), NOW()
FROM reservations nr
JOIN contrat co ON co.id = nr.legacy_id
WHERE co.statut = 'Cloture'
  AND nr.total_amount > 0
  AND NOT EXISTS (SELECT 1 FROM reservation_payments p WHERE p.reservation_id = nr.id);


-- =============================================================================
-- SECTION 10 — Assurances / Vignettes / Visites techniques
-- =============================================================================
-- Reconstituées à partir des dates d'expiration réelles de l'ancien système
-- (créneaux assuranceId/vignetteId/visiteId du véhicule), SANS fichier joint.
-- Aucun numéro de police, compagnie, centre de contrôle ou montant n'existait
-- avant : ces champs texte reçoivent "À COMPLÉTER (migration)" et sont donc
-- facilement repérables pour une correction manuelle ultérieure.

-- Assurances
INSERT INTO insurances (id, vehicle_id, insurance_company, policy_number, type, start_date, end_date, legacy_id, created_at, updated_at)
SELECT
    UUID(), nv.id,
    'À COMPLÉTER (migration)', CONCAT('A-COMPLETER-', d.id), 'third_party',
    DATE_SUB(DATE(d.DateExpiration), INTERVAL 1 YEAR), DATE(d.DateExpiration),
    d.id, NOW(), NOW()
FROM car c
JOIN document d ON d.id = c.assuranceId
JOIN vehicles nv ON nv.legacy_id = c.id
WHERE d.DateExpiration IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM insurances x WHERE x.legacy_id = d.id);

-- Vignettes
INSERT INTO vignettes (id, vehicle_id, year, issue_date, expiry_date, amount, legacy_id, created_at, updated_at)
SELECT
    UUID(), nv.id,
    YEAR(DATE_SUB(DATE(d.DateExpiration), INTERVAL 1 YEAR)),
    DATE_SUB(DATE(d.DateExpiration), INTERVAL 1 YEAR), DATE(d.DateExpiration),
    0.00,
    d.id, NOW(), NOW()
FROM car c
JOIN document d ON d.id = c.vignetteId
JOIN vehicles nv ON nv.legacy_id = c.id
WHERE d.DateExpiration IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM vignettes x WHERE x.legacy_id = d.id);

-- Visites techniques
INSERT INTO technical_inspections (id, vehicle_id, inspection_date, expiry_date, result, inspection_center, legacy_id, created_at, updated_at)
SELECT
    UUID(), nv.id,
    DATE_SUB(DATE(d.DateExpiration), INTERVAL 1 YEAR), DATE(d.DateExpiration),
    'passed', 'À COMPLÉTER (migration)',
    d.id, NOW(), NOW()
FROM car c
JOIN document d ON d.id = c.visiteId
JOIN vehicles nv ON nv.legacy_id = c.id
WHERE d.DateExpiration IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM technical_inspections x WHERE x.legacy_id = d.id);


-- =============================================================================
-- SECTION 11 — Facturation (legal_documents + legal_document_lines -> billing_documents + items)
-- =============================================================================
-- L'ancien document légal n'était pas lié à un client précis (seulement à une
-- agence + des véhicules via ses lignes) : client_name est donc complété par
-- défaut, à corriger manuellement si nécessaire.

INSERT INTO billing_documents (
    id, document_number, type, status, agency_id, client_name,
    issue_date, subtotal, tax_amount, total_amount, notes, legacy_id, created_at, updated_at
)
SELECT
    UUID(),
    COALESCE(NULLIF(TRIM(ld.reference), ''), CONCAT('LEGACY-', ld.id)),
    CASE ld.documentType WHEN 'facture' THEN 'FA' WHEN 'bon_livraison' THEN 'BL' WHEN 'bon_commande' THEN 'BC' ELSE 'FA' END,
    'approved',
    na.id,
    'Client non renseigné (migration)',
    ld.date_document,
    COALESCE((SELECT SUM(l.totalHt) FROM legal_document_lines l WHERE l.documentId = ld.id), 0),
    COALESCE((SELECT SUM(l.ttc - l.totalHt) FROM legal_document_lines l WHERE l.documentId = ld.id), 0),
    COALESCE((SELECT SUM(l.ttc) FROM legal_document_lines l WHERE l.documentId = ld.id), 0),
    CONCAT('Migré depuis l''ancien système (document légal #', ld.id, ').'),
    ld.id,
    NOW(), NOW()
FROM legal_documents ld
JOIN agencies na ON na.legacy_id = ld.agencyId
WHERE NOT EXISTS (SELECT 1 FROM billing_documents x WHERE x.legacy_id = ld.id);

INSERT INTO billing_document_items (id, billing_document_id, description, quantity, unit, unit_price, total_price, tax_rate, created_at, updated_at)
SELECT
    UUID(), nbd.id,
    l.designation,
    l.nrJours,
    'Jour',
    l.pu,
    l.totalHt,
    l.tva,
    NOW(), NOW()
FROM legal_document_lines l
JOIN billing_documents nbd ON nbd.legacy_id = l.documentId
WHERE NOT EXISTS (
    -- Idempotence approximative : on ne réinsère pas de lignes si le document en a déjà autant que l'ancien.
    SELECT 1 FROM billing_document_items bi
    WHERE bi.billing_document_id = nbd.id
    HAVING COUNT(*) >= (SELECT COUNT(*) FROM legal_document_lines l2 WHERE l2.documentId = l.documentId)
);


-- =============================================================================
-- SECTION 11b — Compteurs de numérotation des documents de facturation
-- =============================================================================
-- Les nouveaux documents générés par l'application utilisent un format différent
-- de l'ancien (ex. 'FA-000007' contre l'ancien 'FAC-2026-07-006'), piloté par un
-- compteur persistant (table settings, groupe 'counters', clés fa_current /
-- bc_current / bl_current). On l'aligne ici sur le nombre réel de documents déjà
-- migrés par type, pour que la numérotation continue logiquement au lieu de
-- repartir de zéro. GREATEST() évite de faire reculer le compteur si le script
-- est ré-exécuté ou si le compteur a déjà été avancé manuellement/par l'usage.

UPDATE settings s
JOIN (
    SELECT
        CASE type WHEN 'FA' THEN 'fa_current' WHEN 'BC' THEN 'bc_current' WHEN 'BL' THEN 'bl_current' END AS setting_key,
        COUNT(*) AS nb
    FROM billing_documents
    WHERE legacy_id IS NOT NULL AND type IN ('FA', 'BC', 'BL')
    GROUP BY type
) counts ON counts.setting_key = s.`key`
SET s.value = GREATEST(CAST(s.value AS UNSIGNED), counts.nb)
WHERE s.group = 'counters';


-- =============================================================================
-- SECTION 12 — (Volontairement non migré)
-- =============================================================================
-- - `logger` (2838 lignes de texte libre, format non structuré) : aucun
--   équivalent structuré côté nouvelle application, non migré.
-- - `notification` : 0 ligne en base au moment de l'analyse, rien à migrer.
-- - Fichiers (`document`/`file`, cartes grises, scans CIN/permis, photos,
--   factures) : non migrés, voir l'en-tête de ce script.


-- =============================================================================
-- SECTION 13 — Rapport de vérification
-- =============================================================================

SELECT 'Agences'                AS entite, (SELECT COUNT(*) FROM agency)          AS ancien, (SELECT COUNT(*) FROM agencies WHERE legacy_id IS NOT NULL)          AS migre
UNION ALL
SELECT 'Utilisateurs',              (SELECT COUNT(*) FROM user),                      (SELECT COUNT(*) FROM users WHERE legacy_id IS NOT NULL)
UNION ALL
SELECT 'Clients',                   (SELECT COUNT(*) FROM client),                    (SELECT COUNT(*) FROM clients WHERE legacy_id IS NOT NULL)
UNION ALL
SELECT 'Vehicules',                 (SELECT COUNT(*) FROM car),                       (SELECT COUNT(*) FROM vehicles WHERE legacy_id IS NOT NULL)
UNION ALL
SELECT 'Reservations',              (SELECT COUNT(*) FROM contrat),                   (SELECT COUNT(*) FROM reservations WHERE legacy_id IS NOT NULL)
UNION ALL
SELECT 'Documents facturation',     (SELECT COUNT(*) FROM legal_documents),           (SELECT COUNT(*) FROM billing_documents WHERE legacy_id IS NOT NULL)
UNION ALL
SELECT 'Lignes facturation',        (SELECT COUNT(*) FROM legal_document_lines),      (SELECT COUNT(*) FROM billing_document_items)
UNION ALL
-- Colonne "ancien" = créneau assurance/vignette/visite configuré sur le véhicule ;
-- "migré" est normalement inférieur car seuls les créneaux avec une date
-- d'expiration renseignée sont repris (voir section 10).
SELECT 'Assurances (créneau configuré)', (SELECT COUNT(*) FROM car WHERE assuranceId IS NOT NULL), (SELECT COUNT(*) FROM insurances WHERE legacy_id IS NOT NULL)
UNION ALL
SELECT 'Vignettes (créneau configuré)',  (SELECT COUNT(*) FROM car WHERE vignetteId IS NOT NULL),  (SELECT COUNT(*) FROM vignettes WHERE legacy_id IS NOT NULL)
UNION ALL
SELECT 'Visites (créneau configuré)',    (SELECT COUNT(*) FROM car WHERE visiteId IS NOT NULL),    (SELECT COUNT(*) FROM technical_inspections WHERE legacy_id IS NOT NULL);

-- Véhicules à corriger manuellement (valeurs par défaut appliquées)
SELECT id, legacy_id, brand, model, registration_number, year, vin, category, transmission, daily_rate
FROM vehicles
WHERE notes LIKE '%MIGRATION:%'
ORDER BY legacy_id;

-- Enregistrements avec champs "À COMPLÉTER" à corriger manuellement
SELECT 'insurance' AS type, id, legacy_id FROM insurances WHERE insurance_company = 'À COMPLÉTER (migration)'
UNION ALL
SELECT 'technical_inspection', id, legacy_id FROM technical_inspections WHERE inspection_center = 'À COMPLÉTER (migration)'
UNION ALL
SELECT 'billing_document', id, legacy_id FROM billing_documents WHERE client_name = 'Client non renseigné (migration)';

-- Compteurs de facturation après migration (prochain numéro généré = valeur + 1)
SELECT `key`, value FROM settings WHERE `group` = 'counters' AND `key` IN ('fa_current', 'bc_current', 'bl_current');

SELECT CONCAT('Migration terminée en ', TIMESTAMPDIFF(SECOND, @migration_started_at, NOW()), ' secondes.') AS resultat;


-- =============================================================================
-- SECTION 14 — Nettoyage des anciennes tables (A EXECUTER SEPAREMENT ET
--              VOLONTAIREMENT, une fois la migration vérifiée en conditions
--              réelles dans l'application). Décommentez le bloc ci-dessous.
-- =============================================================================

-- DROP TABLE IF EXISTS legal_document_lines;
-- DROP TABLE IF EXISTS legal_documents;
-- DROP TABLE IF EXISTS file;
-- DROP TABLE IF EXISTS document;
-- DROP TABLE IF EXISTS contrat;
-- DROP TABLE IF EXISTS agency_users_user;
-- DROP TABLE IF EXISTS client;
-- DROP TABLE IF EXISTS car;
-- DROP TABLE IF EXISTS email;
-- DROP TABLE IF EXISTS fax;
-- DROP TABLE IF EXISTS telephone;
-- DROP TABLE IF EXISTS notification;
-- DROP TABLE IF EXISTS logger;
-- DROP TABLE IF EXISTS user;
-- DROP TABLE IF EXISTS agency;
