# HELPDESK

Mini-projet de soutenance : application web de gestion de tickets de support.

Les clients ouvrent des tickets, les agents les prennent en charge et y répondent, les administrateurs gèrent les comptes, les statistiques et les réglages de l'application.

## Fonctionnalités

- Connexion, inscription et désactivation de comptes
- Quatre rôles : client, agent, administrateur, super-administrateur
- Tickets : création, réponses, statut, priorité, assignation à un agent, historique, évaluation de satisfaction
- Tableau de bord et page de statistiques
- Administration des utilisateurs (création, modification, activation, réinitialisation du mot de passe)
- Réglages globaux (langue, fuseau horaire, couleurs, SLA) et préférences par utilisateur

## Technologies

- PHP 8.2 ou plus, Laravel 12
- Vues Blade, Tailwind CSS
- SQLite par défaut (MySQL possible)

## Installation (Windows, PowerShell)

Prérequis : PHP 8.2+, Composer.

```powershell
git clone https://github.com/ZOULOUKNK/HELPDESK.git
cd HELPDESK
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database\database.sqlite -ItemType File
php artisan migrate --seed
php artisan serve
```

Ouvrir ensuite http://127.0.0.1:8000.

`migrate --seed` crée les comptes de démonstration et environ 200 tickets d'exemple.

### Utiliser MySQL

Dans `.env`, remplacer `DB_CONNECTION=sqlite` par :

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk
DB_USERNAME=root
DB_PASSWORD=
```

Créer la base `helpdesk`, puis lancer `php artisan migrate --seed`.

## Comptes de démonstration

Mot de passe de tous les comptes : `password`. **À changer avant toute mise en ligne.**

| Rôle | E-mail |
|---|---|
| Super-administrateur | superadmin@helpdesk.fr |
| Administrateur | admin@helpdesk.fr |
| Agents | thomas@helpdesk.fr, sophie@helpdesk.fr, lucas@helpdesk.fr |
| Clients | claire@helpdesk.fr, julien@helpdesk.fr, emma@helpdesk.fr, nicolas@helpdesk.fr, laura@helpdesk.fr |

## Commandes utiles

| Commande | Effet |
|---|---|
| `php artisan test` | Lance les tests (base SQLite en mémoire, sans toucher à vos données) |
| `php artisan migrate:fresh --seed` | Recrée la base avec les données de démonstration (**efface tout**) |
| `php artisan tickets:clear` | Supprime tous les tickets, réponses et historiques |

## Sécurité

- Un client ne voit que ses propres tickets et leurs pièces jointes.
- Les pièces jointes sont stockées dans `storage/app/private` et servies par l'application après contrôle d'accès ; celles des notes internes sont réservées au support.
- Seul un super-administrateur peut gérer un compte super-administrateur. Personne ne peut changer son propre rôle ni désactiver son propre compte.
- Connexion et inscription limitées à 5 tentatives par minute.

## Tests

```powershell
php artisan test
```

`tests/Feature` couvre :

- la connexion (redirections, identifiants erronés, compte désactivé, limitation des tentatives) ;
- l'accès aux tickets et aux pièces jointes selon le rôle ;
- la gestion des comptes par les administrateurs ;
- l'installation de la base (migrations, clés étrangères, données de démonstration) et le démarrage sans base ;
- le nombre de requêtes du tableau de bord.
