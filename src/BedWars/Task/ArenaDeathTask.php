<?php

namespace BedWars\Task;

use BedWars\BedWars;
use MTCore\MTCore;
use MTCore\MySQL\AsyncQuery;
use MTCore\MySQLManager;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ArenaDeathTask extends AsyncQuery{

    public function __construct(MTCore $plugin, $player){
        $this->player = $player;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        $this->setResult([$data["rank"]]);
    }

    public function onCompletion(Server $server){
        $p = $server->getPlayer($this->player);
        $rank = $this->getResult()[0];

        if ($p instanceof Player && $p->isOnline()) {
            if ($rank == "hrac"){
                $p->getInventory()->clearAll();
            }
            else {
                $p->sendMessage("§6You didn't lose your items due to §bVIP Respawn!");
            }
        }
    }

}