<?php

namespace BedWars\MySQL;

use BedWars\BedWars;
use pocketmine\Player;
use pocketmine\Server;

class DoActionQuery extends AsyncQuery{

    private $action;

    public function __construct(BedWars $plugin, $player, $action){
        $this->action = $action;
        $this->player = $player;

        parent::__construct($plugin);
    }

    public function onQuery(array $data) {
        if ($data === null){
            return;
        }
        switch ($this->action){
            case 0:
                $this->addWin($this->player);
                break;
            case 1:
                $this->addLoss($this->player);
                break;
            case 2:
                $this->addKill($this->player);
                break;
            case 3:
                $this->addDeath($this->player);
                break;
            case 4:
                $this->addBed($this->player);
                break;
        }
    }


}