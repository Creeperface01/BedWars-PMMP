<?php

namespace BedWars\MySQL;

use BedWars\BedWars;
use BedWars\MySQLManager;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

abstract class AsyncQuery extends AsyncTask{

    const MYSQLI_KEY = "BedWars.MySQL";

    protected $table = "bedwars";

    protected $player;

    public function __construct(BedWars $plugin){
        $plugin->getServer()->getScheduler()->scheduleAsyncTask($this);
    }

    public function onRun(){
        $data = $this->getPlayer($this->player, $this->table);

        $this->onQuery(is_array($data) ? $data : []);
    }

    protected function onQuery(array $data){

    }

    public function onCompletion(Server $server){

    }

    protected function getMysqli(){
        $mysqli = $this->getFromThreadStore(self::MYSQLI_KEY);

        if($mysqli !== null){
            return $mysqli;
        }

        $mysqli = MySQLManager::getMysqliConnection();
        $this->saveToThreadStore(self::MYSQLI_KEY, $mysqli);
        return $mysqli;
    }

    public function getPlayer($player, $table = "bedwars"){

        $name = $this->getMysqli()->escape_string(trim(strtolower($player)));

        $result = $this->getMysqli()->query
        (
            "SELECT * FROM ".$table." WHERE name = '" . $name ."'"
        );
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and $data["name"] === trim(strtolower($player))){
                unset($data["name"]);
                return $data;
            }
        }
        return null;
    }

    public function registerPlayer($player){
        $database = $this->getMysqli();
        $name = trim(strtolower($player));
        $data =
            [
                "name" => $name,
                "kills" => 0,
                "deaths" => 0,
                "wins" => 0,
                "losses" => 0,
                "beds" => 0
            ];

        $database->query
        (
            "INSERT INTO bedwars (
            name, kills, deaths, wins, losses, beds)
            VALUES
            ('".$database->escape_string($name)."', '".$data["kills"]."', '".$data["deaths"]."', '".$data["wins"]."', '".$data["losses"]."', '".$data["beds"]."')"
        );
    }

    public function isRegistered($player){
        if ($this->getPlayer($player) !== null){
            return true;
        }
        return false;
    }

    public function addKill($player, $kills = 1){
        $database = $this->getMysqli();
        $database->query
        (
            "UPDATE bedwars SET kills = kills+'".$kills."' WHERE name = '".$database->escape_string(trim(strtolower($player)))."'"
        );
    }

    public function addDeath($player, $deaths = 1){
        $database = $this->getMysqli();
        $database->query
        (
            "UPDATE bedwars SET deaths = deaths+'".$deaths."' WHERE name = '".$database->escape_string(trim(strtolower($player)))."'"
        );
    }

    public function addWin($player, $kills = 1){
        $database = $this->getMysqli();
        $database->query
        (
            "UPDATE bedwars SET wins = wins+'".$kills."' WHERE name = '".$database->escape_string(trim(strtolower($player)))."'"
        );
    }

    public function addLoss($player, $kills = 1){
        $database = $this->getMysqli();
        $database->query
        (
            "UPDATE bedwars SET losses = losses+'".$kills."' WHERE name = '".$database->escape_string(trim(strtolower($player)))."'"
        );
    }

    public function addBed($player, $kills = 1){
        $database = $this->getMysqli();
        $database->query
        (
            "UPDATE bedwars SET beds = beds+'".$kills."' WHERE name = '".$database->escape_string(trim(strtolower($player)))."'"
        );
    }

}