<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script type="text/javascript">

	var MapWidth=24;
	var MapHeight=40;
	var array=[];
	var array1=[];
	var array2=[];
	var n=1;
	var PlayerPosition=0;

	function draw(){
		for (var i=1;i<=MapHeight;i++){
			for (var j=1;j<=MapWidth;j++){
				if((i>=MapHeight-1)||(j>=MapWidth-1)||(i<=2)||(j<=2)) {
					x=9;
				}else if (((i>=7)&&(i<=11)&&(j>=11)&&(j<=22))||((i>=14)&&(i<=18)&&(j>=17)&&(j<=22))||((i>=25)&&(i<=29)&&(j>=13)&&(j<=18))) x=1;
				else x=0;
				document.getElementById("p"+n).innerHTML=x;
				array[n]=x;
				array1[n]=i;
				array2[n]=j;


				if((i==(MapHeight/2))&&(j==(MapWidth/2))){ 
					PlayerPosition=n;
					document.getElementById("p"+n).style.backgroundColor='red';
				}
				n++;

			}
		}
	}
	function coiso(e){
		document.getElementById("coisito").innerHTML=array1[e]+" "+array2[e];
	}
		
		$(document).ready(draw);
</script>
<body>
	<div style="position:absolute;top:0;left:0;"><img src="1.png"></div><div style="position:absolute;top:0;left:0;">
<table cellspacing="0" cellpadding="0" style="font-size:12px">
	<?php
		$n=0;
		for ($i=1; $i <= 40 ; $i++) { 
			echo "<tr>";
			for ($j=1; $j <= 24; $j++) { 
				$n++;
				echo"<td onmouseover='coiso(".$n.")'style='height:16px;width:16px;'id='p".$n."'>0</td>";
			}
			echo "</tr>";
		}
	?>
	</table>
	</div>
	<p style="text-align:right"id="coisito">0</p>
</body>
</html>