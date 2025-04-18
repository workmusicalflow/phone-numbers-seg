# Suivi de Migration vers Doctrine ORM

Ce document permet de suivre l'avancement de l'implémentation de Doctrine ORM dans le projet Oracle. Il est organisé par modules et fonctionnalités, avec des listes de vérification pour chaque étape.

## État Global de la Migration

- [x] Installation des dépendances Doctrine
- [x] Configuration de base de Doctrine ORM
- [x] Création de l'infrastructure de base (BaseRepository, interfaces)
- [x] Preuve de concept avec l'entité SenderName
- [x] Migration des modules principaux (Utilisateurs, Contacts, Groupes de Contacts, SMS)
- [x] Migration complète des entités et repositories
- [x] Scripts de migration de données
- [ ] Tests complets de l'implémentation
- [ ] Documentation complète

## Infrastructure de Base

- [x] Installation des packages Doctrine ORM
- [x] Création du fichier bootstrap-doctrine.php
- [x] Création de l'interface DoctrineRepositoryInterface
- [x] Création de la classe BaseRepository
  - [x] Correction du problème de récursion infinie dans le constructeur (suppression de `$this->repository = $entityManager->getRepository($entityClass)`)
  - [x] Implémentation des méthodes utilisant directement l'EntityManager au lieu du repository
- [x] Mise à jour du conteneur d'injection de dépendances
- [x] Script de test pour valider l'infrastructure

## Migration des Entités et Repositories par Module

### Module Utilisateurs

- [x] Entité User

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires
  - [x] Ajout des colonnes api_key et reset_token

- [x] Repository UserRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [x] Mise à jour des services
  - [x] Adaptation de AuthService
  - [x] Tests d'intégration

### Module Contacts

- [x] Entité Contact

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Repository ContactRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [ ] Mise à jour des services
  - [ ] Adaptation des services concernés
  - [ ] Tests d'intégration

### Module Groupes de Contacts

- [x] Entité ContactGroup

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Entité ContactGroupMembership

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Repository ContactGroupRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [x] Repository ContactGroupMembershipRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [ ] Mise à jour des services
  - [ ] Adaptation des services concernés
  - [ ] Tests d'intégration

### Module SMS

- [x] Entité SMSHistory

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Repository SMSHistoryRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [ ] Mise à jour des services
  - [ ] Adaptation de SMSService
  - [ ] Adaptation de SMSBusinessService
  - [ ] Adaptation de SMSHistoryService
  - [ ] Tests d'intégration

### Module Noms d'Expéditeur

- [x] Entité SenderName

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Repository SenderNameRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [x] Mise à jour des services
  - [x] Création du SenderNameService
  - [x] Implémentation de la limite de deux noms d'expéditeur approuvés par utilisateur
  - [x] Tests d'intégration

### Module Commandes SMS

- [x] Entité SMSOrder

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Repository SMSOrderRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [ ] Mise à jour des services
  - [ ] Adaptation des services concernés
  - [ ] Tests d'intégration

### Module Segments

- [x] Entité Segment

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Entité CustomSegment

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Repository SegmentRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [x] Repository CustomSegmentRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [x] Entité PhoneNumberSegment

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Repository PhoneNumberSegmentRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [x] Repository TechnicalSegmentRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [ ] Mise à jour des services
  - [ ] Adaptation de PhoneSegmentationService
  - [ ] Adaptation de BatchSegmentationService
  - [ ] Adaptation de CustomSegmentMatcher
  - [ ] Tests d'intégration

### Module Configuration API Orange

- [x] Entité OrangeAPIConfig

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec attributs PHP 8
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires
  - [x] Ajout d'une contrainte d'unicité sur user_id

- [x] Repository OrangeAPIConfigRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [x] Mise à jour des services
  - [x] Création du OrangeAPIConfigService
  - [x] Implémentation de la restriction d'accès aux administrateurs
  - [x] Tests d'intégration

### Module Numéros de Téléphone (Legacy)

- [x] Entité PhoneNumber

  - [x] Création de la classe entité
  - [x] Mapping des propriétés avec annotations
  - [x] Implémentation des getters/setters
  - [x] Tests unitaires

- [x] Repository PhoneNumberRepository

  - [x] Création de la classe repository
  - [x] Implémentation des méthodes spécifiques
  - [x] Mise à jour du conteneur DI
  - [x] Tests unitaires

- [ ] Mise à jour des services
  - [ ] Adaptation des services concernés
  - [ ] Tests d'intégration

## Tests d'Intégration Globaux

- [x] Tests de migration de données

  - [x] Scripts de migration des données existantes
  - [x] Validation de l'intégrité des données migrées

- [ ] Tests de performance

  - [ ] Benchmarks avant/après migration
  - [ ] Optimisation si nécessaire

- [ ] Tests fonctionnels
  - [ ] Validation des fonctionnalités principales
  - [ ] Vérification de la non-régression

## Documentation

- [x] Documentation de l'architecture Doctrine
- [x] Guide d'utilisation de Doctrine ORM
- [x] Documentation des entités et repositories
  - [x] Documentation des services SenderNameService et OrangeAPIConfigService
  - [x] Documentation des nouvelles fonctionnalités (API key, reset token, etc.)
- [ ] Guide de migration pour les développeurs

## Déploiement

- [x] Script de mise à jour du schéma de base de données
  - [x] Création du script update-schema.sql
  - [x] Création du script apply-schema-updates.php
- [ ] Procédure de déploiement
- [ ] Plan de rollback en cas de problème

## Plan d'Implémentation Détaillé

### Phase 1: Complétion des Entités et Repositories (Semaines 1-2)

1. ✅ Implémenter les entités et repositories pour le module Utilisateurs
2. ✅ Implémenter les entités et repositories pour le module Contacts
3. ✅ Implémenter les entités et repositories pour le module Groupes de Contacts
4. ✅ Implémenter les entités et repositories pour le module SMS
5. ✅ Implémenter les entités et repositories pour le module Commandes SMS
6. ✅ Implémenter les entités et repositories pour le module Segments
7. ✅ Implémenter les entités et repositories pour le module Configuration API Orange
8. ✅ Implémenter les entités et repositories pour le module Numéros de Téléphone (Legacy)
   - [x] Analyser la structure actuelle du modèle PhoneNumber
   - [x] Créer l'entité PhoneNumber avec annotations Doctrine
   - [x] Implémenter les getters/setters
   - [x] Créer le repository PhoneNumberRepository
   - [x] Implémenter les méthodes spécifiques (findById, findByNumber, etc.)
   - [x] Mettre à jour le conteneur DI
   - [x] Écrire les tests unitaires
   - [x] Créer un script de test pour valider l'implémentation
9. ✅ Implémenter le TechnicalSegmentRepository
   - [x] Créer la classe repository
   - [x] Implémenter les méthodes spécifiques
   - [x] Mettre à jour le conteneur DI
   - [x] Écrire les tests unitaires

### Phase 2: Mise à jour du Conteneur DI (Semaine 3)

1. Compléter les mises à jour du conteneur DI pour tous les repositories
   - [x] UserRepository
   - [x] ContactRepository
   - [x] ContactGroupRepository
   - [x] ContactGroupMembershipRepository
   - [x] SMSHistoryRepository
   - [x] SMSOrderRepository
   - [ ] Autres repositories manquants
2. Créer un pattern cohérent pour l'enregistrement des repositories
   - [ ] Définir une structure standard pour les factory functions
   - [ ] Implémenter un mécanisme pour basculer facilement entre les implémentations legacy et Doctrine
   - [ ] Documenter l'approche pour les développeurs

### Phase 3: Adaptation des Services (Semaines 3-4)

1. Mettre à jour les services pour utiliser les repositories Doctrine
   - [x] AuthService
     - [x] Identifier les méthodes qui utilisent le repository legacy
     - [x] Adapter les méthodes pour utiliser le repository Doctrine
     - [ ] Tester les fonctionnalités
   - [x] SMSService
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
   - [x] SMSBusinessService
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
   - [x] SMSHistoryService
     - [x] Identifier les méthodes qui utilisent le repository legacy
     - [x] Adapter les méthodes pour utiliser le repository Doctrine
     - [ ] Tester les fonctionnalités
   - [x] PhoneSegmentationService
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
   - [x] BatchSegmentationService
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
   - [x] CustomSegmentMatcher
     - [x] Identifier les méthodes qui utilisent le repository legacy
     - [x] Adapter les méthodes pour utiliser le repository Doctrine
     - [ ] Tester les fonctionnalités
   - [x] CSVImportService
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
   - [x] ExportService
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
   - [x] GraphQLFormatterService
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
   - [x] RealtimeNotificationService
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
   - [x] NotificationService
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
   - [x] AdminActionLogger
     - [x] Identifier les méthodes qui utilisent les repositories legacy
     - [x] Adapter les méthodes pour utiliser les repositories Doctrine
     - [ ] Tester les fonctionnalités
2. Implémenter le pattern Adapter où nécessaire
   - [ ] Identifier les services qui nécessitent une compatibilité ascendante
   - [ ] Créer des adaptateurs pour ces services
   - [ ] Tester la compatibilité

### Phase 4: Migration des Données (Semaines 5-6)

1. Créer des scripts de migration de données
   - [x] Analyser la structure des données existantes
   - [x] Créer un script pour migrer les utilisateurs (migrate-users.php)
   - [x] Créer un script pour migrer les contacts (migrate-contacts.php)
   - [x] Créer un script pour migrer les groupes de contacts (migrate-contact-groups.php)
   - [x] Créer un script pour migrer les appartenances aux groupes (migrate-contact-group-memberships.php)
   - [x] Créer un script pour migrer l'historique SMS (migrate-sms-history.php)
   - [x] Créer un script pour migrer les commandes SMS (migrate-sms-orders.php)
   - [x] Créer un script pour migrer les segments personnalisés (migrate-segments.php)
   - [x] Créer un script pour migrer les numéros de téléphone (migrate-phone-numbers.php)
   - [x] Créer un script pour migrer les associations entre numéros et segments (migrate-phone-number-segments.php)
2. Implémenter la validation de l'intégrité des données
   - [x] Créer des tests de validation pour chaque type de données
   - [x] Vérifier les contraintes d'intégrité
   - [x] Vérifier les relations entre entités
3. Développer une stratégie pour la période de transition
   - [ ] Concevoir un mécanisme de double écriture (legacy et Doctrine)
   - [ ] Implémenter des vérifications de cohérence
   - [ ] Créer un plan de basculement progressif

### Phase 5: Tests d'Intégration (Semaines 7-8)

1. Développer des tests d'intégration complets
   - [ ] Créer des tests pour les fonctionnalités inter-modules
   - [ ] Tester les endpoints API
   - [ ] Tester les resolvers GraphQL
   - [ ] Effectuer des benchmarks de performance
2. Créer une suite de tests automatisés
   - [ ] Configurer un environnement de test
   - [ ] Automatiser l'exécution des tests
   - [ ] Intégrer les tests dans le pipeline CI/CD

### Phase 6: Documentation (Semaine 8)

1. Documenter les entités et leurs relations
   - [ ] Créer des diagrammes ER
   - [ ] Documenter les propriétés et méthodes des entités
   - [ ] Documenter les relations entre entités
2. Documenter les repositories
   - [ ] Documenter les méthodes publiques
   - [ ] Fournir des exemples d'utilisation
3. Créer un guide de migration pour les développeurs
   - [ ] Documenter le processus de migration
   - [ ] Fournir des exemples de code avant/après
   - [ ] Documenter les pièges courants
4. Mettre à jour les diagrammes d'architecture
   - [ ] Créer des diagrammes reflétant la nouvelle structure ORM
   - [ ] Documenter les flux de données

### Phase 7: Déploiement (Semaine 9-10)

1. Créer des scripts de mise à jour du schéma de base de données
   - [ ] Créer des scripts pour chaque module
   - [ ] Tester les scripts dans un environnement de staging
2. Développer une stratégie de déploiement par phases
   - [ ] Identifier les modules à déployer en premier
   - [ ] Créer un plan de déploiement progressif
   - [ ] Définir des critères de succès pour chaque phase
3. Implémenter la surveillance pour la transition
   - [ ] Configurer des alertes pour les erreurs
   - [ ] Mettre en place des métriques de performance
   - [ ] Créer des tableaux de bord de surveillance
4. Créer un plan de rollback
   - [ ] Définir des critères de rollback
   - [ ] Créer des scripts de rollback
   - [ ] Tester les procédures de rollback

## Intégration avec l'Implémentation des URL Constants

L'implémentation des URL constants peut être coordonnée avec la migration Doctrine ORM:

1. Pistes de développement parallèles

   - [ ] Équipe A: Complétion de la migration Doctrine ORM
   - [ ] Équipe B: Implémentation de la structure des URL constants

2. Points d'intégration coordonnés

   - [ ] Lors de la mise à jour des services pour utiliser les repositories Doctrine, mettre à jour simultanément la gestion des URL
   - [ ] S'assurer que les nouvelles constantes URL sont utilisées dans tout le nouveau code lié à Doctrine
   - [ ] Inclure l'utilisation des constantes URL dans tous les tests d'intégration

3. Documentation partagée

   - [ ] Documenter les deux initiatives de manière coordonnée
   - [ ] Créer des références croisées entre les deux ensembles de documentation

4. Tests combinés
   - [ ] Tester les deux changements ensemble pour assurer la compatibilité
   - [ ] Créer des cas de test qui vérifient spécifiquement l'interaction entre les deux systèmes
