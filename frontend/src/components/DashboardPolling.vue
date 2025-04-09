<template>
  <div>
    <slot :data="dashboardData" :loading="loading" :error="error" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { api, apolloClient, gql } from 'src/services/api';

interface DashboardStats {
  totalUsers: number;
  totalSmsCredits: number;
  lastUpdated: string;
}

const props = defineProps({
  interval: {
    type: Number,
    default: 5000, // 5 seconds
  },
});

const dashboardData = ref<DashboardStats | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

let intervalId: NodeJS.Timeout | null = null;

const fetchDashboardData = async () => {
  loading.value = true;
  error.value = null;

  try {
    const { data } = await apolloClient.query({
      query: gql`
        query GetDashboardStats {
          dashboardStats {
            usersCount
            totalSmsCredits
            lastUpdated
          }
        }
      `,
    });

    dashboardData.value = {
      totalUsers: data.dashboardStats.usersCount,
      totalSmsCredits: data.dashboardStats.totalSmsCredits,
      lastUpdated: data.dashboardStats.lastUpdated
    };
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to fetch dashboard data';
    console.error(error.value);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
  intervalId = setInterval(fetchDashboardData, props.interval);
});

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId);
  }
});
</script>
