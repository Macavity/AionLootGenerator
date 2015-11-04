<?php
/*
	v1.0
	
	Ziel: Ein Monster wird eingelesen, für dieses wird der Loot aus adb.com und aus unserer DB gelesen.
	Kinah soll 50% wahrscheinlichkeit haben
	Quest Items sollen mindestens 10% Dropchance haben (inklusive Droprate)
	graue items sollen 100/Droprate/CountGrey*2 Prozent kriegen
	
		zb 5 graue Items:
		100/5 = 20 
		/ 5 => 4
		*2 => 8% pro Item 
		mit droprate => 40% Dropchance pro Grauem gegenstand. Hei�t es sollten im Schnitt 2 Graue Items kommen.
	
	falls greenCount > 0{
		100/droprate/greenCount*0.3 => jedes 3te mal ein gr�nes
	}
	
	Falls Monster Boss{
	
		falls blueCount > 0{
			100/Droprate/blueCount Prozent f�r jedes so das im schnitt eines der blauen Items dropt
		}
		falls goldCount > 0 && blueCount == 0{
		
		}
	
	}
	else{
		
	}
	
	
	
*/
$droprate = 5;
$zone = (isset($_REQUEST['zone'])) ? $_REQUEST['zone'] : "";
$key = (isset($_REQUEST['key'])) ? $_REQUEST['key'] : "";
$offset = (isset($_REQUEST['offset'])) ? $_REQUEST['offset'] : 0;
$type = (isset($_REQUEST['type'])) ? $_REQUEST['type'] : "monster";
$list_empty = (isset($_REQUEST['list_empty'])) ? (boolean)$_REQUEST['list_empty'] : false;

$maxPerPage = 50;

?>
<form action="gen_loot.php">
    <input type="hidden" name="key" value="<?= $key ?>">
    Zone-ID: <input name="zone" value="<?= $zone ?>" size="10"> <br>
    Typ : <input type="radio" name="type" value="monster" <?= (($type == "monster") ? ' checked="checked"' : '') ?>/>
    Monster
    <input type="radio" name="type" value="named" <?= (($type == "named") ? ' checked="checked"' : '') ?>/> Named
    <input type="radio" name="type" value="boss" <?= (($type == "boss") ? ' checked="checked"' : '') ?>/> Boss
    <input type="radio" name="type" value="all" <?= (($type == "all") ? ' checked="checked"' : '') ?>/> Alle<br/>
    NPCs ohne Loot zeigen: <input type="checkbox" name="list_empty" value="true" size="10"> <br>
    <br>
    <input type="submit" value="Senden"/>
</form>
<br>
<?
if (is_numeric($zone) && $zone > 0) {
    $adbArray = file("http://www.aiondatabase.com/npc/list/3." . $zone);
    $adbString = implode("", $adbArray);

    $check_name = '<a href="/npc/list/3.' . $zone . '">';
    $regexName = '@<a href="\/npc\/list\/3\.' . $zone . '">([^<]+)<\/a>@';

    $noLootMobs = array();
    /*
        {"id":749163,"n":"5Destruction Trap","r":1,"rn":"Asmodian","i":2,"t":"Monster"
    */
    if ($type == "all") {
        $regexMob = '@\{"id":(\d+),"n":"\d*([^"]+)","r":(\d+),"rn":"([^"]+)","i":(\d+),"t":"(Boss|Named Mob|Monster)"@';
        echo "<p>-- Alle --</p>";
    } elseif ($type == "boss") {
        $regexMob = '@\{"id":(\d+),"n":"\d*([^"]+)","r":(\d+),"rn":"([^"]+)","i":(\d+),"t":"(Boss)"@';
        echo "<p>-- Bosse --</p>";
    } elseif ($type == "named") {
        $regexMob = '@\{"id":(\d+),"n":"\d*([^"]+)","r":(\d+),"rn":"([^"]+)","i":(\d+),"t":"(Named Mob)"@';
        echo "<p>-- Named --</p>";
    } else {
        $regexMob = '@\{"id":(\d+),"n":"\d*([^"]+)","r":(\d+),"rn":"([^"]+)","i":(\d+),"t":"(Monster)"@';
        echo "<p>-- Monster --</p>";
    }
    $sql = "";
    $paginate = false;
    $counter = $offset;

    foreach ($adbArray as $line) {
        if (substr_count($line, $check_name) > 0) {
            if (preg_match($regexName, $line, $matches)) {
                $zone_name = $matches[1];
                echo "\n<br><h3>$zone_name</h3>";
            }
        }
        if (preg_match_all($regexMob, $line, $matches)) {
            $count = count($matches[1]);
            $ids = $matches[1];
            $names = $matches[2];
            $types = $matches[3];
            $mobCounter = 0;        // To limit the page checks per page

            //echo "<p>$count NPC gefunden insgesamt.</p>";

            echo "<pre>";            // to show the SQL in preformatted font
            for ($i = $offset; $i < $count; $i++) {
                $mobId = $ids[$i];
                $mobName = $names[$i];
                $mobType = $types[$i];
                //echo "\n<br>Monster gefunden: [$mobId] $mobName";

                // get data from adb.com
                $adbMobArray = file("http://www.aiondatabase.com/npc/" . $mobId);
                $adbMobString = implode("", $adbMobArray);

                $regexLoot = '@\{"id":(\d+),"pct":(-?\d+\.?\d*),"mia":(-?\d+\.?\d*),"mxa":(-?\d+\.?\d*),"n":"\d*([^"]+)"@';

                if (preg_match_all($regexLoot, $adbMobString, $lootMatches)) {
                    $mobCounter++;
                    $countLoot = count($lootMatches[1]);

                    if ($mobType == "Boss")
                        echo "\n\n-- [$mobId] BOSS - $mobName ($countLoot)";
                    if ($mobType == "Named Mob")
                        echo "\n\n-- [$mobId] NAMED - $mobName ($countLoot)";
                    else
                        echo "\n\n-- [$mobId] $mobName ($countLoot)";
                    echo "\nDELETE FROM `droplist` WHERE `mobId` = $mobId;";
                    echo "\nINSERT INTO `droplist` (`mobId`, `itemId`, `min`, `max`, `chance`) VALUES ";

                    for ($j = 0; $j < $countLoot; $j++) {
                        $lootId = $lootMatches[1][$j];
                        $lootPct = $lootMatches[2][$j];
                        $lootMin = $lootMatches[3][$j];
                        $lootMax = $lootMatches[4][$j];
                        $lootName = $lootMatches[5][$j];
                        if ($lootPct < 0)
                            $lootPct = 0.01;
                        echo "\n\t($mobId, $lootId, $lootMin, $lootMax, $lootPct)";
                        echo ($j == ($countLoot - 1)) ? ";" : ",";
                        echo " -- $lootName ";
                    }
                } else {
                    $noLootMobs[] = "[$mobId] $mobName";
                }
                if ($mobCounter == $maxPerPage) {
                    // are there other entries left
                    if ($i <= ($count - 1)) {
                        $paginate = true;
                    }
                }

            }
            echo "\n/*
	=== $zone_name ===

		Zone-ID: $zone
		Typ: " . ucfirst($type) . "
		Loot: $mobCounter/$count";
            if ($list_empty && count($noLootMobs) > 0) {
                echo "\n\n\t\tNo Loot:\n\t\t" . (implode(", ", $noLootMobs)) . "";
            }
            echo "\n*/";

            echo "</pre>";

            if ($paginate) {
                /*	echo '
                    <p>
                        <a href="gen_loot.php?zone='.$zone.'&offset='.($offset+$maxPerPage).'">N&auml;chste Seite</a>
                    </p>';
                */
            }
        }
    }


}

