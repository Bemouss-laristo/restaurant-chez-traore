# Chez Traoré — Gestion de restaurant

Application web de gestion pour un petit restaurant, centrée sur le **contrôle financier** :
ventes, dépenses, stock et caisse, avec tableau de bord, rapports et alertes.

Conçue pour fonctionner en **réseau local, sans connexion Internet**, et déployable
plus tard sur un serveur sans changer d'architecture.

## Fonctionnalités

- Authentification à 3 rôles : Administrateur, Gérant, Caissier
- Catalogue de produits avec photos et recettes (nomenclature)
- Stock piloté par un registre de mouvements, avec seuils d'alerte
- Ventes sur écran tactile (le stock se décrémente selon les recettes)
- Dépenses catégorisées
- Caisse en sessions (ouverture / clôture) avec calcul de l'écart réel / théorique
- Tableau de bord, rapports journalier / hebdomadaire / mensuel (graphiques)
- Export des rapports en **PDF** et **Excel**
- Devise : MRU (Ouguiya)

## Prérequis

- PHP 8.2+ (idéalement 8.4)
- Composer
- MySQL (via XAMPP par exemple)
- Node.js + npm (pour compiler les assets)

## Installation

```bash
# 1. Récupérer le projet
git clone <url-du-depot> Restaurant_Chez_tra
cd Restaurant_Chez_tra

# 2. Dépendances
composer install
npm install
npm run build

# 3. Configuration
cp .env.example .env
php artisan key:generate
# Adapter les identifiants MySQL dans .env si besoin

# 4. Base de données (crée les tables + charge le menu, les comptes et le stock de démo)
php artisan migrate:fresh --seed

# 5. Lancer
php artisan serve --host=0.0.0.0 --port=8000
```

L'application est alors accessible sur `http://<IP-du-PC>:8000` depuis le réseau local.

## Comptes de démonstration

| Rôle          | Email                     | Mot de passe |
|---------------|---------------------------|--------------|
| Administrateur| admin@cheztraore.mr       | password     |
| Gérant        | gerant@cheztraore.mr      | password     |
| Caissier      | caissier@cheztraore.mr    | password     |

> **Sécurité :** changez ces mots de passe avant toute mise en service réelle
> (menu **Employés** une fois connecté en administrateur).

## Tests

```bash
php artisan test
```

Les tests utilisent une base SQLite en mémoire — la base MySQL n'est pas touchée.

## Notes

- Le fichier `.env` (clé de l'application, identifiants) n'est **pas** versionné.
- Les photos de produits sont stockées dans `storage/app/public/products` et servies
  par une route de l'application (pas besoin de `php artisan storage:link`).
