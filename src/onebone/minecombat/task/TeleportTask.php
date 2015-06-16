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

namespace onebone\minecombat\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;

use onebone\minecombat\MineCombat;

class TeleportTask extends PluginTask{
	private $player;
	
	public function __construct(MineCombat $plugin, $player){
		parent::__construct($plugin);
		$this->player = $player;
	}
	
	public function onRun($currentTick){
		if(($player = Server::getInstance()->getPlayerExact($this->player)) instanceof Player){
			$this->getOwner()->teleportToSpawn($player);
		}
	}
}