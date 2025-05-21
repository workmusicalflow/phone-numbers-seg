<template>
  <div>
    <canvas ref="chartCanvas" height="250"></canvas>
    <div v-if="!data || data.length === 0" class="text-center q-pa-md">
      <q-icon name="info" color="grey" size="2rem" />
      <div class="text-grey q-mt-sm">Aucune donnée d'utilisation disponible</div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, watch, PropType } from 'vue';
import Chart from 'chart.js/auto';
import { UsageDataPoint } from '../stores/userDashboardStore';

export default defineComponent({
  name: 'UsageChart',
  
  props: {
    data: {
      type: Array as PropType<UsageDataPoint[]>,
      required: true
    }
  },
  
  setup(props) {
    const chartCanvas = ref<HTMLCanvasElement | null>(null);
    let chart: Chart | null = null;
    
    // Fonction pour initialiser le graphique
    const initChart = () => {
      if (!chartCanvas.value || !props.data || props.data.length === 0) return;
      
      const ctx = chartCanvas.value.getContext('2d');
      if (!ctx) return;
      
      // Préparer les données pour le graphique
      const labels = props.data.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
      });
      
      const sentData = props.data.map(item => item.sent);
      const deliveredData = props.data.map(item => item.delivered);
      const failedData = props.data.map(item => item.failed);
      
      // Créer le graphique
      chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            {
              label: 'Envoyés',
              data: sentData,
              backgroundColor: 'rgba(54, 162, 235, 0.5)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1
            },
            {
              label: 'Livrés',
              data: deliveredData,
              backgroundColor: 'rgba(75, 192, 192, 0.5)',
              borderColor: 'rgba(75, 192, 192, 1)',
              borderWidth: 1
            },
            {
              label: 'Échoués',
              data: failedData,
              backgroundColor: 'rgba(255, 99, 132, 0.5)',
              borderColor: 'rgba(255, 99, 132, 1)',
              borderWidth: 1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Nombre de SMS'
              }
            },
            x: {
              title: {
                display: true,
                text: 'Date'
              }
            }
          },
          plugins: {
            legend: {
              position: 'top'
            },
            tooltip: {
              mode: 'index',
              intersect: false
            }
          }
        }
      });
    };
    
    // Mettre à jour le graphique lorsque les données changent
    const updateChart = () => {
      if (chart) {
        chart.destroy();
      }
      initChart();
    };
    
    // Observer les changements dans les données
    watch(() => props.data, () => {
      updateChart();
    }, { deep: true });
    
    // Initialiser le graphique au montage du composant
    onMounted(() => {
      initChart();
    });
    
    return {
      chartCanvas
    };
  }
});
</script>

<style scoped>
/* Styles spécifiques au graphique d'utilisation */
</style>
