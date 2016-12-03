<?php

namespace BedWars\MySQL;

use BedWars\BedWars;
use pocketmine\Server;

class JoinTask extends AsyncQuery{

    public $isNew;

    public function __construct(BedWars $plugin, $player) {
        $this->player = $player;
        $this->isNew = false;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        if (empty($data)){
            $this->registerPlayer($this->player);
            $this->isNew = true;
        }
    }

    public function onCompletion(Server $server) {
        if ($this->isNew) $server->getLogger()->info(BedWars::getPrefix()."§aRegistered new player §e".$this->player);
    }

}