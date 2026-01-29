<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
  locations: Array,
  eligibleParents: Array,
});

const showModal = ref(false);
const modalMode = ref('add'); // 'add' or 'edit'
const showDeleteDialog = ref(false);
const locationToDelete = ref(null);
const sortColumn = ref('created_at');
const sortDirection = ref('desc');

const form = useForm({
  id: null,
  name: '',
  location_type: 'Storage',
  deck_type: '',
  commander: '',
  side_deck_parent: null,
  is_default: false,
});

const locationTypes = [
  { value: 'Storage', label: 'Storage' },
  { value: 'Deck', label: 'Deck' },
];

// Add "Side Deck" option if eligible parents exist
const locationTypeOptions = computed(() => {
  const options = [...locationTypes];
  if (props.eligibleParents && props.eligibleParents.length > 0) {
    options.push({ value: 'Side Deck', label: 'Side Deck' });
  }
  return options;
});

const deckTypes = [
  { value: '', label: 'None' },
  { value: 'Standard', label: 'Standard' },
  { value: 'Commander', label: 'Commander' },
  { value: 'Modern', label: 'Modern' },
  { value: 'Legacy', label: 'Legacy' },
  { value: 'Casual', label: 'Casual' },
];

const showDeckTypeField = computed(() => {
  return form.location_type === 'Deck' || form.location_type === 'Side Deck';
});

const showCommanderField = computed(() => {
  return form.location_type === 'Deck' && form.deck_type === 'Commander';
});

const showParentDeckField = computed(() => {
  return form.location_type === 'Side Deck';
});

const canSave = computed(() => {
  if (!form.name) return false;
  if (form.location_type === 'Side Deck' && !form.side_deck_parent) return false;
  return true;
});

const sortedLocations = computed(() => {
  if (!props.locations) return [];
  
  const sorted = [...props.locations].sort((a, b) => {
    let aVal = a[sortColumn.value];
    let bVal = b[sortColumn.value];
    
    // Handle null/undefined values
    if (aVal === null || aVal === undefined) aVal = '';
    if (bVal === null || bVal === undefined) bVal = '';
    
    // Convert to lowercase for string comparison
    if (typeof aVal === 'string') aVal = aVal.toLowerCase();
    if (typeof bVal === 'string') bVal = bVal.toLowerCase();
    
    let comparison = 0;
    if (aVal > bVal) comparison = 1;
    if (aVal < bVal) comparison = -1;
    
    return sortDirection.value === 'asc' ? comparison : -comparison;
  });
  
  return sorted;
});

function sortBy(column) {
  if (sortColumn.value === column) {
    // Toggle direction if clicking the same column
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    // Set new column and default to ascending
    sortColumn.value = column;
    sortDirection.value = 'asc';
  }
}

// Watch location type changes
watch(() => form.location_type, (newValue) => {
  if (newValue === 'Storage') {
    form.deck_type = '';
    form.commander = '';
    form.side_deck_parent = null;
  } else if (newValue === 'Side Deck') {
    form.commander = '';
    // Inherit deck type from parent when selected
  }
});

// Watch parent deck selection to inherit deck type
watch(() => form.side_deck_parent, (newValue) => {
  if (newValue && form.location_type === 'Side Deck') {
    const parent = props.eligibleParents.find(p => p.id === newValue);
    if (parent) {
      form.deck_type = parent.deck_type;
    }
  }
});

// Watch deck type changes
watch(() => form.deck_type, (newValue) => {
  if (newValue !== 'Commander') {
    form.commander = '';
  }
});

function openAddModal() {
  form.reset();
  form.location_type = 'Storage';
  modalMode.value = 'add';
  showModal.value = true;
}

function openEditModal(location) {
  form.id = location.id;
  form.name = location.name;
  form.location_type = location.location_type;
  form.deck_type = location.deck_type || '';
  form.commander = location.commander || '';
  form.side_deck_parent = location.side_deck_parent_id;
  form.is_default = location.is_default;
  modalMode.value = 'edit';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  form.reset();
  form.clearErrors();
}

function submitForm() {
  if (modalMode.value === 'add') {
    form.post(route('admin.locations.store'), {
      onSuccess: () => {
        closeModal();
      },
    });
  } else {
    form.put(route('admin.locations.update', form.id), {
      onSuccess: () => {
        closeModal();
      },
    });
  }
}

function confirmDelete(location) {
  locationToDelete.value = location;
  showDeleteDialog.value = true;
}

function closeDeleteDialog() {
  showDeleteDialog.value = false;
  locationToDelete.value = null;
}

function deleteLocation() {
  if (locationToDelete.value) {
    router.delete(route('admin.locations.destroy', locationToDelete.value.id), {
      onSuccess: () => {
        closeDeleteDialog();
      },
    });
  }
}
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Manage Locations" />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-100">Manage Locations</h1>
        <button
          @click="openAddModal"
          class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition"
        >
          Add Location
        </button>
      </div>

      <!-- Empty State -->
      <div v-if="!locations || locations.length === 0" class="bg-gray-800 rounded-lg p-8 text-center">
        <p class="text-gray-400 mb-4">No locations found. Click 'Add Location' to create your first location.</p>
      </div>

      <!-- Locations Table -->
      <div v-else class="bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-900">
              <tr>
                <th 
                  @click="sortBy('name')"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-800 transition"
                >
                  <div class="flex items-center space-x-1">
                    <span>Name</span>
                    <span v-if="sortColumn === 'name'" class="text-purple-400">
                      {{ sortDirection === 'asc' ? '↑' : '↓' }}
                    </span>
                  </div>
                </th>
                <th 
                  @click="sortBy('location_type')"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-800 transition"
                >
                  <div class="flex items-center space-x-1">
                    <span>Location Type</span>
                    <span v-if="sortColumn === 'location_type'" class="text-purple-400">
                      {{ sortDirection === 'asc' ? '↑' : '↓' }}
                    </span>
                  </div>
                </th>
                <th 
                  @click="sortBy('deck_type')"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-800 transition"
                >
                  <div class="flex items-center space-x-1">
                    <span>Deck Type</span>
                    <span v-if="sortColumn === 'deck_type'" class="text-purple-400">
                      {{ sortDirection === 'asc' ? '↑' : '↓' }}
                    </span>
                  </div>
                </th>
                <th 
                  @click="sortBy('card_count')"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-800 transition"
                >
                  <div class="flex items-center space-x-1">
                    <span>Card Count</span>
                    <span v-if="sortColumn === 'card_count'" class="text-purple-400">
                      {{ sortDirection === 'asc' ? '↑' : '↓' }}
                    </span>
                  </div>
                </th>
                <th 
                  @click="sortBy('is_default')"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-800 transition"
                >
                  <div class="flex items-center space-x-1">
                    <span>Default</span>
                    <span v-if="sortColumn === 'is_default'" class="text-purple-400">
                      {{ sortDirection === 'asc' ? '↑' : '↓' }}
                    </span>
                  </div>
                </th>
                <th 
                  @click="sortBy('commander')"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-800 transition"
                >
                  <div class="flex items-center space-x-1">
                    <span>Commander</span>
                    <span v-if="sortColumn === 'commander'" class="text-purple-400">
                      {{ sortDirection === 'asc' ? '↑' : '↓' }}
                    </span>
                  </div>
                </th>
                <th 
                  @click="sortBy('side_deck_parent')"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-800 transition"
                >
                  <div class="flex items-center space-x-1">
                    <span>Side Deck Parent</span>
                    <span v-if="sortColumn === 'side_deck_parent'" class="text-purple-400">
                      {{ sortDirection === 'asc' ? '↑' : '↓' }}
                    </span>
                  </div>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
              <tr v-for="location in sortedLocations" :key="location.id" class="hover:bg-gray-750">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-100">{{ location.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ location.location_type }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ location.deck_type || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ location.card_count }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span v-if="location.is_default" class="px-2 py-1 bg-green-600 text-white text-xs rounded">Yes</span>
                  <span v-else class="text-gray-500">No</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ location.commander || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ location.side_deck_parent || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                  <button
                    @click="openEditModal(location)"
                    class="text-purple-400 hover:text-purple-300 transition"
                  >
                    Edit
                  </button>
                  <button
                    @click="confirmDelete(location)"
                    class="text-red-400 hover:text-red-300 transition"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-100">
              {{ modalMode === 'add' ? 'Add Location' : 'Edit Location' }}
            </h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1">
                Name <span class="text-red-400">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Enter location name (e.g., Main Binder, Commander Deck - Atraxa)"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:border-purple-500"
                required
              />
              <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
            </div>

            <!-- Location Type -->
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1">
                Location Type <span class="text-red-400">*</span>
              </label>
              <select
                v-model="form.location_type"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:outline-none focus:border-purple-500"
                required
              >
                <option v-for="type in locationTypeOptions" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
              </select>
              <p v-if="form.errors.location_type" class="mt-1 text-sm text-red-400">{{ form.errors.location_type }}</p>
            </div>

            <!-- Deck Type -->
            <div v-if="showDeckTypeField">
              <label class="block text-sm font-medium text-gray-300 mb-1">
                Deck Type
                <span v-if="form.location_type === 'Side Deck'" class="text-sm text-gray-500">(Inherited from parent)</span>
              </label>
              <select
                v-model="form.deck_type"
                :disabled="form.location_type === 'Side Deck'"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:outline-none focus:border-purple-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <option v-for="type in deckTypes" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
              </select>
              <p v-if="form.errors.deck_type" class="mt-1 text-sm text-red-400">{{ form.errors.deck_type }}</p>
            </div>

            <!-- Commander -->
            <div v-if="showCommanderField">
              <label class="block text-sm font-medium text-gray-300 mb-1">
                Commander
              </label>
              <input
                v-model="form.commander"
                type="text"
                placeholder="Enter commander card name (e.g., Atraxa, Praetors' Voice)"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:border-purple-500"
              />
              <p v-if="form.errors.commander" class="mt-1 text-sm text-red-400">{{ form.errors.commander }}</p>
            </div>

            <!-- Parent Deck -->
            <div v-if="showParentDeckField">
              <label class="block text-sm font-medium text-gray-300 mb-1">
                Parent Deck <span class="text-red-400">*</span>
              </label>
              <select
                v-model="form.side_deck_parent"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:outline-none focus:border-purple-500"
                required
              >
                <option :value="null">Select parent deck...</option>
                <option v-for="parent in eligibleParents" :key="parent.id" :value="parent.id">
                  {{ parent.label }}
                </option>
              </select>
              <p v-if="!eligibleParents || eligibleParents.length === 0" class="mt-1 text-sm text-yellow-400">
                No eligible parent decks found. Please create a Commander or Standard deck first.
              </p>
              <p v-if="form.errors.side_deck_parent" class="mt-1 text-sm text-red-400">{{ form.errors.side_deck_parent }}</p>
            </div>

            <!-- Make Default -->
            <div class="flex items-center">
              <input
                v-model="form.is_default"
                type="checkbox"
                id="is_default"
                class="w-4 h-4 text-purple-600 bg-gray-900 border-gray-700 rounded focus:ring-purple-500 focus:ring-2"
              />
              <label for="is_default" class="ml-2 text-sm text-gray-300">
                Set as default location for new cards
              </label>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-700">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-100 rounded-lg transition"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="!canSave || form.processing"
                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ form.processing ? 'Saving...' : 'Save Location' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div v-if="showDeleteDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-100 mb-4">Confirm Deletion</h3>
        <p class="text-gray-300 mb-2">
          Are you sure you want to delete '<span class="font-semibold">{{ locationToDelete?.name }}</span>'?
        </p>
        <p class="text-gray-400 text-sm mb-4">This action cannot be undone.</p>
        <p v-if="locationToDelete && locationToDelete.card_count > 0" class="text-yellow-400 text-sm mb-4">
          ⚠️ This location contains {{ locationToDelete.card_count }} card(s). Deleting will also remove all card assignments.
        </p>
        <div class="flex justify-end space-x-3">
          <button
            @click="closeDeleteDialog"
            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-100 rounded-lg transition"
          >
            Cancel
          </button>
          <button
            @click="deleteLocation"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
