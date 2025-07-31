<?php

declare(strict_types=1);

$entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Repositories\Doctrine\WhatsApp\WhatsAppTemplateRepository;
use Doctrine\ORM\EntityRepository;

echo "=== Test des templates WhatsApp unifiés ===\n\n";

try {
    // Récupérer l'utilisateur admin
    $adminUser = $entityManager->getRepository(User::class)->findOneBy(['isAdmin' => true]);
    
    if (!$adminUser) {
        echo "Aucun utilisateur admin trouvé!\n";
        exit(1);
    }
    
    echo "Utilisateur admin: " . $adminUser->getEmail() . "\n\n";
    
    // Créer directement une instance du repository personnalisé
    $templateRepository = new WhatsAppTemplateRepository($entityManager, $entityManager->getClassMetadata(WhatsAppTemplate::class));
    
    // Tester différentes méthodes du repository
    
    echo "1. Templates globaux:\n";
    $globalTemplates = $templateRepository->findGlobalTemplates();
    foreach ($globalTemplates as $template) {
        echo "- " . $template->getName() . " (" . $template->getLanguage() . ") - " . $template->getStatus() . "\n";
    }
    
    echo "\n2. Templates accessibles à l'admin:\n";
    $accessibleTemplates = $templateRepository->findAccessibleByUser($adminUser);
    foreach ($accessibleTemplates as $template) {
        echo "- " . $template->getName() . " (" . $template->getLanguage() . ") - Global: " . ($template->isGlobal() ? 'Oui' : 'Non') . "\n";
    }
    
    echo "\n3. Templates approuvés accessibles à l'admin:\n";
    $approvedTemplates = $templateRepository->findAccessibleApprovedByUser($adminUser);
    foreach ($approvedTemplates as $template) {
        echo "- " . $template->getName() . " (" . $template->getLanguage() . ")\n";
        echo "  Body: " . substr($template->getBodyText(), 0, 50) . "...\n";
    }
    
    // Tester la création d'un template spécifique à l'utilisateur
    echo "\n4. Création d'un template spécifique à l'utilisateur:\n";
    $userTemplate = new WhatsAppTemplate();
    $userTemplate->setName('personal_greeting');
    $userTemplate->setLanguage('fr');
    $userTemplate->setCategory(WhatsAppTemplate::CATEGORY_MARKETING);
    $userTemplate->setBodyText('Salut {{1}}! Voici une offre spéciale pour vous.');
    $userTemplate->setStatus(WhatsAppTemplate::STATUS_APPROVED);
    $userTemplate->setUser($adminUser);
    $userTemplate->setIsGlobal(false);
    $userTemplate->setIsActive(true);
    
    $entityManager->persist($userTemplate);
    $entityManager->flush();
    
    echo "Template personnel créé: " . $userTemplate->getName() . "\n";
    
    // Vérifier que le template est bien accessible
    echo "\n5. Templates de l'utilisateur admin:\n";
    $userTemplates = $templateRepository->findByUser($adminUser);
    foreach ($userTemplates as $template) {
        echo "- " . $template->getName() . " (" . $template->getLanguage() . ")\n";
    }
    
    echo "\n6. Tous les templates accessibles maintenant:\n";
    $allAccessible = $templateRepository->findAccessibleByUser($adminUser);
    foreach ($allAccessible as $template) {
        echo "- " . $template->getName() . " - Global: " . ($template->isGlobal() ? 'Oui' : 'Non') . "\n";
    }
    
    echo "\n=== Test terminé avec succès ===\n";
    
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}