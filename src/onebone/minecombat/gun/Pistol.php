<?php

namespace onebone\minecombat\gun;

class Pistol extends BaseGun{
	public function __construct($player){
		parent::__construct($player, 56);
	}

	public function canShoot(){
		$ret = parent::canShoot();
		if($ret){
			if(microtime() - $this->lastShot > 0.5) return true;
		}
		return false;
	}

	public function getMagazineAmmo(){
		return 8;
	}

	public function getName(){
		return "Desert Eagle";
	}

	public function getClass(){
		return "D";
	}

	public function getDamage($distance){
		return 5;
	}
}
