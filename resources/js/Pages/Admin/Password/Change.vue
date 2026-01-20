<template>
  <div>
    <h1>Change Password</h1>
    <form @submit.prevent="submit">
      <div>
        <label for="password">New Password</label>
        <input id="password" v-model="form.password" type="password" required />
      </div>
      <div>
        <label for="password_confirmation">Confirm Password</label>
        <input id="password_confirmation" v-model="form.password_confirmation" type="password" required />
      </div>
      <button type="submit">Change Password</button>
    </form>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  password: '',
  password_confirmation: ''
})

function submit() {
  form.post('/password/change')
}
</script>
