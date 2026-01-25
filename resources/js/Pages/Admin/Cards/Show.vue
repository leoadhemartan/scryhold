<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
const props = defineProps({ id: [String, Number] })
</script>

<template>
  <AuthenticatedLayout>
    <div class="max-w-2xl mx-auto mt-8 bg-gray-800 p-6 rounded-lg shadow text-gray-100">
      <h1 class="text-2xl font-bold mb-4">Admin: Card Details</h1>
      <p>Details for card ID: {{ id }}</p>
    </div>
  </AuthenticatedLayout>
</template>
