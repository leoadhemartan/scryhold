<template>
  <div>
    <h1>Edit Card</h1>
    <p>Form to edit card ID: {{ id }}</p>
  </div>
</template>
<script setup>
const props = defineProps({ id: [String, Number] })
</script>
