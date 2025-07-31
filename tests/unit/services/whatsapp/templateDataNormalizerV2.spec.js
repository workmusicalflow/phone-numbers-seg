/**
 * Tests unitaires pour le service templateDataNormalizerV2
 */

import { templateDataNormalizerV2 } from '@/services/whatsapp/templateDataNormalizerV2';
import { mockTemplates, expectedApiFormats } from '../../mocks/whatsappTemplates';
import { mockAnalysisResults } from '../../mocks/whatsappAnalysisResults';
import { HeaderFormat } from '@/types/whatsapp-templates';

describe('TemplateDataNormalizerV2', () => {

  describe('createTemplateData', () => {
    it('should create template data from analysis result', () => {
      const templateData = templateDataNormalizerV2.createTemplateData(
        mockTemplates.basicTemplate,
        mockAnalysisResults.basicTemplateResult,
        '+2250700000000'
      );
      
      // Vérifier la structure
      expect(templateData).toHaveProperty('recipientPhoneNumber');
      expect(templateData).toHaveProperty('template');
      expect(templateData).toHaveProperty('bodyVariables');
      expect(templateData).toHaveProperty('buttonVariables');
      expect(templateData).toHaveProperty('headerMediaType');
      
      // Vérifier les valeurs
      expect(templateData.recipientPhoneNumber).toBe('+2250700000000');
      expect(templateData.template).toBe(mockTemplates.basicTemplate);
      expect(templateData.bodyVariables).toEqual(mockAnalysisResults.basicTemplateResult.bodyVariables);
      expect(templateData.headerMediaType).toBe(HeaderFormat.NONE);
    });
    
    it('should create template data for image header template', () => {
      const templateData = templateDataNormalizerV2.createTemplateData(
        mockTemplates.imageHeaderTemplate,
        mockAnalysisResults.imageHeaderTemplateResult,
        '+2250700000000'
      );
      
      expect(templateData.headerMediaType).toBe(HeaderFormat.IMAGE);
      expect(templateData.bodyVariables.length).toBe(2);
    });
    
    it('should handle empty analysis results', () => {
      const emptyResult = {
        bodyVariables: [],
        buttonVariables: [],
        headerMedia: { type: HeaderFormat.NONE },
        hasFooter: false,
        errors: [],
        warnings: []
      };
      
      const templateData = templateDataNormalizerV2.createTemplateData(
        mockTemplates.basicTemplate,
        emptyResult,
        '+2250700000000'
      );
      
      expect(templateData.bodyVariables.length).toBe(0);
      expect(templateData.buttonVariables.length).toBe(0);
    });
  });
  
  describe('prepareTemplateMessage', () => {
    it('should prepare a basic template message for the API', () => {
      const message = templateDataNormalizerV2.prepareTemplateMessage(
        '+2250700000000',
        mockTemplates.basicTemplate,
        ['John Doe', '25/12/2025']
      );
      
      // Vérifier la structure du message
      expect(message).toHaveProperty('messaging_product');
      expect(message).toHaveProperty('to');
      expect(message).toHaveProperty('type');
      expect(message).toHaveProperty('template');
      
      // Vérifier les valeurs
      expect(message.messaging_product).toBe('whatsapp');
      expect(message.to).toBe('+2250700000000');
      expect(message.type).toBe('template');
      expect(message.template.name).toBe(mockTemplates.basicTemplate.name);
      expect(message.template.language.code).toBe(mockTemplates.basicTemplate.language);
      
      // Vérifier les composants
      expect(message.template.components).toBeDefined();
      const bodyComponent = message.template.components.find(c => c.type === 'body');
      expect(bodyComponent).toBeDefined();
      expect(bodyComponent.parameters.length).toBe(2);
      expect(bodyComponent.parameters[0].type).toBe('text');
      expect(bodyComponent.parameters[0].text).toBe('John Doe');
    });
    
    it('should prepare a template message with image header', () => {
      const message = templateDataNormalizerV2.prepareTemplateMessage(
        '+2250700000000',
        mockTemplates.imageHeaderTemplate,
        ['Premium', '15€'],
        { 
          type: HeaderFormat.IMAGE, 
          value: 'https://example.com/image.jpg' 
        }
      );
      
      // Vérifier la structure et les valeurs
      expect(message.template.components.length).toBeGreaterThan(1);
      
      // Vérifier le composant d'en-tête
      const headerComponent = message.template.components.find(c => c.type === 'header');
      expect(headerComponent).toBeDefined();
      expect(headerComponent.parameters[0].type).toBe('image');
      expect(headerComponent.parameters[0].image.link).toBe('https://example.com/image.jpg');
      
      // Vérifier le composant de corps
      const bodyComponent = message.template.components.find(c => c.type === 'body');
      expect(bodyComponent).toBeDefined();
      expect(bodyComponent.parameters.length).toBe(2);
    });
    
    it('should prepare a template message with media ID', () => {
      const message = templateDataNormalizerV2.prepareTemplateMessage(
        '+2250700000000',
        mockTemplates.imageHeaderTemplate,
        ['Standard', '10€'],
        { 
          type: HeaderFormat.IMAGE, 
          value: '123456789', 
          isId: true 
        }
      );
      
      // Vérifier le composant d'en-tête avec ID
      const headerComponent = message.template.components.find(c => c.type === 'header');
      expect(headerComponent).toBeDefined();
      expect(headerComponent.parameters[0].type).toBe('image');
      expect(headerComponent.parameters[0].image.id).toBe('123456789');
      expect(headerComponent.parameters[0].image.link).toBeUndefined();
    });
    
    it('should normalize phone numbers correctly', () => {
      // Format avec espaces et sans +
      const message = templateDataNormalizerV2.prepareTemplateMessage(
        '225 07 00 00 00 00',
        mockTemplates.basicTemplate,
        ['John Doe']
      );
      
      expect(message.to).toBe('+2250700000000');
    });
    
    it('should skip components for empty body values', () => {
      const message = templateDataNormalizerV2.prepareTemplateMessage(
        '+2250700000000',
        mockTemplates.basicTemplate,
        []
      );
      
      // Vérifier que les composants sont absents ou vides
      if (message.template.components) {
        const bodyComponent = message.template.components.find(c => c.type === 'body');
        expect(bodyComponent).toBeUndefined();
      }
    });
  });
  
  describe('convertFormValuesToParameters', () => {
    it('should convert body variables to string array', () => {
      const bodyVariables = [
        { index: 1, type: 'text', value: 'John Doe' },
        { index: 2, type: 'date', value: '25/12/2025' }
      ];
      
      const { bodyValues } = templateDataNormalizerV2.convertFormValuesToParameters(
        bodyVariables
      );
      
      expect(bodyValues).toEqual(['John Doe', '25/12/2025']);
    });
    
    it('should process header media from URL', () => {
      const headerMedia = { 
        type: HeaderFormat.IMAGE, 
        value: 'http://example.com/image.jpg'  // HTTP
      };
      
      const { headerMedia: processedMedia } = templateDataNormalizerV2.convertFormValuesToParameters(
        [],
        headerMedia
      );
      
      // Vérifier que HTTP est converti en HTTPS
      expect(processedMedia.value).toBe('https://example.com/image.jpg');
    });
    
    it('should handle undefined values gracefully', () => {
      const result = templateDataNormalizerV2.convertFormValuesToParameters(
        undefined,
        undefined
      );
      
      expect(result.bodyValues).toBeDefined();
      expect(result.bodyValues.length).toBe(0);
      expect(result.headerMedia).toBeUndefined();
    });
  });
});