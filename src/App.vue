<template>
  <router-view />

  <!-- Full-screen modals (navbar) -->
  <PCModal  v-if="modalStore.pcOpen"  @close="modalStore.closePC()" />
  <DexModal v-if="modalStore.dexOpen" :initial-id="modalStore.dexId" @close="modalStore.closeDex()" />

  <!-- Pocket panels (global — opened from QuickMenu inside GameView) -->
  <PCPocket  v-if="modalStore.pocketOpen === 'pc'"  @close="modalStore.closePocket()" />
  <DexPocket v-if="modalStore.pocketOpen === 'dex'" :initial-id="modalStore.pocketDexId" @close="modalStore.closePocket()" />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useSaveStore }  from './stores/save'
import { useModalStore } from './stores/modals'
import PCModal   from './components/PCModal.vue'
import DexModal  from './components/DexModal.vue'
import PCPocket  from './components/PCPocket.vue'
import DexPocket from './components/DexPocket.vue'

const saveStore  = useSaveStore()
const modalStore = useModalStore()

onMounted(() => {
  saveStore.load()
})
</script>

<style>
@import './assets/main.css';
</style>
