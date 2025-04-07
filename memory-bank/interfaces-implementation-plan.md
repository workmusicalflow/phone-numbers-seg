# Plan d'Implémentation des Fonctionnalités Manquantes

Ce document présente un plan détaillé pour finaliser les fonctionnalités manquantes des interfaces administrateur et utilisateur du projet Oracle. Pour chaque fonctionnalité, nous décrivons les composants à développer, les modifications à apporter aux fichiers existants, et les étapes d'implémentation.

## Interface Administrateur

### 1. Rapports et Statistiques Avancés

#### Description

Développer un système de rapports avancés permettant aux administrateurs d'analyser l'utilisation des SMS et d'exporter les données en différents formats.

#### Composants à Développer

1. **Vue de Rapports** (`frontend/src/views/Reports.vue`)

   - Interface pour générer et visualiser des rapports
   - Filtres par période, utilisateur, statut de livraison, etc.
   - Graphiques et tableaux pour visualiser les données

2. **Store Pinia** (`frontend/src/stores/reportStore.ts`)

   - Gestion de l'état des rapports
   - Méthodes pour générer et exporter les rapports

3. **Contrôleur GraphQL** (`src/GraphQL/Controllers/ReportController.php`)

   - Endpoints pour générer des rapports
   - Agrégation des données de différentes sources

4. **Service de Rapports** (`src/Services/ReportService.php`)

   - Logique métier pour la génération de rapports
   - Formatage des données pour différents types de rapports

5. **Interface de Service** (`src/Services/Interfaces/ReportServiceInterface.php`)
   - Définition des méthodes pour la génération de rapports

#### Étapes d'Implémentation

1. **Backend**

   - Créer l'interface `ReportServiceInterface`
   - Implémenter le service `ReportService`
   - Développer le contrôleur GraphQL `ReportController`
   - Ajouter les types GraphQL nécessaires

2. **Frontend**

   - Créer le store Pinia `reportStore`
   - Développer la vue `Reports.vue`
   - Intégrer les graphiques avec Chart.js
   - Implémenter l'exportation en CSV, Excel et PDF

3. **Intégration**
   - Ajouter la route dans le routeur Vue
   - Ajouter l'entrée dans le menu de navigation
   - Configurer les permissions d'accès

### 2. Gestion des Rôles et Permissions Avancée

#### Description

Développer un système avancé de gestion des rôles et permissions permettant de définir des rôles personnalisés et d'attribuer des permissions granulaires.

#### Composants à Développer

1. **Vue de Gestion des Rôles** (`frontend/src/views/Roles.vue`)

   - Interface pour créer, modifier et supprimer des rôles
   - Attribution de permissions aux rôles

2. **Vue de Gestion des Permissions** (`frontend/src/views/Permissions.vue`)

   - Interface pour gérer les permissions disponibles
   - Regroupement des permissions par module

3. **Store Pinia** (`frontend/src/stores/roleStore.ts`)

   - Gestion de l'état des rôles et permissions
   - Méthodes pour manipuler les rôles et permissions

4. **Modèles** (`src/Models/Role.php`, `src/Models/Permission.php`, `src/Models/RolePermission.php`)

   - Représentation des rôles, permissions et leurs relations

5. **Repositories** (`src/Repositories/RoleRepository.php`, `src/Repositories/PermissionRepository.php`)

   - Accès aux données des rôles et permissions

6. **Contrôleur GraphQL** (`src/GraphQL/Controllers/RoleController.php`)

   - Endpoints pour gérer les rôles et permissions

7. **Service d'Autorisation** (`src/Services/AuthorizationService.php`)

   - Logique métier pour la vérification des permissions
   - Gestion des rôles et permissions

8. **Interface de Service** (`src/Services/Interfaces/AuthorizationServiceInterface.php`)
   - Définition des méthodes pour la gestion des rôles et permissions

#### Étapes d'Implémentation

1. **Backend**

   - Créer les modèles `Role`, `Permission` et `RolePermission`
   - Créer les migrations pour les tables correspondantes
   - Implémenter les repositories `RoleRepository` et `PermissionRepository`
   - Développer l'interface `AuthorizationServiceInterface`
   - Implémenter le service `AuthorizationService`
   - Développer le contrôleur GraphQL `RoleController`
   - Ajouter les types GraphQL nécessaires

2. **Frontend**

   - Créer le store Pinia `roleStore`
   - Développer les vues `Roles.vue` et `Permissions.vue`
   - Implémenter les formulaires de création et d'édition de rôles
   - Développer l'interface d'attribution des permissions

3. **Intégration**
   - Ajouter les routes dans le routeur Vue
   - Ajouter les entrées dans le menu de navigation
   - Mettre à jour le middleware d'authentification pour utiliser le nouveau système de permissions
   - Mettre à jour les composants existants pour vérifier les permissions

### 3. Journalisation des Activités

#### Description

Développer un système de journalisation des activités administratives pour suivre les actions des utilisateurs et auditer les modifications du système.

#### Composants à Développer

1. **Vue de Journalisation** (`frontend/src/views/ActivityLog.vue`)

   - Interface pour consulter les journaux d'activité
   - Filtres par utilisateur, action, date, etc.
   - Exportation des journaux

2. **Store Pinia** (`frontend/src/stores/activityLogStore.ts`)

   - Gestion de l'état des journaux d'activité
   - Méthodes pour récupérer et filtrer les journaux

3. **Modèle** (`src/Models/ActivityLog.php`)

   - Représentation des entrées de journal d'activité

4. **Repository** (`src/Repositories/ActivityLogRepository.php`)

   - Accès aux données des journaux d'activité

5. **Contrôleur GraphQL** (`src/GraphQL/Controllers/ActivityLogController.php`)

   - Endpoints pour récupérer les journaux d'activité

6. **Service de Journalisation** (`src/Services/ActivityLogService.php`)

   - Logique métier pour l'enregistrement des activités
   - Formatage des entrées de journal

7. **Interface de Service** (`src/Services/Interfaces/ActivityLogServiceInterface.php`)
   - Définition des méthodes pour la journalisation des activités

#### Étapes d'Implémentation

1. **Backend**

   - Créer le modèle `ActivityLog`
   - Créer la migration pour la table correspondante
   - Implémenter le repository `ActivityLogRepository`
   - Développer l'interface `ActivityLogServiceInterface`
   - Implémenter le service `ActivityLogService`
   - Développer le contrôleur GraphQL `ActivityLogController`
   - Ajouter les types GraphQL nécessaires

2. **Frontend**

   - Créer le store Pinia `activityLogStore`
   - Développer la vue `ActivityLog.vue`
   - Implémenter les filtres et l'exportation des journaux

3. **Intégration**
   - Ajouter la route dans le routeur Vue
   - Ajouter l'entrée dans le menu de navigation
   - Intégrer le service de journalisation dans les services existants pour enregistrer les actions importantes

## Interface Utilisateur

### 1. Modèles de SMS

#### Description

Développer un système de modèles de SMS permettant aux utilisateurs de créer et gérer des modèles de messages avec des variables dynamiques.

#### Composants à Développer

1. **Vue de Gestion des Modèles** (`frontend/src/views/SMSTemplates.vue`)

   - Interface pour créer, modifier et supprimer des modèles de SMS
   - Prévisualisation des modèles avec variables
   - Sélection des modèles lors de l'envoi de SMS

2. **Composant d'Édition de Modèle** (`frontend/src/components/SMSTemplateEditor.vue`)

   - Éditeur de texte avec support pour les variables
   - Insertion de variables prédéfinies
   - Validation du contenu

3. **Store Pinia** (`frontend/src/stores/smsTemplateStore.ts`)

   - Gestion de l'état des modèles de SMS
   - Méthodes pour manipuler les modèles

4. **Modèle** (`src/Models/SMSTemplate.php`)

   - Représentation des modèles de SMS

5. **Repository** (`src/Repositories/SMSTemplateRepository.php`)

   - Accès aux données des modèles de SMS

6. **Contrôleur GraphQL** (`src/GraphQL/Controllers/SMSTemplateController.php`)

   - Endpoints pour gérer les modèles de SMS

7. **Service de Modèles** (`src/Services/SMSTemplateService.php`)

   - Logique métier pour la gestion des modèles
   - Remplacement des variables dans les modèles

8. **Interface de Service** (`src/Services/Interfaces/SMSTemplateServiceInterface.php`)
   - Définition des méthodes pour la gestion des modèles

#### Étapes d'Implémentation

1. **Backend**

   - Créer le modèle `SMSTemplate`
   - Créer la migration pour la table correspondante
   - Implémenter le repository `SMSTemplateRepository`
   - Développer l'interface `SMSTemplateServiceInterface`
   - Implémenter le service `SMSTemplateService`
   - Développer le contrôleur GraphQL `SMSTemplateController`
   - Ajouter les types GraphQL nécessaires

2. **Frontend**

   - Créer le store Pinia `smsTemplateStore`
   - Développer la vue `SMSTemplates.vue`
   - Implémenter le composant `SMSTemplateEditor.vue`
   - Intégrer la sélection de modèles dans la vue `SMS.vue`

3. **Intégration**
   - Ajouter la route dans le routeur Vue
   - Ajouter l'entrée dans le menu de navigation
   - Mettre à jour le service d'envoi de SMS pour utiliser les modèles

### 2. Planification d'Envois

#### Description

Développer un système de planification d'envoi de SMS permettant aux utilisateurs de programmer l'envoi de messages à des dates spécifiques et de configurer des envois récurrents.

#### Composants à Développer

1. **Vue de Planification** (`frontend/src/views/SMSSchedule.vue`)

   - Interface pour planifier des envois de SMS
   - Configuration des envois récurrents
   - Visualisation des envois planifiés

2. **Composant de Calendrier** (`frontend/src/components/SMSCalendar.vue`)

   - Affichage des envois planifiés dans un calendrier
   - Interaction pour créer et modifier des planifications

3. **Store Pinia** (`frontend/src/stores/smsScheduleStore.ts`)

   - Gestion de l'état des envois planifiés
   - Méthodes pour manipuler les planifications

4. **Modèles** (`src/Models/SMSSchedule.php`, `src/Models/RecurrenceRule.php`)

   - Représentation des envois planifiés et des règles de récurrence

5. **Repositories** (`src/Repositories/SMSScheduleRepository.php`, `src/Repositories/RecurrenceRuleRepository.php`)

   - Accès aux données des envois planifiés et des règles de récurrence

6. **Contrôleur GraphQL** (`src/GraphQL/Controllers/SMSScheduleController.php`)

   - Endpoints pour gérer les envois planifiés

7. **Service de Planification** (`src/Services/SMSScheduleService.php`)

   - Logique métier pour la gestion des planifications
   - Exécution des envois planifiés

8. **Interface de Service** (`src/Services/Interfaces/SMSScheduleServiceInterface.php`)

   - Définition des méthodes pour la gestion des planifications

9. **Tâche Cron** (`src/Cron/ProcessScheduledSMS.php`)
   - Script pour exécuter les envois planifiés

#### Étapes d'Implémentation

1. **Backend**

   - Créer les modèles `SMSSchedule` et `RecurrenceRule`
   - Créer les migrations pour les tables correspondantes
   - Implémenter les repositories `SMSScheduleRepository` et `RecurrenceRuleRepository`
   - Développer l'interface `SMSScheduleServiceInterface`
   - Implémenter le service `SMSScheduleService`
   - Développer le contrôleur GraphQL `SMSScheduleController`
   - Ajouter les types GraphQL nécessaires
   - Créer la tâche cron `ProcessScheduledSMS.php`

2. **Frontend**

   - Créer le store Pinia `smsScheduleStore`
   - Développer la vue `SMSSchedule.vue`
   - Implémenter le composant `SMSCalendar.vue`
   - Intégrer la planification dans la vue `SMS.vue`

3. **Intégration**
   - Ajouter la route dans le routeur Vue
   - Ajouter l'entrée dans le menu de navigation
   - Configurer la tâche cron pour s'exécuter régulièrement

### 3. Gestion des Contacts

#### Description

Développer un système de gestion des contacts permettant aux utilisateurs d'organiser leurs contacts en groupes et d'importer/exporter des contacts.

#### Composants à Développer

1. **Vue de Gestion des Contacts** (`frontend/src/views/Contacts.vue`)

   - Interface pour gérer les contacts
   - Création et modification de contacts
   - Organisation des contacts en groupes

2. **Vue de Gestion des Groupes** (`frontend/src/views/ContactGroups.vue`)

   - Interface pour gérer les groupes de contacts
   - Attribution de contacts aux groupes

3. **Composant d'Import/Export** (`frontend/src/components/ContactImportExport.vue`)

   - Interface pour importer et exporter des contacts
   - Support pour différents formats (CSV, vCard)

4. **Store Pinia** (`frontend/src/stores/contactStore.ts`)

   - Gestion de l'état des contacts et des groupes
   - Méthodes pour manipuler les contacts et les groupes

5. **Modèles** (`src/Models/Contact.php`, `src/Models/ContactGroup.php`, `src/Models/ContactGroupMembership.php`)

   - Représentation des contacts, des groupes et de leurs relations

6. **Repositories** (`src/Repositories/ContactRepository.php`, `src/Repositories/ContactGroupRepository.php`)

   - Accès aux données des contacts et des groupes

7. **Contrôleur GraphQL** (`src/GraphQL/Controllers/ContactController.php`)

   - Endpoints pour gérer les contacts et les groupes

8. **Service de Contacts** (`src/Services/ContactService.php`)

   - Logique métier pour la gestion des contacts
   - Import et export de contacts

9. **Interface de Service** (`src/Services/Interfaces/ContactServiceInterface.php`)
   - Définition des méthodes pour la gestion des contacts

#### Étapes d'Implémentation

1. **Backend**

   - Créer les modèles `Contact`, `ContactGroup` et `ContactGroupMembership`
   - Créer les migrations pour les tables correspondantes
   - Implémenter les repositories `ContactRepository` et `ContactGroupRepository`
   - Développer l'interface `ContactServiceInterface`
   - Implémenter le service `ContactService`
   - Développer le contrôleur GraphQL `ContactController`
   - Ajouter les types GraphQL nécessaires

2. **Frontend**

   - Créer le store Pinia `contactStore`
   - Développer les vues `Contacts.vue` et `ContactGroups.vue`
   - Implémenter le composant `ContactImportExport.vue`
   - Intégrer la sélection de contacts dans la vue `SMS.vue`

3. **Intégration**
   - Ajouter les routes dans le routeur Vue
   - Ajouter les entrées dans le menu de navigation
   - Mettre à jour le service d'envoi de SMS pour utiliser les contacts et les groupes

### 4. Tableau de Bord Utilisateur

#### Description

Développer un tableau de bord personnalisé pour les utilisateurs, affichant des statistiques d'utilisation et permettant de suivre les crédits SMS.

#### Composants à Développer

1. **Vue de Tableau de Bord** (`frontend/src/views/UserDashboard.vue`)

   - Interface personnalisée pour chaque utilisateur
   - Affichage des statistiques d'utilisation
   - Suivi des crédits SMS

2. **Composants de Visualisation** (`frontend/src/components/UsageChart.vue`, `frontend/src/components/CreditWidget.vue`)

   - Graphiques et widgets pour visualiser les données
   - Affichage interactif des statistiques

3. **Store Pinia** (`frontend/src/stores/userDashboardStore.ts`)

   - Gestion de l'état du tableau de bord
   - Méthodes pour récupérer les statistiques

4. **Contrôleur GraphQL** (`src/GraphQL/Controllers/UserDashboardController.php`)

   - Endpoints pour récupérer les données du tableau de bord

5. **Service de Tableau de Bord** (`src/Services/UserDashboardService.php`)

   - Logique métier pour la génération des statistiques
   - Agrégation des données de différentes sources

6. **Interface de Service** (`src/Services/Interfaces/UserDashboardServiceInterface.php`)
   - Définition des méthodes pour la génération des statistiques

#### Étapes d'Implémentation

1. **Backend**

   - Développer l'interface `UserDashboardServiceInterface`
   - Implémenter le service `UserDashboardService`
   - Développer le contrôleur GraphQL `UserDashboardController`
   - Ajouter les types GraphQL nécessaires

2. **Frontend**

   - Créer le store Pinia `userDashboardStore`
   - Développer la vue `UserDashboard.vue`
   - Implémenter les composants `UsageChart.vue` et `CreditWidget.vue`

3. **Intégration**
   - Ajouter la route dans le routeur Vue
   - Ajouter l'entrée dans le menu de navigation
   - Configurer le tableau de bord comme page d'accueil pour les utilisateurs connectés

## Calendrier d'Implémentation

| Fonctionnalité                   | Priorité | Estimation (jours) | Dépendances                  |
| -------------------------------- | -------- | ------------------ | ---------------------------- |
| Modèles de SMS                   | Haute    | 5                  | -                            |
| Tableau de Bord Utilisateur      | Haute    | 7                  | -                            |
| Journalisation des Activités     | Moyenne  | 4                  | -                            |
| Gestion des Contacts             | Moyenne  | 8                  | -                            |
| Rapports et Statistiques Avancés | Moyenne  | 10                 | Journalisation des Activités |
| Planification d'Envois           | Basse    | 12                 | Modèles de SMS               |
| Gestion des Rôles et Permissions | Basse    | 15                 | Journalisation des Activités |

## Conclusion

Ce plan d'implémentation détaille les fonctionnalités manquantes des interfaces administrateur et utilisateur du projet Oracle. Pour chaque fonctionnalité, nous avons décrit les composants à développer, les modifications à apporter aux fichiers existants, et les étapes d'implémentation.

En suivant ce plan, nous pourrons finaliser les interfaces administrateur et utilisateur et offrir une expérience complète et optimale aux utilisateurs du système. Les fonctionnalités ont été priorisées en fonction de leur importance et de leur complexité, avec un calendrier d'implémentation réaliste.
