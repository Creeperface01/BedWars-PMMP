<?php
namespace BedWars\Arena;

use pocketmine\inventory\CustomInventory;
use pocketmine\Player;
use pocketmine\inventory\InventoryType;

class VirtualInventory extends CustomInventory{
    
    public $hotbar = [];
    public $armor = [];
    
    public function __construct(Player $p){
        parent::__construct($p, InventoryType::get(2));
        $inv = $p->getInventory();
        $this->setContents($inv->getContents());
        $this->armor = $inv->getArmorContents();
        for($i = 0; $i <= 7; $i++){
            $this->hotbar[$i] = $inv->getHotbarSlotIndex($i);
        }
    }
}