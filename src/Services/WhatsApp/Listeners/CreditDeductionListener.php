<?php

namespace App\Services\WhatsApp\Listeners;

use App\Services\WhatsApp\Events\ListenerInterface;
use App\Services\WhatsApp\Events\EventInterface;
use App\Services\WhatsApp\Events\TemplateMessageSentEvent;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Listener qui déduit les crédits de l'utilisateur après l'envoi d'un message
 */
class CreditDeductionListener implements ListenerInterface
{
    private UserRepositoryInterface $userRepository;
    private LoggerInterface $logger;
    private int $creditCost;

    public function __construct(
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger,
        int $creditCost = 1
    ) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->creditCost = $creditCost;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(EventInterface $event): void
    {
        if (!$event instanceof TemplateMessageSentEvent) {
            return;
        }

        $user = $event->getUser();
        $currentCredits = $user->getCredits();
        
        if ($currentCredits < $this->creditCost) {
            $this->logger->warning('User has insufficient credits after sending', [
                'user_id' => $user->getId(),
                'current_credits' => $currentCredits,
                'required' => $this->creditCost
            ]);
            return;
        }

        // Déduire les crédits
        $newCredits = $currentCredits - $this->creditCost;
        $this->userRepository->updateCredits($user->getId(), $newCredits);

        $this->logger->info('Credits deducted for WhatsApp message', [
            'user_id' => $user->getId(),
            'credits_before' => $currentCredits,
            'credits_after' => $newCredits,
            'cost' => $this->creditCost,
            'message_id' => $event->getMessageHistory()->getWabaMessageId()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAsync(): bool
    {
        // La déduction de crédits peut être asynchrone
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'credit_deduction_listener';
    }
}