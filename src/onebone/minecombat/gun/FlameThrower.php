<?php
/*   Copyright (C) 2015 Khinenw, onebone
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


use onebone\minecombat\MineCombat;
use onebone\minecombat\task\FlameTask;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\level\particle\FlameParticle;
use pocketmine\math\Vector3;
use pocketmine\network\Network;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\Player;
use pocketmine\Server;

class FlameThrower extends BaseGun{
	const BASE_DAMAGE = 10;

	private $lastShoot;
	public static $tasks = array();

	public function __construct(MineCombat $plugin, Player $player, $color = [175, 175, 175]){
		parent::__construct($plugin, $player, 10, 70, $color);
	}

	public static function getInstance(MineCombat $plugin, Player $player, $color){
		return new self($plugin, $player, $color);
	}

	public function onShoot(){
		$this->lastShoot = microtime(true);
	}

	public function canShoot(){
		$time = microtime(true);
		return ($time - $this->lastShoot > 0.3);
	}

	public function onShot(Player $target){
		if($this->getPlugin()->isEnemy($this->getPlayer()->getName(), $target->getName())){
			$distance = $this->getPlayer()->distance($target);

			$damage = $this->getDamage($distance);
			$target->attack($damage, new EntityDamageByEntityEvent($this->getPlayer(), $target, 15, $damage, 0));
			$this->setFlaming($target);
			if(!isset(self::$tasks[$target->getName()])) {
				$task = new FlameTask($this->getPlayer(), $target, $this->getPlugin(), 3);
				self::$tasks[$target->getName()] = $task;
				$handler = $this->getPlugin()->getServer()->getScheduler()->scheduleRepeatingTask($task, 1);
				$task->setHandler($handler);
			}
		}
	}

	public function getDamage($distance){
		return self::BASE_DAMAGE - $distance;
	}

	public function getParticle(Vector3 $position, $r, $g, $b){
		return new FlameParticle($position);
	}

	public function setFlaming(Player $target){
		$flags = (int) $target->getDataProperty(Player::DATA_FLAGS);
		$flags ^= 1 << Player::DATA_FLAG_ONFIRE;

		$dataProperty = [Player::DATA_FLAGS => [Player::DATA_TYPE_BYTE, $flags]];
		$pk = new SetEntityDataPacket();
		$pk->eid = $target->getId();
		$pk->metadata = $dataProperty;
		Server::broadcastPacket($this->getPlayer()->getLevel()->getPlayers(), $pk->setChannel(Network::CHANNEL_WORLD_EVENTS));
	}

	public static function getName(){
		return "Flame Thrower";
	}

	public static function getClass(){
		return "C";
	}
}