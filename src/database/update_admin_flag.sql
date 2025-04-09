-- Script pour mettre à jour le flag is_admin de l'utilisateur Admin
UPDATE users SET is_admin = 1 WHERE username = 'Admin';
