<template>
  <div id="red"><div></div></div>
  <div id="pokelines"><div><div></div></div></div>
  <RouterLink to="/" id="pokebol" title="Home">
    <div><img src="/textures/Header/pokeballcenter.png" alt="pokeball" /></div>
  </RouterLink>

  <nav id="app-nav">
    <RouterLink to="/save" id="gear-btn" title="Save & Settings">
      <i class="fa-solid fa-gear"></i>
    </RouterLink>
    <RouterLink to="/pokedex" class="nav-link">
      <span class="nav-icon">📖</span>POKÉDEX
    </RouterLink>
    <button id="nav-btn" @click="$emit('navClick')">{{ label }}</button>
  </nav>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
defineProps<{ label: string }>()
defineEmits<{ navClick: [] }>()
</script>
