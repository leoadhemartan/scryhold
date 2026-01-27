
<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <GuestLayout>
    <Head title="Card Search" />
    <div class="bg-gray-800 p-6 rounded-lg shadow text-gray-100 max-w-2xl mx-auto mt-8">
      <h1 class="text-2xl font-bold mb-4">Cards</h1>
      <p>Publicly viewable list of cards will appear here.</p>
    </div>
  </GuestLayout>
</template>
