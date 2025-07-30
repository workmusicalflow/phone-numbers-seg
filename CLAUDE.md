# CLAUDE.md - Instructions pour l'Assistant Claude

## 🚨 RÈGLES IMPORTANTES

### Vérification des serveurs AVANT lancement
**TOUJOURS vérifier si les ports sont occupés avant de lancer des serveurs détachés:**
```bash
lsof -i :5173 && lsof -i :8000
```

### Configuration serveurs
- **Frontend** : `npm run dev` sur port 5173
- **Backend** : `php -S localhost:8000 -t public` sur port 8000
- **Utilisateur de test** : admin / admin123

### Commandes de développement
```bash
# Démarrer frontend
cd /Users/ns2poportable/Desktop/phone-numbers-seg && npm run dev

# Démarrer backend  
cd /Users/ns2poportable/Desktop/phone-numbers-seg && php -S localhost:8000 -t public

# Tests GraphQL
php test_graphql_simple.php
php test_simple_resolver.php
```

## ✅ Résolutions récentes

### ✅ RÉSOLU : Contact Group Counter (30/07/2025)
- **Problème** : Les compteurs de contacts ne s'affichaient pas dans l'interface contact-groups
- **Cause identifiée** : Perte de session PHP entre login et requête GraphQL ("Authentication required")
- **Solution** : Configuration CORS renforcée
- **Résultat** : ✅ Tous les compteurs s'affichent correctement (3 contacts par groupe)

**Détails techniques :**
- **Architecture active** : Manual GraphQL (public/graphql.php), pas GraphQLite
- **Frontend endpoint** : `/graphql.php` (ligne 25 main.ts)
- **Backend** : ContactGroupResolver.php avec `$this->membershipRepository->countByGroupId()`
- **Fix appliqué** : Ajout de `credentials: 'include'` dans Apollo Client createHttpLink()

### Configuration CORS finale (robuste)
**Backend (public/graphql.php) :**
```php
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
```

**Frontend (main.ts) :**
```javascript
const httpLink = createHttpLink({
  uri: "/graphql.php",
  credentials: 'include', // Assure l'envoi des cookies cross-origin
});
```

## 📁 Structure GraphQL du projet
```
src/GraphQL/
├── GraphQLiteConfiguration.php    # GraphQLite setup
├── Types/ContactGroupType.php      # Returns hardcoded 0
├── Resolvers/ContactGroupResolver.php # Working implementation
└── schema.graphql                  # Manual schema

public/graphql.php                  # Manual GraphQL endpoint
```

## 🛠 Outils de débogage disponibles
- MCP terminal-observer : Lancer des processus détachés
- MCP sqlite-admin : Analyser base de données
- MCP browser-automation : Tests E2E
- MCP graphql-master : Tests GraphQL
- MCP gemini-copilot : Consultation expert