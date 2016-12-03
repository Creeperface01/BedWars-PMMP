<?php

namespace BedWars\Arena;

use BedWars\Task\ShopManagerDisplay;
use MTCore\MySQLManager;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\inventory\PlayerInventory;
use pocketmine\utils\TextFormat;
use pocketmine\item\enchantment\Enchantment;

class ShopManager{
    
    public $plugin;
    public $players = [];
    public $shopping = [];
    
    private $fakeChest = null;
    private $virtualChest = null;

    /** @var  Item[][] $items */
    public $items;
    
    public function __construct(Arena $plugin) {
        $this->plugin = $plugin;
        $this->items['main'] = [Item::get(24, 2, 1), Item::get(303, 0, 1), Item::get(274, 0, 1), Item::get(283, 0, 1), Item::get(261, 0, 1), Item::get(260, 0, 1), Item::get(54, 0, 1), Item::get(373, 0, 1), Item::get(46, 0, 1), Item::get(175, 0, 1), 26 => Item::get(35, 14, 1)];
        $this->items['blocks'] = [Item::get(24, 2, 1), Item::get(24, 2, 16), Item::get(121, 0, 1), Item::get(42, 0, 1), Item::get(89, 0, 1), 26 => Item::get(35, 14, 1)];
        $this->items['armor'] = [Item::get(298, 0, 1), Item::get(300, 0, 1), Item::get(301, 0, 1), Item::get(303, 0, 1), Item::get(303, 0, 1), Item::get(303, 0, 1), 26 => Item::get(35, 14, 1)];
        $this->items['pickaxes'] = [Item::get(270, 0, 1), Item::get(274, 0, 1), Item::get(257, 0, 1), 26 => Item::get(35, 14, 1)];
        $this->items['swords'] = [Item::get(283, 0, 1), Item::get(283, 0, 1), Item::get(267, 0, 1), 26 => Item::get(35, 14, 1)];
        $this->items['bows'] = [Item::get(261, 0, 1), Item::get(261, 0, 1), Item::get(261, 0, 1), Item::get(262, 0, 1), 26 => Item::get(35, 14, 1)];
        $this->items['food'] = [Item::get(260, 0, 1), Item::get(320, 0, 1), Item::get(354, 0, 1), 26 => Item::get(35, 14, 1)];
        $this->items['chests'] = [Item::get(54, 0, 1), 26 => Item::get(35, 14, 1)];
        $this->items['potions'] = [Item::get(373, 21, 1), Item::get(373, 22, 1), Item::get(373, 14, 1), Item::get(373, 29, 1), Item::get(373, 31, 1), 26 => Item::get(35, 14, 1)];
        $this->items['special'] = [Item::get(30, 0, 1), Item::get(19, 0, 1), Item::get(Item::SNOWBALL, 0, 1), 26 => Item::get(35, 14, 1)];
        $this->items['tokens'] = [Item::get(Item::DIAMOND_SWORD, 0, 1), Item::get(Item::BRICKS, 0, 1), Item::get(Item::DIAMOND_PICKAXE, 0, 1), Item::get(Item::OBSIDIAN, 0, 1), Item::get(Item::DIAMOND_HELMET,0, 1), Item::get(Item::DIAMOND_CHESTPLATE, 0, 1), Item::get(Item::DIAMOND_LEGGINGS, 0, 1),Item::get(Item::DIAMOND_BOOTS, 0, 1),26 => Item::get(35, 14, 1)];
        $this->items['armor'][3]->addEnchantment(0, 1);
        $this->items['armor'][3]->addEnchantment(Enchantment::UNBREAKING, 1);
        $this->items['armor'][4]->addEnchantment(0, 2);
        $this->items['armor'][4]->addEnchantment(Enchantment::UNBREAKING, 1);
        $this->items['armor'][5]->addEnchantment(0, 3);
        $this->items['armor'][5]->addEnchantment(Enchantment::UNBREAKING, 1);
        $this->items['pickaxes'][0]->addEnchantment(15, 1);
        $this->items['pickaxes'][1]->addEnchantment(15, 1);
        $this->items['pickaxes'][2]->addEnchantment(15, 1);
        $this->items['pickaxes'][0]->addEnchantment(Enchantment::UNBREAKING, 1);
        $this->items['pickaxes'][1]->addEnchantment(Enchantment::UNBREAKING, 1);
        $this->items['pickaxes'][2]->addEnchantment(Enchantment::UNBREAKING, 1);
        $this->items['swords'][0]->addEnchantment(Enchantment::SHARPNESS, 1);
        $this->items['swords'][0]->addEnchantment(Enchantment::UNBREAKING, 1);
        $this->items['swords'][1]->addEnchantment(Enchantment::SHARPNESS, 2);
        $this->items['swords'][0]->addEnchantment(Enchantment::UNBREAKING, 1);
        $this->items['swords'][2]->addEnchantment(Enchantment::SHARPNESS, 2);
        $this->items['swords'][0]->addEnchantment(Enchantment::UNBREAKING, 1);
        foreach ($this->items['bows'] as $bow){
            $bow->addEnchantment(Enchantment::INFINITY, 1);
        }
        $this->items['bows'][0]->addEnchantment(Enchantment::POWER, 1);
        $this->items['bows'][1]->addEnchantment(Enchantment::POWER, 2);
        $this->items['bows'][2]->addEnchantment(Enchantment::POWER, 2);
        $this->items['bows'][2]->addEnchantment(Enchantment::PUNCH, 1);
        $this->items['special'][1]->setCustomName("§r§eLucky Block");
        $this->items['special'][2]->setCustomName("§r§bEnder Pearl");
    }

    public function openShop(Player $p){
        if($this->isShopping($p)){
            return;
        }
        $inv = $p->getInventory();
        $this->players[strtolower($p->getName())]['items'] = new VirtualInventory($p);
        $this->players[strtolower($p->getName())]['window'] = "main";
        $this->shopping[$p->getName()] = true;
        $inv->clearAll();
        $inv->setContents($this->items['main']);
        for($i = 0; $i <= 8; $i++){
            $inv->setHotbarSlotIndex($i, 34);
        }
        $inv->sendContents($p);
        $p->addWindow($p->getInventory());
        $p->sendMessage($this->plugin->plugin->getPrefix().TextFormat::GREEN."Open your inventory for shopping");
    }

    public function buy(Player $p, Item $item, PlayerItemHeldEvent $e, $slot){
        if(!$this->isShopping($p) || $item->getId() === Item::AIR or $item->getCount() <= 0){
            return;
        }
        $inv = $p->getInventory();
        $window = $this->players[strtolower($p->getName())]['window'];
        $id = $item->getId();
        switch($window){
            case "main":
                switch($id){
                    case 24:
                        $this->players[strtolower($p->getName())]['window'] = "blocks";
                        $inv->clearAll();
                        $inv->setContents($this->items['blocks']);
                        for($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 303:
                        $this->players[strtolower($p->getName())]['window'] = "armor";
                        $inv->clearAll();
                        $cont = $this->items['armor'];
                        $c = $this->plugin->getPlayerColor($p);
                        $cont[0]->setCustomColor($c);
                        $cont[1]->setCustomColor($c);
                        $cont[2]->setCustomColor($c);
                        $inv->setContents($cont);
                        for($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 274:
                        $this->players[strtolower($p->getName())]['window'] = "pickaxes";
                        $inv->clearAll();
                        $inv->setContents($this->items['pickaxes']);
                        for($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 283:
                        $this->players[strtolower($p->getName())]['window'] = "swords";
                        $inv->clearAll();
                        $inv->setContents($this->items['swords']);
                        for($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 261:
                        $this->players[strtolower($p->getName())]['window'] = "bows";
                        $inv->clearAll();
                        $inv->setContents($this->items['bows']);
                        for($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 260:
                        $this->players[strtolower($p->getName())]['window'] = "food";
                        $inv->clearAll();
                        $inv->setContents($this->items['food']);
                        for($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 54:
                        $this->players[strtolower($p->getName())]['window'] = "chests";
                        $inv->clearAll();
                        $inv->setContents($this->items['chests']);
                        for($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 373:
                        $this->players[strtolower($p->getName())]['window'] = "potions";
                        $inv->clearAll();
                        $inv->setContents($this->items['potions']);
                        for($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 46:
                        $this->players[strtolower($p->getName())]['window'] = "special";
                        $inv->clearAll();
                        $inv->setContents($this->items['special']);
                        for($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 175:
                        $this->players[strtolower($p->getName())]['window'] = "tokens";
                        $inv->clearAll();
                        $inv->setContents($this->items['tokens']);
                        for ($i = 0; $i <= 8; $i++){
                            $inv->setHotbarSlotIndex($i, 34);
                        }
                        break;
                    case 35:
                        if($item->getDamage() !== 14){
                            break;
                        }
                        $this->unsetPlayer($p);
                        break;
                }
                break;
            case "blocks":
                switch($slot){
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "sendstone";
                        $this->openBuyWindow([Item::get(24, 2, 2), Item::get(336, 0, 1)], $inv);
                        break;
                    case 1:
                        $this->players[strtolower($p->getName())]['window'] = "sendstone16";
                        $this->openBuyWindow([Item::get(24, 2, 16), Item::get(336, 0, 8)], $inv);
                        break;
                    case 2:
                        $this->players[strtolower($p->getName())]['window'] = "endstone";
                        $this->openBuyWindow([$this->items['blocks'][1], Item::get(336, 0, 7)], $inv);
                        break;
                    case 3:
                        $this->players[strtolower($p->getName())]['window'] = "iron";
                        $this->openBuyWindow([$this->items['blocks'][2], Item::get(265, 0, 1)], $inv);
                        break;
                    case 4:
                        $this->players[strtolower($p->getName())]['window'] = "glowstone";
                        $this->openBuyWindow([Item::get(89, 0, 4), Item::get(336, 0, 4)], $inv);
                        break;
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                }
                for($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            case "armor":
                $c = $this->plugin->getPlayerColor($p);
                switch($slot){
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "helmet";
                        $this->openBuyWindow([$this->items['armor'][0]->setCustomColor($c), Item::get(336, 0, 1)], $inv);
                        break;
                    case 1:
                        $this->players[strtolower($p->getName())]['window'] = "leggings";
                        $this->openBuyWindow([$this->items['armor'][1]->setCustomColor($c), Item::get(336, 0, 1)], $inv);
                        break;
                    case 2:
                        $this->players[strtolower($p->getName())]['window'] = "boots";
                        $this->openBuyWindow([$this->items['armor'][2]->setCustomColor($c), Item::get(336, 0, 1)], $inv);
                        break;
                    case 3:
                        $this->players[strtolower($p->getName())]['window'] = "chestplate1";
                        $this->openBuyWindow([$this->items['armor'][3], Item::get(265, 0, 1)], $inv);
                        break;
                    case 4:
                        $this->players[strtolower($p->getName())]['window'] = "chestplate2";
                        $this->openBuyWindow([$this->items['armor'][4], Item::get(265, 0, 3)], $inv);
                        break;
                    case 5:
                        $this->players[strtolower($p->getName())]['window'] = "chestplate3";
                        $this->openBuyWindow([$this->items['armor'][5], Item::get(265, 0, 7)], $inv);
                        break;
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                }
                for($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            case "pickaxes":
                switch($slot){
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "pickaxe1";
                        $this->openBuyWindow([$this->items['pickaxes'][0], Item::get(336, 0, 3)], $inv);
                        break;
                    case 1:
                        $this->players[strtolower($p->getName())]['window'] = "pickaxe2";
                        $this->openBuyWindow([$this->items['pickaxes'][1], Item::get(265, 0, 2)], $inv);
                        break;
                    case 2:
                        $this->players[strtolower($p->getName())]['window'] = "pickaxe3";
                        $this->openBuyWindow([$this->items['pickaxes'][2], Item::get(266, 0, 1)], $inv);
                        break;
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                }
                for($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            case "swords":
                switch($slot){
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "sword1";
                        $this->openBuyWindow([$this->items['swords'][0], Item::get(265, 0, 1)], $inv);
                        break;
                    case 1:
                        $this->players[strtolower($p->getName())]['window'] = "sword2";
                        $this->openBuyWindow([$this->items['swords'][1], Item::get(265, 0, 3)], $inv);
                        break;
                    case 2:
                        $this->players[strtolower($p->getName())]['window'] = "sword3";
                        $this->openBuyWindow([$this->items['swords'][2], Item::get(266, 0, 5)], $inv);
                        break;
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                }
                for($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            case "bows":
                switch($slot){
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "bow1";
                        $this->openBuyWindow([$this->items['bows'][0], Item::get(266, 0, 3)], $inv);
                        break;
                    case 1:
                        $this->players[strtolower($p->getName())]['window'] = "bow2";
                        $this->openBuyWindow([$this->items['bows'][1], Item::get(266, 0, 7)], $inv);
                        break;
                    case 2:
                        $this->players[strtolower($p->getName())]['window'] = "bow3";
                        $this->openBuyWindow([$this->items['bows'][2], Item::get(266, 0, 13)], $inv);
                        break;
                    case 3:
                        $this->players[strtolower($p->getName())]['window'] = "arrow";
                        $this->openBuyWindow([$this->items['bows'][3], Item::get(266, 0, 1)], $inv);
                        break;
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                }
                for($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            case "food":
                switch($slot){
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "apple";
                        $this->openBuyWindow([$this->items['food'][0], Item::get(336, 0, 1)], $inv);
                        break;
                    case 1:
                        $this->players[strtolower($p->getName())]['window'] = "porkchop";
                        $this->openBuyWindow([$this->items['food'][1], Item::get(336, 0, 4)], $inv);
                        break;
                    case 2:
                        $this->players[strtolower($p->getName())]['window'] = "cake";
                        $this->openBuyWindow([$this->items['food'][2], Item::get(336, 0, 5)], $inv);
                        break;
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                }
                for($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            case "chests":
                switch($slot){
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "chest";
                        $this->openBuyWindow([$this->items['chests'][0], Item::get(265, 0, 1)], $inv);
                        break;
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                }
                for($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            case "potions":
                switch($slot){
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "healing I";
                        $this->openBuyWindow([$this->items['potions'][0], Item::get(265, 0, 3)], $inv);
                        break;
                    case 1:
                        $this->players[strtolower($p->getName())]['window'] = "healing II";
                        $this->openBuyWindow([$this->items['potions'][1], Item::get(265, 0, 5)], $inv);
                        break;
                    case 2:
                        $this->players[strtolower($p->getName())]['window'] = "speed I";
                        $this->openBuyWindow([$this->items['potions'][2], Item::get(265, 0, 7)], $inv);
                        break;
                    case 3:
                        $this->players[strtolower($p->getName())]['window'] = "regeneration I";
                        $this->openBuyWindow([$this->items['potions'][3], Item::get(266, 0, 3)], $inv);
                        break;
                    case 4:
                        $this->players[strtolower($p->getName())]['window'] = "strenght I";
                        $this->openBuyWindow([$this->items['potions'][4], Item::get(266, 0, 8)], $inv);
                        break;
                }
                for($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            case "special":
                switch($slot){
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "cobweb";
                        $this->openBuyWindow([Item::get(30, 0, 1), Item::get(265, 0, 4)], $inv);
                        break;
                    case 1:
                        $this->players[strtolower($p->getName())]['window'] = "sponge";
                        $this->openBuyWindow([Item::get(19, 0, 1), Item::get(265, 0, 5)], $inv);
                        break;
                    case 2:
                        $this->players[strtolower($p->getName())]['window'] = "snowball";
                        $this->openBuyWindow([Item::get(Item::SNOWBALL, 0, 1), Item::get(Item::GOLD_INGOT, 0, 13)], $inv);
                        break;
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                }
                for($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            case "tokens":
                switch ($slot){
                    case 0:
                        $this->players[strtolower($p->getName())]['window'] = "diamond_sword";
                        $p->sendPopup($this->plugin->plugin->getPrefix()."§bThis item costs §e300 Tokens");
                        $this->openBuyWindow([Item::get(Item::DIAMOND_SWORD, 0, 1), Item::get(Item::DIAMOND, 0, 30)], $inv);
                        break;
                    case 1:
                        $this->players[strtolower($p->getName())]['window'] = "bricks";
                        $p->sendPopup($this->plugin->plugin->getPrefix()."§bThis item costs §e100 Tokens");
                        $this->openBuyWindow([Item::get(Item::BRICKS, 0, 64), Item::get(Item::DIAMOND, 0, 10)], $inv);
                        break;
                    case 2:
                        $this->players[strtolower($p->getName())]['window'] = "diamond_pickaxe";
                        $p->sendPopup($this->plugin->plugin->getPrefix()."§bThis item costs §e500 Tokens");
                        $this->openBuyWindow([Item::get(Item::DIAMOND_PICKAXE, 0, 1), Item::get(Item::DIAMOND, 0, 50)], $inv);
                        break;
                    case 3:
                        $this->players[strtolower($p->getName())]['window'] = "obsidian";
                        $p->sendPopup($this->plugin->plugin->getPrefix()."§bThis item costs §e200 Tokens");
                        $this->openBuyWindow([Item::get(Item::OBSIDIAN, 0, 1), Item::get(Item::DIAMOND, 0, 20)], $inv);
                        break;
                    case 4:
                        $this->players[strtolower($p->getName())]['window'] = "diamond_helmet";
                        $p->sendPopup($this->plugin->plugin->getPrefix()."§bThis item costs §e250 Tokens");
                        $this->openBuyWindow([Item::get(Item::DIAMOND_HELMET, 0, 1), Item::get(Item::DIAMOND, 0, 25)], $inv);
                        break;
                    case 5:
                        $this->players[strtolower($p->getName())]['window'] = "diamond_chestplate";
                        $p->sendPopup($this->plugin->plugin->getPrefix()."§bThis item costs §e400 Tokens");
                        $this->openBuyWindow([Item::get(Item::DIAMOND_CHESTPLATE, 0, 1), Item::get(Item::DIAMOND, 0, 40)], $inv);
                        break;
                    case 6:
                        $this->players[strtolower($p->getName())]['window'] = "diamond_leggins";
                        $p->sendPopup($this->plugin->plugin->getPrefix()."§bThis item costs §e350 Tokens");
                        $this->openBuyWindow([Item::get(Item::DIAMOND_LEGGINGS, 0, 1), Item::get(Item::DIAMOND, 0, 35)], $inv);
                        break;
                    case 7:
                        $this->players[strtolower($p->getName())]['window'] = "diamond_boots";
                        $p->sendPopup($this->plugin->plugin->getPrefix()."§bThis item costs §e200 Tokens");
                        $this->openBuyWindow([Item::get(Item::DIAMOND_BOOTS, 0, 1), Item::get(Item::DIAMOND, 0, 20)], $inv);
                        break;
                    case 26:
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                }
                for ($i = 0; $i <= 8; $i++){
                    $inv->setHotbarSlotIndex($i, 34);
                }
                break;
            default:
                switch($id){
                    case 35:
                        if($item->getDamage() !== 14){
                            break;
                        }
                        $inv->clearAll();
                        $inv->setContents($this->items['main']);
                        $this->players[strtolower($p->getName())]['window'] = "main";
                        break;
                    case 265:
                        break;
                    case 266:
                        break;
                    case 336:
                        break;
                    default:
                        $this->clickItem($p, $item, $inv->getItem(2));
                }
        }
        $inv->sendContents($inv->getHolder());
        $e->setCancelled();
    }
    
    public function openBuyWindow(array $items, PlayerInventory $inv){
        $inv->clearAll();
        $inv->setItem(0, $items[0]);
        $inv->setItem(2, $items[1]);
        $inv->setItem(26, Item::get(35, 14, 1));
        for($i = 0; $i <= 6; $i++){
            $inv->setHotbarSlotIndex($i, 34);
        }
        $inv->sendContents($inv->getHolder());
    }
    
    public function clickItem(Player $p, Item $buyItem, Item $costItem){
        /** @var PlayerInventory $inv */
        $inv = $this->players[strtolower($p->getName())]['items'];
        if ($costItem->getId() === Item::DIAMOND){
            $price = 10*$costItem->getCount();
            new ShopManagerDisplay($this->plugin->mtcore, $p->getName(), $price, $buyItem->getId());
            return;
        }
        if ($costItem->getId() === Item::BRICK){
            $costItem->setCustomName("§r§6Bronze");
        }
        if($inv->contains($costItem)){
            $inv->removeItem($costItem);
            $inv->addItem($buyItem);
            $p->sendPopup($this->plugin->plugin->getPrefix().TextFormat::GREEN."Purchased {$buyItem->getName()}");   
            return;
        }
        $p->sendPopup($this->plugin->plugin->getPrefix().TextFormat::RED."You don't have enough ".$costItem->getName());
    }
    
    public function isShopping(Player $p){
        if(isset($this->players[strtolower($p->getName())]['window'])){
            return true;
        }
        return false;
    }
    
    public function unsetPlayer(Player $p){
        if($p->isOnline()){
            /** @var VirtualInventory $cv */
            $cv = $this->players[strtolower($p->getName())]['items'];
            $inv = $p->getInventory();
            $inv->clearAll();
            $inv->setContents($cv->getContents());
            $inv->setArmorContents($cv->armor);
            for($i = 0; $i <= 7; $i++){
                $inv->setHotbarSlotIndex($cv->hotbar[$i], $i);
            }
            $inv->sendContents($p);
        }
        unset($this->players[strtolower($p->getName())]);
    }
}