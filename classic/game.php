<?php 
session_start(); 
if ($_SESSION["auth"]==0) header('Location: index.php');
$mysqli = new mysqli('localhost','root','','pokemon');
if ($mysqli->connect_error) {
    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Overworld</title>    
	<style type="text/css">
	/**,img{
		border: 1px solid lightgreen;
	}*/
	#PlayerImg{
		width:55px;
	}
	#PetImg{
		image-rendering: pixelated;
		width:48px;
		height:48px;
	}
	.loveit{
		z-index:-1;
	}
	body{
		background-color: black;
	}
	#tablet{
		position:absolute; 
		right:0;
		top:90px;
		overflow:auto;
	}
	#tablet>tr>td{
		font-size:2px;
		width:8px;
	}
	#navegator{
		height:50px;
		background-color:transparent;
		margin:auto;
		margin-top:82px;
		width:200px;
		position: relative;
		z-index: 3;
	}
	#navegator>img{
		width:50px;
	}
	#GamePad{
		position: absolute;
		z-index: 3;
		bottom: 10px;
		left:10px;
		width:150px;
		height:150px;
		background:url('Textures/Nav/Pad/0.png');
		-webkit-background-size: cover;
		-moz-background-size: cover;
		-o-background-size: cover;
		background-size: cover;
		display: none;
	}
	#TrackerBall{
		position: absolute;
		bottom: 62px;
		left:36px;
		z-index: 7;
		border-radius: 100%;
		opacity: 0.5;
		display: none;
	}
	.ImageWild{
		width:125px;

	}
	#TrackerBush{
		position: absolute;
		bottom: 0;
		left: 0;
		width:200px;
		height:250px;
		z-index: 6;
		border-right:3px solid black; 
		border-top:3px solid black; 
		background-color: #333;
		border-top-right-radius:10px; 
		visibility: hidden;
		color: white;
		text-align: center;
	}
	#TrackerBush>p{padding-top:10px;}
	.trackerdiv{
		width:123px;
		border: 4px solid black;
		display: inline-block;
		border-radius: 100%;
	}
	#trackerimg{
		border-radius: 100%;
	}
	#TrackerBush>div>img{
		width:115px;
	}
	#PlayerTracker{
		z-index:2;
		width: 120px;
		height: 120px;
		background-image: url(Textures/HUD/PlayerBack.jpg);
		position: absolute;
		top:80px;
		right:0;
		border-bottom:3px solid black;
		border-left:3px solid black;
		-webkit-background-size: cover;
		-moz-background-size: cover;
		-o-background-size: cover;
		background-size: cover;
	}
	#PlayerTracker>img{
		width: 117px;
		height:117px;
	}
	#PlayerStats{
		width:120px;
		background-color:#5F2890;
		color: white;
		text-align: right;
		position: absolute;
		top:200px;
		right:0;
		border-bottom:3px solid black;
		border-left:3px solid black;
		border-bottom-left-radius: 10px; 
	}
	#PlayerStats>p{
		padding: 0;
		margin:0;
	}
	.slots{
		/*border:2px solid white;*/
		width:500px;
		height:230px;
		position: absolute;
		bottom: 0;
		right: 0;
		text-align: right;
		z-index: 2;
	}
	.slots>p{
		width:70%;
		font-weight: bold;
		display: inline-block;
		margin: 9px 10px 9px 0 ;

	}
	.slots>div{
		width:40px;
		float:right;
		display: block;
	}
	.slots>div>img{
		display: inline-block;
		background-color:white;
		border-radius: 100%;
		border: 2px solid black;
	}
	@media screen and (max-width: 980px){
		#GamePad{display: block;}
		#navegator{margin-right:0px;}
		#TrackerBush{
			right:0;left:auto;
			border-left:3px solid black;
			border-right:0; 
			border-top-right-radius:0px;
			border-top-left-radius:10px;
		}
		#PlayerTracker,#PlayerStats{visibility: hidden;}
	}
	.list{
		position: fixed;
		top: 80px;
		left: 0px;
		bottom: 0;
		background-color:  #262626;
		min-width: 250px;
		/*font-family: "Pokemon", sans-serif;*/
		opacity: 0.5;
		z-index:4;
		padding-top: 20px;
	}
	.mini{
		width: 32px;
		height: 32px;
		border-bottom: 1px solid gray;
		width: 80%;
		margin:auto;
		border-collapse: collapse;
		color:white;
		padding-bottom: 3px;
		cursor: pointer;
		z-index:5;
	}
	.mini>img{
		display: inline-block;
	}
	.mini>p{
		display: inline-block;
	}
	.list:hover{opacity:1;overflow: auto;}
	</style>
	<link rel="stylesheet" type="text/css" href="css/header.css">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="scripts/move.js"></script>
	<script src="scripts/GamePad.js"></script>
	<script src="scripts/pokedex.js"></script>
	<script type="text/javascript">
	//Shiny rate= 1/8169
	//change
	/*$_SESSION["username"]
	$_SESSION["money"];
    $_SESSION["lvl"];
    $_SESSION["player"];
    $_SESSION["Slot1"];
    $_SESSION["Slot2"];
    $_SESSION["Slot3"];
    $_SESSION["Slot4"];
    $_SESSION["Slot5"];
    $_SESSION["Slot6"];
    $_SESSION["Slot1Lvl"];
    $_SESSION["Slot2Lvl"];
    $_SESSION["Slot3Lvl"];
    $_SESSION["Slot4Lvl"];
    $_SESSION["Slot5Lvl"];
    $_SESSION["Slot6Lvl"];*/
    var LvlMax=<?php echo $_SESSION["lvl"];?>;
	var MapWidth=24;
	var MapHeight=40;
	//
	var array=[];
	var n=1;
	var PlayerPosition=0;
	var Middle1=0;
	var Middle2=0;
	var PetType='Normal';
	var PetPosition1=1;
	var PetPosition2=2;
	var position=0;
	PetNumber=<?php echo $_SESSION["Slot1"];?>;
	//change
	PlayerPosition=468;
	//
	var yes=0;
	var PlayerTexture=<?php echo '"'.$_SESSION["player"].'"'; ?>;
	function draw(){
		if($(window).width() < 980) {
	         $("#Padpng").attr("src", "Textures/Nav/Pad/1.png");
	     } else {
	         $("#img").attr("src", "small.png");
	     }
		document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/1.png";

		//array do Overworld
		for (var i=1;i<=MapHeight;i++){
			for (var j=1;j<=MapWidth;j++){
						//Blocks
				if((j==22 || j==23) && i==37){x=9}
					else if((i>=MapHeight-1)||(j>=MapWidth-1)||(i<=2)||(j<=2)) {x=4}
					else if ((j==9 || j==10) && (i>4 && i<13)) {x=4}
					else if ((j==3 || j==4 || (j>10 && j<17)) && (i==16||i==17)){x=4}
					else if ((j>0 && j<13) && (i==26 || i==27)){x=4}
					else if((i==37 || i==38) && (j<13 || j>14)){x=4}
					else if(j==10 && i==32){x=4}
						//Fences
					else if((j>2 && j<9) && (i==6)){x=6}
					else if((j>10 && j<17) && (i==6)){x=6}
					else if((j>2 && j<9) && (i==11)){x=6}
					else if((j>4 && j<11) && (i==16)){x=6}
					else if((j>2 && j<5) && (i==21)){x=6}
					else if((j>5 && j<10) && (i==21)){x=6}
					else if((j>11 && j<24) && (i==21)){x=6}
					else if((j>18 && j<24) && (i==27)){x=6}
					else if((j>2 && j<7) && (i==32)){x=6}
					else if((j>10 && j<24) && (i==32)){x=6}
						//Bushs
					else if((j>10 && j<24) && (i>6 && i<12)){x=1}
					else if((j>16 && j<24) && (i>13 && i<19)){x=1}
					else if((j>12 && j<19) && (i>24 && i<30)){x=1}
					else if((j>4 && j<12) && (i>32 && i<35)){x=1}
					else if((j>17 && j<23) && (i>32 && i<35)){x=1}
					else if((j>15 && j<21) && (i>34 && i<37)){x=1}
					else if((j>2 && j<10) && (i>34 && i<37)){x=1}
					else if((j>12 && j<15) && (i>35 && i<38)){x=1}


					else x=0;
				document.getElementById("p"+n).innerHTML=x;
				<?php if($_SESSION["username"]!="") echo "document.getElementById('tablet').style.display='none';"; ?>
				if(x==4){document.getElementById("p"+n).style.backgroundColor="#00cc00"}
				if(x==6){document.getElementById("p"+n).style.backgroundColor="#662200"}
				if(x==1){document.getElementById("p"+n).style.backgroundColor="green"}
				if(x==9){document.getElementById("p"+n).style.backgroundColor="#0099ff"}
				array[n]=x;

				if(n==PlayerPosition){
					document.getElementById("p"+n).style.backgroundColor='red';
					
				}
				n++;

			}
		}
		resize();
		document.getElementById("SSlot1").innerHTML=pokedex(<?php echo $_SESSION["Slot1"]; ?>)+" | LVL: "+<?php echo $_SESSION["Slot1Lvl"]; ?>;
		document.getElementById("SSlot2").innerHTML=pokedex(<?php echo $_SESSION["Slot2"]; ?>)+" | LVL: "+<?php echo $_SESSION["Slot2Lvl"]; ?>;
		document.getElementById("SSlot3").innerHTML=pokedex(<?php echo $_SESSION["Slot3"]; ?>)+" | LVL: "+<?php echo $_SESSION["Slot3Lvl"]; ?>;
		document.getElementById("SSlot4").innerHTML=pokedex(<?php echo $_SESSION["Slot4"]; ?>)+" | LVL: "+<?php echo $_SESSION["Slot4Lvl"]; ?>;
		document.getElementById("SSlot5").innerHTML=pokedex(<?php echo $_SESSION["Slot5"]; ?>)+" | LVL: "+<?php echo $_SESSION["Slot5Lvl"]; ?>;
		document.getElementById("SSlot6").innerHTML=pokedex(<?php echo $_SESSION["Slot6"]; ?>)+" | LVL: "+<?php echo $_SESSION["Slot6Lvl"]; ?>;
		<?php echo "document.getElementById('ISlot1').src='Textures/Mini/Gif/";
				$rande=$_SESSION["Slot1"];
				if($rande<10){
					$rande="00".$rande;
				}else if($rande<100){
					$rande="0".$rande;
				}else $rande=$rande;
				echo $rande.".gif';";
				echo "document.getElementById('ISlot2').src='Textures/Mini/Gif/";
				$rande=$_SESSION["Slot2"];
				if($rande<10){
					$rande="00".$rande;
				}else if($rande<100){
					$rande="0".$rande;
				}else $rande=$rande;
				echo $rande.".gif';";
				echo "document.getElementById('ISlot3').src='Textures/Mini/Gif/";
				$rande=$_SESSION["Slot3"];
				if($rande<10){
					$rande="00".$rande;
				}else if($rande<100){
					$rande="0".$rande;
				}else $rande=$rande;
				echo $rande.".gif';";
				echo "document.getElementById('ISlot4').src='Textures/Mini/Gif/";
				$rande=$_SESSION["Slot4"];
				if($rande<10){
					$rande="00".$rande;
				}else if($rande<100){
					$rande="0".$rande;
				}else $rande=$rande;
				echo $rande.".gif';";
				echo "document.getElementById('ISlot5').src='Textures/Mini/Gif/";
				$rande=$_SESSION["Slot5"];
				if($rande<10){
					$rande="00".$rande;
				}else if($rande<100){
					$rande="0".$rande;
				}else $rande=$rande;
				echo $rande.".gif';";
				echo "document.getElementById('ISlot6').src='Textures/Mini/Gif/";
				$rande=$_SESSION["Slot6"];
				if($rande<10){
					$rande="00".$rande;
				}else if($rande<100){
					$rande="0".$rande;
				}else $rande=$rande;
				echo $rande.".gif';";
				?>
		//Posicionar Overworld
		var ji=$(window).height();
		var je=$(window).width();
		//change
		$(".loveit").css('top',-($(".loveit").height()-ji)/2);
		$(".map2").css('top',-($(".loveit").height()-ji)/2)
		if(($(".loveit").width())>($(window).width())){
			$(".loveit").css('left',-($(".loveit").width()-je)/2)
			$(".map2").css('left',-($(".loveit").width()-je)/2)
		}
		//alert($(".loveit").width()+" "+$(window).width());
		$(".player").css('top',ji/2-78);
		$(".player").css('left',je/2-50.5);
		$(".pet").css('top',ji/2-78-10);
		$(".pet").css('left',je/2-50.5+3.5);
		Middle1=ji/2-78-10;
		Middle2=je/2-50.5+3.5;
		document.getElementById("PetImg").src="Textures/Overworld/"+PetType+"/"+PetPosition1+"/"+PetNumber+".png";

	}
	function Fight(){
		
		location.href = 'battle.php';
	}
	function FightPre(){document.getElementById("TrackerBall").style.display="block";}
	function FightPreOff(){document.getElementById("TrackerBall").style.display="none";}
    var rande=0;
	function battle(){
		if(array[PlayerPosition]==1){
			rande=Math.floor((Math.random() * 3) + 1);
			if(rande==1){
				rande=Math.floor((Math.random() * 151) + 1);
					var rande2=Math.floor((Math.random() * (LvlMax-2)) + 4);
				document.getElementById("NumberWild").value=rande;
				document.getElementById("NivelWild").value=rande2;
				if(rande<10){rande="00"+rande}
					else if(rande<100){rande="0"+rande}
						else rande=rande;
				document.getElementById("trackerlvl").innerHTML="N&iacutevel "+rande2;
				document.getElementById("trackername").innerHTML=pokedex(rande);
				document.getElementById("TrackerBush").style.visibility="visible";
				document.getElementById("trackerimg").src="Textures/Art/"+rande+".png";
				document.getElementById("TrackerBall").style.visibility="visible";
			}else {
				document.getElementById("TrackerBush").style.visibility="hidden";
				document.getElementById("TrackerBall").style.visibility="hidden";
				}
		}else {
			document.getElementById("TrackerBush").style.visibility="hidden";
			document.getElementById("TrackerBall").style.visibility="hidden";
		}
	}
	$(window).resize(resize);
	function resize(){
	if($(window).width() <980) {
         $("#Padpng").attr("src", "Textures/Nav/Pad/1.png");
         $("#Pokepng").attr("src", "Textures/Nav/Poke/1.png");
     } else {
         $("#Padpng").attr("src", "Textures/Nav/Pad/0.png");
         $("#Pokepng").attr("src", "Textures/Nav/Poke/0.png");
     }
     padd=1;
     padd1=1;
 }
	//Movimentação do Pokemon no Overworld
	setInterval(function(){ 
		if((PetPosition1%2)==!0){
			document.getElementById("PetImg").src="Textures/Overworld/"+PetType+"/"+PetPosition1+"/"+PetNumber+".png";
			PetPosition1++;
		}else{
			document.getElementById("PetImg").src="Textures/Overworld/"+PetType+"/"+PetPosition1+"/"+PetNumber+".png";
			PetPosition1--;
		}		
	}, 500);
	var padd=0;
	//Nav
	function pad(){
		if(padd==0){padd++;document.getElementById("GamePad").style.display="block"}
		else {padd--;document.getElementById("GamePad").style.display="none"}
		document.getElementById("Padpng").src="Textures/Nav/Pad/"+padd+".png";
	}
	padd1=0;
	function pokenav(){
		if(padd1==0){
			padd1++;
			document.getElementById("PlayerStats").style.visibility="hidden";
			document.getElementById("PlayerTracker").style.visibility="hidden";
		}
		else {
			padd1--;
			document.getElementById("PlayerStats").style.visibility="visible";
			document.getElementById("PlayerTracker").style.visibility="visible";
		}
		document.getElementById("Pokepng").src="Textures/Nav/Poke/"+padd1+".png";
	
	}
	addEventListener("keydown", KeyDown);
	$(document).ready(draw);
	</script>
</head>
<body>
	<div style="background-color:rgb(74, 182, 138);position:fixed;top:0;right:0;bottom:0;left:0;z-index:-2;">
		<div style="margin:auto;width:1155px">
			<div style="position:absolute">
				<div style="position:absolute;top:0;left:0;" class="loveit"><img style="height:1920px;width:1155px;image-rendering:pixelated"src="Textures/Map/Mapa.png"></div>
				<div style="visibility:visible;position:absolute;width:1155px;top:0;left:0;font-size:12px;text-align:center;"></div>
			</div>
		</div>
	</div>
	<div class="pet1" style="position:fixed;top:0;right:0;bottom:0;left:0;z-index:0">
		<div style="margin:auto;width:120px;">
			<div style="position:absolute;" class="pet" ><img id="PetImg" src=""></div>
		</div>
	</div>
	<div style="position:fixed;top:0;right:0;bottom:0;left:0;z-index:1">
		<div style="margin:auto;width:100px;">
			<div style="position:absolute;" class="player"><img id="PlayerImg" src=""></div>
		</div>
	</div>
	<div style="position:fixed;top:0;right:0;bottom:0;left:0;z-index:2;">
		<div style="margin:auto;width:1155px">
			<div style="position:absolute">
				<div class="map2" style="position:absolute;top:0;left:0;"><img style="height:1920px;width:1155px;image-rendering:pixelated" src="Textures/Map/Mapa2.png"></div>
				<div style="visibility:visible;position:absolute;width:1155px;top:0;left:0;font-size:12px;text-align:center;"></div>
			</div>
		</div>
	</div>
	<div id="TrackerBush">
		<h3 id="trackername"><b>Pikachu</b></h3>
		<div class="trackerdiv">
			<img id="trackerimg" src="Textures/Art/001.png" onmouseover="FightPre()">
		</div>
		<p id="trackerlvl">Nível 3</p>
	</div>
	<form enctype="multipart/form-data" action="battle.php" method="post">
	<div id="TrackerBall" onmouseout="FightPreOff()">
		<input type="image" name="submit" class="ImageWild" src="Textures/HUD/Pokeball.png">
		<input type="hidden" name="number" value="" id="NumberWild">
		<input type="hidden" name="nivel" value="" id="NivelWild">
	</div>
	</form>
	<div id="PlayerTracker">
		<img src="Player/<?php echo $_SESSION["player"]; ?>/image.png">
	</div>
	<div id="PlayerStats">
		<p style="display:inline-block;width:70%;"><b><?php echo $_SESSION["Quant"]?></b></p><img src="Textures/Nav/Poke/0.png" style="display:inline-block;width:18px;height:18px;">
		<p><b>$<?php echo $_SESSION["money"]; ?></b></p>
	</div>
	<div class="slots">
		<p id="SSlot1">Bulbasaur | LVL: 7 </p>
		<div>
			<img id="ISlot1" src="">
		</div>
		<p id="SSlot2">Bulbasaur | LVL: 7 </p>
		<div>
			<img id="ISlot2">
		</div>
		<p id="SSlot3">Bulbasaur | LVL: 7 </p>
		<div>
			<img id="ISlot3">
		</div>
		<p id="SSlot4">Bulbasaur | LVL: 7 </p>
		<div>
			<img id="ISlot4">
		</div>
		<p id="SSlot5">Bulbasaur | LVL: 7 </p>
		<div>
			<img id="ISlot5">
		</div>
		<p id="SSlot6">Bulbasaur | LVL: 7 </p>
		<div>
			<img id="ISlot6">
		</div>
	</div>
	<div id="navegator">
		<img src="Textures/Nav/Pokedex/0.png" onclick="location.href = 'pokedex.php';">
		<img src="Textures/Nav/Pad/0.png" id="Padpng" onclick="pad()">
		<img src="Textures/Nav/Poke/0.png" id="Pokepng" onclick="pokenav()">
	</div>
	<?php
		echo '<table cellspacing="0" cellpadding="0" id="tablet">';
		$n=0;
		for ($i=1; $i <= 40 ; $i++) { 
			echo "<tr>";
			for ($j=1; $j <= 24; $j++) { 
				$n++;
				echo"<td id='p".$n."'>0</td>";
			}
			echo "</tr>";
		}
	echo '</table>';
	?>
	<div id="GamePad">
		<div style="background-color:transparent;height:64px;margin:0 52px;"  onmousedown="pads(38)"></div>
		<div style="background-color:transparent;margin-top:-12px;height:46px;width:64px;" onmousedown="pads(37)"></div>
		<div style="background-color:transparent;margin:-46px 0 0 86px;height:46px;width:64px;" onmousedown="pads(39)"></div>
		<div style="background-color:transparent;height:64px;margin:-12px 52px;" onmousedown="pads(40)"></div>
	</div>
	<!--Header-->
		<div id="red"style="z-index:3">
		  <div></div>
		</div>
		<div id="pokelines"style="z-index:4">
		  <div>
		    <div></div>
		  </div>
		</div>
		<div id="Login" style="z-index:5"onclick="location.href = 'redirect.php';">
			<p>Logout</p>
		</div>
		<div class="arrow"  id="pokebol"style="z-index:5">
		  <div>
		    <img src="Textures/Header/pokeballcenter.png">
		  </div>
		</div>
</div>
</body>
</html>