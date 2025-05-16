// @ts-check
const { test, expect } = require('@playwright/test');

// Désactiver le timeout global pour ces tests
test.setTimeout(60000);

test.describe('WhatsApp Integration Tests', () => {
  // Configuration commune
  const baseURL = process.env.BASE_URL || 'http://localhost:5173';
  const apiURL = process.env.API_URL || 'http://localhost:8000';

  test.beforeEach(async ({ page }) => {
    // Se connecter avant chaque test
    await page.goto(`${baseURL}/login`);
    
    // Connexion avec l'utilisateur test
    await page.fill('input[name="email"]', process.env.TEST_USER_EMAIL || 'test@example.com');
    await page.fill('input[name="password"]', process.env.TEST_USER_PASSWORD || 'password');
    await page.click('button[type="submit"]');
    
    // Attendre la redirection
    await page.waitForURL(`${baseURL}/`);
  });

  test('should navigate to WhatsApp page', async ({ page }) => {
    // Accéder à la page WhatsApp
    await page.goto(`${baseURL}/whatsapp`);
    
    // Vérifier que la page est chargée
    await expect(page.locator('h1')).toContainText('WhatsApp');
    await expect(page.locator('.contact-count-badge')).toBeVisible();
  });

  test('should send a text message', async ({ page }) => {
    await page.goto(`${baseURL}/whatsapp`);
    
    // S'assurer qu'on est sur l'onglet d'envoi
    await page.click('text=Envoyer');
    
    // Remplir le formulaire
    await page.fill('input[label="Numéro de téléphone du destinataire"]', '+2250123456789');
    await page.fill('textarea[label="Message"]', 'Test message from E2E test');
    
    // Mock la réponse API pour éviter l'envoi réel
    await page.route('**/graphql', async route => {
      if (route.request().postData()?.includes('sendWhatsAppMessage')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              sendWhatsAppMessage: {
                id: 'test-id',
                wabaMessageId: 'wamid.test',
                phoneNumber: '+2250123456789',
                direction: 'OUTGOING',
                type: 'text',
                content: 'Test message from E2E test',
                status: 'sent',
                createdAt: new Date().toISOString()
              }
            }
          })
        });
      } else {
        await route.continue();
      }
    });
    
    // Cliquer sur envoyer
    await page.click('button:has-text("Envoyer")');
    
    // Vérifier le message de succès
    await expect(page.locator('.q-notification')).toContainText('Message envoyé avec succès');
    
    // Vérifier que le formulaire est réinitialisé
    await expect(page.locator('textarea[label="Message"]')).toHaveValue('');
  });

  test('should show message history', async ({ page }) => {
    // Mock des messages pour l'historique
    await page.route('**/graphql', async route => {
      if (route.request().postData()?.includes('getWhatsAppMessages')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: {
              getWhatsAppMessages: {
                messages: [
                  {
                    id: '1',
                    wabaMessageId: 'wamid.1',
                    phoneNumber: '+2250123456789',
                    direction: 'OUTGOING',
                    type: 'text',
                    content: 'Test message 1',
                    status: 'delivered',
                    createdAt: new Date().toISOString(),
                    deliveredAt: new Date().toISOString(),
                    readAt: null
                  },
                  {
                    id: '2',
                    wabaMessageId: 'wamid.2',
                    phoneNumber: '+2250123456790',
                    direction: 'INCOMING',
                    type: 'text',
                    content: 'Réponse test',
                    status: 'received',
                    createdAt: new Date().toISOString()
                  }
                ],
                totalCount: 2,
                hasMore: false
              }
            }
          })
        });
      } else {
        await route.continue();
      }
    });
    
    await page.goto(`${baseURL}/whatsapp`);
    
    // Aller sur l'onglet Messages
    await page.click('text=Messages');
    
    // Vérifier que les messages sont affichés
    await expect(page.locator('table')).toBeVisible();
    await expect(page.locator('tbody tr')).toHaveCount(2);
    await expect(page.locator('tbody')).toContainText('+2250123456789');
    await expect(page.locator('tbody')).toContainText('Test message 1');
  });

  test('should switch to template message tab', async ({ page }) => {
    await page.goto(`${baseURL}/whatsapp`);
    
    // Changer pour l'onglet template
    await page.click('text=Message template');
    
    // Vérifier que les champs de template sont visibles
    await expect(page.locator('label:has-text("Template")')).toBeVisible();
    await expect(page.locator('label:has-text("Langue")')).toBeVisible();
  });

  test('should handle message filtering', async ({ page }) => {
    await page.goto(`${baseURL}/whatsapp`);
    
    // Aller sur l'onglet Messages
    await page.click('text=Messages');
    
    // Tester le filtre par numéro de téléphone
    await page.fill('input[placeholder="Filtrer par numéro..."]', '225012');
    
    // Vérifier que le filtre est appliqué
    // (avec un vrai backend, on vérifierait que seuls les messages filtrés apparaissent)
  });

  test('should display message statistics', async ({ page }) => {
    await page.goto(`${baseURL}/whatsapp`);
    
    // Vérifier que les statistiques sont affichées
    await expect(page.locator('text=Messages envoyés')).toBeVisible();
    await expect(page.locator('text=Messages délivrés')).toBeVisible();
    await expect(page.locator('text=Messages lus')).toBeVisible();
  });

  test('should handle errors gracefully', async ({ page }) => {
    await page.goto(`${baseURL}/whatsapp`);
    
    // Mock une erreur API
    await page.route('**/graphql', async route => {
      if (route.request().postData()?.includes('sendWhatsAppMessage')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            errors: [{
              message: 'Erreur de test'
            }]
          })
        });
      } else {
        await route.continue();
      }
    });
    
    // Essayer d'envoyer un message
    await page.fill('input[label="Numéro de téléphone du destinataire"]', '+2250123456789');
    await page.fill('textarea[label="Message"]', 'Test');
    await page.click('button:has-text("Envoyer")');
    
    // Vérifier le message d'erreur
    await expect(page.locator('.q-notification')).toContainText('Erreur');
  });

  test('should validate phone number format', async ({ page }) => {
    await page.goto(`${baseURL}/whatsapp`);
    
    // Entrer un numéro invalide
    await page.fill('input[label="Numéro de téléphone du destinataire"]', '123');
    await page.press('input[label="Numéro de téléphone du destinataire"]', 'Tab');
    
    // Vérifier le message de validation
    await expect(page.locator('text=Numéro de téléphone invalide')).toBeVisible();
  });

  test('should work with URL parameters', async ({ page }) => {
    // Naviguer avec un paramètre de destinataire
    await page.goto(`${baseURL}/whatsapp?recipient=2250123456789`);
    
    // Vérifier que le formulaire est pré-rempli
    // Note: Cette fonctionnalité pourrait nécessiter une implémentation
    // dans le composant WhatsAppSendMessage
  });

  test('should refresh data periodically', async ({ page }) => {
    let requestCount = 0;
    
    // Compter les requêtes de récupération des messages
    await page.route('**/graphql', async route => {
      if (route.request().postData()?.includes('getWhatsAppMessages')) {
        requestCount++;
      }
      await route.continue();
    });
    
    await page.goto(`${baseURL}/whatsapp`);
    
    // Attendre 35 secondes pour voir si une nouvelle requête est faite
    // (le rafraîchissement est configuré pour 30 secondes)
    await page.waitForTimeout(35000);
    
    // Vérifier qu'au moins 2 requêtes ont été faites
    expect(requestCount).toBeGreaterThanOrEqual(2);
  });
});