<?php

namespace App\Services\Interfaces\WhatsApp;

/**
 * Interface pour le client API WhatsApp Business Cloud
 */
interface WhatsAppApiClientInterface
{
    /**
     * Envoyer un message via l'API WhatsApp
     * 
     * @param array $payload Données du message
     * @return array Réponse de l'API
     */
    public function sendMessage(array $payload): array;
    
    /**
     * Uploader un média
     * 
     * @param string $filePath Chemin du fichier
     * @param string $mimeType Type MIME
     * @return string ID du média
     */
    public function uploadMedia(string $filePath, string $mimeType): string;
    
    /**
     * Télécharger un média
     * 
     * @param string $mediaId ID du média
     * @return array Contenu et type MIME
     */
    public function downloadMedia(string $mediaId): array;
    
    /**
     * Obtenir l'URL d'un média
     * 
     * @param string $mediaId ID du média
     * @return string URL du média
     */
    public function getMediaUrl(string $mediaId): string;
    
    /**
     * Obtenir la liste des templates
     * 
     * @return array Liste des templates
     */
    public function getTemplates(): array;
    
    /**
     * Créer un nouveau template
     * 
     * @param array $template Données du template
     * @return array Réponse de l'API
     */
    public function createTemplate(array $template): array;
    
    /**
     * Supprimer un template
     * 
     * @param string $templateName Nom du template
     * @return bool Succès de l'opération
     */
    public function deleteTemplate(string $templateName): bool;
}