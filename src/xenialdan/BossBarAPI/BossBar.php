<?php
namespace xenialdan\BossBarAPI;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;

class BossBar{

	public $eid;
	public $owner;
	public $title;
	public $maxHealth;
	public $currentHealth;
	public $visible = true;

	public $lastUpdated = 0;
	public $totalUpdate = 0;

	public $startTime = -1;
	public $endTime = -1;
	public $showRemainTime = true;

	public function __construct($owner, $title = "Undefined", $currentHealth = 600, $maxHealth = 600){
		if($maxHealth < 0){
			$maxHealth = 0;
		}
		if($currentHealth > $maxHealth){
			$currentHealth = $maxHealth;
		}
		$this->lastUpdated = time();
		$this->owner = $owner;
		$this->eid = Entity::$entityCount++;
		$this->title = $title;
		$this->currentHealth = $currentHealth;
		$this->maxHealth = $maxHealth;
	}

	public function setVisible($bool){
		if($bool){
			$this->sendToAll();
		}else foreach(Server::getInstance()->getOnlinePlayers() as $o){
			PacketAPI::removeBossBar($o, $this->eid);
		}
		$this->visible = $bool;
	}

	public function setHealth($health){
		if($health > $this->maxHealth){
			$health = $this->maxHealth;
		}
		if($health < 0){
			$health = 0;
		}
		$this->currentHealth = $health;
		PacketAPI::broadcastPercentage($this->eid, $this->currentHealth / $this->maxHealth * 100);
	}

	public function getHealth(){
		return $this->currentHealth;
	}

	public function setMaxHealth($maxHealth){
		if($maxHealth < 0){
			$maxHealth = 0;
		}
		$this->maxHealth = $maxHealth;
		PacketAPI::broadcastPercentage($this->eid, $this->currentHealth / $this->maxHealth * 100);
	}

	public function getMaxHealth(){
		return $this->maxHealth;
	}

	public function getOwner(){
		return $this->owner;
	}

	public function setTitle($title){
		$this->title = $title;
		PacketAPI::broadcastTitle($this->eid, $this->title);
	}

	public function getTitle(){
		return $this->title;
	}

	public function setTimer($second, $showRemainTime = true){
		$currentTime = time();
		$this->startTime = $currentTime;
		$this->endTime = $currentTime + $second;
		$this->showRemainTime = $showRemainTime;
		$this->onUpdate();
	}

	public function getRemainingTime(){
		$ret = $this->endTime - $this->startTime;
		if($ret < 0){
			return 0;
		}
		return $ret; 
	}












	//update interval is 10 tick
	public function onUpdate(){
		if($this->sendTimerDataToAll()){
			return;
		}
		//	if(System.currentTimeMillis() - $this->lastUpdated > 2000){
		//		$this->sendToAll();
		//		$this->lastUpdated = System.currentTimeMillis();
		//	}
	}

	public function sendToAll(){
		if(! $this->visible){
			return;
		}
		if($this->sendTimerDataToAll()){
			return;
		}
		foreach(Server::getInstance()->getOnlinePlayers() as $o){
			PacketAPI::sendBossBar($o, $this->eid, $this->title);
			PacketAPI::sendPercentage($o, $this->eid, $this->currentHealth / $this->maxHealth * 100);
		}
	}

	public function sendTo(Player $player){
		if(! $this->visible){
			return;
		}
		if($this->sendTimerDataTo($player)){
			return;
		}
		PacketAPI::sendBossBar($player, $this->eid, $this->title);
		PacketAPI::sendPercentage($player, $this->eid, $this->currentHealth / $this->maxHealth * 100);
		return;
	}

	protected function sendTimerDataToAll(){
		return $this->sendTimerDataTo(Server::getInstance()->getOnlinePlayers());
	}

	protected function sendTimerDataTo($players){
		if($players instanceof Player){
			$players = [$players];
		}
		if(count($players) == 0){
			return false;
		}
		if(! $this->visible){
			return false;
		}
		if($this->startTime > 0){
			$total = $this->endTime - $this->startTime;
			$current = $this->endTime - time();
			$percent = 0;

			if($total > 0){
				$percent = $current / $total * 100;
			}

			$remain = "";
			if($this->showRemainTime){
				if($this->title !== ""){
					$remain .= " §f(";
				}
				if($current >= 0){
					if($current < 10){
						$remain .= "§c";
					}else if($current < 30){
						$remain .= "§6";
					}
					$hour = (int) ($current / 3600);
					$current -= $hour*3600;
					$minute = (int) ($current / 60);
					$current -= $minute*60;
					$second = $current;
					if($hour > 0){
						$remain .= $hour . ":";
					}
					if($hour > 0 && $minute < 10){
						$remain .= "0";
					}
					$remain .= $minute . ":";
					if($second < 10){
						$remain .= "0";
					}
					$remain .= $second;
				}else{
					$current = abs($current);
					if($current > 5){
						$this->startTime = -1;
						$this->endTime = -1;
						return false;
					}else if($current % 2 == 1){
						$remain .= "§0";
					}else{
						$remain .= "§c";
					}
					$remain .= "0:00";
				}
				if($this->title !== ""){
					$remain .= "§f)";
				}
			}
			PacketAPI::broadcastBossBar($this->eid, $this->title . $remain);
			PacketAPI::broadcastPercentage($this->eid, $percent);
			return true;
		}
		return false;
	}
}