/**
 * Mocks pour les réponses Axios
 */

export const mockApiResponses = {
  // Réponse pour les templates approuvés
  approvedTemplates: {
    status: 'success',
    templates: [
      {
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
      {
        id: 'template_id_2',
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
      }
    ],
    count: 2,
    meta: {
      source: 'api',
      usedFallback: false,
      timestamp: '2025-05-21T10:00:00.000Z'
    }
  },
  
  // Réponse pour l'envoi réussi d'un message
  messageSendSuccess: {
    success: true,
    messageId: 'wamid.123456789',
    timestamp: '2025-05-21T10:30:00.000Z'
  },
  
  // Réponse pour un échec d'envoi de message
  messageSendError: {
    success: false,
    error: 'Failed to send template message',
    errorCode: 'invalid_template'
  },
  
  // Réponse pour le statut de l'API
  apiStatus: {
    status: 'online',
    version: 'v2.0',
    timestamp: '2025-05-21T10:00:00.000Z'
  },
  
  // Réponse pour un statut d'API offline
  apiStatusOffline: {
    status: 'offline',
    message: 'API maintenance in progress',
    timestamp: '2025-05-21T10:00:00.000Z'
  },
  
  // Réponse pour une erreur de validation
  validationError: {
    success: false,
    error: 'Validation failed',
    validationErrors: [
      'Recipient phone number is invalid',
      'Template name is required'
    ]
  }
};