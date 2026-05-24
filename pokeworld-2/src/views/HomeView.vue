<template>
  <AppHeader label="ENTRAR" @nav-click="router.push('/game')" />

  <main>
    <section id="s1" :style="{ backgroundImage: `url(/wallpapers/${bg}.png)` }"></section>

    <section id="s2">
      <h1>Bem-vindo/a ao <span>PokéWorld</span>!</h1>
      <p>
        Apesar deste <i>"Fan-made Game"</i> ainda estar em
        <span id="beta" @click="scrollTo('s3')"><b>Alpha</b></span>,
        num futuro próximo será possível jogá-lo! Desce na página para mais informações!
      </p>
    </section>

    <section id="s3">
      <br /><br /><br /><br />
      <h1><span>Alpha</span>?<br />Porquê <span>Alpha</span>?</h1>
      <br />
      <p>
        Porque ainda existe muito a ser feito e são coisas que ainda nem sei se vão ser feitas!
        Coisas como o mapa, <i>crys</i> e música são apenas uma suposição porque poderão não
        entrar no jogo <i>"final"</i>. Mas como aprendi nos jogos:<br /><br />
        <i class="solid">"A strong man doesn't need to read the future" - <b>Solid Snake</b> (Metal Gear Solid)</i>
        <br /><br /><br />
        Então o melhor mesmo é aproveitar o presente e usá-lo ao máximo para tudo poder ser feito a tempo!
      </p>
      <br /><br /><br /><br />
    </section>

    <section id="s4">
      <div class="nameup">Updates e <i>Desupdates</i> do Jogo!</div>
      <div class="objetives">
        <div>
          <p class="pl"><b>[+]</b> Animação mais <i>"smooth"</i> da personagem no mundo</p>
          <p class="pl"><b>[+]</b> Background aleatório na página de combate</p>
          <p class="mi"><b>[-]</b> Lista de Pokémons e Modo convidado (Expocic)</p>
          <p class="mi"><b>[-]</b> Bugs na página de Login</p>
          <p class="pl"><b>[+]</b> Página de Combate</p>
          <p class="mi"><b>[-]</b> Bugs no HUD de Pokémons Selvagens</p>
          <p class="pl"><b>[+]</b> Criação da versão para a Expocic</p>
          <p class="pl"><b>[+]</b> HUD Slots de Pokémons apanhados</p>
          <p class="up"><b>[~]</b> Estética do HUD Pokémons Selvagens</p>
          <p class="up"><b>[~]</b> Jogadores recebem $200 em vez de $1 no registo</p>
          <p class="up"><b>[~]</b> Página de Registo + Login</p>
          <p class="pl"><b>[+]</b> HUD Pokémons Selvagens</p>
          <p class="up"><b>[~]</b> Controlador HUD para dispositivos móveis</p>
          <p class="up"><b>[~]</b> HUD Treinador + Dinheiro + total de pokémons</p>
          <p class="pl"><b>[+]</b> Pokémons Selvagens</p>
          <p class="pl"><b>[+]</b> HUD Treinador</p>
          <p class="pl"><b>[+]</b> Barra de opções do HUD</p>
          <p class="pl"><b>[+]</b> HUD</p>
          <p class="up"><b>[~]</b> Pokédex</p>
          <p class="up"><b>[~]</b> Página de Registo + Login</p>
          <p class="pl"><b>[+]</b> Página de Registo</p>
          <p class="pl"><b>[+]</b> Base de dados do Utilizador</p>
          <p class="up"><b>[~]</b> Design do Mapa</p>
          <p class="pl"><b>[+]</b> Esta Página!</p>
          <p class="mi"><b>[-]</b> Página inicial</p>
          <p class="up"><b>[~]</b> Base de dados dos Pokémons</p>
          <p class="pl"><b>[+]</b> Pokédex</p>
          <p class="pl"><b>[+]</b> Movimento do Pokémon no Mapa</p>
          <p class="up"><b>[~]</b> Sprites</p>
          <p class="pl"><b>[+]</b> Movimento da Personagem</p>
          <p class="eb"><b>[#]</b> V2.0</p>
          <p class="pl"><b>[+]</b> Base de dados dos Pokémons</p>
          <p class="pl"><b>[+]</b> Mapa estático</p>
        </div>
      </div>
    </section>

    <section id="s5">
      <p @click="router.push('/game')">Jogo</p>
      <p @click="router.push('/pokedex')">Pokédex</p>
      <p>By Fábio Nogueira</p>
    </section>

  </main>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'

const router = useRouter()
const bg = ref(Math.floor(Math.random() * 3) + 2)

function scrollTo(id: string) {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}
</script>

<style scoped>
main {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
}

#s1 {
  filter: blur(6px);
  -webkit-filter: blur(6px);
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
  background-attachment: fixed;
  margin: 80px 0 0 0;
  height: calc(100vh - 80px);
}

#s2 {
  position: absolute;
  top: 80px;
  left: 0;
  right: 0;
  height: calc(100vh - 80px);
  overflow: hidden;
}
#s2 > h1 {
  font-size: 60px;
  color: white;
  text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
  margin-left: 3%;
  margin-top: 100px;
}
#s2 > h1 > span { color: red; }
#s2 > p {
  font-size: 30px;
  color: white;
  text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
  margin-left: 3%;
}
#s2 > p > span { color: #b300b2; cursor: pointer; }
#s2 > p > span:hover { color: lightblue; }

#s3 {
  background-color: #ff1c1c;
  margin: 0;
  text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
  color: white;
  border-top: 10px solid black;
  border-bottom: 10px solid black;
}
#s3 > h1 { margin: 0 0 0 3%; font-size: 40px; }
#s3 > h1 > span { color: #b300b2; }
#s3 > p { margin-left: 3%; font-size: 1.1rem; }
.solid { font-style: italic; }

#s4 { padding: 20px 0 40px; }
.nameup {
  text-align: center;
  border-bottom: 1px solid black;
  margin: 40px auto 2px auto;
  display: block;
  width: 440px;
  font-size: 20px;
  font-weight: bold;
}
.objetives {
  margin: 0 auto 40px auto;
  border: 1px solid black;
  overflow: auto;
  height: 300px;
  display: block;
  width: 440px;
}
.eb { background-color: #B300B2; margin: 0; color: white; font-weight: bold; }
.pl { background-color: #8cff66; margin: 0; }
.mi { background-color: #ff4d4d; margin: 0; }
.up { background-color: #66b2ff; margin: 0; }

#s5 {
  text-align: center;
  background-color: black;
  color: white;
  text-transform: uppercase;
  padding: 20px 0;
}
#s5 > p { margin: 0; font-weight: bold; cursor: pointer; display: inline-block; width: 33%; }
#s5 > p:hover { color: grey; }

@media screen and (max-width: 960px) {
  #s2 > h1 { text-align: center; margin-top: 50px; }
  #s2 > p { text-align: center; }
  .nameup, .objetives { width: 90%; }
}
@media screen and (min-width: 960px) {
  #s2 { width: 960px; }
}
@media screen and (max-width: 550px) {
  #s2 > h1 { font-size: 50px; }
}
</style>
