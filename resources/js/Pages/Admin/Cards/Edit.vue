

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted } from 'vue'

const props = defineProps({
  id: [String, Number],
  // Optionally, card: Object (if passed from backend)
})

const form = ref({
  collection_number: '',
  set_code: '',
  language: ''
})
const errors = ref({})
const formError = ref('')

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

onMounted(() => {
  // TODO: Fetch card data by ID from backend and populate form
  // For now, simulate with placeholder values
  form.value = {
    collection_number: '123',
    set_code: 'neo',
    language: 'en'
  }
})

function submitForm() {
  errors.value = {}
  formError.value = ''
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
  // Simulate API call
  alert('Card update would be submitted! (API integration pending)')
}
</script>
