<?php

namespace TurfWars\tasks;

use pocketmine\scheduler\Task;

class GameTask extends Task{

    public function __construct(Main $plugin){
	$this->plugin = $plugin;
    }

    public function onRun($currentTick){
	$this->plugin->Second();
    }
}
