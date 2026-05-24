import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: () => import('../views/HomeView.vue') },
    { path: '/register', component: () => import('../views/RegisterView.vue') },
    { path: '/game', component: () => import('../views/GameView.vue') },
    { path: '/battle', component: () => import('../views/BattleView.vue') },
    { path: '/pokedex', component: () => import('../views/PokedexView.vue') },
    { path: '/pc', component: () => import('../views/PCView.vue') },
    { path: '/save', component: () => import('../views/SaveView.vue') },
  ],
})

export default router
