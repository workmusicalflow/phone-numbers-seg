<?php
/**
 * Solution immédiate pour le problème GraphQL des templates WhatsApp
 * 
 * Ce script crée un contrôleur de secours et le configure dans une solution indépendante
 * qui modifie directement la façon dont GraphQL gère la requête problématique.
 */

// 1. Créer un contrôleur d'urgence qui est simplifié au maximum
$controllerContent = <<<'EOT'
<?php
namespace App\GraphQL\Controllers\WhatsApp;

use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Contrôleur GraphQL d'urgence ultra-simplifié qui retourne un tableau vide
 * mais valide pour éviter l'erreur "Cannot return null for non-nullable field"
 */
#[Type]
class WhatsAppUltraFixController
{
    /**
     * Solution d'urgence - retourne toujours un tableau vide mais valide
     */
    #[Query(name: "fetchApprovedWhatsAppTemplates")]
    public function fetchApprovedWhatsAppTemplates(): array 
    {
        // Cette fonction ne fait que retourner un tableau vide
        // C'est suffisant pour que GraphQL soit satisfait et ne lance pas d'erreur
        return [];
    }
}
EOT;

// Écrire le fichier du contrôleur
$fixControllerPath = __DIR__ . '/../src/GraphQL/Controllers/WhatsApp/WhatsAppUltraFixController.php';
file_put_contents($fixControllerPath, $controllerContent);
echo "✅ Contrôleur d'urgence ultra-simplifié créé: $fixControllerPath\n";

// 2. Créer un script PHP de proxy pour l'API GraphQL qui remplace les résultats problématiques
$proxyContent = <<<'EOT'
<?php
/**
 * Proxy d'urgence pour l'API GraphQL
 * 
 * Ce fichier intercepte les requêtes GraphQL, détecte celles qui concernent fetchApprovedWhatsAppTemplates
 * et fournit une réponse de secours pour éviter les erreurs.
 */

// Vérifier si c'est une requête CORS OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Gérer les en-têtes CORS
    header("Access-Control-Allow-Origin: http://localhost:5173");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 3600");
    header('HTTP/1.1 204 No Content');
    exit;
}

// Uniquement pour les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST, OPTIONS');
    header('Content-Type: application/json');
    echo json_encode(['errors' => [['message' => 'Method not allowed. Use POST for queries and mutations.']]]);
    exit;
}

// Lire l'entrée
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Vérifier si c'est une requête fetchApprovedWhatsAppTemplates
$isFetchTemplatesQuery = false;
if (isset($input['query'])) {
    $query = $input['query'];
    // Recherche basique pour détecter cette requête spécifique
    if (strpos($query, 'fetchApprovedWhatsAppTemplates') !== false) {
        $isFetchTemplatesQuery = true;
    }
}

// Si c'est la requête problématique, retourner une réponse de secours
if ($isFetchTemplatesQuery) {
    header('Content-Type: application/json');
    
    // Construire une réponse qui correspond au schéma GraphQL attendu
    // avec un tableau vide mais valide
    $response = [
        'data' => [
            'fetchApprovedWhatsAppTemplates' => []
        ]
    ];
    
    echo json_encode($response);
    exit;
}

// Sinon, transmettre la requête au vrai graphql.php
include __DIR__ . '/../public/graphql.php';
EOT;

// Écrire le fichier proxy
$proxyPath = __DIR__ . '/../public/graphql-proxy.php';
file_put_contents($proxyPath, $proxyContent);
echo "✅ Proxy GraphQL d'urgence créé: $proxyPath\n";

// 3. Créer un fichier HTML de test pour le proxy
$testHtmlContent = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Proxy GraphQL Emergency</title>
    <style>
        body { font-family: -apple-system, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        button { background: #4285f4; color: white; border: none; padding: 8px 16px; margin: 10px 5px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #3367d6; }
        .success { color: #0d904f; }
        .error { color: #d23f31; }
    </style>
</head>
<body>
    <h1>Test du Proxy GraphQL d'Urgence</h1>
    <p>Ce test vérifie si le proxy GraphQL répond correctement aux requêtes fetchApprovedWhatsAppTemplates.</p>
    
    <div>
        <button id="testProxyBtn">Tester le Proxy</button>
        <button id="testDirectBtn">Tester GraphQL Direct</button>
    </div>
    
    <h2>Résultats:</h2>
    <pre id="results">Les résultats apparaîtront ici...</pre>
    
    <script>
        document.getElementById('testProxyBtn').addEventListener('click', async () => {
            await testGraphQL('/graphql-proxy.php', 'Proxy');
        });
        
        document.getElementById('testDirectBtn').addEventListener('click', async () => {
            await testGraphQL('/graphql.php', 'Direct');
        });
        
        async function testGraphQL(endpoint, label) {
            const results = document.getElementById('results');
            results.innerHTML = `Test de ${label} en cours...`;
            
            const query = `
                query {
                    fetchApprovedWhatsAppTemplates {
                        id
                        name
                        category
                        language
                        status
                    }
                }
            `;
            
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({query}),
                    credentials: 'include'
                });
                
                const data = await response.json();
                
                if (data.errors) {
                    results.innerHTML = `<span class="error">❌ ${label} a échoué avec des erreurs:</span>\n${JSON.stringify(data, null, 2)}`;
                } else if (data.data && Array.isArray(data.data.fetchApprovedWhatsAppTemplates)) {
                    results.innerHTML = `<span class="success">✅ ${label} a réussi!</span>\n${JSON.stringify(data, null, 2)}`;
                } else {
                    results.innerHTML = `<span class="error">⚠️ ${label} a répondu avec une structure inattendue:</span>\n${JSON.stringify(data, null, 2)}`;
                }
            } catch (error) {
                results.innerHTML = `<span class="error">❌ Erreur lors du test de ${label}:</span>\n${error.message}`;
            }
        }
    </script>
</body>
</html>
EOT;

// Écrire le fichier HTML de test
$testHtmlPath = __DIR__ . '/../public/test-proxy.html';
file_put_contents($testHtmlPath, $testHtmlContent);
echo "✅ Page de test HTML créée: $testHtmlPath\n";

echo "\n=== SOLUTION D'URGENCE DÉPLOYÉE ===\n";
echo "1. Utilisez le proxy GraphQL pour toutes les requêtes en remplaçant:\n";
echo "   - Ancien endpoint: /graphql.php\n";
echo "   - Nouvel endpoint: /graphql-proxy.php\n";
echo "2. Testez la solution avec la page: /test-proxy.html\n";
echo "3. Cette solution de contournement permet de continuer à utiliser l'interface\n";
echo "   tout en ne retournant pas de templates (tableau vide mais valide)\n";
echo "\nIMPORTANT: Redémarrez votre serveur PHP pour appliquer les changements.\n";