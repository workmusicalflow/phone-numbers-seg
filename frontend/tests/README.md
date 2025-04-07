# Tests pour le Frontend Vue.js

Ce dossier contient les tests unitaires et d'intégration pour le frontend Vue.js de l'application de segmentation de numéros de téléphone.

## Structure des tests

```
tests/
├── components/           # Tests des composants Vue
│   ├── PhoneNumberCard.spec.ts
│   ├── SearchBar.spec.ts
│   └── NotificationService.spec.ts
├── stores/               # Tests des stores Pinia
│   └── phoneStore.spec.ts
└── README.md             # Ce fichier
```

## Exécution des tests

### Tests unitaires

Pour exécuter tous les tests unitaires :

```bash
npm test
```

Pour exécuter les tests avec un rapport de couverture :

```bash
npm run test:coverage
```

Pour exécuter un fichier de test spécifique :

```bash
npm test -- tests/components/PhoneNumberCard.spec.ts
```

### Tests d'intégration

Pour tester l'intégration avec le backend GraphQL :

```bash
npm run test:integration
```

> **Note :** Assurez-vous que le backend PHP est en cours d'exécution et accessible à l'URL configurée dans le script de test d'intégration.

## Ajout de nouveaux tests

### Tests de composants

Les tests de composants utilisent Vue Test Utils et Vitest. Voici un exemple de structure pour un test de composant :

```typescript
import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import MonComposant from "../../src/components/MonComposant.vue";

describe("MonComposant", () => {
  it("se rend correctement", () => {
    const wrapper = mount(MonComposant);
    expect(wrapper.exists()).toBe(true);
  });

  it("réagit aux props", () => {
    const wrapper = mount(MonComposant, {
      props: {
        maProp: "valeur",
      },
    });
    expect(wrapper.text()).toContain("valeur");
  });
});
```

### Tests de stores

Les tests de stores utilisent Pinia et Vitest. Voici un exemple de structure pour un test de store :

```typescript
import { describe, it, expect, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useMonStore } from "../../src/stores/monStore";

describe("monStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("initialise avec l'état par défaut", () => {
    const store = useMonStore();
    expect(store.maValeur).toBe("valeur par défaut");
  });

  it("met à jour l'état", () => {
    const store = useMonStore();
    store.mettreAJour("nouvelle valeur");
    expect(store.maValeur).toBe("nouvelle valeur");
  });
});
```

## Mocking

### Mocking des dépendances externes

Pour les tests qui dépendent de services externes (comme Apollo Client pour GraphQL), utilisez Vitest pour mocker ces dépendances :

```typescript
import { vi } from "vitest";

// Mock Apollo Client
vi.mock("@vue/apollo-composable", () => ({
  useApolloClient: () => ({
    client: {
      query: vi.fn(),
      mutate: vi.fn(),
    },
  }),
}));
```

### Mocking des composants Quasar

Pour les tests de composants qui utilisent des composants Quasar, vous pouvez les stubber :

```typescript
const wrapper = mount(MonComposant, {
  global: {
    stubs: {
      QBtn: true,
      QInput: true,
      QCard: true,
    },
  },
});
```

## Bonnes pratiques

1. **Isolez les tests** : Chaque test doit être indépendant des autres.
2. **Testez les comportements, pas l'implémentation** : Concentrez-vous sur ce que le composant ou le store doit faire, pas sur comment il le fait.
3. **Utilisez des mocks pour les dépendances externes** : Ne dépendez pas de services externes dans les tests unitaires.
4. **Gardez les tests simples et lisibles** : Un test doit tester une seule chose à la fois.
5. **Utilisez des assertions claires** : Les assertions doivent être explicites et faciles à comprendre.
