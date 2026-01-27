
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
const props = defineProps({ id: [String, Number] })
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Location Details" />
    <div class="max-w-2xl mx-auto mt-8 bg-gray-800 p-6 rounded-lg shadow text-gray-100">
      <h1 class="text-2xl font-bold mb-4">Admin: Location Details</h1>
      <p>Details for location ID: {{ id }}</p>
    </div>
  </AuthenticatedLayout>
</template>
