<?php

namespace onebone\minecombat\gun;

class Pistol extends BaseGun{
	public function __construct($player){
		parent::__construct($player, 60);
	}

	public function canShoot(){
		$ret = parent::canShoot();
		if($ret){
			if(microtime() - $this->lastShot > 0.5) return true;
		}
		return false;
	}
}
