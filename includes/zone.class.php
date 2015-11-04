<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "includes/common.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "includes/monster.class.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "includes/lootItem.class.php");

class Zone{
	
	public $id, $url, $name;
	public $monsterIds = array(), $monsters = array();
	public $sameNameMonster = array();
	public $sameNameLoot = array();
	
	public function Zone($id){
		$this->id = $id;
		$this->url = "spawns/".$id.".xml";
		
		/*
			Replace-RegEx: 	{"id":(\d+),"n":"(.+)","zt":\d,"l":"\d+( - \d+)?"},?;?
							$1 => "$2",
		*/
		
		$names = array(
			// Asmodae
			120010000 => "Pandämonium",
			220010000 => "Ishalgen",
			220030000 => "Altgard",
			220020000 => "Morheim",
			220050000 => "Brusthonin",
			220040000 => "Beluslan",
			
			// Elysea
			110010000 => "Sanctum",
			210010000 => "Poeta",
			210030000 => "Verteron",
			210020000 => "Eltnen",
			210060000 => "Theobomos",
			210040000 => "Heiron",
			
			// Abyss
			400010000 => "Reshanta",
			4000100002 => "Reshanta Part II",
			
			// Instanzen
			300030000 => "Nochsana-Ausbildungslager",
			300040000 => "Poeta der Finsternis",
			300050000 => "Abyss von Asteria",
			300060000 => "Schwefelbaum-Nest",
			300070000 => "Untergrundfestung der Ruinen von Roah",
			300080000 => "Kammer im linken Flügel",
			300090000 => "Kammer im rechten Flügel",
			300100000 => "Stahlharke",
			300110000 => "Dredgion",
			300120000 => "Kysis-Kammer",
			300130000 => "Miren-Kammer",
			300140000 => "Krotan-Kammer",
			310050000 => "Lepharisten-Geheimlabor",
			310090000 => "Indratu-Festung",
			310100000 => "Azoturan-Festung",
			310110000 => "Geheim-Labor von Theobomos",
			320050000 => "Himmelstempel von Arkanis (innen)",
			320080000 => "Draupnir-Höhle",
			320100000 => "Feuertempel",
			320110000 => "Alquimia-Labor",
			320130000 => "Adma-Festung",
		);
		$this->name = $names[$id];
		
		
	}
	
	public function getMonsters(){
		$xml = simplexml_load_file($this->url);
	
		foreach($xml->spawn as $spawn){
			$attr = $spawn->attributes();
			
			$this->monsterIds[] = intval($attr["npcid"]);
		}
		
	}

    /**
     *
     */
	public function getLoot(){

		echo "<pre>";
		foreach($this->monsterIds as $monsterId){
			//$monster = new Monster($monsterId);

			$this->monsters[$monsterId] = new Monster($monsterId);

            /**
             * @var Monster $monster
             */
			$monster = $this->monsters[$monsterId];
			//echo "ID:".$monster->id;
			
			if(in_array($monsterId, array_keys($this->sameNameMonster))){
				$oldMonsterId = $this->sameNameMonster[$monsterId];
				
				$monster->getDataFromMonster($this->monsters[$oldMonsterId]);
				
				$this->monsters[$monsterId]->lootItems = $this->sameNameLoot[$oldMonsterId];
				$monster->copiedFromMonster = $oldMonsterId;	
			}
			else{
				$monster->parseAionDatabaseData();
				$monster->parseAionYGData();
				
				$monster->getSameNameYg();
				//echo $monster->getLoots();	
				
				if(count($monster->sameNameMonsterIds) > 0){
					foreach($monster->sameNameMonsterIds as $otherMonsterId){
						$this->sameNameMonster[$otherMonsterId] = $monster->id;
					}
					$this->sameNameLoot[$monsterId] = array();
					$this->sameNameLoot[$monsterId] = $monster->lootItems;
				}
				
				$monster->calcPercentages();
				$monster->complementLoot();
			}
			
			echo $monster->getFinalLoot();

		}
		
		echo "\n/*
	=== {$this->name} ===

		Zone-ID: {$this->id}
		Entries: ".count($this->monsters)."
*/";
		//echo "\n".$lootString;
		echo "</pre>";
		
	}
		
}
?>