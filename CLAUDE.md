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

- Run unit tests before committing changes
- Maintain test coverage >80%
- Frontend tests: `npm run test` or `npm run test:unit`
- Backend tests: `phpunit`

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

## Current Focus

- Finalizing Doctrine ORM migration
- Implementing URL constants system
- Enhancing the ContactCountBadge component

## Before Submitting Code

1. Ensure all tests pass
2. Run linting and fix any issues
3. Document new features or changes
4. Verify compatibility with both development and production environments

**This documentation is maintained based on the .clinerules folder which contains the project's technical standards, context and current focus areas.**
