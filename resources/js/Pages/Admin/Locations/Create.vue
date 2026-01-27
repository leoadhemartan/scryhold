
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Create Location" />
    <div class="max-w-2xl mx-auto mt-8 bg-gray-800 p-6 rounded-lg shadow text-gray-100">
      <h1 class="text-2xl font-bold mb-4">Create Location</h1>
      <p>Form to create a new location will appear here.</p>
    </div>
  </AuthenticatedLayout>
</template>
