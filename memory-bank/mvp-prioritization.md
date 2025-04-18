# Priorisation des Fonctionnalités pour le MVP

## Contexte

Suite à l'audit des interfaces administrateur et utilisateur, nous avons identifié plusieurs fonctionnalités qui restent à implémenter. Cependant, pour éviter la sur-ingénierie et se concentrer sur l'essentiel pour le MVP (Minimum Viable Product), il est nécessaire de prioriser ces fonctionnalités en fonction de leur importance pour les premiers tests utilisateurs.

## Analyse du Script Cron d'Exécution des SMS Planifiés

L'examen du script `scripts/cron/execute_scheduled_sms.php` montre que la fonctionnalité de planification d'envois de SMS est bien implémentée et fonctionnelle. Ce script :

- Permet l'exécution périodique des SMS planifiés via un cron job
- Offre des options de configuration (limite, verbosité, exécution d'un SMS spécifique)
- Utilise l'injection de dépendances pour récupérer les services nécessaires
- Gère les erreurs et fournit des informations détaillées sur les résultats

Cette fonctionnalité est donc prête pour les tests utilisateurs et ne nécessite pas de développement supplémentaire pour le MVP.

## Priorisation des Fonctionnalités Restantes

### Fonctionnalités Essentielles pour le MVP

1. **adoption de Doctrine ORM**
   Doctrine ORM en mode autonome (standalone), sans dépendre du FrameworkBundle de Symfony ni de ses commandes CLI. bootstrap Doctrine autonome

2. **Remplacer les URLs codées en dur par des constantes définies**
   Dans nos conventions de développement, nous visons à centraliser les valeurs de configuration, y compris les URLs et les chemins d'accès. Dès que possible lors de l'évolution du MVP, les liens codés en dur devraient être migrés vers des constantes dédiées pour assurer la maintenabilité.

   - **Justification** : Maintenabilité accrue, réduction des erreurs, cohérence. Essentiel pour la phase de consolidation post-MVP."

3. **Création d'une branche git pour préparer le projet au déploiement**
   - Une fois les fonctionnalités essentielles implémentées, nous devrons créer une branche dédiée à la conformation des fichiers et de la structure globale pour le déploiement via Cpanel et Filezilla.

### Fonctionnalités Non-Essentielles pour le MVP

1. **Rapports et Statistiques Avancés (Interface Administrateur)**

   - **Justification** : Bien que utiles, les rapports avancés ne sont pas essentiels pour la fonctionnalité de base du système. Les statistiques de base déjà présentes dans le tableau de bord administrateur sont suffisantes pour le MVP.
   - **Alternative pour le MVP** : Utiliser les statistiques de base existantes et collecter les retours utilisateurs pour déterminer quels rapports seraient les plus utiles.

2. **Modèles de SMS (Interface Utilisateur)**

   - Cette fonctionnalité n'est pas essentielle

3. **Gestion des Rôles et Permissions Avancée (Interface Administrateur)**

   - **Justification** : Le système d'authentification actuel avec ses fonctionnalités de base est suffisant pour le MVP. Une gestion avancée des rôles peut être développée après avoir recueilli les retours des premiers utilisateurs.
   - **Alternative pour le MVP** : Maintenir le système actuel avec des rôles simples (administrateur/utilisateur).

4. **Journalisation des Activités (Interface Administrateur)**
   - **Justification** : Cette fonctionnalité est principalement utile pour le débogage et l'audit, mais n'est pas critique pour les fonctionnalités de base du système.
   - **Alternative pour le MVP** : Utiliser les logs système existants et les journaux d'erreurs pour le suivi des problèmes.

## Recommandations pour les Tests Utilisateurs

### Préparation Minimale Requise

1. **Implémenter les Modèles de SMS**

   - Développer une version minimale mais fonctionnelle de cette fonctionnalité
   - Se concentrer sur l'utilisabilité plutôt que sur des fonctionnalités avancées

2. **Améliorer la Documentation Utilisateur**

   - Créer des guides d'utilisation simples pour les fonctionnalités existantes
   - Documenter les cas d'utilisation courants et les flux de travail typiques

3. **Préparer un Environnement de Test**
   - Configurer un environnement de test avec des données réalistes
   - S'assurer que tous les composants fonctionnent correctement ensemble

### Plan de Test

1. **Phase 1 : Tests Internes**

   - Effectuer des tests fonctionnels sur toutes les fonctionnalités implémentées
   - Identifier et corriger les problèmes critiques

2. **Phase 2 : Tests Utilisateurs Limités**

   - Sélectionner un petit groupe d'utilisateurs pour les premiers tests
   - Recueillir des retours détaillés sur l'expérience utilisateur
   - Identifier les points de friction et les améliorations prioritaires

3. **Phase 3 : Itération et Amélioration**
   - Implémenter les corrections et améliorations prioritaires
   - Préparer le déploiement pour un groupe plus large d'utilisateurs

## Conclusion

Pour éviter la sur-ingénierie et se concentrer sur l'essentiel pour le MVP, il est recommandé de :

1. **Prioriser l'implémentation des Modèles de SMS** comme seule fonctionnalité restante essentielle pour le MVP
2. **Reporter le développement des fonctionnalités administrateur avancées** après les premiers retours utilisateurs
3. **Se concentrer sur la stabilité, la documentation et la préparation des tests** plutôt que sur l'ajout de nouvelles fonctionnalités

Cette approche permettra de livrer rapidement un MVP fonctionnel et de recueillir des retours précieux pour guider le développement futur du produit.
