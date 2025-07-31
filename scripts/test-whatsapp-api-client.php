<?php

declare(strict_types=1);

/**
 * Script de test pour le client WhatsAppApiClient
 * 
 * Ce script teste directement le client API pour s'assurer qu'il peut
 * se connecter à l'API Meta et récupérer les templates.
 */

require_once __DIR__ . '/../src/bootstrap-doctrine.php';

use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\GraphQL\DIContainer;
use Psr\Container\ContainerInterface;

// Récupérer le conteneur
$container = new DIContainer();

echo "Test du client API WhatsApp\n";
echo "=========================\n\n";

// Récupérer l'API client
try {
    $apiClient = $container->get(WhatsAppApiClientInterface::class);
    echo "Client API récupéré avec succès\n";
    
    // Tester la méthode getTemplates
    echo "\nTest de la méthode getTemplates:\n";
    $templates = $apiClient->getTemplates();
    
    if (is_array($templates)) {
        echo "Succès: " . count($templates) . " templates récupérés\n";
        
        if (count($templates) > 0) {
            echo "\nInformations sur le premier template:\n";
            $firstTemplate = $templates[0];
            
            echo "ID: " . ($firstTemplate['id'] ?? 'Non défini') . "\n";
            echo "Nom: " . ($firstTemplate['name'] ?? 'Non défini') . "\n";
            echo "Catégorie: " . ($firstTemplate['category'] ?? 'Non défini') . "\n";
            echo "Langue: " . ($firstTemplate['language'] ?? 'Non défini') . "\n";
            echo "Statut: " . ($firstTemplate['status'] ?? 'Non défini') . "\n";
            
            echo "\nComposants:\n";
            if (isset($firstTemplate['components']) && is_array($firstTemplate['components'])) {
                foreach ($firstTemplate['components'] as $index => $component) {
                    echo "Composant " . ($index + 1) . " (Type: " . ($component['type'] ?? 'Non défini') . ")\n";
                    
                    // Afficher les détails spécifiques selon le type
                    if (isset($component['type'])) {
                        switch ($component['type']) {
                            case 'HEADER':
                                echo "  Format: " . ($component['format'] ?? 'Non défini') . "\n";
                                if (isset($component['text'])) {
                                    echo "  Texte: " . $component['text'] . "\n";
                                }
                                break;
                                
                            case 'BODY':
                                if (isset($component['text'])) {
                                    echo "  Texte: " . $component['text'] . "\n";
                                }
                                break;
                                
                            case 'FOOTER':
                                if (isset($component['text'])) {
                                    echo "  Texte: " . $component['text'] . "\n";
                                }
                                break;
                                
                            case 'BUTTONS':
                                if (isset($component['buttons']) && is_array($component['buttons'])) {
                                    echo "  Nombre de boutons: " . count($component['buttons']) . "\n";
                                    foreach ($component['buttons'] as $buttonIndex => $button) {
                                        echo "  Bouton " . ($buttonIndex + 1) . " (Type: " . ($button['type'] ?? 'Non défini') . ")\n";
                                        if (isset($button['text'])) {
                                            echo "    Texte: " . $button['text'] . "\n";
                                        }
                                    }
                                }
                                break;
                        }
                    }
                }
            } else {
                echo "Aucun composant trouvé ou format invalide\n";
            }
        } else {
            echo "ATTENTION: Aucun template trouvé. Vérifiez que l'API est correctement configurée.\n";
        }
    } else {
        echo "ERREUR: La méthode getTemplates n'a pas retourné un tableau\n";
        echo "Type retourné: " . gettype($templates) . "\n";
        echo "Valeur: " . print_r($templates, true) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest terminé\n";