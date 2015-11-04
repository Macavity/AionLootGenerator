<?
/*
	v1.1
	
	- Monster ID wird übergeben
	- Auslesen der Daten von aiondatabas.com
	- Auslesen der Daten von aion.yg.com
	- Auslesen der Daten von aionarmory.com (Nur Items keine Wahrscheinlichkeiten)
	- Zusammenschreiben aller Daten
		- Items der gleichen Farbe sollen untereinander die gleiche Chance haben
		- 
	- SQL Ausgabe
	
	
		zb 5 graue Items:
		100/5 = 20 
		/ 5 => 4
		*2 => 8% pro Item 
		mit droprate => 40% Dropchance pro Grauem gegenstand. Hei§t es sollten im Schnitt 2 Graue Items kommen.
	
		1/5
		15*0.2 = 
		20
		13
		24
		50
		=> 24.4
	
	falls greenCount > 0{
		100/droprate/greenCount*0.3 => jedes 3te mal ein grŸnes
	}
	
	Falls Monster Boss{
	
		falls blueCount > 0{
			100/Droprate/blueCount Prozent fŸr jedes so das im schnitt eines der blauen Items dropt
		}
		falls goldCount > 0 && blueCount == 0{
		
		}
	
	}
	else{
		
	}
	
*/
require_once("includes/zone.class.php");
require_once("includes/monster.class.php");
require_once("includes/lootItem.class.php");

$zoneId = (isset($_REQUEST['zone'])) ? $_REQUEST['zone'] : "";
$monster = (isset($_REQUEST['monster'])) ? $_REQUEST['monster'] : "";
$offset = (isset($_REQUEST['offset'])) ? $_REQUEST['offset'] : 0;
$type = (isset($_REQUEST['type'])) ? $_REQUEST['type'] : "monster";
$list_empty = (isset($_REQUEST['list_empty'])) ? (boolean) $_REQUEST['list_empty'] : false;
 
$maxPerPage = 50;

/*if($key != "ndunuch0nkuwerdyn0"){
	die("unauthorized access");
}*/


?>
<form action="<?=$_SERVER['PHP_SELF']?>">
	Zone-ID: <input name="zone" value="<?=$zoneId?>" size="10"> <br>
	NPCs ohne Loot zeigen: <input type="checkbox" name="list_empty" value="true" size="10"> <br>
	<br />
	<input type="submit" value="Senden"/>
</form>
<br>
<?
if(is_numeric($zoneId) && $zoneId > 0){
	
	$zone = new Zone($zoneId);
	$zone->getMonsters();
	//$zone->monsterIds = array(210524,210525);
	
	$zone->getLoot();
	//print_r($zone);
}
else{
	echo "Keine g&uuml;ltige Zone-Id.";
}
?>
