<?php

namespace BedWars\Task;

use BedWars\Arena\Arena;
use BedWars\BedWars;
use MTCore\MTCore;
use MTCore\MySQL\AsyncQuery;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

class QuickJoinTask extends AsyncQuery{

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

        /** @var BedWars $plugin */
        $plugin = $server->getPluginManager()->getPlugin("BedWars");

        if (!$plugin instanceof Plugin or !$plugin->isEnabled()){
            return;
        }

        if ($p instanceof Player && $p->isOnline()) {
            if ($rank == "hrac"){
                $p->sendMessage("§cOnly §bVIP ranked players §ccan use Quickjoin!\n§eYou can use join signs anyways");
                $p->sendTip("\n§cOnly §bVIP ranked players §ccan use Quickjoin!\n§eYou can use join signs anyways");
            }
            else {
                /** @var Arena $arena */
                $arena = $plugin->ins["bw-1"];
                $arena->joinToArena($p);
            }
        }
    }
}