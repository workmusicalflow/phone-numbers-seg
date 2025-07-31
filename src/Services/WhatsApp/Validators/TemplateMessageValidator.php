<?php

namespace App\Services\WhatsApp\Validators;

use App\Entities\User;
use App\Services\WhatsApp\ValueObjects\ValidationResult;
use App\Exceptions\ValidationException;

/**
 * Validateur pour les messages template WhatsApp
 * 
 * Cette classe centralise toutes les validations liées à l'envoi
 * de messages template, réduisant ainsi la complexité de WhatsAppService
 */
class TemplateMessageValidator
{
    /**
     * Valide les données d'un message template
     * 
     * @param User $user L'utilisateur envoyant le message
     * @param string $recipient Le numéro de téléphone destinataire
     * @param string $templateName Le nom du template
     * @param string $languageCode Le code de langue
     * @param array $bodyParams Les paramètres du corps du message
     * @param string|null $headerImageUrl L'URL de l'image d'en-tête
     * @return ValidationResult
     */
    public function validate(
        User $user,
        string $recipient,
        string $templateName,
        string $languageCode,
        array $bodyParams = [],
        ?string $headerImageUrl = null
    ): ValidationResult {
        $errors = [];

        // Validation de l'utilisateur
        if (!$user->getId()) {
            $errors[] = 'Utilisateur non valide';
        }

        // Validation du numéro de téléphone
        $phoneValidation = $this->validatePhoneNumber($recipient);
        if (!$phoneValidation->isValid()) {
            $errors = array_merge($errors, $phoneValidation->getErrors());
        }

        // Validation du template
        $templateValidation = $this->validateTemplateName($templateName);
        if (!$templateValidation->isValid()) {
            $errors = array_merge($errors, $templateValidation->getErrors());
        }

        // Validation du code de langue
        $languageValidation = $this->validateLanguageCode($languageCode);
        if (!$languageValidation->isValid()) {
            $errors = array_merge($errors, $languageValidation->getErrors());
        }

        // Validation des paramètres du corps
        $paramsValidation = $this->validateBodyParameters($bodyParams);
        if (!$paramsValidation->isValid()) {
            $errors = array_merge($errors, $paramsValidation->getErrors());
        }

        // Validation de l'URL de l'image si fournie
        if ($headerImageUrl !== null) {
            $imageValidation = $this->validateHeaderImageUrl($headerImageUrl);
            if (!$imageValidation->isValid()) {
                $errors = array_merge($errors, $imageValidation->getErrors());
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Valide un numéro de téléphone
     */
    private function validatePhoneNumber(string $phoneNumber): ValidationResult
    {
        $errors = [];

        if (empty($phoneNumber)) {
            $errors[] = 'Le numéro de téléphone est requis';
            return new ValidationResult(false, $errors);
        }

        // Vérifier le format E.164
        if (!preg_match('/^\+[1-9]\d{10,14}$/', $phoneNumber)) {
            $errors[] = 'Le numéro de téléphone doit être au format E.164 (+XXXXXXXXXXXX)';
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Valide le nom du template
     */
    private function validateTemplateName(string $templateName): ValidationResult
    {
        $errors = [];

        if (empty($templateName)) {
            $errors[] = 'Le nom du template est requis';
            return new ValidationResult(false, $errors);
        }

        // Vérifier le format du nom (lettres minuscules, chiffres et underscores)
        if (!preg_match('/^[a-z0-9_]+$/', $templateName)) {
            $errors[] = 'Le nom du template doit contenir uniquement des lettres minuscules, chiffres et underscores';
        }

        // Vérifier la longueur
        if (strlen($templateName) > 100) {
            $errors[] = 'Le nom du template ne doit pas dépasser 100 caractères';
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Valide le code de langue
     */
    private function validateLanguageCode(string $languageCode): ValidationResult
    {
        $errors = [];

        if (empty($languageCode)) {
            $errors[] = 'Le code de langue est requis';
            return new ValidationResult(false, $errors);
        }

        // Liste des codes de langue supportés par WhatsApp
        $supportedLanguages = [
            'af', 'sq', 'ar', 'az', 'bn', 'bg', 'ca', 'zh_CN', 'zh_HK', 'zh_TW',
            'hr', 'cs', 'da', 'nl', 'en', 'en_GB', 'en_US', 'et', 'fil', 'fi',
            'fr', 'ka', 'de', 'el', 'gu', 'ha', 'he', 'hi', 'hu', 'id', 'ga',
            'it', 'ja', 'kn', 'kk', 'rw_RW', 'ko', 'ky_KG', 'lo', 'lv', 'lt',
            'mk', 'ms', 'ml', 'mr', 'nb', 'fa', 'pl', 'pt_BR', 'pt_PT', 'pa',
            'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'es_AR', 'es_ES', 'es_MX', 'sw',
            'sv', 'ta', 'te', 'th', 'tr', 'uk', 'ur', 'uz', 'vi', 'zu'
        ];

        if (!in_array($languageCode, $supportedLanguages)) {
            $errors[] = "Le code de langue '$languageCode' n'est pas supporté par WhatsApp";
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Valide les paramètres du corps du message
     */
    private function validateBodyParameters(array $bodyParams): ValidationResult
    {
        $errors = [];

        foreach ($bodyParams as $index => $param) {
            if (!is_string($param)) {
                $errors[] = "Le paramètre à l'index $index doit être une chaîne de caractères";
                continue;
            }

            // Vérifier la longueur maximale
            if (strlen($param) > 1024) {
                $errors[] = "Le paramètre à l'index $index dépasse la limite de 1024 caractères";
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Valide l'URL de l'image d'en-tête
     */
    private function validateHeaderImageUrl(string $url): ValidationResult
    {
        $errors = [];

        if (empty($url)) {
            $errors[] = "L'URL de l'image ne peut pas être vide";
            return new ValidationResult(false, $errors);
        }

        // Valider le format de l'URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = "L'URL de l'image n'est pas valide";
            return new ValidationResult(false, $errors);
        }

        // Vérifier que l'URL utilise HTTPS
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            $errors[] = "L'URL de l'image doit utiliser HTTPS";
        }

        // Vérifier l'extension du fichier
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "L'image doit être au format JPG, JPEG ou PNG";
        }

        return new ValidationResult(empty($errors), $errors);
    }
}