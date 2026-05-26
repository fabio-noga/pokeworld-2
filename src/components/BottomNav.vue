<template>
  <nav class="bottom-nav">
    <RouterLink to="/game" class="bnav-item" title="Map">
      <i class="fa-solid fa-map-location-dot"></i>
      <span>MAP</span>
    </RouterLink>
    <RouterLink to="/pokedex" class="bnav-item" title="Pokédex">
      <i class="fa-solid fa-book-open"></i>
      <span>DEX</span>
    </RouterLink>
    <RouterLink to="/pc" class="bnav-item" title="PC">
      <i class="fa-solid fa-computer"></i>
      <span>PC</span>
    </RouterLink>
    <RouterLink to="/save" class="bnav-item" title="Settings">
      <i class="fa-solid fa-gear"></i>
      <span>SAVE</span>
    </RouterLink>
  </nav>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 16px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 4px;
  background: rgba(20, 20, 20, 0.92);
  border: 2px solid #cc0000;
  border-radius: 12px;
  padding: 6px 10px;
  z-index: 300;
  box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}

.bnav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 6px 14px;
  color: rgba(255,255,255,0.5);
  text-decoration: none;
  border-radius: 8px;
  transition: color 0.15s, background 0.15s;
  font-family: 'Press Start 2P', monospace;
}

.bnav-item i {
  font-size: 18px;
}

.bnav-item span {
  font-size: 6px;
  letter-spacing: 0.5px;
}

.bnav-item:hover {
  color: #fff;
  background: rgba(255,255,255,0.07);
}

.bnav-item.router-link-active {
  color: #ff4444;
}
</style>
