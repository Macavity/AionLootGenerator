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
	
	
*/
require_once("includes/common.php");
require_once("includes/monster.class.php");
require_once("includes/lootItem.class.php");

$zone = (isset($_REQUEST['zone'])) ? $_REQUEST['zone'] : "";
$monster = (isset($_REQUEST['monster'])) ? $_REQUEST['monster'] : "";
$offset = (isset($_REQUEST['offset'])) ? $_REQUEST['offset'] : 0;
$type = (isset($_REQUEST['type'])) ? $_REQUEST['type'] : "monster";
$list_empty = (isset($_REQUEST['list_empty'])) ? (boolean)$_REQUEST['list_empty'] : false;

$maxPerPage = 50;

/*if($key != "ndunuch0nkuwerdyn0"){
	die("unauthorized access");
}*/


?>
    <form action="gen_loot_single.php">
        Zone-ID: <input name="zone" value="<?= $zone ?>" size="10"> <br>
        Typ : <input type="radio" name="type"
                     value="monster" <?= (($type == "monster") ? ' checked="checked"' : '') ?>/> Monster
        <input type="radio" name="type" value="named" <?= (($type == "named") ? ' checked="checked"' : '') ?>/> Named
        <input type="radio" name="type" value="boss" <?= (($type == "boss") ? ' checked="checked"' : '') ?>/> Boss
        <input type="radio" name="type" value="all" <?= (($type == "all") ? ' checked="checked"' : '') ?>/> Alle<br/>
        NPCs ohne Loot zeigen: <input type="checkbox" name="list_empty" value="true" size="10"> <br>
        <br/>
        oder Monster-ID: <input name="monster" value="<?= $monster ?>" size="10"> <br>
        <br>
        <input type="submit" value="Senden"/>
    </form>
    <br>
<?
if (is_numeric($monster) && $monster > 0) {
    $monsterID = $monster;
    $monster = new Monster($monsterID);

    $monster->parseAionDatabaseData();
    $monster->parseAionYGData();

    $monster->getSameNameYg();

    echo $monster->getLoots();
    $monster->complementLoot();

    $monster->calcPercentages();

    echo "<br><H3>Final Loot</h3>";
    echo "<pre>";
    echo $monster->getFinalLoot();
    echo "</pre>";

}

