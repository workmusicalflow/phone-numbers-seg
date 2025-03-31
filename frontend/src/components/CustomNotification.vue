<template>
  <transition
    appear
    enter-active-class="animate__animated animate__fadeInDown"
    leave-active-class="animate__animated animate__fadeOutUp"
  >
    <div
      v-if="visible"
      class="custom-notification"
      :class="[`bg-${color}`, `text-${textColor}`]"
    >
      <div class="custom-notification-content">
        <div class="icon-wrapper q-mr-sm">
          <span v-if="icon === 'error'" class="material-icons">error</span>
          <span v-else-if="icon === 'warning'" class="material-icons">warning</span>
          <span v-else-if="icon === 'info'" class="material-icons">info</span>
          <span v-else-if="icon === 'check_circle'" class="material-icons">check_circle</span>
          <span v-else class="material-icons">{{ icon }}</span>
        </div>
        <span>{{ message }}</span>
      </div>
      <button
        class="close-button"
        :class="`text-${textColor}`"
        @click="hide"
      >
        <span class="material-icons">close</span>
      </button>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';

// Props
const props = defineProps({
  message: {
    type: String,
    required: true
  },
  color: {
    type: String,
    default: 'primary'
  },
  textColor: {
    type: String,
    default: 'white'
  },
  icon: {
    type: String,
    default: 'info'
  },
  timeout: {
    type: Number,
    default: 3000
  },
  autoClose: {
    type: Boolean,
    default: true
  }
});

// State
const visible = ref(false);
let timeoutId: number | null = null;

// Methods
const show = () => {
  visible.value = true;
  if (props.autoClose && props.timeout > 0) {
    timeoutId = window.setTimeout(() => {
      hide();
    }, props.timeout);
  }
};

const hide = () => {
  visible.value = false;
  if (timeoutId !== null) {
    clearTimeout(timeoutId);
    timeoutId = null;
  }
};

// Lifecycle
onMounted(() => {
  // Use nextTick to ensure the component is fully mounted before showing
  setTimeout(() => {
    show();
  }, 0);
});

onBeforeUnmount(() => {
  if (timeoutId !== null) {
    clearTimeout(timeoutId);
  }
});

// Expose methods
defineExpose({
  show,
  hide
});
</script>

<style scoped>
.custom-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  min-width: 300px;
  max-width: 80%;
  padding: 12px 16px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.custom-notification-content {
  display: flex;
  align-items: center;
}

.icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
}

.close-button {
  background: transparent;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  border-radius: 50%;
}

.close-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.material-icons {
  font-family: 'Material Icons';
  font-weight: normal;
  font-style: normal;
  font-size: 24px;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
  -webkit-font-smoothing: antialiased;
}

/* Import animate.css classes for transitions */
@import 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css';
/* Import Material Icons */
@import 'https://fonts.googleapis.com/icon?family=Material+Icons';
</style>
