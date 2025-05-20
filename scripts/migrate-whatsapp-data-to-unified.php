<?php

declare(strict_types=1);

$entityManager = require __DIR__ . '/../src/bootstrap-doctrine-simple.php';

use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\User;

echo "=== Migration des données WhatsApp vers la structure unifiée ===\n";

try {
    // Récupérer l'utilisateur admin
    $adminUser = $entityManager->getRepository(User::class)->findOneBy(['isAdmin' => true]);
    
    if (!$adminUser) {
        echo "Aucun utilisateur admin trouvé!\n";
        exit(1);
    }
    
    echo "Utilisateur admin trouvé: " . $adminUser->getEmail() . "\n";
    
    // Vérifier s'il existe des templates dans l'ancienne table
    $connection = $entityManager->getConnection();
    
    // Vérifier si les anciennes tables existent encore
    $tables = $connection->fetchAllAssociative("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'whatsapp_%'");
    echo "\nTables WhatsApp existantes:\n";
    foreach ($tables as $table) {
        echo "- " . $table['name'] . "\n";
    }
    
    // Migrer les templates de l'ancienne structure si elle existe
    $oldUserTemplatesExist = $connection->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='whatsapp_user_templates'");
    
    if ($oldUserTemplatesExist) {
        echo "\nMigration des templates utilisateur...\n";
        
        $oldTemplates = $connection->fetchAllAssociative("
            SELECT ut.*, t.* 
            FROM whatsapp_user_templates ut
            LEFT JOIN whatsapp_templates t ON ut.template_id = t.id
        ");
        
        foreach ($oldTemplates as $data) {
            $template = new WhatsAppTemplate();
            $template->setName($data['name'] ?? 'Template ' . $data['id']);
            $template->setLanguage($data['language'] ?? 'fr');
            $template->setCategory($data['category'] ?? WhatsAppTemplate::CATEGORY_UTILITY);
            $template->setStatus($data['status'] ?? WhatsAppTemplate::STATUS_APPROVED);
            $template->setBodyText($data['body_text'] ?? $data['bodyText'] ?? '');
            $template->setHeaderFormat($data['header_format'] ?? $data['headerFormat'] ?? WhatsAppTemplate::HEADER_FORMAT_NONE);
            $template->setHeaderText($data['header_text'] ?? $data['headerText'] ?? null);
            $template->setFooterText($data['footer_text'] ?? $data['footerText'] ?? null);
            $template->setMetaTemplateId($data['meta_template_id'] ?? $data['metaTemplateId'] ?? null);
            $template->setIsActive(true);
            
            // Déterminer si c'est global ou spécifique à un utilisateur
            if ($data['is_custom'] ?? false) {
                $userId = $data['user_id'] ?? null;
                if ($userId) {
                    $user = $entityManager->getRepository(User::class)->find($userId);
                    if ($user) {
                        $template->setUser($user);
                        $template->setIsGlobal(false);
                    }
                }
            } else {
                $template->setIsGlobal(true);
            }
            
            $entityManager->persist($template);
            echo "- Template migré: " . $template->getName() . " (" . $template->getLanguage() . ")\n";
        }
    }
    
    // S'assurer qu'on a au moins quelques templates globaux
    $globalTemplatesCount = $connection->fetchOne("SELECT COUNT(*) FROM whatsapp_templates WHERE is_global = 1");
    
    if ($globalTemplatesCount == 0) {
        echo "\nAucun template global trouvé. Création de templates par défaut...\n";
        
        // Créer quelques templates par défaut
        $defaultTemplates = [
            [
                'name' => 'order_confirmation',
                'language' => 'fr',
                'category' => WhatsAppTemplate::CATEGORY_UTILITY,
                'bodyText' => 'Bonjour {{1}}, votre commande {{2}} a été confirmée. Montant total: {{3}} FCFA. Merci!',
                'headerFormat' => WhatsAppTemplate::HEADER_FORMAT_TEXT,
                'headerText' => 'Confirmation de commande',
                'status' => WhatsAppTemplate::STATUS_APPROVED
            ],
            [
                'name' => 'welcome_message',
                'language' => 'fr',
                'category' => WhatsAppTemplate::CATEGORY_UTILITY,
                'bodyText' => 'Bienvenue {{1}}! Nous sommes ravis de vous compter parmi nos clients.',
                'footerText' => 'Pour toute question, contactez-nous.',
                'status' => WhatsAppTemplate::STATUS_APPROVED
            ],
            [
                'name' => 'appointment_reminder',
                'language' => 'fr',
                'category' => WhatsAppTemplate::CATEGORY_UTILITY,
                'bodyText' => 'Rappel: Votre rendez-vous est prévu le {{1}} à {{2}}. Merci de confirmer votre présence.',
                'headerFormat' => WhatsAppTemplate::HEADER_FORMAT_TEXT,
                'headerText' => 'Rappel de rendez-vous',
                'status' => WhatsAppTemplate::STATUS_APPROVED
            ],
            [
                'name' => 'delivery_notification',
                'language' => 'fr',
                'category' => WhatsAppTemplate::CATEGORY_UTILITY,
                'bodyText' => 'Votre colis {{1}} est en cours de livraison. Heure estimée: {{2}}.',
                'status' => WhatsAppTemplate::STATUS_APPROVED
            ]
        ];
        
        foreach ($defaultTemplates as $templateData) {
            $template = new WhatsAppTemplate();
            $template->setName($templateData['name']);
            $template->setLanguage($templateData['language']);
            $template->setCategory($templateData['category']);
            $template->setBodyText($templateData['bodyText']);
            $template->setStatus($templateData['status']);
            $template->setIsGlobal(true);
            $template->setIsActive(true);
            
            if (isset($templateData['headerFormat'])) {
                $template->setHeaderFormat($templateData['headerFormat']);
            }
            if (isset($templateData['headerText'])) {
                $template->setHeaderText($templateData['headerText']);
            }
            if (isset($templateData['footerText'])) {
                $template->setFooterText($templateData['footerText']);
            }
            
            $entityManager->persist($template);
            echo "- Template par défaut créé: " . $template->getName() . "\n";
        }
    }
    
    // Appliquer les changements
    $entityManager->flush();
    
    echo "\nMigration terminée avec succès!\n";
    
    // Afficher le résumé
    $totalTemplates = $connection->fetchOne("SELECT COUNT(*) FROM whatsapp_templates");
    $globalTemplates = $connection->fetchOne("SELECT COUNT(*) FROM whatsapp_templates WHERE is_global = 1");
    $userTemplates = $connection->fetchOne("SELECT COUNT(*) FROM whatsapp_templates WHERE is_global = 0");
    
    echo "\nRésumé:\n";
    echo "- Total des templates: $totalTemplates\n";
    echo "- Templates globaux: $globalTemplates\n";
    echo "- Templates utilisateur: $userTemplates\n";
    
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}