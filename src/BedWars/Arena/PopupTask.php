<?php

namespace BedWars\Arena;

use pocketmine\level\sound\FizzSound;
use pocketmine\scheduler\Task;
use BedWars\Arena\Arena;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\Player;

class PopupTask extends Task{
    
    public $plugin;
    public $task;
    public $ending = 0;
    
    public function __construct(Arena $plugin){
        $this->plugin = $plugin;
    }
    
    public function onRun($currentTick){
        if($this->plugin->game === 1 && $this->plugin->ending !== true){
            $this->sendStatus();
        }
        if($this->plugin->ending === true && $this->plugin->game === 1){
            if($this->ending === 30){
                $this->plugin->ending = false;
                $this->plugin->stopGame();
                $this->ending = 0;
                return;
            }
            $this->ending++;
            $this->sendEnding();
        }
        if($this->plugin->game === 0){
            $this->sendVotes();
        }
    }
    
    public function sendVotes(){
        /** @var Player $p */
        foreach($this->plugin->getArenaPlayers() as $p){
            $vm = $this->plugin->votingManager;
            $votes = [$vm->currentTable[0], $vm->currentTable[1], $vm->currentTable[2]];
            $p->sendTip("                                                   §8Voting §f| §6/vote <name>"
                    . "\n                                                 §b[1] §8$votes[0] §c» §a{$vm->stats[1]} Votes"
                    . "\n                                                 §b[2] §8$votes[1] §c» §a{$vm->stats[2]} Votes"
                    . "\n                                                 §b[3] §8$votes[2] §c» §a{$vm->stats[3]} Votes");
        }                        //    |
    }
    
    public function sendStatus(){
        /** @var Player $p */
        foreach(array_merge($this->plugin->getArenaPlayers(), $this->plugin->spectators) as $p){
            $p->sendTip($this->plugin->getGameStatus());
        }
    }
    
    public function sendEnding(){
        $team = $this->plugin->winnerTeam;
        $name = $this->plugin->getTeamColor($team).$this->plugin->getTeamName($team);
        /** @var Player $p */
        foreach(array_merge($this->plugin->spectators, $this->plugin->getArenaPlayers()) as $p){
            $p->sendTip(TextFormat::GRAY."                    ================[ ".TextFormat::DARK_AQUA."Progress".TextFormat::GRAY." ]================\n"
                                                  . "                               ".TextFormat::BOLD.$name.TextFormat::GREEN." team won the game\n"
                        .TextFormat::GRAY         . "======================================================");
            $this->plugin->level->addSound(new FizzSound(new Vector3($p->x, $p->y, $p->z)));
        }
    }
}