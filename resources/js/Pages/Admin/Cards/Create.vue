

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue'

const form = ref({
  collection_number: '',
  set_code: '',
  language: ''
})
const errors = ref({})
const formError = ref('')

// Scryfall language codes (https://scryfall.com/docs/api/languages)
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
  // ...add more as needed
]

function submitForm() {
  // Placeholder for form submission logic
}
</script>

<template>
  <AuthenticatedLayout>
    <div class="max-w-lg mx-auto p-4">
      <div class="bg-gray-800 p-6 rounded-lg shadow text-gray-100">
        <h1 class="text-2xl font-bold mb-4">Add Card</h1>
        <form @submit.prevent="submitForm" class="space-y-4">
          <!-- Form fields go here -->
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

