<template>
  <div>
    <h1>Admin: Location Details</h1>
    <p>Details for location ID: {{ id }}</p>
  </div>
</template>
<script setup>
const props = defineProps({ id: [String, Number] })
</script>
