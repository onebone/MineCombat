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
	/** @var int 		$ammo */
	protected $ammo = 50;
	/** @var int 		$lastShot */
	protected $lastShot = 0;

	public function __construct($player, $ammo = 50){
		$this->player = $player;
		$this->ammo = $ammo;
	}

	public function shoot(){

	}

	/**
	 * @return bool
	 */
	public function canShoot(){
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
