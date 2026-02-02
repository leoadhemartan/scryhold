


<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

// Props
const props = defineProps({
  cards: Object, // Paginated data object
  locations: Array
})

// Modal state
const showAddModal = ref(false)
const showBulkAddModal = ref(false)
const showBulkSummaryModal = ref(false)
const showResultsModal = ref(false)
const showDetailModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const isUpdatingLibrary = ref(false)
const isSavingCard = ref(false)
const isLoadingDetail = ref(false)
const isMovingInstances = ref(false)
const isRemovingInstances = ref(false)
const isUpdatingCardData = ref(false)
const isProcessingBulkAdd = ref(false)

// JSON tab state
const activeJsonTab = ref('parsed')

// Selected card for detail view
const selectedCard = ref(null)

// Edit/Delete form data
const moveForm = ref({
  quantity: 1,
  from_location_id: '',
  to_location_id: ''
})
const removeForm = ref({
  quantity: 1,
  location_id: ''
})
const moveError = ref('')
const removeError = ref('')

// Set library results
const libraryResults = ref({
  imported: 0,
  errors: [],
  log: []
})

// Form data
const form = ref({
  collection_number: '',
  set_code: '',
  language: 'en',
  location_id: ''
})
const errors = ref({})
const formError = ref('')

// API response for Card Data display
const apiResponse = ref(null)
const isLoadingCard = ref(false)

// Bulk add form data
const bulkAddForm = ref({
  entries: '',
  location_id: ''
})
const bulkAddSummary = ref({
  total_processed: 0,
  success_count: 0,
  failed_count: 0,
  failed_entries: [],
  successful_cards: []
})
const bulkAddError = ref('')

// Computed property for card image display
const getCardImage = (card) => {
  // Multi-faced card with separate face images: show front image
  if (card.cfl_image_uri) {
    return `/storage/${card.cfl_image_uri}`
  }
  // Single-faced or multi-faced with shared image: show image_uri
  if (card.image_uri) {
    return `/storage/${card.image_uri}`
  }
  // No image available
  return null
}

// Computed property for type_line display with conditional logic
const getCardTypeLine = (card) => {
  // If type_line is "N/A", display card face type lines separated by " | "
  if (card.type_line === 'N/A' && card.cfl_type_line && card.cfr_type_line) {
    return `${card.cfl_type_line} | ${card.cfr_type_line}`
  }
  // Otherwise display the type_line as is
  return card.type_line || 'N/A'
}

// Computed property for parsed JSON data (structured view)
const parsedJsonData = computed(() => {
  if (!selectedCard.value?.scryfall_json_raw) return null
  
  try {
    const data = JSON.parse(selectedCard.value.scryfall_json_raw)
    return data
  } catch (e) {
    return null
  }
})

// Open card detail modal
async function openDetailModal(cardId) {
  showDetailModal.value = true
  isLoadingDetail.value = true
  selectedCard.value = null
  
  try {
    const response = await fetch(`/admin/cards/${cardId}`)
    const data = await response.json()
    
    if (response.ok) {
      selectedCard.value = data
    } else {
      alert('Failed to load card details')
      showDetailModal.value = false
    }
  } catch (error) {
    console.error('Error loading card details:', error)
    alert('Error loading card details')
    showDetailModal.value = false
  } finally {
    isLoadingDetail.value = false
  }
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedCard.value = null
  showEditModal.value = false
  showDeleteModal.value = false
  moveError.value = ''
  removeError.value = ''
  activeJsonTab.value = 'parsed' // Reset to default tab
}

function openEditModal() {
  showEditModal.value = true
  moveForm.value = {
    quantity: 1,
    from_location_id: selectedCard.value.instances[0]?.location_id || '',
    to_location_id: ''
  }
  moveError.value = ''
}

function closeEditModal() {
  showEditModal.value = false
  moveError.value = ''
}

function openDeleteModal() {
  showDeleteModal.value = true
  removeForm.value = {
    quantity: 1,
    location_id: selectedCard.value.instances[0]?.location_id || ''
  }
  removeError.value = ''
}

function closeDeleteModal() {
  showDeleteModal.value = false
  removeError.value = ''
}

async function submitMoveInstances() {
  moveError.value = ''
  
  if (!moveForm.value.quantity || moveForm.value.quantity < 1) {
    moveError.value = 'Quantity must be at least 1'
    return
  }
  
  if (!moveForm.value.from_location_id) {
    moveError.value = 'Please select a source location'
    return
  }
  
  if (!moveForm.value.to_location_id) {
    moveError.value = 'Please select a destination location'
    return
  }
  
  if (moveForm.value.from_location_id === moveForm.value.to_location_id) {
    moveError.value = 'Source and destination must be different'
    return
  }
  
  isMovingInstances.value = true
  
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || ''
    
    const response = await fetch(`/admin/cards/${selectedCard.value.id}/move`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(moveForm.value)
    })
    
    const data = await response.json()
    
    if (response.ok && data.success) {
      alert(data.message)
      closeEditModal()
      closeDetailModal()
      router.reload({ preserveScroll: true })
    } else {
      moveError.value = data.message || 'Failed to move card instances'
    }
  } catch (error) {
    console.error('Error moving instances:', error)
    moveError.value = 'Error moving card instances'
  } finally {
    isMovingInstances.value = false
  }
}

async function submitRemoveInstances() {
  removeError.value = ''
  
  if (!removeForm.value.quantity || removeForm.value.quantity < 1) {
    removeError.value = 'Quantity must be at least 1'
    return
  }
  
  if (!removeForm.value.location_id) {
    removeError.value = 'Please select a location'
    return
  }
  
  isRemovingInstances.value = true
  
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || ''
    
    const response = await fetch(`/admin/cards/${selectedCard.value.id}/remove`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(removeForm.value)
    })
    
    const data = await response.json()
    
    if (response.ok && data.success) {
      alert(data.message)
      closeDeleteModal()
      closeDetailModal()
      router.reload({ preserveScroll: true })
    } else {
      removeError.value = data.message || 'Failed to remove card instances'
    }
  } catch (error) {
    console.error('Error removing instances:', error)
    removeError.value = 'Error removing card instances'
  } finally {
    isRemovingInstances.value = false
  }
}

async function updateCardData() {
  if (!confirm('This will fetch the latest card data from Scryfall and update the card. Continue?')) {
    return
  }
  
  isUpdatingCardData.value = true
  
  router.post(`/admin/cards/${selectedCard.value.id}/update-data`, {}, {
    preserveScroll: true,
    onSuccess: (page) => {
      isUpdatingCardData.value = false
      const flash = page.props.flash
      alert(flash?.success || 'Card data updated successfully')
      // Reload the card details
      openDetailModal(selectedCard.value.id)
    },
    onError: (errors) => {
      isUpdatingCardData.value = false
      console.error('Update failed:', errors)
      alert(errors.message || 'Failed to update card data')
    },
    onFinish: () => {
      isUpdatingCardData.value = false
    }
  })
}

// Computed property for pagination page numbers
const pageNumbers = computed(() => {
  if (!props.cards || !props.cards.last_page) return []
  
  const current = props.cards.current_page
  const last = props.cards.last_page
  const delta = 2 // Show 2 pages on each side of current page
  const pages = []
  
  // Always show first page
  pages.push(1)
  
  // Calculate range around current page
  const rangeStart = Math.max(2, current - delta)
  const rangeEnd = Math.min(last - 1, current + delta)
  
  // Add ellipsis after first page if needed
  if (rangeStart > 2) {
    pages.push('...')
  }
  
  // Add pages around current
  for (let i = rangeStart; i <= rangeEnd; i++) {
    pages.push(i)
  }
  
  // Add ellipsis before last page if needed
  if (rangeEnd < last - 1) {
    pages.push('...')
  }
  
  // Always show last page (if more than 1 page)
  if (last > 1) {
    pages.push(last)
  }
  
  return pages
})

// Scryfall language codes
const languages = [
  { code: 'en', name: 'English' },
  { code: 'es', name: 'Spanish' },
  { code: 'fr', name: 'French' },
  { code: 'de', name: 'German' },
  { code: 'it', name: 'Italian' },
  { code: 'pt', name: 'Portuguese' },
  { code: 'ja', name: 'Japanese' },
  { code: 'ko', name: 'Korean' },
  { code: 'ru', name: 'Russian' },
  { code: 'zhs', name: 'Simplified Chinese' },
  { code: 'zht', name: 'Traditional Chinese' },
  { code: 'he', name: 'Hebrew' },
  { code: 'la', name: 'Latin' },
  { code: 'grc', name: 'Ancient Greek' },
  { code: 'ar', name: 'Arabic' },
  { code: 'sa', name: 'Sanskrit' },
  { code: 'ph', name: 'Phyrexian' }
]

function openAddModal() {
  showAddModal.value = true
  // Reset form
  form.value = {
    collection_number: '',
    set_code: '',
    language: 'en',
    location_id: props.locations && props.locations.length > 0 ? props.locations[0].id : ''
  }
  errors.value = {}
  formError.value = ''
  apiResponse.value = null
}

function closeAddModal() {
  showAddModal.value = false
}

async function submitAddForm() {
  errors.value = {}
  formError.value = ''
  apiResponse.value = null
  
  // Frontend validation
  if (!form.value.collection_number.match(/^[\w-]+$/)) {
    errors.value.collection_number = 'Invalid collection number format.'
    return
  }
  if (!form.value.set_code.match(/^[a-zA-Z0-9]{2,5}$/)) {
    errors.value.set_code = 'Set code must be 2-5 alphanumeric characters.'
    return
  }
  if (!form.value.language) {
    errors.value.language = 'Language is required.'
    return
  }
  
  // Call Scryfall API
  isLoadingCard.value = true
  try {
    // Build API URL: GET /cards/:code/:number(/:lang)
    const baseUrl = 'https://api.scryfall.com/cards'
    const url = `${baseUrl}/${form.value.set_code}/${form.value.collection_number}/${form.value.language}`
    
    const response = await fetch(url)
    const data = await response.json()
    
    if (!response.ok) {
      formError.value = data.details || 'Card not found in Scryfall API'
      apiResponse.value = null
    } else {
      apiResponse.value = data
    }
  } catch (error) {
    formError.value = 'Failed to fetch card from Scryfall API: ' + error.message
  } finally {
    isLoadingCard.value = false
  }
}

function updateSetLibrary() {
  if (isUpdatingLibrary.value) return
  
  if (!confirm('This will fetch all sets from Scryfall and may take a few minutes. Continue?')) {
    return
  }
  
  // Reset and show modal immediately
  libraryResults.value = {
    imported: 0,
    errors: [],
    log: ['Starting update...']
  }
  showResultsModal.value = true
  isUpdatingLibrary.value = true
  
  // Call backend API to update set library
  router.post('/admin/cards/update-set-library', {}, {
    preserveScroll: true,
    onSuccess: (page) => {
      isUpdatingLibrary.value = false
      const response = page.props.flash || {}
      libraryResults.value = {
        imported: response.imported || 0,
        errors: response.errors || [],
        log: response.log || ['Update completed']
      }
    },
    onError: (errors) => {
      isUpdatingLibrary.value = false
      console.error('Update failed:', errors)
      libraryResults.value = {
        imported: 0,
        errors: ['Failed to update set library. Check console for details.'],
        log: ['Update failed']
      }
    },
    onFinish: () => {
      isUpdatingLibrary.value = false
    }
  })
}

function closeResultsModal() {
  showResultsModal.value = false
}

function goToEdit(id) {
  window.location.href = `/admin/cards/${id}/edit`
}

function saveCardToLibrary() {
  if (!form.value.location_id) {
    formError.value = 'Please select a location'
    return
  }

  if (!apiResponse.value) {
    formError.value = 'No card data available to save'
    return
  }

  isSavingCard.value = true
  formError.value = ''

  router.post('/admin/cards', {
    scryfall_data: apiResponse.value,
    location_id: form.value.location_id
  }, {
    preserveScroll: true,
    onSuccess: (page) => {
      isSavingCard.value = false
      closeAddModal()
      // Show success message from server response
      const flash = page.props.flash
      if (flash && flash.success) {
        alert(flash.success)
      } else {
        alert('Card added to library successfully')
      }
    },
    onError: (errors) => {
      isSavingCard.value = false
      console.error('Save failed:', errors)
      // Display first error message
      if (errors.message) {
        formError.value = errors.message
      } else if (Object.keys(errors).length > 0) {
        formError.value = Object.values(errors)[0]
      } else {
        formError.value = 'Failed to save card. Please try again.'
      }
    },
    onFinish: () => {
      isSavingCard.value = false
    }
  })
}

// Bulk Add Functions
function openBulkAddModal() {
  showBulkAddModal.value = true
  bulkAddForm.value = {
    entries: '',
    location_id: props.locations && props.locations.length > 0 ? props.locations[0].id : ''
  }
  bulkAddError.value = ''
}

function closeBulkAddModal() {
  showBulkAddModal.value = false
  bulkAddError.value = ''
}

function closeBulkSummaryModal() {
  showBulkSummaryModal.value = false
  // Refresh the page to show newly added cards
  router.reload({ preserveScroll: true })
}

function submitBulkAdd() {
  bulkAddError.value = ''
  
  // Validation
  if (!bulkAddForm.value.entries.trim()) {
    bulkAddError.value = 'Please enter at least one card entry'
    return
  }
  
  if (!bulkAddForm.value.location_id) {
    bulkAddError.value = 'Please select a location'
    return
  }
  
  isProcessingBulkAdd.value = true
  
  router.post('/admin/cards/bulk-add', {
    entries: bulkAddForm.value.entries,
    location_id: bulkAddForm.value.location_id
  }, {
    preserveScroll: true,
    onSuccess: (page) => {
      isProcessingBulkAdd.value = false
      
      // Get bulk_summary from flash session
      const flash = page.props.flash || {}
      const summary = flash.bulk_summary
      
      if (summary && summary.success) {
        bulkAddSummary.value = {
          total_processed: summary.total_processed,
          success_count: summary.success_count,
          failed_count: summary.failed_count,
          failed_entries: summary.failed_entries,
          successful_cards: summary.successful_cards || []
        }
        
        // Close bulk add modal and show summary modal
        closeBulkAddModal()
        showBulkSummaryModal.value = true
      } else {
        bulkAddError.value = 'Failed to process bulk add'
      }
    },
    onError: (errors) => {
      isProcessingBulkAdd.value = false
      console.error('Bulk add failed:', errors)
      
      // Display error message
      if (errors.message) {
        bulkAddError.value = errors.message
      } else if (Object.keys(errors).length > 0) {
        bulkAddError.value = Object.values(errors)[0]
      } else {
        bulkAddError.value = 'Failed to process bulk add. Please try again.'
      }
    },
    onFinish: () => {
      isProcessingBulkAdd.value = false
    }
  })
}
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Manage Cards" />
    <div class="max-w-7xl mx-auto p-4 relative">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-100">Manage Cards</h1>
        <div class="flex gap-2">
          <button 
            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed" 
            @click="updateSetLibrary"
            :disabled="isUpdatingLibrary"
          >
            {{ isUpdatingLibrary ? 'Updating...' : 'Update Set Library' }}
          </button>
          <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition" @click="openAddModal">Lookup Card</button>
          <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition" @click="openBulkAddModal">Bulk Add</button>
        </div>
      </div>

      <!-- Card Grid Display -->
      <div class="bg-gray-800 p-6 rounded-lg shadow text-gray-100">
        <div v-if="props.cards.data && props.cards.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
          <div 
            v-for="card in props.cards.data" 
            :key="card.id" 
            class="bg-gray-700 rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300 flex flex-col p-4 cursor-pointer hover:bg-gray-650"
            @click="openDetailModal(card.id)"
          >
            <!-- Card Name -->
            <h3 class="text-lg font-bold text-center mb-3 line-clamp-2">{{ card.name }}</h3>
            
            <!-- Card Image -->
            <div class="relative aspect-[5/7] bg-gray-900 flex items-center justify-center overflow-hidden rounded mb-3">
              <img 
                v-if="getCardImage(card)" 
                :src="getCardImage(card)" 
                :alt="card.name"
                class="w-full h-full object-cover"
                loading="lazy"
              />
              <div v-else class="text-gray-500 text-sm p-4 text-center">No Image</div>
            </div>
            
            <!-- Layout & Language Table -->
            <table class="w-full text-xs mb-3 border-collapse">
              <thead>
                <tr class="border-b border-gray-600">
                  <th class="py-1 px-2 text-left text-gray-300">Layout</th>
                  <th class="py-1 px-2 text-left text-gray-300">Language</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="py-1 px-2 text-gray-400">{{ card.layout }}</td>
                  <td class="py-1 px-2 text-gray-400">{{ card.lang }}</td>
                </tr>
              </tbody>
            </table>
            
            <!-- Quantity Table -->
            <div class="flex-1">
              <table class="w-full text-xs border-collapse">
                <thead>
                  <tr class="border-b border-gray-600">
                    <th colspan="2" class="py-1 px-2 text-center text-gray-300 font-semibold">Quantity</th>
                  </tr>
                  <tr class="border-b border-gray-600">
                    <th class="py-1 px-2 text-left text-gray-300">Location</th>
                    <th class="py-1 px-2 text-right text-gray-300">Qty</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="card.locations && card.locations.length > 0">
                    <tr v-for="loc in card.locations" :key="loc.location_id" class="border-b border-gray-700">
                      <td class="py-1 px-2 text-gray-400">{{ loc.location_name }}</td>
                      <td class="py-1 px-2 text-right text-gray-200 font-semibold">{{ loc.quantity }}</td>
                    </tr>
                    <tr class="border-t-2 border-gray-600">
                      <td class="py-1 px-2 text-gray-300 font-semibold">TOTAL</td>
                      <td class="py-1 px-2 text-right text-blue-400 font-bold">{{ card.total_quantity || 0 }}</td>
                    </tr>
                  </template>
                  <template v-else>
                    <tr>
                      <td colspan="2" class="py-2 text-center text-gray-500">No instances</td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div v-else class="text-gray-400 text-center py-8">No cards found.</div>

        <!-- Pagination Controls -->
        <div v-if="props.cards.data && props.cards.data.length > 0" class="mt-8 flex items-center justify-center gap-2">
          <!-- Previous Button -->
          <Link
            v-if="props.cards.prev_page_url"
            :href="props.cards.prev_page_url"
            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition"
            preserve-scroll
          >
            Previous
          </Link>
          <button
            v-else
            disabled
            class="px-4 py-2 bg-gray-800 text-gray-500 rounded-lg cursor-not-allowed"
          >
            Previous
          </button>

          <!-- Page Numbers -->
          <div class="flex items-center gap-2">
            <template v-for="page in pageNumbers" :key="page">
              <Link
                v-if="page !== '...'"
                :href="`/admin/cards?page=${page}`"
                :class="[
                  'px-3 py-2 rounded-lg transition',
                  page === props.cards.current_page
                    ? 'bg-blue-600 text-white font-semibold'
                    : 'bg-gray-700 hover:bg-gray-600 text-white'
                ]"
                preserve-scroll
              >
                {{ page }}
              </Link>
              <span v-else class="px-2 text-gray-500">...</span>
            </template>
          </div>

          <!-- Next Button -->
          <Link
            v-if="props.cards.next_page_url"
            :href="props.cards.next_page_url"
            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition"
            preserve-scroll
          >
            Next
          </Link>
          <button
            v-else
            disabled
            class="px-4 py-2 bg-gray-800 text-gray-500 rounded-lg cursor-not-allowed"
          >
            Next
          </button>
        </div>

        <!-- Page Info -->
        <div v-if="props.cards.data && props.cards.data.length > 0" class="mt-4 text-center text-sm text-gray-400">
          Page {{ props.cards.current_page }} of {{ props.cards.last_page }}
        </div>
      </div>

      <!-- Add Card Modal -->
      <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="closeAddModal">
      <div class="bg-gray-800 rounded-lg shadow-xl w-full mx-4 p-6 text-gray-100 max-h-[90vh] overflow-y-auto" :class="apiResponse ? 'max-w-4xl' : 'max-w-lg'">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-2xl font-bold">Lookup Card</h2>
          <button @click="closeAddModal" class="text-gray-400 hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <form @submit.prevent="submitAddForm" class="space-y-4">
          <div>
            <label class="block font-semibold mb-1">Collection Number</label>
            <input 
              v-model="form.collection_number" 
              type="text" 
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100" 
              placeholder="e.g., 123, 045a"
              required 
            />
            <span v-if="errors.collection_number" class="text-red-500 text-sm">{{ errors.collection_number }}</span>
          </div>
          
          <div>
            <label class="block font-semibold mb-1">Set Code</label>
            <input 
              v-model="form.set_code" 
              type="text" 
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100" 
              placeholder="e.g., neo, khm"
              required 
            />
            <span v-if="errors.set_code" class="text-red-500 text-sm">{{ errors.set_code }}</span>
          </div>
          
          <div>
            <label class="block font-semibold mb-1">Language</label>
            <select 
              v-model="form.language" 
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100" 
              required
            >
              <option value="">Select language</option>
              <option v-for="lang in languages" :key="lang.code" :value="lang.code">
                {{ lang.name }} ({{ lang.code }})
              </option>
            </select>
            <span v-if="errors.language" class="text-red-500 text-sm">{{ errors.language }}</span>
          </div>
          
          <div v-if="formError" class="text-red-500 text-sm">{{ formError }}</div>
          
          <div class="flex justify-end gap-2 mt-6">
            <button type="button" @click="closeAddModal" :disabled="isLoadingCard" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
              Cancel
            </button>
            <button type="submit" :disabled="isLoadingCard" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
              {{ isLoadingCard ? 'Loading...' : 'Lookup Card' }}
            </button>
          </div>
        </form>
        
        <!-- Card Data div (expanded when card data is available) -->
        <div v-if="apiResponse" class="mt-6 pt-6 border-t border-gray-700">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Card Data</h3>
            <button @click="apiResponse = null" class="text-gray-400 hover:text-gray-200">&times;</button>
          </div>
          
          <!-- Single-Faced Card Display -->
          <div v-if="!apiResponse.card_faces" class="flex flex-col items-center space-y-4 bg-gray-900 p-6 rounded-lg">
            <h4 class="text-2xl font-bold text-center">{{ apiResponse.name }}</h4>
            <img v-if="apiResponse.image_uris?.normal" :src="apiResponse.image_uris.normal" :alt="apiResponse.name" class="rounded-lg shadow-lg max-w-sm" />
            <div class="flex gap-4 text-center">
              <span class="font-semibold">{{ apiResponse.mana_cost || 'N/A' }}</span>
              <span>•</span>
              <span class="font-semibold">{{ apiResponse.type_line || 'N/A' }}</span>
            </div>
            <p class="text-gray-300 whitespace-pre-line text-center max-w-2xl">{{ apiResponse.oracle_text }}</p>
            <p class="text-xs text-gray-500 mt-4">ID: {{ apiResponse.id }}</p>
          </div>
          
          <!-- Multi-Faced Card Display -->
          <div v-else class="bg-gray-900 p-6 rounded-lg space-y-4">
            <!-- Main card info -->
            <div class="text-center mb-6">
              <h4 class="text-2xl font-bold">{{ apiResponse.name }}</h4>
              <p class="text-sm text-gray-400 mt-1">{{ apiResponse.layout }}</p>
            </div>
            
            <!-- Shared Card Image (when face images are not available) -->
            <div v-if="!apiResponse.card_faces[0]?.image_uris && !apiResponse.card_faces[1]?.image_uris && apiResponse.image_uris?.normal" class="flex justify-center mb-6">
              <img 
                :src="apiResponse.image_uris.normal" 
                :alt="apiResponse.name" 
                class="rounded-lg shadow-lg max-w-sm" 
                :class="{ 'rotate-90': apiResponse.layout === 'split' }"
              />
            </div>
            
            <!-- Two columns -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Left Column (card_faces[0]) -->
              <div id="left_col" class="flex flex-col items-center space-y-3 bg-gray-800 p-4 rounded-lg">
                <h5 class="text-xl font-semibold">{{ apiResponse.card_faces[0]?.name || 'N/A' }}</h5>
                <img 
                  v-if="apiResponse.card_faces[0]?.image_uris?.normal" 
                  :src="apiResponse.card_faces[0].image_uris.normal" 
                  :alt="apiResponse.card_faces[0].name" 
                  class="rounded-lg shadow-lg max-w-full" 
                />
                <div class="flex gap-3 text-sm">
                  <span class="font-semibold">{{ apiResponse.card_faces[0]?.mana_cost || 'N/A' }}</span>
                  <span>•</span>
                  <span class="font-semibold">{{ apiResponse.card_faces[0]?.type_line || 'N/A' }}</span>
                </div>
                <p class="text-gray-300 whitespace-pre-line text-sm text-center">{{ apiResponse.card_faces[0]?.oracle_text }}</p>
              </div>
              
              <!-- Right Column (card_faces[1]) -->
              <div id="right_col" class="flex flex-col items-center space-y-3 bg-gray-800 p-4 rounded-lg">
                <h5 class="text-xl font-semibold">{{ apiResponse.card_faces[1]?.name || 'N/A' }}</h5>
                <img 
                  v-if="apiResponse.card_faces[1]?.image_uris?.normal" 
                  :src="apiResponse.card_faces[1].image_uris.normal" 
                  :alt="apiResponse.card_faces[1].name" 
                  class="rounded-lg shadow-lg max-w-full" 
                />
                <div class="flex gap-3 text-sm">
                  <span class="font-semibold">{{ apiResponse.card_faces[1]?.mana_cost || 'N/A' }}</span>
                  <span>•</span>
                  <span class="font-semibold">{{ apiResponse.card_faces[1]?.type_line || 'N/A' }}</span>
                </div>
                <p class="text-gray-300 whitespace-pre-line text-sm text-center">{{ apiResponse.card_faces[1]?.oracle_text }}</p>
              </div>
            </div>
            
            <!-- ID at bottom -->
            <p class="text-xs text-gray-500 text-center mt-4">ID: {{ apiResponse.id }}</p>
          </div>

          <!-- Location Selector and Add Card Button -->
          <div class="mt-6 pt-6 border-t border-gray-700 space-y-4">
            <div>
              <label class="block font-semibold mb-2">Location</label>
              <select 
                v-model="form.location_id" 
                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100" 
                required
              >
                <option value="">Select a location</option>
                <option v-for="location in props.locations" :key="location.id" :value="location.id">
                  {{ location.name }}
                </option>
              </select>
            </div>

            <button 
              @click="saveCardToLibrary" 
              :disabled="isSavingCard || !form.location_id"
              class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ isSavingCard ? 'Saving...' : 'Add Card To Library' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Set Library Results Modal -->
    <div v-if="showResultsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full mx-4 p-6 text-gray-100">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-2xl font-bold">Set Library Update</h2>
          <button @click="closeResultsModal" :disabled="isUpdatingLibrary" class="text-gray-400 hover:text-gray-200 text-2xl disabled:opacity-50 disabled:cursor-not-allowed">&times;</button>
        </div>
        
        <div class="space-y-4">
          <!-- Processing Status -->
          <div v-if="isUpdatingLibrary" class="bg-gray-700 p-4 rounded-lg">
            <div class="flex items-center gap-3">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
              <div class="text-lg font-semibold">Processing...</div>
            </div>
          </div>
          
          <!-- Summary (shown after completion) -->
          <div v-if="!isUpdatingLibrary" class="bg-gray-700 p-4 rounded-lg">
            <div class="text-lg font-semibold mb-2">Summary</div>
            <div class="text-green-400 text-xl">
              ✓ Successfully imported {{ libraryResults.imported }} new sets
            </div>
          </div>
          
          <!-- Processing Log -->
          <div class="bg-gray-700 p-4 rounded-lg">
            <div class="text-lg font-semibold mb-2">{{ isUpdatingLibrary ? 'Activity Log' : 'Import Log' }}</div>
            <div class="max-h-96 overflow-y-auto space-y-1 font-mono text-sm">
              <div v-for="(entry, index) in libraryResults.log" :key="index" class="text-gray-300 bg-gray-800 p-2 rounded">
                {{ entry }}
              </div>
            </div>
          </div>
          
          <!-- Errors -->
          <div v-if="libraryResults.errors.length > 0" class="bg-gray-700 p-4 rounded-lg">
            <div class="text-lg font-semibold mb-2 text-yellow-400">Errors ({{ libraryResults.errors.length }})</div>
            <div class="max-h-64 overflow-y-auto space-y-2">
              <div v-for="(error, index) in libraryResults.errors" :key="index" class="text-sm text-red-400 bg-gray-800 p-2 rounded">
                {{ error }}
              </div>
            </div>
          </div>
          
          <div class="flex justify-end mt-6">
            <button 
              @click="closeResultsModal" 
              :disabled="isUpdatingLibrary"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ isUpdatingLibrary ? 'Processing...' : 'Close' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Card Detail Modal -->
    <div v-if="showDetailModal" class="absolute inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="closeDetailModal">
      <div class="bg-gray-800 rounded-lg shadow-xl w-full p-6 text-gray-100 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-2xl font-bold">Card Details</h2>
          <button @click="closeDetailModal" class="text-gray-400 hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <!-- Loading State -->
        <div v-if="isLoadingDetail" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
        
        <!-- Card Details (Two Column Layout) -->
        <div v-else-if="selectedCard" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Left Column: Card Info and Actions -->
          <div class="space-y-4">
            <!-- Card Name -->
            <h3 class="text-2xl font-bold">{{ selectedCard.name }}</h3>
            
            <!-- Card Image -->
            <div class="relative aspect-[5/7] bg-gray-900 flex items-center justify-center overflow-hidden rounded">
              <img 
                v-if="getCardImage(selectedCard)" 
                :src="getCardImage(selectedCard)" 
                :alt="selectedCard.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="text-gray-500 text-sm p-4 text-center">No Image</div>
            </div>
            
            <!-- Metadata -->
            <div class="space-y-2 text-sm">
              <p><span class="font-semibold">Date Added:</span> {{ selectedCard.created_at }}</p>
              <p><span class="font-semibold">Last Update:</span> {{ selectedCard.updated_at }}</p>
            </div>
            
            <!-- Update Card Data Button -->
            <div class="border-t border-gray-700 pt-4">
              <button 
                @click="updateCardData" 
                :disabled="isUpdatingCardData"
                class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ isUpdatingCardData ? 'Updating...' : 'Update Card Data from Scryfall' }}
              </button>
            </div>
            
            <!-- Update Card Instance Actions -->
            <div class="border-t border-gray-700 pt-4">
              <h4 class="text-lg font-semibold mb-3">Update Card Instance</h4>
              <div class="flex gap-3">
                <button 
                  @click="openEditModal" 
                  class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                >
                  Edit - Move Locations
                </button>
                <button 
                  @click="openDeleteModal" 
                  class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition"
                >
                  Delete - Remove from Library
                </button>
              </div>
            </div>
            
            <!-- Current Instances -->
            <div class="border-t border-gray-700 pt-4">
              <h4 class="text-lg font-semibold mb-3">Current Locations</h4>
              <table class="w-full text-sm border-collapse">
                <thead>
                  <tr class="border-b border-gray-600">
                    <th class="py-2 px-2 text-left text-gray-300">Location</th>
                    <th class="py-2 px-2 text-right text-gray-300">Qty</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="instance in selectedCard.instances" :key="instance.id" class="border-b border-gray-700">
                    <td class="py-2 px-2 text-gray-400">{{ instance.location_name }}</td>
                    <td class="py-2 px-2 text-right text-gray-200 font-semibold">{{ instance.quantity }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Right Column: Scryfall JSON -->
          <div class="space-y-4">
            <div class="flex justify-between items-center">
              <h4 class="text-lg font-semibold">Scryfall JSON Data</h4>
              <div class="flex border border-gray-600 rounded-lg overflow-hidden">
                <button 
                  @click="activeJsonTab = 'parsed'"
                  :class="[
                    'px-4 py-1.5 text-sm font-medium transition-colors',
                    activeJsonTab === 'parsed' 
                      ? 'bg-blue-600 text-white' 
                      : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                  ]"
                >
                  Parsed
                </button>
                <button 
                  @click="activeJsonTab = 'pretty'"
                  :class="[
                    'px-4 py-1.5 text-sm font-medium transition-colors border-l border-gray-600',
                    activeJsonTab === 'pretty' 
                      ? 'bg-blue-600 text-white' 
                      : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                  ]"
                >
                  Pretty Print
                </button>
                <button 
                  @click="activeJsonTab = 'raw'"
                  :class="[
                    'px-4 py-1.5 text-sm font-medium transition-colors border-l border-gray-600',
                    activeJsonTab === 'raw' 
                      ? 'bg-blue-600 text-white' 
                      : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                  ]"
                >
                  Raw
                </button>
              </div>
            </div>
            <div class="bg-gray-900 rounded-lg p-4 overflow-auto max-h-[70vh]">
              <!-- Parsed View: Structured key-value display -->
              <div v-if="activeJsonTab === 'parsed' && parsedJsonData" class="space-y-3 text-sm">
                <div v-for="(value, key) in parsedJsonData" :key="key" class="border-b border-gray-700 pb-2 last:border-0">
                  <div class="flex gap-2">
                    <span class="font-semibold text-blue-400 min-w-[150px]">{{ key }}:</span>
                    <span v-if="typeof value === 'object' && value !== null" class="text-gray-300 font-mono text-xs">
                      <pre class="whitespace-pre-wrap">{{ JSON.stringify(value, null, 2) }}</pre>
                    </span>
                    <span v-else-if="typeof value === 'boolean'" class="text-yellow-400">{{ value }}</span>
                    <span v-else-if="typeof value === 'number'" class="text-green-400">{{ value }}</span>
                    <span v-else class="text-gray-300">{{ value }}</span>
                  </div>
                </div>
              </div>
              <!-- Pretty Print View -->
              <pre v-else-if="activeJsonTab === 'pretty'" class="text-xs text-gray-300 font-mono whitespace-pre-wrap">{{ selectedCard.scryfall_json }}</pre>
              <!-- Raw View -->
              <pre v-else class="text-xs text-gray-300 font-mono whitespace-pre-wrap break-all">{{ selectedCard.scryfall_json_raw }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal (Move Locations) -->
    <div v-if="showEditModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="closeEditModal">
      <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6 text-gray-100">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Move Card Instances</h2>
          <button @click="closeEditModal" class="text-gray-400 hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <form @submit.prevent="submitMoveInstances" class="space-y-4">
          <div>
            <label class="block font-semibold mb-1">How many card instances to move?</label>
            <input 
              v-model.number="moveForm.quantity" 
              type="number" 
              min="1"
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100" 
              required 
            />
          </div>
          
          <div>
            <label class="block font-semibold mb-1">From which location?</label>
            <select 
              v-model="moveForm.from_location_id" 
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100" 
              required
            >
              <option value="">Select source location</option>
              <option v-for="instance in selectedCard.instances" :key="instance.location_id" :value="instance.location_id">
                {{ instance.location_name }} (Qty: {{ instance.quantity }})
              </option>
            </select>
          </div>
          
          <div>
            <label class="block font-semibold mb-1">To which location?</label>
            <select 
              v-model="moveForm.to_location_id" 
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100" 
              required
            >
              <option value="">Select destination location</option>
              <option v-for="location in props.locations" :key="location.id" :value="location.id">
                {{ location.name }}
              </option>
            </select>
          </div>
          
          <div v-if="moveError" class="text-red-500 text-sm">{{ moveError }}</div>
          
          <div class="flex justify-end gap-2 mt-6">
            <button type="button" @click="closeEditModal" :disabled="isMovingInstances" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition disabled:opacity-50">
              Cancel
            </button>
            <button type="submit" :disabled="isMovingInstances" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition disabled:opacity-50">
              {{ isMovingInstances ? 'Moving...' : 'Move Instances' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Modal (Remove from Library) -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="closeDeleteModal">
      <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6 text-gray-100">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Remove from Library</h2>
          <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <form @submit.prevent="submitRemoveInstances" class="space-y-4">
          <div>
            <label class="block font-semibold mb-1">From which location?</label>
            <select 
              v-model="removeForm.location_id" 
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-gray-100" 
              required
            >
              <option value="">Select location</option>
              <option v-for="instance in selectedCard.instances" :key="instance.location_id" :value="instance.location_id">
                {{ instance.location_name }} (Qty: {{ instance.quantity }})
              </option>
            </select>
          </div>
          
          <div>
            <label class="block font-semibold mb-1">How many to remove?</label>
            <input 
              v-model.number="removeForm.quantity" 
              type="number" 
              min="1"
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-gray-100" 
              required 
            />
          </div>
          
          <div v-if="removeError" class="text-red-500 text-sm">{{ removeError }}</div>
          
          <div class="flex justify-end gap-2 mt-6">
            <button type="button" @click="closeDeleteModal" :disabled="isRemovingInstances" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition disabled:opacity-50">
              Cancel
            </button>
            <button type="submit" :disabled="isRemovingInstances" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition disabled:opacity-50">
              {{ isRemovingInstances ? 'Removing...' : 'Remove Instances' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bulk Add Modal -->
    <div v-if="showBulkAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="closeBulkAddModal">
      <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl mx-4 p-6 text-gray-100 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-2xl font-bold">Bulk Add Cards</h2>
          <button @click="closeBulkAddModal" class="text-gray-400 hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <div class="space-y-4">
          <!-- Instructions -->
          <div class="bg-gray-700 p-4 rounded-lg text-sm space-y-2">
            <p class="font-semibold text-blue-400">Entry Format:</p>
            <p class="font-mono text-gray-300">CollectionNumber SetCode [Language] [Quantity]</p>
            <p class="text-gray-400">Examples:</p>
            <ul class="list-disc list-inside text-gray-400 space-y-1">
              <li><span class="font-mono text-white">123 neo</span> - defaults to English (en), quantity 1</li>
              <li><span class="font-mono text-white">123 neo 2</span> - defaults to English (en), quantity 2</li>
              <li><span class="font-mono text-white">123 neo ja 2</span> - Japanese, quantity 2</li>
            </ul>
            <p class="text-gray-400 mt-2"><strong>Note:</strong> Language defaults to "en" (English) and Quantity defaults to 1 if omitted</p>
          </div>
          
          <!-- Text Area for entries -->
          <div>
            <label class="block font-semibold mb-2">Card Entries (one per line)</label>
            <textarea 
              v-model="bulkAddForm.entries" 
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100 font-mono text-sm" 
              rows="12"
              placeholder="123 neo en 1&#10;045 m21 ja 2&#10;001 khm en"
              required
            ></textarea>
          </div>
          
          <!-- Location Selector -->
          <div>
            <label class="block font-semibold mb-2">Destination Location</label>
            <select 
              v-model="bulkAddForm.location_id" 
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100" 
              required
            >
              <option value="">Select a location</option>
              <option v-for="location in props.locations" :key="location.id" :value="location.id">
                {{ location.name }}
              </option>
            </select>
          </div>
          
          <!-- Error Message -->
          <div v-if="bulkAddError" class="text-red-500 text-sm bg-red-900/20 p-3 rounded-lg border border-red-500/30">
            {{ bulkAddError }}
          </div>
          
          <!-- Processing Indicator -->
          <div v-if="isProcessingBulkAdd" class="bg-blue-900/20 p-4 rounded-lg border border-blue-500/30">
            <div class="flex items-center gap-3">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
              <div class="text-blue-400 font-semibold">Processing entries...</div>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="flex justify-end gap-2 pt-4 border-t border-gray-700">
            <button 
              type="button" 
              @click="closeBulkAddModal" 
              :disabled="isProcessingBulkAdd"
              class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancel
            </button>
            <button 
              @click="submitBulkAdd" 
              :disabled="isProcessingBulkAdd || !bulkAddForm.entries.trim() || !bulkAddForm.location_id"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ isProcessingBulkAdd ? 'Processing...' : 'Add To Library' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Add Summary Modal -->
    <div v-if="showBulkSummaryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="closeBulkSummaryModal">
      <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-3xl mx-4 p-6 text-gray-100 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-2xl font-bold">Bulk Add Summary</h2>
          <button @click="closeBulkSummaryModal" class="text-gray-400 hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <div class="space-y-4">
          <!-- Summary Stats -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-700 p-4 rounded-lg text-center">
              <div class="text-3xl font-bold text-blue-400">{{ bulkAddSummary.total_processed }}</div>
              <div class="text-sm text-gray-400 mt-1">Total Processed</div>
            </div>
            <div class="bg-gray-700 p-4 rounded-lg text-center">
              <div class="text-3xl font-bold text-green-400">{{ bulkAddSummary.success_count }}</div>
              <div class="text-sm text-gray-400 mt-1">Successfully Added</div>
            </div>
            <div class="bg-gray-700 p-4 rounded-lg text-center">
              <div class="text-3xl font-bold" :class="bulkAddSummary.failed_count > 0 ? 'text-red-400' : 'text-gray-400'">
                {{ bulkAddSummary.failed_count }}
              </div>
              <div class="text-sm text-gray-400 mt-1">Failed Entries</div>
            </div>
          </div>
          
          <!-- Overall Result Message -->
          <div 
            class="p-4 rounded-lg text-center font-semibold"
            :class="{
              'bg-green-900/30 border border-green-500/30 text-green-400': bulkAddSummary.failed_count === 0 && bulkAddSummary.success_count > 0,
              'bg-yellow-900/30 border border-yellow-500/30 text-yellow-400': bulkAddSummary.failed_count > 0 && bulkAddSummary.success_count > 0,
              'bg-red-900/30 border border-red-500/30 text-red-400': bulkAddSummary.failed_count > 0 && bulkAddSummary.success_count === 0
            }"
          >
            <template v-if="bulkAddSummary.failed_count === 0 && bulkAddSummary.success_count > 0">
              ✓ All {{ bulkAddSummary.success_count }} cards were successfully added to your library!
            </template>
            <template v-else-if="bulkAddSummary.failed_count > 0 && bulkAddSummary.success_count > 0">
              ⚠ {{ bulkAddSummary.success_count }} cards added successfully, but {{ bulkAddSummary.failed_count }} entries failed.
            </template>
            <template v-else-if="bulkAddSummary.failed_count > 0 && bulkAddSummary.success_count === 0">
              ✗ No cards were added. All {{ bulkAddSummary.failed_count }} entries failed.
            </template>
          </div>
          
          <!-- Successfully Added Cards List -->
          <div v-if="bulkAddSummary.successful_cards && bulkAddSummary.successful_cards.length > 0" class="bg-gray-700 p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-3 text-green-400">Successfully Added Cards</h3>
            <div class="max-h-96 overflow-y-auto space-y-2">
              <div 
                v-for="(card, index) in bulkAddSummary.successful_cards" 
                :key="index" 
                class="bg-gray-800 p-3 rounded border-l-4 border-green-500 flex justify-between items-center"
              >
                <div class="flex-1">
                  <div class="font-semibold text-gray-200">{{ card.name }}</div>
                  <div class="text-xs text-gray-400 mt-1">
                    Language: <span class="text-blue-400">{{ card.language }}</span>
                  </div>
                </div>
                <div class="text-right ml-4">
                  <div class="text-sm text-gray-400">Qty</div>
                  <div class="text-lg font-bold text-green-400">{{ card.quantity }}</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Failed Entries Details -->
          <div v-if="bulkAddSummary.failed_entries && bulkAddSummary.failed_entries.length > 0" class="bg-gray-700 p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-3 text-red-400">Failed Entries</h3>
            <div class="max-h-96 overflow-y-auto space-y-3">
              <div 
                v-for="(failedEntry, index) in bulkAddSummary.failed_entries" 
                :key="index" 
                class="bg-gray-800 p-3 rounded border-l-4 border-red-500"
              >
                <div class="font-mono text-sm text-gray-300 mb-1">{{ failedEntry.line }}</div>
                <div class="text-xs text-red-400">Reason: {{ failedEntry.reason }}</div>
              </div>
            </div>
          </div>
          
          <!-- Close Button -->
          <div class="flex justify-end pt-4 border-t border-gray-700">
            <button 
              @click="closeBulkSummaryModal" 
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
    </div>
  </AuthenticatedLayout>
</template>


