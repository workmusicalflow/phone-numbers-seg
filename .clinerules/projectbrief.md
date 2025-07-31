# Projet Oracle - Gestionnaire de Numéros de Téléphone

## Aperçu du Projet

Oracle est une application web complète pour la segmentation, l'analyse et la gestion des numéros de téléphone. Elle permet aux utilisateurs d'extraire des informations structurées à partir de numéros de téléphone, de les organiser en groupes et segments, d'envoyer des SMS et de suivre l'historique des communications.

## Objectifs Principaux

1. **Segmentation de numéros** : Analyser les numéros de téléphone pour extraire des informations comme le code pays, l'opérateur et le numéro d'abonné.
2. **Traitement par lots** : Permettre la segmentation simultanée de grandes quantités de numéros.
3. **Envoi de SMS** : Intégrer l'API Orange SMS pour l'envoi de messages individuels ou en masse.
4. **Historique et traçabilité** : Suivre toutes les opérations effectuées sur les numéros, notamment les SMS envoyés.
5. **Import/Export** : Faciliter l'échange de données avec d'autres systèmes via différents formats.
6. **API flexible** : Exposer les fonctionnalités via des API REST et GraphQL pour l'intégration avec d'autres systèmes.

## Architecture Technique

L'application est construite selon une architecture en couches clairement séparées :

1. **Couche de présentation** : Interface utilisateur et API

   - Frontend en Vue.js avec Quasar Framework
   - API REST pour la compatibilité avec les systèmes existants
   - API GraphQL pour une flexibilité accrue

2. **Couche métier** : Services et contrôleurs

   - Services encapsulant la logique métier complexe
   - Contrôleurs pour la gestion des requêtes HTTP

3. **Couche de données** : Modèles et repositories
   - Modèles représentant les entités du système
   - Repositories pour l'accès aux données
   - Base de données SQLite pour le stockage

## Technologies Utilisées

### Backend

- **PHP 8.3** : Langage principal du backend
- **SQLite** : Base de données légère et portable
- **Composer** : Gestion des dépendances PHP
- **GraphQLite** : Implémentation GraphQL pour PHP

### Frontend

- **Vue.js 3** : Framework frontend progressif
- **Quasar Framework** : Composants UI pour Vue.js
- **TypeScript** : Typage statique pour JavaScript
- **Pinia** : Gestion d'état pour Vue.js
- **Vite** : Outil de build rapide

### Outils de Développement

- **PHPUnit** : Tests unitaires pour PHP
- **Vitest** : Tests unitaires pour Vue.js
- **ESLint/Prettier** : Linting et formatage du code
- **Git** : Contrôle de version

## Fonctionnalités Principales

### Segmentation de Numéros

- Analyse des numéros pour identifier le code pays, l'opérateur, etc.
- Segmentation individuelle ou par lot
- Détection automatique de l'opérateur
- Segments personnalisés définis par l'utilisateur

### Gestion des Contacts

- Stockage des informations de contact (nom, prénom, civilité)
- Organisation en segments personnalisés
- Recherche et filtrage des contacts

### Envoi de SMS

- Intégration avec l'API Orange SMS
- Envoi individuel ou en masse
- Suivi des statuts d'envoi
- Historique complet des SMS envoyés

### Import/Export

- Import depuis CSV ou texte brut
- Export vers différents formats (CSV, Excel)
- Options de filtrage pour l'export
- Validation et normalisation des données importées

### API

- API REST avec endpoints documentés
- API GraphQL avec schéma auto-documenté
- Validation des entrées et gestion des erreurs
- Pagination pour les grandes collections

## État Actuel du Projet

Le projet est actuellement à environ 87% de complétion, avec plusieurs fonctionnalités clés déjà implémentées et opérationnelles. Les développements récents incluent :

1. **Extension du modèle de données** : Ajout des champs civilité et prénom au modèle PhoneNumber
2. **Finalisation de l'import/export** : Implémentation complète de l'import CSV et du texte brut
3. **Intégration GraphQL** : API GraphQL complète en parallèle de l'API REST existante
4. **Migration vers Vue.js** : Progression significative dans la migration de l'interface utilisateur
5. **Amélioration du système SMS** : Gestion des erreurs robuste et système d'historique complet

## Contraintes et Considérations

1. **Performance** : Le traitement par lot doit rester performant même avec un grand nombre de numéros
2. **Sécurité** : Validation stricte des entrées pour éviter les injections et autres vulnérabilités
3. **Expérience utilisateur** : Interface intuitive et réactive, même pendant le traitement de grandes quantités de données
