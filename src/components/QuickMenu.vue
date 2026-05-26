<template>
  <div class="qm-root">

    <!-- Classic Pokémon Emerald START menu — renders above the toggle -->
    <Transition name="qm-slide">
      <div v-if="open" class="qm-panel">

        <button class="qm-row" :class="{ sel: modalStore.pocketOpen === 'dex' }" @click="pick('dex')">
          <span class="cur">{{ modalStore.pocketOpen === 'dex' ? '▶' : ' ' }}</span>POKéDEX
        </button>

        <button class="qm-row" :class="{ sel: modalStore.pocketOpen === 'pc' }" @click="pick('pc')">
          <span class="cur">{{ modalStore.pocketOpen === 'pc' ? '▶' : ' ' }}</span>PC
        </button>

        <button class="qm-row" :class="{ sel: padActive }" @click="pickPad">
          <span class="cur">{{ padActive ? '▶' : ' ' }}</span>D-PAD
        </button>

        <button class="qm-row qm-exit" @click="open = false">
          <span class="cur">&nbsp;</span>EXIT
        </button>

      </div>
    </Transition>

    <!-- Toggle tab — sits at the bottom of the root -->
    <button class="qm-toggle" :class="{ open }" @click="open = !open" title="Menu">
      MENU
    </button>

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useModalStore } from '../stores/modals'

const props = defineProps<{ padActive?: boolean }>()
const emit  = defineEmits<{ 'toggle-dpad': [] }>()

const modalStore = useModalStore()
const open = ref(false)

function pick(which: 'pc' | 'dex') {
  modalStore.togglePocket(which)
  open.value = false
}
function pickPad() {
  emit('toggle-dpad')
  open.value = false
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');

.qm-root {
  position: fixed;
  bottom: 240px;
  right: 0;
  z-index: 490;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

/* ── Toggle tab ── */
.qm-toggle {
  font-family: 'Press Start 2P', monospace;
  font-size: 9px;
  letter-spacing: 0.5px;
  color: #2a2a2a;
  background: #f0f0f0;
  border: 2px solid #606060;
  border-right: none;
  border-radius: 4px 0 0 4px;
  padding: 10px 14px;
  cursor: pointer;
  box-shadow: -2px 2px 0 #a0a0a0;
  transition: background 0.1s;
}
.qm-toggle:hover,
.qm-toggle.open {
  background: #fff;
}

/* ── Panel — classic GBA dialog box ── */
.qm-panel {
  margin-bottom: 2px;
  background: #f8f8f8;

  /* Outer dark border + inner white highlight = classic GBA box */
  border: 3px solid #484848;
  border-right: none;
  outline: 1px solid #f8f8f8;
  outline-offset: -4px;

  border-radius: 4px 0 0 4px;
  box-shadow: -3px 3px 0 #484848;
  width: 130px;
}

/* ── Rows ── */
.qm-row {
  display: flex;
  align-items: baseline;
  gap: 4px;
  width: 100%;
  padding: 10px 14px 9px 10px;
  background: transparent;
  border: none;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  color: #1e1e1e;
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  letter-spacing: 0.4px;
  cursor: pointer;
  text-align: left;
  white-space: nowrap;
  transition: background 0.07s;
}
.qm-row:last-child {
  border-bottom: none;
}
.qm-row:hover {
  background: rgba(0, 0, 0, 0.05);
}
.qm-row.sel {
  background: rgba(0, 0, 0, 0.05);
}

/* ▶ cursor character */
.cur {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  width: 12px;
  display: inline-block;
  flex-shrink: 0;
}

/* EXIT item */
.qm-exit {
  color: #1e1e1e;
}

/* ── Slide up from bottom ── */
.qm-slide-enter-active { animation: qm-in  0.12s ease-out; transform-origin: bottom right; }
.qm-slide-leave-active { animation: qm-out 0.09s ease-in;  transform-origin: bottom right; }

@keyframes qm-in  { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes qm-out { from { opacity: 1; transform: translateY(0);     } to { opacity: 0; transform: translateY(10px); } }
</style>
