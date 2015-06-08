<?php 

namespace onebone\minecombat\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

use onebone\minecombat\MineCombat;

class ShootTask extends AsyncTask{
	public $ret = [];
	
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
		
		while($cnt < $this->maxRange){
			$xx = $this->x + (0.4 + $cnt) * $sin * $pcos;
			$yy = $this->y + (0.4 + $cnt) * $tan;
			$zz = $this->z + (0.4 + $cnt) * $cos * $pcos;
			
			$ret[$cnt] = [$xx, $yy, $zz, false];
			
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