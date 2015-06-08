<?php 

namespace onebone\minecombat\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\level\Position;

use onebone\minecombat\MineCombat;
use onebone\minecombat\grenade\BaseGrenade;

class ExplosionTask extends PluginTask{
	private $player, $pos, $grenade;
	
	public function __construct(MineCombat $plugin, BaseGrenade $grenade, Position $pos){
		parent::__construct($plugin);
		$this->pos = $pos;
		$this->grenade = $grenade;
	}
	
	public function onRun($currentTick){
		$this->grenade->explode($this->pos);
	}
}