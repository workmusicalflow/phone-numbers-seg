# ORACLE PROJECT - INSTRUCTIONS FOR CLAUDE

## Project Overview

Oracle is a modern PHP 8.3 and Vue.js 3 SMS management platform with phone number segmentation capabilities. The application enables SMS sending via Orange API integration, whatsapp messages (with Meta API Cloud), contact management, and advanced segmentation features.

## Technical Architecture

### Backend

- **PHP 8.3** with SOLID principles and clean code patterns
- **SQLite** database with Doctrine ORM (standalone implementation) `/Users/ns2poportable/Desktop/phone-numbers-seg/var/database.sqlite` or relative path is `var/database.sqlite`
- **GraphQL API** using GraphQLite for type generation
- **REST API** for compatibility with existing systems
- **Design Patterns**: Repository, Service, Dependency Injection (PHP-DI), DataLoader

### Frontend

- **Vue.js 3** with Composition API
- **TypeScript** for type safety
- **Quasar Framework** for UI components
- **Pinia** for state management
- **Vite** for development and building

## Code Standards

### PHP

- Follow **PSR-12** coding standards
- Use **strict type declarations**
- Implement **SOLID principles**
- Document with **PHP DocBlocks in French**
- Classes in `PascalCase`, methods/variables in `camelCase`, constants in `UPPER_SNAKE_CASE`
- Files match class names with PSR-4 namespacing

### JavaScript/TypeScript

- Use **ESLint** and **Prettier** standards
- Vue components in `PascalCase`, props/methods in `camelCase`
- Pinia stores named `camelCaseStore`
- CSS/SASS files in `kebab-case`

### Database

- Tables in `snake_case_plural`, columns in `snake_case`
- Include timestamps (`created_at`, `updated_at`)
- Implement soft deletes where appropriate
- Use prepared statements for all SQL queries

### GraphQL

- Types in `PascalCase`, fields in `camelCase`
- Implement DataLoader pattern to prevent N+1 query problems
- Use batching for similar queries

## Testing Requirements

- Run unit tests before committing changes: `vendor/bin/phpunit` 
- Maintain test coverage >80%
- Tests are essential and must be fixed regardless of difficulty as they are crucial for TDD and quality
- Implement tests according to Clean Code, Clean Architecture and SOLID principles
- Frontend tests: `npm run test` or `npm run test:unit`
- Backend tests: `vendor/bin/phpunit`
- Write tests for repositories, services, and controllers

## Security Practices

- Validate all input systematically
- Sanitize user data
- Prevent XSS, CSRF, and SQL injection
- Avoid storing sensitive information in logs

## Project Specifics

- Phone numbers: Normalize to +XXX format, validate with RegEx
- Phone segmentation: Country code, operator code, subscriber number
- SMS: 160 character limit with history tracking
- Import/Export: Support for CSV with flexible delimiters and encoding

## Workflow Guidelines

- Feature branches with descriptive names
- Commit messages should be clear and descriptive
- Run linting before committing:
  - Backend: PHP linting tools (TBD)
  - Frontend: `npm run lint`

## Environment Setup

- Backend is PHP 8.3 with Composer for dependencies
- Frontend requires Node.js v22.14.0 (LTS) and npm

## ✅ FONCTIONNALITÉS RÉCEMMENT COMPLETÉES

### 🎯 Import CSV avec Auto-assignation aux Groupes (Janvier 2025)

**Statut : ✅ TERMINÉ ET VALIDÉ**

Implémentation complète de l'import CSV avec assignation automatique aux groupes de contacts, incluant :

#### Backend (PHP)
- **CSVImportService étendu** : Support des `groupIds` avec assignation automatique via `ContactGroupMembershipRepository`
- **GraphQL API** : Extension d'`ImportExportController` avec validation et gestion des erreurs
- **Transformation des données** : Méthode `transformImportResult()` pour compatibilité schema GraphQL

#### Frontend (Vue.js/TypeScript) 
- **Interface utilisateur** : Sélecteur de groupes multiples dans `ImportCSVForm.vue`
- **Authentification automatique** : Suppression du sélecteur utilisateur, affectation automatique au compte actif
- **Gestion d'erreurs** : Notifications améliorées et dialogue de résultats détaillé
- **Corrections critiques** : Résolution boucle infinie récursive et erreurs d'import path

#### Validation E2E
- ✅ Interface fonctionnelle sans erreurs Vite
- ✅ Authentification et navigation opérationnelles  
- ✅ Chargement et sélection des groupes confirmés
- ✅ Configuration automatique utilisateur validée
- ✅ Prêt pour test CSV avec groupe QUALIPRO

#### Fichiers Modifiés
```
Backend:
- src/Services/CSVImportService.php
- src/GraphQL/Controllers/ImportExportController.php
- src/Repositories/ContactGroupMembershipRepository.php

Frontend:
- frontend/src/components/import-export/ImportCSVForm.vue
- frontend/src/components/import-export/composables/useImport.ts
- frontend/src/views/Import.vue
- frontend/src/stores/authStore.ts (path correction)
```

## Current Focus

- Finalizing Doctrine ORM migration
- Implementing URL constants system  
- Enhancing the ContactCountBadge component
- Fixing WhatsApp template issues related to API connectivity

## Meta API Credentials

These credentials are used for WhatsApp Business Cloud API:

- App ID: Check in .env file (WHATSAPP_APP_ID)
- Phone Number ID: Check in .env file (WHATSAPP_PHONE_NUMBER_ID)
- WhatsApp Business Account ID: Check in .env file (WHATSAPP_WABA_ID)
- API Version: v22.0 (WHATSAPP_API_VERSION)
- Access Token: Check in .env file (WHATSAPP_ACCESS_TOKEN or WHATSAPP_API_TOKEN)
- Webhook Verify Token: "oracle_whatsapp_webhook_verification_token" (WHATSAPP_WEBHOOK_VERIFY_TOKEN)
- Webhook Callback URL: Check in .env file (WHATSAPP_WEBHOOK_CALLBACK_URL)

## Before Submitting Code

1. Ensure all tests pass
2. Run linting and fix any issues
3. Document new features or changes
4. Verify compatibility with both development and production environments

**This documentation is maintained based on the .clinerules folder which contains the project's technical standards, context and current focus areas.**
