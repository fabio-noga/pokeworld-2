<template>
  <main>
    <!-- Mobile tab bar -->
    <div class="mob-tabs">
      <button :class="{ active: tab === 'login' }" @click="tab = 'login'">Login</button>
      <button :class="{ active: tab === 'register' }" @click="tab = 'register'">Registar</button>
    </div>

    <!-- Login -->
    <div class="Log" :class="{ 'mob-hidden': tab !== 'login' }">
      <h1>Login</h1><br />
      <input v-model="loginName" type="text" placeholder="Nome" class="form-control" /><br />
      <input v-model="loginPass" type="password" placeholder="Password" class="form-control" /><br />
      <button @click="doLogin">Login</button>
      <p v-if="authStore.error" class="err">{{ authStore.error }}</p>
    </div>

    <div class="divider"></div>

    <!-- Register -->
    <div class="Reg" :class="{ 'mob-hidden': tab !== 'register' }">
      <h1>Registar</h1><br />
      <input v-model="regName" type="text" placeholder="Nome" class="form-control" /><br />
      <input v-model="regPass" type="password" placeholder="Password" class="form-control" /><br />
      <input v-model="regPass2" type="password" placeholder="Confirmar Password" class="form-control" /><br />

      <h3>{{ selectedPlayerLabel }}</h3>
      <div class="player">
        <div @click="selectPlayer(1)"><img src="/sprites/trainers/FireRed/1.png" /></div>
        <div @click="selectPlayer(2)"><img src="/sprites/trainers/Hiro/1.png" /></div>
        <div @click="selectPlayer(3)"><img src="/sprites/trainers/Old/1.png" /></div>
        <div @click="selectPlayer(4)"><img src="/sprites/trainers/Duncan/1.png" /></div>
        <div @click="selectPlayer(5)"><img src="/sprites/trainers/Ness/1.png" /></div>
      </div><br />

      <h3>{{ selectedPokeLabel }}</h3>
      <div class="player2">
        <div @click="selectPoke(1)"><img src="/textures/Art/001.png" /></div>
        <div @click="selectPoke(4)"><img src="/textures/Art/004.png" /></div>
        <div @click="selectPoke(7)"><img src="/textures/Art/007.png" /></div>
      </div><br />

      <button @click="doRegister">Registar</button>
      <p v-if="regError" class="err">{{ regError }}</p>
    </div>

    <div class="guest-wrap">
      <button class="guest-btn" @click="doGuest">Entrar como Convidado</button>
    </div>

    <p class="info">
      (Se estiveres nesta página depois de registares ou fazeres login,
      é provável que tenhas posto as informações erradas!)
    </p>

    <AppHeader @nav-click="router.push('/')" />
  </main>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import { useAuthStore } from '../stores/auth'
import { useSaveStore } from '../stores/save'

const router = useRouter()
const authStore = useAuthStore()
const saveStore = useSaveStore()

const tab = ref<'login' | 'register'>('login')

const loginName = ref('')
const loginPass = ref('')
const regName = ref('')
const regPass = ref('')
const regPass2 = ref('')
const selectedPlayer = ref(0)
const selectedPoke = ref(0)
const regError = ref('')

const PLAYER_LABELS: Record<number, string> = { 1: 'Red', 2: 'Hiro', 3: 'Old Man', 4: 'Duncan', 5: 'Ness' }
const POKE_LABELS: Record<number, string> = { 1: 'Bulbasaur', 4: 'Charmander', 7: 'Squirtle' }

const selectedPlayerLabel = computed(() =>
  selectedPlayer.value ? PLAYER_LABELS[selectedPlayer.value] : 'Escolhe uma Personagem!'
)
const selectedPokeLabel = computed(() =>
  selectedPoke.value ? POKE_LABELS[selectedPoke.value] : 'Escolhe o teu Pokémon!'
)

function selectPlayer(id: number) { selectedPlayer.value = id }
function selectPoke(id: number) { selectedPoke.value = id }

async function doLogin() {
  await authStore.login(loginName.value, loginPass.value)
  if (authStore.isLoggedIn) {
    saveStore.load()
    router.push('/game')
  }
}

async function doGuest() {
  await authStore.loginGuest()
  if (authStore.isLoggedIn) {
    saveStore.initNewGame('Convidado', 'FireRed', 4)
    router.push('/game')
  }
}

async function doRegister() {
  regError.value = ''
  if (regPass.value !== regPass2.value) { regError.value = 'As passwords não coincidem.'; return }
  if (!selectedPlayer.value) { regError.value = 'Escolhe uma personagem.'; return }
  if (!selectedPoke.value) { regError.value = 'Escolhe um Pokémon inicial.'; return }

  await authStore.register(regName.value, regPass.value)
  if (authStore.isLoggedIn) {
    saveStore.initNewGame(regName.value, PLAYER_LABELS[selectedPlayer.value], selectedPoke.value)
    router.push('/game')
  }
}

// Redirect if already logged in
watch(() => authStore.isLoggedIn, (v) => { if (v) router.push('/game') }, { immediate: true })
</script>

<style scoped>
main {
  margin: 0 10%;
  min-width: 0;
  background-color: #e6e6e6;
  box-shadow: 0 0 10px #888;
  margin-top: 150px;
  display: flex;
  flex-wrap: wrap;
  padding-bottom: 20px;
}
.Log { padding-left: 30px; margin-top: 10px; flex: 1; min-width: 220px; }
.Reg { margin: 10px 0 0 30px; flex: 2; min-width: 300px; }
.form-control { width: 250px; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; }
.player {
  display: flex;
  flex-wrap: wrap;
  border: 1px solid #ccc;
  border-radius: 4px;
  background: white;
  padding: 4px;
  max-height: 94px;
  overflow-y: auto;
}
.player > div {
  background: white;
  width: 68px;
  border: 1px solid #ccc;
  border-radius: 4px;
  margin: 3px 10px;
  cursor: pointer;
}
.player > div:hover { border-color: #66afe9; }
.player > div > img { margin: 0 10px 20px; }
.player2 { display: flex; gap: 10px; }
.player2 > div { cursor: pointer; flex: 1; }
.player2 > div > img { width: 100%; max-width: 133px; }
.player2 > div > img:hover { border: 1px solid #66afe9; }
.divider { border-left: 1px solid #000; height: 640px; width: 0; margin: 20px 0; }
.info { text-align: center; color: grey; width: 100%; padding: 10px; }
.err { color: red; margin-top: 8px; font-size: 0.9rem; }
button {
  padding: 6px 16px;
  background: #ff1c1c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}
button:hover { background: #cc0000; }
.guest-wrap {
  width: 100%;
  padding: 20px 30px 0;
  border-top: 1px solid #ccc;
  margin-top: 10px;
}
.guest-btn {
  width: 100%;
  background: #fff;
  color: #555;
  border: 2px solid #ccc;
  font-weight: normal;
}
.guest-btn:hover { background: #f0f0f0; border-color: #aaa; }

/* ── Mobile tab bar — hidden on desktop ─────────────────── */
.mob-tabs { display: none; }

@media screen and (max-width: 600px) {
  main {
    margin: 80px 0 0;
    box-shadow: none;
    flex-direction: column;
    padding: 0 0 20px;
  }

  /* Tab bar */
  .mob-tabs {
    display: flex;
    width: 100%;
    border-bottom: 2px solid #cc0000;
    margin-bottom: 12px;
  }
  .mob-tabs button {
    flex: 1;
    padding: 12px;
    background: #e6e6e6;
    color: #333;
    border: none;
    border-radius: 0;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
  }
  .mob-tabs button.active {
    background: #cc0000;
    color: #fff;
  }
  .mob-tabs button:hover:not(.active) { background: #d5d5d5; }

  /* Hide inactive panel */
  .mob-hidden { display: none !important; }

  /* Collapse sections to full width */
  .Log, .Reg {
    flex: none;
    width: 100%;
    min-width: 0;
    margin: 0;
    padding: 0 16px;
  }
  .divider { display: none; }
  .form-control { width: 100%; }
  .guest-wrap { padding: 16px 16px 0; }
}

@media screen and (max-width: 830px) and (min-width: 601px) {
  .divider { display: none; }
  main { margin-top: 100px; }
}
</style>
