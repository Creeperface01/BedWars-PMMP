<?php

namespace BedWars\Task;

use BedWars\BedWars;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class ShowItemsOne extends AsyncTask{

    public $items;
    public $name;

    public function __construct(BedWars $plugin, $items, $name) {
        $this->items = $items;
        $this->name = $name;

        $plugin->getServer()->getScheduler()->scheduleAsyncTask($this);
    }

    public function onRun(){
        $result = [];
        $count = 0;
        foreach ($this->items as $nick => $items){
            if ($items[1] == "now"){
                $result[$count] = ["online", $items[0], $items[1], $items[3], "serpl"];
                $count++;
            }
            else {
                $data = file_get_contents("http://status.minetox.cz/data/".$nick."_players.txt");
                if ($data == "Offline"){
                    $result[$count] = ["offline", $items[0], $items[1], $items[3], 0];
                    $count++;
                }
                else {
                    $dalsi = file_get_contents("http://status.minetox.cz/data/" . $nick . ".txt");
                    $result[$count] = ["online", $items[0], $items[1], $items[3], $dalsi];
                    $count++;
                }
            }
        }
        $this->setResult($result);
    }

    public function onCompletion(Server $server){
        $result = $this->getResult();
        /** @var Player $p */
        $p = $server->getPlayer($this->name);
        if (!$p instanceof Player or !$p->isOnline()){
            return;
        }
        /** @var PlayerInventory $inv */
        $inv = $p->getInventory();
        $inv->clearAll();
        foreach ($result as $i => $item){
            $a = $item[1];
            $b = $item[2];
            $c = $item[3];
            if ($item[0] == "online"){
                if ($item[4] == "serpl"){
                    echo "Je to tak";
                    $item[4] = \count($server->getOnlinePlayers());
                }
                $item = Item::get(159, 13, $item[4]);
                if ($b == "now"){
                    $item->setCustomName("§r§l§4Bed§fWars §r§e- §aOnline\n§6Hold screen to join");
                }
                else {
                    $item->setCustomName("§r§l§4Bed§fWars §r§e".$a."- §aOnline\n§e".$b.":".$c."\n§6Hold screen to join");
                }
                $item->addEnchantment(-1, 1);
                $inv->setItem($i, $item);
            }
            else {
                $item = Item::get(159, 14, 1);
                //$item->setCustomName("§l§4Bed§fWars §r§e".$item[1]." - §cThis server is currently offline");
                $inv->setItem($i, $item);
            }
        }
        $inv->setItem(35, Item::get(Item::BED, 0, 1)->setCustomName("§7Back"));
        $inv->sendContents($p);
    }

}