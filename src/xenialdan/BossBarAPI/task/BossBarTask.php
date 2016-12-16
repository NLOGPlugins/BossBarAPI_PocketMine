<?php

namespace xenialdan\BossBarAPI\task;

use pocketmine\scheduler\PluginTask;
use xenialdan\BossBarAPI\Main;
use xenialdan\BossBarAPI\BossBarAPI;

class BossBarTask extends PluginTask{

	public function __construct(Main $owner){
		parent::__construct($owner);
	}

	public function onRun($currentTick){
		BossBarAPI::updateBossBar();
	}
}