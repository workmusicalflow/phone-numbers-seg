<?php

declare(strict_types=1);

namespace App\GraphQL\Types\WhatsApp;

use App\Entities\WhatsApp\WhatsAppTemplateHistory;
use App\GraphQL\Types\ContactType;
use App\GraphQL\Types\UserType;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Type GraphQL pour l'historique des templates WhatsApp
 * 
 * @Type(class=WhatsAppTemplateHistory::class, name="WhatsAppTemplateHistory")
 */
class WhatsAppTemplateHistoryType
{
    /**
     * @Field
     */
    public function id(WhatsAppTemplateHistory $history): ID
    {
        return new ID($history->getId());
    }

    /**
     * @Field
     */
    public function templateId(WhatsAppTemplateHistory $history): string
    {
        return $history->getTemplateId();
    }

    /**
     * @Field
     */
    public function templateName(WhatsAppTemplateHistory $history): string
    {
        return $history->getTemplateName();
    }

    /**
     * @Field
     */
    public function language(WhatsAppTemplateHistory $history): string
    {
        return $history->getLanguage();
    }

    /**
     * @Field
     */
    public function category(WhatsAppTemplateHistory $history): string
    {
        return $history->getCategory();
    }

    /**
     * @Field
     */
    public function phoneNumber(WhatsAppTemplateHistory $history): string
    {
        return $history->getPhoneNumber();
    }

    /**
     * @Field
     */
    public function parameters(WhatsAppTemplateHistory $history): ?array
    {
        return $history->getParameters();
    }

    /**
     * @Field
     */
    public function headerMediaType(WhatsAppTemplateHistory $history): ?string
    {
        return $history->getHeaderMediaType();
    }

    /**
     * @Field
     */
    public function headerMediaUrl(WhatsAppTemplateHistory $history): ?string
    {
        return $history->getHeaderMediaUrl();
    }

    /**
     * @Field
     */
    public function headerMediaId(WhatsAppTemplateHistory $history): ?string
    {
        return $history->getHeaderMediaId();
    }

    /**
     * @Field
     */
    public function buttonValues(WhatsAppTemplateHistory $history): ?array
    {
        return $history->getButtonValues();
    }

    /**
     * @Field
     */
    public function wabaMessageId(WhatsAppTemplateHistory $history): ?string
    {
        return $history->getWabaMessageId();
    }

    /**
     * @Field
     */
    public function status(WhatsAppTemplateHistory $history): string
    {
        return $history->getStatus();
    }

    /**
     * @Field
     */
    public function usedAt(WhatsAppTemplateHistory $history): string
    {
        return $history->getUsedAt()->format('c');
    }

    /**
     * @Field
     */
    public function createdAt(WhatsAppTemplateHistory $history): string
    {
        return $history->getCreatedAt()->format('c');
    }

    /**
     * @Field
     */
    public function updatedAt(WhatsAppTemplateHistory $history): ?string
    {
        $updatedAt = $history->getUpdatedAt();
        return $updatedAt ? $updatedAt->format('c') : null;
    }

    /**
     * @Field
     */
    public function user(WhatsAppTemplateHistory $history): UserType
    {
        return new UserType($history->getOracleUser());
    }

    /**
     * @Field
     */
    public function contact(WhatsAppTemplateHistory $history): ?ContactType
    {
        $contact = $history->getContact();
        return $contact ? new ContactType($contact) : null;
    }

    /**
     * @Field
     */
    public function template(WhatsAppTemplateHistory $history): ?WhatsAppTemplateType
    {
        $template = $history->getTemplate();
        return $template ? new WhatsAppTemplateType($template) : null;
    }

    /**
     * @Field
     */
    public function messageHistory(WhatsAppTemplateHistory $history): ?WhatsAppMessageHistoryType
    {
        $messageHistory = $history->getMessageHistory();
        return $messageHistory ? new WhatsAppMessageHistoryType($messageHistory) : null;
    }
}