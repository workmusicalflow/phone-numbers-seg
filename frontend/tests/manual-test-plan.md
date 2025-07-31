# Plan de Tests Manuels - Opérations Essentielles

## Prérequis
- Environnement de test avec base de données propre
- Accès aux comptes Admin (oraclesms2025-0) et AfricaQSHE (Qualitas@2024)
- Navigateur web moderne (Chrome, Firefox, ou Safari)

## Instructions d'Installation de Playwright

Pour exécuter les tests automatisés E2E, suivez ces étapes:

```bash
# Naviguer vers le répertoire frontend
cd frontend

# Installer Playwright
npm install --save-dev @playwright/test

# Installer les navigateurs nécessaires
npx playwright install --with-deps

# Exécuter les tests
npx playwright test
```

## Scénarios de Test Manuel

### 1. Vider l'Historique SMS

#### Pour l'utilisateur Admin
1. Se connecter avec le compte Admin (oraclesms2025-0)
2. Naviguer vers la page d'historique SMS
3. Vérifier qu'il y a des entrées dans l'historique
   - Si aucune entrée n'existe, envoyer un SMS de test
4. Cliquer sur le bouton "Vider l'historique"
5. Confirmer l'action dans la boîte de dialogue
6. **Résultat attendu**: L'historique est vide, un message indique qu'aucun SMS n'a été envoyé

#### Pour l'utilisateur AfricaQSHE
1. Se connecter avec le compte AfricaQSHE (Qualitas@2024)
2. Répéter les étapes 2-6 ci-dessus

### 2. Gestion des Contacts

#### Pour l'utilisateur Admin
1. Se connecter avec le compte Admin
2. Naviguer vers la page des contacts
3. Ajouter un nouveau contact:
   - Cliquer sur "Ajouter un contact"
   - Remplir le formulaire avec:
     - Nom: "Contact Test Manuel"
     - Téléphone: "0777104936"
     - Email: "test.manuel@example.com"
     - Notes: "Contact créé pour test manuel"
   - Enregistrer
   - **Résultat attendu**: Le contact apparaît dans la liste
4. Modifier le contact:
   - Cliquer sur l'icône de modification
   - Changer le nom en "Contact Test Manuel Modifié"
   - Enregistrer
   - **Résultat attendu**: Le contact apparaît avec le nouveau nom
5. Supprimer le contact:
   - Cliquer sur l'icône de suppression
   - Confirmer la suppression
   - **Résultat attendu**: Le contact n'apparaît plus dans la liste

#### Pour l'utilisateur AfricaQSHE
1. Se connecter avec le compte AfricaQSHE
2. Répéter les étapes 2-5 ci-dessus

### 3. Gestion des Groupes

#### Pour l'utilisateur Admin
1. Se connecter avec le compte Admin
2. Naviguer vers la page des groupes de contacts
3. Créer un nouveau groupe:
   - Cliquer sur "Créer un groupe"
   - Remplir le formulaire avec:
     - Nom: "Groupe Test Manuel"
     - Description: "Groupe créé pour test manuel"
   - Enregistrer
   - **Résultat attendu**: Le groupe apparaît dans la liste
4. Modifier le groupe:
   - Cliquer sur l'icône de modification
   - Changer le nom en "Groupe Test Manuel Modifié"
   - Enregistrer
   - **Résultat attendu**: Le groupe apparaît avec le nouveau nom
5. Supprimer le groupe:
   - Cliquer sur l'icône de suppression
   - Confirmer
   - **Résultat attendu**: Le groupe n'apparaît plus dans la liste

#### Pour l'utilisateur AfricaQSHE
1. Se connecter avec le compte AfricaQSHE
2. Répéter les étapes 2-5 ci-dessus

### 4. Gestion des Contacts dans les Groupes

#### Pour l'utilisateur Admin
1. Se connecter avec le compte Admin
2. Créer un contact de test:
   - Naviguer vers la page des contacts
   - Ajouter un contact nommé "Contact pour Groupe Test"
3. Créer un groupe de test:
   - Naviguer vers la page des groupes
   - Créer un groupe nommé "Groupe pour Test Manuel"
4. Ajouter le contact au groupe:
   - Ouvrir le groupe créé
   - Cliquer sur "Ajouter des contacts"
   - Sélectionner le contact créé
   - Confirmer
   - **Résultat attendu**: Le contact apparaît dans la liste des membres du groupe
5. Supprimer le contact du groupe:
   - Cliquer sur l'icône de suppression à côté du contact
   - Confirmer
   - **Résultat attendu**: Le contact n'apparaît plus dans la liste des membres
6. Nettoyer:
   - Supprimer le groupe
   - Supprimer le contact

#### Pour l'utilisateur AfricaQSHE
1. Se connecter avec le compte AfricaQSHE
2. Répéter les étapes 2-6 ci-dessus

## Tableau de Résultats

| Test                                  | Admin | AfricaQSHE | Notes                                |
|---------------------------------------|-------|------------|--------------------------------------|
| Vider l'historique SMS                | □     | □          |                                      |
| Ajouter un contact                    | □     | □          |                                      |
| Modifier un contact                   | □     | □          |                                      |
| Supprimer un contact                  | □     | □          |                                      |
| Créer un groupe                       | □     | □          |                                      |
| Modifier un groupe                    | □     | □          |                                      |
| Supprimer un groupe                   | □     | □          |                                      |
| Ajouter un contact à un groupe        | □     | □          |                                      |
| Supprimer un contact d'un groupe      | □     | □          |                                      |

## Problèmes Identifiés

| Problème                              | Sévérité | Étapes pour reproduire                  |
|---------------------------------------|----------|----------------------------------------|
|                                       |          |                                        |
|                                       |          |                                        |

## Notes Supplémentaires

- Date d'exécution des tests: _______________
- Testeur: _______________
- Version de l'application: _______________
