<template>
  <Teleport to="body">
    <div class="mp-backdrop" @click.self="$emit('close')">
      <div class="mp-modal">
        <!-- Header -->
        <div class="mp-header">
          <span class="mp-title">🌐 Multiplayer</span>
          <button class="mp-close" @click="$emit('close')">✕</button>
        </div>

        <div class="mp-body">
          <!-- Status -->
          <div class="mp-status-row">
            <span class="mp-dot" :class="isOnline ? 'dot-on' : 'dot-off'"></span>
            <span class="mp-status-text">{{ statusMsg }}</span>
          </div>

          <!-- Connected: player list -->
          <template v-if="isOnline">
            <div class="mp-players">
              <div class="mp-player-row mp-self">
                <img :src="`/sprites/trainers/${saveStore.playerData.sprite}/image.png`" />
                <span>{{ saveStore.playerData.nome || 'You' }} <em>(you)</em></span>
              </div>
              <div v-for="p in mpStore.playersList" :key="p.id" class="mp-player-row">
                <img :src="`/sprites/trainers/${p.sprite}/image.png`" />
                <span>{{ p.name }}</span>
              </div>
            </div>
            <button class="mp-btn danger" @click="doDisconnect">Leave Room</button>
          </template>

          <!-- Offline: reconnect -->
          <template v-else>
            <p class="mp-hint">Searching for a room automatically when you enter the game.</p>
            <button class="mp-btn primary" :disabled="reconnecting" @click="doReconnect">
              {{ reconnecting ? 'Connecting…' : 'Reconnect' }}
            </button>
          </template>

          <!-- Private room — always visible -->
          <div class="mp-divider"></div>
          <div class="mp-private">
            <div class="mp-private-label">PRIVATE ROOM</div>
            <div class="mp-private-row">
              <input
                v-model="privateKey"
                class="mp-key-input"
                type="text"
                inputmode="numeric"
                maxlength="5"
                placeholder="5-digit key"
                @keydown.enter="doJoinPrivate"
              />
              <button class="mp-btn primary mp-private-btn" :disabled="privateKey.length !== 5 || joiningPrivate" @click="doJoinPrivate">
                {{ joiningPrivate ? '…' : 'JOIN' }}
              </button>
            </div>
            <p v-if="privateError" class="mp-private-error">{{ privateError }}</p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { isOnline, statusMsg, autoConnect, disconnect, joinPrivateRoom } from '../composables/useMultiplayer'
import { useMultiplayerStore } from '../stores/multiplayer'
import { useSaveStore } from '../stores/save'
import { getOrCreateUUID } from '../utils/uuid'

const props = defineProps<{
  currentTile: number
  currentDir: 'up' | 'down' | 'left' | 'right'
}>()

defineEmits<{ (e: 'close'): void }>()

const mpStore   = useMultiplayerStore()
const saveStore = useSaveStore()

const reconnecting  = ref(false)
const privateKey    = ref('')
const joiningPrivate = ref(false)
const privateError  = ref('')

function playerInfo() {
  const followerId = saveStore.team[0]?.id ?? 1
  return {
    uuid:       getOrCreateUUID(),
    name:       saveStore.playerData.nome || 'Trainer',
    sprite:     String(saveStore.playerData.sprite),
    tile:       props.currentTile,
    dir:        props.currentDir,
    followerId,
    shiny:      saveStore.shinydex?.[String(followerId)] === 'caught',
  }
}

function doDisconnect() {
  disconnect()
}

async function doReconnect() {
  reconnecting.value = true
  await autoConnect(playerInfo())
  reconnecting.value = false
}

async function doJoinPrivate() {
  const key = privateKey.value.trim()
  if (!/^\d{5}$/.test(key)) { privateError.value = 'Enter exactly 5 digits'; return }
  privateError.value = ''
  joiningPrivate.value = true
  await joinPrivateRoom(key, playerInfo())
  joiningPrivate.value = false
  if (statusMsg.value === 'Private room is full') {
    privateError.value = 'Room is full (15/15)'
  }
}
</script>

<style scoped>
.mp-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.65);
  display: flex; align-items: center; justify-content: center;
}
.mp-modal {
  background: #0e1a26;
  border: 2px solid #2a4268;
  width: 280px;
  font-family: 'Pokemon GB', monospace, sans-serif;
  color: #c8e0f0;
}
.mp-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px; border-bottom: 1px solid #1e3040; background: #0b1520;
}
.mp-title { font-size: 13px; font-weight: bold; }
.mp-close { background: none; border: none; color: #c8e0f0; cursor: pointer; font-size: 14px; }

.mp-body { padding: 14px; display: flex; flex-direction: column; gap: 12px; }

.mp-status-row { display: flex; align-items: center; gap: 8px; }
.mp-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dot-on  { background: #44cc88; box-shadow: 0 0 6px #44cc88; }
.dot-off { background: #555; }
.mp-status-text { font-size: 11px; color: #7a9ab0; }

.mp-hint { font-size: 10px; color: #6a8aa0; margin: 0; }

.mp-players { display: flex; flex-direction: column; gap: 5px; max-height: 160px; overflow-y: auto; }
.mp-player-row {
  display: flex; align-items: center; gap: 8px;
  background: #0b1520; padding: 4px 8px;
}
.mp-player-row img { width: 28px; height: 28px; image-rendering: pixelated; object-fit: contain; }
.mp-player-row span { font-size: 11px; }
.mp-player-row em { color: #44cc88; font-style: normal; font-size: 10px; }
.mp-self { border-left: 2px solid #44cc88; }

.mp-btn {
  padding: 8px; border: none; font-size: 11px; font-family: inherit;
  cursor: pointer; font-weight: bold;
}
.mp-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.mp-btn.primary { background: #1c5030; color: #44cc88; border: 1px solid #44cc88; }
.mp-btn.primary:hover:not(:disabled) { background: #245a38; }
.mp-btn.danger  { background: #3a1010; color: #cc4444; border: 1px solid #cc4444; }
.mp-btn.danger:hover { background: #4a1818; }

/* ── Private room section ── */
.mp-divider { border-top: 1px solid #1e3040; margin: 0 -14px; }
.mp-private { display: flex; flex-direction: column; gap: 8px; }
.mp-private-label { font-size: 9px; color: #3a5a70; letter-spacing: 1px; }
.mp-private-row { display: flex; gap: 6px; }
.mp-key-input {
  flex: 1; background: #0b1520; border: 1px solid #2a4268; color: #c8e0f0;
  font-family: 'Pokemon GB', monospace, sans-serif; font-size: 12px;
  padding: 6px 8px; outline: none; letter-spacing: 3px; text-align: center;
}
.mp-key-input::placeholder { letter-spacing: 0; color: #3a5a70; font-size: 9px; }
.mp-key-input:focus { border-color: #44cc88; }
.mp-private-btn { padding: 6px 12px; flex-shrink: 0; }
.mp-private-error { font-size: 9px; color: #cc4444; margin: 0; }
</style>
