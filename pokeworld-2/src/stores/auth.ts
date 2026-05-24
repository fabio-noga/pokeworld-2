import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  createUserWithEmailAndPassword,
  signInWithEmailAndPassword,
  signInAnonymously,
  signOut,
  onAuthStateChanged,
  type User,
} from 'firebase/auth'
import { auth } from '../firebase'

const toEmail = (username: string) => `${username.toLowerCase().trim()}@pkw.local`

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isLoggedIn = ref(false)
  const isGuest = ref(false)
  const error = ref('')

  if (auth) {
    onAuthStateChanged(auth, (u) => {
      user.value = u
      isLoggedIn.value = !!u
      isGuest.value = u?.isAnonymous ?? false
    })
  }

  async function login(username: string, password: string) {
    error.value = ''
    if (!auth) { error.value = 'Firebase não configurado (.env em falta).'; return }
    try {
      await signInWithEmailAndPassword(auth, toEmail(username), password)
    } catch {
      error.value = 'Nome ou password incorretos.'
    }
  }

  async function register(username: string, password: string) {
    error.value = ''
    if (!auth) { error.value = 'Firebase não configurado (.env em falta).'; return }
    try {
      await createUserWithEmailAndPassword(auth, toEmail(username), password)
    } catch {
      error.value = 'Erro no registo. O nome pode já estar em uso.'
    }
  }

  async function loginGuest() {
    error.value = ''
    if (!auth) { error.value = 'Firebase não configurado (.env em falta).'; return }
    try {
      await signInAnonymously(auth)
    } catch {
      error.value = 'Erro ao entrar como convidado.'
    }
  }

  async function logout() {
    if (!auth) return
    await signOut(auth)
  }

  return { user, isLoggedIn, isGuest, error, login, register, loginGuest, logout }
})
