<?php 

namespace onebone\minecombat\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;

use onebone\minecombat\MineCombat;

class TeleportTask extends PluginTask{
	private $player;
	
	public function __construct(MineCombat $plugin, $player){
		parent::__construct($plugin);
		$this->player = $player;
	}
	
	public function onRun($currentTick){
		if(($player = Server::getInstance()->getPlayerExact($this->player)) instanceof Player){
			$this->getOwner()->teleportToSpawn($player);
		}
	}
}