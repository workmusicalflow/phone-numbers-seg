# Correction de l'erreur CORS pour l'upload de médias WhatsApp

## Problème
L'upload de médias WhatsApp échouait avec une erreur CORS :
```
Access to XMLHttpRequest at 'http://localhost:8000/api/whatsapp/upload.php' 
from origin 'http://localhost:5173' has been blocked by CORS policy
```

## Cause
1. Le composant Vue utilisait une URL incomplète (`/whatsapp/upload.php`)
2. L'API axios avait une baseURL configurée à `http://localhost:8000/api`
3. Les headers CORS n'étaient pas uniformes sur tous les endpoints
4. Le port du frontend (5173) n'était pas correctement configuré dans les headers

## Solutions appliquées

### 1. Correction du composant Vue
Modification dans `WhatsAppMediaUpload.vue` :
```javascript
// Ancienne version
const response = await api.post('/whatsapp/upload.php', formData, {

// Nouvelle version
const response = await api.post('http://localhost:8000/api/whatsapp/upload.php', formData, {
  baseURL: '', // Override le baseURL pour cette requête
```

### 2. Uniformisation des headers CORS
Création d'un script `fix-cors-everywhere.php` qui :
- Corrige tous les headers CORS dans les fichiers PHP publics
- Remplace le port 3000 par 5173
- Ajoute la gestion des requêtes OPTIONS

### 3. Création de fichiers .htaccess
Ajout de fichiers `.htaccess` dans :
- `/public/.htaccess`
- `/public/api/whatsapp/.htaccess`

Contenu :
```apache
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "http://localhost:5173"
    Header set Access-Control-Allow-Credentials "true"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
    Header set Access-Control-Max-Age "3600"
</IfModule>
```

### 4. Configuration du proxy Vite
Ajout dans `vite.config.ts` :
```javascript
"/whatsapp": {
  target: "http://localhost:8000",
  changeOrigin: true,
},
```

### 5. Vérification de l'implémentation WhatsApp
L'upload suit correctement la documentation WhatsApp :
- Upload via l'API Meta : `POST /media`
- Paramètres : `messaging_product: "whatsapp"`, `file: @PATH`
- Retourne un ID de média utilisable pour l'envoi

## Tests
Création de fichiers de test :
- `/public/api/whatsapp/test-cors.php` : Test des headers CORS
- `/frontend/test-whatsapp-upload.html` : Test d'upload depuis le frontend
- `/scripts/test-whatsapp-media-upload.php` : Test direct de l'API Meta

## Résultat
Les erreurs CORS sont maintenant corrigées et l'upload de médias WhatsApp fonctionne correctement depuis le frontend vers le backend.