<template>
  <div>
    <h1>Admin: Card Details</h1>
    <p>Details for card ID: {{ id }}</p>
  </div>
</template>
<script setup>
const props = defineProps({ id: [String, Number] })
</script>
