# Runbook — Migration finale des données (prod)

Ce fichier reprend, dans l'ordre exact, toutes les étapes validées aujourd'hui
(test réussi sur le serveur réel, login confirmé). À suivre telles quelles pour
l'exécution finale de cette nuit avec le dump le plus récent.

Serveur : `vmi3384296` — domaine `itibanrentcar.com`
Backend : `/var/www/ges-cars-admin/backend`
Base de données : `itiban_rent_car_db` (utilisateur MySQL `itiban_user`)
Process frontend (PM2) : `itiban-frontend`

---

## 0. Pré-requis déjà en place (ne pas refaire)

- Le correctif nginx (buffers de proxy) est déjà appliqué dans
  `/etc/nginx/sites-available/itibanrentcar.com`, bloc `location /` :
  ```nginx
  proxy_buffer_size 32k;
  proxy_buffers 8 32k;
  proxy_busy_buffers_size 64k;
  ```
  Vérifier juste qu'il est toujours là avant de commencer :
  ```bash
  grep -A2 "proxy_cache_bypass" /etc/nginx/sites-available/itibanrentcar.com
  ```
  Sans ce correctif, la connexion échoue avec une 502 ("upstream sent too big
  header") dès qu'un login réussit, à cause de la taille du cookie JWT
  (rôles + 69 permissions + agences).

- Placer le nouveau dump de l'ancienne base sur le serveur (ex. via `scp`),
  dans le dossier home ou `/tmp`, en notant son nom exact (ex. `cars (4).sql`).

---

## 1. Mode maintenance

```bash
cd /var/www/ges-cars-admin/backend
php artisan down
```

## 2. Sauvegarde de la base cible AVANT toute suppression

```bash
mysqldump -u itiban_user -p --no-tablespaces itiban_rent_car_db > ~/backup_avant_migration_$(date +%F_%H%M).sql
```

## 3. Suppression + recréation de la base (retire les données de test d'aujourd'hui)

```bash
mysql -u root -p -e "DROP DATABASE itiban_rent_car_db; CREATE DATABASE itiban_rent_car_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```
(adapter l'utilisateur/mot de passe root MySQL selon votre setup)

## 4. Mettre à jour le code si nécessaire

```bash
git pull
composer install --no-dev --optimize-autoloader   # seulement si des dépendances ont changé
```

Ce `git pull` ramène aussi la fonctionnalité (indépendante de la migration)
d'alerte Telegram à la connexion. Optionnel — sans configuration elle ne fait
rien silencieusement. Pour l'activer, ajouter dans `.env` :
```
TELEGRAM_BOT_TOKEN=...
TELEGRAM_CHAT_ID=...
```

## 5. Rejouer le schéma de la nouvelle application

```bash
php artisan migrate --force
```

## 6. Seeding minimal (PAS de --seed complet : évite le compte de démo avec mot de passe faible)

```bash
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
```

## 7. Importer le nouveau dump de l'ancienne base dans LA MÊME base

```bash
mysql -u root -p --default-character-set=utf8mb4 \
  -e "SET SESSION sql_mode = ''; SOURCE /chemin/vers/cars (4).sql;" itiban_rent_car_db
```
Ou plus simplement (si pas de zéro-dates problématiques, sinon garder la commande ci-dessus) :
```bash
mysql -u root -p itiban_rent_car_db < "/chemin/vers/cars (4).sql"
```

## 8. Exécuter le script de migration

```bash
mysql -u root -p itiban_rent_car_db < /var/www/ges-cars-admin/backend/database/migration-scripts/migrate_legacy_data.sql
```

Lire attentivement le rapport de vérification affiché en fin de script
(section 13) : comptages ancien vs nouveau par entité, liste des véhicules
"MIGRATION:" à corriger, liste des enregistrements "À COMPLÉTER", et les
compteurs de facturation (`fa_current`/`bc_current`/`bl_current`).

**Important — réservations migrées = archive pure** : elles reçoivent une
référence `ARCHIVE-<ancien_id>` (jamais générée par l'application, donc aucune
collision possible), et sont forcées à `status=completed` / `payment_status=paid`
quel que soit leur statut réel dans l'ancien système. Elles sont exclues des
statistiques de revenus/crédit partout dans l'application (dashboard, stats
agence/véhicule/client, listes de crédit) via un filtre `legacy_id IS NOT NULL`
côté backend — elles restent visibles dans les listings normaux (historique
client, etc.) mais ne faussent aucun chiffre financier. Les nouvelles
réservations créées après la migration utilisent le format `YY-XXXX`
(ex. `26-0001`), sans lien avec les références archivées.

Les factures/BC/BL migrées gardent leur référence d'origine (`legal_documents.reference`,
déjà unique dans l'ancien système), et les compteurs de génération
(`fa_current`, `bc_current`, `bl_current`) sont alignés automatiquement par le
script pour que les prochains documents continuent la numérotation.

Videz le cache applicatif après la migration pour être sûr que les compteurs
mis à jour en base sont bien pris en compte :
```bash
php artisan cache:clear
```

## 9. Vérifier les rôles migrés

```bash
mysql -u itiban_user -p itiban_rent_car_db -e "
SELECT u.email, u.legacy_id, r.name AS role
FROM users u
LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\\\Models\\\\User'
LEFT JOIN roles r ON r.id = mhr.role_id
WHERE u.legacy_id IS NOT NULL;"
```
Si un utilisateur migré apparaît avec `role` NULL, l'assigner manuellement :
```bash
mysql -u itiban_user -p itiban_rent_car_db -e "
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\\\Models\\\\User', u.id
FROM users u
JOIN roles r ON r.name = 'super-admin'
WHERE u.legacy_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM model_has_roles m WHERE m.model_id = u.id AND m.role_id = r.id);"
```

## 10. Sortir du mode maintenance et relancer le frontend

```bash
php artisan up
pm2 restart itiban-frontend
```

## 11. Test de connexion

- Email : (le compte migré, ex. `admin@gmail.com`)
- Mot de passe temporaire commun : `GesCars2026!`

Se connecter directement dans le navigateur sur `https://itibanrentcar.com/sign-in`.
(Ne pas tester le login via `curl` sur `/api/auth/login` : ce chemin est
intercepté par le frontend Next.js/NextAuth, pas par le vrai backend Laravel.
Le vrai backend répond sous `/api/v1/...`.)

Si la connexion échoue encore avec une 502, revérifier l'étape 0 (buffers nginx).

## 12. Vérifications applicatives

- Ouvrir quelques véhicules migrés (`notes LIKE '%MIGRATION:%'`) et corriger
  année/catégorie/transmission/tarif journalier si connus.
- Ouvrir quelques assurances/vignettes/visites techniques marquées
  "À COMPLÉTER (migration)" et compléter les champs texte si disponibles.
- Ouvrir une réservation migrée (`reservation_number LIKE 'ARCHIVE-%'`) et
  confirmer que les notes mentionnent bien l'ancien contrat.
- Vérifier les totaux financiers d'une agence.

## 13. (Optionnel, séparé) Suppression des anciennes tables

À faire uniquement après validation complète en conditions réelles — section 14
du script `migrate_legacy_data.sql` (actuellement commentée, à décommenter et
exécuter manuellement le moment venu).
