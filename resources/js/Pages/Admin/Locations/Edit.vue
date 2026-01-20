<template>
  <div>
    <h1>Edit Location</h1>
    <p>Form to edit location ID: {{ id }}</p>
  </div>
</template>
<script setup>
const props = defineProps({ id: [String, Number] })
</script>
