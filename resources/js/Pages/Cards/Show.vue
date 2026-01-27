
<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
const props = defineProps({ id: [String, Number] })
</script>

<template>
  <GuestLayout>
    <Head title="Card Details" />
    <div class="max-w-2xl mx-auto mt-8 bg-gray-800 p-6 rounded-lg shadow text-gray-100">
      <h1 class="text-2xl font-bold mb-4">Card Details</h1>
      <p>Details for card ID: {{ id }}</p>
    </div>
  </GuestLayout>
</template>
