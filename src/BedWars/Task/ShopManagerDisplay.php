<?php

namespace BedWars\Task;

use BedWars\BedWars;
use MTCore\MTCore;
use MTCore\MySQLManager;
use MTCore\MySQL\AsyncQuery;
use pocketmine\item\Item;
use pocketmine\inventory\PlayerInventory;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ShopManagerDisplay extends AsyncQuery{

    public $price;
    public $buyItem;

    public function __construct(MTCore $plugin, $player, $price, $buyItem){
        $this->player = $player;
        $this->price = $price;
        $this->buyItem = $buyItem;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        $this->setResult([$data["tokens"]]);
    }

    public function onCompletion(Server $server){
        $p = $server->getPlayer($this->player);
        $money = $this->getResult()[0];
        if ($p instanceof Player && $p->isOnline()) {
            if ($this->price > $money){
                $p->sendPopup("§0[ §4Bed§fWars §0] §r§f"."§cYou don't have enough tokens\n§aGet tokens by winning in games or buy them at §bbit.do/mtBUY");
            }
            else {
                /** @var BedWars $bw */
                $bw = $server->getPluginManager()->getPlugin("BedWars");
                $arena = $bw->getPlayerArena($p);
                if ($arena !== null){
                    $cnt = $this->buyItem == Item::BRICKS ? 64 : 1;
                    $item = Item::get($this->buyItem, 0, $cnt);
                    /** @var PlayerInventory $inv */
                    $inv = $arena->shopManager->players[strtolower($p->getName())]['items'];
                    $inv->addItem($item);
                    $p->sendPopup("§0[ §4Bed§fWars §0] §r§f"."§aPurchased §b".$item->getName()." §afor §e".$this->price." §aTokens");
                    new ShopManagerBuy($arena->mtcore, $this->player, $this->price);
                }
            }
        }
    }

}