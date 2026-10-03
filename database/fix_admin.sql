-- Réapplique le mot de passe admin (Admin@123) si besoin
USE tweet_messagerie;
UPDATE users
SET password = '$2y$10$Rib5c64dN35vC26oABIh8eoiJ/1DSzGv9LdqJUWz0CEybVZxekMN.'
WHERE email = 'admin@tweet.local';
