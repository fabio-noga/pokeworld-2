<?php 
session_start();
if (isset($_SESSION["auth"])) header('Location: game.php');
 ?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
	<link rel="stylesheet" type="text/css" href="css/header.css">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	
<style type="text/css">
	main{
		margin: 0 10%;
		min-width: 500px;
		background-color: #e6e6e6;
		box-shadow: 0px 0px 10px #888888;
		margin-top:150px;
	}
	.Log{
		padding-left:30px;
		margin-top: 10px;
	}
	.Reg{margin: 10px 0;}
	.form-control{
		width:250px;
	}
	.player2, .player{
		display: block;
		background-color: #FFF;
		border-radius: 4px;
	}
	.player2{background-color: transparent;border: none;max-height: 133px;}
	.player{
		box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.075) inset;
		border: 1px solid #CCC;
		line-height: 1.42857;
		overflow-y: auto;
		max-height:94px;
		}
	.player>div{
		background-color: white;
		width:68px;
		border:1px solid #CCC;
		border-radius: 4px;
		margin: 3px 10px;
		display: inline-block;
	}
	.player>div:hover{border:1px solid #66afe9;}
	
	.player>div>img{
		margin:0 10px 20px;
	}
	.player2>div>img{width:100%;max-width: 133px;}
	.player2>div{
		display: inline-block;
	}
	.player2>div>img:hover{border:1px solid #66afe9;}
	.divider{
		border-left:1px solid #000;
		height:640px;
		width:0px;
		margin:20px 0;
	}
	.hr{display:none;}
	.info{
		text-align: center;
		color: grey;

	}
	@media screen and (max-width: 830px){
		.divider{display: none;}
		main{padding-top: 10px;}
		.Reg{padding-left:30px;}
		.hr{display: block;}
	}
	.guest{
		width:100%;
		height:100px;
		background-color:white;
	}
</style>
<script type="text/javascript">
	function player(e){
		document.getElementById('Chare1').value = e;
		if (e==1){document.getElementById('Chare').innerHTML='Red';} 
		else if (e==2){document.getElementById('Chare').innerHTML='Hiro';}
		else if (e==3){document.getElementById('Chare').innerHTML="Old Man";}
		else if (e==4){document.getElementById('Chare').innerHTML='Duncan';}
		else if (e==5){document.getElementById('Chare').innerHTML='Ness';}
	}
	function poke(e){
		document.getElementById('Chare2').value = e;
		if(e==1) {document.getElementById('Poke').innerHTML='Bulbassaur';}
		else if(e==4) {document.getElementById('Poke').innerHTML='Charmander';}
		else if(e==7) {document.getElementById('Poke').innerHTML='Squirtle';}
	}
</script>
<body>
	<main>
	
	<!--<div class="guest">

	</div>-->
		<div class="Log col-sm-5">
			<form enctype="multipart/form-data" action="redirect.php" method="post">
				<h1>Login</h1><br>
				<input type="text" name="username1" placeholder="Nome" class="form-control"><br>
				<input type="password" name="password1" placeholder="Password" class="form-control"><br>
				<button type="submit" name="log">Login</button><br><br>
				<form enctype="multipart/form-data" action="redirect.php" method="post">
		</form>
			</form>
		</div>


		<div class="divider col-sm-1"></div>
		<hr class="hr">


		<div class="Reg col-sm-6">
		<form enctype="multipart/form-data" action="redirect.php" method="post">
			<h1>Registar</h1><br>
			<input type="text" name="username" placeholder="Nome" class="form-control"><br>
			<input type="password" name="password" placeholder="Password" class="form-control"><br>
			<input type="password" name="password2" placeholder="Confirmar Password" class="form-control"><br>			
			<h3 id="Chare">Escolhe uma Personagem!</h3>
			<input value="" id="Chare1" type="hidden" name="player">
			<div class="player">
				<div onclick="player(1)"><img src="Player/FireRed/1.png"></div>
				<div onclick="player(2)"><img src="Player/Hiro/1.png"></div>
				<div onclick="player(3)"><img src="Player/Old/1.png"></div>
				<div onclick="player(4)"><img src="Player/Duncan/1.png"></div>
				<div onclick="player(5)"><img src="Player/Ness/1.png"></div>
			</div><br>
			<h3 id="Poke">Escolhe o teu Pok&eacutemon!</h3>
			<input id="Chare2" value="" type="hidden" name="poke">
			<div class="player2">
				<div onclick="poke(1)" class="col-sm-4"><img src="Textures/Art/001.png"></div>
				<div onclick="poke(4)" class="col-sm-4"><img src="Textures/Art/004.png"></div>
				<div onclick="poke(7)" class="col-sm-4"><img src="Textures/Art/007.png"></div>
				<div class="clearfix"></div>
			</div><br>
			<button type="submit" name="reg">Registar</button>
			<input value="2" type="hidden" name="great">
			</form>
		</div>
		<div class="clearfix"></div>
		<p class="info">(Se estiveres esta p&aacutegina depois de registares ou fazeres login, &eacute prov&aacutevel que tenhas posto as informa&ccedil&otildes erradas!)</p>
		

	</main>


	<div id="red">
		  <div></div>
		</div>
		<div id="pokelines">
		  <div>
		    <div></div>
		  </div>
		</div>		
		<div id="Login" onclick="location.href = 'index.php';">
			<p>In&iacutecio</p>
		</div>
		<div class="arrow"  id="pokebol">
		  <div>
		    <img src="Textures/Header/pokeballcenter.png">
		  </div>
		</div>
</body>
</html>