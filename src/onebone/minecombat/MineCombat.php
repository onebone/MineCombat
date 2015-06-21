<?php 

/**
 *   Copyright (C) 2015 onebone
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace onebone\minecombat;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\scheduler\AsyncTask;

use onebone\minecombat\grenade\BaseGrenade;
use onebone\minecombat\gun\BaseGun;
use onebone\minecombat\gun\Pistol;
use onebone\minecombat\grenade\FragmentationGrenade;
use onebone\minecombat\task\GameStartTask;
use onebone\minecombat\task\GameEndTask;
use onebone\minecombat\task\TeleportTask;
use onebone\minecombat\task\PopupTask;

class MineCombat extends PluginBase implements Listener{
	const STAT_GAME_END = 0;
	const STAT_GAME_PREPARE = 1;
	const STAT_GAME_IN_PROGRESS = 2;
	
	const PLAYER_DEAD = 0;
	const PLAYER_ALIVE = 1;
	
	const TEAM_RED = 0;
	const TEAM_BLUE = 1;
	
	const GRENADE_ID = Item::SLIMEBALL;
	const GUN_ID = Item::MELON_STEM;

	private $rank, $players, $score, $status, $spawnPos = null, $nextLevel = null, $level, $killDeath;
	
	/** @var $loadedGuns string[] */
	private $loadedGuns = [];
	
	private $gunCache = [];
	
	private static $obj;
	
	private static $colorArr = [
			"A" => TextFormat::BLUE,
			"B" => TextFormat::AQUA,
			"C" => TextFormat::GREEN,
			"D" => TextFormat::GOLD,
			"E" => TextFormat::RED
		];

	/**
	 * @return MineCombat
	 */
	public static function getInstance(){
		return self::$obj;
	}
	
	public function prepareGame(){
		$this->status = self::STAT_GAME_PREPARE;
		
		$this->getServer()->getScheduler()->scheduleDelayedTask(new GameStartTask($this), $this->getConfig()->get("prepare-time") * 20);
		
		$this->getServer()->broadcastMessage(TextFormat::AQUA."[MineCombat] Preparation time is started.");
		
		$pos = $this->getConfig()->get("spawn-pos");
		
		if($pos === []) return;
		$randKey = array_rand($pos);
		
		$randPos = $pos[$randKey];
		
		if(($level = $this->getServer()->getLevelByName($randPos["blue"][3])) instanceof Level){
			$this->spawnPos = [new Position($randPos["red"][0], $randPos["red"][1], $randPos["red"][2], $level), new Position($randPos["blue"][0], $randPos["blue"][1], $randPos["blue"][2], $level)];
			$this->nextLevel = $randKey;
		}else{
			$this->getLogger()->critical("Invalid level name was given.");
			$this->getServer()->shutdown();
		}
	}
	
	public function startGame(){
		if(count($this->getServer()->getOnlinePlayers()) < 1){ ///// TODO: CHANGE HERE ON RELEASE
			$this->getServer()->broadcastMessage(TextFormat::YELLOW."Player is not enough to start the match. Preparation time is going longer...");
			$this->getServer()->getScheduler()->scheduleDelayedTask(new GameStartTask($this), $this->getConfig()->get("prepare-time") * 20);
			return;
		}
		
		$blue = $red = 0;
		
		$this->status = self::STAT_GAME_IN_PROGRESS;
		
		$online = $this->getServer()->getOnlinePlayers();
		shuffle($online);
		foreach($online as $player){		
			if($blue < $red){
				$this->players[$player->getName()][2] = self::TEAM_BLUE;
				
				if(isset($this->level[$player->getName()])){
					$level = floor(($this->level[$player->getName()] / 10000));
					$player->setNameTag("Lv.".$level.TextFormat::BLUE.$player->getName());
				}else{
					$player->setNameTag(TextFormat::BLUE.$player->getName());
				}
				
				$player->sendMessage("[MineCombat] You are ".TextFormat::BLUE."BLUE".TextFormat::RESET." team.");
				if(isset($this->players[$player->getName()][0])){
					$this->players[$player->getName()][0]->setColor([40, 45, 208]);
				}
				
				++$blue;
			}else{
				$this->players[$player->getName()][2] = self::TEAM_RED;
				
				if(isset($this->level[$player->getName()])){
					$level = floor(($this->level[$player->getName()] / 10000));
					$player->setNameTag("Lv.".$level.TextFormat::RED.$player->getName());
				}else{
					$player->setNameTag(TextFormat::RED.$player->getName());
				}
				
				$player->sendMessage("[MineCombat] You are ".TextFormat::RED."RED".TextFormat::RESET." team.");
				if(isset($this->players[$player->getName()][0])){
					$this->players[$player->getName()][0]->setColor([247, 2, 9]);
				}
				
				++$red;
			}
			$this->killDeath[0][$player->getName()] = 0;
			$this->killDeath[1][$player->getName()] = 1;
			
			$this->teleportToSpawn($player);
			$player->setHealth(20);
			
			if(isset($this->players[$player->getName()][0])){
				$this->players[$player->getName()][0]->setAmmo($this->players[$player->getName()][0]->getDefaultAmmo());
			}
			$this->players[$player->getName()][3] = time();
		}
		
		$this->resetGunCache($player);
		
		$this->score = [0, 0];
		
		$this->getServer()->getScheduler()->scheduleDelayedTask(new GameEndTask($this), $this->getConfig()->get("game-time") * 20);
		
		$this->getServer()->broadcastMessage(TextFormat::GREEN."[MineCombat] Game is started. Kill as much as enemies and get more scores.");
	}
	
	public function endGame(){
		$this->status = self::STAT_GAME_END;
		
		$winner = TextFormat::YELLOW."TIED".TextFormat::RESET;
		if($this->score[self::TEAM_RED] > $this->score[self::TEAM_BLUE]){
			$winner = TextFormat::RED."RED".TextFormat::RESET." team win";
		}elseif($this->score[self::TEAM_BLUE] > $this->score[self::TEAM_RED]){
			$winner = TextFormat::BLUE."BLUE".TextFormat::RESET." team win";
		}
		
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$player->setNameTag($player->getName());
		}
		$this->getServer()->broadcastMessage(TextFormat::GREEN."[MineCombat] Game has been finished. ".$winner);
		
		$this->prepareGame();
		
		$this->gunCache = [];
	}
	
	public function isEnemy($player1, $player2){
		if(isset($this->players[$player1]) and isset($this->players[$player2])){
			return ($this->players[$player1][2] !== $this->players[$player2][2]);
		}
		return false;
	}

	/**
	 * @param Player|string $player
	 *
	 * @return BaseGun|null
	 */
	public function getGunByPlayer($player){
		if($player instanceof Player){
			$player = $player->getName();
		}
		
		if(isset($this->players[$player][0])){
			return $this->players[$player][0];
		}
		return null;
	}
	
	public function broadcastPopup($message){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$player->sendTip($message);
		}
	}
	
	public function getPlayersCountOnTeam($team){
		$ret = 0;
		foreach($this->players as $stats){
			if($stats[2] === $team){
				$ret++;
			}
		}
		return $ret;
	}
	
	public function teleportToSpawn(Player $player){
		if($this->spawnPos === null) return;
		$team = $this->players[$player->getName()][2];
		switch($team){
			case self::TEAM_BLUE:
			$player->teleport($this->spawnPos[1]);
			break;
			default: // RED team or not decided
			$player->teleport($this->spawnPos[0]);
			break;
		}
	}
	
	public function showPopup(){
		if($this->status === self::STAT_GAME_IN_PROGRESS){
			foreach($this->getServer()->getOnlinePlayers() as $player){
				if(!isset($this->players[$player->getName()])) continue;
				if($this->players[$player->getName()][2] === self::TEAM_RED){
					$popup = TextFormat::RED."RED TEAM\n".TextFormat::WHITE."Scores: ".TextFormat::RED.($this->score[self::TEAM_RED]).TextFormat::WHITE." / ".TextFormat::BLUE.($this->score[self::TEAM_BLUE].TextFormat::WHITE." / xp : ".TextFormat::YELLOW.$this->level[$player->getName()]);
				}else{
					$popup = (TextFormat::BLUE."BLUE TEAM\n".TextFormat::WHITE."Scores: ".TextFormat::BLUE.$this->score[self::TEAM_BLUE].TextFormat::WHITE." / ".TextFormat::RED.$this->score[self::TEAM_RED].TextFormat::WHITE." / xp : ".TextFormat::YELLOW.$this->level[$player->getName()]);
				}
				$ammo = "";
				$gun = "";
				if(isset($this->players[$player->getName()][0])){
					$ammo = $this->players[$player->getName()][0]->getLeftAmmo();
					if($ammo <= 0){
						$ammo = TextFormat::RED.$ammo;
					}
					$gun = $this->getClassColor($this->players[$player->getName()][0]->getClass()).$this->players[$player->getName()][0]->getName();
				}
				$popup .= "\n".TextFormat::RESET."Weapon : ".$gun.TextFormat::RESET."   Ammo: ".TextFormat::AQUA.$ammo;
				$player->sendPopup($popup);
			}
		}else{
			foreach($this->getServer()->getOnlinePlayers() as $player){
				$levelStr = "";
				if($this->nextLevel !== null){
					$levelStr = "\nNext map: ".TextFormat::AQUA.$this->nextLevel;
				}
				$player->sendPopup(TextFormat::GREEN."Preparation in progress".$levelStr);
			}
		}
	}

	public static function getClassColor($class){		
		return (isset(self::$colorArr[$class]) === true) ? self::$colorArr[$class] : TextFormat::GRAY;
	}
	
	public function submitAsyncTask(AsyncTask $task){
		$this->getServer()->getScheduler()->scheduleAsyncTask($task);
	}
	
	public function loadGun($path){
		$phar = new \Phar($path);
		if(isset($phar["gun_info.yml"])){
			$info = $phar["gun_info.yml"];
			if($info instanceof \PharFileInfo){
				$file = "phar://$path";
				
				$gun_info = yaml_parse($info->getContent());
				$this->getServer()->getLoader()->addPath($file."/src");
				
				$main = $gun_info["main"];
				$item = $gun_info["item"];
				$name = $gun_info["name"];
				$author = $gun_info["author"];
				$this->getLogger()->info("Custom weapon ".TextFormat::AQUA.$name.TextFormat::RESET." by ".TextFormat::GOLD.$author.TextFormat::RESET." is being loaded.");
				
				if(isset($this->loadedGuns[$item])){
					$this->getLogger()->info($path.": Already item has been registered.");
					return false;
				}
				
				if(class_exists($main, true)){
					$gun = new \ReflectionClass($main);
					if(!$gun->isSubclassOf("\\onebone\\minecombat\\gun\\BaseGun")){
						$this->getLogger()->warning($path." is not valid gun! Aborting register!");
						return false;
					}
					
					$main::init();
					$this->loadedGuns[$item] = $main;
					return true;
				}else{
					$this->getLogger()->warning($path." is not valid gun! Aborting register!");
					return false;
				}
			}else{
				$this->getLogger()->warning($path.": Not valid information");
			}
		}
		return false;
	}
	
	public function loadGuns($directory){
		if(!is_dir($directory)) return false;
		$cnt = 0;
		$directory .= "/";
		foreach(new \RegexIterator(new \DirectoryIterator($directory), "/\\.phar$/i") as $file){
			if($file !== "." and $file !== ".."){
				if($this->loadGun($directory.$file)){
					++$cnt;
				}
			}
		}
		$this->getLogger()->notice($cnt." custom weapons are detected!");
	}
	
	public function onEnable(){
		self::$obj = $this;
		
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder());
		}
		if(!file_exists($this->getDataFolder()."guns")){
			mkdir($this->getDataFolder()."guns");
		}
		
		if(!is_file($this->getDataFolder()."rank.dat")){
			file_put_contents($this->getDataFolder()."rank.dat", serialize([]));
		}
		$this->rank = unserialize(file_get_contents($this->getDataFolder()."rank.dat"));
		
		if(!is_file($this->getDataFolder()."level.dat")){
			file_put_contents($this->getDataFolder()."level.dat", serialize([]));
		}
		$this->level = unserialize(file_get_contents($this->getDataFolder()."level.dat"));
		
		if(!is_file($this->getDataFolder()."kill_death.dat")){
			file_put_contents($this->getDataFolder()."kill_death.dat", serialize([]));
		}
		$this->killDeath = unserialize(file_get_contents($this->getDataFolder()."kill_death.dat"));
		
		$this->players = [];
		
		$this->saveDefaultConfig();
		
		$spawnPos = $this->getConfig()->get("spawn-pos");
		
		$cnt = 0;
		foreach($spawnPos as $key => $data){
			if(!isset($data["blue"]) or !isset($data["red"])){
				unset($spawnPos[$key]);
				++$cnt;
			}
		}
		if($cnt > 0){
			$this->getLogger()->warning("$cnt positions are not set correctly.");
		}
		if($spawnPos !== [] and $spawnPos !== null){
			$this->getLogger()->notice(count($spawnPos)." of battle positions have been found.");
			$this->prepareGame();
		}else{
			$this->getLogger()->warning("Set the spawn position of each team by /spawnpos and restart server to start the match.");
			return;
		}
		
		$this->loadedGuns = [
			/*"259:0" => "\\onebone\\minecombat\\gun\\FlameThrower",
			"352:0" => "\\onebone\\minecombat\\gun\\Shotgun",
			"338:0" => "\\onebone\\minecombat\\gun\\Bazooka",*/
			"105:0" => "\\onebone\\minecombat\\gun\\Pistol"
		];
		$this->loadGuns($this->getDataFolder()."guns");
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new PopupTask($this), 10);
	}
	
	public function onDisable(){
		file_put_contents($this->getDataFolder()."rank.dat", serialize($this->rank));
		file_put_contents($this->getDataFolder()."level.dat", serialize($this->level));
		file_put_contents($this->getDataFolder()."kill_death.dat", serialize($this->killDeath));
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $params){
		switch($command->getName()){
			case "rank":
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."Please run this command in-game.");
				return true;
			}
			
			$data = $this->killDeath[0];
			
			arsort($data);
			
			$cnt = 0;
			$send = "Your status : ".TextFormat::YELLOW.$this->killDeath[0][$sender->getName()].TextFormat::WHITE."kills/".TextFormat::YELLOW.$this->killDeath[1][$sender->getName()].TextFormat::WHITE."deaths\n--------------------\n";
			foreach($data as $player => $datam){
				$send .= TextFormat::GREEN.$player.TextFormat::WHITE." ".TextFormat::YELLOW.$datam.TextFormat::WHITE."kills/".TextFormat::YELLOW.$this->killDeath[1][$player].TextFormat::WHITE."deaths\n";
				if($cnt >= 5){
					break;
				}
				++$cnt;
			}
			$sender->sendMessage($send);
			return true;
			case "spawnpos":
			$sub = strtolower(array_shift($params));
			switch($sub){
				case "blue":
				case "b":
				if(!$sender instanceof Player){
					$sender->sendMessage(TextFormat::RED."Please run this command in-game.");
					return true;
				}
				
				$name = array_shift($params);
				if(trim($name) === ""){
					$sender->sendMessage(TextFormat::RED."Usage: /spawnpos blue <name>");
					return true;
				}
				
				$config = $this->getConfig()->get("spawn-pos");
				if(isset($config[$name]["blue"])){
					$sender->sendMessage(TextFormat::RED."$name already exists.");
					return true;
				}
				$loc = [
					$sender->getX(), $sender->getY(), $sender->getZ(), $sender->getLevel()->getFolderName()
				];
				$config[$name]["blue"] = $loc;
				$this->getConfig()->set("spawn-pos", $config);
				$this->getConfig()->save();
				$sender->sendMessage("[MineCombat] Spawn position of BLUE team set.");
				return true;
				case "r":
				case "red":
				if(!$sender instanceof Player){
					$sender->sendMessage(TextFormat::RED."Please run this command in-game.");
					return true;
				}
				
				$name = array_shift($params);
				if(trim($name) === ""){
					$sender->sendMessage(TextFormat::RED."Usage: /spawnpos red <name>");
					return true;
				}
				
				$config = $this->getConfig()->get("spawn-pos");
				if(isset($config[$name]["red"])){
					$sender->sendMessage(TextFormat::RED."$name already exists.");
					return true;
				}
				
				$loc = [
					$sender->getX(), $sender->getY(), $sender->getZ(), $sender->getLevel()->getFolderName()
				];
				$config[$name]["red"] = $loc;
				$this->getConfig()->set("spawn-pos", $config);
				$this->getConfig()->save();
				$sender->sendMessage("[MineCombat] Spawn position of RED team set.");
				return true;
				case "remove":
				$name = array_shift($params);
				if(trim($name) === ""){
					$sender->sendMessage(TextFormat::RED."Usage: /spawnpos blue <name>");
					return true;
				}
				
				$config = $this->getConfig()->get("spawn-pos");
				$config[$name] = null;
				unset($config[$name]);
				
				$this->getConfig()->set("spawn-pos", $config);
				$this->getConfig()->save();
				return true;
				case "list":
				$list = implode(", ", array_keys($this->getConfig()->get("spawn-pos")));
				$sender->sendMessage("Positions list: \n".$list);
				return true;
				default:
				$sender->sendMessage("Usage: ".$command->getUsage());
			}
			return true;
			case "momap":
			$name = array_shift($params);
			
			if(trim($name) === ""){
				$sender->sendMessage(TextFormat::RED."Usage: ".$command->getUsage());
				return true;
			}
			
			if($this->status === self::STAT_GAME_IN_PROGRESS){
				$sender->sendMessage(TextFormat::RED."Game is already in progress. Select map after the game is ended.");
				return true;
			}
			
			$pos = $this->getConfig()->get("spawn-pos");
			if(!isset($pos[$name])){
				$sender->sendMessage("Map ".TextFormat::RED.$name.TextFormat::WHITE." exist!");
				return true;
			}else{
				$selectedPos = $pos[$name];
				if(($level = $this->getServer()->getLevelByName($selectedPos["blue"][3])) instanceof Level){
					$this->spawnPos = [new Position($selectedPos["red"][0], $selectedPos["red"][1], $selectedPos["red"][2], $level), new Position($selectedPos["blue"][0], $selectedPos["blue"][1], $selectedPos["blue"][2], $level)];
					$this->nextLevel = $name;
					$sender->sendMessage("Map was selected to ".TextFormat::AQUA.$name);
				}else{
					$this->getLogger()->critical("Invalid level name was given.");
					$this->getServer()->shutdown();
				}
			}
			return true;
		}

		return true;
	}
	
	public function onInteract(PlayerInteractEvent $event){
		if($this->status === self::STAT_GAME_IN_PROGRESS){
			$player = $event->getPlayer();
			$item = $player->getInventory()->getItemInHand();
			if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
				if($item->getId().":".$item->getDamage() === $this->players[$player->getName()][0]->getGunItem()){
					$this->players[$player->getName()][0]->shoot();
				}
			}elseif($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR){
				if($item->getId() === self::GRENADE_ID){
					$this->players[$player->getName()][1]->lob($event->getTouchVector());
					$player->getInventory()->removeItem(Item::get(self::GRENADE_ID, 0, 1));
				}
			}
			if($player->getGamemode() !== 1){
				$event->setCancelled();
			}
		}
	}
	
	public function onLoginEvent(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		
		if(!isset($this->level[$player->getName()])){
			$this->level[$player->getName()] = 0;
		}
		if(!isset($this->killDeath[0][$player->getName()])){
			$this->killDeath[0][$player->getName()] = 0;
			$this->killDeath[1][$player->getName()] = 0;
		}
		
		$this->players[$player->getName()] = [
			new Pistol($this, $player, array(175, 175, 175)),
			new FragmentationGrenade($this, $player),
			-1,
			time()
		];
	}
	
	public function onJoinEvent(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		
		if($this->status === self::STAT_GAME_IN_PROGRESS){
			$redTeam = $this->getPlayersCountOnTeam(self::TEAM_RED);
			$blueTeam = $this->getPlayersCountOnTeam(self::TEAM_BLUE);
			if($redTeam > $blueTeam){
				$team = self::TEAM_BLUE;
				
				$level = floor(($this->level[$player->getName()] / 10000));
				$player->setNameTag("Lv.".$level.TextFormat::BLUE.$player->getName());
				
				$this->players[$player->getName()][0]->setColor([40, 45, 208]);
			}else{
				$team = self::TEAM_RED;
				
				$level = floor(($this->level[$player->getName()] / 10000));
				$player->setNameTag("Lv.".$level.TextFormat::RED.$player->getName());
				
				$this->players[$player->getName()][0]->setColor([247, 2, 9]);
			}
			$this->players[$player->getName()][2] = $team;
			
			$this->teleportToSpawn($player);
			$player->sendMessage("[MineCombat] You are ".($team === self::TEAM_RED ? TextFormat::RED."RED" : TextFormat::BLUE."BLUE").TextFormat::WHITE." team. Kill as much as enemies and get more scores.");
		}else{
			$player->sendMessage("[MineCombat] It is preparation time. Please wait for a while to start the match.");
		}
	}
	
	public function onQuitEvent(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		
		if($player->loggedIn and isset($this->players[$player->getName()])){
			unset($this->players[$player->getName()]);
		}
	}
	
	public function onDeath(PlayerDeathEvent $event){
		$player = $event->getEntity();

		if($this->status === self::STAT_GAME_IN_PROGRESS){
			$items = $event->getDrops();
			foreach($items as $key => $item){
				if(!isset($this->loadedGuns[$item->getId().":".$item->getDamage()])){
					unset($items[$key]);
				}
			}
			$event->setDrops($items);
			$cause = $player->getLastDamageCause();

			if($cause !== null && $cause->getCause() == EntityDamageEvent::CAUSE_FALL){
				if($this->players[$player->getName()][2] === self::TEAM_BLUE){
					$playerColor = TextFormat::BLUE;
					$damagerColor = TextFormat::RED;
					$this->score[self::TEAM_RED]++;
				}else{
					$playerColor = TextFormat::RED;
					$damagerColor = TextFormat::BLUE;
					$this->score[self::TEAM_BLUE]++;
				}
				$firstKill = "";
				if($this->score[self::TEAM_BLUE] + $this->score[self::TEAM_RED] <= 1){
					$firstKill = TextFormat::YELLOW."FIRST BLOOD\n".TextFormat::WHITE;
				}
				$this->broadcastPopup($firstKill.$playerColor.$player->getName().$damagerColor." SUICIDED");
			}

			if(!($cause instanceof EntityDamageByEntityEvent)){
				return;
			}

			if($cause !== null and $cause->getCause() === 15){
				$damager = $cause->getDamager();
				if($damager instanceof Player){
					if($this->players[$damager->getName()][2] === self::TEAM_BLUE){
						$damagerColor = TextFormat::BLUE;
						$playerColor = TextFormat::RED;
						$this->score[self::TEAM_BLUE]++;
					}else{
						$damagerColor = TextFormat::RED;
						$playerColor = TextFormat::BLUE;
						$this->score[self::TEAM_RED]++;
					}
					$firstKill = "";
					if($this->score[self::TEAM_BLUE] + $this->score[self::TEAM_RED] <= 1){
						$firstKill = TextFormat::YELLOW."FIRST BLOOD\n".TextFormat::WHITE;
					}
					$this->broadcastPopup($firstKill.$damagerColor.$damager->getName().TextFormat::WHITE." -> ".$playerColor.$player->getName());
					
					++$this->killDeath[0][$damager->getName()];
					++$this->killDeath[1][$player->getName()];
					
					$this->level[$damager->getName()] += ($damager->getHealth() * 5);
					$level = floor(($this->level[$damager->getName()] / 10000));
					$damager->setNameTag("Lv.".$level.$damagerColor.$damager->getName());
				}
			}elseif($cause !== null and $cause->getCause() === 16){
				$damager = $cause->getDamager();
				if($damager instanceof Player){
					if($this->players[$damager->getName()][2] === self::TEAM_BLUE){
						$damagerColor = TextFormat::BLUE;
						$playerColor = TextFormat::RED;
						$this->score[self::TEAM_BLUE]++;
					}else{
						$damagerColor = TextFormat::RED;
						$playerColor = TextFormat::BLUE;
						$this->score[self::TEAM_RED]++;
					}
					$firstKill = "";
					if($this->score[self::TEAM_BLUE] + $this->score[self::TEAM_RED] <= 1){
						$firstKill = TextFormat::YELLOW."FIRST BLOOD\n".TextFormat::WHITE;
					}
					$this->broadcastPopup($firstKill.$damagerColor.$damager->getName().TextFormat::WHITE." -O-> ".$playerColor.$player->getName());
					
					++$this->killDeath[0][$damager->getName()];
					++$this->killDeath[1][$player->getName()];
					
					$this->level[$damager->getName()] += ($damager->getHealth() * 5);
					$level = floor(($this->level[$damager->getName()] / 10000));
					$damager->setNameTag("Lv.".$level.$damagerColor.$damager->getName());
				}
			}
			$event->setDeathMessage("");
		}
	}
	
	public function onRespawn(PlayerRespawnEvent $event){
		$player = $event->getPlayer();
		
		$this->resetGunCache($player);
		
		$this->getServer()->getScheduler()->scheduleDelayedTask(new TeleportTask($this, $player->getName()), 5);
		
		if(!$player->getInventory()->contains(Item::get(self::GRENADE_ID))){
			$player->getInventory()->addItem(Item::get(self::GRENADE_ID, 0, 2));
		}
		
		foreach($this->gunCache[$player->getName()] as $item => $gun){
			$item = explode(":", $item);
			$item = Item::get($item[0], $item[1]);
			
			if(!$player->getInventory()->contains($item)){
				if($gun->canGive($player)){
					$player->getInventory()->addItem($item);
				}
			}
		}
		
		$this->players[$player->getName()][3] = time();
		if(isset($this->players[$player->getName()][0])){
			$this->players[$player->getName()][0]->setAmmo($this->players[$player->getName()][0]->getDefaultAmmo());
		}
	}
	
	public function onDamage(EntityDamageEvent $event){
		$player = $event->getEntity();
		if($player instanceof Player){
			if($this->status !== self::STAT_GAME_IN_PROGRESS){
				$event->setCancelled();
				return;
			}
			if((time() - $this->players[$player->getName()][3]) < 3){
				$event->setCancelled();
				return;
			}
			if($event instanceof EntityDamageByEntityEvent){
				$damager = $event->getDamager();
				$event->setKnockBack(0.2);
				if($damager instanceof Player){
					if(!$this->isEnemy($player->getName(), $damager->getName())){
						$event->setCancelled();
					}
				}
			}
		}
	}
	
	public function onDropItem(PlayerDropItemEvent $event){
		$event->setCancelled();
	}
	
	public function onPickup(InventoryPickupItemEvent $event){
		$player = $event->getInventory()->getHolder();
		
		if($player instanceof Player){
			$item = $event->getItem()->getItem();
			if(isset($this->loadedGuns[$item->getId().":".$item->getDamage()])){
				$gun = $this->players[$player->getName()][0];
				if($gun->getGunItem() === $item->getId().":".$item->getDamage()){
					$gun->addAmmo($gun->getMagazineAmmo());
					$event->getItem()->kill();
				}
				$event->setCancelled();
			}else{
				$event->getItem()->kill();
			}
		}
	}
	
	public function onItemHeld(PlayerItemHeldEvent $event){
		$player = $event->getPlayer();
		
		$item = $event->getItem();
		if(isset($this->loadedGuns[$item->getId().":".$item->getDamage()])){
			$teamColor = [
				self::TEAM_BLUE => [40, 45, 208],
				self::TEAM_RED => [247, 2, 9],
				-1 => [175, 175, 175]
			];
			if(isset($this->gunCache[$player->getName()][$item->getId().":".$item->getDamage()])){
				$this->players[$player->getName()][0] = $this->gunCache[$player->getName()][$item->getId().":".$item->getDamage()];
			}else{
				$this->players[$player->getName()][0] = new $this->loadedGuns[$item->getId().":".$item->getDamage()]($this, $player, $teamColor[$this->players[$player->getName()][2]]);
				$this->gunCache[$player->getName()][$item->getId().":".$item->getDamage()] = $this->players[$player->getName()][0];
			}
		}
	}

	public function giveGun($player){
		switch($this->players[$player][2]){
			case self::TEAM_RED: $color = [247, 2, 9]; break;
			case self::TEAM_BLUE: $color = [40, 45, 208]; break;
				break;
			default: return false;
		}

		$gun->setColor($color);
		$this->players[$player][0] = $gun;
		return true;
	}

	public function giveGrenade($playerName, BaseGrenade $grenade){
		$this->players[$playerName][1] = $grenade;
	}
	
	private function resetGunCache(Player $player){
		$teamColor = [
				self::TEAM_BLUE => [40, 45, 208],
				self::TEAM_RED => [247, 2, 9],
				-1 => [175, 175, 175]
			];
		
		$this->gunCache[$player->getName()] = [];
		foreach($this->loadedGuns as $item => $class){
			$this->gunCache[$player->getName()][$item] = new $class($this, $player, $teamColor[$this->players[$player->getName()][2]]);
		}
	}

	public function decreaseXP($playerName, $amount){
		if($this->level[$playerName] >= $amount) {
			$this->level[$playerName] -= $amount;
			return true;
		}else{
			return false;
		}
	}
	
	public function getXP($player){
		if(isset($this->level[$player])){
			return $this->level[$player];
		}
		return false;
	}

	public function getTeam($playerName){
		return $this->players[$playerName][2];
	}

	public function getStatus(){
		return $this->status;
	}
	
	public function getLoadedGuns(){
		return $this->loadedGuns;
	}
}
