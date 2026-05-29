<template>
  <Teleport to="body">
    <div class="fr-backdrop">
      <div class="fr-modal">
        <!-- Header -->
        <div class="fr-header">
          <span class="fr-title">⚡ Welcome, Trainer!</span>
        </div>

        <div class="fr-body">
          <!-- Name input -->
          <label class="fr-label">Your name</label>
          <input
            v-model="name"
            class="fr-input"
            placeholder="Enter your name…"
            maxlength="16"
            autofocus
            @keydown.enter="confirm"
          />

          <!-- Starter selection -->
          <label class="fr-label fr-label--starter">Choose your starter</label>
          <div class="fr-starters">
            <button
              v-for="s in starters"
              :key="s.id"
              class="fr-starter"
              :class="{ selected: chosen === s.id }"
              @click="chosen = s.id"
            >
              <img :src="`/textures/Mini/Gif/${padId(s.id)}.gif`" :alt="s.name" />
              <span>{{ s.name }}</span>
            </button>
          </div>

          <button class="fr-btn" @click="confirm">Let's Go! →</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { padId } from '../data/pokemon'

const props = defineProps<{
  initialName?: string
}>()

const emit = defineEmits<{
  (e: 'confirm', payload: { name: string; starterId: number }): void
}>()

const starters = [
  { id: 1,  name: 'Bulbasaur'  },
  { id: 4,  name: 'Charmander' },
  { id: 7,  name: 'Squirtle'   },
  { id: 25, name: 'Pikachu'    },
]

const name   = ref(props.initialName ?? '')
const chosen = ref(1)  // default: Bulbasaur

function confirm() {
  const finalName = name.value.trim() || `user_${Date.now()}`
  emit('confirm', { name: finalName, starterId: chosen.value })
}
</script>

<style scoped>
.fr-backdrop {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
}

.fr-modal {
  background: #0e1a26;
  border: 2px solid #2a4268;
  width: 320px;
  font-family: 'Pokemon GB', monospace, sans-serif;
  color: #c8e0f0;
  box-shadow: 0 8px 32px rgba(0,0,0,0.7);
}

.fr-header {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px 16px;
  border-bottom: 1px solid #1e3040;
  background: #0b1520;
}
.fr-title {
  font-size: 13px;
  font-weight: bold;
  letter-spacing: 0.5px;
}

.fr-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.fr-label {
  font-size: 9px;
  color: #7a9ab0;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.fr-label--starter {
  margin-top: 4px;
}

.fr-input {
  background: #0b1520;
  border: 1px solid #2a4268;
  color: #c8e0f0;
  font-family: inherit;
  font-size: 11px;
  padding: 8px 10px;
  outline: none;
  transition: border-color 0.2s;
}
.fr-input:focus {
  border-color: #44cc88;
}
.fr-input::placeholder {
  color: #3a5a70;
}

.fr-starters {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
}

.fr-starter {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 8px 4px;
  background: #0b1520;
  border: 1px solid #2a4268;
  color: #7a9ab0;
  font-family: inherit;
  font-size: 7px;
  cursor: pointer;
  transition: border-color 0.15s, color 0.15s, background 0.15s;
}
.fr-starter img {
  width: 36px;
  height: 36px;
  image-rendering: pixelated;
  object-fit: contain;
}
.fr-starter:hover {
  border-color: #44cc88;
  color: #c8e0f0;
}
.fr-starter.selected {
  border-color: #44cc88;
  background: #0d2a1e;
  color: #44cc88;
}

.fr-btn {
  margin-top: 6px;
  padding: 10px;
  background: #1c5030;
  color: #44cc88;
  border: 1px solid #44cc88;
  font-family: inherit;
  font-size: 11px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.15s;
  letter-spacing: 0.5px;
}
.fr-btn:hover {
  background: #245a38;
}
</style>
