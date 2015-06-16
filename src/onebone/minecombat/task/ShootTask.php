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

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

use onebone\minecombat\MineCombat;

class ShootTask extends AsyncTask{
	public $ret = [];

	/** @var double */
	private $x, $y, $z, $yaw, $pitch;

	/** @var string[] */
	private $players;

	/** @var string */
	private $player;

	/** @var int */
	private $maxRange;

	/**
	 * @param double $x
	 * @param double $y
	 * @param double $z
	 * @param double $yaw
	 * @param double $pitch
	 * @param string[] $players
	 * @param int $maxRange
	 * @param string $player
	 */
	public function __construct($x, $y, $z, $yaw, $pitch, $players, $maxRange, $player){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->yaw = $yaw;
		$this->pitch = $pitch;
		$this->players = $players;
		$this->maxRange = $maxRange;
		$this->player = $player;
	}
	
	public function onRun(){		
		$this->ret = $this->processShoot();
	}
	
	public function processShoot(){
		$sin = -sin($this->yaw/180 * M_PI);
		$cos = cos($this->yaw/180*M_PI);
		$tan = -sin($this->pitch/180*M_PI);
		$pcos = cos($this->pitch/180*M_PI);
		
		$cnt = 0;
		$ret = [];
		
		while($cnt < $this->maxRange){
			$xx = $this->x + (0.4 + $cnt) * $sin * $pcos;
			$yy = $this->y + (0.4 + $cnt) * $tan;
			$zz = $this->z + (0.4 + $cnt) * $cos * $pcos;
			
			$ret[$cnt] = [$xx, $yy, $zz, false, round($xx), round($yy), round($zz)];
			
			foreach($this->players as $name => $player){
				if($xx - 1 < $player[0] and $xx + 1 > $player[0] and $yy - 2 < $player[1] and $yy + 1 > $player[1] and $zz + 1 > $player[2]	and $zz - 1 < $player[2]){
					$ret[$cnt][3] = $name;
					break 2;
				}
			}
			++$cnt;
		}
		return $ret;
	}
	
	public function onCompletion(Server $server){
		if(($gun = MineCombat::getInstance()->getGunByPlayer($this->player)) !== null){
			$gun->processShoot($this->ret);
		}
	}
}