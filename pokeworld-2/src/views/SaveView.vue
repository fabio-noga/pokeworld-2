<template>
  <AppHeader label="JOGAR" @nav-click="router.push('/game')" />

  <main>
    <div class="page-title">
      <h1>💾 Save Manager</h1>
      <p>Export, import or wipe your save data</p>
    </div>

    <div class="bento-grid">

      <!-- Player snapshot -->
      <div class="bento-cell cell-player">
        <div class="cell-header">🧑 Trainer</div>
        <div class="player-info" v-if="saveStore.hasSave()">
          <img
            :src="`/textures/Players/${saveStore.playerData.sprite}/Front.png`"
            class="trainer-sprite"
            alt="trainer"
          />
          <div class="player-stats">
            <div class="stat-row"><span class="stat-label">Name</span><span class="stat-val">{{ saveStore.playerData.nome }}</span></div>
            <div class="stat-row"><span class="stat-label">Money</span><span class="stat-val">${{ saveStore.playerData.dinheiro }}</span></div>
            <div class="stat-row"><span class="stat-label">Team</span><span class="stat-val">{{ activeTeam }} Pokémon</span></div>
            <div class="stat-row"><span class="stat-label">PC</span><span class="stat-val">{{ saveStore.pc.filter(p => p.id > 0).length }} stored</span></div>
            <div class="stat-row"><span class="stat-label">Pokédex</span><span class="stat-val">{{ caughtCount }} caught / {{ seenCount }} seen</span></div>
            <div class="stat-row"><span class="stat-label">Shiny</span><span class="stat-val">✨ {{ shinyCaughtCount }} caught</span></div>
          </div>
        </div>
        <div class="no-save" v-else>No save found</div>
      </div>

      <!-- Export -->
      <div class="bento-cell cell-export">
        <div class="cell-header">📤 Export Save</div>
        <p class="cell-desc">Download your save file as <code>.pkwsave</code>. Keep it safe — you can reimport it anytime.</p>
        <button class="action-btn btn-export" @click="handleExport" :disabled="!saveStore.hasSave()">
          ⬇ Download Save
        </button>
        <div class="feedback success" v-if="exportMsg">{{ exportMsg }}</div>
      </div>

      <!-- Import -->
      <div class="bento-cell cell-import">
        <div class="cell-header">📥 Import Save</div>
        <p class="cell-desc">Load a <code>.pkwsave</code> file. This will overwrite your current save.</p>
        <label class="file-label">
          <input type="file" accept=".pkwsave,.txt" @change="handleImport" ref="fileInput" />
          ⬆ Choose File
        </label>
        <div class="feedback success" v-if="importMsg === 'ok'">Save loaded successfully!</div>
        <div class="feedback error" v-else-if="importMsg === 'err'">Invalid save file.</div>
      </div>

      <!-- Settings -->
      <div class="bento-cell cell-settings">
        <div class="cell-header">⚙ Settings</div>
        <div class="setting-row">
          <span class="setting-label">XP Share</span>
          <button
            class="toggle-btn"
            :class="{ active: saveStore.xpShare }"
            @click="toggleXpShare"
          >
            {{ saveStore.xpShare ? 'ON' : 'OFF' }}
          </button>
        </div>
        <div class="setting-row">
          <span class="setting-label">XP Rate</span>
          <div class="multiplier-ctrl">
            <button class="mult-btn" @click="adjustXp(-1)" :disabled="saveStore.xpMultiplier <= 1">−</button>
            <span class="mult-val">×{{ saveStore.xpMultiplier }}</span>
            <button class="mult-btn" @click="adjustXp(1)" :disabled="saveStore.xpMultiplier >= 10">+</button>
          </div>
        </div>
      </div>

      <!-- Danger zone -->
      <div class="bento-cell cell-danger">
        <div class="cell-header danger-header">⚠ Danger Zone</div>
        <p class="cell-desc">Wiping your save is <strong>permanent</strong> and cannot be undone. Export first!</p>
        <button class="action-btn btn-wipe" @click="askWipe" :disabled="!saveStore.hasSave()">
          🗑 Wipe Save Data
        </button>
        <div v-if="wipeConfirm" class="confirm-box">
          <p>Are you sure? This cannot be undone.</p>
          <div class="confirm-btns">
            <button class="action-btn btn-confirm-yes" @click="confirmWipe">Yes, delete</button>
            <button class="action-btn btn-confirm-no" @click="wipeConfirm = false">Cancel</button>
          </div>
        </div>
        <div class="feedback success" v-if="wipeMsg">{{ wipeMsg }}</div>
      </div>

    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import { useSaveStore } from '../stores/save'

const router = useRouter()
const saveStore = useSaveStore()
const fileInput = ref<HTMLInputElement | null>(null)

const exportMsg = ref('')
const importMsg = ref<'' | 'ok' | 'err'>('')
const wipeConfirm = ref(false)
const wipeMsg = ref('')

const activeTeam = computed(() => saveStore.team.filter(p => p.id > 0).length)
const caughtCount = computed(() => Object.values(saveStore.pokedex).filter(v => v === 'caught').length)
const seenCount = computed(() => Object.values(saveStore.pokedex).length)
const shinyCaughtCount = computed(() => Object.values(saveStore.shinydex).filter(v => v === 'caught').length)

function handleExport() {
  saveStore.exportSave()
  exportMsg.value = 'File downloaded!'
  setTimeout(() => (exportMsg.value = ''), 3000)
}

async function handleImport(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  const text = await file.text()
  const ok = saveStore.importSave(text)
  importMsg.value = ok ? 'ok' : 'err'
  setTimeout(() => (importMsg.value = ''), 3000)
  if (fileInput.value) fileInput.value.value = ''
}

function askWipe() {
  wipeConfirm.value = true
}

function confirmWipe() {
  localStorage.removeItem('pkw_save')
  saveStore.load()
  wipeConfirm.value = false
  wipeMsg.value = 'Save wiped.'
  setTimeout(() => (wipeMsg.value = ''), 3000)
}

function toggleXpShare() {
  saveStore.xpShare = !saveStore.xpShare
  saveStore.save()
}

function adjustXp(delta: number) {
  saveStore.xpMultiplier = Math.min(10, Math.max(1, saveStore.xpMultiplier + delta))
  saveStore.save()
}
</script>

<style scoped>
main {
  padding-top: 90px;
  min-height: 100vh;
  background-color: #d0d0d0;
  background-image: radial-gradient(circle, #aaaaaa 1.5px, transparent 1.5px);
  background-size: 16px 16px;
}

.page-title {
  text-align: center;
  padding: 24px 16px 8px;
}
.page-title h1 {
  font-family: 'Press Start 2P', monospace;
  font-size: 18px;
  color: #1a3080;
  text-shadow: 2px 2px 0 rgba(0,0,0,0.12);
}
.page-title p {
  margin-top: 8px;
  font-size: 13px;
  color: #444;
}

/* ── Bento grid ──────────────────────────────────────────────────── */
.bento-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: auto auto auto;
  gap: 16px;
  max-width: 820px;
  margin: 16px auto 48px;
  padding: 0 16px;
}

.bento-cell {
  background: #f0f8ff;
  border: 3px solid #2848a8;
  border-radius: 4px;
  box-shadow: 0 5px 0 #1a3080;
  padding: 18px 20px 20px;
}

.cell-header {
  font-family: 'Press Start 2P', monospace;
  font-size: 9px;
  color: #1a3080;
  letter-spacing: 0.5px;
  margin-bottom: 14px;
  padding-bottom: 8px;
  border-bottom: 2px solid #c0d4f0;
}

.danger-header { color: #c0392b; }

/* ── Player cell ─────────────────────────────────────────────────── */
.cell-player {
  grid-column: 1 / 2;
  grid-row: 1 / 2;
}

.player-info {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}
.trainer-sprite {
  width: 64px;
  height: 64px;
  image-rendering: pixelated;
  flex-shrink: 0;
}
.player-stats { flex: 1; }
.stat-row {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  padding: 3px 0;
  border-bottom: 1px dashed #c0d4f0;
}
.stat-label { color: #555; }
.stat-val { font-weight: bold; color: #1a3080; }
.no-save { color: #888; font-size: 12px; font-style: italic; }

/* ── Export cell ─────────────────────────────────────────────────── */
.cell-export {
  grid-column: 2 / 3;
  grid-row: 1 / 2;
}

/* ── Import cell ─────────────────────────────────────────────────── */
.cell-import {
  grid-column: 1 / 2;
  grid-row: 2 / 3;
}

/* ── Settings cell ───────────────────────────────────────────────── */
.cell-settings {
  grid-column: 2 / 3;
  grid-row: 2 / 3;
}

/* ── Danger cell ─────────────────────────────────────────────────── */
.cell-danger {
  grid-column: 1 / 3;
  grid-row: 3 / 4;
  border-color: #c0392b;
  box-shadow: 0 5px 0 #7b241c;
  background: #fff5f5;
}

/* ── Shared UI ───────────────────────────────────────────────────── */
.cell-desc {
  font-size: 12px;
  color: #444;
  line-height: 1.6;
  margin-bottom: 14px;
}
.cell-desc code {
  background: #dde8ff;
  padding: 1px 5px;
  border-radius: 3px;
  font-size: 11px;
  color: #1a3080;
}

.action-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  letter-spacing: 0.5px;
  transition: opacity 0.15s, transform 0.1s;
}
.action-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.action-btn:not(:disabled):hover { opacity: 0.85; }
.action-btn:not(:disabled):active { transform: translateY(2px); }

.btn-export {
  background: #2848a8;
  color: #fff;
  box-shadow: 0 3px 0 #1a3080;
}
.btn-wipe {
  background: #c0392b;
  color: #fff;
  box-shadow: 0 3px 0 #7b241c;
}
.btn-confirm-yes {
  background: #c0392b;
  color: #fff;
  box-shadow: 0 3px 0 #7b241c;
  font-size: 7px;
}
.btn-confirm-no {
  background: #555;
  color: #fff;
  box-shadow: 0 3px 0 #222;
  font-size: 7px;
}

/* File input styled as button */
.file-label {
  display: inline-block;
  font-family: 'Press Start 2P', monospace;
  font-size: 8px;
  padding: 10px 16px;
  background: #2848a8;
  color: #fff;
  border-radius: 3px;
  cursor: pointer;
  box-shadow: 0 3px 0 #1a3080;
  letter-spacing: 0.5px;
  transition: opacity 0.15s;
}
.file-label:hover { opacity: 0.85; }
.file-label input[type="file"] { display: none; }

/* Feedback messages */
.feedback {
  margin-top: 10px;
  font-size: 11px;
  padding: 6px 10px;
  border-radius: 3px;
}
.feedback.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.feedback.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

/* Confirm box */
.confirm-box {
  margin-top: 14px;
  padding: 12px;
  background: #ffe0de;
  border: 1px solid #f5c6cb;
  border-radius: 3px;
}
.confirm-box p {
  font-size: 12px;
  color: #721c24;
  margin-bottom: 10px;
}
.confirm-btns { display: flex; gap: 10px; }

/* Settings */
.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px dashed #c0d4f0;
}
.setting-label {
  font-size: 12px;
  color: #333;
}
.toggle-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  padding: 5px 12px;
  border: 2px solid #aaa;
  border-radius: 3px;
  background: #ddd;
  color: #555;
  cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.toggle-btn.active {
  background: #2848a8;
  color: #fff;
  border-color: #1a3080;
}
.multiplier-ctrl {
  display: flex;
  align-items: center;
  gap: 8px;
}
.mult-btn {
  width: 26px;
  height: 26px;
  border: 2px solid #2848a8;
  border-radius: 3px;
  background: #f0f8ff;
  color: #1a3080;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.mult-btn:hover:not(:disabled) { background: #dde8ff; }
.mult-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.mult-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 10px;
  color: #1a3080;
  min-width: 28px;
  text-align: center;
}

/* Responsive */
@media (max-width: 600px) {
  .bento-grid {
    grid-template-columns: 1fr;
  }
  .cell-player,
  .cell-export,
  .cell-import,
  .cell-settings,
  .cell-danger {
    grid-column: 1 / 2;
    grid-row: auto;
  }
}
</style>
