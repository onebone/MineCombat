<?php 

namespace onebone\minecombat\grenade;

use pocketmine\Player;
use pocketmine\level\Explosion;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\math\AxisAlignedBB;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\network\Network;

use onebone\minecombat\MineCombat;
use onebone\minecombat\task\ExplosionTask;

class FragmentationGrenade extends BaseGrenade{
	private $plugin;
	
	const RANGE = 4;
	
	public function __construct(MineCombat $plugin, Player $player){
		parent::__construct($plugin, $player);
		
		$this->plugin = $plugin;
	}
	
	public function onCollide(Position $pos){
		Server::getInstance()->getScheduler()->scheduleDelayedTask(new ExplosionTask($this->plugin, $this, $pos), 40);
	}
	
	public function explode(Position $pos){
		$aabb = new AxisAlignedBB($pos->getX() - $range, $pos->getY() - $range, $pos->getZ() - $range, $pos->getX() + $range, $pos->getY() + $range, $pos->getZ() + $range);
		$list = $this->getPlayer()->getLevel()->getNearbyEntities($aabb, null);
		
		$pk = new ExplodePacket();
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->radius = 10;
		$pk->records = [new Vector3($pos->x, $pos->y, $pos->z)];
		Server::broadcastPacket($this->getPlayer()->getLevel()->getChunkPlayers($pos->x >> 4, $pos->z >> 4), $pk->setChannel(Network::CHANNEL_BLOCKS));
		
		foreach($list as $entity){
			$ev = new EntityDamageByEntityEvent($this->getPlayer(), $entity, 16, 15);
			$entity->attack($ev->getFinalDamage(), $ev);
		}
	}
	
	public function getGravity(){
		return 0.14;
	}
}