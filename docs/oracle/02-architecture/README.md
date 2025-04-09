# Architecture

Cette section présente l'architecture du projet Oracle, décrivant comment le système est structuré et comment ses différents composants interagissent.

## Vue d'ensemble

L'architecture du projet Oracle est conçue pour être modulaire, évolutive et maintenable. Elle suit les principes de conception modernes et utilise des patterns éprouvés pour résoudre les problèmes courants en développement logiciel.

## Contenu de cette section

Cette section contient les documents suivants :

1. [Architecture en couches](01-layered-architecture.md) - Description détaillée de l'architecture en couches du projet, expliquant les responsabilités de chaque couche et leurs interactions.

2. [Patterns de conception](02-design-patterns.md) - Présentation des principaux patterns de conception utilisés dans le projet, avec des exemples concrets d'implémentation.

3. [Diagrammes d'architecture](03-diagrams.md) - Collection de diagrammes visuels illustrant différents aspects de l'architecture du système.

4. [Flux de données](04-data-flow.md) - Description des principaux flux de données dans l'application, depuis leur saisie par l'utilisateur jusqu'à leur stockage et leur traitement.

5. [Base de données](05-database.md) - Documentation sur la structure de la base de données, le schéma, les tables, les relations et les considérations de conception.

## Principes architecturaux

L'architecture du projet Oracle est guidée par les principes suivants :

### Séparation des préoccupations

Chaque composant du système a une responsabilité unique et bien définie. Cette séparation permet de développer, tester et maintenir chaque partie du système indépendamment.

### Inversion de dépendance

Les composants de haut niveau ne dépendent pas des composants de bas niveau, mais plutôt d'abstractions. Cela permet de découpler les différentes parties du système et facilite les tests unitaires.

### Ouvert/fermé

Les composants sont ouverts à l'extension mais fermés à la modification. Cela permet d'ajouter de nouvelles fonctionnalités sans modifier le code existant.

### Substitution de Liskov

Les sous-types doivent être substituables à leurs types de base. Cela garantit que les interfaces sont cohérentes et que les composants peuvent être remplacés sans affecter le comportement du système.

### Ségrégation des interfaces

Les interfaces sont spécifiques aux clients qui les utilisent, plutôt que d'être génériques. Cela évite que les clients dépendent de méthodes qu'ils n'utilisent pas.

## Technologies utilisées

L'architecture du projet Oracle s'appuie sur les technologies suivantes :

- **PHP** pour le backend
- **Vue.js** et **Quasar** pour le frontend
- **GraphQL** pour l'API
- **SQLite/MySQL** pour la base de données
- **Composer** pour la gestion des dépendances PHP
- **npm** pour la gestion des dépendances JavaScript

## Évolution de l'architecture

L'architecture du projet Oracle est conçue pour évoluer avec les besoins du projet. Elle est suffisamment flexible pour permettre l'ajout de nouvelles fonctionnalités et l'adaptation à de nouvelles exigences.

Les décisions architecturales sont documentées et révisées régulièrement pour s'assurer qu'elles restent pertinentes et efficaces.
