<?php

namespace BedWars\Task;

use MTCore\MTCore;
use MTCore\MySQL\AsyncQuery;
use MTCore\MySQLManager;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class StartArenaTask extends AsyncQuery{

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
            if ($rank != "hrac"){
                $p->getInventory()->addItem(Item::get(336, 0, 16)->setCustomName("§r§6Bronze"));
                $p->getInventory()->addItem(Item::get(265, 0, 4));
                $p->getInventory()->addItem(Item::get(266, 0, 1));
            }
        }
    }

}