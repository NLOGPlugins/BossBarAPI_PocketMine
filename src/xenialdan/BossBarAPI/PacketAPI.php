<?php

namespace xenialdan\BossBarAPI;

use pocketmine\Player;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\Server;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\entity\Entity;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\level\Location;

use xenialdan\BossBarAPI\network\BossBarValues;
use xenialdan\BossBarAPI\network\BossEventPacket;
use xenialdan\BossBarAPI\network\EvenePacket;
use xenialdan\BossBarAPI\network\UpdateAttributesPacket;
use xenialdan\BossBarAPI\network\SetEntityDataPacket;

class PacketAPI{

	public static function broadcastBossBar(int $eid, string $title){
		foreach(Server::getInstance()->getOnlinePlayers() as $o){
			self::sendBossBar($o, $eid, $title);
		}
	}

	public static function broadcastPercentage(int $eid, int $percentage){
		foreach(Server::getInstance()->getOnlinePlayers() as $o){
			self::sendPercentage($o, $eid, $percentage);
		}
	}

	public static function broadcastTitle(int $eid, string $title){
		foreach(Server::getInstance()->getOnlinePlayers() as $o){
			self::sendTitle($o, $eid, $title);
		}
	}

	/**
	 * Sends the text to one player
	 *
	 * @param Player $players
	 * To who to send
	 * @param int $eid
	 * The EID of an existing fake wither
	 * @param string $title
	 * The title of the boss bar
	 * @param null|int $ticks
	 * How long it displays
	 */
	public static function sendBossBar(Player $player, int $eid, string $title){
		if($title === ""){
			return;
		}
		if(isset(Main::getInstance()->hide[strtolower($player->getName())])){
			return;
		}
		$packet = new AddEntityPacket();
		$packet->eid = $eid;
		$packet->type = 52;
		$packet->yaw = 0;
		$packet->pitch = 0;
		$packet->metadata = [Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1], Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 0 ^ 1 << Entity::DATA_FLAG_SILENT ^ 1 << Entity::DATA_FLAG_INVISIBLE ^ 1 << Entity::DATA_FLAG_NO_AI], Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0], 
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title], Entity::DATA_BOUNDING_BOX_WIDTH => [Entity::DATA_TYPE_FLOAT, 0], Entity::DATA_BOUNDING_BOX_HEIGHT => [Entity::DATA_TYPE_FLOAT, 0]];
		$packet->x = $player->x;
		$packet->y = $player->y - 28;
		$packet->z = $player->z;
		$player->dataPacket($packet);
		
		$bpk = new BossEventPacket(); // This updates the bar
		$bpk->eid = $eid;
		$bpk->state = 0;
		$player->dataPacket($bpk);
	}

	/**
	 * Sets how many % the bar is full by EID
	 *
	 * @param int $percentage
	 * 0-100
	 * @param int $eid 
	 */
	public static function sendPercentage(Player $player, int $eid, int $percentage){
		$upk = new UpdateAttributesPacket(); // Change health of fake wither -> bar progress
		$upk->entries[] = new BossBarValues(0, 600, max(0, min([$percentage, 100])) / 100 * 600, 'minecraft:health'); // Ensures that the number is between 0 and 100;
		$upk->entityId = $eid;
		Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $upk);
		
		$bpk = new BossEventPacket(); // This updates the bar
		$bpk->eid = $eid;
		$bpk->state = 0;
		$player->dataPacket($bpk);
	}

	/**
	 * Sets the BossBar title by EID
	 *
	 * @param string $title 
	 * @param int $eid 
	 */
	public static function sendTitle(Player $player, int $eid, string $title){
		$npk = new SetEntityDataPacket(); // change name of fake wither -> bar text
		$npk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title]];
		$npk->eid = $eid;
		$player->dataPacket($npk);
		
		$bpk = new BossEventPacket(); // This updates the bar
		$bpk->eid = $eid;
		$bpk->state = 0;
		$player->dataPacket($bpk);
	}

	/**
	 * Remove BossBar from players by EID
	 *
	 * @param Player[] $players 
	 * @param int $eid 
	 * @return boolean removed
	 */
	public static function removeBossBar(Player $player, int $eid){
		$pk = new RemoveEntityPacket();
		$pk->eid = $eid;
		$player->dataPacket($pk);
		return true;
	}

	/**
	 * Handle player movement
	 *
	 * @param Location $pos
	 * @param unknown $eid 
	 * @return MoveEntityPacket $pk
	 */
	public static function playerMove(Location $pos, $eid){
		$pk = new MoveEntityPacket();
		$pk->x = $pos->x;
		$pk->y = $pos->y - 28;
		$pk->z = $pos->z;
		$pk->eid = $eid;
		$pk->yaw = $pk->pitch = $pk->headYaw = 0;
		return clone $pk;
	}
}