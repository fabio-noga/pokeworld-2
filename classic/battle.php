<?php
session_start();
$mysqli = new mysqli('localhost','root','','pokemon');
if ($mysqli->connect_error) {
    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
}
if (!isset($_POST["number"])) header('Location: game.php');
?>
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/header.css">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/inicial.css">
	<script src="scripts/pokedex.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
	<style type="text/css">
	main{
		height:500px;
		width:700px;
		margin:auto;
		margin-top: 120px;
		box-shadow: 0px 0px 100px #888888;
	}
	.monitor{
		display: inline-block;
		height:349px;
		width: 500px;

		background-image: url(Textures/Battle/Arena/background<?php echo rand(1,4);?>.png);
		-webkit-background-size: cover;
		-moz-background-size: cover;
		-o-background-size: cover;
		background-size: cover;
	}
	.pokeball{
		display: inline-block;
		height:200px;
		width: 200px;
		top:0px;
		position: absolute;
		background-color:#CEC;
		border:1px solid black;
		cursor: pointer;
	}
	.pokeball>img{
		width:150px;
		margin-top: 25px;
		margin-left: 25px;
	}
	.pokeball:hover{
		box-shadow: inset 0px 0px 50px #888888;
	}
	.tackle{
		display: inline-block;
		height:151px;
		width: 500px;
		bottom:0px;
		left:0px;
		position: absolute;
		border-bottom:1px solid black;
		border-left:1px solid black;
		border-top:1px solid black;
	}
	.pokemons{
		display: inline-block;
		height:300px;
		width: 200px;
		bottom:0px;
		right:0px;
		position: absolute;
		text-align: right;
		font-weight: bold;
	}
	#rival{
		position: absolute;
		top:100px;
		right:290px;
	}
	#rival>img{
		width:150%;
	}
	#pokemon{
		position: absolute;
		top:190px;
		left:20px;
	}
	#pokemon>img{
		width:200%;
	}
	.pokemons>div{
		box-sizing: border-box;
    	-moz-box-sizing: border-box;
    	-webkit-box-sizing: border-box;
		height: 50px;
		width:200px;
		background-color: #CEC;
		border-bottom:1px solid black;
		border-left:1px solid black;
		border-right:1px 
		solid black;
		cursor: pointer;
	}
	#Slot1{
		color:white;
		background-color: grey;
	}
	#Slot1:hover,#Slot2:hover,#Slot3:hover,#Slot4:hover,#Slot5:hover,#Slot6:hover{
		background-color: #737373;
		color:white;
	}
	#Slot2,#Slot3,#Slot4,#Slot5,#Slot6{
		background-color: #a6a6a6;
	}
	.Power1, .Power2, .Power3, .Power4{
		position: absolute;
		width:200px;
		font-size: 20px;
		height:50px;
		border:3px solid black;
		border-radius: 30px;
		text-align: center;
		display: inline-block;
	} 
	.Power1:hover, .Power2:hover, .Power3:hover, .Power4:hover{
		box-shadow: inset 0px 0px 20px #888888;
		cursor: pointer;
	}
	.Power1, .Power2{bottom:80px;}
	.Power1, .Power3{left: 40px;}
	.Power2, .Power4{left:265px;}
	.Power3, .Power4{bottom:10px;}
	#Power1,#Power2,#Power3,#Power4{
		margin: 4px;
	}
	</style>
	<script type="text/javascript">
	var PP=[0,0,0,0];
	var PPP=[0,0,0,0];
	var Atk=["","","",""];
	tack=function(e){
		document.getElementById("Power"+e).innerHTML='PP: '+PP[e]+"/"+PPP[e];
	}
	teck=function(e){
		document.getElementById("Power"+e).innerHTML=Atk[e];
	}
	$(document).ready(function(){
		var poke=<?php echo $_SESSION["Slot1"];?>;
		var poke2=pokedex(<?php echo $_SESSION["Slot1"];?>);
		document.getElementById("Slot1Name").innerHTML=poke2+" | LVL "+<?php echo $_SESSION["Slot1Lvl"];?>;
		document.getElementById("Slot1HP").innerHTML=<?php
		$poke=$_SESSION["Slot1"];
		$a=0;
		$b=0;
		$results = $mysqli->query("SELECT HP FROM pokemon where Slot='1' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $a=$row["HP"];
          }
		$results = $mysqli->query("SELECT HP FROM stats where Number='".$poke."';");
          while($row = $results->fetch_assoc()) {
            $b=$row["HP"];
          }
          echo $a."+'/'+".$b;?>;
          var eu="Textures/Battle/Normal/Back/Gif/";
          if(poke<10)eu=eu+"00"+poke+".gif";
          else if(poke<100)eu=eu+"0"+poke+".gif";
          else eu=eu+poke+".gif";
          document.getElementById("PokemonImg").src=eu;
          poke=<?php echo $_POST["number"];?>;
          var eu="Textures/Battle/Normal/Front/Gif/";
          if(poke<10)eu=eu+"00"+poke+".gif";
          else if(poke<100)eu=eu+"0"+poke+".gif";
          else eu=eu+poke+".gif";
          document.getElementById("RivalImg").src=eu;

          var poke=<?php echo $_SESSION["Slot2"];?>;
		var poke2=pokedex(<?php echo $_SESSION["Slot2"];?>);
		document.getElementById("Slot2Name").innerHTML=poke2+" | LVL "+<?php echo $_SESSION["Slot2Lvl"];?>;
		document.getElementById("Slot2HP").innerHTML=<?php
		$poke=$_SESSION["Slot2"];
		$a=0;
		$b=0;
		$results = $mysqli->query("SELECT HP FROM pokemon where Slot='2' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $a=$row["HP"];
          }
		$results = $mysqli->query("SELECT HP FROM stats where Number='".$poke."';");
          while($row = $results->fetch_assoc()) {
            $b=$row["HP"];
          }
          echo $a."+'/'+".$b;
          ?>

          var poke=<?php echo $_SESSION["Slot3"];?>;
		var poke2=pokedex(<?php echo $_SESSION["Slot3"];?>);
		document.getElementById("Slot3Name").innerHTML=poke2+" | LVL "+<?php echo $_SESSION["Slot3Lvl"];?>;
		document.getElementById("Slot3HP").innerHTML=<?php
		$poke=$_SESSION["Slot3"];
		$ab=0;
		$bb=0;
		$results = $mysqli->query("SELECT HP FROM pokemon where Slot='3' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $ab=$row["HP"];
          }
		$results = $mysqli->query("SELECT HP FROM stats where Number='".$poke."';");
          while($row = $results->fetch_assoc()) {
            $bb=$row["HP"];
          }
          echo $ab."+'/'+".$bb;
          ?>

            var poke=<?php echo $_SESSION["Slot4"];?>;
		var poke2=pokedex(<?php echo $_SESSION["Slot4"];?>);
		document.getElementById("Slot4Name").innerHTML=poke2+" | LVL "+<?php echo $_SESSION["Slot4Lvl"];?>;
		document.getElementById("Slot4HP").innerHTML=<?php
		$poke=$_SESSION["Slot4"];
		$ab=0;
		$bb=0;
		$results = $mysqli->query("SELECT HP FROM pokemon where Slot='4' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $ab=$row["HP"];
          }
		$results = $mysqli->query("SELECT HP FROM stats where Number='".$poke."';");
          while($row = $results->fetch_assoc()) {
            $bb=$row["HP"];
          }
          echo $ab."+'/'+".$bb;
          ?>

            var poke=<?php echo $_SESSION["Slot5"];?>;
		var poke2=pokedex(<?php echo $_SESSION["Slot5"];?>);
		document.getElementById("Slot5Name").innerHTML=poke2+" | LVL "+<?php echo $_SESSION["Slot5Lvl"];?>;
		document.getElementById("Slot5HP").innerHTML=<?php
		$poke=$_SESSION["Slot5"];
		$ab=0;
		$bb=0;
		$results = $mysqli->query("SELECT HP FROM pokemon where Slot='5' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $ab=$row["HP"];
          }
		$results = $mysqli->query("SELECT HP FROM stats where Number='".$poke."';");
          while($row = $results->fetch_assoc()) {
            $bb=$row["HP"];
          }
          echo $ab."+'/'+".$bb;
          ?>

            var poke=<?php echo $_SESSION["Slot6"];?>;
		var poke2=pokedex(<?php echo $_SESSION["Slot6"];?>);
		document.getElementById("Slot6Name").innerHTML=poke2+" | LVL "+<?php echo $_SESSION["Slot6Lvl"];?>;
		document.getElementById("Slot6HP").innerHTML=<?php
		$poke=$_SESSION["Slot6"];
		$ab=0;
		$bb=0;
		$results = $mysqli->query("SELECT HP FROM pokemon where Slot='6' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $ab=$row["HP"];
          }
		$results = $mysqli->query("SELECT HP FROM stats where Number='".$poke."';");
          while($row = $results->fetch_assoc()) {
            $bb=$row["HP"];
          }
          echo $ab."+'/'+".$bb;
          ?>

          document.getElementById("Power1").innerHTML=<?php
          $results = $mysqli->query("SELECT At1,PP1 FROM pokemon where Slot='1' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $a=$row["At1"];
            $b=$row["PP1"];
          }
          $results = $mysqli->query("SELECT Name,Power,Acc,PP FROM ataques where Id=".$a.";");
          while($row = $results->fetch_assoc()) {
            echo "'".$row["Name"]."';PP[1]=".$b.";Atk[1]='".$row["Name"]."';PPP[1]=".$row["PP"].";";
          }
          ?>
          document.getElementById("Power2").innerHTML=<?php
          $results = $mysqli->query("SELECT At2,PP2 FROM pokemon where Slot='1' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $a=$row["At2"];
            $b=$row["PP2"];
          }
          $results = $mysqli->query("SELECT Name,Power,Acc,PP FROM ataques where Id=".$a.";");
          while($row = $results->fetch_assoc()) {
            echo "'".$row["Name"]."';PP[2]=".$b.";Atk[2]='".$row["Name"]."';PPP[2]=".$row["PP"].";";
          }
          ?>
          document.getElementById("Power3").innerHTML=<?php
          $results = $mysqli->query("SELECT At3,PP3 FROM pokemon where Slot='1' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $a=$row["At3"];
            $b=$row["PP3"];
          }
          $results = $mysqli->query("SELECT Name,Power,Acc,PP FROM ataques where Id=".$a.";");
          while($row = $results->fetch_assoc()) {
            echo "'".$row["Name"]."';PP[3]=".$b.";Atk[3]='".$row["Name"]."';PPP[3]=".$row["PP"].";";
          }
          ?>
          document.getElementById("Power4").innerHTML=<?php
          $results = $mysqli->query("SELECT At4,PP4 FROM pokemon where Slot='1' AND IdPlayer='".$_SESSION["IdPlayer"]."';");
          while($row = $results->fetch_assoc()) {
            $a=$row["At4"];
            $b=$row["PP4"];
          }
          $results = $mysqli->query("SELECT Name,Power,Acc,PP FROM ataques where Id=".$a.";");
          while($row = $results->fetch_assoc()) {
            echo "'".$row["Name"]."';PP[4]=".$b.";Atk[4]='".$row["Name"]."';PPP[4]=".$row["PP"].";";
          }
          ?>
	});
		
	</script>
	<title></title>
	
</head>
<body>
	<main>
		<div class="monitor">
		<div id="rival">
			<img id="RivalImg" src="Textures/Battle/Normal/Front/Gif/095.gif">
		</div>
		<div id="pokemon">
			<img id="PokemonImg" src="">
		</div>
		</div>
		<div class="pokeball">
			<img src="Textures/HUD/Pokeball.png">
		</div>
		<div class="tackle">
			<div class="Power1">
				<h2 id="Power1" onmouseover="tack(1)" onmouseout="teck(1)">Tackldvsdvacs</h2>
			</div>
			<div class="Power2">
				<h2 id="Power2"  onmouseover="tack(2)" onmouseout="teck(2)">Tackascsale</h2>
			</div>
			<div class="Power3">
				<h2 id="Power3" onmouseover="tack(3)" onmouseout="teck(3)">Tacasckle</h2>
			</div>
			<div class="Power4">
				<h2 id="Power4" onmouseover="tack(4)" onmouseout="teck(4)">Taascasckle</h2>
			</div>
		</div>
		<div class="pokemons">
			<div id="Slot1">
				<p id="Slot1Name"></p>
				<p id="Slot1HP"></p>
			</div>
			<div id="Slot2">
				<p id="Slot2Name"></p>
				<p id="Slot2HP"></p>
			</div>
			<div id="Slot3">
				<p id="Slot3Name"></p>
				<p id="Slot3HP"></p>
			</div>
			<div id="Slot4">
				<p id="Slot4Name"></p>
				<p id="Slot4HP"></p>
			</div>
			<div id="Slot5">
				<p id="Slot5Name"></p>
				<p id="Slot5HP"></p>
			</div>
			<div id="Slot6">
				<p id="Slot6Name"></p>
				<p id="Slot6HP"></p>
			</div>
		</div>
	</main>
	<div id="red">
		  <div></div>
		</div>
		<div id="pokelines">
		  <div>
		    <div></div>
		  </div>
		</div>
		<div id="Login" onclick="location.href = 'game.php';">
			<p>Sair</p>
		</div>
		<div class="arrow"  id="pokebol">
		  <div>
		    <img src="Textures/Header/pokeballcenter.png">
		  </div>
		</div>
</body>
</html>