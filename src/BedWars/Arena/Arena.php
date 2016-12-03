<?php

namespace BedWars\Arena;

use BedWars\MySQL\DoActionQuery;
use BedWars\Task\ArenaDeathTask;
use BedWars\Task\StartArenaTask;
use BedWars\Task\WorldCopyTask;
use BedWars\Task\CheckFullArenaTask;
use MTCore\MySQL\AddCoinsQuery;
use MTCore\MySQLManager;
use pocketmine\block\Snow;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Arrow;
use pocketmine\entity\Entity;
use pocketmine\entity\Snowball;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\block\Block;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\level\Level;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\entity\Villager;
use BedWars\BedWars;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\entity\Effect;
use pocketmine\level\particle\LargeExplodeParticle;

class Arena implements Listener{

    public $id;
    public $plugin;
    public $data;
    public $mainData;

    /** @var  Level $level */
    public $level;
    public $players = [];
    
    public $teams = [0 => ['name' => "lobby", 'color' => "§5", 'players' => []] ,1 => ['bed' => true, 'name' => "blue", 'color' => "§9", 'alive' => true, 'dec' => 3361970,'players' => []], 2 => ['bed' => true, 'name' => "red", 'color' => "§c", 'alive' => true, 'dec' => 10040115,'players' => []], 3 => ['bed' => true, 'name' => "yellow", 'color' => "§e", 'alive' => true, 'dec' => 15066419, 'players' => []], 4 => ['bed' => true, 'name' => "green", 'color' => "§a", 'alive' => true, 'dec' => 6717235, 'players' => []]];

    /** @var Player[] */
    public $ingamep = [];

    /** @var Player[] */
    public $lobbyp = [];
    
    public $game = 0;
    
    public $starting = false;
    public $ending = false;

    /** @var ArenaSchedule $task */
    public $task;
    /** @var  $popupTask */
    public $popupTask;
    /** @var VotingManager $votingManager */
    public $votingManager;
    /** @var ShopManager $shopManager */
    public $shopManager;
    /** @var DeathManager $deathManager */
    public $deathManager;

    public $map = "Voting";
    
    public $winnerTeam;
    
    public $mtcore;

    /** @var Player[] */
    public $spectators = [];

    public $canJoin = true;
    
    public function __construct($id, BedWars $plugin){
        $this->id = $id;
        $this->plugin = $plugin;
        $this->mainData = $this->plugin->arenas[$this->id];
        $this->enableScheduler();
        $this->votingManager = new VotingManager($this);
        $this->shopManager = new ShopManager($this);
        $this->deathManager = new DeathManager($this);
        $this->votingManager->createVoteTable();
        $this->mtcore = $this->plugin->mtcore;
    }
    
    public function enableScheduler(){
        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($this->task = new ArenaSchedule($this), 20);
        $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask($this->popupTask = new PopupTask($this), 10);
    }
    
    public function onArrow(InventoryPickupArrowEvent $e){
      if ($e->getArrow() instanceof Arrow){
        $e->setCancelled(true);
      }
    }
    
    public function onBlockTouch(PlayerInteractEvent $e){
        $b = $e->getBlock();
        $p = $e->getPlayer();
        if($e->isCancelled()){
            return;
        }
        if($this->shopManager->isShopping($p)){
            $e->setCancelled();
            $this->shopManager->unsetPlayer($p);
            return;
        }
        if(($is = $this->isJoinSign($b)) !== false){
            $this->addToTeam($p, $is);
        }
        if($b->x == $this->mainData['sign']->x && $b->y == $this->mainData['sign']->y && $b->z == $this->mainData['sign']->z){
            $this->joinToArena($p);
        }
        /*if($this->game === 1 && $this->inArena($p)){
            if($this->isEnderChest($b)){
                $e->setCancelled();
                $p->addWindow($this->getTeamEnderChest($this->getPlayerTeam($p))->getInventory());
                return;
            }
        }*/
        /*if($e->getAction() === $e::RIGHT_CLICK_AIR && $this->isSpectator($p) && ($p->getInventory()->getItemInHand()->getId()) === Item::CLOCK){
            $item = $p->getInventory()->getItemInHand();
            $count = count($this->getPlayersInTeam());
            $find = false;
            foreach(array_keys($this->getPlayersInTeam()) as $id => $name){
                if($name == $item->getCustomName()){
                    if($id >= $count){
                        $next = array_keys($this->getPlayersInTeam())[0];
                        $nextp = $this->getPlayersInTeam()[$next];
                        $item->setCustomName($next);
                        $p->teleport(new Vector3($nextp->x, $nextp->y, $nextp->z));
                        return;
                    }
                    $find = true;
                    continue;
                }
                if($find === true){
                    $nextP = $this->getPlayersInTeam()[$name];
                    $item->setCustomName($name);
                    $p->teleport(new Vector3($nextP->x, $nextP->y, $nextP->z));
                    return;
                }
            }
        }*/
    }
    
    public function messageAlivePlayers($msg){
        foreach($this->ingamep as $p){
            if($p->isOnline()){
                $p->sendMessage($this->plugin->getPrefix().$msg);
            }
        }
        $this->plugin->getServer()->getLogger()->info($this->plugin->getPrefix().$msg);
    }
    
    public function joinToArena(Player $p){
            if($this->game === 1){
                $p->sendMessage($this->plugin->getPrefix().TextFormat::BLUE."Joining as spectator...");
                $this->setSpectator($p);
                return;
            }
            if(count($this->getArenaPlayers()) >= 16 && !$p->isOp()){
                new CheckFullArenaTask($this->plugin, $p->getName());
            }
            if ($p->getGamemode() === 2){
                return;
            }
            if(!$this->canJoin){
                return;
            }
            $this->players[strtolower($p->getName())]['team'] = 0;
            $this->lobbyp[strtolower($p->getName())] = $p;
            $this->teams[0]['players'][strtolower($p->getName())] = $p;
            $p->setDisplayName("§5[Lobby]  ".$this->mtcore->getDisplayRank($p)." ".$p->getName()."§f");
            $p->setNameTag($p->getName());
            $p->sendMessage($this->plugin->getPrefix().TextFormat::GREEN."Joining to $this->id...");
            $p->teleport($this->mainData['lobby']);
            $p->setSpawn($this->mainData['lobby']);
            $inv = $p->getInventory();
            $inv->clearAll();
            $inv->setItem(0, Item::get(159, 11, 1)->setCustomName("§r§eJoin §9Blue"));
            $inv->setItem(1, Item::get(159, 14, 1)->setCustomName("§r§eJoin §4Red"));
            $inv->setItem(2, Item::get(159, 4, 1)->setCustomName("§r§eJoin §eYellow"));
            $inv->setItem(3, Item::get(159, 5, 1)->setCustomName("§r§eJoin §aGreen"));
            for($i = 0; $i < 9; $i++){
                $inv->setHotbarSlotIndex(35, $i);
            }
            $inv->sendContents($p);
            $this->mtcore->unsetLobby($p);
            $this->checkLobby();
            return;
    }
    
    public function leaveArena(Player $p){
        if($this->shopManager->isShopping($p)){
            $this->shopManager->unsetPlayer($p);
        }
        if($this->getPlayerTeam($p) !== 0 && $this->getPlayerTeam($p) !== false){
            $this->messageTeam($this->getTeamColor($this->getPlayerTeam($p)).$p->getName().TextFormat::GRAY." left game", null, $this->getPlayerTeam($p));
            new DoActionQuery($this->plugin, $p->getName(), 1);
            $p->sendMessage($this->plugin->getPrefix()."Leaving arena...");
        }
        $this->unsetPlayer($p);
        if($this->game === 1){
            $this->checkAlive();
        }
    }
    
    public function startGame($force = false)
    {
        $this->starting = false;

        Server::getInstance()->loadLevel($this->map);
        $this->level = $this->plugin->getServer()->getLevelByName($this->map);

        foreach ($this->lobbyp as $p) {
            if ($p->isOnline()) {
                unset($this->lobbyp[strtolower($p->getName())]);
                if ($this->getPlayerTeam($p) === 0) {
                    $p->kick("§cYou did not have selected your team!");
                    return;
                }
                $p->teleport($this->plugin->mainLobby);
                $p->getInventory()->clearAll();
                $this->level->addSound(new AnvilUseSound(new Vector3($p->x, $p->y, $p->z)), [$p]);

                new StartArenaTask($this->mtcore, $p->getName());
                $p->setExperience(0);
                $p->setExpLevel(0);
            }
        }



        $this->level->setTime(0);
        $this->level->stopTime();
        foreach ($this->getPlayersInTeam() as $p) {
            if ($p->isOnline()) {
                unset($this->lobbyp[strtolower($p->getName())]);
                $this->ingamep[strtolower($p->getName())] = $p;
                $team = $this->getPlayerTeam($p);
                $p->setHealth(20);
                if (isset($this->data[$team . "spawn"])) {
                    $p->teleport(new Position($this->data[$team . "spawn"]->x, $this->data[$team . "spawn"]->y, $this->data[$team . "spawn"]->z, $this->plugin->getServer()->getLevelByName($this->map)));
                    $p->setSpawn(new Vector3($this->data[$team . "spawn"]->x, $this->data[$team . "spawn"]->y, $this->data[$team . "spawn"]->z));
                }
            }
        }
        $this->game = 1;
        $this->messageAllPlayers($this->plugin->getPrefix() . TextFormat::AQUA . "Game started!");
    }
    
    public function onQuit(PlayerQuitEvent $e){
        if($this->getPlayerTeam($e->getPlayer()) !== false || $this->isSpectator($e->getPlayer())){
            $this->leaveArena($e->getPlayer());
        }
    }
    
    public function checkAlive(){
        if($this->ending === false){
            if(count($teams = $this->getAliveTeams()) === 1){
                $this->winnerTeam = $teams[0];
                foreach($this->getTeamPlayers($this->winnerTeam) as $p){
                    new DoActionQuery($this->plugin, $p->getName(), 0);
                    new AddCoinsQuery($this->mtcore, $p->getName(), 80);
                    $p->sendMessage($this->plugin->getPrefix().TextFormat::GOLD."Recieved 80 tokens for win");
                }
                $this->ending = true;
            }
        }
        if(count($this->getArenaPlayers()) <= 0){
            $this->stopGame();
            return;
        }
        if($this->level instanceof Level){
            if(count($this->level->getPlayers()) <= 0){
                $this->stopGame();
            }
        }
    }
    
    public function stopGame(){
        /** @var Player $p */
        foreach(array_merge($this->getArenaPlayers(), $this->spectators) as $p){
            if($p->isOnline()){
                if($this->shopManager->isShopping($p)){
                    $this->shopManager->unsetPlayer($p);
                }
                $this->unsetSpectator($p);
                $p->getInventory()->clearAll();
                $p->getInventory()->sendContents($p);
                $p->removeAllEffects();
                $p->setNameTag($this->mtcore->getDisplayRank($p)."  ".$p->getName());
                $p->setDisplayName($this->mtcore->getDisplayRank($p)." ".$p->getName());
                $p->setHealth(20);
                $p->teleport($this->plugin->mainLobby);
                $p->setSpawn($this->plugin->mainLobby);
                $this->mtcore->setLobby($p);
            }
        }
        $this->unsetAllPlayers();
        $this->task->gameTime = 3600;
        $this->task->startTime = 120;
        $this->task->drop = 0;
        $this->task->sign = 0;
        $this->popupTask->ending = 0;
        $this->votingManager->currentTable = [];
        $this->votingManager->stats = [];
        $this->votingManager->players = [];
        $this->votingManager->createVoteTable();
        $this->shopManager->players = [];
        $this->ending = false;
        $this->winnerTeam = null;
        $this->resetTeams();
        $this->game = 0;
        $this->plugin->getServer()->unloadLevel($this->level);
    }
    
    public function unsetAllPlayers(){
        $this->players = [];
        $this->spectators = [];
        $this->teams[0]['players'] = [];
        $this->teams[1]['players'] = [];
        $this->teams[2]['players'] = [];
        $this->teams[3]['players'] = [];
        $this->teams[4]['players'] = [];
        $this->lobbyp = [];
        $this->ingamep = [];
    }
    
    public function onRespawn(PlayerRespawnEvent $e){
        $p = $e->getPlayer();
        if(!$this->inArena($p)){
            return;
        }
        if($this->shopManager->isShopping($p)){
            $this->shopManager->unsetPlayer($p);
            return;
        }
        if(!$this->checkBed($this->getPlayerTeam($p))){
            $this->unsetPlayer($p);
        }
    }
    
    public function onDeath(PlayerDeathEvent $e){
        $p = $e->getEntity();
        $e->setDrops([]);
        $e->setDeathMessage("");
        $e->setKeepInventory(true);
        new ArenaDeathTask($this->mtcore, $p->getName());
        if(!$this->inArena($p) || !$this->getPlayerTeam($p) > 0){
            return;
        }
        if($this->shopManager->isShopping($p)){
            $this->shopManager->unsetPlayer($p);
        }
        if($this->game === 1){
            $this->deathManager->onDeath($e);
            new DoActionQuery($this->plugin, $p->getName(), 3);
            if($this->checkBed($this->getPlayerTeam($p)) === false){
                new DoActionQuery($this->plugin, $p->getName(), 1);
                $this->unsetPlayer($p);
                $p->getInventory()->clearAll();
                $p->sendMessage($this->plugin->getPrefix().TextFormat::YELLOW."Well played");
                $this->setSpectator($p, true);
            }
        }
    }
    
    public function onDropItem(PlayerDropItemEvent $e){
        $p = $e->getPlayer();
        if($this->shopManager->isShopping($p)){
            $e->setCancelled();
            $this->shopManager->unsetPlayer($p);
            return;
        }
        if(!$p->isOp() && $this->getPlayerTeam($p) === 0){
            $e->setCancelled();
        }
    }
    
    public function onHit(EntityDamageEvent $e){
        $victim = $e->getEntity();

        if($victim instanceof Player && !$this->inArena($victim)){
            return;
        }

        if($victim instanceof Villager){
            $e->setCancelled();
        }
        if($this->isSpectator($victim)){
            $e->setCancelled();
        }
        if($e instanceof EntityDamageByEntityEvent){
            $killer = $e->getDamager();
            if($this->isSpectator($killer)){
                $e->setCancelled();
                return;
            }
            if($victim instanceof Villager && $killer instanceof Player && isset($this->ingamep[strtolower($killer->getName())]) && $killer->getGamemode() === 0){
                $this->shopManager->openShop($killer);
                $e->setCancelled();
            }
            if($killer instanceof Player && $victim instanceof Player){
                if($this->inArena($killer) && ($this->game < 1 || $this->getPlayerTeam($victim) === $this->getPlayerTeam($killer))){
                    $e->setCancelled();
                    return;
                }
                if($this->shopManager->isShopping($victim) && $this->getPlayerTeam($victim) !== $this->getPlayerTeam($killer)){
                    $this->shopManager->unsetPlayer($victim);
                    return;
                }
                if($this->shopManager->isShopping($killer) && $this->getPlayerTeam($victim) !== $this->getPlayerTeam($killer)){
                    $this->shopManager->unsetPlayer($killer);
                    return;
                }
                $this->players[strtolower($victim->getName())]['killer'] = $killer->getName();
                $this->players[strtolower($victim->getName())]['killer_color'] = $this->getTeamColor($this->getPlayerTeam($killer));
                $this->players[strtolower($victim->getName())]['tick'] = $this->plugin->getServer()->getTick() + 200;
            }
        }
        if(!$victim instanceof Player){
            return;
        }
            if($e instanceof \pocketmine\event\entity\EntityDamageByChildEntityEvent){
                $killer = $e->getDamager();
                if($this->isSpectator($killer)){
                    $e->setCancelled();
                    return;
                }
                if($this->inArena($killer) && ($this->game < 1 || $this->getPlayerTeam($victim) === $this->getPlayerTeam($killer))){
                    $e->setCancelled();
                    return;
                }
                if($this->getPlayerTeam($killer) === $this->getPlayerTeam($victim)){
                    $e->setCancelled();
                    return;
                }
                if($this->shopManager->isShopping($victim) && $this->getPlayerTeam($victim) !== $this->getPlayerTeam($killer)){
                    $this->shopManager->unsetPlayer($victim);
                    return;
                }
                if($this->shopManager->isShopping($killer) && $this->getPlayerTeam($victim) !== $this->getPlayerTeam($killer)){
                    $this->shopManager->unsetPlayer($killer);
                    return;
                }
                $this->players[strtolower($victim->getName())]['killer'] = $killer->getName();
                $this->players[strtolower($victim->getName())]['killer_color'] = $this->getTeamColor($this->getPlayerTeam($killer));
                $this->players[strtolower($victim->getName())]['tick'] = $this->plugin->getServer()->getTick() + 200;
            }
    }
    
    public function onBlockBreak(BlockBreakEvent $e){
        $p = $e->getPlayer();
        $b = $e->getBlock();

        $e->setInstaBreak(true);

        if($b->level->getName() == "BedWars_hub" && !$p->isOp()){
            $e->setCancelled();
            return;
        }
        if($this->isSpectator($p)){
            $e->setCancelled();
            return;
        }
        if(!$this->inArena($p)){
            return;
        }
        if($this->shopManager->isShopping($p)){
            $e->setCancelled();
            $this->shopManager->unsetPlayer($p);
            return;
        }
        if($this->game === 0 || $this->getPlayerTeam($p) === false){
            $e->setCancelled();
            return;
        }
        if($this->isChest($b)){
            $e->setCancelled();
            return;
        }
        if($this->isBed($b) !== false){
            $this->onBedBreak($p, $this->isBed($b), $e);
            return;
        }
        $allowedBlocks = [24, 2, 30, 42, 54, 89, 121, 19, 92, Item::OBSIDIAN, Item::BRICKS];
        if (!in_array($b->getId(), $allowedBlocks)){
            $e->setCancelled();
            return;
	    }
        if($b->getId() === 19){
            $section = array_rand($this->shopManager->items, 1);
            $r = array_rand($this->shopManager->items[$section], 1);
            $randItem = $this->shopManager->items[$section][$r];
            if ($randItem instanceof Item){
                $e->setDrops([$randItem]);
            }
            else {
                $e->setDrops([Item::get($randItem, 0, 1)]);
            }
        }
    }
    
    public function onBlockPlace(BlockPlaceEvent $e){
        $p = $e->getPlayer();
        $b = $e->getBlock();
        if($this->isSpectator($p)){
            $e->setCancelled();
            return;
        }
        if(!$this->inArena($p)){
            return;
        }
        if($this->shopManager->isShopping($p)){
            $e->setCancelled();
            $this->shopManager->unsetPlayer($p);
            return;
        }
        if($this->game === 0 || $this->getPlayerTeam($p) === false){
            $e->setCancelled();
            return;
        }
        $allowedBlocks = [24, 2, 30, 42, 54, 89, 121, 19, 92, Item::OBSIDIAN, Item::BRICKS];
        if (!in_array($b->getId(), $allowedBlocks)){
            $e->setCancelled();
            return;
        }
    }

    public function onBedBreak(Player $p, $bedteam, BlockBreakEvent $e){
        if($this->getPlayerTeam($p) === $bedteam){
            $p->sendMessage($this->plugin->getPrefix().TextFormat::RED."You can not break your own bed");
            $e->setCancelled();
            return false;
        }
        if($this->getPlayerTeam($p) === false){
            return;
        }
        if($this->teams[$bedteam]['bed'] === false){
            return;
        }
        foreach($this->getTeamPlayers($bedteam) as $pl){
            if($p->isOnline()){
                $pl->setSpawn($this->plugin->mainLobby);
            }
        }
        new DoActionQuery($this->plugin, $p->getName(), 4);
        $b = $e->getBlock();
        $this->level->addParticle(new LargeExplodeParticle(new Vector3($b->x, $b->y, $b->z)));
        $pk = new ExplodePacket();
        $pk->x = $b->x;
        $pk->y = $b->y;
        $pk->z = $b->z;
        $pk->radius = 5;
        $light = new AddEntityPacket();
        $light->type = 93;
        $light->eid = Entity::$entityCount++;
        $light->metadata = 0;
        $light->speedX = 0;
        $light->speedY = 0;
        $light->speedZ = 0;
        $light->x = $b->x;
        $light->y = $b->y;
        $light->z = $b->z;
        /** @var Player $pl */
        foreach(array_merge($this->getArenaPlayers(), $this->spectators) as $pl){
            $pl->dataPacket($pk);
            $pl->dataPacket($light);
        }
        Server::broadcastPacket(array_merge($this->getArenaPlayers(), $this->spectators), $light->setChannel(Network::CHANNEL_ENTITY_SPAWNING));
        $team = $this->getPlayerTeam($p);
        $color = $this->getTeamColor($team);
        $name = $this->getTeamName($team);
        $this->messageAllPlayers(TextFormat::GRAY."================[ ".TextFormat::DARK_AQUA."Progress".TextFormat::GRAY." ]================\n"
                                                  . $color.$p->getName().TextFormat::GRAY." from ".$color.$name.TextFormat::GRAY." team destroyed ".$this->getTeamColor($bedteam).$this->getTeamName($bedteam).TextFormat::GRAY." team bed\n"
                        .TextFormat::GRAY         . "==========================================");
        $this->teams[$bedteam]['bed'] = false;
    }
    
    public function isJoinSign(Block $b){
        $sign1 = "{$this->mainData['1sign']->x}:{$this->mainData['1sign']->y}:{$this->mainData['1sign']->z}";
        $sign2 = "{$this->mainData['2sign']->x}:{$this->mainData['2sign']->y}:{$this->mainData['2sign']->z}";
        $sign3 = "{$this->mainData['3sign']->x}:{$this->mainData['3sign']->y}:{$this->mainData['3sign']->z}";
        $sign4 = "{$this->mainData['4sign']->x}:{$this->mainData['4sign']->y}:{$this->mainData['4sign']->z}";
        switch("$b->x:$b->y:$b->z"){
            case $sign1:
                return 1;
            case $sign2:
                return 2;
            case $sign3:
                return 3;
            case $sign4:
                return 4;
            default:
                return false;
        }
    }
    
    public function isBed(Block $b){
        if($b->level != $this->level){
            return false;
        }
        if($b->getId() !== 26){
            return false;
        }
        $bed1 = "{$this->data['1bed']->x}:{$this->data['1bed']->y}:{$this->data['1bed']->z}";
        $bed12 = "{$this->data['1bed2']->x}:{$this->data['1bed2']->y}:{$this->data['1bed2']->z}";
        $bed2 = "{$this->data['2bed']->x}:{$this->data['2bed']->y}:{$this->data['2bed']->z}";
        $bed22 = "{$this->data['2bed2']->x}:{$this->data['2bed2']->y}:{$this->data['2bed2']->z}";
        $bed3 = "{$this->data['3bed']->x}:{$this->data['3bed']->y}:{$this->data['3bed']->z}";
        $bed32 = "{$this->data['3bed2']->x}:{$this->data['3bed2']->y}:{$this->data['3bed2']->z}";
        $bed4 = "{$this->data['4bed']->x}:{$this->data['4bed']->y}:{$this->data['4bed']->z}";
        $bed42 = "{$this->data['4bed2']->x}:{$this->data['4bed2']->y}:{$this->data['4bed2']->z}";
        switch("$b->x:$b->y:$b->z"){
            case $bed1:
                return 1;
            case $bed12:
                return 1;
            case $bed2:
                return 2;
            case $bed22:
                return 2;
            case $bed3:
                return 3;
            case $bed32:
                return 3;
            case $bed4:
                return 4;
            case $bed42:
                return 4;
            default:
                return false;
        }
    }
    
    public function getPlayerTeam(Player $p){
        if(isset($this->players[strtolower($p->getName())]['team'])){
            return $this->players[strtolower($p->getName())]['team'];
        }
        return false;
    }

    public function getPlayerColor(Player $p){
        if(isset($this->players[strtolower($p->getName())]['team'])){
            return $this->teams[$this->getPlayerTeam($p)]['dec'];
        }
    }
    
    public function getTeamColor($team){
        return $this->teams[$team]['color'];
    }
    
    public function getTeamName($team){
        return $this->teams[$team]['name'];
    }
    
    public function addToTeam(Player $p, $team){
        if(!isset($this->lobbyp[strtolower($p->getName())])){
            return;
        }

        $isBigger = true;
        $p1 = \count($this->getTeamPlayers(1));
        $p2 = \count($this->getTeamPlayers(2));
        $p3 = \count($this->getTeamPlayers(3));
        $p4 = \count($this->getTeamPlayers(4));

        foreach ([$p1, $p2, $p3, $p4] as $count){
            if ($count >= \count($this->getTeamPlayers($team))){
                $isBigger = false;
                break;
            }
        }

        if($isBigger){
            $p->sendMessage("§cYou can't join that team, because it has more players than others");
            return;
        }
        if($this->getPlayerTeam($p) === false){
            return;
        }
        if($this->getPlayerTeam($p) === $team){
            $p->sendMessage(TextFormat::GRAY."You are already in ".$this->getTeamColor($team).$this->getTeamName($team).TextFormat::GRAY." team");
            return;
        }
        unset($this->teams[$this->getPlayerTeam($p)]['players'][strtolower($p->getName())]);
        $color = $this->getTeamColor($team);
        $p->sendMessage($this->plugin->getPrefix().TextFormat::GRAY."Joined ".$color.$this->getTeamName($team));
        $this->players[strtolower($p->getName())]['team'] = $team;
        $this->teams[$team]['players'][strtolower($p->getName())] = $p;
        $p->setNameTag($this->getTeamColor($team).$p->getName()."§f"); $p->setDisplayName($this->mtcore->getDisplayRank($p)." ".$color.$p->getName()."§f");
    }
    
    public function isTeamFull($team){
        if(count($this->getTeamPlayers($team)) >= 4){
            return true;
        }
        return false;
    }

    /**
     * @param $team
     * @return Player[]
     */
    public function getTeamPlayers($team){
        return $this->teams[$team]['players'];
    }

    /**
     * @return Player[]
     */
    public function getPlayersInTeam(){
        return array_merge($this->teams[1]['players'], $this->teams[2]['players'], $this->teams[3]['players'], $this->teams[4]['players']);
    }

    /**
     * @return Player[]
     */
    public function getArenaPlayers(){
        return array_merge($this->lobbyp, $this->ingamep);
    }
    
    public function unsetPlayer(Player $p){
        $team = $this->getPlayerTeam($p);
        $this->unsetSpectator($p);
        if(isset($this->players[strtolower($p->getName())])){
            unset($this->players[strtolower($p->getName())]);
        }
        if(isset($this->teams[$team]['players'][strtolower($p->getName())])){
            unset($this->teams[$team]['players'][strtolower($p->getName())]);
        }
        if(isset($this->lobbyp[strtolower($p->getName())])){
            unset($this->lobbyp[strtolower($p->getName())]);
        }
        if(isset($this->ingamep[strtolower($p->getName())])){
            unset($this->ingamep[strtolower($p->getName())]);
        }
        $p->setNameTag($this->mtcore->getDisplayRank($p)."  ".$p->getName());
        $p->setDisplayName($this->mtcore->getDisplayRank($p)." ".$p->getName());
        $p->setGamemode(0);
        if($p->isOnline()){
            $p->getInventory()->clearAll();
        }
    }
    
    public function dropBronze(){
        $chests = [$this->level->getTile($this->data['1bronze']), $this->level->getTile($this->data['2bronze']), $this->level->getTile($this->data['3bronze']), $this->level->getTile($this->data['4bronze'])];
        foreach($chests as $chest){
            if($chest instanceof \pocketmine\tile\Chest){
                /** @var ChestInventory $inv */
                $inv = $chest->getInventory();
                $inv->addItem(Item::get(336, 0, 1)->setCustomName("§r§6Bronze"));
            }
        }
    }
    
    public function dropIron(){
        $this->level->dropItem($this->data['1iron'], Item::get(265));
        $this->level->dropItem($this->data['2iron'], Item::get(265));
        $this->level->dropItem($this->data['3iron'], Item::get(265));
        $this->level->dropItem($this->data['4iron'], Item::get(265));
    }
    
    public function dropGold(){
        $this->level->dropItem($this->data['1gold'], Item::get(266));
        $this->level->dropItem($this->data['2gold'], Item::get(266));
        $this->level->dropItem($this->data['3gold'], Item::get(266));
        $this->level->dropItem($this->data['4gold'], Item::get(266));
    }
    
    public function messageTeam($message, Player $player = null, $team = null){
        if($player === null){
            /** @var Player $p */
            foreach($this->teams[$team]['players'] as $p){
                if($p->isOnline()){
                    $p->sendMessage($message);
                }
            }
            return;
        }
        $color = "";
        /** @var Player $p */
        foreach(array_merge($this->getTeamPlayers(0), $this->spectators, $this->teams[$this->getPlayerTeam($player)]['players']) as $p){
            if($player !== null){
                if($p->isOnline()){
                    $color = $this->getTeamColor($this->getPlayerTeam($player));
                    $p->sendMessage(TextFormat::GRAY."[{$color}Team".TextFormat::GRAY."]   ".$player->getDisplayName().TextFormat::DARK_AQUA." > ".$this->mtcore->getChatColor($player).$message);
                }
            }
        }
        $this->plugin->getServer()->getLogger()->info(TextFormat::GRAY."[{$color}Team".TextFormat::GRAY."]   ".$player->getDisplayName().TextFormat::DARK_AQUA." > ".$this->mtcore->getChatColor($player).$message);
    }
    
    public function messageAllPlayers($message, Player $player = null){
        /** @var Player $p */
        foreach(array_merge($this->getArenaPlayers(), $this->spectators) as $p){
            if($player !== null){
                if($p->isOnline()){
                    if($this->getPlayerTeam($player) === 0){
                        $p->sendMessage($player->getDisplayName().TextFormat::DARK_AQUA." > ".$message);
                        return;
                    }
                    $color = $this->getTeamColor($this->getPlayerTeam($player));
                    $p->sendMessage(TextFormat::GRAY."[{$color}All".TextFormat::GRAY."]   ".$player->getDisplayName().TextFormat::DARK_AQUA." > ".$this->mtcore->getChatColor($player).substr($message, 1));
                }
            }
            else{
                $p->sendMessage($message);
            }
        }
        $color = "";
        if($player !== null){
            $this->plugin->getServer()->getLogger()->info(TextFormat::GRAY."[{$color}All".TextFormat::GRAY."]   ".$player->getDisplayName().TextFormat::DARK_AQUA." > ".$this->mtcore->getChatColor($player).substr($message, 1));
        }
        else{
            $this->plugin->getServer()->getLogger()->info($message);
        }
    }
    
    public function checkBed($team){
        return $this->teams[$team]['bed'];
    }
    
    public function getGameStatus(){
                if($this->checkBed(1) === true){
                    $bed1 = "§9Blue: §a✔   ";
                }
                elseif($this->teams[1]['alive'] === false){
                    $bed1 = "";
                }
                else{
                    $bed1 = "§9Blue: §c✖   ";
                }
                if($this->checkBed(2) === true){
                    $bed2 = "§cRed: §a✔   ";
                }
                elseif($this->teams[2]['alive'] === false){
                    $bed2 = "";
                }
                else{
                    $bed2 = "§cRed: §c✖   ";
                }
                if($this->checkBed(3) === true){
                    $bed3 = "§eYellow: §a✔   ";
                }
                elseif($this->teams[3]['alive'] === false){
                    $bed3 = "";
                }
                else{
                    $bed3 = "§eYellow: §c✖   ";
                }
                if($this->checkBed(4) === true){
                    $bed4 = "§aGreen: §a✔";
                }
                elseif($this->teams[4]['alive'] === false){
                    $bed4 = "";
                }
                else{
                    $bed4 = "§aGreen: §c✖";
                }
                return "\n\n\n\n".$bed1.$bed2.$bed3.$bed4."\n ".TextFormat::GRAY.count($this->getTeamPlayers(1))."         ".count($this->getTeamPlayers(2))."          ".count($this->getTeamPlayers(3))."            ".count($this->getTeamPlayers(4));
    }
    
    public function selectMap($force = false){

        $stats = $this->votingManager->stats;
        asort($stats);
        if(!isset($this->votingManager->currentTable[array_keys($stats)[2] - 1])){
            $first = $this->votingManager->currentTable[0];
        }else {
            $first = $this->votingManager->currentTable[array_keys($stats)[2] - 1];
        }

        $map = $first."_".$this->id;

        if($this->plugin->getServer()->isLevelLoaded($map)){
            $this->plugin->getServer()->unloadLevel($this->plugin->getServer()->getLevelByName($map));
        }

        new WorldCopyTask($this->plugin, $this->plugin->getServer()->getDataPath(), $map);

        $this->map = $map;
        $this->data = $this->plugin->maps[$first];
        foreach($this->getArenaPlayers() as $p){
            if($p->isOnline()){
                $p->sendMessage(TextFormat::BOLD.TextFormat::YELLOW.$first.TextFormat::GOLD." was chosen");
            }
        }
    }
    
    public function checkLobby(){

        if(count($this->getArenaPlayers()) >= 8 && $this->game === 0){
            $this->starting = true;
        }
        if (count($this->getArenaPlayers()) >= 12 and $this->game === 0 and $this->task->startTime > 10){
            $this->task->startTime = 10;
            $this->task->isShortered = true;
        }
    }
    
    public function getAliveTeams(){
        $teams = [];
        for($i = 1; $i < 5; $i++){
            $players = [];
            foreach($this->getTeamPlayers($i) as $p){
                if(!$p->isOnline() || $p->getLevel() !== $this->level){
                    $this->unsetPlayer($p);
                    continue;
                }
                if(($p->isAlive()) || $this->checkBed($i) === true){
                    $players[] = $p;
                }
            }
            if(count($players) >= 1){
                $teams[] = $i;
            }
        }
        return $teams;
    }
    
    public function onItemHold(PlayerItemHeldEvent $e){
        $p = $e->getPlayer();
        if($this->getPlayerTeam($p) >= 1){
            $this->shopManager->buy($p, $e->getItem(), $e, $e->getInventorySlot());
        }
        if(isset($this->lobbyp[strtolower($p->getName())])){
            switch("{$e->getItem()->getId()}:{$e->getItem()->getDamage()}"){
                case "159:11":
                    $this->addToTeam($p, 1);
                    $e->setCancelled();
                    break;
                case "159:14":
                    $this->addToTeam($p, 2);
                    $e->setCancelled();
                    break;
                case "159:4":
                    $this->addToTeam($p, 3);
                    $e->setCancelled();
                    break;
                case "159:5":
                    $this->addToTeam($p, 4);
                    $e->setCancelled();
                    break;
            }
        }
    }
    
    public function onItemTake(InventoryPickupItemEvent $e){
        $inv = $e->getInventory();
        $p = $inv->getHolder();
        if($p instanceof Player){
            if($this->shopManager->isShopping($p)){
                $this->shopManager->unsetPlayer($p);
            }
        }
    }
    
    public function inArena(Player $p){
        return isset($this->players[strtolower($p->getName())]);
    }
    
    public function onBucketFill(PlayerBucketFillEvent $e){
        $p = $e->getPlayer();
        if(!$p->isOp() || $this->inArena($p)){
            $e->setCancelled();
        }
    }
    
    public function onBucketEmpty(PlayerBucketEmptyEvent $e){
        $p = $e->getPlayer();
        if(!$p->isOp() || $this->inArena($p)){
            $e->setCancelled();
        }
    }
    
    public function onCraft(CraftItemEvent $e){
        $e->setCancelled(true);
    }
    
    public function onBedEnter(PlayerBedEnterEvent $e){
        $e->setCancelled();
    }
    
    public function resetTeams(){
        $this->teams = [0 => ['name' => "lobby", 'color' => "§5", 'players' => []] ,1 => ['bed' => true, 'name' => "blue", 'color' => "§9", 'alive' => true, 'players' => []], 2 => ['bed' => true, 'name' => "red", 'color' => "§c", 'alive' => true, 'players' => []], 3 => ['bed' => true, 'name' => "yellow", 'color' => "§e", 'alive' => true, 'players' => []], 4 => ['bed' => true, 'name' => "green", 'color' => "§a", 'alive' => true, 'players' => []]];
    }

    public function isSpectator($p){
        if($p instanceof Player){
            return isset($this->spectators[strtolower($p->getName())]) ? true : false;
        }
        return false;
    }

    public function setSpectator(Player $p, $respawn = false){
        if($this->getPlayerTeam($p) !== false || $this->game !== 1){
            return;
        }
        $this->spectators[strtolower($p->getName())] = $p;
        $p->getInventory()->clearAll();
        $randPlayer = $this->getArenaPlayers()[array_rand($this->getArenaPlayers(), 1)];
        if($respawn !== true){
            $p->teleport(new Position($randPlayer->x, $randPlayer->y, $randPlayer->z, $this->level));
        }
        else{
            $p->setSpawn(new Position($p->x + 1, $p->y + 1, $p->z + 1, $this->level));
        }
        $p->setSneaking(false);
        $p->setGamemode(3);
        $p->getInventory()->setItem(0, Item::get(Item::CLOCK)->setCustomName(strtolower($randPlayer->getName())));
        $p->getInventory()->setHotbarSlotIndex(0, 0);
        $p->getInventory()->sendContents($p);

        foreach($this->getPlayersInTeam() as $pl){
            $p->despawnFrom($pl);
        }

        $this->mtcore->unsetLobby($p);
    }

    public function unsetSpectator(Player $p){
        if(!isset($this->spectators[strtolower($p->getName())])){
            return false;
        }
        unset($this->spectators[strtolower($p->getName())]);
        $p->setGamemode(0);
        $p->spawnToAll();
        $p->setSpawn($this->plugin->mainLobby);
        $p->removeAllEffects();
        if(($inventory = $p->getInventory()) instanceof PlayerInventory){
            $p->getInventory()->clearAll();
            $p->getInventory()->sendContents($p);
        }
        $this->mtcore->setLobby($p);
    }

    public function isChest(Block $b){
        return $b->equals($this->data['1bronze']) || $b->equals($this->data['2bronze']) || $b->equals($this->data['3bronze']) || $b->equals($this->data['4bronze']);
    }

    public function isEnderChest(Block $b){
        return $b->equals($this->data['1chest']) || $b->equals($this->data['2chest']) || $b->equals($this->data['3chest']) || $b->equals($this->data['4chest']);
    }

    public function onProjectileHit(ProjectileHitEvent $e){
        $ent = $e->getEntity();
        /** @var Player $p */
        if($ent instanceof Snowball && ($p = $ent->shootingEntity) instanceof Player && $this->inArena($p)){
            $p->teleport($ent->getPosition());
            $e = new EntityDamageEvent($p, EntityDamageEvent::CAUSE_FALL, 5);
            $p->attack($e->getDamage(), $e);
        }
    }

    /**
     * @param $team
     * @return Chest
     */
    public function getTeamEnderChest($team){
        return $this->level->getTile($this->data[$team."chest"]);
    }

    public function onChat(PlayerChatEvent $e){
        $p = $e->getPlayer();
        if($e->isCancelled() || !$this->inArena($p)){
            return;
        }

        $e->setCancelled();

        if($this->isSpectator($p)){
            $e->setCancelled();
            return;
        }
        if($this->shopManager->isShopping($p)){
            $this->shopManager->unsetPlayer($p);
            return;
        }
        if((strpos($e->getMessage(), "!") === 0 && strlen($e->getMessage()) > 1) || $this->getPlayerTeam($p) === 0){
            $this->messageAllPlayers($e->getMessage(), $p);
            return;
        }
        $this->messageTeam($e->getMessage(), $p);
    }

    public function onSpawn(EntitySpawnEvent $e){
        $p = $e->getEntity();
        if($p instanceof Player && $this->isSpectator($p)){
            $e->setCancelled();
        }
    }
}