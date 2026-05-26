<?php
session_start();
$mysqli = new mysqli('localhost','root','','pokemon');
if ($mysqli->connect_error) {
    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Pok&eacute;dex</title>
	<script src="scripts/evolve.js"></script>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		$('.arrow').click(function(){
			if($('.list').css('left')=="-250px"){	
				$('.list').animate({left:'0px'},'fast');
				$('.arrow').animate({left:'61px'},'fast');
			} else{
				$('.list').animate({left:'-250px'},'fast');
				$('.arrow').animate({left:'-64px'},'fast');
			}
		});
		var b=0;
		<?php
		for ($i=1; $i <= 151; $i++) { 
			# code...
			$results = $mysqli->query("SELECT * FROM stats where Number='".$i."';");
			while($row = $results->fetch_assoc()) {
				$hp=$row['HP'];
				$atk=$row['Attack'];
				$def=$row['Defense'];
				$satk=$row['SpAtk'];
				$sdef=$row['SpDef'];
				$spd=$row['Speed'];
				$nome=$row['Name'];
			}
			echo'$(".'.$i.'").click(function(){';
			echo'$(".hp").animate({width:"'.$hp.'"},"fast");';
			echo'$(".atk").animate({width:"'.$atk.'"},"fast");';
			echo'$(".def").animate({width:"'.$def.'"},"fast");';
			echo'$(".satk").animate({width:"'.$satk.'"},"fast");';
			echo'$(".sdef").animate({width:"'.$sdef.'"},"fast");';
			echo'$(".spd").animate({width:"'.$spd.'"},"fast");';
			echo'document.getElementById("nome").innerHTML="#'.$i.' '.$nome.'";';
			if($i<10) {
				echo'$(".seeimg").attr("src","Textures/Art/00'.$i.'.png");';
			} else if($i<100) {
				echo'$(".seeimg").attr("src","Textures/Art/0'.$i.'.png");';
			}else {
				echo'$(".seeimg").attr("src","Textures/Art/'.$i.'.png");';
			}
			echo'b=evolve(1,'.$i.',0);';
			$j=$i+1;
			$g="class='".$j."'";
			echo'if(b>0){
				document.getElementById("text").innerHTML="Evolui para<b>"+document.getElementById('.$j.').innerHTML+"</b>&nbsp; a nivel <b>"+b+"</b>";
			}else if(b=="Thunderstone"||b=="Leaf Stone"||b=="Water Stone"||b=="Moon Stone"||b=="Fire Stone"){
				document.getElementById("text").innerHTML="Evolui para<b>"+document.getElementById('.$j.').innerHTML+"</b>&nbsp; em contacto com uma <b>"+b+"</b>";
			}else if(b=="Trade"){
				document.getElementById("text").innerHTML="Evolui para<b>"+document.getElementById('.$j.').innerHTML+"</b>&nbsp; ao ser <b>trocado</b> com outro jogador";
			}else if(b=="Eevee"){
				document.getElementById("text").innerHTML="Evolui para <b>Vaporeon</b> com uma <b>Water Stone</b>, <b>Jolteon</b> com uma <b>Thunderstone</b> e para <b>Flareon</b> com uma <b>Fire Stone</b>";
			}else document.getElementById("text").innerHTML="Este pok&eacutemon n&atildeo tem evolu&ccedil&atildeo"';
			echo'});';
		}

		?>
	});
	</script>
	<link rel="stylesheet" type="text/css" href="css/header.css">
	<link rel="stylesheet" type="text/css" href="css/pokedex.css">
</head>
<body>
<div id="nome">#1 Bulbasaur</div>
<div class="stat">
	<div><div class="statname">HP</div><div class="hp ststs"></div></div>
	<div><div class="statname">Attack</div><div class="atk ststs"></div></div>
	<div><div class="statname">Defense</div><div class="def ststs"></div></div>
	<div><div class="statname">SP Attack</div><div class="satk ststs"></div></div>
	<div><div class="statname">SP Defense</div><div class="sdef ststs"></div></div>
	<div><div class="statname">Speed</div><div class="spd ststs"></div></div>
</div>
<div class="see">
	<img class="seeimg" src="Textures/Art/001.png">
</div>
<div id="text">Evolui para  <b>Ivysaur</b>  a nivel <b>16</b></div>
<div class="list">
	<div class="mini" style="border:0;height:36px;">&nbsp;</div>
	<?php
	for ($i=1; $i <= 151; $i++) { 
		echo'<div class="mini '.$i.'"><img src="Textures/Mini/Png/';
		$results = $mysqli->query("SELECT Name FROM stats where Number='".$i."';");
			while($row = $results->fetch_assoc()) {
				$b=$row['Name'];
			}
		if ($i<10) {
			echo'00'.$i.'.png"><p>#00'.$i.'</p><p id="'.$i.'">&nbsp;&nbsp;'.$b.'</p></div>';
		} else if ($i<100) {
			echo'0'.$i.'.png"><p>#0'.$i.'</p><p id="'.$i.'">&nbsp;&nbsp;'.$b.'</p></div>';
		} else {
			echo $i.'.png"><p>#'.$i.'</p><p id="'.$i.'">&nbsp;&nbsp;'.$b.'</p></div>';
		}
	}


	?>
</div>
<div id="red">
  <div></div>
</div>
<div id="pokelines">
  <div>
    <div></div>
  </div>
</div>
	<?php
	echo '<div id="Login" onclick="location.href = ';
	if (!isset($_SESSION["auth"])) echo "'game.php';";
		else echo "'index.php';";
	echo '">';
		if (isset($_SESSION["auth"])) echo "<p>Voltar ao Jogo</p>";
		else echo "<p>In&iacutecio</p>";
		?>
</div>
<div class="arrow"  id="pokebol2">
  <div>
    <img src="Textures/Header/pokeballcenter.png">
  </div>
</div>
<!--<div><imgsrc="right.png"></div>-->
</body>
</html>