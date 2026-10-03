# TWEET — Messagerie instantanée

Application web de messagerie (HTML, CSS, JavaScript, PHP, MySQL) — architecture MVC.

## Phase 1 (livrée)

- Inscription / connexion / déconnexion
- Profil + confidentialité + changement de mot de passe
- Recherche d'utilisateurs
- Contacts (ajout / suppression)
- Blocage / déblocage
- Sessions actives
- Schéma MySQL complet (prêt pour les phases suivantes)
- Sécurité : PDO, CSRF, XSS, password_hash, rate-limit login

## Prérequis

- XAMPP (Apache + MySQL + PHP 8+)
- Extension PHP : `pdo_mysql`, `fileinfo`, `mbstring`

## Installation

1. Placez le projet dans `C:\xampp\htdocs\TWEET` (déjà le cas).

2. Démarrez **Apache** et **MySQL** dans XAMPP.

3. Importez la base :
   - Ouvrez phpMyAdmin → Importer → `database/schema.sql`
   - Ou en ligne de commande :

```bash
e:\xampp\mysql\bin\mysql.exe -u root < e:\xampp\htdocs\TWEET\database\schema.sql
```

4. Vérifiez `config/database.php` (par défaut : user `root`, mot de passe vide).

5. Ouvrez : [http://localhost/TWEET/public](http://localhost/TWEET/public)

## Compte admin

| Champ | Valeur |
|-------|--------|
| Email | `admin@tweet.local` |
| Mot de passe | `Admin@123` |

## Structure

```
TWEET/
├── app/Controllers|Models|Views|Core|Middleware|Helpers
├── config/
├── database/schema.sql
└── public/  ← point d'entrée web
```

## Prochaines phases

2. Messagerie privée · 3. Multimédia · 4. Groupes · 5. Statuts · 6. Temps réel / appels · 7. Admin
