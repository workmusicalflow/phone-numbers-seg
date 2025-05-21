<?php

declare(strict_types=1);

// Charger l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Repositories\Doctrine\WhatsApp\WhatsAppTemplateRepository;

echo "=== Test des templates WhatsApp avec catégorie ===\n\n";

try {
    // Récupérer l'utilisateur admin
    $adminUser = $entityManager->getRepository(User::class)->findOneBy(['isAdmin' => true]);
    
    if (!$adminUser) {
        echo "Aucun utilisateur admin trouvé! Création d'un utilisateur de test temporaire.\n";
        
        // Créer un utilisateur temporaire pour le test
        $testUser = new User();
        $testUser->setEmail('test_admin@example.com');
        $testUser->setPassword(password_hash('test123', PASSWORD_DEFAULT));
        $testUser->setFirstName('Test');
        $testUser->setLastName('Admin');
        $testUser->setIsAdmin(true);
        
        $entityManager->persist($testUser);
        $entityManager->flush();
        
        echo "Utilisateur de test créé avec succès.\n\n";
        $adminUser = $testUser;
    }
    
    echo "Utilisateur admin: " . $adminUser->getEmail() . "\n\n";
    
    // Créer le repository
    $templateRepository = new WhatsAppTemplateRepository($entityManager, WhatsAppTemplate::class);
    
    // 1. Créer un template de test avec une catégorie spécifique
    echo "1. Création d'un template de test avec catégorie 'MARKETING':\n";
    
    $marketingTemplate = new WhatsAppTemplate();
    $marketingTemplate->setName('marketing_special_promo');
    $marketingTemplate->setLanguage('fr');
    $marketingTemplate->setCategory(WhatsAppTemplate::CATEGORY_MARKETING);
    $marketingTemplate->setStatus(WhatsAppTemplate::STATUS_APPROVED);
    $marketingTemplate->setIsGlobal(false);
    $marketingTemplate->setIsActive(true);
    
    // Ajouter des composants avec du texte pour le body
    $components = [
        [
            'type' => 'BODY',
            'text' => 'Bonjour {{1}}! Voici une offre spéciale: {{2}} de réduction sur votre prochain achat.'
        ]
    ];
    $marketingTemplate->setComponentsFromArray($components);
    
    $entityManager->persist($marketingTemplate);
    $entityManager->flush();
    
    echo "  - Template marketing créé: " . $marketingTemplate->getName() . "\n";
    echo "  - Catégorie: " . $marketingTemplate->getCategory() . "\n";
    echo "  - Body text: " . $marketingTemplate->getBodyText() . "\n";
    echo "  - Variables count: " . $marketingTemplate->getBodyVariablesCount() . "\n";
    
    // 2. Créer un template utilitaire
    echo "\n2. Création d'un template utilitaire:\n";
    
    $utilityTemplate = new WhatsAppTemplate();
    $utilityTemplate->setName('utility_appointment');
    $utilityTemplate->setLanguage('fr');
    $utilityTemplate->setCategory(WhatsAppTemplate::CATEGORY_UTILITY);
    $utilityTemplate->setStatus(WhatsAppTemplate::STATUS_APPROVED);
    $utilityTemplate->setIsGlobal(true);
    $utilityTemplate->setIsActive(true);
    
    // Ajouter des composants avec du texte pour le body et un header
    $components = [
        [
            'type' => 'HEADER',
            'format' => 'IMAGE'
        ],
        [
            'type' => 'BODY',
            'text' => 'Rappel: Votre rendez-vous est confirmé pour le {{1}} à {{2}}. Contactez-nous pour toute modification.'
        ]
    ];
    $utilityTemplate->setComponentsFromArray($components);
    
    $entityManager->persist($utilityTemplate);
    $entityManager->flush();
    
    echo "  - Template utilitaire créé: " . $utilityTemplate->getName() . "\n";
    echo "  - Catégorie: " . $utilityTemplate->getCategory() . "\n";
    echo "  - Has header media: " . ($utilityTemplate->hasHeaderMedia() ? 'Oui' : 'Non') . "\n";
    echo "  - Body variables count: " . $utilityTemplate->getBodyVariablesCount() . "\n";
    
    // 3. Récupérer et afficher tous les templates avec leur catégorie
    echo "\n3. Liste de tous les templates avec leur catégorie:\n";
    
    $allTemplates = $templateRepository->findAll();
    foreach ($allTemplates as $template) {
        echo "  - Template: " . $template->getName() . "\n";
        echo "    • Catégorie: " . $template->getCategory() . "\n";
        echo "    • Langue: " . $template->getLanguage() . "\n";
        echo "    • Global: " . ($template->isGlobal() ? 'Oui' : 'Non') . "\n";
        echo "    • Var. corps: " . $template->getBodyVariablesCount() . "\n";
        echo "    • Header média: " . ($template->hasHeaderMedia() ? 'Oui' : 'Non') . "\n";
        echo "\n";
    }
    
    // 4. Tester la récupération de templates par catégorie
    echo "4. Templates par catégorie:\n";
    
    $categories = [
        WhatsAppTemplate::CATEGORY_MARKETING,
        WhatsAppTemplate::CATEGORY_UTILITY,
        WhatsAppTemplate::CATEGORY_AUTHENTICATION,
        WhatsAppTemplate::CATEGORY_STANDARD
    ];
    
    foreach ($categories as $category) {
        echo "\n  Templates de catégorie '$category':\n";
        
        $templates = $templateRepository->findBy(['category' => $category]);
        if (empty($templates)) {
            echo "  • Aucun template trouvé dans cette catégorie\n";
            continue;
        }
        
        foreach ($templates as $template) {
            echo "  • " . $template->getName() . " (" . $template->getLanguage() . ")\n";
        }
    }
    
    echo "\n=== Test terminé avec succès ===\n";
    
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}