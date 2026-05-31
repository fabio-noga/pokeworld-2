<template>
  <div id="red"><div></div></div>
  <div id="pokelines"><div><div></div></div></div>
  <RouterLink to="/game" id="pokebol" title="Map">
    <div><img src="/textures/Header/pokeballcenter.png" alt="pokeball" /></div>
  </RouterLink>

  <!-- Desktop nav — game mode: logout only; default: full nav -->
  <nav id="app-nav" class="nav-desktop">
    <template v-if="!gameMode">
      <RouterLink to="/game" class="hnav-item" title="Map">
        <i class="fa-solid fa-map-location-dot"></i>
        <span>MAP</span>
      </RouterLink>
      <RouterLink to="/pokedex" class="hnav-item hnav-dex" title="Pokédex">
        <img src="/textures/Nav/Pokedex/0.png" class="hnav-dex-img" alt="Pokédex" />
        <span>DEX</span>
      </RouterLink>
      <RouterLink to="/pc" class="hnav-item" title="PC">
        <i class="fa-solid fa-computer"></i>
        <span>PC</span>
      </RouterLink>
      <RouterLink to="/save" class="hnav-item" title="Save">
        <i class="fa-solid fa-gear"></i>
        <span>SAVE</span>
      </RouterLink>
    </template>
    <button id="logout-btn" @click="$emit('navClick')" title="Logout">
      <i class="fa-solid fa-right-from-bracket"></i>
    </button>
  </nav>

  <!-- Mobile burger button -->
  <button class="burger-btn" :class="{ open: menuOpen }" @click="menuOpen = !menuOpen" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>

  <!-- Mobile dropdown -->
  <Transition name="burger-drop">
    <nav v-if="menuOpen" class="nav-mobile" @click="menuOpen = false">
      <template v-if="gameMode">
        <button class="mob-item" @click.stop="$emit('openDex'); menuOpen = false">
          <img src="/textures/Nav/Pokedex/0.png" class="mob-dex-img" alt="" /> DEX
        </button>
        <button class="mob-item" @click.stop="$emit('openPC'); menuOpen = false">
          <i class="fa-solid fa-computer"></i> PC
        </button>
        <RouterLink to="/save" class="mob-item">
          <i class="fa-solid fa-gear"></i> SAVE
        </RouterLink>
      </template>
      <template v-else>
        <RouterLink to="/game" class="mob-item">
          <i class="fa-solid fa-map-location-dot"></i> MAP
        </RouterLink>
        <RouterLink to="/pokedex" class="mob-item">
          <img src="/textures/Nav/Pokedex/0.png" class="mob-dex-img" alt="" /> DEX
        </RouterLink>
        <RouterLink to="/pc" class="mob-item">
          <i class="fa-solid fa-computer"></i> PC
        </RouterLink>
        <RouterLink to="/save" class="mob-item">
          <i class="fa-solid fa-gear"></i> SAVE
        </RouterLink>
      </template>
      <button class="mob-item mob-logout" @click.stop="$emit('navClick'); menuOpen = false">
        <i class="fa-solid fa-right-from-bracket"></i> LOGOUT
      </button>
    </nav>
  </Transition>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

defineProps<{ gameMode?: boolean }>()
defineEmits<{ navClick: []; openPC: []; openDex: [] }>()
const menuOpen = ref(false)
</script>

<style scoped>
/* ── Burger button (mobile only) ───────────────────────── */
.burger-btn {
  display: none;
  position: fixed;
  top: 12px;
  right: 12px;
  z-index: 210;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 5px;
  width: 40px;
  height: 40px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
}
.burger-btn span {
  display: block;
  width: 24px;
  height: 2px;
  background: #fff;
  border-radius: 2px;
  transition: transform 0.2s, opacity 0.2s;
}
/* X when open */
.burger-btn.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.burger-btn.open span:nth-child(2) { opacity: 0; }
.burger-btn.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ── Mobile dropdown ──────────────────────────────────── */
.nav-mobile {
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 70px;
  right: 0;
  width: 180px;
  background: #cc0000;
  border-left: 3px solid #880000;
  border-bottom: 3px solid #880000;
  border-radius: 0 0 0 8px;
  z-index: 209;
  padding: 6px 0;
  box-shadow: -4px 4px 16px rgba(0,0,0,0.4);
}
.mob-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 20px;
  color: rgba(255,255,255,0.8);
  text-decoration: none;
  font-family: 'Press Start 2P', monospace;
  font-size: 8px;
  letter-spacing: 0.5px;
  border: none;
  background: none;
  cursor: pointer;
  width: 100%;
  text-align: left;
  transition: background 0.1s, color 0.1s;
}
.mob-item:hover,
.mob-item.router-link-active { color: #fff; background: rgba(255,255,255,0.12); }
.mob-dex-img {
  width: 16px; height: 16px;
  image-rendering: pixelated;
  filter: invert(1);
  opacity: 0.8;
}
.mob-logout { border-top: 1px solid rgba(255,255,255,0.2); margin-top: 4px; }

/* ── Dropdown animation ────────────────────────────────── */
.burger-drop-enter-active { animation: drop-in  0.15s ease-out; transform-origin: top right; }
.burger-drop-leave-active { animation: drop-out 0.1s  ease-in;  transform-origin: top right; }
@keyframes drop-in  { from { opacity: 0; transform: scaleY(0.6); } to { opacity: 1; transform: scaleY(1); } }
@keyframes drop-out { from { opacity: 1; transform: scaleY(1);   } to { opacity: 0; transform: scaleY(0.6); } }

/* ── Breakpoint ────────────────────────────────────────── */
@media (max-width: 540px) {
  .nav-desktop { display: none !important; }
  .burger-btn  { display: flex; }
}
</style>
