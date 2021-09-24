<?php

namespace TurfWars;

use pocketmine\scheduler\Task as PluginTask;

class Task extends PluginTask{

    public function __construct(Main $plugin){
	$this->plugin = $plugin;
    }

    public function onRun($currentTick){
	$this->plugin->Second();
    }
}
