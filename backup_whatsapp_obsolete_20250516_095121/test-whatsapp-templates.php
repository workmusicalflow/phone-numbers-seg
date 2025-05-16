<?php
/**
 * Page de test dédiée pour les templates WhatsApp
 * Ce script affiche un bouton pour tester directement l'API GraphQL
 * puis affiche les résultats obtenus directement depuis la base de données.
 */

header('Content-Type: text/html; charset=utf-8');

// Configuration d'accès CORS pour permettre les requêtes du frontend en développement
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

// Base de données SQLite
$dbPath = __DIR__ . '/../var/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Log des accès
$logFile = __DIR__ . '/../var/logs/templates-test.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Accès à test-whatsapp-templates.php\n", FILE_APPEND);

// Récupération des templates pour l'utilisateur 2 (testuser)
$userId = isset($_GET['userId']) ? (int)$_GET['userId'] : 2;
$stmt = $pdo->prepare('SELECT * FROM whatsapp_user_templates WHERE user_id = ?');
$stmt->execute([$userId]);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de l'utilisateur
$stmtUser = $pdo->prepare('SELECT username FROM users WHERE id = ?');
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$username = $user ? $user['username'] : "Utilisateur inconnu";

// Comptage des templates WhatsApp standards
$stmtTemplatesCount = $pdo->prepare('SELECT COUNT(*) as count FROM whatsapp_templates');
$stmtTemplatesCount->execute();
$templatesCount = $stmtTemplatesCount->fetch(PDO::FETCH_ASSOC)['count'];

// HTML de la page
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test des Templates WhatsApp</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background-color: #f4f4f4; padding: 10px 20px; border-radius: 5px; margin-bottom: 20px; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        h1, h2, h3 { color: #333; }
        pre { background-color: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .result { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Test des Templates WhatsApp</h1>
            <p>Cette page permet de tester les requêtes GraphQL pour récupérer les templates WhatsApp ainsi que le mécanisme de fallback.</p>
        </div>

        <div class="section">
            <h2>Informations générales</h2>
            <ul>
                <li><strong>Utilisateur ID:</strong> ' . $userId . ' (' . $username . ')</li>
                <li><strong>Nombre de templates standards WhatsApp:</strong> ' . $templatesCount . '</li>
                <li><strong>Nombre de templates utilisateur trouvés en base:</strong> ' . count($templates) . '</li>
                <li><strong>Date et heure du test:</strong> ' . date('Y-m-d H:i:s') . '</li>
            </ul>
        </div>

        <div class="section">
            <h2>Templates trouvés en base pour l\'utilisateur ' . $userId . '</h2>';

// Affichage des templates trouvés
if (count($templates) > 0) {
    echo '<table>
            <tr>
                <th>ID</th>
                <th>Nom du template</th>
                <th>Langue</th>
                <th>Variables</th>
                <th>Header Media</th>
                <th>Special</th>
                <th>Date création</th>
            </tr>';
    
    foreach ($templates as $template) {
        echo '<tr>
                <td>' . $template['id'] . '</td>
                <td>' . $template['template_name'] . '</td>
                <td>' . $template['language_code'] . '</td>
                <td>' . ($template['body_variables_count'] ?? 'Non défini') . '</td>
                <td>' . ($template['has_header_media'] ? 'Oui' : 'Non') . '</td>
                <td>' . ($template['is_special_template'] ? 'Oui' : 'Non') . '</td>
                <td>' . $template['created_at'] . '</td>
            </tr>';
    }
    
    echo '</table>';
} else {
    echo '<p class="error">Aucun template trouvé en base de données pour cet utilisateur.</p>';
}

echo '</div>

        <div class="section">
            <h2>Test de l\'API GraphQL</h2>
            <p>Cliquez sur le bouton ci-dessous pour tester la requête GraphQL pour récupérer les templates de l\'utilisateur.</p>
            <button id="testGraphQL">Tester l\'API GraphQL</button>
            <div id="graphqlResult" class="result" style="display: none;"></div>
        </div>

        <div class="section">
            <h2>Test de l\'API Fallback</h2>
            <p>Cliquez sur le bouton ci-dessous pour tester l\'API de fallback qui est utilisée quand l\'API GraphQL échoue.</p>
            <button id="testFallback">Tester l\'API Fallback</button>
            <div id="fallbackResult" class="result" style="display: none;"></div>
        </div>
    </div>

    <script>
        // Fonction pour tester l\'API GraphQL
        document.getElementById("testGraphQL").addEventListener("click", async function() {
            const resultDiv = document.getElementById("graphqlResult");
            resultDiv.style.display = "block";
            resultDiv.innerHTML = "Chargement...";
            
            try {
                const response = await fetch("/graphql.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        query: `
                            query WhatsappUserTemplates($userId: ID!, $limit: Int, $offset: Int) {
                                whatsappUserTemplates(userId: $userId, limit: $limit, offset: $offset) {
                                    id
                                    userId
                                    templateName
                                    languageCode
                                    bodyVariablesCount
                                    hasHeaderMedia
                                    isSpecialTemplate
                                    headerMediaUrl
                                    createdAt
                                    updatedAt
                                }
                            }
                        `,
                        variables: {
                            userId: "' . $userId . '",
                            limit: 50,
                            offset: 0
                        }
                    })
                });
                
                const data = await response.json();
                
                if (data.errors) {
                    resultDiv.innerHTML = "<h3 class=\"error\">Erreur GraphQL:</h3><pre>" + JSON.stringify(data.errors, null, 2) + "</pre>";
                } else if (data.data && data.data.whatsappUserTemplates) {
                    const templates = data.data.whatsappUserTemplates;
                    if (templates.length === 0) {
                        resultDiv.innerHTML = "<h3 class=\"error\">Aucun template récupéré via GraphQL</h3>";
                    } else {
                        resultDiv.innerHTML = "<h3 class=\"success\">" + templates.length + " template(s) récupéré(s) via GraphQL:</h3><pre>" + JSON.stringify(templates, null, 2) + "</pre>";
                    }
                } else {
                    resultDiv.innerHTML = "<h3 class=\"error\">Réponse GraphQL inattendue:</h3><pre>" + JSON.stringify(data, null, 2) + "</pre>";
                }
            } catch (error) {
                resultDiv.innerHTML = "<h3 class=\"error\">Erreur lors de la requête:</h3><pre>" + error + "</pre>";
            }
        });
        
        // Fonction pour tester l\'API Fallback
        document.getElementById("testFallback").addEventListener("click", async function() {
            const resultDiv = document.getElementById("fallbackResult");
            resultDiv.style.display = "block";
            resultDiv.innerHTML = "Chargement...";
            
            try {
                // Ajout d\'un timestamp pour éviter la mise en cache
                const timestamp = new Date().getTime();
                const response = await fetch(`/fallback-whatsapp-templates.php?userId=' . $userId . '&limit=50&offset=0&_=${timestamp}`);
                
                const data = await response.json();
                
                if (data.data && data.data.whatsappUserTemplates) {
                    const templates = data.data.whatsappUserTemplates;
                    if (templates.length === 0) {
                        resultDiv.innerHTML = "<h3 class=\"error\">Aucun template récupéré via l\'API Fallback</h3>";
                    } else {
                        resultDiv.innerHTML = "<h3 class=\"success\">" + templates.length + " template(s) récupéré(s) via l\'API Fallback:</h3><pre>" + JSON.stringify(templates, null, 2) + "</pre>";
                    }
                } else {
                    resultDiv.innerHTML = "<h3 class=\"error\">Réponse inattendue de l\'API Fallback:</h3><pre>" + JSON.stringify(data, null, 2) + "</pre>";
                }
            } catch (error) {
                resultDiv.innerHTML = "<h3 class=\"error\">Erreur lors de la requête:</h3><pre>" + error + "</pre>";
            }
        });
    </script>
</body>
</html>';

// Log de la fin d'exécution
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Templates trouvés pour l'utilisateur $userId: " . count($templates) . "\n", FILE_APPEND);