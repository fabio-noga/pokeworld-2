<?php 
session_start();
$mysqli = new mysqli('localhost','root','','pokemon');
if ($mysqli->connect_error) {
    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
}


// CONEXÃO AO SERVIDOR (SERVER, USER, PASSWORD, BD)

function autenticar($username,$password) {
  $mysqli = new mysqli('localhost','root','','pokemon');
  if ($mysqli->connect_error) {
    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}
    $hash=password_hash($password,PASSWORD_DEFAULT);
    $query = 'SELECT Nome,Password FROM player WHERE Nome = ? ';
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




<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
<?php 

//  REGISTO


		if (isset($_POST["reg"])) {
			if( ($_POST["username"]!="") && ($_POST["password"]!="") && ($_POST["password2"]!="") && ($_POST["player"]!="") && ($_POST["poke"]!="") && ($_POST["password"]==$_POST["password2"])){

				$user=$_POST["username"];
				$pass=$_POST["password"];
				$player=$_POST["player"];
				$poke=$_POST["poke"];
				$b=0;
				$results = $mysqli->query("SELECT * FROM player where Player='".$user."';");
  			while($row = $results->fetch_assoc()) {
    			$b++;
  			}
  			if($b==0){

  				$d='INSERT INTO player(Nome,Password,Player,LvlMax,Dinheiro,Gym) values(?,?,?,7,200,0)';
  				$regpp=$mysqli->prepare($d);
  				$hashst=password_hash($pass,PASSWORD_DEFAULT);
  				$regpp->bind_param('ssi',$user,$hashst,$player);
  				$regpp->execute();
          $regpp->close();
  				$at2=96;
  				$pp2=25;
  				if($poke==1) {
  					$at1=81;
  					$pp1=35;
  					$at3=91;
  					$pp3=25;
  					$at4=61;
  					$pp4=25;
  				}
  				else if($poke==4) {
  					$at1=66;
  					$pp1=35;
  					$at3=26;
  					$pp3=25;
  					$at4=30;
  					$pp4=15;
  				}
  				else if($poke==7) {
  					$at1=81;
  					$pp1=35;
  					$at3=9;
  					$pp3=30;
  					$at4=92;
  					$pp4=25;
  				}
  				$results = $mysqli->query("SELECT Id FROM player where Nome='".$user."';");
  				while($row = $results->fetch_assoc()) {
  					$userid=$row["Id"];
  				}
  				$results = $mysqli->query("SELECT Type1,Type2,HP FROM stats where Number=".$poke.";");
  				while($row = $results->fetch_assoc()) {
  					$Type1=$row["Type1"];
  					$Type2=$row["Type2"];
  					$HP=$row["HP"];
  				}
  				$d='INSERT INTO pokemon(IdPlayer,Pokemon,Lvl,At1,At2,At3,At4,PP1,PP2,PP3,PP4,HP,XP,Slot) values(?,?,7,?,?,?,?,?,?,?,?,?,0,1)';

  				$regpp=$mysqli->prepare($d);
  				$regpp->bind_param('iiiiiiiiiii',$userid,$poke,$at1,$at2,$at3,$at4,$pp1,$pp2,$pp3,$pp4,$HP);
  				$regpp->execute();
          $regpp->close();
          $_SESSION["auth"]=1;
          $_SESSION["username"]=$user;
          $_SESSION["money"]=200;
          $_SESSION["lvl"]=7;
          if($player==1) $player1='FireRed';
          else if($player==2) $player1='Hiro';
          else if($player==3) $player1='Old';
          else if($player==4) $player1='Duncan';
          else if($player==5) $player1='Ness';
          $_SESSION["player"]=$player1;
          $_SESSION["Slot1"]=$poke;
          $_SESSION["Slot2"]=0;
          $_SESSION["Slot3"]=0;
          $_SESSION["Slot4"]=0;
          $_SESSION["Slot5"]=0;
          $_SESSION["Slot6"]=0;
          $_SESSION["Slot1Lvl"]=7;
          $_SESSION["Slot2Lvl"]=0;
          $_SESSION["Slot3Lvl"]=0;
          $_SESSION["Slot4Lvl"]=0;
          $_SESSION["Slot5Lvl"]=0;
          $_SESSION["Slot6Lvl"]=0;
          $_SESSION["Quant"]=1;
          $results = $mysqli->query("SELECT Id FROM player where Nome='".$_SESSION["username"]."';");
          while($row = $results->fetch_assoc()) {
            $_SESSION["IdPlayer"]=$row["Id"];
          }
  				header('Location: game.php');
  			}
			} else header('Location: register.php');


//    LOG IN

		} else if (isset($_POST["log"])) {
			if (autenticar($_POST["username1"],$_POST["password1"])) {
          $_SESSION["auth"]=1;
          $_SESSION["username"]=$_POST["username1"];
          $results = $mysqli->query("SELECT Id,Dinheiro,LvlMax,Player FROM player where Nome='".$_POST["username1"]."';");
          while($row = $results->fetch_assoc()) {
            $id=$row["Id"];
            $_SESSION["money"]=$row["Dinheiro"];
            $_SESSION["lvl"]=$row["LvlMax"];
            $player=$row["Player"];
          }
          if($player==1) $player1='FireRed';
          else if($player==2) $player1='Hiro';
          else if($player==3) $player1='Old';
          else if($player==4) $player1='Duncan';
          else if($player==5) $player1='Ness';
          $_SESSION["player"]=$player1;
          echo "ID: ".$id;
          $v=0;
          $results = $mysqli->query("SELECT Slot,Pokemon,Lvl FROM pokemon where IdPlayer=".$id.";");
          while($row = $results->fetch_assoc()) {
            if($row["Slot"]==1) {
              $_SESSION["Slot1"]=$row["Pokemon"];
              $_SESSION["Slot1Lvl"]=$row["Lvl"];
            }
            else if($row["Slot"]==2) {$_SESSION["Slot2"]=$row["Pokemon"];$_SESSION["Slot2Lvl"]=$row["Lvl"];}
            else if($row["Slot"]==3) {$_SESSION["Slot3"]=$row["Pokemon"];$_SESSION["Slot3Lvl"]=$row["Lvl"];}
            else if($row["Slot"]==4) {$_SESSION["Slot4"]=$row["Pokemon"];$_SESSION["Slot4Lvl"]=$row["Lvl"];}
            else if($row["Slot"]==5) {$_SESSION["Slot5"]=$row["Pokemon"];$_SESSION["Slot5Lvl"]=$row["Lvl"];}
            else if($row["Slot"]==6) {$_SESSION["Slot6"]=$row["Pokemon"];$_SESSION["Slot6Lvl"]=$row["Lvl"];}
            $v++;
          }
          if(!isset($_SESSION["Slot2"]))$_SESSION["Slot2"]=0;
          if(!isset($_SESSION["Slot3"]))$_SESSION["Slot3"]=0;
          if(!isset($_SESSION["Slot4"]))$_SESSION["Slot4"]=0;
          if(!isset($_SESSION["Slot5"]))$_SESSION["Slot5"]=0;
          if(!isset($_SESSION["Slot6"]))$_SESSION["Slot6"]=0;
          if(!isset($_SESSION["Slot2Lvl"]))$_SESSION["Slot2Lvl"]=0;
          if(!isset($_SESSION["Slot3Lvl"]))$_SESSION["Slot3Lvl"]=0;
          if(!isset($_SESSION["Slot4Lvl"]))$_SESSION["Slot4Lvl"]=0;
          if(!isset($_SESSION["Slot5Lvl"]))$_SESSION["Slot5Lvl"]=0;
          if(!isset($_SESSION["Slot6Lvl"]))$_SESSION["Slot6Lvl"]=0;
          $_SESSION["Quant"]=$v;
          echo nl2br ("\nNome: ".$_SESSION["username"]."\nDinheiro: ".$_SESSION["money"]."$\nLvlMax: ".$_SESSION["lvl"]."\n".$_SESSION["player"]."\nSLOT1: ".$_SESSION["Slot1"]."\nLVLSlot1: ".$_SESSION["Slot1Lvl"]."\n".$_SESSION["Slot3"]."\n".$_SESSION["Slot4"]."\n".$_SESSION["Slot5"]."\n".$_SESSION["Slot6"]."\n");
          $results = $mysqli->query("SELECT Id FROM player where Nome='".$_SESSION["username"]."';");
          while($row = $results->fetch_assoc()) {
            $_SESSION["IdPlayer"]=$row["Id"];
          }
          header('Location: game.php');
      } else header('Location: register.php');


//     CONVIDADO

    } else if (isset($_POST["guest"])) {
          $_SESSION["auth"]=1;
          $_SESSION["username"]='Convidado';
          $_SESSION["money"]=200;
          $_SESSION["lvl"]=7;
          $_SESSION["player"]='FireRed';
          $_SESSION["Slot1"]=4;
          $_SESSION["Slot2"]=0;
          $_SESSION["Slot3"]=0;
          $_SESSION["Slot4"]=0;
          $_SESSION["Slot5"]=0;
          $_SESSION["Slot6"]=0;
          $_SESSION["Slot1Lvl"]=7;
          $_SESSION["Slot2Lvl"]=0;
          $_SESSION["Slot3Lvl"]=0;
          $_SESSION["Slot4Lvl"]=0;
          $_SESSION["Slot5Lvl"]=0;
          $_SESSION["Slot6Lvl"]=0;
          $_SESSION["Quant"]=1;
          
		} else {
      session_unset();
      header('Location: register.php');
      }
	 ?>

</body>
</html>