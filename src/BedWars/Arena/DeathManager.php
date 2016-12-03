<?php

namespace BedWars\Arena;

use BedWars\MySQL\DoActionQuery;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\entity\Projectile;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DeathManager{
    
    public $plugin;
    
    public function __construct(Arena $plugin){
        $this->plugin = $plugin;
    }
    
    public function onDeath(PlayerDeathEvent $e){
        $p = $e->getEntity();
        $lastDmg = $p->getLastDamageCause();
        $pColor = $this->plugin->getTeamColor($this->plugin->getPlayerTeam($p));
        $dColor = "";
        $escape = false;
        if($lastDmg instanceof EntityDamageEvent){
            if($lastDmg instanceof EntityDamageByEntityEvent){
                $killer = $lastDmg->getDamager();
                if($killer instanceof Player){
                    $dColor = $this->plugin->getTeamColor($this->plugin->getPlayerTeam($killer));
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was slain by ".$dColor."{$killer->getName()}");
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                }
                return;
            }
            if($lastDmg instanceof EntityDamageByChildEntityEvent){
                $arrow = $lastDmg->getChild();
                /** @var Player $killer */
                $killer = $lastDmg->getDamager();
                if($arrow instanceof Projectile){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was shot by ".$this->plugin->getTeamColor($this->plugin->getPlayerTeam($killer)).$killer->getName());
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                }
                return;
            }
            /** @var Player $killer */
            $killer = "";
            if(isset($this->plugin->players[strtolower($p->getName())]['tick']) && $this->plugin->players[strtolower($p->getName())]['tick'] >= $this->plugin->plugin->getServer()->getTick()){
                $escape = true;
                $dColor = $this->plugin->players[strtolower($p->getName())]['killer_color'];
                $killer = $this->plugin->players[strtolower($p->getName())]['killer'];
            }
        switch($lastDmg->getCause()){
            case 0:
                if($escape === true){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." walked into a cactus while trying to escape ".$dColor.$killer);
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                    return;
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was pricked to death");
                break;
            case 3:
                if($escape === true){
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." suffocated in a wall");
                break;
            case 4:
                if($escape === true){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was doomed to fall by ".$dColor.$killer);
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                    break;
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." fell from high place");
                break;
            case 5:
                if($escape === true){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." walked into a fire whilst fighting ".$dColor.$killer);
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                    break;
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." went up in flames");
                break;
            case 6:
                if($escape === true){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was burnt to a crisp whilst fighting ".$dColor.$killer);
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                    break;
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." burned to death");
                break;
            case 7:
                if($escape === true){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." tried to swim in lava while trying to escape ".$dColor.$killer);
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                    break;
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." tried to swim in lava");
                break;
            case 8:
                if($escape === true){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." drowned whilst trying to escape ".$dColor.$killer);
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                    break;
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." drowned");
                break;
            case 9:
                if($escape === true){
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." blew up");
                break;
            case 10:
                if($escape === true){
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." blew up");
                break;
            case 11:
                if($escape === true){
                    $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." was doomed to fall by ".$dColor.$killer);
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                    break;
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." fell out of the world");
                break;
            case 12:
                if($escape === true){
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." died");
                break;
            case 13:
                if($escape === true){
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." died");
                break;
            case 14:
                if($escape === true){
                    new DoActionQuery($this->plugin->plugin, $killer->getName(), 2);
                }
                $this->plugin->messageAllPlayers($pColor."{$p->getName()}".TextFormat::GRAY." died");
                break;
        }
        }
    }
}