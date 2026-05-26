<?php
session_start();
$mysqli = new mysqli('www1.cic.pt','usr47','BtAnD2y7','usr47');
if ($mysqli->connect_error) {
    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
}
?>
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/header.css">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/inicial.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
	<style type="text/css">
#s1{
		<?php 
		echo'background: url(Wallpapers/';
		echo rand(2,4);
		echo '.png) no-repeat center center fixed;';
		?>
	-webkit-background-size: cover;
	-moz-background-size: cover;
	-o-background-size: cover;
	background-size: cover;
}
.eb{
	background-color: #B300B2;
	margin:0;
	color:white;
	font-weight: bold;
}
	</style>
	<script type="text/javascript">

	$(document).ready(function(){
			$('#s1').height(($(window).height())-80);
			$('#s2').height(($(window).height())-80);
		$(window).resize(function(){
			$('#s1').height(($(window).height())-80);
			$('#s2').height(($(window).height())-80);
		});
		$("#beta").click(function(){
			$('html, body').animate({
				scrollTop: $('#s3').offset().top-70
			},1000);
		});
		$("#linkpoke").click(function(){
			window.location.href = "pokedex.php";
		});
		$("#linkjogo").click(function(){
			window.location.href = "register.php";
		});
	});
	</script>
	<title></title>
	
</head>
<body>
	<main>
	<section id="s1">
	</section>
	<section id="s2">
		<h1>Bem-vindo/a ao <span>Pok&eacute;World</span>!</h1>
		<p>Apesar deste <i>&quot;Fan-made Game&quot;</i> ainda estar em <span id="beta"><b>Alpha</b></span>, num futuro pr&oacute;ximo ser&aacute; poss&iacute;vel jog&aacute-lo! Desce na p&aacutegina para mais informa&ccedil&otildees!</p>
	</section>
	<section id="s3">
		<br><br><br><br>
		<h1><span>Alpha</span>?<br>Porqu&ecirc; <span>Alpha</span>?</h1>
		<br>
		<p>Porque ainda existe muito a ser feito e s&atilde;o coisas que ainda nem sei se v&atilde;o ser feitas! Coisas como o mapa, <i>crys</i> e m&uacute;sica s&atildeo apenas uma suposi&ccedil&atildeo porque poder&atildeo n&atildeo entrar no jogo <i>"final"</i>. Mas como aprendi nos jogos:<br><br><i class="solid">&quotA strong man doesn't need to read the future&quot - <b>Solid Snake</b> (Metal Gear Solid)</i><br><br><br>Ent&atildeo o melhor mesmo &eacute aproveitar o presente e us&aacute-lo ao m&aacuteximo para tudo poder ser feito a tempo!</p>
		<br><br><br><br>
	</section>
	<section id="s4">
		<div class="nameup">Updates e <i>Desupdates</i> do Jogo!</div>
		<div class="objetives">
			<div>
				<p class="pl"><b>[+]</b> Anima&ccedil;&atilde;o mais <i>"smooth"</i> da personagem no mundo</p>
				<p class="pl"><b>[+]</b> Background aleat&oacute;rio na p&aacute;gina de combate</p>
				<p class="mi"><b>[-]</b> Lista de Pokémons e Modo convidado (Expocic)</p>
				<p class="mi"><b>[-]</b> Bugs na p&aacute;gina de Login</p>
				<p class="pl"><b>[+]</b> P&aacute;gina de Combate</p>
				<p class="mi"><b>[-]</b> Bugs no HUD de Pok&eacute;mons Selvagens</p>
				<p class="pl"><b>[+]</b> Cria&ccedil;&atilde;o da vers&atilde;o para a Expocic</p>
				<p class="pl"><b>[+]</b> HUD Slots de Pokémons apanhados</p>
				<p class="up"><b>[~]</b> Est&eacute;tica do HUD Pok&eacute;mons Selvagens</p>
				<p class="up"><b>[~]</b> Jogadores recebem $200 em vez de $1 no registo</p>
				<p class="up"><b>[~]</b> P&aacute;gina de Registo + Login</p>
				<p class="pl"><b>[+]</b> HUD Pok&eacute;mons Selvagens</p>
				<p class="up"><b>[~]</b> Controlador HUD para dispositivos m&oacute;veis</p>
				<p class="up"><b>[~]</b> HUD Treinador + Dinheiro + total de pok&eacute;mons</p>
				<p class="pl"><b>[+]</b> Pok&eacute;mons Selvagens</p>
				<p class="pl"><b>[+]</b> HUD Treinador</p>
				<p class="pl"><b>[+]</b> Barra de op&ccedil;&otilde;es do HUD</p>
				<p class="pl"><b>[+]</b> HUD</p>
				<p class="up"><b>[~]</b> Pok&eacute;dex</p>
				<p class="up"><b>[~]</b> Página de Registo + Login</p>
				<p class="pl"><b>[+]</b> Página de Registo</p>
				<p class="pl"><b>[+]</b> Base de dados do Utilizador</p>
				<p class="up"><b>[~]</b> Design do Mapa</p>
				<p class="pl"><b>[+]</b> Esta P&aacute;gina!</p>
				<p class="mi"><b>[-]</b> P&aacute;gina inicial</p>
				<p class="up"><b>[~]</b> Base de dados dos Pok&eacute;mons</p>
				<p class="pl"><b>[+]</b> Pok&eacute;dex</p>
				<p class="pl"><b>[+]</b> Movimento do Pok&eacute;mon no Mapa</p>
				<p class="up"><b>[~]</b> Sprites</p>
				<p class="pl"><b>[+]</b> Movimento da Personagem</p>
				<p class="up"><b>[~]</b> Sprites</p>
				<p class="pl"><b>[+]</b> Base de dados dos Pok&eacute;mons</p>
				<p class="mi"><b>[-]</b> Base de dados do Utilizador</p>
				<p class="up"><b>[~]</b> Sprites</p>
				<p class="pl"><b>[+]</b> Design do Mapa movimentado</p>
				<p class="pl"><b>[+]</b> Sprites</p>
				<p class="pl"><b>[+]</b> Mapa movimentado</p>
				<p class="eb"><b>[#]</b> V2.0</p>
				<p class="pl"><b>[+]</b> Base de dados do Utilizador</p>
				<p class="mi"><b>[-]</b> Script dos Pok&eacute;mons</p>
				<p class="mi"><b>[-]</b> Sprites</p>
				<p class="mi"><b>[-]</b> Movimento do Pok&eacute;mon no Mapa</p>
				<p class="mi"><b>[-]</b> Movimento da Personagem</p>
				<p class="pl"><b>[+]</b> P&aacute;gina inicial</p>
				<p class="mi"><b>[-]</b> Batalha de Pok&eacute;mons</p>
				<p class="mi"><b>[-]</b> Mapa est&aacute;tico</p>
				<p class="pl"><b>[+]</b> Batalha de Pok&eacute;mons</p>
				<p class="pl"><b>[+]</b> Script dos Pok&eacute;mons</p>
				<p class="pl"><b>[+]</b> Sprites</p>
				<p class="pl"><b>[+]</b> Movimento do Pok&eacute;mon no Mapa</p>
				<p class="pl"><b>[+]</b> Movimento da Personagem</p>
				<p class="pl"><b>[+]</b> Mapa est&aacute;tico</p>

			</div>
		</div>
	</section>
	<section id="s5">
		<p class="col-sm-4" id="linkjogo">Jogo</p>
		<p class="col-sm-4" id="linkpoke">Pok&eacute;dex</p>
		<p class="col-sm-4">By Fábio Nogueira</p>
		<div class="clearfix"></div>
	</section>
		<!--Header-->
		<div id="red">
		  <div></div>
		</div>
		<div id="pokelines">
		  <div>
		    <div></div>
		  </div>
		</div>
		<form>
		<div id="Login" onclick="location.href = 'register.php';">
			<p>Entrar</p>
		</div>
		</form>
		<div class="arrow"  id="pokebol">
		  <div>
		    <img src="Textures/Header/pokeballcenter.png">
		  </div>
		</div>
	</main>
</body>
</html>