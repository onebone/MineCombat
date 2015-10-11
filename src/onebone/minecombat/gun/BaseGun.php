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

abstract class BaseGun{
	/** @var string $player */
	protected $player;
	/** @var int		$allAmmo */
	protected $allAmmo = 50;
	/** @var int 		$ammo */
	protected $ammo = 15;
	/** @var int 		$lastShot */
	protected $lastShot = 0;

	public function __construct($player, $allAmmo = 50, $ammo = 15){
		$this->player = $player;
		$this->allAmmo = $allAmmo;
		$this->ammo = $ammo;
	}

	public function shoot(){
		$this->lastShot = microtime(true);
	}

	/**
	 * Reloads ammo to the magazine
	 */
	public function reload(){
		$this->ammo += min($this->getMaxMagazineAmmo(), $this->allAmmo);
		$this->allAmmo -= min($this->getMaxMagazineAmmo(), $this->allAmmo);
	}

	/**
	 * Returns amount of ammo that is reloaded and available to use
	 *
	 * @return int
	 */
	public function getAmmo(){
		return $this->ammo;
	}

	/**
	 * Returns amount of all ammo that is not reloaded
	 *
	 * @return int
	 */
	public function getAllAmmo(){
		return $this->allAmmo;
	}

	/**
	 * @return bool
	 */
	public function canShoot(){
		if($this->getAmmo() <= 0) return false;
		return true;
	}

	/**
	 * @return int
	 */
	public function getDefaultAmmo(){
		return 50;
	}

	/**
	 * @return int
	 */
	public function getPickupAmmo(){
		return 15;
	}

	/**
	 * Returns the max amount of ammo that can be reloaded in magazine
	 *
	 * @return int
	 */
	public function getMaxMagazineAmmo(){
		return 15;
	}

	/**
	 * Returns range of gun can shoot
	 *
	 * @return int
	 */
	public function getRange(){
		return 30;
	}

	/**
	 * @return string
 	 */
	abstract public function getClass();
	/**
	 * @return int
	 */
	abstract public function getDamage($distance);
}
