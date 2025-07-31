<template>
  <q-dialog v-model="isOpen">
    <q-card class="q-pa-md" style="min-width: 700px">
      <q-card-section>
        <div class="text-h6">Diagnostics Média</div>
      </q-card-section>

      <q-card-section class="q-pt-none">
        <q-tabs
          v-model="activeTab"
          dense
          class="text-grey"
          active-color="primary"
          indicator-color="primary"
          align="justify"
        >
          <q-tab name="logs" label="Logs" />
          <q-tab name="system" label="Système" />
          <q-tab name="media" label="Média" />
          <q-tab name="network" label="Réseau" />
        </q-tabs>

        <q-tab-panels v-model="activeTab" animated>
          <q-tab-panel name="logs">
            <div class="text-subtitle2 q-mb-md">Journaux récents</div>
            
            <q-select
              v-model="logLevel"
              :options="logLevelOptions"
              label="Niveau"
              outlined
              dense
              class="q-mb-md"
            />

            <q-table
              :rows="filteredLogs"
              :columns="logColumns"
              row-key="timestamp"
              dense
              :pagination="{rowsPerPage: 10}"
              class="logs-table"
            >
              <template v-slot:body="props">
                <q-tr :props="props">
                  <q-td key="timestamp" :props="props">
                    {{ formatDate(props.row.timestamp) }}
                  </q-td>
                  <q-td key="level" :props="props">
                    <q-chip :color="getLevelColor(props.row.level)" dense text-color="white">
                      {{ props.row.level.toUpperCase() }}
                    </q-chip>
                  </q-td>
                  <q-td key="message" :props="props">
                    {{ props.row.message }}
                  </q-td>
                  <q-td key="operation" :props="props">
                    {{ props.row.operation || '-' }}
                  </q-td>
                  <q-td key="data" :props="props">
                    <q-btn flat size="sm" color="primary" @click="showData(props.row.data)" v-if="props.row.data">
                      Détails
                    </q-btn>
                    <span v-else>-</span>
                  </q-td>
                </q-tr>
              </template>
            </q-table>
          </q-tab-panel>

          <q-tab-panel name="system">
            <div class="text-subtitle2 q-mb-md">Informations système</div>
            
            <q-list bordered separator>
              <q-item>
                <q-item-section>
                  <q-item-label>Navigateur</q-item-label>
                  <q-item-label caption>{{ diagnostics.browser?.userAgent || 'Inconnu' }}</q-item-label>
                </q-item-section>
              </q-item>
              
              <q-item>
                <q-item-section>
                  <q-item-label>Plateforme</q-item-label>
                  <q-item-label caption>{{ diagnostics.browser?.platform || 'Inconnu' }}</q-item-label>
                </q-item-section>
              </q-item>
              
              <q-item>
                <q-item-section>
                  <q-item-label>Langue</q-item-label>
                  <q-item-label caption>{{ diagnostics.browser?.language || 'Inconnue' }}</q-item-label>
                </q-item-section>
              </q-item>
              
              <q-item>
                <q-item-section>
                  <q-item-label>Stockage</q-item-label>
                  <q-item-label caption>
                    Total utilisé: {{ formatSize(diagnostics.storage?.totalUsed || 0) }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>

          <q-tab-panel name="media">
            <div class="text-subtitle2 q-mb-md">Statistiques média</div>
            
            <q-list bordered separator>
              <q-item>
                <q-item-section>
                  <q-item-label>Médias récents</q-item-label>
                  <q-item-label caption>{{ diagnostics.mediaStats?.recentCount || 0 }} éléments</q-item-label>
                </q-item-section>
              </q-item>
              
              <q-item>
                <q-item-section>
                  <q-item-label>Médias favoris</q-item-label>
                  <q-item-label caption>{{ diagnostics.mediaStats?.favoriteCount || 0 }} éléments</q-item-label>
                </q-item-section>
              </q-item>
              
              <q-item v-if="diagnostics.mediaStats?.typeDistribution">
                <q-item-section>
                  <q-item-label>Distribution par type</q-item-label>
                  <q-item-label caption>
                    <div v-for="(count, type) in diagnostics.mediaStats.typeDistribution" :key="type">
                      {{ type }}: {{ count }}
                    </div>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>

          <q-tab-panel name="network">
            <div class="text-subtitle2 q-mb-md">État du réseau</div>
            
            <q-list bordered separator>
              <q-item>
                <q-item-section>
                  <q-item-label>Connectivité</q-item-label>
                  <q-item-label caption>
                    <q-chip 
                      :color="diagnostics.networkStatus ? 'positive' : 'negative'" 
                      text-color="white"
                    >
                      {{ diagnostics.networkStatus ? 'Connecté' : 'Déconnecté' }}
                    </q-chip>
                  </q-item-label>
                </q-item-section>
              </q-item>
              
              <q-item>
                <q-item-section>
                  <q-item-label>Test de connexion</q-item-label>
                  <q-item-label caption>
                    <q-btn 
                      color="primary" 
                      label="Tester la connexion" 
                      size="sm"
                      :loading="testingConnection"
                      @click="testConnection"
                    />
                    <div v-if="connectionTestResult !== null" class="q-mt-sm">
                      {{ connectionTestResult }}
                    </div>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>
        </q-tab-panels>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn label="Rafraîchir" color="primary" flat @click="refresh" />
        <q-btn label="Fermer" color="primary" flat v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <q-dialog v-model="showDataDialog">
    <q-card style="min-width: 400px">
      <q-card-section>
        <div class="text-h6">Données détaillées</div>
      </q-card-section>

      <q-card-section class="q-pt-none">
        <pre class="data-preview">{{ JSON.stringify(currentData, null, 2) }}</pre>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Fermer" color="primary" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue';
import { mediaLogger, LogLevel } from '../../services/mediaLogger';
import { date } from 'quasar';

export default defineComponent({
  name: 'MediaDiagnostics',
  props: {
    modelValue: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const isOpen = computed({
      get: () => props.modelValue,
      set: (value) => emit('update:modelValue', value)
    });

    const activeTab = ref('logs');
    const logLevel = ref(null);
    const logs = ref([]);
    const diagnostics = ref({});
    const showDataDialog = ref(false);
    const currentData = ref(null);
    const testingConnection = ref(false);
    const connectionTestResult = ref(null);

    const logLevelOptions = [
      { label: 'Tous', value: null },
      { label: 'Debug', value: LogLevel.DEBUG },
      { label: 'Info', value: LogLevel.INFO },
      { label: 'Warn', value: LogLevel.WARN },
      { label: 'Error', value: LogLevel.ERROR }
    ];

    const logColumns = [
      { name: 'timestamp', align: 'left', label: 'Heure', field: 'timestamp' },
      { name: 'level', align: 'left', label: 'Niveau', field: 'level' },
      { name: 'message', align: 'left', label: 'Message', field: 'message' },
      { name: 'operation', align: 'left', label: 'Opération', field: 'operation' },
      { name: 'data', align: 'left', label: 'Données', field: 'data' }
    ];

    const filteredLogs = computed(() => {
      if (logLevel.value === null) {
        return logs.value;
      }
      return logs.value.filter(log => log.level === logLevel.value);
    });

    const formatDate = (timestamp: string) => {
      return date.formatDate(timestamp, 'HH:mm:ss.SSS');
    };

    const getLevelColor = (level: string) => {
      switch (level) {
        case LogLevel.DEBUG: return 'blue';
        case LogLevel.INFO: return 'green';
        case LogLevel.WARN: return 'orange';
        case LogLevel.ERROR: return 'red';
        default: return 'grey';
      }
    };

    const formatSize = (bytes: number) => {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const showData = (data: any) => {
      currentData.value = data;
      showDataDialog.value = true;
    };

    const refresh = () => {
      logs.value = mediaLogger.getLogs();
      diagnostics.value = mediaLogger.generateDiagnostics();
    };

    const testConnection = async () => {
      testingConnection.value = true;
      connectionTestResult.value = null;
      
      try {
        const startTime = Date.now();
        const response = await fetch('/api/ping', { 
          method: 'GET',
          cache: 'no-cache'
        });
        
        const endTime = Date.now();
        const duration = endTime - startTime;
        
        if (response.ok) {
          connectionTestResult.value = `Connexion établie en ${duration}ms`;
        } else {
          connectionTestResult.value = `Erreur ${response.status}: ${response.statusText}`;
        }
      } catch (error) {
        connectionTestResult.value = `Erreur de connexion: ${error.message}`;
      } finally {
        testingConnection.value = false;
      }
    };

    onMounted(() => {
      refresh();
    });

    return {
      isOpen,
      activeTab,
      logLevel,
      logLevelOptions,
      logs,
      logColumns,
      filteredLogs,
      diagnostics,
      showDataDialog,
      currentData,
      testingConnection,
      connectionTestResult,
      formatDate,
      getLevelColor,
      formatSize,
      showData,
      refresh,
      testConnection
    };
  }
});
</script>

<style scoped>
.logs-table {
  max-height: 400px;
}

.data-preview {
  max-height: 400px;
  overflow: auto;
  white-space: pre-wrap;
  word-break: break-word;
  background-color: #f5f5f5;
  padding: 8px;
  border-radius: 4px;
  font-family: monospace;
}
</style>