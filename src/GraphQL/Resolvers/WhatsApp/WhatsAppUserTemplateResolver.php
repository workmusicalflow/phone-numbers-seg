<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers\WhatsApp;

use App\GraphQL\Context\GraphQLContext;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Exceptions\ForbiddenException;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Resolver GraphQL pour les templates WhatsApp associés aux utilisateurs
 */
class WhatsAppUserTemplateResolver
{
    private EntityManagerInterface $entityManager;
    private WhatsAppTemplateRepositoryInterface $templateRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        WhatsAppTemplateRepositoryInterface $templateRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->templateRepository = $templateRepository;
        $this->logger = $logger;
    }

    /**
     * Récupère les templates WhatsApp disponibles pour l'utilisateur connecté
     * 
     * @Query
     * @return WhatsAppTemplate[]
     */
    public function getWhatsAppUserTemplates(
        ?string $category = null,
        ?string $language = null,
        ?GraphQLContext $context = null
    ): array {
        $user = $context?->getCurrentUser();
        if ($user === null) {
            throw new ForbiddenException('Utilisateur non authentifié');
        }

        $this->logger->info('Récupération des templates utilisateur', [
            'user_id' => $user->getId(),
            'category' => $category,
            'language' => $language
        ]);

        // Requête pour obtenir les templates associés à l'utilisateur
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('t')
           ->from('App\Entities\WhatsApp\WhatsAppTemplate', 't')
           ->join('App\Entities\WhatsApp\WhatsAppUserTemplate', 'ut', 'WITH', 
                  't.name = ut.templateName AND t.language = ut.languageCode')
           ->where('ut.user = :user')
           ->andWhere('t.isActive = :active')
           ->andWhere('t.status = :status')
           ->setParameter('user', $user)
           ->setParameter('active', true)
           ->setParameter('status', 'APPROVED');

        // Ajouter les filtres si fournis
        if ($category !== null) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }

        if ($language !== null) {
            $qb->andWhere('t.language = :language')
               ->setParameter('language', $language);
        }

        $qb->orderBy('t.name', 'ASC')
           ->addOrderBy('t.language', 'ASC');

        $templates = $qb->getQuery()->getResult();

        $this->logger->info('Templates trouvés', [
            'count' => count($templates)
        ]);

        // On retourne les WhatsAppTemplate, pas les WhatsAppUserTemplate
        return $templates;
    }

    /**
     * Envoie un message avec un template
     * 
     * @Mutation
     */
    public function sendWhatsAppTemplateMessage(
        string $recipient,
        string $templateName,
        string $languageCode,
        ?array $bodyParams = null,
        ?string $headerImageUrl = null,
        ?GraphQLContext $context = null
    ): array {
        $user = $context?->getCurrentUser();
        if ($user === null) {
            throw new ForbiddenException('Utilisateur non authentifié');
        }

        // Vérifier que l'utilisateur a accès à ce template
        $userHasTemplate = $this->entityManager->createQueryBuilder()
            ->select('COUNT(ut.id)')
            ->from('App\Entities\WhatsApp\WhatsAppUserTemplate', 'ut')
            ->where('ut.user = :user')
            ->andWhere('ut.templateName = :templateName')
            ->andWhere('ut.languageCode = :languageCode')
            ->setParameter('user', $user)
            ->setParameter('templateName', $templateName)
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->getSingleScalarResult();

        if ($userHasTemplate == 0) {
            throw new ForbiddenException('Template non autorisé pour cet utilisateur');
        }

        // Utiliser le service WhatsApp pour envoyer le message
        $whatsAppService = $this->entityManager->getRepository('App\Services\WhatsApp\WhatsAppService');
        
        $messageHistory = $whatsAppService->sendTemplateMessage(
            $user,
            $recipient,
            $templateName,
            $languageCode,
            $headerImageUrl,
            $bodyParams ?? []
        );

        return [
            'id' => $messageHistory->getId(),
            'wabaMessageId' => $messageHistory->getWabaMessageId(),
            'status' => $messageHistory->getStatus(),
            'type' => $messageHistory->getType(),
            'phoneNumber' => $messageHistory->getPhoneNumber()
        ];
    }
}