<?php 

namespace onebone\minecombat\gun;

use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use onebone\minecombat\MineCombat;

class Pistol extends BaseGun{
	private $plugin, $lastShoot;
	
	public function __construct(MineCombat $plugin, Player $player, $color = [175, 175, 175]){
		parent::__construct($plugin, $player, 30, $color);
		
		$this->plugin = $plugin;
	}
	
	public function onShoot(){
		$this->lastShoot = microtime(true);
	}
	
	public function canShoot(){
		$time = microtime(true);
		return ($time - $this->lastShoot > 0.5);
		return true;
	}
	
	public function onShot(Player $target){
		if($this->plugin->isEnemy($this->getPlayer()->getName(), $target->getName())){
			$distance = $this->getPlayer()->distance($target);
			
			$damage = $this->getDamage($distance);
			$target->attack($damage, new EntityDamageByEntityEvent($this->getPlayer(), $target, 15, $damage, 0));
		}
	}
	
	public function getDamage($distance){
		return 5; // TODO: Damage by distance
	}
}