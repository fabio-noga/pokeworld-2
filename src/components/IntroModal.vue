<template>
  <Teleport to="body">
    <div class="intro-backdrop">
      <div class="intro-box">

        <!-- Image area -->
        <div class="intro-img-area">
          <!-- Step 0: Oak -->
          <img v-if="step === 0" src="/textures/oak/oak.png" class="intro-oak" alt="Professor Oak" />
          <!-- Step 1: chosen starter battle gif -->
          <img v-else-if="step === 1" :src="`/textures/Battle/Normal/Front/Gif/${padId(chosen)}.gif`" class="intro-starter-gif" :alt="starters.find(s => s.id === chosen)?.name" />
          <!-- Step 2: Pikachu -->
          <img v-else src="/textures/Battle/Normal/Front/Gif/025.gif" class="intro-pikachu" alt="Pikachu" />
        </div>

        <!-- Dialog box -->
        <div class="intro-dialog" @click="typing && skipType()">
          <div class="intro-dialog-text">
            <span v-html="displayedText"></span><span v-if="typing" class="intro-cursor">▮</span>
          </div>

          <!-- Name input — step 0 (shown as soon as typing finishes) -->
          <div v-if="step === 0 && !typing" class="intro-name-row">
            <input
              v-model="name"
              class="intro-name-input"
              maxlength="16"
              placeholder="Your name…"
              autofocus
              @keydown.enter="advance"
            />
          </div>

          <!-- Starter selection — step 1 -->
          <div v-if="step === 1 && !typing" class="intro-starters">
            <button
              v-for="s in starters" :key="s.id"
              class="intro-starter"
              :class="{ sel: chosen === s.id }"
              @click="chosen = s.id"
            >
              <img :src="`/textures/Mini/Gif/${padId(s.id)}.gif`" :alt="s.name" />
              <span>{{ s.name }}</span>
            </button>
          </div>

          <!-- Online yes/no — step 2 -->
          <div v-if="step === 2 && !typing" class="intro-yesno">
            <button class="intro-yn yn-yes" @click="finish(true)">YES!</button>
            <button class="intro-yn yn-no"  @click="finish(false)">No thanks</button>
          </div>

          <!-- Back / Next buttons -->
          <div v-if="!typing && step !== 2" class="intro-actions">
            <button v-if="step > 0" class="intro-back" @click="goBack">◀ BACK</button>
            <button class="intro-next" @click="advance">NEXT ▶</button>
          </div>
          <div v-if="step === 2 && !typing" class="intro-back-row">
            <button class="intro-back intro-back--sm" @click="goBack">◀ BACK</button>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { padId } from '../data/pokemon'

const props = defineProps<{
  initialName?: string
  onlineOnly?:  boolean   // skip to online question for existing players
}>()

const emit = defineEmits<{
  (e: 'done', payload: { name: string; starterId: number; online: boolean }): void
}>()

// ── State ─────────────────────────────────────────────────────────
const step   = ref(props.onlineOnly ? 2 : 0)
const name   = ref(props.initialName ?? '')
const chosen = ref(1)   // default: Bulbasaur
const starters = [
  { id: 1,  name: 'Bulbasaur'  },
  { id: 4,  name: 'Charmander' },
  { id: 7,  name: 'Squirtle'   },
  { id: 25, name: 'Pikachu'    },
]

// ── Dialog lines (step 0 = intro + name together, step 1 = starter, step 2 = online) ──
const LINES: Record<number, string> = {
  0: 'Hello there! Welcome to POKéWORLD!\nI\'m OAK, the POKéMON PROF\nfrom Kanto!\nWhat\'s your name, Trainer?',
  1: 'Excellent! Now choose\nyour first partner POKéMON!',
  2: 'One more thing…\nExplore the world with\nother Trainers online?',
}

// ── Typewriter effect ─────────────────────────────────────────────
const displayedText = ref('')
const typing = ref(false)
let typeTimer: ReturnType<typeof setTimeout> | null = null
let fianoTimer: ReturnType<typeof setTimeout> | null = null
let currentHtml = ''   // track exactly what's being typed so skipType shows the right thing

function typeText(text: string) {
  displayedText.value = ''
  typing.value = true
  currentHtml = text.replace(/\n/g, '<br>')
  const html = currentHtml
  let i = 0
  function tick() {
    if (html.slice(i).startsWith('<br>')) {
      displayedText.value += '<br>'
      i += 4
    } else {
      displayedText.value += html[i]
      i++
    }
    if (i < html.length) {
      typeTimer = setTimeout(tick, 12)
    } else {
      typing.value = false
    }
  }
  tick()
}

function skipType() {
  if (!typing.value) return
  if (typeTimer) clearTimeout(typeTimer)
  displayedText.value = currentHtml   // always complete exactly what was being typed
  typing.value = false
}

// ── Navigation ────────────────────────────────────────────────────
const fianoRejected = ref(false)

function goBack() {
  if (typeTimer)  clearTimeout(typeTimer)
  if (fianoTimer) { clearTimeout(fianoTimer); fianoTimer = null }
  typing.value = false
  fianoRejected.value = false
  name.value = ''
  chosen.value = 1
  if (step.value === 0) {
    // Already on step 0 — watcher won't fire, retrigger manually
    typeText(LINES[0])
  } else {
    step.value = 0  // watcher fires typeText
  }
}

function advance() {
  if (typing.value) { skipType(); return }

  // Easter egg — step 0 (name entry)
  if (step.value === 0) {
    const n = name.value.trim().toLowerCase()
    if (n === 'fiano') {
      if (!fianoRejected.value) {
        // First attempt — reject and clear input so they must retype
        fianoRejected.value = true
        name.value = ''
        typeText('No.\nYou can\'t be Fiano.')
        return
      } else {
        // Second attempt — just let them through
        step.value++
        return
      }
    }
    fianoRejected.value = false
  }

  step.value++
}

function finish(online: boolean) {
  const finalName = name.value.trim() || `Trainer`
  emit('done', { name: finalName, starterId: chosen.value, online })
}

watch(step, (s) => { typeText(LINES[s] ?? '') }, { immediate: false })

onMounted(() => { typeText(LINES[step.value]) })
</script>

<style scoped>
.intro-backdrop {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0,0,0,0.6);
  display: flex; align-items: center; justify-content: center;
}

/* ── Modal shell ── */
.intro-box {
  width: min(340px, calc(100vw - 24px));
  background: #f0f0f0;
  border: 4px solid #383838;
  box-shadow: 0 0 0 2px #f0f0f0, 0 0 0 4px #383838, 0 12px 40px rgba(0,0,0,0.7);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* ── Image area ── */
.intro-img-area {
  height: 200px;
  background: #e8e8e8;
  border-bottom: 3px solid #383838;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}
.intro-oak {
  height: 185px;
  image-rendering: pixelated;
  object-fit: contain;
}
.intro-pikachu {
  height: 140px;
  image-rendering: pixelated;
  object-fit: contain;
  margin-bottom: 8px;
}

/* ── Dialog box ── */
.intro-dialog {
  padding: 14px 16px 12px;
  background: #f8f8f8;
  min-height: 88px;
}
.intro-dialog-text {
  font-family: 'Pokemon GB', 'Press Start 2P', monospace;
  font-size: 9px;
  line-height: 1.9;
  color: #1a1a1a;
  min-height: 52px;
}
.intro-cursor {
  animation: blink 0.7s step-end infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }

/* ── Starter battle gif ── */
.intro-starter-gif {
  height: 160px;
  image-rendering: pixelated;
  object-fit: contain;
  margin-bottom: 10px;
}

/* ── Action row (back + next) ── */
.intro-actions {
  display: flex;
  gap: 6px;
  margin-top: 12px;
}
.intro-back-row {
  margin-top: 8px;
}
.intro-next {
  flex: 1;
  padding: 10px 0;
  background: #383838;
  color: #f0f0f0;
  border: none;
  font-family: 'Pokemon GB', 'Press Start 2P', monospace;
  font-size: 9px;
  letter-spacing: 1px;
  cursor: pointer;
  transition: background 0.1s;
}
.intro-next:hover { background: #1a1a1a; }
.intro-back {
  padding: 10px 14px;
  background: #e0e0e0;
  color: #383838;
  border: 2px solid #b0b0b0;
  font-family: 'Pokemon GB', 'Press Start 2P', monospace;
  font-size: 9px;
  letter-spacing: 1px;
  cursor: pointer;
  transition: background 0.1s;
}
.intro-back:hover { background: #cacaca; }
.intro-back--sm { padding: 8px 14px; font-size: 8px; }

/* ── Name input ── */
.intro-name-row { margin-top: 10px; }
.intro-name-input {
  width: 100%; box-sizing: border-box;
  border: 2px solid #383838; padding: 7px 10px;
  font-family: 'Pokemon GB', monospace; font-size: 9px;
  background: #fff; color: #1a1a1a; outline: none;
}
.intro-name-input:focus { border-color: #c82020; }
.intro-name-input::placeholder { color: #aaa; }

/* ── Starter grid ── */
.intro-starters {
  display: grid; grid-template-columns: repeat(4,1fr); gap: 5px;
  margin-top: 10px;
}
.intro-starter {
  display: flex; flex-direction: column; align-items: center; gap: 3px;
  padding: 6px 2px;
  border: 2px solid #b0b0b0; background: #fff;
  font-family: 'Pokemon GB', monospace; font-size: 6px; color: #555;
  cursor: pointer; transition: border-color 0.1s;
}
.intro-starter img { width: 36px; height: 36px; image-rendering: pixelated; }
.intro-starter:hover { border-color: #555; }
.intro-starter.sel { border-color: #c82020; background: #fff4f4; color: #c82020; }

/* ── Yes / No ── */
.intro-yesno { display: flex; gap: 6px; margin-top: 10px; }
.intro-yn {
  flex: 1; padding: 9px 0;
  border: 2px solid #383838;
  font-family: 'Pokemon GB', monospace; font-size: 8px;
  cursor: pointer; background: #fff; color: #1a1a1a;
  transition: background 0.1s;
}
.intro-yn:hover { background: #efefef; }
.yn-yes { border-color: #c82020; color: #c82020; font-weight: bold; }
.yn-yes:hover { background: #fff0f0; }
</style>
