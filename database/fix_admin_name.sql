USE tweet_messagerie;
UPDATE users
SET prenom = 'Système', username = 'admin'
WHERE id = 1 AND role = 'admin';
