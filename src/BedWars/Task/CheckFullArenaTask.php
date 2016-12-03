<?php

namespace BedWars\Task;

use BedWars\BedWars;
use MTCore\MySQL\AsyncQuery;
use pocketmine\Player;
use pocketmine\Server;

class CheckFullArenaTask extends AsyncQuery{

    public function __construct(BedWars $plugin, $player) {
        $this->player = $player;

        parent::__construct($plugin);
    }

    public function onQuery(array $data) {
        $this->setResult($data["rank"]);
    }

    public function onCompletion(Server $server) {

        $p = $server->getPlayer($this->player);
        if (!$p instanceof Player or !$p->isOnline()){
            return;
        }

        if ($this->getResult() == "hrac"){
            $p->sendMessage(BedWars::getPrefix()."§cArena is full");
            $p->setGamemode(2);
        }

    }

}