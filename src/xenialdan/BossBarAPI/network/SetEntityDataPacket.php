<?php

namespace xenialdan\BossBarAPI\network;

use pocketmine\utils\Binary;
use pocketmine\network\protocol\DataPacket;

class SetEntityDataPacket extends DataPacket{
	const NETWORK_ID = 0x26;
	public $eid;
	public $metadata;

	public function decode(){}

	public function encode(){
		$this->reset();
		$this->putEntityId($this->eid);
		$meta = Binary::writeMetadata($this->metadata);
		$this->put($meta);
	}
}
