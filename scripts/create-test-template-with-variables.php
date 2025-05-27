<?php
/**
 * Script pour créer un template de test avec des variables
 */

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Entities\WhatsApp\WhatsAppTemplate;

echo "=== Création d'un template de test avec variables ===\n\n";

try {
    // Construction du conteneur d'injection de dépendances
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
    $container = $containerBuilder->build();

    // Récupération de l'entity manager
    $entityManager = $container->get(EntityManagerInterface::class);
    
    // Créer un nouveau template avec des variables
    $template = new WhatsAppTemplate();
    $template->setName('test_template_variables');
    $template->setLanguage('fr');
    $template->setCategory('MARKETING');
    $template->setStatus('APPROVED');
    $template->setIsActive(true);
    $template->setIsGlobal(true);
    
    // Composants avec variables
    $components = [
        [
            'type' => 'HEADER',
            'format' => 'TEXT',
            'text' => 'Bonjour {{1}} !'
        ],
        [
            'type' => 'BODY',
            'text' => 'Cher(e) {{1}},\n\nNous avons le plaisir de vous informer que votre commande n°{{2}} a été confirmée.\n\nDate de livraison prévue : {{3}}\nMontant total : {{4}} €\n\nMerci pour votre confiance !\n\nCordialement,\nL\'équipe {{5}}'
        ],
        [
            'type' => 'FOOTER',
            'text' => 'Service Client disponible 24/7'
        ],
        [
            'type' => 'BUTTONS',
            'buttons' => [
                [
                    'type' => 'URL',
                    'text' => 'Suivre ma commande',
                    'url' => 'https://example.com/tracking/{{2}}'
                ],
                [
                    'type' => 'QUICK_REPLY',
                    'text' => 'Besoin d\'aide'
                ]
            ]
        ]
    ];
    
    $template->setComponents(json_encode($components));
    
    // Sauvegarder le template
    $entityManager->persist($template);
    $entityManager->flush();
    
    echo "✅ Template de test créé avec succès !\n";
    echo "Nom : test_template_variables\n";
    echo "Variables détectées :\n";
    echo "- {{1}} : Nom du client (dans header et body)\n";
    echo "- {{2}} : Numéro de commande (dans body et URL du bouton)\n";
    echo "- {{3}} : Date de livraison\n";
    echo "- {{4}} : Montant total\n";
    echo "- {{5}} : Nom de l'équipe\n\n";
    
    echo "Vous pouvez maintenant tester ce template dans l'interface WhatsApp Bulk.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la création du template : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}