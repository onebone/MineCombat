<?php 

namespace onebone\minecombat\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;

use onebone\minecombat\MineCombat;
use onebone\minecombat\grenade\BaseGrenade;

class UpdateTask extends PluginTask{
	private $player;
	
	public function __construct(MineCombat $plugin, BaseGrenade $grenade){
		parent::__construct($plugin);
		
		$this->grenade = $grenade;
	}
	
	public function onRun($currentTick){
		$this->grenade->update();
	}
}