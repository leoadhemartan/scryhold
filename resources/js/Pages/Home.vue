<template>
  <div>
    <h1>Welcome to Scryhold</h1>
    <div v-if="showLogin">
      <p>Please <a href="/login">log in</a> as admin to access management features.</p>
    </div>
    <div v-else>
      <p>You are logged in as admin.</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ showLogin: Boolean })
</script>
