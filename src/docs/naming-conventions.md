# Conventions de Nommage - Projet Oracle

Ce document définit les conventions de nommage standardisées pour le projet Oracle. Ces conventions doivent être suivies pour tous les nouveaux développements et lors de la refactorisation du code existant.

## Principes Généraux

1. **Cohérence** : Utiliser le même style de nommage dans tout le code.
2. **Clarté** : Les noms doivent être descriptifs et révéler l'intention.
3. **Concision** : Les noms doivent être aussi courts que possible tout en restant clairs.
4. **Éviter les abréviations** : Sauf si elles sont très communes et bien comprises.

## Conventions par Type

### Classes et Interfaces

- **Style** : PascalCase
- **Suffixes pour les types spécifiques** :
  - Interfaces : Suffixe `Interface` (ex: `PhoneNumberValidatorInterface`)
  - Abstractions : Préfixe `Abstract` (ex: `AbstractSegmentationHandler`)
  - Exceptions : Suffixe `Exception` (ex: `ImportException`)
  - Factories : Suffixe `Factory` (ex: `SegmentationStrategyFactory`)
  - Repositories : Suffixe `Repository` (ex: `PhoneNumberRepository`)
  - Services : Suffixe `Service` (ex: `PhoneSegmentationService`)
  - Controllers : Suffixe `Controller` (ex: `PhoneNumberController`)
  - Types GraphQL : Suffixe `Type` (ex: `PhoneNumberType`)
  - Handlers : Suffixe `Handler` (ex: `CountryCodeHandler`)
  - Strategies : Suffixe `Strategy` (ex: `IvoryCoastSegmentationStrategy`)
  - Formatters : Suffixe `Formatter` (ex: `BatchResultFormatter`)
  - Validators : Suffixe `Validator` (ex: `RegexValidator`)
  - Matchers : Suffixe `Matcher` (ex: `CustomSegmentMatcher`)
  - Clients : Suffixe `Client` (ex: `OrangeAPIClient`)
  - Configurations : Suffixe `Config` (ex: `ImportConfig`)
  - Results : Suffixe `Result` (ex: `ImportResult`)

### Méthodes et Fonctions

- **Style** : camelCase
- **Préfixes pour les types spécifiques** :
  - Getters : Préfixe `get` (ex: `getPhoneNumber`)
  - Setters : Préfixe `set` (ex: `setPhoneNumber`)
  - Booléens : Préfixe `is`, `has`, `can` ou `should` (ex: `isValid`, `hasChildren`, `canProcess`)
  - Factories : Préfixe `create` (ex: `createPhoneNumber`)
  - Conversions : Préfixe `to` (ex: `toArray`, `toString`)
  - Actions : Verbe d'action (ex: `process`, `validate`, `send`, `import`)

### Variables et Propriétés

- **Style** : camelCase
- **Conventions spécifiques** :
  - Propriétés privées : Pas de préfixe spécial (ex: `phoneNumber`, `segmentType`)
  - Constantes : UPPER_SNAKE_CASE (ex: `DEFAULT_COUNTRY_CODE`, `MAX_BATCH_SIZE`)
  - Paramètres de méthode : camelCase (ex: `$phoneNumber`, `$segmentType`)
  - Variables locales : camelCase (ex: `$result`, `$isValid`)

### Namespaces

- **Style** : PascalCase
- **Structure** : Correspondant à la structure des dossiers
- **Exemple** : `App\Services\Strategies`, `App\Repositories`, `App\GraphQL\Types`

### Fichiers

- **Style** : Même que la classe principale qu'ils contiennent
- **Exemple** : `PhoneNumber.php`, `SegmentRepository.php`, `CustomSegmentType.php`

### Base de Données

- **Tables** : snake_case au pluriel (ex: `phone_numbers`, `custom_segments`)
- **Colonnes** : snake_case (ex: `phone_number_id`, `created_at`)
- **Clés primaires** : `id` (par convention)
- **Clés étrangères** : Nom de la table référencée au singulier suivi de `_id` (ex: `phone_number_id`)
- **Tables de jointure** : Noms des deux tables au singulier séparés par un underscore (ex: `phone_number_segment`)

### Vue.js

- **Composants** : PascalCase (ex: `PhoneNumberCard.vue`, `CustomSegmentForm.vue`)
- **Props** : camelCase (ex: `phoneNumber`, `segmentType`)
- **Méthodes** : camelCase (ex: `handleSubmit`, `validateForm`)
- **Computed Properties** : camelCase (ex: `formattedPhoneNumber`, `isValid`)
- **Stores Pinia** : camelCase avec suffixe "Store" (ex: `phoneStore`, `segmentStore`)
- **Fichiers CSS/SASS** : kebab-case (ex: `global.css`, `quasar-variables.sass`)

### GraphQL

- **Types** : PascalCase (ex: `PhoneNumber`, `CustomSegment`)
- **Champs** : camelCase (ex: `phoneNumber`, `segmentType`)
- **Requêtes** : camelCase (ex: `getPhoneNumber`, `searchPhoneNumbers`)
- **Mutations** : camelCase (ex: `createPhoneNumber`, `updateCustomSegment`)

## Exemples Concrets

### Classe PHP

```php
class PhoneSegmentationService implements PhoneSegmentationServiceInterface
{
    private PhoneNumberValidatorInterface $phoneNumberValidator;
    private SegmentationStrategyFactory $strategyFactory;

    public function __construct(
        PhoneNumberValidatorInterface $phoneNumberValidator,
        SegmentationStrategyFactory $strategyFactory
    ) {
        $this->phoneNumberValidator = $phoneNumberValidator;
        $this->strategyFactory = $strategyFactory;
    }

    public function segmentPhoneNumber(string $phoneNumber): array
    {
        if (!$this->phoneNumberValidator->isValid($phoneNumber)) {
            throw new InvalidPhoneNumberException("Invalid phone number: $phoneNumber");
        }

        $strategy = $this->strategyFactory->createForPhoneNumber($phoneNumber);
        return $strategy->segment($phoneNumber);
    }
}
```

### Composant Vue.js

```vue
<template>
  <div class="phone-number-card">
    <h3>{{ formattedPhoneNumber }}</h3>
    <p v-if="hasContactInfo">{{ contactInfo }}</p>
    <segment-list :segments="phoneNumber.segments" />
    <button @click="handleEdit">Edit</button>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import SegmentList from "./SegmentList.vue";
import type { PhoneNumber } from "../types";

const props = defineProps<{
  phoneNumber: PhoneNumber;
}>();

const emit = defineEmits<{
  (e: "edit", id: number): void;
}>();

const formattedPhoneNumber = computed(() => {
  return formatPhoneNumber(props.phoneNumber.number);
});

const hasContactInfo = computed(() => {
  return !!props.phoneNumber.name || !!props.phoneNumber.company;
});

const contactInfo = computed(() => {
  const parts = [];
  if (props.phoneNumber.civility) parts.push(props.phoneNumber.civility);
  if (props.phoneNumber.firstName) parts.push(props.phoneNumber.firstName);
  if (props.phoneNumber.name) parts.push(props.phoneNumber.name);
  if (props.phoneNumber.company) parts.push(`(${props.phoneNumber.company})`);
  return parts.join(" ");
});

function handleEdit() {
  emit("edit", props.phoneNumber.id);
}

function formatPhoneNumber(number: string): string {
  // Logique de formatage
  return number;
}
</script>
```

### Requête GraphQL

```graphql
query GetPhoneNumber($id: ID!) {
  phoneNumber(id: $id) {
    id
    number
    civility
    firstName
    name
    company
    segments {
      id
      type
      value
    }
  }
}

mutation CreatePhoneNumber($input: PhoneNumberInput!) {
  createPhoneNumber(input: $input) {
    id
    number
  }
}
```

## Mise en Œuvre

1. **Nouveaux Développements** : Tous les nouveaux développements doivent suivre ces conventions.
2. **Refactorisation** : Lors de la modification de code existant, mettre à jour les noms pour suivre ces conventions.
3. **Outils** : Utiliser les outils de refactorisation de l'IDE pour renommer de manière cohérente.
4. **Revue de Code** : Vérifier le respect des conventions lors des revues de code.

## Exceptions

Dans certains cas, il peut être nécessaire de dévier de ces conventions pour maintenir la compatibilité avec des bibliothèques tierces ou des standards externes. Ces exceptions doivent être documentées et limitées autant que possible.

## Conclusion

Ces conventions de nommage visent à améliorer la lisibilité, la maintenabilité et la cohérence du code dans le projet Oracle. Elles doivent être suivies par tous les développeurs travaillant sur le projet.
