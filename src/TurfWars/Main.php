<?php

namespace TurfWars;

use FilesystemIterator;
use pocketmine\block\BaseSign;
use pocketmine\block\BlockFactory;
use pocketmine\block\utils\SignText;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\Listener;
use pocketmine\item\ItemFactory;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use TurfWars\tasks\GameTask;
//use TurfWars\command\TurfWarsCommand;
//use TurfWars\arena\MapReset;

class Main extends PluginBase implements Listener {
	
    public int $MAX = 2;
    public string $PREFIX = "§d[§9TW§d]§b ";
    public $REDSPAWN;
    public $BLUESPAWN;
    public $config;
    public $sign;
    public array $games = ["Game1" => ["Arena" => "TW-1", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game2" => ["Arena" => "TW-2", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game3" => ["Arena" => "TW-3", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game4" => ["Arena" => "TW-4", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game5" => ["Arena" => "TW-5", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game6" => ["Arena" => "TW-6", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game7" => ["Arena" => "TW-7", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game8" => ["Arena" => "TW-8", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game9" => ["Arena" => "TW-9", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game10" => ["Arena" => "TW-10", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game11" => ["Arena" => "TW-11", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game12" => ["Arena" => "TW-12", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game13" => ["Arena" => "TW-13", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game14" => ["Arena" => "TW-14", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game15" => ["Arena" => "TW-15", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game16" => ["Arena" => "TW-16", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game17" => ["Arena" => "TW-17", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game18" => ["Arena" => "TW-18", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game19" => ["Arena" => "TW-19", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0], "Game20" => ["Arena" => "TW-20", "Status" => "JOINABLE", "RedScore" => 0, "BlueScore" => 0]];
    public bool $isDevelopmentBuild = false;
	
    public function onEnable(): void
    {
        if ($this->isDevelopmentBuild == true) {
            $this->getLogger()->error("[ERROR] You cant use a development build... Disabling the plugin");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        if (!is_dir($this->getServer()->getDataPath() . "worlds/TW-1")) {
            $this->copymap($this->getDataFolder() . "maps/TW-BACKUP", $this->getServer()->getDataPath() . "worlds/TW-1");
            $this->getLogger()->info("A TurfWars map was added to the worlds folder, you can add more by copy and rename it to TW-2, TW-3, TW-4...");
        }
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $this->saveResource("config.yml");
        }
        $this->config = $this->getConfig();
        $this->MAX = $this->config->get("Max_Players");
        $this->PREFIX = $this->config->get("Prefix") . " ";
        $this->BLUESPAWN = ["x" => 608, "y" => 64, "z" => 1689];
        $this->REDSPAWN = ["x" => 534, "y" => 64, "z" => 1665];
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
//        $this->getServer()->getCommandMap()->register("TW-DRAGKILLS", new TurfWarsCommand($this));
        $this->getScheduler()->scheduleRepeatingTask(new GameTask($this), 20);
    }
	
    public function onDisable(): void
    {
        foreach ($this->games as $game => $index) {
            if ($index["Status"] == "INGAME") {
                $this->deleteDirectory($this->getServer()->getDataPath() . "worlds/" . $index["Arena"]);
                $this->copymap($this->getDataFolder() . "maps/TW-BACKUP", $this->getServer()->getDataPath() . "worlds/" . $index["Arena"]);
            }
        }
    }
	
    public function SignUpdate() {
        $lobby = $this->getServer()->getWorldManager()->getDefaultWorld();
        if ($this->getServer()->getWorldManager()->getWorldByName($lobby->getFolderName())) {
            foreach ($lobby->getLoadedChunks() as $chunks) {
                foreach ($chunks->getTiles() as $tile){
                    $signb = $this->getServer()->getWorldManager()->getDefaultWorld()->getBlock(new Vector3($tile->getPosition()->getX(), $tile->getPosition()->getY(), $tile->getPosition()->getZ()));
                    if ($signb->getID() == 323 || $signb->getID() == 63 || $signb->getID() == 68) {
                        $sign = $tile;
                        if($sign instanceof BaseSign){
                            $signt = $sign->getText()->getLines();
                            if ($signt[0] == "§d[§9TW§d]") {
                                $levelname = str_replace("§b", "", $signt[1]);
                                $Status = $this->games[$this->getGameByLevel($levelname) ]["Status"];
                                if ($Status > 5 || $Status == "JOINABLE") {
                                    $st = "§fJoinable";
                                } else {
                                    $st = "§cIngame";
                                }
                                if ($this->getServer()->getWorldManager()->isWorldLoaded($levelname)) {
                                    $players = $this->getServer()->getWorldManager()->getWorldByName($levelname)->getPlayers();
                                    if (!isset($players)) {
                                        $sign->setText(new SignText([$signt[0] => "§d0§e / §d" . $this->MAX, $signt[1] => $st]));
                                    }
                                    if (count($players) < 2 && $signt[2] == "§cIngame") {
                                        $sign->setText(new SignText([$signt[0] => "§d0§e / §d" . $this->MAX, $signt[1] => $st]));
                                    } else {
                                        $sign->setText(new SignText([$signt[0] => "§e / §d" . $this->MAX, $signt[1] => $st]));
                                    }
                                } else {
                                    $sign->setText(new SignText([$signt[0] => "§d0§e / §d" . $this->MAX, $signt[1] => $st]));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
	
    public function onQuit(PlayerQuitEvent $event)
    {
        if ($this->inTurfWars($event->getPlayer())) {
            $s = $this->games[$this->getGameByPlayer($event->getPlayer()) ]["Status"];
            $this->LeaveCheck($this->getGameByPlayer($event->getPlayer()), $this->getTeam($event->getPlayer()));
        }
    }
	
    public function updateTerrain($game, $scorer_team) {
        $x = 570 + $this->games[$game]["RedScore"] - $this->games[$game]["BlueScore"];
        $lvl = $this->getServer()->getWorldManager()->getWorldByName($this->games[$game]["Arena"]);
        if ($scorer_team == "Red") {
            $lvl->setBlock(new Vector3($x, 63, 1661), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1662), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1663), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1664), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1665), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1666), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1667), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1668), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1669), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1670), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1670), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1671), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1672), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1673), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1674), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1675), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1676), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1677), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1678), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1679), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1680), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1681), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1682), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1683), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1684), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1685), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1686), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1687), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1688), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1689), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1690), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1691), BlockFactory::getInstance()->get(251, 14));
            $lvl->setBlock(new Vector3($x, 63, 1692), BlockFactory::getInstance()->get(251, 14));
            for ($y = 64; $y <= 100; $y++){
                $lvl->setBlock(new Vector3($x, 63, 1661), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1662), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1663), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1664), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1665), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1666), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1667), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1668), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1669), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1670), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1670), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1671), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1672), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1673), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1674), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1675), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1676), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1677), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1678), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1679), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1680), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1681), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1682), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1683), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1684), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1685), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1686), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1687), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1688), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1689), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1690), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1691), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x, 63, 1692), BlockFactory::getInstance()->get(0));
            }
        }
        if ($scorer_team == "Blue") {
            $lvl->setBlock(new Vector3($x + 1, 63, 1662), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1663), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1664), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1665), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1666), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1667), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1668), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1669), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1670), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1670), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1671), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1672), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1673), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1674), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1675), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1676), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1677), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1678), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1679), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1680), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1681), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1682), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1683), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1684), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1685), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1686), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1687), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1688), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1689), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1690), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1691), BlockFactory::getInstance()->get(251, 11));
            $lvl->setBlock(new Vector3($x + 1, 63, 1692), BlockFactory::getInstance()->get(251, 11));
            for ($y = 64; $y <= 100; $y++){
                $lvl->setBlock(new Vector3($x + 1, 63, 1662), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1663), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1664), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1665), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1666), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1667), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1668), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1669), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1670), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1670), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1671), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1672), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1673), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1674), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1675), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1676), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1677), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1678), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1679), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1680), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1681), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1682), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1683), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1684), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1685), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1686), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1687), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1688), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1689), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1690), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1691), BlockFactory::getInstance()->get(0));
                $lvl->setBlock(new Vector3($x + 1, 63, 1692), BlockFactory::getInstance()->get(0));
            }
        }
    }
	
    public function LeaveCheck($game, $team) {
        $gameplayers = $this->getServer()->getWorldManager()->getWorldByName($this->games[$game]["Arena"])->getPlayers();
        $s = $this->games[$game]["Status"];
        if ($s > 5 && count($gameplayers) < 3) {
            $this->games[$game]["Status"] = "JOINABLE";
            return;
        }
        if ($s === "INGAME" || $s === 5 || $s === 4 || $s === 3 || $s === 2 || $s === 1 || $s === "zero") {
            $red = 0;
            $blue = 0;
            if ($team == "Red") {
                $red = - 1;
            } else {
                $red = 0;
            }
            if ($team == "Blue") {
                $blue = - 1;
            } else {
                $blue = 0;
            }
            foreach ($gameplayers as $player) {
                if ($this->getTeam($player) == "Red") {
                    $red++;
                }
                if ($this->getTeam($player) == "Blue") {
                    $blue++;
                }
            }
            if (count($gameplayers) < 3 || $blue == 0 || $red == 0) {
                $this->games[$game]["RedScore"] = 0;
                $this->games[$game]["BlueScore"] = 0;
                $this->games[$game]["Status"] = "JOINABLE";
                foreach ($gameplayers as $player) {
                    $player->getInventory()->clearAll();
                    $player->setNameTag($player->getName());
                    $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                    $player->sendPopup(" ");
                    $player->sendMessage($this->PREFIX . "Game was cancelled, to less players.");
                }
                $this->deleteDirectory($this->getServer()->getDataPath() . "worlds/" . $this->games[$game]["Arena"]);
                $this->copymap($this->getDataFolder() . "maps/TW-BACKUP", $this->getServer()->getDataPath() . "worlds/" . $this->games[$game]["Arena"]);
            }
        }
    }
	
    public function removeArrow(ProjectileHitEvent $event)
    {
        if ($this->inTurfWars($event->getEntity()->getOwningEntity())) {
            if ($event->getEntity() instanceof Arrow) {
                if ($event->getEntity()->onGround && $event->getEntity()->isAlive() && !$event->getEntity()->isClosed() && !$event->getEntity()->isFlaggedForDespawn()) {
                    $event->getEntity()->close();
                    $event->getEntity()->flagForDespawn();
                }
            }
        }
    }
	
    public function onSignCreate(SignChangeEvent $event)
    {
        if ($event->getPlayer()->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            if ($event->getSign()->getText()->getLine(0) == "§d[§9TW§d]") {
                if (!$this->getServer()->getWorldManager()->isWorldGenerated($event->getSign()->getText()->getLine(1))) {
                    $event->getPlayer()->sendMessage($this->PREFIX . "The level does not exist.");
                    $event->cancel();
                    return;
                }
                $event->getSign()->setText(new SignText(
                    [
                        1 => "§b" . $event->getSign()->getText()->getLine(1),
                        2 => "§fJoinable",
                        3 => "§d0§e / §d" . $this->MAX
                    ]));
                $event->getPlayer()->sendMessage($this->PREFIX . "Join sign was succesfuly created.");
            }
        } else {
            $event->getPlayer()->sendMessage($this->PREFIX . "§cYou need to be an OP to create a join sign for that minigame.");
            $event->cancel();
        }
    }
	
    public function onInteract(PlayerInteractEvent $event) {
        if ($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68) {
            $sign = $event->getPlayer()->getWorld()->getTile($event->getBlock()->getPosition()->asVector3());
            if ($sign instanceof BaseSign) {
                $signt = $sign->getText()->getLines();
                if ($signt[0] == "§d[§9TW§d]") {
                    // remove the color text from the world name
                    $signt[1] = str_replace("§b", "", $signt[1]);
                    if ($signt[2] != "§fJoinable") {
                        $event->getPlayer()->sendMessage($this->PREFIX . "The game is already full.");
                        return;
                    }
                    // check if the level exists
                    if ($this->getServer()->getWorldManager()->isWorldGenerated($signt[1])) {
                        $this->joinGame($event->getPlayer(), str_replace("§b", "", $signt[1]));
                        // refresh sign text
                        $s1 = str_replace("§", "", $signt[3]);
                        $s2 = str_replace(" ", "", $s1);
                        $s3 = str_replace("/", "", $s2);
                        $s4 = str_replace("e", "", $s3);
                        $s5 = str_replace("d", "", $s4);
                        $am = str_replace($this->MAX, "", $s5);
                        $am = $am + 1;
                        if ($am != $this->MAX) {
                            $sign->setText(new SignText([$signt[0] => "§b" . $signt[1], $signt[2] => "§d" . $am . "§e / §d" . $this->MAX]));
                        }
                        if ($am == $this->MAX) {
                            $sign->setText(new SignText([$signt[0] => "§b" . $signt[1], $signt[1] => "§d" . $am . "§e / §d" . $this->MAX, $signt[2] => "§cIngame"]));
                        }
                    } else {
                        $event->getPlayer()->sendMessage($this->PREFIX . "This game is not in usage.");
                    }
                }
            }
        }
    }
	
    public function onLaunch(ProjectileLaunchEvent $event)
    {
        if ($this->inTurfWars($player = $event->getEntity()->getOwningEntity())) {
            if($player instanceof Player){
                $player->getInventory()->setItem(1, ItemFactory::getInstance()->get(262, 0, 5));
                if ($this->games[$this->getGameByPlayer($player) ]["Status"] != "INGAME") {
                    $event->cancel();
                }
            }
        }
    }
	
    public function onItemDrop(PlayerDropItemEvent $event) {
        if ($this->inTurfWars($event->getPlayer())) {
            $event->cancel();
        }
    }
	
    public function onBreak(BlockBreakEvent $event) {
        if ($this->inTurfWars($event->getPlayer())) {
            $event->cancel();
        }
        if ($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68) {
            if (!$event->getPlayer()->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                $tile = $this->getServer()->getWorldManager()->getDefaultWorld()->getTile($event->getBlock()->getPosition()->asVector3());
                if($tile instanceof BaseSign){
                    if ($tile->getText() [0] == "§d[§9TW§d]") {
                        $event->cancel();
                        return;
                    }
                }
            }
            $tile = $this->getServer()->getWorldManager()->getDefaultWorld()->getTile($event->getBlock()->getPosition()->asVector3());
            if($tile instanceof BaseSign){
                if ($tile->getText() [0] == "§d[§9TW§d]") {
                    $event->getPlayer()->sendMessage($this->PREFIX . "Join sign was removed.");
                }
            }
        }
    }
	
    public function onMove(PlayerMoveEvent $event) {
        if ($this->inTurfWars($player = $event->getPlayer())) {
            if (!($this->games[$this->getGameByPlayer($player) ]["Status"] == "INGAME" || $this->games[$this->getGameByPlayer($player) ]["Status"] == "JOINABLE" || $this->games[$this->getGameByPlayer($player) ]["Status"] > 5)) {
                $event->cancel();
            }
            $block = $event->getPlayer()->getWorld()->getBlock(new Vector3($player->getPosition()->getX(), 63, $player->getPosition()->getZ()));
            if ($this->getTeam($player) == "Red") {
                if ($block->getId() == 159 && $block->getDamage() == 3) {
                    $player->setMotion(new Vector3(-1.5, 1, 0));
                }
            }
            if ($this->getTeam($player) == "Blue") {
                if ($block->getId() == 159 && $block->getDamage() == 14) {
                    $player->setMotion(new Vector3(1.5, 1, 0));
                }
            }
        }
    }
	
    public function onDamage(EntityDamageEvent $event) {
        if ($event instanceof EntityDamageByEntityEvent) {
            if ($event->getCause() == EntityDamageEvent::CAUSE_PROJECTILE) {
                $killer = $event->getDamager();
                $victim = $event->getEntity();
                if ($this->inTurfWars($victim)) {
                    if ($this->getTeam($victim) == $this->getTeam($killer)) {
                        $event->cancel();
                        return;
                    }
                    if (!$killer instanceof Arrow) {
                        if ($this->getTeam($killer) == $this->getTeam($victim)) {
                            $event->cancel();
                            return;
                        }
                        $this->addScore($this->getTeam($killer), $this->getGameByPlayer($killer));
                        $this->updateTerrain($this->getGameByLevel($event->getEntity()->getLevel()->getFolderName()), $this->getTeam($killer));
                        if ($victim instanceof Player && $killer instanceof Player) {
                            $victim->sendMessage($this->PREFIX . "§dYou were killed by §c" . $killer->getName());
                            $killer->sendMessage($this->PREFIX . "§dYou killed §a" . $victim->getName());
                        }
                        $event->cancel();
                        if ($this->getTeam($victim) == "Red") {
                            $victim->teleport(new Vector3($this->REDSPAWN["x"], $this->REDSPAWN["y"], $this->REDSPAWN["z"]));
                            $victim->setMotion(new Vector3(0, 0.1, 0));
                        } elseif ($this->getTeam($victim) == "Blue") {
                            $victim->teleport(new Vector3($this->BLUESPAWN["x"], $this->BLUESPAWN["y"], $this->BLUESPAWN["z"]));
                            $victim->setMotion(new Vector3(0, 0.1, 0));
                        }
                    }
                }
            }
        } else {
            if ($event->getEntity() instanceof Player) {
                if ($this->inTurfWars($event->getEntity())) {
                    $event->cancel();
                }
            }
        }
        if ($this->inTurfWars($event->getEntity())) {
            $event->cancel();
        }
    }
	
    public function joinGame($player, $levelname) {
        if (!$this->getServer()->getWorldManager()->isWorldLoaded($levelname)) {
            $this->getServer()->getWorldManager()->loadWorld($levelname);
            $this->getServer()->getWorldManager()->loadWorld($levelname);
            $this->getServer()->getWorldManager()->loadWorld($levelname);
            $this->getServer()->getWorldManager()->getWorldByName($levelname)->setTime(0);
            $this->getServer()->getWorldManager()->getWorldByName($levelname)->stopTime();
        }
        if ($this->games[$this->getGameByLevel($levelname) ]["Status"] == "JOINABLE" || $this->games[$this->getGameByLevel($levelname) ]["Status"] > 5) {
            $this->GameSetup($player, $levelname);
        } else {
            $player->sendMessage($this->PREFIX . "Game is running already.");
        }
    }
	
    public function joinRandomGame($player) {
        foreach ($this->games as $game => $value) {
            $Arena = $value["Arena"];
            $Status = $value["Status"];
            if ($this->getServer()->getWorldManager()->isWorldGenerated($Arena)) {
                if (!$this->getServer()->getWorldManager()->isWorldLoaded($Arena)) {
                    $this->getServer()->getWorldManager()->loadWorld($Arena);
                    $this->getServer()->getWorldManager()->getWorldByName($Arena)->setTime(0);
                    $this->getServer()->getWorldManager()->getWorldByName($Arena)->stopTime();
                }
                if (count($this->getServer()->getWorldManager()->getWorldByName($Arena)->getPlayers()) < $this->MAX) {
                    $this->GameSetup($player, $Arena);
                    return;
                }
            }
        }
    }
	
    public function GameSetup($player, $Arena) {
        // Game setup
        $Players = $this->getServer()->getWorldManager()->getWorldByName($Arena)->getPlayers();
        // RELOAD AND RESTORE LEVEL
        if (count($Players) == 0) {
            if ($this->getServer()->getWorldManager()->isWorldLoaded($Arena)) {
                $this->getServer()->getWorldManager()->unloadWorld($this->getServer()->getWorldManager()->getWorldByName($Arena));
            }
        }
        $this->getServer()->getWorldManager()->loadWorld($Arena);
        // RESTORING END
        $player->getInventory()->clearAll();
        $player->getInventory()->addItem(ItemFactory::getInstance()->get(261, 0, 1));
        $player->getInventory()->addItem(ItemFactory::getInstance()->get(262, 0, 5));
        $player->teleport(new Position(573, 83, 1648, $this->getServer()->getWorldManager()->getWorldByName($Arena)));
        $player->sendMessage($this->PREFIX . "Sent you to §a" . $Arena);
        $player->sendMessage($this->PREFIX . "Bow and Arrows were added to your inventory, open it to select them.");
        // teleport player to spawn
        if (count($this->getServer()->getWorldManager()->getWorldByName($Arena)->getPlayers()) == 2) {
            $this->games[$this->getGameByLevel($Arena) ]["Status"] = 80;
            // countdown
            
        }
        if (count($this->getServer()->getWorldManager()->getWorldByName($Arena)->getPlayers()) == $this->MAX) {
            $this->StartGame($Arena);
            $this->games[$this->getGameByLevel($Arena) ]["Status"] = 5;
        }
    }
	
    /* GAME API */
    public function StartGame($Arena) {
        $players = $this->getServer()->getWorldManager()->getWorldByName($Arena)->getPlayers();
        $len = count($players);
        $blues = array_slice($players, $len / 2);
        $reds = array_slice($players, 0, $len / 2);
        foreach ($blues as $blue) {
            $this->setTeam($blue, "Blue");
            $blue->sendMessage($this->PREFIX . "Your Team: §lBlue");
            $blue->teleport(new Position($this->BLUESPAWN["x"], $this->BLUESPAWN["y"], $this->BLUESPAWN["z"], $this->getServer()->getWorldManager()->getWorldByName($Arena)));
        }
        foreach ($reds as $red) {
            $this->setTeam($red, "Red");
            $red->sendMessage($this->PREFIX . "Your Team: §l§cRed");
            $red->teleport(new Position($this->REDSPAWN["x"], $this->REDSPAWN["y"], $this->REDSPAWN["z"], $this->getServer()->getWorldManager()->getWorldByName($Arena)));
        }
    }
	
    public function setTeam($player, $teamname) {
        if ($teamname == "Red") {
            $player->setNameTag("§c[RED] §f" . $player->getName());
        }
        if ($teamname == "Blue") {
            $player->setNameTag("§b[BLUE] §f" . $player->getName());
        }
    }
	
    public function getTeam($player)
    {
        if (strpos($player->getNameTag(), "[RED] ")) {
            return "Red";
        } elseif (strpos($player->getNameTag(), "[BLUE] ")) {
            return "Blue";
        }
        return null;
    }
	
    public function inTurfWars($player)
    {
        if ($this->getGameByPlayer($player) == "") {
            return false;
        } else {
            return true;
        }
    }
	
    public function getGameByPlayer($player)
    {
        if (is_null($player)) {
            return false;
        }
        foreach ($this->games as $game => $value) {
            if ($value["Arena"] == $player->getLevel()->getFolderName()) {
                return $game;
            }
        }
        return null;
    }
	
    public function getGameByLevel($level)
    {
        foreach ($this->games as $game => $value) {
            if ($value["Arena"] == $level) {
                return $game;
            }
        }
        return null;
    }
	
    public function addScore($team, $game) {
        /* Game1, Game2 etc...*/
        $levelname = $this->games[$game]["Arena"];
        $level = $this->getServer()->getWorldManager()->getWorldByName($levelname);
        if ($team == "Red") {
            $this->games[$game]["RedScore"] = $this->games[$game]["RedScore"] + 1;
        } elseif ($team == "Blue") {
            $this->games[$game]["BlueScore"] = $this->games[$game]["BlueScore"] + 1;
        }
        if ($this->games[$game]["RedScore"] - $this->games[$game]["BlueScore"] == 32) {
            foreach ($this->getServer()->getWorldManager()->getWorldByName($this->games[$game]["Arena"])->getPlayers() as $player) {
                if ($this->getTeam($player) == "Red") {
                    $player->sendMessage($this->PREFIX . " Your team won.");
                }
                $player->getInventory()->clearAll();
                $player->setNameTag($player->getDisplayName());
                $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                $this->games[$game]["Status"] = "JOINABLE";
                $player->sendMessage($this->PREFIX . "§cRed§b team won.");
            }
            $this->restore($game);
        }
        if ($this->games[$game]["BlueScore"] - $this->games[$game]["RedScore"] == 32) {
            foreach ($this->getServer()->getWorldManager()->getWorldByName($this->games[$game]["Arena"])->getPlayers() as $player) {
                $player->sendMessage($this->PREFIX . " You got 4 coins for participation.");
                $player->getInventory()->clearAll();
                $player->setNameTag($player->getDisplayName());
                $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                $player->sendMessage($this->PREFIX . "§bBlue team won.");
            }
            $this->games[$game]["Status"] = "JOINABLE";
            $this->restore($game);
        }
    }
    public function Second() {
        $this->SignUpdate();
        foreach ($this->games as $game => $value) {
            $Arena = $value["Arena"];
            $level = $this->getServer()->getWorldManager()->getWorldByName($Arena);
            if ($this->getServer()->getWorldManager()->isWorldLoaded($Arena)) {
                $Status = $value["Status"];
                $RedScore = $value["RedScore"];
                $BlueScore = $value["BlueScore"];
                if ($this->games[$this->getGameByLevel($Arena) ]["Status"] == "INGAME") {
                    foreach ($this->getServer()->getWorldManager()->getWorldByName($Arena)->getPlayers() as $player) {
                        if ($this->getTeam($player) == "Red") {
                            $player->sendPopup("§cRed: " . $RedScore . "    §bBlue: " . $BlueScore);
                        }
                        if ($this->getTeam($player) == "Blue") {
                            $player->sendPopup("§bBlue: " . $BlueScore . "    §cRed: " . $RedScore);
                        }
                    }
                } elseif ($Status == "JOINABLE") {
                    foreach ($this->getServer()->getWorldManager()->getWorldByName($Arena)->getPlayers() as $player) {
                        $player->sendPopup("§eWaiting for players...   §d" . count($this->getServer()->getWorldManager()->getWorldByName($Arena)->getPlayers()) . " / " . $this->MAX);
                    }
                    // SEND PLAYERS COUNTDOWN START MESSAGES
                    
                } elseif (is_numeric($Status) && $Status > 5) {
                    $this->games[$game]["Status"]-= 1;
                    if ($Status < 16) {
                        $c = "§c";
                    } else {
                        $c = "§a";
                    }
                    foreach ($level->getPlayers() as $player) {
                        $player->sendPopup("§7Starts in " . $c . gmdate("i.s", $Status - 5));
                    }
                } elseif (is_numeric($Status) && $Status < 6) {
                    if (count($this->getServer()->getWorldManager()->getWorldByName($Arena)->getPlayers()) < $this->MAX && $Status == 5) {
                        $this->StartGame($Arena);
                    }
                    if ($Status == 1) {
                        $this->games[$game]["Status"] = "zero";
                    } else {
                        $this->games[$game]["Status"]-= 1;
                    }
                    foreach ($level->getPlayers() as $player) {
                        $player->sendPopup("§7Starts in §b" . $Status . " §7Seconds");
                    }
                } elseif ($Status == "zero") {
                    $this->games[$game]["Status"] = "INGAME";
                    foreach ($level->getPlayers() as $player) {
                        $player->sendPopup("§dGame has started!");
                        $player->sendMessage($this->PREFIX . "Game has started!");
                    }
                }
            }
        }
    }

    /**
     * @param $game
     */
    public function restore($game): void
    {
        $this->games[$game]["RedScore"] = 0;
        $this->games[$game]["BlueScore"] = 0;
        $this->deleteDirectory($this->getServer()->getDataPath() . "worlds/" . $this->games[$game]["Arena"]);
        $this->copymap($this->getDataFolder() . "maps/TW-BACKUP", $this->getServer()->getDataPath() . "worlds/" . $this->games[$game]["Arena"]);
    }
	
    public function copymap($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if ($file != "." && $file != "..") {
                if (is_dir($src . "/" . $file)) {
                    $this->copymap($src . "/" . $file, $dst . "/" . $file);
                } else {
                    copy($src . "/" . $file, $dst . "/" . $file);
                }
            }
        }
        closedir($dir);
    }
	
    public function deleteDirectory($dirname)
    {
        if (is_dir($dirname)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirname, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) {
                    //rmdir($file->getRealPath());
                    if (is_dir($dirname . DIRECTORY_SEPARATOR . "region")) {
                        if ($handle = opendir($dirname . DIRECTORY_SEPARATOR . "region")) {
                            while (false !== ($entry = readdir($handle))) {
                                if ($entry !== "." && $entry !== "..") {
                                    if (empty($entry) || !file_exists($entry)) {
                                        return true; // No such file/folder exists.
                                        
                                    } elseif (is_file($entry) || is_link($entry)) {
                                        return @unlink($entry); // Delete file/link.
                                        
                                    }
                                }
                            }
                            closedir($handle);
                            rmdir($dirname . DIRECTORY_SEPARATOR . "region");
                        }
                    }
                }
                if ($file->isFile()) {
                    unlink($file->getRealPath());
                }
            }
            rmdir($dirname);
        }
        return null;
    }
}
