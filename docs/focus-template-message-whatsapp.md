Les composants personnalisables pour les template WhatsApp messages Oracle sont :type et parameters. Une ressource informative informative à ce propos :### 3.6. Envoi de Messages Basés sur des Modèles (`type: "template"`)

- Nécessite un objet `template`.
  ```json
  "template": {
      "name": "NOM_DU_MODELE_PRE_APPROUVE",
      "language": {
          "code": "CODE_LANGUE_LOCALE" // ex: "fr", "en_US". Doit correspondre à une traduction approuvée du modèle.
      },
      "components": [ // Optionnel, requis si le modèle a des variables ou des boutons dynamiques.
          // {
          //   "type": "header" | "body" | "button",
          //   // ... autres paramètres spécifiques au composant
          // }
      ]
  }
  ```
- **Composants (`components`)** :
  - **`type: "header"`** :
    - `parameters`: Tableau d'objets `parameter`. Pour les headers média, un seul paramètre de type `image`, `video`, ou `document` avec l'objet média correspondant (`id` ou `link`). Pour le texte, un paramètre de type `text`.
  - **`type: "body"`** :
    - `parameters`: Tableau d'objets `parameter` de type `text`, `currency`, ou `date_time` pour remplacer les variables `{{1}}`, `{{2}}`, etc. dans le corps du modèle.
  - **`type: "button"`** :
    - `sub_type: "quick_reply" | "url"`
    - `index`: "0", "1", "2", etc. (chaîne de caractères) correspondant au bouton dans le modèle.
    - `parameters`: Tableau d'objets `parameter`.
      - Pour `quick_reply`: `type: "payload"`, `payload: "DEVELOPER_DEFINED_PAYLOAD"`.
      - Pour `url`: `type: "text"`, `text: "PARTIE_VARIABLE_DE_L_URL"`.
- **Objets Paramètre (`parameter`)** :
  - `type: "text"`, `text: "valeur"`
  - `type: "currency"`, `currency: { "fallback_value": "...", "code": "USD", "amount_1000": 123450 }` (123.45 USD)
  - `type: "date_time"`, `date_time: { "fallback_value": "...", "day_of_week": 1, ... }`
  - `type: "image" | "video" | "document"`, `image: { "id": "..." ou "link": "..." }`

Exemple JSON d’un template valide :

```json
{
  "messaging_product": "whatsapp",
  "to": "2250777104936",
  "type": "template",
  "template": {
    "name": "qshe_invitation1",
    "language": {
      "code": "fr"
    },
    "components": [
      {
        "type": "header",
        "parameters": [
          {
            "type": "image",
            "image": {
              "link": "https://events-qualitas-ci.com/public/images/banner/QSHEf2025-1024.jpg"
            }
          }
        ]
      }
    ]
  }
}
```
