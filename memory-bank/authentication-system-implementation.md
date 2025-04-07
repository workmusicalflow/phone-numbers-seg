# Implémentation du Système d'Authentification

## Composants Implémentés

### Backend

1. **Interface AuthServiceInterface**

   - Définit les méthodes nécessaires pour l'authentification
   - Gestion des tentatives de connexion échouées
   - Réinitialisation de mot de passe

2. **Service AuthService**

   - Implémente l'interface AuthServiceInterface
   - Authentification des utilisateurs
   - Vérification de la complexité des mots de passe
   - Gestion du verrouillage des comptes
   - Génération et vérification des tokens de réinitialisation de mot de passe

3. **Middleware JWTAuthMiddleware**
   - Vérifie la validité des tokens JWT pour les requêtes API

### Frontend

1. **Store Pinia pour l'authentification (authStore.ts)**

   - Gestion de l'état d'authentification
   - Méthodes pour la connexion, déconnexion, rafraîchissement du token
   - Gestion de la réinitialisation du mot de passe

2. **Pages Vue.js**

   - Page de connexion (Login.vue)
   - Page de réinitialisation de mot de passe (ResetPassword.vue)

3. **Intégration dans le routeur Vue Router**

   - Gardes de navigation pour protéger les routes
   - Redirection vers la page de connexion pour les routes protégées

4. **Intégration dans l'application principale (App.vue)**
   - Bouton de connexion/déconnexion dans la barre de navigation
   - Affichage conditionnel des éléments de menu selon les droits de l'utilisateur

## Flux d'Authentification

1. **Connexion**

   - L'utilisateur saisit son nom d'utilisateur et son mot de passe
   - Le service d'authentification vérifie les informations
   - En cas de succès, une session est créée côté serveur
   - Un cookie de session est envoyé au client et utilisé pour les requêtes ultérieures

2. **Vérification d'Authentification**

   - Le middleware vérifie la validité du cookie de session pour chaque requête API
   - Le routeur Vue vérifie l'authentification pour chaque changement de route en interrogeant le serveur

3. **Déconnexion**

   - La session est détruite côté serveur
   - Le cookie de session est invalidé
   - L'utilisateur est redirigé vers la page de connexion

4. **Réinitialisation de Mot de Passe**
   - L'utilisateur demande une réinitialisation en fournissant son email
   - Un email contenant un lien avec un token est envoyé
   - L'utilisateur clique sur le lien et est redirigé vers la page de réinitialisation
   - Le nouveau mot de passe est validé et enregistré

## Sécurité

1. **Protection contre les attaques par force brute**

   - Verrouillage du compte après un certain nombre de tentatives échouées
   - Délai de verrouillage configurable

2. **Validation des mots de passe**

   - Vérification de la complexité (longueur, caractères spéciaux, etc.)
   - Hachage sécurisé des mots de passe avec bcrypt

3. **Protection des routes**
   - Routes protégées par authentification
   - Routes administratives protégées par vérification des droits

## Améliorations Futures

1. **Authentification à Deux Facteurs (2FA)**

   - Implémentation de l'authentification par SMS ou application d'authentification

2. **Gestion des Sessions**

   - Amélioration de la gestion des sessions multiples
   - Possibilité de déconnecter toutes les sessions actives

3. **Audit de Sécurité**

   - Journalisation des connexions et tentatives échouées
   - Alertes en cas d'activités suspectes

4. **Intégration avec des Fournisseurs d'Identité Externes**
   - Authentification via Google, Facebook, etc.
