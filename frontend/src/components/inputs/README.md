# Composants d'input spécialisés pour WhatsApp Template Variables

Ces composants ont été créés pour gérer les différents types de variables pouvant être utilisées dans les templates WhatsApp. Chaque composant est spécialisé pour un type de données spécifique et offre une validation et un formatage adaptés.

## Types de composants disponibles

1. **WhatsAppTemplateVariableDateInput** - Pour les dates (format JJ/MM/AAAA)
2. **WhatsAppTemplateVariableTimeInput** - Pour les heures (format HH:MM)
3. **WhatsAppTemplateVariableCurrencyInput** - Pour les montants avec devise (€, $, FCFA)
4. **WhatsAppTemplateVariableEmailInput** - Pour les adresses e-mail
5. **WhatsAppTemplateVariablePhoneInput** - Pour les numéros de téléphone internationaux
6. **WhatsAppTemplateVariableReferenceInput** - Pour les codes de référence
7. **WhatsAppTemplateVariableNumberInput** - Pour les valeurs numériques
8. **WhatsAppTemplateVariableTextInput** - Pour le texte standard (valeur par défaut)

## Utilisation

### Import direct
```vue
<script>
import { WhatsAppTemplateVariableDateInput } from '@/components/inputs';

export default {
  components: {
    WhatsAppTemplateVariableDateInput
  }
}
</script>
```

### Utilisation dynamique (avec `component` de Vue)
```vue
<template>
  <component
    :is="getVariableInputComponent(variableType)"
    v-model="variableValue"
    :label="getVariableLabel(variableType)"
    :placeholder="getVariablePlaceholder(variableType)"
    :rules="getVariableRules(variableType)"
    outlined
  />
</template>

<script>
import { getVariableTypeInfo } from '@/components/inputs';

export default {
  setup() {
    // ...
    
    const getVariableInputComponent = (type) => {
      return getVariableTypeInfo(type).component;
    };
    
    // ...
    
    return {
      getVariableInputComponent
      // ...
    };
  }
}
</script>
```

### Propriétés communes à tous les composants

| Propriété     | Type     | Défaut  | Description                                    |
|---------------|----------|---------|------------------------------------------------|
| `modelValue`  | String   | `''`    | Valeur liée via v-model                        |
| `label`       | String   | Varie   | Libellé du champ                               |
| `hint`        | String   | `''`    | Texte d'aide affiché sous le champ             |
| `placeholder` | String   | Varie   | Texte de placeholder                           |
| `rules`       | Array    | `[]`    | Règles de validation supplémentaires           |
| `outlined`    | Boolean  | `true`  | Style avec contour                             |
| `dense`       | Boolean  | `false` | Mode compact                                   |
| `maxlength`   | Number   | Varie   | Longueur maximale autorisée                    |
| `readonly`    | Boolean  | `false` | Mode lecture seule                             |
| `disable`     | Boolean  | `false` | Désactive le champ                             |
| `clearable`   | Boolean  | `true`  | Affiche un bouton pour effacer le contenu      |

### Propriétés spécifiques

#### WhatsAppTemplateVariableDateInput
- `format`: Format de date (défaut: 'DD/MM/YYYY')
- `minDate`: Date minimale autorisée
- `maxDate`: Date maximale autorisée

#### WhatsAppTemplateVariableTimeInput
- `use24Hours`: Utiliser le format 24h (défaut: true)

#### WhatsAppTemplateVariableCurrencyInput
- `defaultCurrency`: Devise par défaut (défaut: 'EUR')

#### WhatsAppTemplateVariablePhoneInput
- `defaultCountryCode`: Code pays par défaut (défaut: '+225')

#### WhatsAppTemplateVariableReferenceInput
- `uppercase`: Convertir en majuscules (défaut: true)
- `prefix`: Préfixe à ajouter automatiquement
- `allowedPattern`: Expression régulière pour les caractères autorisés

#### WhatsAppTemplateVariableNumberInput
- `allowDecimals`: Autoriser les nombres décimaux (défaut: false)
- `minValue`: Valeur minimale autorisée
- `maxValue`: Valeur maximale autorisée
- `useThousandsSeparator`: Utiliser le séparateur de milliers (défaut: true)

#### WhatsAppTemplateVariableTextInput
- `multiline`: Champ multiligne (défaut: false)
- `autogrow`: Agrandissement automatique (défaut: true)
- `minLength`: Longueur minimale requise
- `disallowedChars`: Tableau des caractères non autorisés

## Utilitaires

Le fichier `index.ts` exporte également plusieurs utilitaires :

- `WhatsAppTemplateVariableInputMapping`: Mapping des types vers les composants
- `WhatsAppTemplateVariableLimits`: Limites de caractères par type
- `WhatsAppTemplateVariableLabels`: Labels par défaut par type
- `WhatsAppTemplateVariablePlaceholders`: Placeholders par défaut par type
- `getVariableTypeInfo(type)`: Fonction qui retourne les informations d'un type

## Plugin Vue

Ces composants peuvent être enregistrés globalement via le plugin :

```js
// main.ts
import WhatsAppTemplateVariableInputs from '@/components/inputs';

createApp(App)
  .use(WhatsAppTemplateVariableInputs)
  .mount('#app');
```