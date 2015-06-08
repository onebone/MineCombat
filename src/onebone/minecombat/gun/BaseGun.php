<?php 

namespace onebone\minecombat\gun;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\network\Network;
use pocketmine\level\particle\DustParticle;
use pocketmine\Thread;

use onebone\minecombat\MineCombat;
use onebone\minecombat\task\ShootTask;

abstract class BaseGun{
	protected $player;
	
	private $plugin, $ammo, $maxRange, $color, $shoot = false;
	
	public function __construct(MineCombat $plugin, Player $player, $maxRange, $ammo = 50, $color = [175, 175, 175]){
		$this->plugin = $plugin;
		$this->player = $player;
		$this->ammo = $ammo;
		$this->color = $color;
		
		if($maxRange < 0){
			throw new \Exception("Max range of gun cannot be smaller than 0");
		}
		$this->maxRange = $maxRange;
	}
	
	public function setColor($color){
		$this->color = $color;
	}
	
	public function shoot(){
		
		
		if(!$this->shoot){
			$time = microtime(true);
			
			if(!$this->canShoot($time)) return false;	
			if($this->ammo <= 0){
				return false;
			}
			$this->ammo--;
			
			$this->onShoot();
			
			$this->shoot = true;
			
			$players = [];
			foreach($this->player->getLevel()->getPlayers() as $player){
				if($player->getName() === $this->player->getName()) continue;
				$players[$player->getName()] = [$player->getX(), $player->getY() + 1.62, $player->getZ()];
			}
			
			$pk = new ExplodePacket();
			$pk->x = $this->player->getX();
			$pk->y = $this->player->getY();
			$pk->z = $this->player->getZ();
			$pk->radius = 10;
			$pk->records = [new Vector3($this->player->getX(), $this->player->getY() + 1.62, $this->player->getZ())];
			Server::broadcastPacket($this->getPlayer()->getLevel()->getChunkPlayers($this->player->getX() >> 4, $this->player->getZ() >> 4), $pk->setChannel(Network::CHANNEL_BLOCKS));
			
			$thr = new ShootTask($this->player->getX(), $this->player->getY() + 1.62, $this->player->getZ(), $this->player->yaw, $this->player->pitch, $players, $this->getMaxRange(), $this->player->getName());
			
			$this->plugin->submitAsyncTask($thr);
		}
	}
	
	public function processShoot($ret){
		$level = $this->player->getLevel();
		
		foreach($ret as $val){
			$vec = new Vector3($val[0], $val[1], $val[2]);
			
			if($level->getBlock(new Vector3($val[4], $val[5], $val[6]))->getId() !== 0){
				$this->shoot = false;
				return;
			}
			$particle = new DustParticle($vec, $this->color[0], $this->color[1], $this->color[2]);
			$level->addParticle($particle);
			
			if($val[3] !== false){
				$player = Server::getInstance()->getPlayerExact($val[3]);
				if($player instanceof Player){
					$this->onShot($player);
				}
				$this->shoot = false;
				return;
			}
		}
		$this->shoot = false;
	}
	
	public function canShoot(){
		return true;
	}
	
	public function onShoot(){
		return;
	}
	
	public final function getPlayer(){
		return $this->player;
	}
	
	public function getLeftAmmo(){
		return $this->ammo;
	}
	
	public function addAmmo($ammo){
		$this->ammo += $ammo;
	}
	
	public function setAmmo($ammo){
		$this->ammo = $ammo;
	}
	
	public function getMaxRange(){
		return $this->maxRange;
	}
	
	abstract public function onShot(Player $target);
	abstract public function getDamage($distance);
}