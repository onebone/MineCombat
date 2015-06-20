<?php

/*
 *   Copyright (C) 2015 Khinenw, onebone
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

use pocketmine\level\particle\SmokeParticle;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\Network;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use onebone\minecombat\MineCombat;
use pocketmine\Server;

class Bazooka extends BaseGun{
	private $lastShoot;

	const RANGE = 3;

	public function __construct(MineCombat $plugin, Player $player, $color = [175, 175, 175]){
		parent::__construct($plugin, $player, 30, 50, $color);
	}

	public function onShoot(){
		$this->lastShoot = microtime(true);
		$pk = new ExplodePacket();
		$pk->x = $this->player->getX();
		$pk->y = $this->player->getY();
		$pk->z = $this->player->getZ();
		$pk->radius = 10;
		$pk->records = [new Vector3($this->player->getX(), $this->player->getY() + 1.62, $this->player->getZ())];
		Server::broadcastPacket($this->getPlayer()->getLevel()->getChunkPlayers($this->player->getX() >> 4, $this->player->getZ() >> 4), $pk->setChannel(Network::CHANNEL_BLOCKS));
		$vec = $this->player->getDirectionVector()->multiply(0.9);
		$this->player->setMotion(new Vector3(- $vec->getX(), - $vec->getY(), - $vec->getZ()));
	}

	public function canShoot(){
		$time = microtime(true);
		return ($time - $this->lastShoot > 4);
	}

	public function onShot(Player $target){

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

			if($this->getPlugin()->isEnemy($this->getPlayer()->getName(), $entity->getName())){
				//cause : 15, damage : 15, knockback : 5
				$event = new EntityDamageByEntityEvent($this->getPlayer(), $entity, 15, 15, 5);
				$entity->attack($event->getFinalDamage(), $event);
			}
		}

		for($i = 0; $i < 100; $i++){
			$this->getPlayer()->getLevel()->addParticle(new SmokeParticle(new Vector3($pos->x + mt_rand(-self::RANGE, self::RANGE), $pos->y + mt_rand(-self::RANGE, self::RANGE), $pos->z + mt_rand(-self::RANGE, self::RANGE))));
		}
	}

	public function shoot(){
		if (!$this->getShoot()){

			if (!$this->canShoot()){
				return false;
			}

			if ($this->getLeftAmmo() <= 0){
				return false;
			}

			$this->setAmmo($this->getLeftAmmo() - 1);
			$this->onShoot();
			$this->setShoot(true);

			$this->calculateVector($this->player->getDirectionVector()->multiply(3), $this->player->getPosition(), 50, 1, $this->player->getEyeHeight());
		}

		return true;
	}

	public function calculateVector(Vector3 $vector, Position $position, $maxStep, $size, $yOffset){
		$motionX = $vector->getX();
		$motionY = $vector->getY();
		$motionZ = $vector->getZ();

		$x = $position->getX();
		$y = $position->getY() + $yOffset;
		$z = $position->getZ();

		$return = ["stack" => array(), "final" => null];
		$return["stack"] = array();

		for($i = 0; $i < $maxStep; $i++){
			$x += $motionX;
			$y += $motionY;
			$z += $motionZ;

			$aabb = new AxisAlignedBB($x - $size, $y - $size, $z - $size, $x + $size, $y + $size, $z + $size);

			$currentPos = new Position($x, $y, $z, $position->getLevel());

			array_push($return["stack"], $currentPos);

			$collidingEntities = $position->getLevel()->getCollidingEntities($aabb);

			foreach($collidingEntities as $entity){
				if($entity instanceof Player && $this->getPlugin()->isEnemy($this->player->getName(), $entity->getName())) {
					$return["final"] = $currentPos;
					$this->processBazookaShoot($return);
					return;
				}
			}

			$block = $position->getLevel()->getBlock($currentPos);
			if($block->getId() !== 0 || $y < 0){
				$return["final"] = $currentPos;
				$this->processBazookaShoot($return);
				return;
			}
		}

		$this->processBazookaShoot(null);
	}

	public function processBazookaShoot($returnVal){
		$this->setShoot(false);
		if(!isset($returnVal["stack"])){
			return;
		}

		foreach($returnVal["stack"] as $stackedPosition){
			$this->player->getLevel()->addParticle($this->getParticle($stackedPosition, $this->color[0], $this->color[1], $this->color[2]));
		}

		if(!isset($returnVal["final"])){
			return;
		}

		$this->explode($returnVal["final"]);
	}

	public function getDamage($distance){
		return 15;
	}

	public static function getName(){
		return "RPG-7";
	}

	public static function getClass(){
		return "A";
	}

	public static function getInstance(MineCombat $plugin, Player $player, $color){
		return new self($plugin, $player, $color);
	}

}