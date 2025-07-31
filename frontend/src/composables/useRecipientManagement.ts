import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { useContactGroupStore } from '@/stores/contactGroupStore'
import { useSegmentStore } from '@/stores/segmentStore'
import type { ContactGroup } from '@/types/contactGroup'

export function useRecipientManagement() {
  const $q = useQuasar()
  const contactGroupStore = useContactGroupStore()
  const segmentStore = useSegmentStore()

  // État
  const recipientTab = ref('manual')
  const manualNumbers = ref('')
  const selectedGroups = ref<any[]>([])
  const selectedSegments = ref<any[]>([])
  const csvFile = ref<File | null>(null)
  const recipients = ref<string[]>([])

  // Méthodes
  const parseManualNumbers = (value: string | number | null) => {
    if (!value || typeof value !== 'string') {
      recipients.value = []
      return
    }
    const numbers = value.split('\n')
      .map(num => num.trim())
      .filter(num => num.length > 0 && num.startsWith('+'))
    recipients.value = [...new Set(numbers)]
  }

  const updateRecipientsFromGroups = async () => {
    if (selectedGroups.value.length === 0) {
      recipients.value = []
      return
    }

    try {
      const allContacts: string[] = []
      for (const group of selectedGroups.value) {
        await contactGroupStore.fetchContactGroups()
        const groupData = contactGroupStore.contactGroups.find((g: ContactGroup) => g.id === group.id)
        if (groupData && groupData.contactCount > 0) {
          // TODO: Implémenter la récupération des contacts du groupe
          console.log('Récupération des contacts du groupe:', group.id)
        }
      }
      recipients.value = [...new Set(allContacts)]
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erreur lors du chargement des contacts'
      })
    }
  }

  const updateRecipientsFromSegments = async () => {
    if (selectedSegments.value.length === 0) {
      recipients.value = []
      return
    }

    try {
      const allNumbers: string[] = []
      for (const segment of selectedSegments.value) {
        // TODO: Implémenter la récupération des numéros du segment
        console.log('Récupération des numéros du segment:', segment.id)
      }
      recipients.value = [...new Set(allNumbers)]
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erreur lors du chargement des numéros'
      })
    }
  }

  const processCsvFile = async (file: File | null) => {
    if (!file) {
      recipients.value = []
      return
    }

    try {
      const text = await file.text()
      const lines = text.split('\n')
      const numbers: string[] = []

      for (const line of lines) {
        const trimmedLine = line.trim()
        if (trimmedLine) {
          const columns = trimmedLine.split(',')
          const phoneNumber = columns[0]?.trim()
          
          if (phoneNumber && phoneNumber.startsWith('+')) {
            numbers.push(phoneNumber)
          }
        }
      }

      recipients.value = [...new Set(numbers)]
      
      $q.notify({
        type: 'positive',
        message: `${recipients.value.length} numéros importés`
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erreur lors de la lecture du fichier CSV'
      })
    }
  }

  const removeRecipient = (index: number) => {
    recipients.value.splice(index, 1)
  }

  const resetRecipients = () => {
    recipientTab.value = 'manual'
    manualNumbers.value = ''
    selectedGroups.value = []
    selectedSegments.value = []
    csvFile.value = null
    recipients.value = []
  }

  const validateRecipients = (): boolean => {
    if (recipients.value.length === 0) {
      $q.notify({
        type: 'negative',
        message: 'Aucun destinataire sélectionné'
      })
      return false
    }

    if (recipients.value.length > 500) {
      $q.notify({
        type: 'negative',
        message: 'Maximum 500 destinataires autorisés'
      })
      return false
    }

    return true
  }

  // Méthodes d'update pour le component parent
  const updateRecipients = (newRecipients: string[]) => {
    recipients.value = newRecipients
  }

  const updateRecipientTab = (tab: string) => {
    recipientTab.value = tab
  }

  const updateSelectedGroups = (groups: number[]) => {
    selectedGroups.value = groups
  }

  const updateSelectedSegments = (segments: number[]) => {
    selectedSegments.value = segments
  }

  return {
    // État
    recipientTab,
    manualNumbers,
    selectedGroups,
    selectedSegments,
    csvFile,
    recipients,
    
    // Méthodes
    parseManualNumbers,
    updateRecipientsFromGroups,
    updateRecipientsFromSegments,
    processCsvFile,
    removeRecipient,
    resetRecipients,
    validateRecipients,
    updateRecipients,
    updateRecipientTab,
    updateSelectedGroups,
    updateSelectedSegments
  }
}