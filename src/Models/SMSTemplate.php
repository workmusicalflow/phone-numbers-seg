<?php

namespace App\Models;

/**
 * Modèle pour les modèles de SMS
 * 
 * Ce modèle représente un modèle de SMS qui peut être utilisé pour créer
 * rapidement des messages avec des variables personnalisables.
 */
class SMSTemplate
{
    /**
     * Identifiant unique du modèle
     * 
     * @var int
     */
    private $id;

    /**
     * Identifiant de l'utilisateur propriétaire du modèle
     * 
     * @var int
     */
    private $userId;

    /**
     * Titre du modèle
     * 
     * @var string
     */
    private $title;

    /**
     * Contenu du modèle avec variables (ex: {nom}, {prénom})
     * 
     * @var string
     */
    private $content;

    /**
     * Description du modèle (optionnel)
     * 
     * @var string|null
     */
    private $description;

    /**
     * Date de création du modèle
     * 
     * @var string
     */
    private $createdAt;

    /**
     * Date de dernière modification du modèle
     * 
     * @var string
     */
    private $updatedAt;

    /**
     * Constructeur
     * 
     * @param int $id Identifiant unique
     * @param int $userId Identifiant de l'utilisateur propriétaire
     * @param string $title Titre du modèle
     * @param string $content Contenu du modèle
     * @param string|null $description Description du modèle (optionnel)
     * @param string|null $createdAt Date de création (format Y-m-d H:i:s)
     * @param string|null $updatedAt Date de dernière modification (format Y-m-d H:i:s)
     */
    public function __construct(
        int $id = 0,
        int $userId = 0,
        string $title = '',
        string $content = '',
        ?string $description = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->content = $content;
        $this->description = $description;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?? date('Y-m-d H:i:s');
    }

    /**
     * Obtenir l'identifiant du modèle
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Définir l'identifiant du modèle
     * 
     * @param int $id Nouvel identifiant
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Obtenir l'identifiant de l'utilisateur propriétaire
     * 
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Définir l'identifiant de l'utilisateur propriétaire
     * 
     * @param int $userId Nouvel identifiant utilisateur
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Obtenir le titre du modèle
     * 
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Définir le titre du modèle
     * 
     * @param string $title Nouveau titre
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Obtenir le contenu du modèle
     * 
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Définir le contenu du modèle
     * 
     * @param string $content Nouveau contenu
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Obtenir la description du modèle
     * 
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Définir la description du modèle
     * 
     * @param string|null $description Nouvelle description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Obtenir la date de création
     * 
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Définir la date de création
     * 
     * @param string $createdAt Nouvelle date de création
     * @return self
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Obtenir la date de dernière modification
     * 
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * Définir la date de dernière modification
     * 
     * @param string $updatedAt Nouvelle date de modification
     * @return self
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Extraire les variables du contenu du modèle
     * 
     * @return array Liste des variables trouvées dans le contenu
     */
    public function extractVariables(): array
    {
        preg_match_all('/{([^}]+)}/', $this->content, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Appliquer des valeurs aux variables du modèle
     * 
     * @param array $values Tableau associatif des valeurs à appliquer (clé = nom de variable, valeur = valeur à insérer)
     * @return string Contenu avec les variables remplacées
     */
    public function applyVariables(array $values): string
    {
        $content = $this->content;
        foreach ($values as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }

    /**
     * Convertir le modèle en tableau associatif
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'title' => $this->title,
            'content' => $this->content,
            'description' => $this->description,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'variables' => $this->extractVariables()
        ];
    }

    /**
     * Créer une instance à partir d'un tableau associatif
     * 
     * @param array $data Données du modèle
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? 0,
            $data['userId'] ?? 0,
            $data['title'] ?? '',
            $data['content'] ?? '',
            $data['description'] ?? null,
            $data['createdAt'] ?? null,
            $data['updatedAt'] ?? null
        );
    }
}
