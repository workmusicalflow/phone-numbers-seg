# Tests d'intégration WhatsApp

## Vue d'ensemble

Ce document décrit les tests d'intégration mis en place pour valider le bon fonctionnement de l'intégration WhatsApp dans Oracle.

## Tests créés

### 1. Test d'intégration backend (`test-whatsapp-integration.php`)

Ce script teste :
- L'injection de dépendances des services WhatsApp
- L'envoi de messages texte simples
- La récupération des messages
- La simulation du webhook
- La sauvegarde en base de données
- Les templates WhatsApp
- Les performances d'envoi

**Utilisation :**
```bash
php scripts/test-whatsapp-integration.php
```

### 2. Test du webhook avec localtunnel (`test-whatsapp-webhook-localtunnel.sh`)

Ce script :
- Démarre un serveur PHP local
- Crée un tunnel public avec localtunnel
- Teste la vérification du webhook
- Simule l'envoi de webhooks (status updates, messages entrants)
- Vérifie la création des logs

**Utilisation :**
```bash
./scripts/test-whatsapp-webhook-localtunnel.sh
```

**Configuration requise :**
- Installer localtunnel : `npm install -g localtunnel`
- Le serveur reste actif jusqu'à l'interruption (Ctrl+C)

### 3. Tests E2E frontend (`whatsapp-integration.spec.js`)

Tests Playwright qui vérifient :
- La navigation vers la page WhatsApp
- L'envoi de messages texte
- L'affichage de l'historique des messages
- Le changement d'onglets
- Le filtrage des messages
- Les statistiques
- La gestion des erreurs
- La validation des formulaires
- Le rafraîchissement automatique

**Utilisation :**
```bash
npm run test:e2e
# ou pour un test spécifique
npx playwright test whatsapp-integration.spec.js
```

### 4. Test de l'API GraphQL (`test-whatsapp-graphql.sh`)

Ce script teste toutes les mutations et requêtes GraphQL :
- Authentification
- Envoi de messages (texte et template)
- Récupération des messages
- Récupération des templates
- Filtrage et pagination
- Gestion des erreurs

**Utilisation :**
```bash
./scripts/test-whatsapp-graphql.sh
```

### 5. Test de la base de données (`test-whatsapp-database.php`)

Vérifie :
- La structure des tables WhatsApp
- L'insertion de messages
- La récupération avec différents critères
- Les mises à jour de statut
- Les requêtes complexes
- Les performances
- L'intégrité des données

**Utilisation :**
```bash
php scripts/test-whatsapp-database.php
```

## Workflow de test complet

Pour effectuer un test complet de l'intégration :

1. **Préparer l'environnement**
   ```bash
   # S'assurer que la base de données est à jour
   php scripts/update-doctrine-schema.php
   
   # Créer un utilisateur test si nécessaire
   php scripts/create_user.php
   ```

2. **Tester le backend**
   ```bash
   # Test d'intégration général
   php scripts/test-whatsapp-integration.php
   
   # Test de la base de données
   php scripts/test-whatsapp-database.php
   
   # Test de l'API GraphQL
   ./scripts/test-whatsapp-graphql.sh
   ```

3. **Tester le webhook**
   ```bash
   # Dans un terminal séparé
   ./scripts/test-whatsapp-webhook-localtunnel.sh
   
   # Noter l'URL du tunnel pour la configuration dans Meta
   ```

4. **Tester le frontend**
   ```bash
   # Démarrer le serveur de développement
   npm run dev
   
   # Dans un autre terminal
   npm run test:e2e
   ```

## Validation des fonctionnalités

### ✅ Fonctionnalités validées

1. **Envoi de messages**
   - Messages texte simples
   - Messages avec templates
   - Validation des numéros de téléphone

2. **Réception et webhooks**
   - Vérification du webhook
   - Traitement des mises à jour de statut
   - Gestion des messages entrants

3. **Stockage et récupération**
   - Sauvegarde en base de données
   - Requêtes avec filtres
   - Pagination
   - Historique par utilisateur

4. **Interface utilisateur**
   - Vue WhatsApp fonctionnelle
   - Formulaires de saisie
   - Affichage de l'historique
   - Statistiques en temps réel

### ⚠️ Points à vérifier en production

1. **Configuration Meta**
   - URL du webhook en production
   - Token de vérification
   - Gestion des certificats SSL

2. **Limites de l'API**
   - Rate limiting
   - Quotas d'envoi
   - Gestion des erreurs de l'API

3. **Sécurité**
   - Validation des signatures webhook
   - Permissions utilisateur
   - Journalisation des actions

## Problèmes connus et solutions

### 1. Erreur de connexion à l'API Meta
**Symptôme :** Messages d'erreur lors de l'envoi
**Solution :** Vérifier les tokens et IDs dans la configuration

### 2. Webhook non reçu
**Symptôme :** Les statuts ne se mettent pas à jour
**Solution :** Vérifier l'URL du webhook et le token de vérification

### 3. Performance dégradée
**Symptôme :** Lenteur lors de l'envoi en masse
**Solution :** Implémenter la file d'attente (tâche #13)

## Métriques de succès

- **Taux de réussite des envois :** > 95%
- **Temps de réponse moyen :** < 2 secondes
- **Disponibilité du webhook :** > 99%
- **Intégrité des données :** 100%

## Prochaines étapes

1. Implémenter des tests de charge
2. Ajouter des tests de sécurité
3. Créer des tests d'intégration continue (CI)
4. Documenter les procédures de débogage
5. Mettre en place la surveillance en production