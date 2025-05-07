<?php

namespace App\Services\WhatsApp;

use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Client pour l'API WhatsApp Business Cloud
 */
class WhatsAppApiClient implements WhatsAppApiClientInterface
{
    /**
     * @var Client
     */
    private Client $httpClient;

    /**
     * @var string
     */
    private string $accessToken;

    /**
     * @var string
     */
    private string $phoneNumberId;

    /**
     * @var string
     */
    private string $apiVersion;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var string
     */
    private string $baseUrl = 'https://graph.facebook.com/';

    /**
     * Constructeur
     *
     * @param string $accessToken
     * @param string $phoneNumberId
     * @param string $apiVersion
     * @param LoggerInterface $logger
     */
    public function __construct(
        string $accessToken,
        string $phoneNumberId,
        string $apiVersion,
        LoggerInterface $logger
    ) {
        $this->accessToken = $accessToken;
        $this->phoneNumberId = $phoneNumberId;
        $this->apiVersion = $apiVersion;
        $this->logger = $logger;
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    /**
     * Envoie un message texte WhatsApp
     *
     * @param string $to Numéro de téléphone du destinataire
     * @param string $message Contenu du message
     * @return array Réponse de l'API
     */
    public function sendTextMessage(string $to, string $message): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message
            ]
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Envoie un message template WhatsApp
     *
     * @param string $to Numéro de téléphone du destinataire
     * @param string $templateName Nom du template
     * @param string $languageCode Code de langue pour le template
     * @param array $parameters Paramètres du template
     * @return array Réponse de l'API
     */
    public function sendTemplateMessage(string $to, string $templateName, string $languageCode, array $parameters = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ]
            ]
        ];

        // Ajouter les composants si fournis
        if (!empty($parameters)) {
            $payload['template']['components'] = $parameters;
        }

        return $this->sendRequest($payload);
    }

    /**
     * Envoie un message image WhatsApp
     *
     * @param string $to Numéro de téléphone du destinataire
     * @param string $imageUrl URL de l'image
     * @param string|null $caption Légende optionnelle
     * @return array Réponse de l'API
     */
    public function sendImageMessage(string $to, string $imageUrl, ?string $caption = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'image',
            'image' => [
                'link' => $imageUrl
            ]
        ];

        // Ajouter une légende si fournie
        if ($caption) {
            $payload['image']['caption'] = $caption;
        }

        return $this->sendRequest($payload);
    }

    /**
     * Télécharge un média depuis l'API WhatsApp
     *
     * @param string $mediaId ID du média à télécharger
     * @return string|null Contenu du média ou null en cas d'échec
     */
    public function downloadMedia(string $mediaId): ?string
    {
        try {
            // Première requête pour obtenir l'URL du média
            $mediaInfoResponse = $this->httpClient->get(
                "{$this->apiVersion}/{$mediaId}",
                ['http_errors' => false]
            );

            $mediaInfo = json_decode($mediaInfoResponse->getBody()->getContents(), true);
            
            if (!isset($mediaInfo['url'])) {
                $this->logger->error('URL du média non trouvée', [
                    'media_id' => $mediaId,
                    'response' => $mediaInfo
                ]);
                return null;
            }

            // Deuxième requête pour télécharger le contenu du média
            $mediaResponse = $this->httpClient->get(
                $mediaInfo['url'],
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken
                    ],
                    'http_errors' => false
                ]
            );

            return $mediaResponse->getBody()->getContents();

        } catch (GuzzleException $e) {
            $this->logger->error('Erreur lors du téléchargement du média', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Marque un message comme lu
     *
     * @param string $messageId ID du message à marquer comme lu
     * @return bool Succès de l'opération
     */
    public function markMessageAsRead(string $messageId): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId
        ];

        try {
            $response = $this->sendRequest($payload);
            return isset($response['success']) && $response['success'];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du marquage du message comme lu', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie une requête à l'API WhatsApp
     *
     * @param array $payload Données à envoyer
     * @return array Réponse de l'API
     * @throws \Exception
     */
    private function sendRequest(array $payload): array
    {
        try {
            $endpoint = "{$this->apiVersion}/{$this->phoneNumberId}/messages";
            
            $this->logger->info('Envoi d\'une requête à l\'API WhatsApp', [
                'endpoint' => $endpoint,
                'payload_type' => $payload['type'] ?? 'inconnu'
            ]);

            $response = $this->httpClient->post($endpoint, [
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('Requête API WhatsApp réussie', [
                    'status_code' => $statusCode,
                    'response' => $responseData
                ]);
                return $responseData;
            }

            $this->logger->error('Erreur lors de la requête API WhatsApp', [
                'status_code' => $statusCode,
                'response' => $responseData
            ]);

            throw new \Exception('Erreur API WhatsApp: ' . ($responseData['error']['message'] ?? 'Erreur inconnue'));

        } catch (GuzzleException $e) {
            $this->logger->error('Exception lors de la requête API WhatsApp', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Erreur de connexion à l\'API WhatsApp: ' . $e->getMessage());
        }
    }
}