<template>
  <div id="red"><div></div></div>
  <div id="pokelines"><div><div></div></div></div>
  <RouterLink to="/game" id="pokebol" title="Map">
    <div><img src="/textures/Header/pokeballcenter.png" alt="pokeball" /></div>
  </RouterLink>

  <nav id="app-nav">
    <RouterLink to="/game"    class="hnav-item" title="Map">
      <i class="fa-solid fa-map-location-dot"></i>
      <span>MAP</span>
    </RouterLink>
    <RouterLink to="/pokedex" class="hnav-item hnav-dex" title="Pokédex">
      <img src="/textures/Nav/Pokedex/0.png" class="hnav-dex-img" alt="Pokédex" />
      <span>DEX</span>
    </RouterLink>
    <RouterLink to="/pc"      class="hnav-item" title="PC">
      <i class="fa-solid fa-computer"></i>
      <span>PC</span>
    </RouterLink>
    <RouterLink to="/save"    class="hnav-item" title="Save">
      <i class="fa-solid fa-gear"></i>
      <span>SAVE</span>
    </RouterLink>
    <button id="logout-btn" @click="$emit('navClick')" title="Logout">
      <i class="fa-solid fa-right-from-bracket"></i>
    </button>
  </nav>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
defineEmits<{ navClick: [] }>()
</script>
