<template>
  <div class="whatsapp-template-selector">
    <div class="template-selector-header">
      <h3>Sélection de template</h3>
      <q-btn
        flat
        color="primary"
        icon="refresh"
        @click="loadTemplates"
        :loading="loading"
        size="sm"
      />
    </div>

    <div class="filters-container q-mb-md">
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-4">
          <q-input
            outlined
            dense
            v-model="filters.search"
            label="Rechercher un template"
            clearable
            @update:model-value="filterTemplates"
          >
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>
        <div class="col-12 col-md-4">
          <q-select
            outlined
            dense
            v-model="filters.category"
            :options="categoryOptions"
            label="Catégorie"
            emit-value
            map-options
            clearable
            @update:model-value="filterTemplates"
          />
        </div>
        <div class="col-12 col-md-4">
          <q-select
            outlined
            dense
            v-model="filters.language"
            :options="languageOptions"
            label="Langue"
            emit-value
            map-options
            clearable
            @update:model-value="filterTemplates"
          />
        </div>
      </div>
    </div>

    <q-card
      v-if="!selectedTemplate"
      class="template-list-container"
      flat
      bordered
    >
      <q-card-section>
        <div v-if="loading" class="text-center q-pa-md">
          <q-spinner color="primary" size="3em" />
          <p>Chargement des templates...</p>
        </div>

        <div v-else-if="filteredTemplates.length === 0" class="text-center q-pa-md">
          <q-icon name="info" color="grey" size="3em" />
          <p>Aucun template trouvé. Veuillez ajuster vos filtres ou vérifier que des templates sont disponibles dans votre compte WhatsApp Business.</p>
        </div>

        <q-list v-else separator>
          <q-item
            v-for="template in filteredTemplates"
            :key="template.id"
            clickable
            v-ripple
            @click="selectTemplate(template)"
          >
            <q-item-section>
              <q-item-label>{{ template.name }}</q-item-label>
              <q-item-label caption>
                {{ template.description }}
              </q-item-label>
              <div class="template-details q-mt-xs">
                <q-badge outline :color="getCategoryColor(template.category)">
                  {{ template.category }}
                </q-badge>
                <q-badge outline color="grey" class="q-ml-sm">
                  {{ template.language }}
                </q-badge>
                <q-badge
                  v-if="template.hasMediaHeader"
                  outline
                  color="purple"
                  class="q-ml-sm"
                >
                  {{ template.headerType }}
                </q-badge>
                <q-badge
                  v-if="template.bodyVariablesCount > 0"
                  outline
                  color="blue"
                  class="q-ml-sm"
                >
                  {{ template.bodyVariablesCount }} variable(s)
                </q-badge>
                <q-badge
                  v-if="template.hasButtons"
                  outline
                  color="orange"
                  class="q-ml-sm"
                >
                  {{ template.buttonsCount }} bouton(s)
                </q-badge>
              </div>
            </q-item-section>
            <q-item-section side>
              <q-btn
                color="primary"
                flat
                round
                icon="arrow_forward"
              />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>
    </q-card>

    <div v-else class="template-config-container">
      <div class="template-header q-mb-md">
        <div>
          <h4 class="q-mt-none q-mb-xs">{{ selectedTemplate.name }}</h4>
          <div class="template-badges">
            <q-badge outline :color="getCategoryColor(selectedTemplate.category)">
              {{ selectedTemplate.category }}
            </q-badge>
            <q-badge outline color="grey" class="q-ml-sm">
              {{ selectedTemplate.language }}
            </q-badge>
          </div>
        </div>
        <q-btn
          outline
          color="primary"
          label="Changer de template"
          icon="arrow_back"
          @click="selectedTemplate = null"
        />
      </div>

      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Configuration du template</div>
          
          <!-- Header Media (si applicable) -->
          <div 
            v-if="templateComponents.header && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(templateComponents.header.format)"
            class="q-mt-md"
          >
            <div class="text-subtitle2">En-tête {{ templateComponents.header.format }}</div>
            <q-input
              outlined
              v-model="headerMediaUrl"
              :label="`URL du média (${templateComponents.header.format.toLowerCase()})`"
              hint="URL HTTPS vers l'image, vidéo ou document"
              class="q-mt-xs"
              :rules="[val => val.startsWith('https://') || 'L\'URL doit commencer par https://']"
            />
          </div>

          <!-- Variables de corps -->
          <div v-if="bodyVariables.length > 0" class="q-mt-md">
            <div class="text-subtitle2">Variables du corps</div>
            <div 
              v-for="(variable, index) in bodyVariables" 
              :key="`body-var-${index}`"
              class="q-mt-sm"
            >
              <q-input
                outlined
                v-model="bodyVariables[index].value"
                :label="`Variable {{${index + 1}}}`"
                :hint="getVariableHint(index)"
              />
            </div>
          </div>

          <!-- Variables de boutons (si applicable) -->
          <div v-if="buttonVariables.length > 0" class="q-mt-md">
            <div class="text-subtitle2">Variables de boutons</div>
            <div
              v-for="(button, index) in buttonVariables"
              :key="`button-var-${index}`"
              class="q-mt-sm"
            >
              <q-input
                outlined
                v-model="buttonVariables[index].value"
                :label="`Variable pour bouton ${index + 1} (${button.type})`"
                :hint="button.type === 'URL' ? 'Partie variable de l\'URL' : 'Payload pour réponse rapide'"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-section>
          <div class="text-h6">Aperçu du message</div>
          <div class="message-preview q-mt-sm q-pa-md">
            <!-- Header preview -->
            <div v-if="templateComponents.header" class="preview-header q-mb-sm">
              <template v-if="templateComponents.header.format === 'TEXT'">
                <div class="preview-header-text">{{ templateComponents.header.text || 'Texte d\'en-tête' }}</div>
              </template>
              <template v-else-if="headerMediaUrl && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(templateComponents.header.format)">
                <div class="preview-header-media">
                  <div v-if="templateComponents.header.format === 'IMAGE'" class="image-placeholder">
                    <q-icon name="image" size="2rem" color="grey" />
                    <div class="text-caption">{{ getFilenameFromUrl(headerMediaUrl) }}</div>
                  </div>
                  <div v-else-if="templateComponents.header.format === 'VIDEO'" class="video-placeholder">
                    <q-icon name="videocam" size="2rem" color="grey" />
                    <div class="text-caption">{{ getFilenameFromUrl(headerMediaUrl) }}</div>
                  </div>
                  <div v-else-if="templateComponents.header.format === 'DOCUMENT'" class="document-placeholder">
                    <q-icon name="description" size="2rem" color="grey" />
                    <div class="text-caption">{{ getFilenameFromUrl(headerMediaUrl) }}</div>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="preview-header-placeholder">
                  En-tête {{ templateComponents.header.format }}
                </div>
              </template>
            </div>

            <!-- Body preview -->
            <div v-if="templateComponents.body" class="preview-body q-mb-sm">
              {{ getPreviewBodyText() }}
            </div>

            <!-- Footer preview -->
            <div v-if="templateComponents.footer" class="preview-footer text-caption text-grey q-mb-sm">
              {{ templateComponents.footer.text }}
            </div>

            <!-- Buttons preview -->
            <div v-if="templateComponents.buttons && templateComponents.buttons.buttons?.length > 0" class="preview-buttons">
              <div
                v-for="(button, index) in templateComponents.buttons.buttons"
                :key="`preview-button-${index}`"
                class="preview-button q-my-xs"
              >
                <q-btn
                  :outline="button.type === 'URL'"
                  :color="button.type === 'URL' ? 'primary' : 'grey'"
                  :label="button.text"
                  :icon="button.type === 'URL' ? 'open_in_new' : undefined"
                  :icon-right="button.type === 'URL' ? 'open_in_new' : undefined"
                  no-caps
                  class="full-width"
                  size="sm"
                />
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="negative" v-close-popup @click="cancelSelection" />
          <q-btn unelevated label="Utiliser ce template" color="primary" @click="useTemplate" />
        </q-card-actions>
      </q-card>
    </div>
  </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted, watch } from 'vue';
import { useQuasar } from 'quasar';

export default defineComponent({
  name: 'WhatsAppTemplateSelector',
  props: {
    recipientPhoneNumber: {
      type: String,
      required: true
    }
  },
  emits: ['template-selected', 'cancel'],
  setup(props, { emit }) {
    const $q = useQuasar();
    const loading = ref(false);
    const templates = ref([]);
    const selectedTemplate = ref(null);
    const templateComponents = ref({});
    const headerMediaUrl = ref('');
    const bodyVariables = ref([]);
    const buttonVariables = ref([]);
    const error = ref(null);

    // Filtres
    const filters = ref({
      search: '',
      category: null,
      language: null
    });

    // Options pour les filtres
    const categoryOptions = computed(() => {
      const categories = [...new Set(templates.value.map(t => t.category))];
      return categories.map(category => ({
        label: category,
        value: category
      }));
    });

    const languageOptions = computed(() => {
      const languages = [...new Set(templates.value.map(t => t.language))];
      return languages.map(language => ({
        label: language,
        value: language
      }));
    });

    // Templates filtrés
    const filteredTemplates = computed(() => {
      return templates.value.filter(template => {
        // Filtre par recherche
        if (filters.value.search && !template.name.toLowerCase().includes(filters.value.search.toLowerCase()) &&
            !template.description.toLowerCase().includes(filters.value.search.toLowerCase())) {
          return false;
        }
        
        // Filtre par catégorie
        if (filters.value.category && template.category !== filters.value.category) {
          return false;
        }
        
        // Filtre par langue
        if (filters.value.language && template.language !== filters.value.language) {
          return false;
        }
        
        return true;
      });
    });

    // Couleur selon la catégorie
    const getCategoryColor = (category) => {
      const colors = {
        'MARKETING': 'green',
        'UTILITY': 'blue',
        'AUTHENTICATION': 'orange',
        'ISSUE_RESOLUTION': 'red'
      };
      
      return colors[category] || 'grey';
    };

    // Charger les templates depuis l'API
    const loadTemplates = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await fetch('/graphql.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            query: `
              query GetWhatsAppTemplates {
                getWhatsAppUserTemplates {
                  id
                  template_id
                  name
                  language
                  status
                }
              }
            `
          }),
          credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.errors) {
          throw new Error(result.errors[0].message);
        }
        
        // Si nous recevons les templates, les adapter au format attendu par le composant
        const rawTemplates = result.data.getWhatsAppUserTemplates || [];
        templates.value = rawTemplates.map(template => ({
          id: template.id,
          name: template.name,
          language: template.language,
          status: template.status,
          category: 'UTILITY', // Par défaut si non spécifié
          description: `Template ${template.name}`,
          componentsJson: '{}', // Sera récupéré au besoin
          hasMediaHeader: false,
          headerType: 'TEXT',
          bodyVariablesCount: 0,
          hasButtons: false,
          buttonsCount: 0,
          hasFooter: false
        }));
        
        // Récupérer les détails des templates si nécessaire
        // Cette étape peut être implémentée dans une future version
        
        console.log("Templates récupérés avec succès:", templates.value);
      } catch (err) {
        console.error('Erreur lors du chargement des templates:', err);
        error.value = err.message;
        $q.notify({
          color: 'negative',
          position: 'top',
          message: `Erreur: ${err.message}`,
          icon: 'error'
        });
      } finally {
        loading.value = false;
      }
    };

    // Sélectionner un template
    const selectTemplate = (template) => {
      selectedTemplate.value = template;
      
      // Analyser les composants du template
      templateComponents.value = JSON.parse(template.componentsJson || '{}');
      
      // Réinitialiser les variables
      headerMediaUrl.value = '';
      
      // Préparer les variables du corps
      bodyVariables.value = [];
      if (template.bodyVariablesCount > 0) {
        for (let i = 0; i < template.bodyVariablesCount; i++) {
          bodyVariables.value.push({ 
            index: i + 1,
            value: ''
          });
        }
      }
      
      // Préparer les variables de boutons
      buttonVariables.value = [];
      if (templateComponents.value.buttons && templateComponents.value.buttons.buttons) {
        templateComponents.value.buttons.buttons.forEach((button, index) => {
          if (button.type === 'URL') {
            buttonVariables.value.push({
              index: index,
              type: 'URL',
              value: ''
            });
          } else if (button.type === 'QUICK_REPLY') {
            buttonVariables.value.push({
              index: index,
              type: 'QUICK_REPLY',
              value: ''
            });
          }
        });
      }
    };

    // Filtrer les templates
    const filterTemplates = () => {
      // La logique est déjà dans le computed filteredTemplates
    };

    // Obtenir un indice pour la variable
    const getVariableHint = (index) => {
      // Générer un exemple en fonction de l'index
      const examples = [
        'ex: John Doe',
        'ex: 25/12/2023',
        'ex: 29.99 €',
        'ex: 14h30',
        'ex: PROD123'
      ];
      
      return examples[index % examples.length];
    };

    // Extraire le nom de fichier d'une URL
    const getFilenameFromUrl = (url) => {
      if (!url) return '';
      
      try {
        const urlObj = new URL(url);
        const pathname = urlObj.pathname;
        return pathname.substring(pathname.lastIndexOf('/') + 1);
      } catch (e) {
        return url.substring(url.lastIndexOf('/') + 1);
      }
    };

    // Obtenir le texte du corps avec variables remplacées
    const getPreviewBodyText = () => {
      if (!templateComponents.value.body || !templateComponents.value.body.text) {
        return '';
      }
      
      let text = templateComponents.value.body.text;
      
      // Remplacer les variables {{N}} par les valeurs saisies
      bodyVariables.value.forEach(variable => {
        const placeholder = `{{${variable.index}}}`;
        const value = variable.value || `[Variable ${variable.index}]`;
        text = text.replace(placeholder, value);
      });
      
      return text;
    };

    // Annuler la sélection
    const cancelSelection = () => {
      selectedTemplate.value = null;
      emit('cancel');
    };

    // Utiliser le template sélectionné
    const useTemplate = () => {
      // Vérifier si le template a un header média et si l'URL est fournie
      if (
        templateComponents.value.header &&
        ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(templateComponents.value.header.format) &&
        !headerMediaUrl.value
      ) {
        $q.notify({
          color: 'warning',
          message: `L'URL du média d'en-tête est requise pour ce template.`,
          icon: 'warning'
        });
        return;
      }
      
      // Préparer les données du template
      const templateData = {
        template: selectedTemplate.value,
        recipientPhoneNumber: props.recipientPhoneNumber,
        components: templateComponents.value,
        templateComponentsJsonString: selectedTemplate.value.componentsJson,
        bodyVariables: bodyVariables.value.map(v => v.value),
        buttonVariables: buttonVariables.value.reduce((acc, v) => {
          acc[v.index] = v.value;
          return acc;
        }, {}),
        headerMediaUrl: headerMediaUrl.value
      };
      
      // Émettre l'événement avec les données
      emit('template-selected', templateData);
    };

    // Charger les templates au montage du composant
    onMounted(() => {
      console.log('[WhatsAppTemplateSelector] Composant monté');
      console.log('[WhatsAppTemplateSelector] Numéro de téléphone du destinataire:', props.recipientPhoneNumber);
      loadTemplates();
    });

    return {
      loading,
      templates,
      filteredTemplates,
      selectedTemplate,
      templateComponents,
      headerMediaUrl,
      bodyVariables,
      buttonVariables,
      filters,
      categoryOptions,
      languageOptions,
      error,
      
      loadTemplates,
      selectTemplate,
      filterTemplates,
      getCategoryColor,
      getVariableHint,
      getFilenameFromUrl,
      getPreviewBodyText,
      cancelSelection,
      useTemplate
    };
  }
});
</script>

<style scoped>
.whatsapp-template-selector {
  width: 100%;
}

.template-selector-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.template-list-container {
  max-height: 450px;
  overflow-y: auto;
}

.template-details {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.template-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.message-preview {
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #f8f8f8;
  padding: 12px;
  max-width: 350px;
  margin: 0 auto;
}

.preview-header-placeholder,
.image-placeholder,
.video-placeholder,
.document-placeholder {
  background-color: #e0e0e0;
  border-radius: 4px;
  padding: 12px;
  text-align: center;
  margin-bottom: 8px;
}

.preview-header-text {
  font-weight: bold;
  margin-bottom: 8px;
}

.preview-body {
  white-space: pre-line;
}

.preview-buttons {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 12px;
}
</style>