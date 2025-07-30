<template>
  <div class="group-multi-selector">
    <q-select
      v-model="selectedGroups"
      :options="groupOptions"
      multiple
      chips
      use-chips
      :loading="loading"
      :disable="disable"
      label="Groupes à assigner (optionnel)"
      hint="Sélectionnez les groupes auxquels assigner automatiquement les contacts importés"
      outlined
      clearable
      @update:model-value="onSelectionChange"
    >
      <template v-slot:prepend>
        <q-icon name="groups" />
      </template>
      
      <template v-slot:no-option>
        <q-item>
          <q-item-section class="text-grey">
            {{ loading ? 'Chargement des groupes...' : 'Aucun groupe disponible' }}
          </q-item-section>
        </q-item>
      </template>
      
      <template v-slot:selected-item="scope">
        <q-chip
          removable
          @remove="scope.removeAtIndex(scope.index)"
          :tabindex="scope.tabindex"
          color="primary"
          text-color="white"
          class="q-ma-xs"
        >
          <q-icon name="group" size="xs" class="q-mr-xs" />
          {{ scope.opt.label }}
        </q-chip>
      </template>
      
      <template v-slot:option="scope">
        <q-item v-bind="scope.itemProps">
          <q-item-section avatar>
            <q-icon name="group" />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ scope.opt.label }}</q-item-label>
            <q-item-label caption v-if="scope.opt.description">
              {{ scope.opt.description }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-select>
    
    <!-- Info section -->
    <div v-if="selectedGroups.length > 0" class="q-mt-sm">
      <q-banner class="bg-blue-1 text-blue-9" rounded>
        <template v-slot:avatar>
          <q-icon name="info" />
        </template>
        <div class="text-caption">
          <strong>{{ selectedGroups.length }} groupe{{ selectedGroups.length > 1 ? 's' : '' }} sélectionné{{ selectedGroups.length > 1 ? 's' : '' }}</strong><br>
          Les contacts créés lors de l'import seront automatiquement ajoutés à {{ selectedGroups.length > 1 ? 'ces groupes' : 'ce groupe' }}.
        </div>
      </q-banner>
    </div>
    
    <!-- Error display -->
    <div v-if="error" class="q-mt-sm">
      <q-banner class="bg-negative text-white" rounded>
        <template v-slot:avatar>
          <q-icon name="error" />
        </template>
        {{ error }}
      </q-banner>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useContactGroupStore } from '@/stores/contactGroupStore'
import { useAuthStore } from '@/stores/authStore'

interface GroupOption {
  label: string
  value: string
  description?: string
}

interface Props {
  modelValue?: string[]
  disable?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: string[]): void
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  disable: false
})

const emit = defineEmits<Emits>()

// Store and reactive data
const contactGroupStore = useContactGroupStore()
const authStore = useAuthStore()
const selectedGroups = ref<GroupOption[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

// Computed properties
const groupOptions = computed(() => {
  return contactGroupStore.userGroups.map(group => ({
    label: group.name,
    value: group.id,
    description: group.description || undefined
  }))
})

const currentUserId = computed(() => {
  return authStore.user?.id
})

// Methods
const loadUserGroups = async () => {
  if (!currentUserId.value) {
    error.value = 'Utilisateur non connecté'
    return
  }
  
  loading.value = true
  error.value = null
  
  try {
    await contactGroupStore.fetchUserGroups(currentUserId.value)
  } catch (err) {
    error.value = 'Erreur lors du chargement des groupes'
    console.error('Error loading user groups:', err)
  } finally {
    loading.value = false
  }
}

const onSelectionChange = (newSelection: GroupOption[]) => {
  const groupIds = newSelection.map(group => group.value)
  emit('update:modelValue', groupIds)
}

const syncModelValue = () => {
  if (props.modelValue && props.modelValue.length > 0) {
    selectedGroups.value = groupOptions.value.filter(option => 
      props.modelValue.includes(option.value)
    )
  } else {
    selectedGroups.value = []
  }
}

// Watchers
watch(() => props.modelValue, syncModelValue, { deep: true })
watch(() => currentUserId.value, (newUserId) => {
  if (newUserId) {
    loadUserGroups()
  }
}, { immediate: true })
watch(() => groupOptions.value, syncModelValue, { deep: true })

// Lifecycle
onMounted(() => {
  if (currentUserId.value) {
    loadUserGroups()
  }
})
</script>

<style lang="scss" scoped>
.group-multi-selector {
  .q-select {
    .q-field__control {
      min-height: 56px;
    }
  }
  
  .q-banner {
    border-radius: 8px;
  }
  
  .q-chip {
    font-weight: 500;
  }
}
</style>