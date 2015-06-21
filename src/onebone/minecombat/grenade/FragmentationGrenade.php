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

namespace onebone\minecombat\grenade;

use pocketmine\level\particle\CriticalParticle;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\math\AxisAlignedBB;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\network\Network;

use onebone\minecombat\MineCombat;
use onebone\minecombat\task\ExplosionTask;

class FragmentationGrenade extends BaseGrenade{
	const RANGE = 4;
	
	public function __construct(MineCombat $plugin, Player $player){
		parent::__construct($plugin, $player);
	}
	
	public function onCollide(Position $pos){
		Server::getInstance()->getScheduler()->scheduleDelayedTask(new ExplosionTask($this->getPlugin(), $this, $pos), 40);
	}
	
	public function explode(Position $pos){
		$aabb = new AxisAlignedBB($pos->getX() - self::RANGE, $pos->getY() - self::RANGE, $pos->getZ() - self::RANGE, $pos->getX() + self::RANGE, $pos->getY() + self::RANGE, $pos->getZ() + self::RANGE);
		$nearbyEntities = $this->getPlayer()->getLevel()->getNearbyEntities($aabb, null);
		
		$pk = new ExplodePacket();
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->radius = 10;
		$pk->records = [new Vector3($pos->x, $pos->y, $pos->z)];
		Server::broadcastPacket($this->getPlayer()->getLevel()->getChunkPlayers($pos->x >> 4, $pos->z >> 4), $pk->setChannel(Network::CHANNEL_BLOCKS));
		
		foreach($nearbyEntities as $entity){
			if(!($entity instanceof Player)){
				continue;
			}

			if($this->getPlugin()->isEnemy($this->getPlayer()->getName(), $entity->getName())) {
				$event = new EntityDamageByEntityEvent($this->getPlayer(), $entity, 16, 15);
				$entity->attack($event->getFinalDamage(), $event);
			}
		}

		for($i = 0; $i < 100; $i++){
			$this->getPlayer()->getLevel()->addParticle(new CriticalParticle(new Vector3($pos->x + mt_rand(-self::RANGE, self::RANGE), $pos->y + mt_rand(-self::RANGE, self::RANGE), $pos->z + mt_rand(-self::RANGE, self::RANGE))));
		}
	}
	
	public function getGravity(){
		return 0.14;
	}

	public static function getClass(){
		return "D";
	}

	public static function getName(){
		return "Fragmentation Grenade";
	}

	public static function getInstance(MineCombat $plugin, Player $player){
		return new self($plugin, $player);
	}
}