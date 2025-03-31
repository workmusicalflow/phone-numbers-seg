# Standards et Conventions pour le Développement Vue.js

Ce document définit les standards et conventions à suivre pour le développement des composants et interfaces Vue.js dans l'application de segmentation de numéros de téléphone.

## Structure du Projet

### Organisation des Fichiers

```
frontend/
├── src/
│   ├── assets/           # Ressources statiques (images, styles globaux)
│   ├── components/       # Composants réutilisables
│   │   ├── base/         # Composants de base (boutons, inputs, etc.)
│   │   └── ui/           # Composants d'interface plus complexes
│   ├── router/           # Configuration des routes
│   ├── stores/           # Stores Pinia pour la gestion d'état
│   ├── views/            # Composants de page/vue
│   ├── services/         # Services pour les appels API
│   ├── utils/            # Fonctions utilitaires
│   ├── App.vue           # Composant racine
│   └── main.ts           # Point d'entrée de l'application
├── public/               # Fichiers statiques accessibles publiquement
└── tests/                # Tests unitaires et d'intégration
```

### Conventions de Nommage

- **Fichiers de composants** : PascalCase (ex: `PhoneNumberCard.vue`)
- **Fichiers de services** : camelCase (ex: `phoneService.ts`)
- **Fichiers de stores** : camelCase avec suffixe "Store" (ex: `phoneStore.ts`)
- **Fichiers de vues** : PascalCase (ex: `Home.vue`, `Segment.vue`)
- **Fichiers utilitaires** : camelCase (ex: `formatters.ts`)

## Composants Vue

### Structure des Composants

Chaque composant doit suivre cette structure :

```vue
<template>
  <!-- Template HTML -->
</template>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({
  name: "ComponentName",
  // Options du composant
});
</script>

<script setup lang="ts">
// Code du composant avec Composition API
</script>

<style scoped lang="scss">
/* Styles spécifiques au composant */
</style>
```

### Composition API

Nous utilisons la Composition API avec `<script setup>` pour tous les nouveaux composants :

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { usePhoneStore } from "@/stores/phoneStore";

// Déclaration des props
const props = defineProps<{
  phoneNumber: string;
  showDetails: boolean;
}>();

// Émission d'événements
const emit = defineEmits<{
  (e: "update", id: number): void;
  (e: "delete", id: number): void;
}>();

// État local
const isLoading = ref(false);

// Méthodes
const handleClick = () => {
  emit("update", props.id);
};

// Hooks de cycle de vie
onMounted(() => {
  // Code à exécuter au montage du composant
});
</script>
```

### Props

- Toujours définir le type des props avec TypeScript
- Utiliser des noms descriptifs en camelCase
- Documenter les props avec des commentaires JSDoc

```ts
const props = defineProps<{
  /** Le numéro de téléphone à afficher */
  phoneNumber: string;
  /** Indique si les détails doivent être affichés */
  showDetails?: boolean; // Le ? indique que la prop est optionnelle
}>();
```

### Événements

- Utiliser des noms descriptifs en kebab-case pour les événements
- Documenter les événements avec des commentaires JSDoc

```ts
const emit = defineEmits<{
  /** Émis lorsque l'utilisateur clique sur le bouton de mise à jour */
  (e: "update", id: number): void;
  /** Émis lorsque l'utilisateur clique sur le bouton de suppression */
  (e: "delete", id: number): void;
}>();
```

## Gestion d'État avec Pinia

### Structure des Stores

```ts
// phoneStore.ts
import { defineStore } from "pinia";
import { PhoneNumber } from "@/types";
import { phoneService } from "@/services/phoneService";

export const usePhoneStore = defineStore("phone", {
  state: () => ({
    phones: [] as PhoneNumber[],
    isLoading: false,
    error: null as string | null,
  }),

  getters: {
    getPhoneById: (state) => (id: number) => {
      return state.phones.find((phone) => phone.id === id);
    },
    // Autres getters...
  },

  actions: {
    async fetchPhones() {
      this.isLoading = true;
      this.error = null;
      try {
        this.phones = await phoneService.getAll();
      } catch (error) {
        this.error = error.message;
      } finally {
        this.isLoading = false;
      }
    },
    // Autres actions...
  },
});
```

### Utilisation des Stores

```vue
<script setup lang="ts">
import { usePhoneStore } from "@/stores/phoneStore";
import { storeToRefs } from "pinia";

// Extraction réactive des propriétés du store
const phoneStore = usePhoneStore();
const { phones, isLoading, error } = storeToRefs(phoneStore);

// Appel des actions du store
const loadPhones = async () => {
  await phoneStore.fetchPhones();
};
</script>
```

## Intégration GraphQL avec Apollo Client

### Structure des Requêtes

Les requêtes GraphQL doivent être définies dans des fichiers séparés :

```ts
// queries/phoneQueries.ts
import gql from "graphql-tag";

export const GET_PHONES = gql`
  query GetPhones($limit: Int, $offset: Int, $search: String) {
    phones(limit: $limit, offset: $offset, search: $search) {
      id
      number
      civility
      firstName
      name
      company
      sector
      segments {
        id
        type
        value
      }
    }
  }
`;
```

### Utilisation d'Apollo dans les Composants

```vue
<script setup lang="ts">
import { useQuery, useMutation } from "@vue/apollo-composable";
import { GET_PHONES } from "@/queries/phoneQueries";
import { ref, watch } from "vue";

// Paramètres de requête
const searchText = ref("");
const limit = ref(10);
const offset = ref(0);

// Exécution de la requête
const { result, loading, error, refetch } = useQuery(GET_PHONES, {
  limit,
  offset,
  search: searchText,
});

// Réaction aux changements
watch(searchText, () => {
  // Réinitialiser l'offset et refaire la requête
  offset.value = 0;
  refetch();
});
</script>
```

## Styles et UI

### Utilisation de Quasar

Nous utilisons Quasar Framework pour les composants UI :

```vue
<template>
  <q-page class="q-pa-md">
    <q-card>
      <q-card-section>
        <q-input v-model="searchText" label="Rechercher" />
      </q-card-section>

      <q-card-section>
        <q-table
          :rows="phones"
          :columns="columns"
          :loading="loading"
          row-key="id"
        />
      </q-card-section>
    </q-card>
  </q-page>
</template>
```

### Variables Sass

Les variables Sass globales sont définies dans `src/quasar-variables.sass` :

```sass
$primary   : #1976D2
$secondary : #26A69A
$accent    : #9C27B0

$dark      : #1D1D1D
$dark-page : #121212

$positive  : #21BA45
$negative  : #C10015
$info      : #31CCEC
$warning   : #F2C037
```

### Styles Spécifiques aux Composants

```vue
<style scoped lang="scss">
.phone-card {
  border-radius: 8px;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  }

  .phone-number {
    font-weight: bold;
    color: $primary; // Utilisation des variables Sass de Quasar
  }
}
</style>
```

## Tests

### Tests Unitaires avec Vitest

```ts
// tests/components/PhoneNumberCard.spec.ts
import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import PhoneNumberCard from "@/components/PhoneNumberCard.vue";
import { createTestingPinia } from "@pinia/testing";

describe("PhoneNumberCard", () => {
  it("renders properly", () => {
    const wrapper = mount(PhoneNumberCard, {
      props: {
        phoneNumber: "+2250777104936",
        showDetails: true,
      },
      global: {
        plugins: [
          createTestingPinia({
            createSpy: vi.fn,
          }),
        ],
        // Stubs pour les composants Quasar
        stubs: {
          QCard: true,
          QCardSection: true,
        },
      },
    });

    expect(wrapper.text()).toContain("+2250777104936");
  });

  it("emits update event when update button is clicked", async () => {
    // Test d'émission d'événement
  });
});
```

### Mocks pour les Composants Quasar

Pour les tests, nous utilisons des stubs pour les composants Quasar :

```ts
// tests/setup.ts
import { config } from "@vue/test-utils";

// Stubs globaux pour les composants Quasar
config.global.stubs = {
  QBtn: {
    template: "<button><slot /></button>",
    props: ["color", "icon", "flat", "round", "dense"],
  },
  QInput: {
    template: '<input v-model="inputValue" />',
    props: ["modelValue"],
    computed: {
      inputValue: {
        get() {
          return this.modelValue;
        },
        set(value) {
          this.$emit("update:modelValue", value);
        },
      },
    },
  },
  // Autres stubs...
};
```

## Bonnes Pratiques

### Performance

- Utiliser `v-memo` pour les listes avec beaucoup d'éléments
- Implémenter le lazy loading des composants avec `defineAsyncComponent`
- Utiliser `shallowRef` pour les objets volumineux qui ne nécessitent pas de réactivité profonde

```ts
// Lazy loading d'un composant
import { defineAsyncComponent } from "vue";

const HeavyComponent = defineAsyncComponent(
  () => import("@/components/HeavyComponent.vue")
);
```

### Accessibilité

- Utiliser des attributs ARIA appropriés
- Assurer un contraste suffisant pour le texte
- Supporter la navigation au clavier
- Tester avec des lecteurs d'écran

```vue
<template>
  <button
    aria-label="Supprimer le numéro"
    @click="handleDelete"
    @keyup.enter="handleDelete"
  >
    <q-icon name="delete" />
  </button>
</template>
```

### Internationalisation

Bien que non implémentée actuellement, nous prévoyons d'utiliser vue-i18n pour l'internationalisation :

```vue
<template>
  <div>{{ $t("phone.details") }}</div>
</template>

<script setup>
import { useI18n } from "vue-i18n";
const { t } = useI18n();
</script>
```

## Processus de Développement

### Création d'un Nouveau Composant

1. Créer le fichier du composant dans le dossier approprié
2. Implémenter le composant en suivant les conventions
3. Créer des tests unitaires pour le composant
4. Documenter le composant avec des commentaires JSDoc
5. Intégrer le composant dans l'application

### Modification d'un Composant Existant

1. Comprendre le fonctionnement actuel du composant
2. Mettre à jour les tests unitaires pour refléter les changements prévus
3. Implémenter les modifications
4. Vérifier que tous les tests passent
5. Mettre à jour la documentation si nécessaire

## Ressources Utiles

- [Documentation Vue.js](https://vuejs.org/)
- [Documentation Quasar](https://quasar.dev/)
- [Documentation Pinia](https://pinia.vuejs.org/)
- [Documentation Apollo Vue](https://v4.apollo.vuejs.org/)
- [Documentation TypeScript](https://www.typescriptlang.org/docs/)
