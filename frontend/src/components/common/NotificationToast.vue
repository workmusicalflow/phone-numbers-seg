<template>
  <q-dialog v-model="isVisible" position="top">
    <q-card :class="['notification-toast', `bg-${type}`]">
      <q-card-section class="row items-center no-wrap">
        <div class="text-white">{{ message }}</div>
        <q-btn flat round dense icon="close" class="text-white" @click="isVisible = false" />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from 'vue';

export default defineComponent({
  name: 'NotificationToast',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    message: {
      type: String,
      default: ''
    },
    type: {
      type: String,
      default: 'primary',
      validator: (value: string) => ['primary', 'secondary', 'positive', 'negative', 'warning', 'info'].includes(value)
    },
    timeout: {
      type: Number,
      default: 3000
    }
  },
  emits: ['update:show'],
  setup(props, { emit }) {
    const isVisible = ref(props.show);
    let timer: number | null = null;

    // Watch for changes in the show prop
    watch(() => props.show, (newVal) => {
      isVisible.value = newVal;
      if (newVal && props.timeout > 0) {
        if (timer) clearTimeout(timer);
        timer = window.setTimeout(() => {
          isVisible.value = false;
          emit('update:show', false);
        }, props.timeout);
      }
    });

    // Watch for changes in the isVisible ref
    watch(isVisible, (newVal) => {
      if (!newVal) {
        emit('update:show', false);
        if (timer) {
          clearTimeout(timer);
          timer = null;
        }
      }
    });

    return {
      isVisible
    };
  }
});
</script>

<style scoped>
.notification-toast {
  min-width: 300px;
  max-width: 80vw;
}
</style>
