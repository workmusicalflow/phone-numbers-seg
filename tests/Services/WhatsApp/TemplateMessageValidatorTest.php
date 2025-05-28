<?php

namespace Tests\Services\WhatsApp;

use PHPUnit\Framework\TestCase;
use App\Services\WhatsApp\Validators\TemplateMessageValidator;
use App\Entities\User;

class TemplateMessageValidatorTest extends TestCase
{
    private TemplateMessageValidator $validator;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TemplateMessageValidator();
        
        // Créer un utilisateur de test
        $this->user = $this->createMock(User::class);
        $this->user->method('getId')->willReturn(1);
    }

    public function testValidateSuccessWithMinimalData(): void
    {
        $result = $this->validator->validate(
            $this->user,
            '+33612345678',
            'hello_world',
            'fr',
            [],
            null
        );

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateSuccessWithFullData(): void
    {
        $result = $this->validator->validate(
            $this->user,
            '+33612345678',
            'marketing_template',
            'en_US',
            ['John', 'Doe', 'Product'],
            'https://example.com/image.jpg'
        );

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateFailsWithInvalidPhoneNumber(): void
    {
        // Numéro sans +
        $result = $this->validator->validate(
            $this->user,
            '0612345678',
            'hello_world',
            'fr'
        );

        $this->assertFalse($result->isValid());
        $this->assertContains(
            'Le numéro de téléphone doit être au format E.164 (+XXXXXXXXXXXX)',
            $result->getErrors()
        );
    }

    public function testValidateFailsWithEmptyPhoneNumber(): void
    {
        $result = $this->validator->validate(
            $this->user,
            '',
            'hello_world',
            'fr'
        );

        $this->assertFalse($result->isValid());
        $this->assertContains(
            'Le numéro de téléphone est requis',
            $result->getErrors()
        );
    }

    public function testValidateFailsWithInvalidTemplateName(): void
    {
        // Nom avec caractères spéciaux
        $result = $this->validator->validate(
            $this->user,
            '+33612345678',
            'Hello-World!',
            'fr'
        );

        $this->assertFalse($result->isValid());
        $this->assertContains(
            'Le nom du template doit contenir uniquement des lettres minuscules, chiffres et underscores',
            $result->getErrors()
        );
    }

    public function testValidateFailsWithUnsupportedLanguage(): void
    {
        $result = $this->validator->validate(
            $this->user,
            '+33612345678',
            'hello_world',
            'xyz' // Langue non supportée
        );

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString("Le code de langue 'xyz' n'est pas supporté", $errors[0]);
    }

    public function testValidateFailsWithHttpImageUrl(): void
    {
        $result = $this->validator->validate(
            $this->user,
            '+33612345678',
            'hello_world',
            'fr',
            [],
            'http://example.com/image.jpg' // HTTP au lieu de HTTPS
        );

        $this->assertFalse($result->isValid());
        $this->assertContains(
            "L'URL de l'image doit utiliser HTTPS",
            $result->getErrors()
        );
    }

    public function testValidateFailsWithInvalidImageFormat(): void
    {
        $result = $this->validator->validate(
            $this->user,
            '+33612345678',
            'hello_world',
            'fr',
            [],
            'https://example.com/image.gif' // GIF non supporté
        );

        $this->assertFalse($result->isValid());
        $this->assertContains(
            "L'image doit être au format JPG, JPEG ou PNG",
            $result->getErrors()
        );
    }

    public function testValidateFailsWithNonStringBodyParameter(): void
    {
        $result = $this->validator->validate(
            $this->user,
            '+33612345678',
            'hello_world',
            'fr',
            ['Valid', 123, 'Another'], // 123 n'est pas une chaîne
            null
        );

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertTrue(
            in_array("Le paramètre à l'index 1 doit être une chaîne de caractères", $errors)
        );
    }

    public function testValidateFailsWithTooLongBodyParameter(): void
    {
        $longString = str_repeat('a', 1025); // 1025 caractères
        
        $result = $this->validator->validate(
            $this->user,
            '+33612345678',
            'hello_world',
            'fr',
            [$longString],
            null
        );

        $this->assertFalse($result->isValid());
        $this->assertContains(
            "Le paramètre à l'index 0 dépasse la limite de 1024 caractères",
            $result->getErrors()
        );
    }

    public function testValidateAccumulatesMultipleErrors(): void
    {
        $result = $this->validator->validate(
            $this->user,
            '', // Numéro vide
            '', // Template vide
            '', // Langue vide
            [],
            'invalid-url' // URL invalide
        );

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        
        // Vérifier qu'on a bien plusieurs erreurs
        $this->assertGreaterThan(3, count($errors));
        $this->assertContains('Le numéro de téléphone est requis', $errors);
        $this->assertContains('Le nom du template est requis', $errors);
        $this->assertContains('Le code de langue est requis', $errors);
    }
}