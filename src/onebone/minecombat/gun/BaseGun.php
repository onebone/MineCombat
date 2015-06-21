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
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\level\particle\DustParticle;

use onebone\minecombat\MineCombat;
use onebone\minecombat\task\ShootTask;

abstract class BaseGun{
	protected $player;

	/** @var MineCombat */
	private $plugin;

	private $ammo, $maxRange, $shoot = false;
	public $color;
	private static $defaultAmmo = 0;
	
	public function __construct(MineCombat $plugin, Player $player, $maxRange, $ammo = 50, $color = [175, 175, 175]){
		$this->plugin = $plugin;
		$this->player = $player;
		$this->ammo = $ammo;
		$this->color = $color;
		self::$defaultAmmo = $ammo;
		
		if($maxRange < 0){
			throw new \Exception("Max range of gun cannot be smaller than 0");
		}
		$this->maxRange = $maxRange;
	}

	public static function getInstance(MineCombat $plugin, Player $player, $color){
		return null;
	}

	public function setColor($color){
		$this->color = $color;
	}
	
	public function shoot(){
		
		
		if(!$this->shoot){
			$time = microtime(true);
			
			if(!$this->canShoot($time)) return false;	
			if($this->ammo <= 0){
				return false;
			}
			$this->ammo--;
			
			$this->onShoot();
			
			$this->shoot = true;
			
			$players = [];
			foreach($this->player->getLevel()->getPlayers() as $player){
				if($player->getName() === $this->player->getName()) continue;
				$players[$player->getName()] = [$player->getX(), $player->getY() + 1.62, $player->getZ()];
			}
			
			$thr = new ShootTask($this->player->getX(), $this->player->getY() + 1.62, $this->player->getZ(), $this->player->yaw, $this->player->pitch, $players, $this->getMaxRange(), $this->player->getName());
			
			$this->plugin->submitAsyncTask($thr);
		}

		return true;
	}
	
	public function processShoot($ret){
		$level = $this->player->getLevel();
		
		foreach($ret as $val){
			$vec = new Vector3($val[0], $val[1], $val[2]);

			if($level->getBlock(new Vector3($val[4], $val[5], $val[6]))->getId() !== 0){
				$this->shoot = false;
				return;
			}
			$level->addParticle($this->getParticle($vec, $this->color[0], $this->color[1], $this->color[2]));
			
			if($val[3] !== false){
				$player = Server::getInstance()->getPlayerExact($val[3]);
				if($player instanceof Player){
					$this->onShot($player);
				}
				$this->shoot = false;
				return;
			}
		}
		$this->shoot = false;
	}

	public function getParticle(Vector3 $position, $r, $g, $b){
		return new DustParticle($position, $r, $g, $b);
	}

	public function canShoot(){
		return true;
	}
	
	public function onShoot(){
		return;
	}
	
	public final function getPlayer(){
		return $this->player;
	}
	
	public function getLeftAmmo(){
		return $this->ammo;
	}
	
	public function addAmmo($ammo){
		$this->ammo += $ammo;
	}
	
	public function setAmmo($ammo){
		$this->ammo = $ammo;
	}
	
	public function getMaxRange(){
		return $this->maxRange;
	}

	/**
	 * @return MineCombat
	 */
	public function getPlugin(){
		return $this->plugin;
	}

	public static function getName(){
		return "UNKNOWN GUN";
	}

	//returns class (A ~ E)
	public static function getClass(){
		return "?";
	}

	public static function getDefaultAmmo(){
		return self::$defaultAmmo;
	}

	public function getShoot(){
		return $this->shoot;
	}

	public function setShoot($isShooting){
		$this->shoot = $isShooting;
	}
	
	public static function getGunItem(){
		return "0:0";
	}
	
	public static function init(){}
	
	abstract public function onShot(Player $target);
	abstract public function getDamage($distance);
	abstract public function getMagazineAmmo();
	
	/** @return bool */
	abstract public function canGive(Player $player);
}