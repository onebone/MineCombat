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

	public function onEnable(){
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder());
		}
		$this->initializeData();

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	private function initializeData(){
		if(!is_file($this->getDataFolder()."players.dat")){
			file_put_contents($this->getDataFolder()."players.dat", json_encode([]));
		}

		$this->players = [];
		$players = json_decode(file_get_contents($this->getDataFolder()."players.dat"), true);
		foreach($players as $player){
			$this->players[$player["name"]] = new PlayerContainer($player["name"], $player["xp"], $player["coins"]);
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$iusername = strtolower($player->getName());
		if(!isset($this->players[$iusername])){
			$this->players[$iusername] = new PlayerContainer($iusername);
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		if(isset($this->players[$iusername])){

		}
	}
}
