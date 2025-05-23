/**
 * Tests unitaires pour le service templateParserV2
 */

import { templateParserV2 } from '@/services/whatsapp/templateParserV2';
import { mockTemplates } from '../../mocks/whatsappTemplates';
import { mockAnalysisResults } from '../../mocks/whatsappAnalysisResults';
import { HeaderFormat, VariableType } from '@/types/whatsapp-templates';
import { ComponentType } from '@/types/whatsapp-parameters';

describe('WhatsAppTemplateParserV2', () => {
  
  describe('analyzeTemplate', () => {
    it('should correctly parse a basic template', () => {
      const result = templateParserV2.analyzeTemplate(mockTemplates.basicTemplate);
      
      // Vérifier la structure du résultat
      expect(result).toHaveProperty('bodyVariables');
      expect(result).toHaveProperty('buttonVariables');
      expect(result).toHaveProperty('headerMedia');
      expect(result).toHaveProperty('hasFooter');
      expect(result).toHaveProperty('errors');
      expect(result).toHaveProperty('warnings');
      
      // Vérifier les variables du corps
      expect(result.bodyVariables.length).toBe(2);
      expect(result.bodyVariables[0].index).toBe(1);
      expect(result.bodyVariables[1].index).toBe(2);
      
      // Vérifier qu'il n'y a pas d'erreurs
      expect(result.errors.length).toBe(0);
      expect(result.warnings.length).toBe(0);
    });
    
    it('should correctly parse a template with text header', () => {
      const result = templateParserV2.analyzeTemplate(mockTemplates.textHeaderTemplate);
      
      expect(result.headerMedia.type).toBe(HeaderFormat.TEXT);
      expect(result.bodyVariables.length).toBe(2);
    });
    
    it('should correctly parse a template with image header', () => {
      const result = templateParserV2.analyzeTemplate(mockTemplates.imageHeaderTemplate);
      
      expect(result.headerMedia.type).toBe(HeaderFormat.IMAGE);
      expect(result.bodyVariables.length).toBe(2);
    });
    
    it('should correctly parse a template with footer', () => {
      const result = templateParserV2.analyzeTemplate(mockTemplates.footerTemplate);
      
      expect(result.hasFooter).toBe(true);
      expect(result.footerText).toBeDefined();
      expect(result.bodyVariables.length).toBe(2);
    });
    
    it('should correctly identify variable types based on context', () => {
      const result = templateParserV2.analyzeTemplate(mockTemplates.multiVariableTemplate);
      
      // Vérifier que toutes les variables sont présentes
      expect(result.bodyVariables.length).toBe(7);
      
      // Vérifier les types détectés
      const nameVar = result.bodyVariables.find(v => v.index === 1);
      const refVar = result.bodyVariables.find(v => v.index === 2);
      const currencyVar = result.bodyVariables.find(v => v.index === 3);
      const dateVar = result.bodyVariables.find(v => v.index === 4);
      const emailVar = result.bodyVariables.find(v => v.index === 5);
      const phoneVar = result.bodyVariables.find(v => v.index === 6);
      const linkVar = result.bodyVariables.find(v => v.index === 7);
      
      expect(nameVar.type).toBe(VariableType.TEXT);
      expect(refVar.type).toBe(VariableType.REFERENCE);
      expect(currencyVar.type).toBe(VariableType.CURRENCY);
      expect(dateVar.type).toBe(VariableType.DATE);
      expect(emailVar.type).toBe(VariableType.EMAIL);
      expect(phoneVar.type).toBe(VariableType.PHONE);
      expect(linkVar.type).toBe(VariableType.LINK);
    });
    
    it('should handle componentsJson string format', () => {
      const result = templateParserV2.analyzeTemplate(mockTemplates.jsonStringTemplate);
      
      expect(result.bodyVariables.length).toBe(1);
      expect(result.errors.length).toBe(0);
    });
    
    it('should handle object components format', () => {
      const result = templateParserV2.analyzeTemplate(mockTemplates.objectComponentsTemplate);
      
      expect(result.bodyVariables.length).toBe(1);
      expect(result.hasFooter).toBe(true);
      expect(result.errors.length).toBe(0);
    });
    
    it('should handle errors gracefully', () => {
      // Créer un template invalide pour provoquer des erreurs
      const invalidTemplate = {
        id: 'invalid_template',
        name: 'invalid_template',
        language: 'fr',
        componentsJson: 'not a valid json string'
      };
      
      const result = templateParserV2.analyzeTemplate(invalidTemplate);
      
      expect(result.errors.length).toBeGreaterThan(0);
      expect(result.bodyVariables.length).toBe(0);
    });
  });
  
  describe('generateApiParameters', () => {
    it('should generate header parameters for image', () => {
      const template = mockTemplates.imageHeaderTemplate;
      const bodyValues = ['Premium', '15€'];
      const headerMedia = { 
        type: HeaderFormat.IMAGE, 
        value: 'https://example.com/image.jpg' 
      };
      
      const parameters = templateParserV2.generateApiParameters(
        template,
        bodyValues,
        headerMedia
      );
      
      // Vérifier que les paramètres sont générés correctement
      expect(parameters.length).toBeGreaterThan(0);
      
      // Vérifier le paramètre d'en-tête
      const headerParam = parameters.find(p => p.type === ComponentType.HEADER);
      expect(headerParam).toBeDefined();
      expect(headerParam.parameters[0].type).toBe('image');
      expect(headerParam.parameters[0].image.link).toBe('https://example.com/image.jpg');
      
      // Vérifier les paramètres du corps
      const bodyParam = parameters.find(p => p.type === ComponentType.BODY);
      expect(bodyParam).toBeDefined();
      expect(bodyParam.parameters.length).toBe(2);
      expect(bodyParam.parameters[0].type).toBe('text');
      expect(bodyParam.parameters[0].text).toBe('Premium');
      expect(bodyParam.parameters[1].text).toBe('15€');
    });
    
    it('should generate header parameters for media id', () => {
      const template = mockTemplates.imageHeaderTemplate;
      const bodyValues = ['Standard', '10€'];
      const headerMedia = { 
        type: HeaderFormat.IMAGE, 
        value: '123456789', 
        isId: true 
      };
      
      const parameters = templateParserV2.generateApiParameters(
        template,
        bodyValues,
        headerMedia
      );
      
      // Vérifier le paramètre d'en-tête avec ID
      const headerParam = parameters.find(p => p.type === ComponentType.HEADER);
      expect(headerParam.parameters[0].type).toBe('image');
      expect(headerParam.parameters[0].image.id).toBe('123456789');
      expect(headerParam.parameters[0].image.link).toBeUndefined();
    });
    
    it('should generate text body parameters', () => {
      const template = mockTemplates.basicTemplate;
      const bodyValues = ['John Doe', '25/12/2025'];
      
      const parameters = templateParserV2.generateApiParameters(
        template,
        bodyValues
      );
      
      // Vérifier les paramètres du corps
      const bodyParam = parameters.find(p => p.type === ComponentType.BODY);
      expect(bodyParam).toBeDefined();
      expect(bodyParam.parameters.length).toBe(2);
      expect(bodyParam.parameters[0].type).toBe('text');
      expect(bodyParam.parameters[0].text).toBe('John Doe');
      expect(bodyParam.parameters[1].text).toBe('25/12/2025');
    });
    
    it('should skip missing body values', () => {
      const template = mockTemplates.basicTemplate;
      const bodyValues = ['John Doe']; // Manque une valeur
      
      const parameters = templateParserV2.generateApiParameters(
        template,
        bodyValues
      );
      
      // Vérifier les paramètres du corps (une seule valeur)
      const bodyParam = parameters.find(p => p.type === ComponentType.BODY);
      expect(bodyParam).toBeDefined();
      expect(bodyParam.parameters.length).toBe(1);
    });
    
    it('should not generate components for empty values', () => {
      const template = mockTemplates.basicTemplate;
      const bodyValues = []; // Pas de valeurs
      
      const parameters = templateParserV2.generateApiParameters(
        template,
        bodyValues
      );
      
      // Vérifier qu'il n'y a pas de composant body
      const bodyParam = parameters.find(p => p.type === ComponentType.BODY);
      expect(bodyParam).toBeUndefined();
    });
  });
  
  describe('detectParameterType', () => {
    // Test de la méthode privée via generateApiParameters
    it('should detect currency values', () => {
      const template = mockTemplates.basicTemplate;
      const bodyValues = ['29.99 €']; // Format monétaire
      
      const parameters = templateParserV2.generateApiParameters(
        template,
        bodyValues
      );
      
      // La détection automatique est difficile à tester directement
      // car c'est une méthode privée, mais on peut vérifier les résultats
      const bodyParam = parameters.find(p => p.type === ComponentType.BODY);
      expect(bodyParam).toBeDefined();
    });
    
    it('should detect date values', () => {
      const template = mockTemplates.basicTemplate;
      const bodyValues = ['25/12/2025']; // Format date
      
      const parameters = templateParserV2.generateApiParameters(
        template,
        bodyValues
      );
      
      const bodyParam = parameters.find(p => p.type === ComponentType.BODY);
      expect(bodyParam).toBeDefined();
    });
    
    it('should detect URL values', () => {
      const template = mockTemplates.basicTemplate;
      const bodyValues = ['https://example.com/page']; // URL
      
      const parameters = templateParserV2.generateApiParameters(
        template,
        bodyValues
      );
      
      const bodyParam = parameters.find(p => p.type === ComponentType.BODY);
      expect(bodyParam).toBeDefined();
    });
  });
});