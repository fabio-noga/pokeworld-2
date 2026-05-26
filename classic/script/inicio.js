	$(document).ready(function(){
    $("#pokebola").click(function(){
        $("#white").animate({
        	bottom:0,
        	height:0,
        },1500);
        $("#red").animate({
        	top:0,
        	height:70,
        },1500);
        $("#pokebola").animate({
        	height:128,
        	width:128,
        },1500);
        $("#pokebol").animate({
        	height:128,
        	width:128,
        	top:3,
        	left:80,
        	right:0,
        	
        },1500);
        $("#pokelines").animate({
        	margin:'auto',
        	left:0,
        	width:'100%'
        	
        },1500);
        $("#pokelines2").animate({
        	left:0,
        	top:20,
        	
        },1500);
        $("#login").delay(1400).fadeIn();
    });
});
    var SoundNumber=0;
    var over=function(e){
      document.getElementById("body").style.backgroundColor=e;
    }
    var RegisterBox=function(e){
      document.getElementById("form").style.visibility='visible';
      document.getElementById("BackBlack").style.visibility='visible';
      document.getElementById("PokeChosen").value=e;
      if(e==4) document.getElementById("form1").className='BackRed Back';
      if(e==1) document.getElementById("form1").className='BackGreen Back';
      if(e==7) document.getElementById("form1").className='BackBlue Back';
    }
    var Mute=function(){
      if(SoundNumber==0){
        document.getElementById("sound").src='texture/header/sound/off.png';
        SoundNumber++;
        document.getElementById("audio").muted=true;
      }else{
        document.getElementById("sound").src='texture/header/sound/on.png';
        SoundNumber=0;
        document.getElementById("audio").muted=false;
      }
    }
    var PlayLoop=function(){
      document.getElementById("audio").src='sound/ThemeLoop.mp3';
      document.getElementById("audio").play();
      document.getElementById("audio").loop=true;
    }
    var AudioPlay=function(){
      document.getElementById("audio").play();
    }
    $(document).ready(function(){
      $(document).keyup(function(e) {
        if (e.keyCode == 27) {
          CloseReg()
        }
      });
    });
    function CloseReg () {
      document.getElementById("form").style.visibility='hidden';
      document.getElementById("BackBlack").style.visibility='hidden';
    }
       