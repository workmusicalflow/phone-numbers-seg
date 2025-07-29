# Spécification : Affectation Automatique aux Groupes lors de l'Import CSV

## 1. Objectif et Contexte

### 1.1 Objectif

Permettre l'affectation automatique de tous les contacts importés via CSV à un ou plusieurs groupes de contacts existants.

### 1.2 Contexte Technique

- **Architecture existante** : PHP 8.3 + Doctrine ORM + Vue.js 3 + GraphQL
- **Service d'import** : `CSVImportService` fonctionnel avec infrastructure robuste
- **Groupes de contacts** : Entités `ContactGroup` et `ContactGroupMembership` déjà implémentées
- **API disponible** : `ContactGroupRepository::addContactToGroup()` opérationnelle

---

## 2. Exigences Fonctionnelles

### 2.1 Sélection des Groupes

- Interface d'import CSV doit proposer un sélecteur multi-groupes optionnel
- Seuls les groupes appartenant à l'utilisateur courant peuvent être sélectionnés
- Support de la sélection multiple pour classification complexe

### 2.2 Validation et Sécurité

- **Validation des groupes** : Vérification de l'existence et des permissions avant import
- **Validation d'échec** : Import stoppé si groupes invalides ou inaccessibles
- **Permissions strictes** : Contrôle `group.userId = currentUser.id`

### 2.3 Stratégie d'Import "Best Effort"

- **Continuation sur erreur** : Si l'affectation d'un contact à un groupe échoue, continuer l'import
- **Reporting détaillé** : Statistiques précises sur succès/échecs d'affectation
- **Idempotence** : Affectation d'un contact déjà membre ignore silencieusement

### 2.4 Reporting

Le rapport d'import doit inclure :

- Nombre de contacts affectés aux groupes avec succès
- Nombre d'échecs d'affectation (avec détails)
- Messages d'erreur explicites pour debugging

---

## 3. Exigences Techniques

### 3.1 Backend (PHP)

#### Extension du CSVImportService

```php
// Nouvelles options
'targetGroupIds' => [], // IDs des groupes cibles
'userId' => null, // ID utilisateur pour validation

// Nouvelles statistiques
'groupAssignmentsCreated' => 0,
'groupAssignmentsErrors' => 0
```

#### Logique d'Implémentation Simplifiée

```php
private function processBatch(array $batch, bool $segment = true): void
{
    // Chargement simple des groupes cibles UNE fois
    $targetGroups = [];
    if (!empty($this->options['targetGroupIds'])) {
        $targetGroups = $this->contactGroupRepository->findByIds(
            $this->options['targetGroupIds'], 
            $this->options['userId']
        );
        
        // Validation rapide
        if (count($targetGroups) !== count($this->options['targetGroupIds'])) {
            throw new InvalidArgumentException("Certains groupes sont invalides ou inaccessibles");
        }
    }
    
    foreach ($batch as $item) {
        try {
            // ... traitement contact existant ...
            
            // Affectation simple aux groupes
            if ($contact && !empty($targetGroups)) {
                foreach ($targetGroups as $group) {
                    $this->contactGroupRepository->addContactToGroup(
                        $contact->getId(), 
                        $group->getId()
                    );
                    $this->stats['groupAssignmentsCreated']++;
                }
            }
        } catch (\Exception $e) {
            // Log l'erreur, continue avec le contact suivant (best effort)
            $this->stats['groupAssignmentsErrors']++;
            $this->logger->error("Erreur contact", ['error' => $e->getMessage()]);
        }
    }
    
    // Flush par batch (tous les 50-100 contacts) pour performance
    if (($i % 50) === 0) {
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
```

#### Injection de Dépendance

Ajouter `ContactGroupRepositoryInterface` au constructeur du `CSVImportService`.

### 3.2 Frontend (Vue.js)

#### Interface d'Import

- Ajouter composant de sélection multi-groupes dans le formulaire d'import CSV
- Chargement des groupes utilisateur via `contactGroupStore.fetchUserGroups()`
- Transmission des `targetGroupIds` dans l'appel GraphQL

#### Extension API GraphQL

```typescript
// Mutation étendue
importPhoneNumbers(
  numbers: string[],
  skipInvalid: boolean = true,
  segmentImmediately: boolean = true,
  targetGroupIds?: number[] // NOUVEAU paramètre optionnel
): ImportResult
```

### 3.3 Gestion des Erreurs

#### Erreur Initiale (Groupes Invalides)

- **Comportement** : Import ne démarre pas
- **Message** : "Groupes non trouvés ou accès refusé : [IDs]"

#### Erreur en Cours d'Import (Groupe Supprimé)

- **Comportement** : Continuer import sans ce groupe
- **Reporting** : Erreur enregistrée et comptabilisée
- **Message utilisateur** : Rapport détaillé des affectations partielles

---

## 4. Tests Essentiels

### 4.1 Tests Unitaires Backend

1. **Test d'import avec groupe unique**
   - Vérifier affectation correcte en base de données
   - Vérifier statistiques `groupAssignmentsCreated`

2. **Test de validation groupes invalides**
   - Vérifier exception levée pour groupes inexistants
   - Vérifier que l'import n'a pas lieu

3. **Test "best effort"**
   - Simuler erreur d'affectation pour un contact
   - Vérifier que l'import continue pour les autres
   - Vérifier compteur `groupAssignmentsErrors`

### 4.2 Tests d'Intégration

**Test End-to-End** : Simuler import CSV complet via interface utilisateur et vérifier affectation en base de données.

---

## 5. Critères d'Acceptation

- [ ] **Fonctionnel** : Import CSV avec affectation groupes opérationnel
- [ ] **Performance** : Pas de régression sur imports existants sans groupes
- [ ] **Sécurité** : Validation permissions stricte respectée
- [ ] **UX** : Interface intuitive avec feedback approprié
- [ ] **Résilience** : Gestion "best effort" des erreurs d'affectation
- [ ] **Tests** : Couverture des scénarios critiques

---

## 6. Estimation

**Temps de développement** : 7-10 jours pour un développeur expérimenté

| Composant | Estimation |
|-----------|------------|
| Backend (CSVImportService + API) | 2.5-3 jours |
| Frontend (Interface + Store) | 2-2.5 jours |
| Tests (Unitaires + E2E) | 1.5-2.5 jours |
| Buffer et intégration | 1-2 jours |

**Approche** : Implémentation simple d'abord, optimisations si nécessaires après tests de performance.