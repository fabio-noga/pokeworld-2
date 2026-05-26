<?php
session_start();
$mysqli = new mysqli('localhost','root','','pokemon');
if ($mysqli->connect_error) {
    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
}
if(isset($_SESSION["auth"])) header('Location: game.php');
function autenticar($username,$password) {
  $mysqli = new mysqli('localhost','root','','pokemon');
  if ($mysqli->connect_error) {
    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
}
    $hash=password_hash($password,PASSWORD_DEFAULT);
    $query = 'SELECT Name,Password FROM Login WHERE Name=?';
        $ppare = $mysqli->prepare($query);
        $ppare->bind_param('s',$username);
        $ppare->bind_result($username,$hash);
        $ppare->execute();
    if($ppare->fetch()) {
        return (password_verify($password, $hash));
    }
    else {
        return false;
    }
}
?>
<?php  
if (isset($_POST["reg"])) {
    $b=0;
    $c=0;
        $usern=$_POST["username"];
        $userp=$_POST["password"];
        $userm=$_POST["email"];

      if(($_POST['username']!='')&&($_POST['password']!='')){
          
      $results = $mysqli->query("SELECT * FROM login where Name='".$usern."';");
      while($row = $results->fetch_assoc()) {
        $b++;
      }
      if($b==0){  
        $d='INSERT INTO login(Name,Password,Email) values(?,?,?)';
              $regpp=$mysqli->prepare($d);
              $hashst=password_hash($userp,PASSWORD_DEFAULT);
              $regpp->bind_param('sss',$usern,$hashst,$userm);
              $regpp->execute();

          $d='INSERT INTO pokemon(IdPokemon,Vida,VidaMax,XP,Lvl,IdCaptura,At1,slot) values(?,?,?,?,?,?,?,?)';
          $results = $mysqli->query("SELECT * FROM login where Name='".$_SESSION["username"]."';");
          while($row = $results->fetch_assoc()) {
            $user=$row["IdPlayer"];
          }
          $regpp=$mysqli->prepare($d);
          $Vida=100;
          $VidaMax=100;
          $XP=0;
          $Lvl=5;
          $IdCaptura=1;
          $At1=1;
          $poke=$_POST["poke"];
          $slot=1;
          $regpp->bind_param('iiiiiiii',$poke,$Vida,$VidaMax,$XP,$Lvl,$IdCaptura,$At1,$slot);
          $regpp->execute();
          $_SESSION["auth"]=1;
          $_SESSION["username"]=$_POST["username"];
          header('Location: game.php');
      }else if($b!=0) echo "<script>alert('O Nome já está em uso');</script>"; 
      else echo "<script>alert('ocorreu um erro');</script>";
      }else echo "<script>alert('Ups, houve um erro!');</script>";
    }


    if (isset($_POST["login"])) {
    if (autenticar($_POST["username"],$_POST["password"])) {
        $_SESSION["auth"]=1;
        $_SESSION["username"]=$_POST["username"];
        print_r($_GET);
        header('Location: game.php');
    }else {
        if (isset($_SESSION["auth"])) session_unset("auth");
        if (isset($_SESSION["username"])) session_unset("username");
        echo "<script>alert('Erro! Nome ou palavra-passe errada');</script>";
    }
}

    if(isset($_POST["logout"])){
      if (isset($_SESSION["auth"])) session_unset("auth");
          if (isset($_SESSION["username"])) session_unset("username");
          header('Location: index.php');
      }
?>
<!doctype html>
<html>
<head>
<title>Bem-Vindo ao Mundo Pokémon!</title>
<link rel="stylesheet" type="text/css" href="css/header.css">
<link rel="stylesheet" type="text/css" href="css/index.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script type="text/javascript" src="script/inicio.js"></script>
<style type="text/css">
html{
    background: url(Textures/Wallpapers/<?php $array=array(1,2,3); echo $array[rand(0, count($array) - 1)];?>.jpg) no-repeat center center fixed; 
      -webkit-background-size: cover;
      -moz-background-size: cover;
      -o-background-size: cover;
      background-size: cover;  
}
</style>
</head>

<body>
<!--Imagens-->
<div id="body" class="absolute">
  <div class="main">
      <div class="ImgBlue Img" onmouseover="over('blue')" onclick="RegisterBox(7)"><img src="Textures/choice/water.png"></div>
      <div class="ImgGreen Img" onmouseover="over('green')" onclick="RegisterBox(1)"><img src="Textures/choice/grass.png"></div>
      <div class="ImgRed Img" onmouseover="over('red')" onclick="RegisterBox(4)"><img src="Textures/choice/fire.png"></div>
  </div>
</div>
<!--Register-->
<div id="BackBlack"></div>

<div class="register">
    <div id="form">
      <form enctype="multipart/form-data" action="index.php" method="post">
      <div style="float:right;"><img style="height:32px;width:32px;" onclick="CloseReg()" src="Textures/oak/close.png"></div> 
          <div class="form2">
          <div style="margin:auto;width:258px;">
            <input class="input" placeholder="Nome" style="display: inline-block;" size="22"type="text" name="username" value="" required maxlength="50"><br><br>
            <input class="input" placeholder="Password" style="display: inline-block;" size="22" type="password" name="password" value="" required maxlength="50"><br><br>
            <input class="input" placeholder="Email" style="display: inline-block;" size="22" type="text" name="email" value="" required maxlength="50"><br><br><br><br>
            <input type="hidden" id="PokeChosen" name="poke" value="">
            <div style="width:200px;margin:auto;"><input id="form1" class="BackRed" type="submit" value="Jogar!" name="reg"/></div>
          </div>
          </div>
      </form>
    </div>
</div>


</div>

<div style="position:absolute;top:80px">
  <img src="Textures/Header/sound/on.png" style="height:32px;" id="sound" onclick="Mute()">
  <audio onended="PlayLoop();" muted id="audio">
      <source src="sound/ThemeMain.mp3" type="audio/mpeg">
  </audio>
</div>
<div style="position:fixed;top:0;height:50%;width:100%;left:0;right:0;" id="red">
  <div style="background-color:#ff1c1c;width:100%;height:100%;"></div>

</div>
<div style="position:fixed;bottom:0;width:100%;left:0;right:0;height:50%;" id="white">
  <div style="background-color:white;width:100%;height:100%;bottom:0;"></div>
</div>
<div style="position:fixed;width:100%;top:45%;left:50%;" id="pokelines2">
  <div style="margin:auto;width:400px;">
    <div style="position:absolute;top:30px;height:30px;left:30px;background-color:black;width:10px;" id="pokelines"></div>
  </div>
</div>
<div style="position:fixed;width:100%;top:28%;"id="pokebol">
  <div style="margin:auto;width:400px;">
    <img style="width:400px;height:400px;"src="Textures/Header/pokeballcenter.png" id="pokebola" onclick="AudioPlay()">
  </div>
</div>
    <form enctype="multipart/form-data" action="index.php" method="post" style="display:none;" id="login">
    <div class="login">
      <input placeholder="Nome" style="display: inline-block;" size="22" type="text" name="username" value="" required maxlength="50"><br>
      <input placeholder="Password" style="display: inline-block;" size="22" type="password" name="password" value="" required maxlength="50"><br>
      <input style="display: inline-block;" type="submit" value="Login!" name="login"/>
    </div>
    </form>
</body>
</html>