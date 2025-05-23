/**
 * Tests unitaires pour le service whatsAppClientV2
 */

import { whatsAppClientV2 } from '@/services/whatsappRestClientV2';
import { mockTemplates } from '../../mocks/whatsappTemplates';
import { mockApiResponses } from '../../mocks/axiosMocks';
import { HeaderFormat } from '@/types/whatsapp-templates';

// Mock des dépendances externes
jest.mock('@/services/whatsappApiClient', () => ({
  whatsappApi: {
    get: jest.fn(),
    post: jest.fn()
  }
}));

// Importer le mock après la déclaration du jest.mock
import { whatsappApi } from '@/services/whatsappApiClient';

describe('WhatsAppRestClientV2', () => {
  
  // Réinitialiser les mocks avant chaque test
  beforeEach(() => {
    whatsappApi.get.mockReset();
    whatsappApi.post.mockReset();
  });
  
  describe('getApprovedTemplates', () => {
    it('should fetch approved templates successfully', async () => {
      // Configurer le mock pour retourner des templates
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.approvedTemplates
      });
      
      const result = await whatsAppClientV2.getApprovedTemplates();
      
      // Vérifier que l'API a été appelée
      expect(whatsappApi.get).toHaveBeenCalled();
      
      // Vérifier la structure de la réponse
      expect(result.status).toBe('success');
      expect(result.templates.length).toBe(2);
      expect(result.count).toBe(2);
      expect(result.meta.source).toBe('api');
    });
    
    it('should handle API errors gracefully', async () => {
      // Configurer le mock pour simuler une erreur
      whatsappApi.get.mockRejectedValueOnce(new Error('Network Error'));
      
      const result = await whatsAppClientV2.getApprovedTemplates();
      
      // Vérifier que l'API a été appelée
      expect(whatsappApi.get).toHaveBeenCalled();
      
      // Vérifier que la réponse d'erreur est formatée correctement
      expect(result.status).toBe('error');
      expect(result.templates.length).toBe(0);
      expect(result.meta.source).toBe('client_error');
      expect(result.message).toBe('Network Error');
    });
    
    it('should apply filters correctly', async () => {
      // Configurer le mock
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.approvedTemplates
      });
      
      // Appeler avec des filtres
      await whatsAppClientV2.getApprovedTemplates({
        name: 'basic',
        language: 'fr',
        category: 'MARKETING'
      });
      
      // Vérifier que l'URL contient les paramètres de filtrage
      const calledUrl = whatsappApi.get.mock.calls[0][0];
      expect(calledUrl).toContain('name=basic');
      expect(calledUrl).toContain('language=fr');
      expect(calledUrl).toContain('category=MARKETING');
    });
  });
  
  describe('checkApiStatus', () => {
    it('should return online status when API is available', async () => {
      // Configurer le mock pour retourner un statut en ligne
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.apiStatus
      });
      
      const result = await whatsAppClientV2.checkApiStatus();
      
      // Vérifier la réponse
      expect(result.success).toBe(true);
      expect(result.status).toBe('online');
    });
    
    it('should handle API status check errors', async () => {
      // Configurer le mock pour simuler une erreur
      whatsappApi.get.mockRejectedValueOnce(new Error('Connection refused'));
      
      const result = await whatsAppClientV2.checkApiStatus();
      
      // Vérifier la réponse d'erreur
      expect(result.success).toBe(false);
      expect(result.error).toBe('Connection refused');
    });
  });
  
  describe('sendTemplateMessageMeta', () => {
    it('should send template message successfully', async () => {
      // Configurer les mocks pour le statut et l'envoi
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.apiStatus
      });
      
      whatsappApi.post.mockResolvedValueOnce({
        data: mockApiResponses.messageSendSuccess
      });
      
      // Préparer les données du message
      const templateMessage = {
        messaging_product: 'whatsapp',
        to: '+2250700000000',
        type: 'template',
        template: {
          name: 'basic_template',
          language: {
            code: 'fr'
          },
          components: [
            {
              type: 'body',
              parameters: [
                {
                  type: 'text',
                  text: 'John Doe'
                },
                {
                  type: 'text',
                  text: '25/12/2025'
                }
              ]
            }
          ]
        }
      };
      
      const result = await whatsAppClientV2.sendTemplateMessageMeta(templateMessage);
      
      // Vérifier que l'API a été appelée correctement
      expect(whatsappApi.get).toHaveBeenCalled();
      expect(whatsappApi.post).toHaveBeenCalled();
      
      // Vérifier la réponse
      expect(result.success).toBe(true);
      expect(result.messageId).toBe('wamid.123456789');
    });
    
    it('should handle validation errors', async () => {
      // Simuler que l'API est disponible
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.apiStatus
      });
      
      // Message invalide (numéro manquant)
      const invalidMessage = {
        messaging_product: 'whatsapp',
        // 'to' missing
        type: 'template',
        template: {
          name: 'basic_template',
          language: {
            code: 'fr'
          }
        }
      };
      
      // Les validations sont faites côté client avant l'appel API
      await expect(whatsAppClientV2.sendTemplateMessageMeta(invalidMessage)).rejects.toThrow();
    });
    
    it('should handle API unavailable', async () => {
      // Simuler que l'API est indisponible
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.apiStatusOffline
      });
      
      const templateMessage = {
        messaging_product: 'whatsapp',
        to: '+2250700000000',
        type: 'template',
        template: {
          name: 'basic_template',
          language: {
            code: 'fr'
          }
        }
      };
      
      const result = await whatsAppClientV2.sendTemplateMessageMeta(templateMessage);
      
      // Vérifier que la réponse indique un échec
      expect(result.success).toBe(false);
      expect(result.error).toContain('API REST non disponible');
    });
    
    it('should handle API errors during send', async () => {
      // Simuler que l'API est disponible
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.apiStatus
      });
      
      // Simuler une erreur lors de l'envoi
      whatsappApi.post.mockResolvedValueOnce({
        data: mockApiResponses.messageSendError
      });
      
      const templateMessage = {
        messaging_product: 'whatsapp',
        to: '+2250700000000',
        type: 'template',
        template: {
          name: 'invalid_template_name',
          language: {
            code: 'fr'
          }
        }
      };
      
      const result = await whatsAppClientV2.sendTemplateMessageMeta(templateMessage);
      
      // Vérifier que la réponse indique un échec avec le message d'erreur
      expect(result.success).toBe(false);
      expect(result.error).toContain('Failed to send template message');
    });
  });
  
  describe('sendTemplate', () => {
    it('should send template with body variables and header media', async () => {
      // Simuler que l'API est disponible
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.apiStatus
      });
      
      // Simuler une réponse de succès
      whatsappApi.post.mockResolvedValueOnce({
        data: mockApiResponses.messageSendSuccess
      });
      
      const result = await whatsAppClientV2.sendTemplate(
        '+2250700000000',
        mockTemplates.imageHeaderTemplate,
        ['Premium', '15€'],
        { 
          type: HeaderFormat.IMAGE, 
          value: 'https://example.com/image.jpg' 
        }
      );
      
      // Vérifier la structure de la requête envoyée
      const sentData = whatsappApi.post.mock.calls[0][1];
      
      // Vérifier le format du message
      expect(sentData.messaging_product).toBe('whatsapp');
      expect(sentData.to).toBe('+2250700000000');
      expect(sentData.type).toBe('template');
      expect(sentData.template.name).toBe(mockTemplates.imageHeaderTemplate.name);
      
      // Vérifier le composant d'en-tête
      const headerComponent = sentData.template.components.find(c => c.type === 'header');
      expect(headerComponent).toBeDefined();
      expect(headerComponent.parameters[0].type).toBe('image');
      expect(headerComponent.parameters[0].image.link).toBe('https://example.com/image.jpg');
      
      // Vérifier le composant de corps
      const bodyComponent = sentData.template.components.find(c => c.type === 'body');
      expect(bodyComponent).toBeDefined();
      expect(bodyComponent.parameters.length).toBe(2);
      expect(bodyComponent.parameters[0].text).toBe('Premium');
      
      // Vérifier la réponse
      expect(result.success).toBe(true);
      expect(result.messageId).toBe('wamid.123456789');
    });
  });
  
  describe('sendTemplateMessageV2', () => {
    it('should provide backward compatibility with the old API format', async () => {
      // Simuler que l'API est disponible
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.apiStatus
      });
      
      // Simuler une réponse de succès
      whatsappApi.post.mockResolvedValueOnce({
        data: mockApiResponses.messageSendSuccess
      });
      
      // Données au format ancien
      const oldFormatData = {
        recipientPhoneNumber: '+2250700000000',
        templateName: 'basic_template',
        templateLanguage: 'fr',
        bodyVariables: ['John Doe', '25/12/2025'],
        headerMediaUrl: 'https://example.com/image.jpg'
      };
      
      const result = await whatsAppClientV2.sendTemplateMessageV2(oldFormatData);
      
      // Vérifier que l'API a été appelée correctement
      expect(whatsappApi.post).toHaveBeenCalled();
      
      // Vérifier que le format de la requête est le nouveau format
      const sentData = whatsappApi.post.mock.calls[0][1];
      expect(sentData.messaging_product).toBe('whatsapp');
      expect(sentData.template.name).toBe('basic_template');
      
      // Vérifier la réponse
      expect(result.success).toBe(true);
      expect(result.messageId).toBe('wamid.123456789');
    });
    
    it('should handle media IDs in the old format', async () => {
      // Simuler que l'API est disponible
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.apiStatus
      });
      
      // Simuler une réponse de succès
      whatsappApi.post.mockResolvedValueOnce({
        data: mockApiResponses.messageSendSuccess
      });
      
      // Données au format ancien avec ID média
      const oldFormatData = {
        recipientPhoneNumber: '+2250700000000',
        templateName: 'image_header_template',
        templateLanguage: 'fr',
        bodyVariables: ['Premium', '15€'],
        headerMediaId: '123456789'
      };
      
      await whatsAppClientV2.sendTemplateMessageV2(oldFormatData);
      
      // Vérifier que le format de la requête est le nouveau format
      const sentData = whatsappApi.post.mock.calls[0][1];
      
      // Vérifier le composant d'en-tête avec ID
      const headerComponent = sentData.template.components.find(c => c.type === 'header');
      expect(headerComponent).toBeDefined();
      expect(headerComponent.parameters[0].type).toContain('image');
      expect(headerComponent.parameters[0].image.id).toBe('123456789');
    });
  });
  
  describe('isValidPhoneNumber and isValidUrl', () => {
    // Ces méthodes sont privées, nous les testons indirectement via sendTemplateMessageMeta
    
    it('should reject invalid phone numbers', async () => {
      // Simuler que l'API est disponible
      whatsappApi.get.mockResolvedValueOnce({
        data: mockApiResponses.apiStatus
      });
      
      // Message avec numéro invalide
      const invalidMessage = {
        messaging_product: 'whatsapp',
        to: 'invalid-phone',
        type: 'template',
        template: {
          name: 'basic_template',
          language: {
            code: 'fr'
          }
        }
      };
      
      // Les validations sont faites côté client avant l'appel API
      await expect(whatsAppClientV2.sendTemplateMessageMeta(invalidMessage)).rejects.toThrow();
    });
    
    it('should reject invalid URLs in header media', async () => {
      // Message avec URL invalide
      const templateMessage = {
        messaging_product: 'whatsapp',
        to: '+2250700000000',
        type: 'template',
        template: {
          name: 'image_header_template',
          language: {
            code: 'fr'
          },
          components: [
            {
              type: 'header',
              parameters: [
                {
                  type: 'image',
                  image: {
                    link: 'invalid-url'
                  }
                }
              ]
            }
          ]
        }
      };
      
      // Les validations URL sont fait par validateTemplateMessage qui est appelé par sendTemplateMessageMeta
      await expect(whatsAppClientV2.sendTemplateMessageMeta(templateMessage)).rejects.toThrow();
    });
  });
});