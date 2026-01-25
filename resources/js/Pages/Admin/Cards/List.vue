


<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Modal state
const showAddModal = ref(false)
const showResultsModal = ref(false)
const isUpdatingLibrary = ref(false)

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
  language: ''
})
const errors = ref({})
const formError = ref('')

// Placeholder card data
const cards = ref([
  { id: 1, collection_number: '123', set_code: 'neo', language: 'en', scryfall_name: 'Jin-Gitaxias, Progress Tyrant' },
  { id: 2, collection_number: '045a', set_code: 'khm', language: 'ja', scryfall_name: 'Esika, God of the Tree' }
])

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
    language: ''
  }
  errors.value = {}
  formError.value = ''
}

function closeAddModal() {
  showAddModal.value = false
}

function lookupSet() {
  // TODO: Call backend API to lookup set information
  if (!form.value.set_code) {
    alert('Please enter a set code first')
    return
  }
  alert(`Set lookup for: ${form.value.set_code} (API integration pending)`)
}

function submitAddForm() {
  errors.value = {}
  formError.value = ''
  
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
  
  // TODO: Call backend API to create card
  alert('Card would be submitted! (API integration pending)')
  closeAddModal()
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

function deleteCard(id) {
  if (confirm('Are you sure you want to delete this card?')) {
    // TODO: Call backend API to delete card
    alert('Delete request would be sent! (API integration pending)')
  }
}
</script>

<template>
  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto p-4">
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
          <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition" @click="openAddModal">Add Card</button>
        </div>
      </div>
      <div class="bg-gray-800 p-4 rounded-lg shadow text-gray-100">
        <table class="table w-full">
          <thead>
            <tr class="border-b border-gray-700">
              <th class="text-left p-2">ID</th>
              <th class="text-left p-2">Collection #</th>
              <th class="text-left p-2">Set</th>
              <th class="text-left p-2">Language</th>
              <th class="text-left p-2">Scryfall Name</th>
              <th class="text-left p-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="card in cards" :key="card.id" class="border-b border-gray-700 hover:bg-gray-700">
              <td class="p-2">{{ card.id }}</td>
              <td class="p-2">{{ card.collection_number }}</td>
              <td class="p-2">{{ card.set_code }}</td>
              <td class="p-2">{{ card.language }}</td>
              <td class="p-2">{{ card.scryfall_name }}</td>
              <td class="p-2">
                <button class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded mr-2" @click="goToEdit(card.id)">Edit</button>
                <button class="px-2 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded" @click="deleteCard(card.id)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="cards.length === 0" class="text-gray-400 mt-4">No cards found.</div>
      </div>
    </div>

    <!-- Add Card Modal -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeAddModal">
      <div class="bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 p-6 text-gray-100">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-2xl font-bold">Add Card</h2>
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
            <div class="flex gap-2">
              <input 
                v-model="form.set_code" 
                type="text" 
                class="flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-100" 
                placeholder="e.g., neo, khm"
                required 
              />
              <button 
                type="button" 
                @click="lookupSet" 
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition whitespace-nowrap"
              >
                Set Code Selector
              </button>
            </div>
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
            <button type="button" @click="closeAddModal" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
              Add Card
            </button>
          </div>
        </form>
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
  </AuthenticatedLayout>
</template>


