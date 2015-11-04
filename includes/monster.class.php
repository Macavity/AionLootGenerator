<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/common.php");

class Monster
{

    public $id = 0;
    public $name;
    public $rank = "", $race = "";
    public $adbURL, $ygURL, $aaURL;
    public $lootItems = array();
    public $lootItemsSN = array();        // Loot from Monster with the same name
    public $lootItemsJunk = array(), $lootItemsCommon = array(),
        $lootItemsSuperior = array(), $lootItemsHeroic = array(),
        $lootItemsFabled = array(), $lootItemsEternal = array();
    public $lootChances = array();
    public $minLootChances = array(), $minGroupChances = array();
    public $sameNameMonsterIds = array();
    public $debug = array();
    public $elderItems;
    public $copiedFromMonster;

    public function Monster($id)
    {
        $this->id = $id;
        $debug[] = "\nMobID: " . $this->id;

        $this->minLootChances = array(
            JUNK => 0.001,
            COMMON => 0.001,
            SUPERIOR => 0.001,
            HEROIC => 0.001,
            FABLED => 0.0001,
            ETERNAL => 0.00001,
            QUEST => 0,
            KINAH => 100,
            ELDER_SET => 0.01,
        );
        $this->minGroupChances = array(
            JUNK => 5,
            COMMON => 3,
            SUPERIOR => 1,
            HEROIC => 0.1,
            FABLED => 0.01,
            ETERNAL => 0.000001,
        );

        $this->elderItems = array(
            // Waffen
            100100231, 100200339, 100500242, 100600258,
            100000318, 100900235, 100900567, 101300232,
            101500244, 101500583, 101700256, 115000504,
            // Ring
            122001040,

            // Stoff, Leder, Kette, Platte
            110100575, 110300536, 110500514, 110600509,    // Brust
            111100504, 111300506, 111500493, 111600480,        // Handschuhe
            112100468, 112300468, 112500456, 112600461,        // Schultern
            113100503, 113300519, 113500492, 113600476,        // Beine
            114100520, 114300533, 114500499, 114600466,    // Schuhe
        );

        /* ADB */
        $regexAdbZoneMob = '@\{"id":(\d+),"n":"\d*([^"]+)","r":(\d+),"rn":"([^"]+)","i":(\d+),"t":"(Boss|Named Mob|Monster)"@';

    }

    /*
        Get Loot from Aiondatabase
    */
    public function parseAionDatabaseData ()
    {

        $adbMobArray = file("http://www.aiondatabase.com/npc/" . $this->id);
        $adbMobString = implode("", $adbMobArray);

        $regexAdbMobName = '@<td><img src="/img/aion/nav/titleicon_npc.png" height="29" width="22" border="0"></td><td>&nbsp;([^<]+)</td>@';
        //echo "\n<br>Regex: $regexAdbMobName";
        if (preg_match($regexAdbMobName, $adbMobString, $matches)) {
            //print_r($matches);
            $this->name = $matches[1];
            $debug[] = "\n<br>Found name: " . $this->name;
        }

        // Check if Quest Items are in the List
        $posStart = strpos($adbMobString, '"icat":[1,');
        $posEnd = strpos($adbMobString, '}]}', $posStart);
        $stringCat = substr($adbMobString, $posStart, $posEnd - $posStart + 5);

        $questPos = -1;
        if (strpos($stringCat, '[1,{"it":"Quest Items","itl":"8.182200000","ipt":"Misc","iptl":"8"}') > 0) {
            $questPos = 1;
        }

        $regexAdbMobItem =
            //{"id":100900197,"pct":0.290698,"mia":1,"mxa":1,"n":"4Kromede's Greatsword","l":35,"p":250150,"r":3,"i":1,"cat":1,"img":"ts_u001c_small.jpg","mvt":3,"is":[{"n":"Kromede the Corrupt","t":1,"id":212846},{"n":"Vile Judge Kromede","t":1,"id":214621}]},
            '@\{"id":(\d+),"pct":(-?\d+\.?\d*),"mia":(-?\d+\.?\d*),"mxa":(-?\d+\.?\d*),"n":"(\d)([^"]+)","l":(-?\d+),"p":(-?\d+),"r":(-?\d+),"i":(-?\d+),"cat":(-?\d+)@';
        if (preg_match_all($regexAdbMobItem, $adbMobString, $lootMatches)) {
            //echo "<br>Found Item";
            //print_r($lootMatches);

            $countLoot = count($lootMatches[1]);
            for ($j = 0; $j < $countLoot; $j++) {
                $itemID = $lootMatches[1][$j];
                //echo "\nADB Item $itemID";
                $lootItem = $this->getLootItem($itemID);
                $lootItem->matchAdb($lootMatches, $j, $questPos);
                //echo print_r($lootItem,true);
            }
        }
    }

    /*
        Add Loot from another Monster from Aiondatabase
    */
    public function sameNameLootAdb ($monsterId)
    {

        $adbMobArray = file("http://www.aiondatabase.com/npc/" . $monsterId);
        //echo '<br><a href="http://www.aiondatabase.com/npc/'.$monsterId.'" target="_blank">ADB URL</a>';
        $adbMobString = implode("", $adbMobArray);

        // Check if Quest Items are in the List
        $posStart = strpos($adbMobString, '"icat":[1,');
        $posEnd = strpos($adbMobString, '}]}', $posStart);
        $stringCat = substr($adbMobString, $posStart, $posEnd - $posStart + 5);

        $questPos = -1;
        if (strpos($stringCat, '[1,{"it":"Quest Items","itl":"8.182200000","ipt":"Misc","iptl":"8"}') > 0) {
            $questPos = 1;
        }

        $regexAdbMobItem =
            //{"id":100900197,"pct":0.290698,"mia":1,"mxa":1,"n":"4Kromede's Greatsword","l":35,"p":250150,"r":3,"i":1,"cat":1,"img":"ts_u001c_small.jpg","mvt":3,"is":[{"n":"Kromede the Corrupt","t":1,"id":212846},{"n":"Vile Judge Kromede","t":1,"id":214621}]},
            '@\{"id":(\d+),"pct":(-?\d+\.?\d*),"mia":(-?\d+\.?\d*),"mxa":(-?\d+\.?\d*),"n":"(\d)([^"]+)","l":(-?\d+),"p":(-?\d+),"r":(-?\d+),"i":(-?\d+),"cat":(-?\d+)@';
        if (preg_match_all($regexAdbMobItem, $adbMobString, $lootMatches)) {
            //echo "<br>Found Item";
            //print_r($lootMatches);

            $countLoot = count($lootMatches[1]);
            for ($j = 0; $j < $countLoot; $j++) {
                $itemId = $lootMatches[1][$j];
                //echo "\n<br>SN Item $itemId";
                $lootItem = $this->getLootItem($itemId);
                $lootItem->matchAdb($lootMatches, $j, $questPos);
                //echo print_r($lootItem,true);
            }
        }
    }

    /*
        Get Loot from YG
    */
    public function parseAionYGData ()
    {
        $mobArray = file("http://aion.yg.com/npc/blub?id=" . $this->id);
        $mobString = implode("", $mobArray);

        /*
        Level, Race and Rank of the NPC

        <div class="quickfacts"><div class="qfheader"><b>Quick Facts</b></div><div class="qfbody"><table width="100%" border="0" cellspacing="3" cellpadding="0"><tr><td>Level</td><td align="right">45&nbsp;</td></tr><tr><td>Race</td><td align="right">Beast&nbsp;</td></tr><tr><td>Rank</td><td align="right">Disciplined&nbsp;</td></tr><tr><td>XP</td>
        */
        $regexRace = "@<td>Race</td><td style=\"text-align: right;\">([^<]+)</td>@";
        if (preg_match($regexRace, $mobString, $matches)) {
            $this->race = $matches[1];
            $debug[] = "<br>race: " . $this->race;
        }
        $regexRank = "@<td>Rank</td><td style=\"text-align: right;\">([^<]+)</td>@";
        if (preg_match($regexRank, $mobString, $matches)) {
            $this->rank = $matches[1];
            $debug[] = "<br>Rank: " . $this->rank;
        }


        $posStart = strpos($mobString, 'var ItemDropsRawData =');
        $posEnd = strpos($mobString, ']];', $posStart);
        $string = substr($mobString, $posStart, $posEnd - $posStart);


        //name:0, level:1, type:2, subtype:3, link:4, icon:5, color:6, id:7, check:8, quantity:9, totalLooted:10, percentLooted:11
        /*
['Stone of Silene',37,'Craftls','Professig','/item/stone-of-silence?id=k12530','icon_item_stone07.png','aion_q4',152012530,'','1','520','21.07%']
['Kromede\'s Tome',35,'Weapons','Spellbook','/item/kromedes-tome?id=100600219','icon_item_bok_u01.png','aion_q5',100600219,'','0 - 1','-','Unconfirmed']
        */
        $regexItems = "@\['([^,]+)',(\d+),'([^']+)','([^']+)','([^']+)','([^']+)','([^']+)',(\d+),'([^']*)','([^']+)','([^']+)','(\d*.?\d*|Unconfirmed)%?'\]@";

        if (preg_match_all($regexItems, $string, $lootMatches)) {
            //echo "Matches ".print_r($lootMatches,true);
            $countLoot = count($lootMatches[1]);
            for ($j = 0; $j < $countLoot; $j++) {
                $itemID = $lootMatches[7 + 1][$j];
                $lootItem = $this->getLootItem($itemID);
                $lootItem->matchYG($lootMatches, $j);

            }
        } else {
            $debug[] = "No Match";
        }
        //echo "\nFinish parse YG";
    }


    /*
        Get Loot from YG of another monster with the same name
    */
    public function sameNameLootYg ($monsterId)
    {
        $mobArray = file("http://aion.yg.com/npc/blub?id=" . $monsterId);
        $mobString = implode("", $mobArray);

        $posStart = strpos($mobString, 'var ItemDropsRawData =');
        $posEnd = strpos($mobString, ']];', $posStart);
        $string = substr($mobString, $posStart, $posEnd - $posStart);

        //name:0, level:1, type:2, subtype:3, link:4, icon:5, color:6, id:7, check:8, quantity:9, totalLooted:10, percentLooted:11
        $regexItems = "@\['([^,]+)',(\d+),'([^']+)','([^']+)','([^']+)','([^']+)','([^']+)',(\d+),'([^']*)','([^']+)','([^']+)','(\d*.?\d*|Unconfirmed)%?'\]@";

        if (preg_match_all($regexItems, $string, $lootMatches)) {
            //echo "Matches ".print_r($lootMatches,true);
            $countLoot = count($lootMatches[1]);
            for ($j = 0; $j < $countLoot; $j++) {
                $itemID = $lootMatches[7 + 1][$j];
                $lootItem = $this->getLootItem($itemID);
                $lootItem->matchYG($lootMatches, $j);
            }
        } else {
            $debug[] = "No Match";
        }
        //echo "\nFinish parse YG";
    }

    public function getDataFromMonster (&$monster)
    {
        $this->name = $monster->name;
        $this->rank = $monster->rank;
        $this->lootItems = $monster->lootItems;
        $this->lootItemsJunk = $monster->lootItemsJunk;
        $this->lootItemsCommon = $monster->lootItemsCommon;
        $this->lootItemsSuperior = $monster->lootItemsSuperior;
        $this->lootItemsHeroic = $monster->lootItemsHeroic;
        $this->lootItemsFabled = $monster->lootItemsFabled;
        $this->lootItemsEternal = $monster->lootItemsEternal;
        $this->lootChances = $monster->lootChances;
        $this->elderItems = $monster->elderItems;
        $this->copiedFromMonster = $monster->id;
    }

    public function complementLoot ()
    {
        $elderLoot = false;

        foreach ($this->lootItems as $lootItem) {
            if (in_array($lootItem->id, $this->elderItems)) {
                $elderLoot = true;
                break;
            }
        }


        if ($elderLoot) {
            foreach ($this->elderItems as $elderItemId) {
                // If the item was not yet inserted it is now.
                $lootItem = $this->getLootItem($elderItemId);
                $lootItem->rarity = FABLED;

                $lootItem->name = "Elder";
                $prefix = substr($elderItemId, 0, 3);
                $fourth = substr($elderItemId, 3, 1);

                if (in_array($prefix, array(110, 111, 112, 113, 114))) {
                    switch ($fourth) {
                        case 1:
                            $lootItem->name .= " Cloth";
                            break;
                        case 3:
                            $lootItem->name .= " Leather";
                            break;
                        case 5:
                            $lootItem->name .= " Chain";
                            break;
                        case 6:
                            $lootItem->name .= " Plate";
                            break;
                    }
                }

                switch ($prefix) {
                    case 100:
                        $lootItem->name .= " One-handed Weapon";
                        break;
                    case 101:
                        $lootItem->name .= " Two-handed Weapon";
                        break;
                    case 115:
                        $lootItem->name .= " Shield";
                        break;
                    case 122:
                        $lootItem->name .= " Ring";
                        break;
                    case 110:
                        $lootItem->name .= " Chest";
                        break;
                    case 111:
                        $lootItem->name .= " Hands";
                        break;
                    case 112:
                        $lootItem->name .= " Shoulder";
                        break;
                    case 113:
                        $lootItem->name .= " Legs";
                        break;
                    case 114:
                        $lootItem->name .= " Feet";
                        break;
                }
                /*
                // Waffen
                100100231, 100200339, 100500242, 100600258, 100000318, 100900235, 100900567, 	// 1H
                101300232, 101500244, 101500583, 101700256, // 2H
                115000504,  // Shield
                122001040,  // Ring

                // Stoff, Leder, Kette, Platte
                110100575, 110300536, 110500514, 110600509,  	// Brust
                111100504, 111300506, 111500493, 111600480,		// Handschuhe
                112100468, 112300468, 112500456, 112600461,		// Schultern
                113100503, 113300519, 113500492, 113600476,		// Beine
                114100520, 114300533, 114500499, 114600466,  	// Schuhe
                */
                $lootItem->setSortString();
                $lootItem->setElder(true);
            }
        }
    }

    public function getSameNameYg ()
    {
        $array = file("http://aion.yg.com/npcs?s=" . str_replace(" ", "+", $this->name));
        $mobString = implode("", $array);

        $posStart = strpos($mobString, 'var NpcsGrid;');
        $posEnd = strpos($mobString, '</script>', $posStart);
        $string = substr($mobString, $posStart, $posEnd - $posStart);
        /*
        var NpcsGrid;var NpcsDataIndexes =  { name:0, link:1, icon:2, race:3, nameplate:4, rank:5, id:6, zones:{ Pos:7, Indexes: {id:0, zone:1, link:2}}, level:8, color:9} ;var NpcsRawData = [
        ['Brohum Hunter','/npc/brohum-hunter?id=214463','icon_emblem_monster_n_2_a.png','Demi-Humanoid','Normal','Disciplined',214463,[[220050000,'Brusthonin','/zone/brusthonin?id=220050000']],49,'aion_q2'],['Brohum Hunter','/npc/brohum-hunter?id=214464','icon_emblem_monster_n_2_a.png','Demi-Humanoid','Normal','Disciplined',214464,[[220050000,'Brusthonin','/zone/brusthonin?id=220050000']],50,'aion_q2']];
        */
        //                   1              2           3                     4         5         6       7
        $regexNpcs = "@\['([^']+)','/npc/([^\?]+)\?id=(\d+)','icon_[^']+','([^']+)','([^']+)','([^']+)',(\d+),\[@";
        //$regexNpcs = "@\['([^']+)','/npc/([^\?]+)\?id=(\d+)','icon_@";
        if (preg_match_all($regexNpcs, $string, $matches)) {
            //print_r($matches);
            //&#039;
            $countNPCs = count($matches[1]);
            $n = 0;
            for ($j = 0; $j < $countNPCs; $j++) {
                $npcId = $matches[3][$j];
                $npcName = trim($matches[1][$j]);
                $npcName = trim(str_replace("&#039;", "'", $npcName));

                $npcRace = $matches[4][$j];
                $npcRank = $matches[6][$j];
                //echo "if($npcName == trim($this->name) && ($npcId != $this->id) && ($npcRace == $this->race) && ($npcRank == $this->rank) )";
                if ($npcName != trim($this->name)) {
                    //echo "name falsch";
                } elseif ($npcRank != $this->rank) {
                    //echo "rank falsch ".$npcRank." == ".$this->rank;
                }
                if ($npcName == trim($this->name) && ($npcId != $this->id) && ($npcRank == $this->rank)) {
                    $n++;
                    $debug[] = "\n<br>Found Mob with same Name, ID: $npcId, $npcRace, $npcRank";
                    $this->sameNameMonsterIds[] = $npcId;
                    $this->sameNameLootAdb($npcId);
                    $this->sameNameLootYg($npcId);
                }
            }
        }
    }

    public function getLootItem ($itemID)
    {
        if (!isset($this->lootItems[$itemID])) {
            $this->lootItems[$itemID] = new LootItem($itemID, $this->id);
        }
        return $this->lootItems[$itemID];
    }

    public function sortLootByRarity ()
    {
        $all = $this->lootItems;

        foreach ($all as $id => $item) {
            switch ($item->rarity) {
                case ETERNAL:
                    $this->lootItemsEternal[$id] = $item;
                    break;
                case FABLED:
                    $this->lootItemsFabled[$id] = $item;
                    break;
                case HEROIC:
                    $this->lootItemsHeroic[$id] = $item;
                    break;
                case SUPERIOR:
                    $this->lootItemsSuperior[$id] = $item;
                    break;
                case COMMON:
                    $this->lootItemsCommon[$id] = $item;
                    break;
                case JUNK:
                    $this->lootItemsJunk[$id] = $item;
                    break;
            }
        }
    }

    public function calcPercentages ()
    {

        $sums = array();
        $counts = array();

        foreach ($this->lootItems as $id => $item) {
            $round = 0;

            if ($item->pctAdb > 0 && $item->pctYg > 0) {
                $round = round(($item->pctAdb + $item->pctYg) / 2, 8);
                //echo "\n<br>($item->pctAdb + $item->pctYg)/2 = $round";
            } elseif ($item->pctAdb > 0) {
                $round = $item->pctAdb;
            } elseif ($item->pctYg > 0) {
                $round = $item->pctYg;
            }


            $sums[$item->rarity] += ($round <= 0) ? $this->minLootChances[$item->rarity] : $round;
            $counts[$item->rarity]++;
            if ($item->rarity == FABLED) {
                $debug[] = "\n<br>[$item->id] $item->pctAdb, $item->pctYg => $round. Sum " . $sums[$item->rarity];
            }
        }


        for ($i = JUNK; $i <= ETERNAL; $i++) {
            $debug[] = "\n<br>LootChance $i " . $sums[$i] . "/" . $counts[$i];

            if ($sums[$i] < $this->minGroupChances[$i])
                $sums[$i] = $this->minGroupChances[$i];

            if (!$counts[$i]) {
                $debug[] = " => keine Items";
                continue;
            }
            $this->lootChances[$i] = max(round($sums[$i] / $counts[$i], 8), $this->minLootChances[$i]);
            $debug[] = "= " . $this->lootChances[$i];
        }
    }

    public function getLoots ()
    {
        $string = "";
        $items = $this->lootItems;


        $sortStrings = array();
        foreach ($this->lootItems as $lootItem) {
            if ($lootItem->id <= 0 || empty($lootItem->id))
                continue;
            $sortStrings[$lootItem->sortString] = $lootItem->id;
        }
        ksort($sortStrings);
        $sortStrings = array_reverse($sortStrings);

        $string .= "
			<table>
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>MinMax</th>
				<th>ADB</th>
				<th>YG</th>
				<th>Rarity</th>
				<th>QuestItem</th>
			</tr>";
        foreach ($sortStrings as $lootItemId) {
            $lootItem = $this->lootItems[$lootItemId];

            $string .= "
			<tr>
				<td>{$lootItem->id}</td>
				<td>{$lootItem->name}</td>
				<td>{$lootItem->getMinMax()}</td> 
				<td>{$lootItem->pctAdb}%</td> 
				<td>{$lootItem->pctYg}%</td> 
				<td>{$lootItem->rarity}</td> 
				<td>{$lootItem->questItem} ({$lootItem->subCat})</td>
			</tr>";
        }
        $string .= "</table>";
        //$string .= "\nItem [{$lootItem->id}] Name {$lootItem->name} {$lootItem->getMinMax()}x {$lootItem->getChance()}%";
        return $string;
    }

    public function getFinalLoot ()
    {

        $sortStrings = array();
        foreach ($this->lootItems as $lootItem) {
            if ($lootItem->id <= 0 || empty($lootItem->id))
                continue;
            $sortStrings[$lootItem->sortString] = $lootItem->id;
        }
        ksort($sortStrings);
        $sortStrings = array_reverse($sortStrings);

        $lootCount = count($sortStrings);
        if ($lootCount == 0)
            return false;

        $string = "\n\n-- [{$this->id}] {$this->name}";
        if ($this->copiedFromMonster > 0)
            $string .= "\n-- Copied loot from monster: " . $this->copiedFromMonster;
        elseif (count($this->sameNameMonsterIds) > 0)
            $string .= "\n-- Same name monsters included: (" . count($this->sameNameMonsterIds) . ") " . implode(", ", $this->sameNameMonsterIds);
        $string .= "\nDELETE FROM `droplist` WHERE `mobId` = {$this->id};";
        $string .= "\nINSERT INTO `droplist` (`mobId`, `itemId`, `min`, `max`, `chance`) VALUES ";

        $j = 0;
        foreach ($sortStrings as $lootItemId) {
            $j++;
            $lootItem = $this->lootItems[$lootItemId];

            if ($lootItemId == 182400001) {
                $chance = $this->minLootChances[KINAH];
            } elseif ($lootItem->questItem) {
                $chance = $this->minLootChances[QUEST];
            } elseif ($lootItem->elderItem) {
                $chance = $this->minLootChances[ELDER_SET];
            } else {
                $chance = $this->lootChances[$lootItem->rarity];
            }


            if ($lootItem->questItem) {
                $string .= "\n\t({$this->id}, $lootItemId, {$lootItem->min}, {$lootItem->max}, {$chance})";
                $string .= ($j == $lootCount) ? ";" : ",";
                $string .= " -- {$lootItem->rarity} {$lootItem->name} ";
                $string .= " (Quest Item)";
            } else {
                $string .= "\n\t({$this->id}, $lootItemId, {$lootItem->min}, {$lootItem->max}, {$chance})";
                $string .= ($j == $lootCount) ? ";" : ",";
                $string .= " -- {$lootItem->rarity} {$lootItem->name} ";
            }

        }
        return $string;
    }

    public function hasLoot ()
    {
        return ((count($this->lootItems) > 0));
    }

}

