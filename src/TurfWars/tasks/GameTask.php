<?php

namespace TurfWars\tasks;

use pocketmine\scheduler\Task;
use TurfWars\Main;

class GameTask extends Task{

    public function __construct(Main $plugin){
	$this->plugin = $plugin;
    }

    public function onRun(): void
    {
	$this->plugin->Second();
    }
}
