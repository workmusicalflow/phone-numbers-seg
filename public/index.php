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

        .coming-soon {
            text-align: center;
            margin: 40px 0;
            font-size: 1.2em;
            color: #7f8c8d;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Segmentation de Numéros de Téléphone</h1>
        <div class="coming-soon">
            <p>Application en cours de développement</p>
            <p>Cette application permettra de segmenter les numéros de téléphone de Côte d'Ivoire.</p>
            <p>Formats supportés:</p>
            <ul style="list-style-type: none; padding: 0;">
                <li>+2250777104936</li>
                <li>002250777104936</li>
                <li>0777104936</li>
            </ul>
        </div>
    </div>
</body>

</html>