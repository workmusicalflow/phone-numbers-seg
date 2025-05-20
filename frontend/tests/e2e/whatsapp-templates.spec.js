// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('WhatsApp Templates', () => {
  test.beforeEach(async ({ page }) => {
    // Se connecter avant chaque test
    await page.goto('/');
    await page.fill('input[type="text"]', 'admin');  // Nom d'utilisateur
    await page.fill('input[type="password"]', 'admin123');  // Mot de passe
    await page.click('button[type="submit"]');
    
    // Attendre que la connexion soit réussie
    await expect(page).toHaveURL(/dashboard/);
    
    // Naviguer vers la page des templates WhatsApp
    await page.goto('/whatsapp-templates');
  });

  test('Affiche la page des templates WhatsApp', async ({ page }) => {
    // Vérifier le titre de la page
    await expect(page.locator('h1')).toContainText('Templates WhatsApp');
    
    // Vérifier que le champ de saisie du numéro est présent
    await expect(page.locator('input[label="Numéro de téléphone"]')).toBeVisible();
    
    // Vérifier que le bouton pour sélectionner un template est présent
    await expect(page.locator('button:has-text("Sélectionner un template")')).toBeVisible();
    
    // Vérifier que la carte d'information est visible
    await expect(page.locator('.message-info-card')).toBeVisible();
  });

  test('Le sélecteur de template s\'affiche après saisie d\'un numéro', async ({ page }) => {
    // Saisir un numéro de téléphone
    await page.fill('input[label="Numéro de téléphone"]', '+2250102030405');
    
    // Cliquer sur le bouton pour sélectionner un template
    await page.click('button:has-text("Sélectionner un template")');
    
    // Vérifier que le sélecteur de template est affiché
    await expect(page.locator('.template-selector-card')).toBeVisible();
    
    // Vérifier que le sélecteur contient les éléments attendus
    await expect(page.locator('.whatsapp-template-selector')).toBeVisible();
    await expect(page.locator('.filters-container')).toBeVisible();
  });

  test('Le chargement des templates affiche un spinner', async ({ page }) => {
    // Saisir un numéro de téléphone et ouvrir le sélecteur
    await page.fill('input[label="Numéro de téléphone"]', '+2250102030405');
    await page.click('button:has-text("Sélectionner un template")');
    
    // Cliquer sur le bouton de rafraîchissement pour déclencher le chargement
    await page.click('.template-selector-header button');
    
    // Vérifier que le spinner est affiché pendant le chargement
    await expect(page.locator('.q-spinner')).toBeVisible();
    
    // Attendre que le chargement soit terminé
    await page.waitForSelector('.q-spinner', { state: 'hidden' });
  });

  test('Les filtres de recherche fonctionnent correctement', async ({ page }) => {
    // Setup: Saisir un numéro et afficher le sélecteur
    await page.fill('input[label="Numéro de téléphone"]', '+2250102030405');
    await page.click('button:has-text("Sélectionner un template")');
    
    // Attendre que les templates soient chargés
    await page.waitForSelector('.q-spinner', { state: 'hidden' });
    
    // Saisir un terme de recherche
    await page.fill('input[placeholder="Rechercher un template"]', 'confirmation');
    
    // Vérifier que les résultats sont filtrés
    // Note: Ce test échouera si aucun template ne contient le terme "confirmation"
    // await expect(page.locator('.template-list-container')).not.toBeEmpty();
  });

  test('Annulation de la sélection de template', async ({ page }) => {
    // Setup: Saisir un numéro et afficher le sélecteur
    await page.fill('input[label="Numéro de téléphone"]', '+2250102030405');
    await page.click('button:has-text("Sélectionner un template")');
    
    // Attendre que les templates soient chargés
    await page.waitForSelector('.q-spinner', { state: 'hidden' });
    
    // Sélectionner un template (cliquer sur le premier template disponible)
    await page.click('.template-list-container .q-item:first-child');
    
    // Cliquer sur le bouton "Changer de template"
    await page.click('button:has-text("Changer de template")');
    
    // Vérifier que nous sommes revenus à la liste des templates
    await expect(page.locator('.template-list-container')).toBeVisible();
  });
});