<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
const props = defineProps({ showLogin: Boolean })
</script>

<template>
  <GuestLayout>
    <h1 class="text-2xl font-bold mb-4">Welcome to Scryhold</h1>
    <div v-if="showLogin">
      <p>Please <a href="/login" class="text-blue-400 underline">log in</a> as admin to access management features.</p>
    </div>
    <div v-else>
      <p>You are logged in as admin.</p>
    </div>
  </GuestLayout>
</template>
