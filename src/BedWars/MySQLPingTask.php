<?php

namespace BedWars;

use pocketmine\scheduler\Task;

class MySQLPingTask extends Task {

    public function onRun($currentTick){
        MySQLManager::getDatabase()->ping();
    }

}