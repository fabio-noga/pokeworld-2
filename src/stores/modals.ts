import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useModalStore = defineStore('modals', () => {
  // ── Full-screen modals (from navbar) ─────────────────────────────
  const pcOpen  = ref(false)
  const dexOpen = ref(false)
  const dexId   = ref(1)

  function openPC()  { pcOpen.value  = true  }
  function closePC() { pcOpen.value  = false }
  function openDex(id = 1) { dexId.value = id; dexOpen.value = true  }
  function closeDex()      {                    dexOpen.value = false }

  // ── Pocket panels (from quick-menu HUD) ──────────────────────────
  const pocketOpen = ref<null | 'pc' | 'dex'>(null)
  const pocketDexId = ref(1)

  function togglePocket(which: 'pc' | 'dex') {
    pocketOpen.value = pocketOpen.value === which ? null : which
  }
  function closePocket() { pocketOpen.value = null }
  function openPocketDex(id = 1) { pocketDexId.value = id; pocketOpen.value = 'dex' }

  // ── Battle overlay ────────────────────────────────────────────────
  const battleOpen = ref(false)
  function openBattle()  { battleOpen.value = true  }
  function closeBattle() { battleOpen.value = false }

  return {
    pcOpen, dexOpen, dexId,
    openPC, closePC, openDex, closeDex,
    pocketOpen, pocketDexId,
    togglePocket, closePocket, openPocketDex,
    battleOpen, openBattle, closeBattle,
  }
})
