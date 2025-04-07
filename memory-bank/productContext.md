# Contexte Produit - Oracle

## Problématique Adressée

Les entreprises et organisations qui gèrent de grandes quantités de numéros de téléphone font face à plusieurs défis :

1. **Manque de structure** : Les numéros de téléphone sont souvent stockés sans contexte ni métadonnées, rendant difficile leur organisation et leur utilisation efficace.

2. **Difficulté d'analyse** : Extraire des informations utiles à partir des numéros (pays, opérateur, type) est un processus manuel et chronophage.

3. **Communication inefficace** : L'envoi de SMS à des groupes spécifiques de contacts nécessite souvent un tri manuel ou des outils disparates.

4. **Traçabilité limitée** : Le suivi des communications et des opérations effectuées sur les numéros est souvent incomplet ou inexistant.

5. **Intégration complexe** : Les solutions existantes s'intègrent difficilement avec d'autres systèmes d'information.

## Solution Proposée

Oracle est une application web complète qui transforme la gestion des numéros de téléphone en un processus structuré et efficace :

1. **Segmentation intelligente** : Analyse automatique des numéros pour extraire des informations pertinentes (code pays, opérateur, type de numéro).

2. **Organisation flexible** : Création de segments personnalisés pour organiser les numéros selon différents critères métier.

3. **Communication ciblée** : Envoi de SMS à des segments spécifiques avec suivi complet des statuts d'envoi.

4. **Traçabilité complète** : Historique détaillé de toutes les opérations effectuées sur les numéros.

5. **Intégration simplifiée** : API REST et GraphQL pour une intégration facile avec d'autres systèmes.

6. **Import/Export fluide** : Fonctionnalités d'import depuis diverses sources et d'export vers différents formats.

7. **Gestion des utilisateurs** : Création et gestion des comptes utilisateurs, y compris la gestion des crédits SMS et des noms d'expéditeur.

8. **Administration centralisée** : Interface d'administration pour gérer les utilisateurs, les configurations et les paramètres du système.

## Utilisateurs Cibles

### Utilisateurs Primaires

1. **Responsables marketing** : Utilisent l'application pour segmenter leur base de contacts et envoyer des campagnes SMS ciblées.

2. **Gestionnaires de relation client** : Organisent les contacts clients et suivent les communications.

3. **Analystes de données** : Exploitent les informations extraites des numéros pour générer des insights.

### Utilisateurs Secondaires

1. **Développeurs** : Intègrent l'API Oracle dans d'autres applications.

2. **Administrateurs système** : Configurent et maintiennent l'application.

3. **Responsables conformité** : S'assurent que la gestion des données respecte les réglementations.

## Parcours Utilisateur

### Parcours 1 : Segmentation de numéros

1. L'utilisateur importe une liste de numéros depuis un fichier CSV.
2. Le système analyse automatiquement les numéros et extrait les informations pertinentes.
3. L'utilisateur visualise les résultats de la segmentation et peut les filtrer selon différents critères.
4. L'utilisateur crée des segments personnalisés basés sur des critères spécifiques.
5. L'utilisateur exporte les résultats ou les utilise pour des actions ultérieures.

### Parcours 2 : Envoi de SMS ciblés

1. L'utilisateur sélectionne un segment de numéros existant.
2. L'utilisateur compose un message SMS et peut utiliser des variables pour personnaliser le contenu.
3. Le système affiche un aperçu du message et une estimation du coût d'envoi (en terme de crédit SMS; 1 SMS = 1 crédit).
4. L'utilisateur confirme l'envoi et peut suivre en temps réel le statut des envois.
5. L'utilisateur consulte l'historique des envois et peut analyser les taux de réussite.

### Parcours 3 : Analyse et reporting

1. L'utilisateur accède au tableau de bord d'analyse.
2. Le système affiche des statistiques sur la distribution des numéros par pays, opérateur, etc.
3. L'utilisateur peut filtrer les données selon différentes dimensions.
4. L'utilisateur génère des rapports personnalisés sur les segments et les communications.
5. L'utilisateur exporte les rapports.

### Parcours 4 : Gestion des utilisateurs (Administrateur)

1. L'administrateur se connecte à l'interface d'administration.
2. L'administrateur crée un nouveau compte utilisateur avec un nom d'utilisateur et un mot de passe par défaut.
3. L'administrateur attribue un crédit SMS initial à l'utilisateur.
4. L'administrateur peut modifier le crédit SMS d'un utilisateur et définir des limites d'envoi.

### Parcours 5 : Commande de crédits SMS (Utilisateur)

1. L'utilisateur accède à la page de commande de crédits SMS.
2. L'utilisateur remplit le formulaire avec ses informations de contact.
3. L'utilisateur soumet la commande.
4. L'administrateur reçoit une notification par SMS et par email.
5. L'administrateur crédite manuellement le compte de l'utilisateur après réception du paiement.
6. L'utilisateur reçoit une notification par SMS et par email confirmant le crédit de son compte.

## Avantages Clés

### Pour les utilisateurs métier

1. **Gain de temps** : Automatisation des tâches manuelles de segmentation et d'analyse.
2. **Précision accrue** : Réduction des erreurs humaines dans la gestion des numéros.
3. **Communication efficace** : Ciblage précis des destinataires pour les campagnes SMS.
4. **Insights précieux** : Meilleure compréhension de la base de contacts.
5. **Traçabilité** : Suivi complet des opérations et des communications.
6. **Gestion simplifiée des utilisateurs** : Création et gestion des comptes utilisateurs, y compris la gestion des crédits SMS et des noms d'expéditeur.

### Pour les équipes techniques

1. **Intégration facile** : API REST et GraphQL pour s'adapter à différents environnements.
2. **Extensibilité** : Architecture modulaire permettant d'ajouter de nouvelles fonctionnalités.
3. **Maintenance simplifiée** : Code bien structuré et documenté.
4. **Performance** : Optimisé pour gérer de grands volumes de données et un nombre important d'utilisateurs simultanés (environ 100).
5. **Sécurité** : Validation stricte des entrées et gestion sécurisée des données.

## Différenciation

### Par rapport aux solutions CRM génériques

1. **Spécialisation** : Focalisé spécifiquement sur la gestion et l'analyse des numéros de téléphone.
2. **Profondeur d'analyse** : Extraction d'informations détaillées à partir des numéros.
3. **Légèreté** : Interface simple et intuitive sans fonctionnalités superflues.
4. **Flexibilité** : S'adapte à différents cas d'usage et secteurs d'activité.

### Par rapport aux outils d'envoi de SMS

1. **Segmentation avancée** : Capacités d'analyse et de segmentation bien au-delà des simples listes.
2. **Intégration** : S'intègre dans l'écosystème existant via des API modernes.
3. **Traçabilité** : Historique complet des communications et des opérations.
4. **Analyse** : Fonctionnalités d'analyse et de reporting intégrées.

## Évolution du Produit

### Phase 1 (Actuelle)

- Segmentation de base des numéros
- Import/export de données
- Envoi de SMS individuels et en masse
- API REST et GraphQL
- Interface utilisateur Vue.js

### Phase 2 (Planifiée)

- Migration vers MySQL pour une meilleure performance et scalabilité
- Gestion des utilisateurs (création, gestion des crédits SMS, noms d'expéditeur)
- Interface d'administration
- Système de notification (SMS et email)
- Segmentation avancée avec règles personnalisables
- Modèles de messages SMS
- Planification des envois
- Tableaux de bord d'analyse améliorés
- Application mobile pour la gestion en déplacement

### Phase 3 (Future)

- Intégration avec d'autres canaux de communication (email, WhatsApp)

## Retours Utilisateurs

Les premiers retours utilisateurs ont mis en évidence plusieurs points forts et axes d'amélioration :

### Points forts

1. **Facilité d'utilisation** : Interface intuitive qui ne nécessite pas de formation approfondie.
2. **Précision de la segmentation** : Identification correcte des opérateurs et des types de numéros.
3. **Rapidité de traitement** : Performance satisfaisante même avec de grands volumes de données.
4. **Flexibilité de l'API** : Intégration facile avec les systèmes existants.

### Axes d'amélioration

1. **Interface d'historique SMS** : Besoin d'une interface plus complète pour consulter et filtrer l'historique des SMS.
2. **Performance sur mobile** : Optimisation nécessaire pour les utilisateurs sur appareils mobiles.
3. **Documentation** : Demande de guides utilisateur plus détaillés et d'exemples d'intégration.
4. **Gestion des erreurs** : Amélioration des messages d'erreur et des mécanismes de récupération.

## Indicateurs de Succès

1. **Adoption** : Nombre d'utilisateurs actifs et taux de rétention.
2. **Engagement** : Fréquence d'utilisation et nombre d'opérations effectuées.
3. **Performance** : Temps de traitement moyen par numéro et par opération.
4. **Satisfaction** : Score NPS (Net Promoter Score) et retours qualitatifs.
5. **Intégration** : Nombre d'intégrations avec d'autres systèmes via l'API.
6. **Communication** : Taux de réussite des envois SMS et engagement des destinataires.
