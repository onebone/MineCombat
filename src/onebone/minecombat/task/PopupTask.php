<?php 

namespace onebone\minecombat\task;

use pocketmine\scheduler\PluginTask;

use onebone\minecombat\MineCombat;

class PopupTask extends PluginTask{
	public function __construct(MineCombat $plugin){
		parent::__construct($plugin);
	}
	
	public function onRun($currentTick){
		$this->getOwner()->showPopup();
	}
}