<?php

namespace BedWars\MySQL;

use BedWars\BedWars;
use pocketmine\Player;
use pocketmine\Server;

class ShowStatsQuery extends AsyncQuery{

    private $isSame;

    public function __construct(BedWars $plugin, $player, $isSame = true){
        $this->player = $player;
        $this->isSame = $isSame;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        $this->setResult($data);
    }

    public function onCompletion(Server $server) {

        $data = $this->getResult();

        $what = $this->isSame ? $this->player : $this->isSame;
        $p = $server->getPlayer($what);

        if (!$p instanceof Player || !$p->isOnline()){
            return;
        }

        $which = $this->isSame ? "Your" : $this->player;

        $p->sendMessage(
          "§7--------------------\n".
          "§9> $which's §l§fBed§4Wars§r§9 stats §9<\n".
          "§2Kills: §5".$data["kills"]."\n".
          "§2Deaths: §5".$data["deaths"]."\n".
          "§2Wins: §5".$data["wins"]."\n".
          "§2Losses: §5".$data["losses"]."\n".
          "§2Beds destroyed: §5".$data["beds"]."\n".
          "§7--------------------");

    }
}