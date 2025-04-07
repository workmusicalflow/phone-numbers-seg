<?php

/**
 * Phone Numbers Segmentation Web Application
 * 
 * Entry point for the application
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define application root
define('APP_ROOT', dirname(__DIR__));

// Require Composer autoloader
require APP_ROOT . '/vendor/autoload.php';

// Load environment variables from .env file
try {
    $dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
    $dotenv->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    // .env file not found, proceed with defaults or system env vars
    // You might want to log this in a real application
    error_log('.env file not found, using default configurations or system environment variables.');
} catch (Exception $e) {
    // Other potential errors during Dotenv loading
    error_log('Error loading .env file: ' . $e->getMessage());
    // Depending on your error handling strategy, you might want to die() here
}


// Initialize application
// This is a placeholder for the actual application initialization
// which will be implemented as the project progresses

// For now, display a simple placeholder page
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segmentation de Numéros de Téléphone</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        h1 {
            color: #2c3e50;
            text-align: center;
        }

        .description {
            text-align: center;
            margin: 40px 0;
            font-size: 1.2em;
            color: #7f8c8d;
        }

        .features {
            margin: 40px 0;
        }

        .feature {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .feature h2 {
            color: #3498db;
            margin-top: 0;
        }

        .nav {
            margin-bottom: 20px;
            text-align: center;
        }

        .nav a {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .nav a:hover {
            background-color: #2980b9;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Segmentation de Numéros de Téléphone</h1>

        <div class="description">
            <p>Cette application permet de segmenter les numéros de téléphone de Côte d'Ivoire.</p>
            <p>Formats supportés:</p>
            <ul style="list-style-type: none; padding: 0;">
                <li>+2250777104936</li>
                <li>002250777104936</li>
                <li>0777104936</li>
            </ul>
        </div>

        <div class="nav">
            <a href="http://localhost:5173/">Vue.js App</a>
            <a href="http://localhost:5173/segment" style="background-color: #27ae60;">Segmentation Vue.js</a>
            <a href="http://localhost:5173/batch">Traitement par Lot Vue.js</a>
            <a href="http://localhost:5173/sms">Envoi de SMS Vue.js</a>
            <a href="http://localhost:5173/segments">Gestion des Segments Vue.js</a>
            <a href="http://localhost:5173/import">Import/Export Vue.js</a>
            <hr style="margin: 20px 0;">
            <a href="segment.html">Segmentation (Ancienne)</a>
            <a href="batch.html">Traitement par Lot (Ancien)</a>
            <a href="sms.html">Envoi de SMS (Ancien)</a>
            <a href="segments.html">Gestion des Segments (Ancien)</a>
            <a href="import.html">Import/Export (Ancien)</a>
            <a href="graphiql.html" style="background-color: #9b59b6;">API GraphQL</a>
        </div>

        <div class="features">
            <div class="feature">
                <h2>Segmentation Individuelle</h2>
                <p>Segmentez un numéro de téléphone à la fois pour obtenir des informations détaillées sur ses
                    composants.</p>
                <p>Idéal pour analyser un numéro spécifique et comprendre sa structure.</p>
                <p><strong>Nouveau :</strong> Ajoutez des informations de contact (civilité, prénom, nom, entreprise)
                    avec la nouvelle interface Vue.js.</p>
                <p><a href="http://localhost:5173/segment" style="color: #27ae60; font-weight: bold;">Essayer la
                        nouvelle interface →</a></p>
            </div>

            <div class="feature">
                <h2>Traitement par Lot</h2>
                <p>Traitez plusieurs numéros de téléphone simultanément pour une analyse efficace de grands volumes de
                    données.</p>
                <p>Possibilité de sauvegarder les résultats dans la base de données pour référence future.</p>
            </div>

            <div class="feature">
                <h2>Envoi de SMS</h2>
                <p>Envoyez des SMS à des segments spécifiques pour vos campagnes marketing ciblées.</p>
                <p>Utilisez l'API Orange pour envoyer des SMS à des numéros individuels ou à des groupes.</p>
            </div>

            <div class="feature">
                <h2>Gestion des Segments</h2>
                <p>Créez, modifiez et supprimez des segments personnalisés pour organiser vos numéros de téléphone.</p>
                <p>Utilisez ces segments pour cibler vos campagnes SMS et analyser vos données.</p>
            </div>

            <div class="feature">
                <h2>Import/Export</h2>
                <p>Importez des numéros de téléphone depuis un fichier CSV ou un texte brut.</p>
                <p>Exportez vos données pour les utiliser dans d'autres applications ou pour sauvegarder vos
                    informations.</p>
            </div>

            <div class="feature">
                <h2>API GraphQL</h2>
                <p>Accédez à toutes les fonctionnalités de l'application via une API GraphQL moderne et flexible.</p>
                <p>Utilisez l'interface GraphiQL pour explorer et tester l'API interactivement.</p>
            </div>
        </div>
    </div>
</body>

</html>