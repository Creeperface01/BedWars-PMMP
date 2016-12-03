<?php

namespace BedWars\Task;

use MTCore\MTCore;
use MTCore\MySQLManager;
use MTCore\MySQL\AsyncQuery;

class ShopManagerBuy extends AsyncQuery{

    private $money;

    public function __construct(MTCore $plugin, $player, $money){
        $this->player = $player;
        $this->money = $money;
        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        if($data != null){
            $this->addTokens($this->player, $this->money);
        }
    }

}