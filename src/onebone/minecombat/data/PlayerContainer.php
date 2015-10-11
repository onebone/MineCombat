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

namespace onebone\minecombat\data;

class PlayerContainer{
	/** @var string $player */
	private $player;
	/** @var BaseGun $gun */
	private $gun;
	/** @var int $xp */
	private $xp = 0;
	/** @var int $coins */
	private $coins = 0;
	/** @var int $lastGame */
	private $lastGame = -1;

	/**
	 * @var string 	$username
	 * @var int			$xp
	 * @var int			$coins
	 */
	public function __construct($username, $xp = 0, $coins = 0, $lastGame = -1){
		$this->player = strtolower($username);
		$this->xp = 0;
		$this->coins = 0;
		$this->lastGame = $lastGame;

		$this->gun = null;
	}

	/**
	 * Sets the gun which player is handling
	 *
	 * @var BaseGun	$gun
	 */
	public function setCurrentGun($gun){
		$this->gun = $gun;
	}

	/**
	 * Sets XP of the player
	 *
	 * @var int
	 */
	public function setXp($xp){
		$this->xp = $xp;
	}

	/**
	 * Adds XP of the player
	 *
	 * @var int
	 */
	public function addXp($xp){
		$this->xp += $xp;
	}

	/**
	 * Adds XP of the player
	 *
	 * @var int
	 */
	public function reduceXp($xp){
		$this->addXp(-$xp);
	}

	/**
	 * Sets the coin of the player
	 *
	 * @var int
	 */
	public function setCoins($coins){
		$this->coins = $coins;
	}

	/**
	 * Adds the coin of the player
	 *
	 * @var int
	 */
	public function addCoins($coins){
		$this->coins += $coins;
	}

	/**
	 * Reduces the coin of the player
	 *
	 * @var int
	 */
	public function reduceCoins($coins){
		$this->addCoins(-$coins);
	}

	/**
	 * Sets the last game of the player
	 *
	 * @var int $game
	 */
	public function setLastGame($game){
		$this->lastGame = $game;
	}

	/**
	 * Returns the username of the player
	 *
	 * @return string
	 */
	public function getPlayer(){
		return $this->player;
	}

	/**
	 * Returns the gun which player is handling
	 *
	 * @return BaseGun
	 */
	public function getCurrentGun(){
		return $this->gun;
	}

	/**
	 * Returns XP of player
	 *
	 * @return int
	 */
	public function getXp(){
		return $this->xp;
	}

	/**
	 * Returns the coin of player
	 *
	 * @return int
	 */
	public function getCoins(){
		return $this->coins;
	}

	/**
	 * Returns the game id that player joined last
	 *
	 * @return int
	 */
	public function getLastGame(){
		return $this->lastGame;
	}
}
