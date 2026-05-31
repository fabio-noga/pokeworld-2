<template>
  <router-view />

  <!-- Full-screen modals (navbar / fullscreen button) -->
  <PCModal  v-if="modalStore.pcOpen"  @close="modalStore.closePC()" />
  <DexModal v-if="modalStore.dexOpen" :initial-id="modalStore.dexId" @close="modalStore.closeDex()" />

  <!-- Pocket panels (global — opened from QuickMenu inside GameView) -->
  <PCPocket  v-if="modalStore.pocketOpen === 'pc'"  @close="modalStore.closePocket()" />
  <DexPocket v-if="modalStore.pocketOpen === 'dex'" :initial-id="modalStore.pocketDexId" @close="modalStore.closePocket()" />

  <!-- Battle overlay — keeps GameView (and WebRTC) mounted -->
  <div v-if="modalStore.battleOpen" class="battle-overlay">
    <BattleView @close="modalStore.closeBattle()" @close-open-pc="onBattleOpenPC()" />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useSaveStore }  from './stores/save'
import { useModalStore } from './stores/modals'
import PCModal    from './components/PCModal.vue'
import DexModal   from './components/DexModal.vue'
import PCPocket   from './components/PCPocket.vue'
import DexPocket  from './components/DexPocket.vue'
import BattleView from './views/BattleView.vue'

const saveStore  = useSaveStore()
const modalStore = useModalStore()

onMounted(() => {
  saveStore.load()
})

function onBattleOpenPC() {
  modalStore.closeBattle()
  modalStore.openPC()
}
</script>

<style>
@import './assets/main.css';

.battle-overlay {
  position: fixed;
  inset: 0;
  z-index: 8000;
}
</style>
