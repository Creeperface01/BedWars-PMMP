<?php

namespace BedWars;

use BedWars\MySQL\JoinTask;
use BedWars\MySQL\ShowStatsQuery;
use BedWars\Task\StartArenaTask;
use BedWars\Task\WorldCopyTask;
use MTCore\MTCore;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\level\Level;
use pocketmine\level\sound\NoteblockSound;
use pocketmine\plugin\PluginBase;
use pocketmine\entity\Entity;
use pocketmine\utils\TextFormat;
use BedWars\Arena\Arena;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use BedWars\MySQLManager;

class BedWars extends PluginBase implements Listener{

    public $maps;

    /** @var  Level $level */
    public $level;

    /** @var  Location $mainLobby */
    public $mainLobby;

    public $arenas;

    /** @var Arena[] */
    public $ins = [];

    /** @var  MTCore $mtcore */
    public $mtcore;

    public $restart;

    public function onEnable(){
        new MySQLManager($this);
        $this->level = $this->getServer()->getDefaultLevel();
        $this->mtcore = $this->getServer()->getPluginManager()->getPlugin("MTCore");
        $this->setMapsData();
        $this->setArenasData();
        $this->registerArena("bw-1");
        $this->registerArena("bw-2");
        $this->registerArena("bw-3");
        $this->mainLobby = $this->level->getSpawnLocation();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->level->setTime(5000);
        $this->level->stopTime;
    }

    public function onDisable(){
        foreach($this->ins as $arena){
            if($arena->game === 1){
                $arena->stopGame();
            }
        }
    }

    public function registerArena($arena){
        $a = new Arena($arena, $this);
        $this->getServer()->getPluginManager()->registerEvents($a, $this);
        $this->ins[$arena] = $a;
    }

    public static function getPrefix(){
        return "§l§0[ §fBed§4Wars§0 ] §r§f";
    }

    public function setArenasData(){
        $this->arenas["bw-1"] = ['sign' => new Vector3(124, 20, 78),
            '1sign' => new Vector3(488, 21, 493),
            '2sign' => new Vector3(488, 21, 491),
            '3sign' => new Vector3(490, 21, 489),
            '4sign' => new Vector3(492, 21, 489),
            'lobby' => new Vector3(528, 20, 497)];
        $this->arenas["bw-2"] = ['sign' => new Vector3(125, 20, 78),
            '1sign' => new Vector3(488, 21, 493),
            '2sign' => new Vector3(488, 21, 491),
            '3sign' => new Vector3(490, 21, 489),
            '4sign' => new Vector3(492, 21, 489),
            'lobby' => new Vector3(528, 20, 497)];
        $this->arenas["bw-3"] = ['sign' => new Vector3(126, 20, 78),
            '1sign' => new Vector3(488, 21, 493),
            '2sign' => new Vector3(488, 21, 491),
            '3sign' => new Vector3(490, 21, 489),
            '4sign' => new Vector3(492, 21, 489),
            'lobby' => new Vector3(528, 20, 497)];
    }

    public function setMapsData(){
        $this->maps = ['Kingdoms' => ['world' => $this->getServer()->getLevelByName("Kingdoms"),
                                    '1spawn' => new Vector3(-19, 10, 386),
                                    '1bed' => new Vector3(-21, 14, 386),
                                    '1bed2' => new Vector3(-22, 14, 386),
                                    '1bronze' => new Vector3(-6, 8, 400),
                                    '1iron' => new Vector3(70, 9, 390),
                                    '1gold' => new Vector3(106, 9, 390),
                                    '1chest' => new Vector3(-11, 12, 410),
                                    '2spawn' => new Vector3(237, 10, 394),
                                    '2bed' => new Vector3(239, 14, 394),
                                    '2bed2' => new Vector3(140, 14, 394),
                                    '2bronze' => new Vector3(224, 8, 380),
                                    '2iron' => new Vector3(148, 9, 390),
                                    '2gold' => new Vector3(112, 9, 390),
                                    '2chest' => new Vector3(229, 12, 370),
                                    '3spawn' => new Vector3(105, 10, 518),
                                    '3bed' => new Vector3(105, 14, 520),
                                    '3bed2' => new Vector3(105, 14, 521),
                                    '3bronze' => new Vector3(119, 8, 505),
                                    '3iron' => new Vector3(109, 9, 429),
                                    '3gold' => new Vector3(109, 9, 393),
                                    '3chest' => new Vector3(89, 12, 270),
                                    '4spawn' => new Vector3(113, 10, 262),
                                    '4bed' => new Vector3(113, 14, 260),
                                    '4bed2' => new Vector3(113, 14, 259),
                                    '4bronze' => new Vector3(99, 8, 275),
                                    '4iron' => new Vector3(109, 9, 351),
                                    '4gold' => new Vector3(109, 9, 387),
                                    '4chest' => new Vector3(129, 12, 510)],
                        'Chinese' => ['world' => $this->getServer()->getLevelByName("Chinese"),
                                    '1spawn' => new Vector3(-1028, 121, 237),
                                    '1bed' => new Vector3(-1038, 121, 237),
                                    '1bed2' => new Vector3(-1039, 121, 237),
                                    '1bronze' => new Vector3(-1032, 120, 237),
                                    '1iron' => new Vector3(-1022, 119, 235),
                                    '1gold' => new Vector3(-951, 109, 238),
                                    '2spawn' => new Vector3(-872, 121, 237),
                                    '2bed' => new Vector3(-862, 121, 237),
                                    '2bed2' => new Vector3(-861, 121, 237),
                                    '2bronze' => new Vector3(-868, 120, 237),
                                    '2iron' => new Vector3(-878, 119, 239),
                                    '2gold' => new Vector3(-948, 109, 237),
                                    '3spawn' => new Vector3(-950, 121, 315),
                                    '3bed' => new Vector3(-950, 121, 325),
                                    '3bed2' => new Vector3(-950, 121, 326),
                                    '3bronze' => new Vector3(-950, 120, 319),
                                    '3iron' => new Vector3(-952, 119, 309),
                                    '3gold' => new Vector3(-949, 109, 239),
                                    '4spawn' => new Vector3(-950, 121, 159),
                                    '4bed' => new Vector3(-950, 121, 149),
                                    '4bed2' => new Vector3(-950, 121, 148),
                                    '4bronze' => new Vector3(-950, 120, 155),
                                    '4iron' => new Vector3(-948, 118, 165),
                                    '4gold' => new Vector3(-950, 108, 236)],
			'Phizzle' => ['world' => $this->getServer()->getLevelByName("Phizzle"),
                                    '1spawn' => new Vector3(-6, 111, 1),
                                    '1bed' => new Vector3(-1, 111, -4),
                                    '1bed2' => new Vector3(0, 111, -4),
                                    '1bronze' => new Vector3(-8, 111, 4),
                                    '1iron' => new Vector3(-9, 110, -5),
                                    '1gold' => new Vector3(-1, 111, 53),
                                    '2spawn' => new Vector3(51, 111, 56),
                                    '2bed' => new Vector3(56, 111, 62),
                                    '2bed2' => new Vector3(56, 121, 61),
                                    '2bronze' => new Vector3(48, 111, 54),
                                    '2iron' => new Vector3(57, 110, 53),
                                    '2gold' => new Vector3(-1, 111, 61),
                                    '3spawn' => new Vector3(-61, 111, 58),
                                    '3bed' => new Vector3(-66, 111, 53),
                                    '3bed2' => new Vector3(-66, 111, 52),
                                    '3bronze' => new Vector3(-58, 111, 60),
                                    '3iron' => new Vector3(-67, 110, 61),
                                    '3gold' => new Vector3(-10, 111, 53),
                                    '4spawn' => new Vector3(-4, 111, 113),
                                    '4bed' => new Vector3(-9, 111, 118),
                                    '4bed2' => new Vector3(-10, 111, 118),
                                    '4bronze' => new Vector3(-2, 111, 110),
                                    '4iron' => new Vector3(-1, 110, 119),
                                    '4gold' => new Vector3(-10, 111, 61)],
                        'STW5' => ['world' => $this->getServer()->getLevelByName("STW5"),
                                    '1spawn' => new Vector3(-349, 35, 257),
                                    '1bed' => new Vector3(-330, 38, 255),
                                    '1bed2' => new Vector3(-330, 38, 254),
                                    '1bronze' => new Vector3(-345, 33, 260),
                                    '1iron' => new Vector3(-346, 34, 214),
                                    '1gold' => new Vector3(-339, 40, 181),
                                    '2spawn' => new Vector3(-343, 35, 91),
                                    '2bed' => new Vector3(-362, 38, 93),
                                    '2bed2' => new Vector3(-362, 38, 94),
                                    '2bronze' => new Vector3(-347, 33, 88),
                                    '2iron' => new Vector3(-346, 34, 134),
                                    '2gold' => new Vector3(-353, 40, 167),
                                    '3spawn' => new Vector3(-429, 35, 171),
                                    '3bed' => new Vector3(-427, 38, 190),
                                    '3bed2' => new Vector3(-426, 38, 190),
                                    '3bronze' => new Vector3(-432, 33, 175),
                                    '3iron' => new Vector3(-386, 34, 174),
                                    '3gold' => new Vector3(-353, 40, 181),
                                    '4spawn' => new Vector3(-263, 35, 177),
                                    '4bed' => new Vector3(-265, 38, 158),
                                    '4bed2' => new Vector3(-266, 38, 158),
                                    '4bronze' => new Vector3(-260, 33, 173),
                                    '4iron' => new Vector3(-306, 34, 174),
                                    '4gold' => new Vector3(-339, 40, 167)],
                        'BedWars1' => ['world' => $this->getServer()->getLevelByName("BedWars1"),
                                    '1spawn' => new Vector3(-1267, 98, -981),
                                    '1bed' => new Vector3(-1267, 102, -986),
                                    '1bed2' => new Vector3(-1267, 102, -985),
                                    '1bronze' => new Vector3(-1267, 98, -983),
                                    '1iron' => new Vector3(-1302, 98, -950),
                                    '1gold' => new Vector3(-1267, 98, -917),
                                    '2spawn' => new Vector3(-1267, 98, -849),
                                    '2bed' => new Vector3(-1267, 102, -844),
                                    '2bed2' => new Vector3(-1267, 102, -845),
                                    '2bronze' => new Vector3(-1267, 98, -847),
                                    '2iron' => new Vector3(-1232, 98, -880),
                                    '2gold' => new Vector3(-1267, 98, -913),
                                    '3spawn' => new Vector3(-1333, 98, -915),
                                    '3bed' => new Vector3(-1338, 102, -915),
                                    '3bed2' => new Vector3(-1337, 102, -915),
                                    '3bronze' => new Vector3(-1335, 98, -915),
                                    '3iron' => new Vector3(-1302, 98, -880),
                                    '3gold' => new Vector3(-1269, 98, -915),
                                    '4spawn' => new Vector3(-1201, 98, -915),
                                    '4bed' => new Vector3(-1196, 102, -915),
                                    '4bed2' => new Vector3(-1197, 102, -915),
                                    '4bronze' => new Vector3(-1199, 98, -915),
                                    '4iron' => new Vector3(-1232, 98, -950),
                                    '4gold' => new Vector3(-1265, 98, -915)],
                        'BedWars2' => ['world' => $this->getServer()->getLevelByName("BedWars2"),
                                    '1spawn' => new Vector3(353, 39, 630),
                                    '1bed' => new Vector3(353, 39, 627),
                                    '1bed2' => new Vector3(353, 39, 626),
                                    '1bronze' => new Vector3(353, 39, 640),
                                    '1iron' => new Vector3(351, 39, 639),
                                    '1gold' => new Vector3(354, 40, 540),
                                    '2spawn' => new Vector3(353, 39, 446),
                                    '2bed' => new Vector3(353, 39, 449),
                                    '2bed2' => new Vector3(353, 39, 450),
                                    '2bronze' => new Vector3(353, 39, 436),
                                    '2iron' => new Vector3(355, 39, 437),
                                    '2gold' => new Vector3(351, 40, 536),
                                    '3spawn' => new Vector3(445, 39, 538),
                                    '3bed' => new Vector3(442, 39, 538),
                                    '3bed2' => new Vector3(441, 39, 538),
                                    '3bronze' => new Vector3(455, 39, 538),
                                    '3iron' => new Vector3(454, 39, 540),
                                    '3gold' => new Vector3(355, 40, 536),
                                    '4spawn' => new Vector3(261, 39, 538),
                                    '4bed' => new Vector3(264, 39, 538),
                                    '4bed2' => new Vector3(265, 39, 538),
                                    '4bronze' => new Vector3(251, 39, 538),
                                    '4iron' => new Vector3(252, 39, 536),
                                    '4gold' => new Vector3(351, 40, 540)],
                        'Nether' => ['world' => $this->getServer()->getLevelByName("Nether"),
                                    '1spawn' => new Vector3(178, 64, 746),
                                    '1bed' => new Vector3(178, 71, 735),
                                    '1bed2' => new Vector3(178, 71, 734),
                                    '1bronze' => new Vector3(174, 64, 740),
                                    '1iron' => new Vector3(182, 64, 740),
                                    '1gold' => new Vector3(178, 65, 793),
                                    '2spawn' => new Vector3(230, 64, 798),
                                    '2bed' => new Vector3(241, 71, 798),
                                    '2bed2' => new Vector3(242, 71, 798),
                                    '2bronze' => new Vector3(236, 64, 794),
                                    '2iron' => new Vector3(236, 64, 802),
                                    '2gold' => new Vector3(182, 65, 799),
                                    '3spawn' => new Vector3(178, 64, 850),
                                    '3bed' => new Vector3(178, 71, 861),
                                    '3bed2' => new Vector3(178, 71, 862),
                                    '3bronze' => new Vector3(182, 64, 856),
                                    '3iron' => new Vector3(174, 64, 856),
                                    '3gold' => new Vector3(178, 65, 803),
                                    '4spawn' => new Vector3(126, 64, 798),
                                    '4bed' => new Vector3(115, 71, 798),
                                    '4bed2' => new Vector3(114, 71, 798),
                                    '4bronze' => new Vector3(120, 64, 802),
                                    '4iron' => new Vector3(120, 64, 794),
                                    '4gold' => new Vector3(173, 65, 799)]];
    }

    public function onJoin(PlayerJoinEvent $e){
        $p = $e->getPlayer();
        new JoinTask($this, $p->getName());
    }

    public function onQuit(PlayerQuitEvent $e){
        $e->setQuitMessage("");
    }

    public function onTouch(PlayerInteractEvent $e){
        $p = $e->getPlayer();
        if (!$this->mtcore->isAuthed($p)){
            return;
        }
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        if($sender instanceof Player){
            $arena = $this->getPlayerArena($sender);
            switch(strtolower($cmd->getName())){
                case 'blue':
                    if($arena === false || $arena->game === 1){
                        break;
                    }
                    $arena->addToTeam($sender, 1);
                    break;
                case 'red':
                    if($arena === false || $arena->game === 1){
                        break;
                    }
                    $arena->addToTeam($sender, 2);
                    break;
                case 'yellow':
                    if($arena === false || $arena->game === 1){
                        break;
                    }
                    $arena->addToTeam($sender, 3);
                    break;
                case 'green':
                    if($arena === false || $arena->game === 1){
                        break;
                    }
                    $arena->addToTeam($sender, 4);
                    break;
                case 'lobby':
                    if($arena !== false){
                        $arena->leaveArena($sender);
                    }
                    else{
                        $sender->teleport($this->mainLobby);
                    }
                    if(($inv = $sender->getInventory()) instanceof PlayerInventory){
                        $inv->clearAll();
                    }
                    break;
                case 'stats':
                    $count = \count($args);
                    if ($count === 0){
                        new ShowStatsQuery($this, $sender->getName());
                    }
                    if ($count >= 1){
                        new ShowStatsQuery($this, $args[0], $sender->getName());
                    }
                    break;
                case 'vote':
                    if($arena === false){
                        break;
                    }
                    if(isset($args[1]) || !isset($args[0])){
                        $sender->sendMessage($this->getPrefix().TextFormat::GRAY."use /vote [map]");
                        break;
                    }
                    $arena->votingManager->onVote($sender, strtolower($args[0]));
                    break;
                case "bw":
                    if(!$sender->isOp()){
                        $sender->sendMessage($cmd->getPermissionMessage());
                        break;
                    }
                    if(!isset($args[0])){
                        return;
                    }
                    switch(strtolower($args[0])){
                        case "start":
                            if(($arena === false && !isset($args[1])) || (isset($args[1]) && !$this->getArena($args[1]))){
                                break;
                            }
                            $arena = $arena !== false ? $arena : $this->getArena($args[1]);
                            $arena->selectMap(true);
                            $arena->startGame(true);
                            break;
                        case "stop":
                            if(($arena === false && !isset($args[1])) || !$this->getArena($args[1])){
                                break;
                            }
                            $arena !== false ? null : $arena = $this->getArena($args[1]);
                            $arena->stopGame();
                            break;
                        case "list":
                            if(!isset($args[1]) || !$this->getArena($args[1])){
                                break;
                            }
                            $arena = $this->getArena($args[1]);
                            $players = [];
                            foreach($arena->getArenaPlayers() as $p){
                                if($p->isOnline()) {
                                    $players[] = $p->getDisplayName();
                                }
                            }
                            $str = implode(", ", $players);
                            $sender->sendMessage(self::getPrefix().TextFormat::GRAY."Players in arena: ".$str);
                    }
            }
        }
    }

    public function getPlayerArena(Player $p){
        foreach($this->ins as $arena){
            if($arena->inArena($p) !== false || $arena->isSpectator($p)){
                return $arena;
            }
        }
        return false;
    }

    public function getArena($arena){
        return isset($this->ins[$arena]) ? $this->ins[$arena] : false;
    }

}