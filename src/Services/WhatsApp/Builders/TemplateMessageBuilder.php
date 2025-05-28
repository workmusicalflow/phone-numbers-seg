<?php

namespace App\Services\WhatsApp\Builders;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use DateTime;

/**
 * Builder pour la construction de messages template WhatsApp
 * 
 * Cette classe encapsule la logique de construction des messages template
 * et de leur historique, réduisant la complexité de WhatsAppService
 */
class TemplateMessageBuilder
{
    /**
     * Construit les composants du template
     */
    public function buildComponents(
        ?string $headerImageUrl = null,
        array $bodyParams = []
    ): array {
        $components = [];

        // Construire le composant header si une image est fournie
        if ($headerImageUrl !== null) {
            $components[] = $this->buildHeaderComponent($headerImageUrl);
        }

        // Construire le composant body si des paramètres sont fournis
        if (!empty($bodyParams)) {
            $components[] = $this->buildBodyComponent($bodyParams);
        }

        return $components;
    }

    /**
     * Construit le payload pour l'API WhatsApp
     */
    public function buildPayload(
        string $normalizedPhoneNumber,
        string $templateName,
        string $languageCode,
        array $components = []
    ): array {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $normalizedPhoneNumber,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ]
            ]
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $payload;
    }

    /**
     * Construit l'objet WhatsAppMessageHistory
     */
    public function buildMessageHistory(
        User $user,
        string $recipient,
        string $templateName,
        string $languageCode,
        array $components,
        string $wabaMessageId
    ): WhatsAppMessageHistory {
        $messageHistory = new WhatsAppMessageHistory();
        
        $messageHistory->setOracleUser($user);
        $messageHistory->setWabaMessageId($wabaMessageId);
        $messageHistory->setPhoneNumber($recipient);
        $messageHistory->setDirection('OUTGOING');
        $messageHistory->setType('template');
        $messageHistory->setStatus('sent');
        $messageHistory->setTemplateName($templateName);
        $messageHistory->setTemplateLanguage($languageCode);
        $messageHistory->setContent($this->buildContentJson($templateName, $languageCode, $components));
        $messageHistory->setTimestamp(new DateTime());

        return $messageHistory;
    }

    /**
     * Construit le composant header pour une image
     */
    private function buildHeaderComponent(string $imageUrl): array
    {
        return [
            'type' => 'header',
            'parameters' => [
                [
                    'type' => 'image',
                    'image' => [
                        'link' => $imageUrl
                    ]
                ]
            ]
        ];
    }

    /**
     * Construit le composant body avec les paramètres
     */
    private function buildBodyComponent(array $bodyParams): array
    {
        $parameters = [];
        
        foreach ($bodyParams as $param) {
            $parameters[] = [
                'type' => 'text',
                'text' => (string) $param
            ];
        }

        return [
            'type' => 'body',
            'parameters' => $parameters
        ];
    }

    /**
     * Construit le contenu JSON pour l'historique
     */
    private function buildContentJson(
        string $templateName,
        string $languageCode,
        array $components
    ): string {
        return json_encode([
            'template' => $templateName,
            'language' => $languageCode,
            'components' => $components
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Extrait les paramètres du corps depuis les composants
     */
    public function extractBodyParams(array $components): array
    {
        $bodyParams = [];

        foreach ($components as $component) {
            if ($component['type'] === 'body' && isset($component['parameters'])) {
                foreach ($component['parameters'] as $param) {
                    if ($param['type'] === 'text' && isset($param['text'])) {
                        $bodyParams[] = $param['text'];
                    }
                }
            }
        }

        return $bodyParams;
    }

    /**
     * Extrait l'URL de l'image header depuis les composants
     */
    public function extractHeaderImageUrl(array $components): ?string
    {
        foreach ($components as $component) {
            if ($component['type'] === 'header' && isset($component['parameters'])) {
                foreach ($component['parameters'] as $param) {
                    if ($param['type'] === 'image' && isset($param['image']['link'])) {
                        return $param['image']['link'];
                    }
                }
            }
        }

        return null;
    }
}