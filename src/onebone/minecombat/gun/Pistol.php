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

namespace onebone\minecombat\gun;

use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use onebone\minecombat\MineCombat;

class Pistol extends BaseGun{
	private $lastShoot;
	
	public function __construct(MineCombat $plugin, Player $player, $color = [175, 175, 175]){
		parent::__construct($plugin, $player, 30, $color);
	}
	
	public function onShoot(){
		$this->lastShoot = microtime(true);
	}
	
	public function canShoot(){
		$time = microtime(true);
		return ($time - $this->lastShoot > 0.5);
	}
	
	public function onShot(Player $target){
		if($this->getPlugin()->isEnemy($this->getPlayer()->getName(), $target->getName())){
			$distance = $this->getPlayer()->distance($target);
			
			$damage = $this->getDamage($distance);
			$target->attack($damage, new EntityDamageByEntityEvent($this->getPlayer(), $target, 15, $damage, 0));
		}
	}
	
	public function getDamage($distance){
		return 5; // TODO: Damage by distance
	}
}