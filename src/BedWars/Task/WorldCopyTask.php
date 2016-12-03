<?php

namespace BedWars\Task;

use BedWars\BedWars;
use BedWars\Arena\Arena;
use BedWars\Arena\WorldManager;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class WorldCopyTask extends AsyncTask{

    private $map;
    private $dataPath;

    public function __construct(BedWars $plugin, $dataPath, $map){
        $this->dataPath = $dataPath;
        $this->map = $map;

        $plugin->getServer()->getScheduler()->scheduleAsyncTask($this);
    }

    public function onRun(){
        WorldManager::resetWorld($this->map, $this->dataPath);
    }

    public function onCompletion(Server $server){
        $server->loadLevel($this->map);
    }

}