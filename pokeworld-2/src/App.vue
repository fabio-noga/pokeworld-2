<template>
  <router-view />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useSaveStore } from './stores/save'

const saveStore = useSaveStore()

onMounted(() => {
  saveStore.load()
})
</script>

<style>
@import './assets/main.css';
</style>
