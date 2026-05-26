<!DOCTYPE html>
<html>
<head>
	<title></title>    
	<style type="text/css">
	#PlayerImg{
		width:55px;
	}
	</style>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script type="text/javascript">
	//change
	var MapWidth=24;
	var MapHeight=40;
	var array=[];
	var n=1;
	var PlayerPosition=0;
	var PetTexture="Pokemon/6/mega/down/";
	var PlayerTexture="Player/FireRed/";
	document.getElementById("PetImg").src=PetTexture+"1.png";
	document.getElementById("PlayerImg").src=PlayerTexture+"1.png";
	function draw(){
		for (var i=1;i<=MapHeight;i++){
			for (var j=1;j<=MapWidth;j++){
				if((i>=MapHeight-1)||(j>=MapWidth-1)||(i<=2)||(j<=2)) {
					x=4;
				}else x=0;
				document.getElementById("p"+n).innerHTML=x;
				array[n]=x;



				if((i==(MapHeight/2))&&(j==(MapWidth/2))){ 
					PlayerPosition=n;
					document.getElementById("p"+n).style.backgroundColor='red';
				}
				n++;

			}
		}





		var ji=$(window).height();
		var je=$(window).width();
		//change
		$(".loveit").css('top',-($(".loveit").height()-ji)/2);
		$(".player").css('top',ji/2-78);
		$(".player").css('left',je/2-50.5);

	}

	var position=0;
	function KeyDown(e){
		var x=e.keyCode;
		if($(".loveit").queue("fx").length==1) return;

	    if (e.ctrlKey==true && (e.which == '171' || e.which == '173')) {
	        e.preventDefault();
	     }
	     $(window).bind('mousewheel DOMMouseScroll', function(event) {
	        if(event.ctrlKey == true)
	        {
	            event.preventDefault(); 
        	}
    	});
		if(x==38){
			//up
			if(array[PlayerPosition-(MapWidth)]!=4){
				PlayerPosition-=(MapWidth);
				document.getElementById("p"+PlayerPosition).style.backgroundColor='yellow';
				if(position==0) {
					document.getElementById("PlayerImg").src=PlayerTexture+"11.png";
					position++;
				}else{
					document.getElementById("PlayerImg").src=PlayerTexture+"12.png";
					position=0;
				}
				$(".loveit").animate({
					top:'+=48px'
				},'medium');
				$(".teste").animate({
					top:'+=48px'
				},{duration: 'medium',queue: false});
				setTimeout(function() {
				document.getElementById("PlayerImg").src=PlayerTexture+"10.png";
            	}, 250);
			}

		} else if(x==40){
			//down
			if(array[PlayerPosition+(MapWidth)]!=4){
				PlayerPosition+=(MapWidth);
				document.getElementById("p"+PlayerPosition).style.backgroundColor='yellow';
				if(position==0) {
					document.getElementById("PlayerImg").src=PlayerTexture+"2.png";
					position++;
				}else{
					document.getElementById("PlayerImg").src=PlayerTexture+"3.png";
					position=0;
				}
				$(".loveit").animate({
					top:'-=48px'
				},'medium');
				$(".teste").animate({
					top:'-=48px'
				},{duration: 'medium',queue: false});
				setTimeout(function() {
				document.getElementById("PlayerImg").src=PlayerTexture+"1.png";
            	}, 250);

			}
		} else if(x==37){
			//left
			if(array[PlayerPosition-1]!=4){
				PlayerPosition-=1;
				document.getElementById("p"+PlayerPosition).style.backgroundColor='yellow';
				document.getElementById("PlayerImg").src=PlayerTexture+"5.png";
				$(".loveit").animate({
					left:'+=48px'
				},'medium');
				$(".teste").animate({
					left:'+=48px'
				},{duration: 'medium',queue: false});
				setTimeout(function() {
				document.getElementById("PlayerImg").src=PlayerTexture+"4.png";
            	}, 250);
			}
		} else if(x==39){
			//right
			if(array[PlayerPosition+1]!=4){
				PlayerPosition+=1;
				document.getElementById("p"+PlayerPosition).style.backgroundColor='yellow';
				document.getElementById("PlayerImg").src=PlayerTexture+"9.png";
				$(".loveit").animate({
					left:'-=48px'
				},'medium');
				$(".teste").animate({
					left:'-=48px'
				},{duration: 'medium',queue: false});
				setTimeout(function() {
				document.getElementById("PlayerImg").src=PlayerTexture+"7.png";
            	}, 250);
			}
		}
	}
	addEventListener("keydown", KeyDown);
	$(document).ready(draw);
	</script>
</head>
<body>
<div style="background-color:white;position:fixed;top:0;right:0;bottom:0;left:0;">
	<div style="margin:auto;width:1155px">
	<div style="position:absolute">
	<div style="position:absolute;top:0;left:0;" class="loveit"><img style="height:1920px;width:1155px;"src="1.png"></div>
	<div style="visibility:visible;position:absolute;width:1155px;top:0;left:0;font-size:12px;text-align:center;">

	</div>
	</div>
</div>

</div>

<div style="position:fixed;top:0;right:0;bottom:0;left:0;">
	<div style="margin:auto;width:100px;">
		<div style="position:absolute;" class="player" ><img id="PetImg" src=""></div>
	</div>
</div>
<div style="position:fixed;top:0;right:0;bottom:0;left:0;">
	<div style="margin:auto;width:100px;">
		<div style="position:absolute;" class="player" ><img id="PlayerImg" src=""></div>
	</div>
</div>
<div class="teste" style="position:fixed;top:0;"><img src="Pokeball.png"></div>
<div style="position:fixed;top:0;left:0;right:0;background-color:white;height:80px;"></div>
<div style="position:fixed;background-color:white;bottom:0;left:0;right:0; height:80px;"></div>
<div style="position:fixed;background-color:white;bottom:0;top:0;width:80px;right:0;"></div>
<div style="position:fixed;background-color:white;bottom:0;top:0;width:80px;left:0;"></div>
	<table cellspacing="0" cellpadding="0" style="position:absolute;">
	<?php
		$n=0;
		for ($i=1; $i <= 40 ; $i++) { 
			echo "<tr>";
			for ($j=1; $j <= 24; $j++) { 
				$n++;
				echo"<td style='font-size:14px;height:16px;width:16px;'id='p".$n."'>0</td>";
			}
			echo "</tr>";
		}
	?>
	</table>
</body>
</html>