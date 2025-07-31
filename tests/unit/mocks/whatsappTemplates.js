/**
 * Mocks de templates WhatsApp pour les tests unitaires
 */

export const mockTemplates = {
  // Template basique avec juste un corps
  basicTemplate: {
    id: 'template_id_1',
    name: 'basic_template',
    category: 'MARKETING',
    language: 'fr',
    status: 'APPROVED',
    components: [
      {
        type: 'BODY',
        text: 'Bonjour {{1}}, votre rendez-vous est confirmé pour le {{2}}.'
      }
    ]
  },
  
  // Template avec en-tête texte
  textHeaderTemplate: {
    id: 'template_id_2',
    name: 'text_header_template',
    category: 'MARKETING',
    language: 'fr',
    status: 'APPROVED',
    components: [
      {
        type: 'HEADER',
        format: 'TEXT',
        text: 'Information importante'
      },
      {
        type: 'BODY',
        text: 'Bonjour {{1}}, nous vous informons que {{2}}.'
      }
    ]
  },
  
  // Template avec en-tête image
  imageHeaderTemplate: {
    id: 'template_id_3',
    name: 'image_header_template',
    category: 'MARKETING',
    language: 'fr',
    status: 'APPROVED',
    components: [
      {
        type: 'HEADER',
        format: 'IMAGE'
      },
      {
        type: 'BODY',
        text: 'Découvrez notre nouvelle offre {{1}} à partir de {{2}}!'
      }
    ]
  },
  
  // Template avec pied de page
  footerTemplate: {
    id: 'template_id_4',
    name: 'footer_template',
    category: 'UTILITY',
    language: 'fr',
    status: 'APPROVED',
    components: [
      {
        type: 'BODY',
        text: 'Bonjour {{1}}, nous confirmons votre commande {{2}}.'
      },
      {
        type: 'FOOTER',
        text: 'Ceci est un message automatique, merci de ne pas y répondre.'
      }
    ]
  },
  
  // Template avec multiples variables et différents contextes
  multiVariableTemplate: {
    id: 'template_id_5',
    name: 'multi_variable_template',
    category: 'UTILITY',
    language: 'fr',
    status: 'APPROVED',
    components: [
      {
        type: 'BODY',
        text: 'Bonjour {{1}}, votre commande n°{{2}} d\'un montant de {{3}} est prévue pour livraison à la date du {{4}}. Pour plus d\'informations, contactez-nous par email à {{5}} ou par téléphone au {{6}}. Suivez votre livraison sur notre site: {{7}}.'
      }
    ]
  },
  
  // Template avec en-tête document
  documentHeaderTemplate: {
    id: 'template_id_6',
    name: 'document_header_template',
    category: 'UTILITY',
    language: 'fr',
    status: 'APPROVED',
    components: [
      {
        type: 'HEADER',
        format: 'DOCUMENT'
      },
      {
        type: 'BODY',
        text: 'Bonjour {{1}}, veuillez trouver ci-joint votre {{2}}.'
      }
    ]
  },
  
  // Template au format JSON string
  jsonStringTemplate: {
    id: 'template_id_7',
    name: 'json_string_template',
    category: 'UTILITY',
    language: 'fr',
    status: 'APPROVED',
    componentsJson: JSON.stringify([
      {
        type: 'BODY',
        text: 'Bonjour {{1}}, message avec composants au format JSON string.'
      }
    ])
  },
  
  // Template avec format objet pour les composants au lieu d'un tableau
  objectComponentsTemplate: {
    id: 'template_id_8',
    name: 'object_components_template',
    category: 'UTILITY',
    language: 'fr',
    status: 'APPROVED',
    componentsJson: JSON.stringify({
      body: {
        text: 'Bonjour {{1}}, message avec composants au format objet.'
      },
      footer: {
        text: 'Pied de page'
      }
    })
  }
};

// Formats d'API attendus pour les différents templates
export const expectedApiFormats = {
  // Format API pour un template basique
  basicTemplateApi: {
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
  },
  
  // Format API pour un template avec image d'en-tête
  imageHeaderTemplateApi: {
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
                link: 'https://example.com/image.jpg'
              }
            }
          ]
        },
        {
          type: 'body',
          parameters: [
            {
              type: 'text',
              text: 'Premium'
            },
            {
              type: 'text',
              text: '15€'
            }
          ]
        }
      ]
    }
  }
};