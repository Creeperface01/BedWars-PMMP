<?php

namespace BedWars\Arena;


use pocketmine\Player;

class Team{

    private $name;
    private $color;

    /** @var Player[] */
    private $players = [];

    private $bed;

    public function __construct($name, $color){
        $this->name = $name;
        $this->color = $color;
    }

    public function addPlayer(Player $p){

    }

    public function removePlayer(Player $p){

    }

    public function getBed(){
        return $this->bed;
    }

    public function setBedDestroyed(){
        $this->bed = false;
    }

    public function getName(){
        return $this->name;
    }

    public function getColor(){
        return $this->color;
    }

    public function isAlive(){
        return count($this->players) > 0 ? true : false;
    }
}