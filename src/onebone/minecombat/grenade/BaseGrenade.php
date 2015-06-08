<?php 

namespace onebone\minecombat\grenade;

use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\Server;

use onebone\minecombat\task\UpdateTask;
use onebone\minecombat\MineCombat;

abstract class BaseGrenade{
	private $player, $plugin;
	
	private $motionX, $motionY, $motionZ, $x, $y, $z, $scheduleId = -1;
	
	public function __construct(MineCombat $plugin, Player $player){
		$this->player = $player;
		$this->plugin = $plugin;
	}
	
	public function lob(Vector3 $aimPos){
		if(Server::getInstance()->getScheduler()->isQueued($this->scheduleId)){
			return;
		}
		/*new Double("", -sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)),
		new Double("", -sin($this->pitch / 180 * M_PI)),
		new Double("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))*/
		$aimPos = $aimPos->multiply(1.5);
		$this->motionX = $aimPos->x;//-sin($this->player->yaw / 180 * M_PI) * cos($this->player->pitch / 180 * M_PI);
		$this->motionY = $aimPos->y;//-sin($this->player->pitch / 180 * M_PI);
		$this->motionZ = $aimPos->z;//cos($this->player->yaw / 180 * M_PI) * cos($this->player->pitch / 180 * M_PI);
		
		$this->x = $this->player->getX();
		$this->y = $this->player->getY() + 1;
		$this->z = $this->player->getZ();
		
		$gravity = $this->getGravity();
		
		$this->scheduleId = Server::getInstance()->getScheduler()->scheduleRepeatingTask(new UpdateTask($this->plugin, $this), 1)->getTaskId();
	}
	
	public function update(){
		$this->motionY -= $this->getGravity();
		
		$this->x += $this->motionX;
		$this->y += $this->motionY;
		$this->z += $this->motionZ;
		
		$pos = new Position($this->x, $this->y, $this->z, $this->player->getLevel());
		$particle = new CriticalParticle(new Vector3($this->x, $this->y, $this->z));
		$this->player->getLevel()->addParticle($particle);
		
		$block = $this->player->getLevel()->getBlock($pos);
		if($block->getId() !== 0 or $this->y <= 0){
			$this->onCollide($pos);
			if(Server::getInstance()->getScheduler()->isQueued($this->scheduleId)){
				Server::getInstance()->getScheduler()->cancelTask($this->scheduleId);
			}
		}
	}
	
	public function getPlayer(){
		return $this->player;
	}
	
	abstract public function onCollide(Position $player);
	abstract public function getGravity();
}