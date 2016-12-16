<?php
namespace xenialdan\BossBarAPI;

use pocketmine\Player;

class BossBarAPI{

	public static $bossBarList = [];

	public static function registerBossBar(BossBar $bossBar){
		self::$bossBarList[$bossBar->getOwner()] = $bossBar;
		$bossBar->sendToAll();
		return $bossBar;
	}

	public static function unregisterBossBar($owner){
		if(isset(self::$bossBarList[$owner])){
			unset(self::$bossBarList[$owner]);
			return true;
		}
		return false;
	}

	public static function getBossBar($owner){
		if(isset(self::$bossBarList[$owner])){
			return self::$bossBarList[$owner];
		}
		return null;
	}

	public static function getAllBossBar(){
		return self::$bossBarList;
	}

	public static function clearBossBar(){
		foreach(self::$bossBarList as $bs){
			$bs->setVisible(false);
		}
		self::$bossBarList = [];
	}

	public static function updateBossBar(){
		if(count(self::$bossBarList) == 0){
			return;
		}
		foreach(self::$bossBarList as $bs){
			$bs->onUpdate();
		}
	}

	public static function updateBossBarToPlayer(Player $player){
		foreach(self::$bossBarList as $bs){
			$bs->sendTo($player);
		}
	}

	public static function removeBossBarToPlayer(Player $player){
		foreach(self::$bossBarList as $bs){
			PacketAPI::removeBossBar($player, $bs->eid);
		}
	}
}