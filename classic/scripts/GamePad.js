function pads(x){
		if($(".loveit").queue("fx").length==1) return;
	    //Movimentação do Player e Pokemón do Overworld (GamePad)
		if(x==38){
			//up
			if(position==0) {
				document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/11.png";
				position++;
			}else{
				document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/12.png";
				position=0;
			}
			setTimeout(function() {
				document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/10.png";
        	}, 250);
			if((array[PlayerPosition-(MapWidth)]!=4)&&(array[PlayerPosition-(MapWidth)]!=6)){
				document.getElementById("PetImg").src="Textures/Overworld/"+PetType+"/"+PetPosition1+"/"+PetNumber+".png";
				PlayerPosition-=(MapWidth);
				document.getElementById("p"+PlayerPosition).style.backgroundColor='yellow';
				PetPosition1=7;
				$(".loveit").animate({
					top:'+=48px'
				},'medium');
				$(".map2").animate({
					top:'+=48px'
				},{duration: 'medium'});
				$(".pet").animate({
					top:'+=48px'
				},{duration: 'medium',queue: false});
				setTimeout(function() {
					yes  = Middle1+50;
					$(".pet").animate({
						"top" : yes,
						"left" : Middle2
					},220);
					$(".pet1").css("z-index",2);
				}, 220);
			}

		} else if(x==40){
			//down
			if(position==0) {
				document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/2.png";
				position++;
			}else{
				document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/3.png";
				position=0;
			}
			setTimeout(function() {
					document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/1.png";
	        }, 250)
			if((array[PlayerPosition+(MapWidth)]!=4)&&(array[PlayerPosition+(MapWidth)]!=6)){
				PlayerPosition+=(MapWidth);
				document.getElementById("p"+PlayerPosition).style.backgroundColor='yellow';
				$(".loveit").animate({
					top:'-=48px'
				},'medium');
				$(".map2").animate({
					top:'-=48px'
				},{duration: 'medium',queue: false});
				$(".pet").animate({
					top:'-=48px'
				},{duration: 'medium',queue: false});
				setTimeout(function() {
					PetPosition1=1;
					document.getElementById("PetImg").src="Textures/Overworld/"+PetType+"/"+PetPosition1+"/"+PetNumber+".png";
					yes  = Middle1;
					$(".pet").animate({
						"top" : yes,
						"left" : Middle2
					},220);
					
					$(".pet1").css("z-index",0);
				}, 220);
			}else if((array[PlayerPosition+(MapWidth)]==6)){
				PlayerPosition+=(MapWidth)*2;
				document.getElementById("p"+PlayerPosition).style.backgroundColor='yellow';
				$(".loveit").animate({
					top:'-=96px'
				},'medium');
				$(".map2").animate({
					top:'-=96px'
				},{duration: 'medium',queue: false});
				$(".pet").animate({
					top:'-=96px'
				},{duration: 'medium',queue: false});
				setTimeout(function() {
					PetPosition1=1;
					document.getElementById("PetImg").src="Textures/Overworld/"+PetType+"/"+PetPosition1+"/"+PetNumber+".png";
					yes  = Middle1;
					$(".pet").animate({
						"top" : yes,
						"left" : Middle2
					},220);
					
					$(".pet1").css("z-index",0);
				}, 220);
			}
		} else if(x==37){
			//left
			document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/5.png";
			setTimeout(function() {
			document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/4.png";
        	}, 250);
			if((array[PlayerPosition-1]!=4)&&(array[PlayerPosition-1]!=6)){
				PlayerPosition-=1;
				document.getElementById("p"+PlayerPosition).style.backgroundColor='yellow';
				$(".loveit").animate({
					left:'+=48px'
				},'medium');
				$(".map2").animate({
					left:'+=48px'
				},{duration: 'medium',queue: false});
				$(".pet").animate({
					left:'+=48px'
				},{duration: 'medium',queue: false});
				setTimeout(function() {
					PetPosition1=3;
					document.getElementById("PetImg").src="Textures/Overworld/"+PetType+"/"+PetPosition1+"/"+PetNumber+".png";
					yes  = Middle2+38;

					$(".pet").animate({
						"left" : yes,
						"top" : (Middle1+30)
					},220);
				}, 220);
			}
		} else if(x==39){
			//right
			document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/9.png";
			setTimeout(function() {
			document.getElementById("PlayerImg").src="Player/"+PlayerTexture+"/7.png";
        	}, 250);
			if((array[PlayerPosition+1]!=4)&&(array[PlayerPosition+1]!=6)){
				PlayerPosition+=1;
				document.getElementById("p"+PlayerPosition).style.backgroundColor='yellow';
				$(".loveit").animate({
					left:'-=48px'
				},'medium');
				$(".map2").animate({
					left:'-=48px'
				},{duration: 'medium',queue: false});
				$(".pet").animate({
					left:'-=48px'
				},{duration: 'medium',queue: false});
				setTimeout(function() {
					PetPosition1=5;
					document.getElementById("PetImg").src="Textures/Overworld/"+PetType+"/"+PetPosition1+"/"+PetNumber+".png";
					yes  = Middle2-38;

					$(".pet").animate({
						"left" : yes,
						"top" : (Middle1+30)
					},220);
				}, 220);
			}
		}
	}

