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

use onebone\minecombat\data\PlayerContainer;

class MineCombat extends PluginBase implements Listener{
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

	public function onPlayerJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$iusername = strtolower($player->getName());
		if(!isset($this->players[$iusername]) or !$this->players[$iusername] instanceof PlayerContainer){
			$this->players[$iusername] = new PlayerContainer($iusername);
		}

		$this->players[$iusername]->setCurrentGun(new Pistol($player->getName())); // TODO: Set different gun if others available

		if($this->players[$iusername]->getLastGame() !== $this->currentGame){
			$gun = $this->players[$iusername]->getCurrentGun();
			$gun->setAmmo($gun->getDefaultAmmo());
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		if(isset($this->players[$iusername])){

		}
	}
}
