<?php

namespace BedWars\Arena;

use BedWars\BedWars;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use BedWars\Arena\Arena;
use pocketmine\tile\Chest;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class ArenaSchedule extends Task{
    
    public $gameTime = 3600;
    public $startTime = 120;
    public $isShortered = false;
    public $sign = 0;
    public $plugin;
    public $drop = 0;
    
    public function __construct(Arena $plugin){
        $this->plugin = $plugin;
    }
    
    public function onRun($currentTick){
        if ($this->plugin->starting !== true and $this->plugin->game === 0){
            $this->waiting();
        }
        if($this->plugin->starting === true){
            $this->starting();
        }
        if($this->plugin->game === 1 &&$this->plugin->ending === false){
            $this->game();
        }
        if($this->sign === 2){
            $this->updateMainSign();
            //$this->updateChest();
            if($this->plugin->game === 0){
                $this->updateTeamSigns();
                $this->plugin->checkLobby();
            }
            $this->sign = 0;
        }
        $this->sign++;
    }
    
    public function updateMainSign(){
        $tile = $this->plugin->plugin->level->getTile($this->plugin->mainData['sign']);
        $map = substr($this->plugin->map, 0, strlen($this->plugin->map) - 5);
        if($this->plugin->game === 0){
            $map = "---";
        }
        if($tile instanceof Sign){
            $game = "§aLobby";
            if($this->plugin->game === 1){
                $game = "§cIngame";
            }elseif(!$this->plugin->canJoin){
                $game = TextFormat::RED.TextFormat::BOLD."RESTART";
            }
            $tile->setText(TextFormat::DARK_RED."■".$this->plugin->id."■", TextFormat::BLACK.count($this->plugin->getArenaPlayers())."/16", $game, TextFormat::BOLD.TextFormat::BLACK.$map);
        }
    }
    
    public function updateTeamSigns(){
        /** @var Sign $blue */
        $blue = $this->plugin->plugin->level->getTile($this->plugin->mainData['1sign']);
        /** @var Sign $red */
        $red = $this->plugin->plugin->level->getTile($this->plugin->mainData['2sign']);
        /** @var Sign $yellow */
        $yellow = $this->plugin->plugin->level->getTile($this->plugin->mainData['3sign']);
        /** @var Sign $green */
        $green = $this->plugin->plugin->level->getTile($this->plugin->mainData['4sign']);
        
        $blue->setText("", TextFormat::BOLD.TextFormat::BLUE."[BLUE]", TextFormat::GRAY.count($this->plugin->getTeamPlayers(1))." players", "");
        $red->setText("", TextFormat::BOLD.TextFormat::RED."[RED]", TextFormat::GRAY.count($this->plugin->getTeamPlayers(2))." players", "");
        $yellow->setText("", TextFormat::BOLD.TextFormat::YELLOW."[YELLOW]", TextFormat::GRAY.count($this->plugin->getTeamPlayers(3))." players", "");
        $green->setText("", TextFormat::BOLD.TextFormat::GREEN."[GREEN]", TextFormat::GRAY.count($this->plugin->getTeamPlayers(4))." players", "");

    }

    public function waiting(){
        $count = \count($this->plugin->getArenaPlayers());
        foreach ($this->plugin->getArenaPlayers() as $p){
            $p->sendPopup("§eWaiting for players... §b(§c".$count."/16");
        }
    }
    
    public function starting(){
        if($this->startTime === 5){
                $this->plugin->selectMap();
            }
            if($this->startTime === 0){
                $this->plugin->startGame();
                $this->startTime = 120;
                return;
            }
            foreach($this->plugin->getArenaPlayers() as $p){
                $p->experience = 0;
                $p->explevel = ($this->startTime);
                if ($this->isShortered){
                    $p->setExpBarPercent($this->startTime/10);
                }
                else {
                    $p->setExpBarPercent($this->startTime/120);
                }
            }
            $this->startTime--;
    }
    
    public function game(){
        $this->gameTime--;
        switch($this->gameTime){
            case 900:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 15 minutes");
                break;
            case 600:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 10 minutes");
                break;
            case 300:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 5 minutes");
                break;
            case 240:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 4 minutes");
                break;
            case 180:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 3 minutes");
                break;
            case 120:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 2 minutes");
                break;
            case 60:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 1 minutes");
                break;
            case 5:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 5 seconds");
                break;
            case 4:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 4 seconds");
                break;
            case 3:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 3 seconds");
                break;
            case 2:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 2 seconds");
                break;
            case 1:
                $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ends in 1 seconds");
                break;
        }
        if($this->gameTime <= 0){
            $this->plugin->messageAllPlayers(BedWars::getPrefix().TextFormat::RED.TextFormat::BOLD."Game ended!");
            $this->plugin->stopGame();
            return;
        }
        $this->plugin->checkAlive();
            $this->plugin->dropBronze();
            if($this->drop === 0){
                $this->plugin->dropIron();
                $this->plugin->dropGold();
            }
            $this->drop++;
            if($this->drop === 15){
                $this->plugin->dropIron();
            }
            if($this->drop === 30){
                $this->plugin->dropIron();
            }
            if($this->drop === 45){
                $this->drop = 0;
            }
    }

}