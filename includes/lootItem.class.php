<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/common.php");

/**
 * Class LootItem
 */
class LootItem
{
    public $id;
    public $mobID;
    public $name;
    public $sortString;    // Combi aus Rarity und Name

    public $chance = -1;
    public $pctAdb = -1;
    public $pctYg = -1;

    public $min = 1;
    public $max = 1;
    public $rarity = 0;
    public $cat = "", $subCat = "";
    public $questItem = false, $elderItem = false;

    /**
     * @param $itemID
     * @param $mobID
     */
    public function LootItem ($itemID, $mobID)
    {
        $this->id = $itemID;
        $this->mobID = $mobID;
        //echo "\nNew Item: ".print_r($this,true);
    }

    /**
     * @param $matches
     * @param $i
     * @param $questPos
     */
    public function matchAdb ($matches, $i, $questPos)
    {
        //echo "\nI $i ".$matches[1][$i];
        /*
                       1                2                  3                  4               5    6            7            8          9          10            11
            '@\{"id":(\d+),"pct":(-?\d+\.?\d*),"mia":(-?\d+\.?\d*),"mxa":(-?\d+\.?\d*),"n":"(\d)([^"]+)","l":(-?\d+),"p":(-?\d+),"r":(-?\d+),"i":(-?\d+),"cat":(-?\d+)@';
        */
        $this->setId($matches[1][$i]);

        $newPct = (is_numeric($matches[2][$i]) && $matches[2][$i] > 0)
            ? $matches[2][$i]
            : 0;
        if ($this->pctAdb > 0) {
            // Adb has already entered a value
            $newPct = round(($this->pctAdb + $newPct) / 2, 6);
        }
        $this->pctAdb = $newPct;

        $this->addMin($matches[3][$i]);
        $this->addMax($matches[4][$i]);

        $this->setRarity($matches[5][$i], "adb");
        $this->setName($matches[6][$i]);
        $this->setSortString();

        if ($questPos == $matches[11][$i]) {
            $this->questItem = true;
        }

    }

    /**
     * @param $matches
     * @param $i
     */
    public function matchYG ($matches, $i)
    {
        //name:0, level:1, type:2, subtype:3, link:4, icon:5, color:6, id:7, check:8, quantity:9, totalLooted:10, percentLooted:11
        /*if($matches[7+1][$i] == 100600219)
            echo "\nID $i, ".$matches[7+1][$i];
        */

        $this->setId($matches[7 + 1][$i]);
        $this->setName($matches[0 + 1][$i]);
        $this->setRarity(str_replace("aion_q", "", $matches[6 + 1][$i]), "yg");

        $pct = $matches[11 + 1][$i];
        if (!is_numeric($pct))
            $pct = 0;
        if ($this->pctYg > 0) {
            $pct = round(($this->pctYg + $pct) / 2, 6);
        }
        $this->pctYg = $pct * 1.0;

        $this->cat = $matches[2 + 1][$i];
        $this->subCat = trim($matches[3 + 1][$i]);

        if ($this->subCat == "Quest Item") {
            $this->questItem = true;
        }

        $count = $matches[9 + 1][$i];
        if (substr_count($count, " - ") > 0) {
            $e = explode(" - ", $count);
            $this->addMin($e[0]);
            $this->addMax($e[1]);
        } else {
            $this->addMin($count);
            $this->addMax($count);
        }
        $this->setSortString();

    }

    /**
     * @return string
     */
    public function getChance ()
    {
        $i = 0;
        if ($this->pctAdb > 0) {
            ++$i;
        }
        if ($this->pctYg > 0) {
            ++$i;
        }
        return "\t\tA: " . print_r($this->pctAdb, true) . ", Y: " . print_r($this->pctYg, true);
    }

    /**
     * @return int|string
     */
    public function getMinMax ()
    {
        return ($this->max > $this->min) ? $this->min . "-" . $this->max : $this->min;
    }

    /**
     * @param $max
     */
    protected function addMax ($max)
    {
        if ($this->max == false)
            $this->max = $max;
        else
            $this->max = max($this->max, $max);
    }

    /**
     * @param $min
     */
    protected function addMin ($min)
    {
        if ($this->min == false)
            $this->min = $min;
        else
            $this->min = min($this->min, $min);
    }

    /**
     * @param $cat
     * @return string|bool
     */
    protected function getAdbCat ($cat)
    {
        switch ($cat) {
            case 1:
                return "GREATSWORD";
                break;
            case 2:
                return "POWER_SHARD";
                break;
            case 3:
                return "BOOK";
                break;
            case 4:
                return "BOW";
                break;
            case 5:
                return "HAT";
                break;
            case 6:
                return "DAGGER";
                break;
            case 7:
                return "MAT";
                break;            // Handwerksmaterial
            case 8:
                return "MACE";
                break;
            case 9:
                return "MANASTONE";
                break;
            case 10:
                return "NECKLACE";
                break;
            case 11:
                return "JEWEL";
                break;
            case 12:
                return "SPEAR";
                break;
            case 13:
                return "SHOULDER";
                break;
            case 14:
                return "STAFF";
                break;
            case 15:
                return "SWORD";
                break;
            case 16:
                return "KINAH";
                break;
            case 17:
                return "POTION";
                break;
            case 18:
                return "RING";
                break;
            case 19:
                return "JUNK";
                break;
            case 20:
                return "STIGMA";
                break;
            case 21:
                return "LEGS";
                break;
            case 22:
                return "BELT";
                break;
            case 23:
                return "SHOES";
                break;
            default:
                return false;
        }
    }

    protected function setCat ($cat)
    {
        if (!isset($this->cat))
            $this->cat = $cat;
    }

    protected function setId ($id)
    {
        if (!isset($this->id))
            $this->id = $id;
    }

    public function setElder ($value)
    {
        $this->elderItem = $value;
    }

    protected function setName ($name)
    {
        if (!isset($this->name))
            $this->name = $name;
    }

    /**
     * @param $rarity
     * @param string $type
     */
    protected function setRarity ($rarity, $type = "adb")
    {
        if ($type == "adb") {
            $array = array(
                3 => ETERNAL,
                4 => FABLED,        // orange
                5 => HEROIC,        // blue
                6 => SUPERIOR,    // green
                7 => COMMON,
                8 => JUNK,);
        } else {
            $array = array(
                6 => ETERNAL,
                5 => FABLED,        // orange
                4 => HEROIC,        // blue
                3 => SUPERIOR,    // green
                2 => COMMON,
                1 => JUNK,);
        }
        $this->rarity = $array[$rarity];
    }

    public function setSortString ()
    {
        if ($this->rarity == 0 || empty($this->name)) {
            return;
        }
        $this->sortString = $this->rarity . $this->id;
    }


}

