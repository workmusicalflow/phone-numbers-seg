<template>
  <q-card>
    <q-card-section>
      <div class="text-h6">
        Importer des numéros depuis un fichier CSV
      </div>
      <div class="text-caption q-mt-sm">
        Le fichier CSV peut contenir les colonnes suivantes : number
        (obligatoire), civility, firstName, name, company, sector, notes, email
      </div>
      <div class="text-caption q-mt-sm">
        <a href="#" @click.prevent="onDownloadTemplate">Télécharger un modèle CSV</a>
      </div>
    </q-card-section>

    <q-card-section>
      <div class="text-caption q-mb-md bg-blue-1 q-pa-sm rounded-borders">
        <p><strong>Guide d'utilisation :</strong></p>
        <ul class="q-mb-none">
          <li>Utilisez un fichier CSV avec délimiteur "{{ options.delimiter }}"</li>
          <li>Format de numéro recommandé : international (+XXX...)</li>
          <li>Taille maximale recommandée : 5000 lignes</li>
          <li>Sélectionnez les colonnes correspondantes ci-dessous</li>
        </ul>
      </div>
      
      <q-form ref="formRef" @submit="onSubmit" class="q-gutter-md">
        <q-file
          v-model="fileModel"
          label="Fichier CSV"
          accept=".csv"
          :rules="[(val) => !!val || 'Le fichier est requis']"
          outlined
        >
          <template v-slot:prepend>
            <q-icon name="attach_file" />
          </template>
        </q-file>

        <q-checkbox
          v-model="options.hasHeader"
          label="Le fichier contient une ligne d'en-tête"
        />

        <q-checkbox
          v-model="options.createContacts"
          label="Créer des contacts à partir des numéros importés"
          class="q-mt-sm"
        />


        <q-select
          v-if="options.createContacts"
          v-model="options.groupIds"
          :options="groupOptions"
          label="Affecter automatiquement aux groupes (optionnel)"
          outlined
          multiple
          emit-value
          map-options
          use-chips
          :loading="loadingGroups"
          class="q-mt-sm"
          hint="Sélectionnez un ou plusieurs groupes pour affecter automatiquement les contacts importés"
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">
                {{ loadingGroups ? 'Chargement des groupes...' : 'Aucun groupe disponible' }}
              </q-item-section>
            </q-item>
          </template>
          <template v-slot:before-options>
            <q-item>
              <q-item-section>
                <q-btn 
                  flat 
                  dense 
                  icon="refresh" 
                  label="Actualiser les groupes" 
                  @click="emit('refresh-groups')"
                  :loading="loadingGroups"
                />
              </q-item-section>
            </q-item>
          </template>
        </q-select>

        <q-input
          v-model="options.delimiter"
          label="Délimiteur"
          :rules="[(val) => !!val || 'Le délimiteur est requis']"
          maxlength="1"
        />

        <!-- Sélection des colonnes -->
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <q-select
              v-model="options.phoneColumn"
              :options="columnOptions"
              label="Colonne des numéros de téléphone *"
              outlined
              :rules="[val => val !== null || 'Cette colonne est requise']"
            />
          </div>
          
          <div class="col-12 col-md-6">
            <q-select
              v-model="options.nameColumn"
              :options="columnOptions"
              label="Colonne du nom (optionnel)"
              outlined
              clearable
            />
          </div>
          
          <div class="col-12 col-md-6">
            <q-select
              v-model="options.emailColumn"
              :options="columnOptions"
              label="Colonne de l'email (optionnel)"
              outlined
              clearable
            />
          </div>
          
          <div class="col-12 col-md-6">
            <q-select
              v-model="options.notesColumn"
              :options="columnOptions"
              label="Colonne des notes (optionnel)"
              outlined
              clearable
            />
          </div>
        </div>

        <div>
          <q-btn
            label="Importer"
            type="submit"
            color="primary"
            :loading="loading"
          />
          
          <!-- Indicateur de progression amélioré -->
          <div v-if="loading" class="q-mt-md">
            <q-linear-progress indeterminate />
            <div class="text-caption q-mt-sm">
              Traitement en cours... Cela peut prendre quelques minutes pour les fichiers volumineux.
              Veuillez ne pas fermer cette page.
            </div>
          </div>
        </div>
      </q-form>
    </q-card-section>
  </q-card>
</template>

<script setup lang="ts">
import { ref, watch, toRefs } from 'vue';
import { QForm } from 'quasar';
import { ImportOptions, ColumnOption, GroupOption } from './composables/useImport';

const props = defineProps<{
  options: ImportOptions;
  columnOptions: ColumnOption[];
  groupOptions: GroupOption[];
  loadingGroups: boolean;
  loading: boolean;
  formRef?: QForm | null;
}>();

const emit = defineEmits<{
  (e: 'update:options', value: ImportOptions): void;
  (e: 'update:file', value: File | null): void;
  (e: 'submit'): void;
  (e: 'download-template'): void;
  (e: 'refresh-groups'): void;
}>();

// Références locales
const { options, loading, groupOptions, loadingGroups } = toRefs(props);
const formRef = ref<QForm | null>(null);
const fileModel = ref<File | null>(null);

// Surveiller les changements de fichier pour les émettre au parent
watch(fileModel, (newFile) => {
  emit('update:file', newFile);
});

// Surveiller les changements d'options pour les émettre au parent
watch(options, (newOptions, oldOptions) => {
  // Éviter la boucle infinie en comparant les valeurs
  if (JSON.stringify(newOptions) !== JSON.stringify(oldOptions)) {
    emit('update:options', { ...newOptions });
  }
}, { deep: true });

// Gestionnaires d'événements
const onSubmit = () => {
  emit('submit');
};

const onDownloadTemplate = () => {
  emit('download-template');
};
</script>
