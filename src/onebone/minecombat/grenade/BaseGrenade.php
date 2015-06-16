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

use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\Server;

use onebone\minecombat\task\UpdateTask;
use onebone\minecombat\MineCombat;

abstract class BaseGrenade{
	/** @var Player */
	private $player;

	/** @var MineCombat */
	private $plugin;
	
	private $motionX, $motionY, $motionZ, $x, $y, $z, $scheduleId = -1;
	
	public function __construct(MineCombat $plugin, Player $player){
		$this->player = $player;
		$this->plugin = $plugin;
	}
	
	public function lob(Vector3 $aimPos){
		if(Server::getInstance()->getScheduler()->isQueued($this->scheduleId)){
			return;
		}
		/*new Double("", -sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)),
		new Double("", -sin($this->pitch / 180 * M_PI)),
		new Double("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))*/
		$aimPos = $aimPos->multiply(1.5);
		$this->motionX = $aimPos->x;//-sin($this->player->yaw / 180 * M_PI) * cos($this->player->pitch / 180 * M_PI);
		$this->motionY = $aimPos->y;//-sin($this->player->pitch / 180 * M_PI);
		$this->motionZ = $aimPos->z;//cos($this->player->yaw / 180 * M_PI) * cos($this->player->pitch / 180 * M_PI);
		
		$this->x = $this->player->getX();
		$this->y = $this->player->getY() + 1;
		$this->z = $this->player->getZ();
		
		$gravity = $this->getGravity();
		
		$this->scheduleId = Server::getInstance()->getScheduler()->scheduleRepeatingTask(new UpdateTask($this->plugin, $this), 1)->getTaskId();
	}
	
	public function update(){
		$this->motionY -= $this->getGravity();
		
		$this->x += $this->motionX;
		$this->y += $this->motionY;
		$this->z += $this->motionZ;
		
		$pos = new Position($this->x, $this->y, $this->z, $this->getPlayer()->getLevel());
		$particle = new CriticalParticle(new Vector3($this->x, $this->y, $this->z));
		$this->getPlayer()->getLevel()->addParticle($particle);
		
		$block = $this->getPlayer()->getLevel()->getBlock($pos);
		if($block->getId() !== 0 or $this->y <= 0){
			$this->onCollide($pos);
			if(Server::getInstance()->getScheduler()->isQueued($this->scheduleId)){
				Server::getInstance()->getScheduler()->cancelTask($this->scheduleId);
			}
		}
	}

	/**
	 * @return Player
	 */
	public function getPlayer(){
		return $this->player;
	}

	/**
	 * @return MineCombat
	 */
	public function getPlugin(){
		return $this->plugin;
	}
	
	abstract public function onCollide(Position $player);
	abstract public function getGravity();
}