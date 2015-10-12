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

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\level\particle\DustParticle;

use onebone\minecombat\data\PlayerContainer;
use onebone\minecombat\gun\BaseGun;
use onebone\minecombat\gun\Pistol;
use onebone\minecombat\task\RepeatTask;

class MineCombat extends PluginBase implements Listener{
	const FORMAT = "%team\nScores: %score / %xpxp\nWeapon: %weapon, Ammo : %ammo/%allAmmo";

	/** @var PlayerContainer[] $players */
	private $players = [];
	/** @var int $currentGame */
	private $currentGame = 0;
	/** @var string[] $loadedGuns */
	private $loadedGuns = [];

	/**
	 * Returns gun class matching name
	 * @var string name
	 *
	 * @return string
	 */
	public function getGunByName($name){
		$iname = strtolower($name);
		foreach($this->loadedGuns as $gun){
			$reflection = new \ReflectionClass($gun);
			if(strtolower($reflection->getShortName()) === $iname){
				return $gun;
			}
		}
		return null;
	}

	public function onEnable(){
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder());
		}
		$this->initializeData();
		$this->saveDefaultConfig();

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new RepeatTask($this), 10, 10);
	}

	private function initializeData(){
		if(!is_file($this->getDataFolder()."players.json")){
			file_put_contents($this->getDataFolder()."players.json", json_encode([]));
		}

		$this->players = [];
		$players = json_decode(file_get_contents($this->getDataFolder()."players.json"), true);
		foreach($players as $player){
			$this->players[$player["name"]] = new PlayerContainer($player["name"], $player["xp"], $player["coins"]);
		}
	}

	public function tick(){
		// TODO: Implement checking game status
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if(!isset($this->players[strtolower($player->getName())])) continue;
			$data = $this->players[strtolower($player->getName())];
			$gun = $data->getCurrentGun();
			$player->sendPopup(str_replace(["%team", "%score", "%xp", "%weapon", "%ammo", "%allAmmo"], ["", "", $data->getXp(), $gun->getName(), $gun->getAmmo(), $gun->getAllAmmo()], self::FORMAT)); // TODO: Implement team, score
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$iusername = strtolower($player->getName());
		if(!isset($this->players[$iusername]) or !$this->players[$iusername] instanceof PlayerContainer){
			$this->players[$iusername] = new PlayerContainer($iusername);
		}

		if(!$this->players[$iusername]->getCurrentGun() instanceof BaseGun){
			$this->players[$iusername]->setCurrentGun(new Pistol($player->getName())); // TODO: Set different gun if others available
		}

		if($this->players[$iusername]->getLastGame() !== $this->currentGame){
			$gun = $this->players[$iusername]->getCurrentGun();
			$gun->setAllAmmo($gun->getDefaultAmmo());
			$gun->reload();
		}
	}

	public function onPlayerTouch(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();

		$xcos = cos(($player->yaw-90)/180 * M_PI);
		$zcos = sin(($player->yaw-90)/180 * M_PI);
		$pcos = cos(($player->pitch + 90)/180 * M_PI);

		$x_ = $player->getX();
		$y_ = $player->getY();
		$z_ = $player->getZ();
		for($o_=0; $o_<100;$o_++){
			$x = $x_ - ($o_ * $xcos);
			$y = $y_ + ($o_ * $pcos);
			$z = $z_ - ($o_ * $zcos);
			$player->getLevel()->setBlock(new \pocketmine\math\Vector3($x, $y, $z), \pocketmine\block\Block::get(1,0));
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		$iusername = strtolower($player->getName());

		if(isset($this->players[$iusername])){

		}
	}
}
