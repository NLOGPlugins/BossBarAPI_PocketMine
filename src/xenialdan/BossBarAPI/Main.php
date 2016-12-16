<?php
/*
 * BossBarAPI
 * A plugin by XenialDan aka thebigsmileXD
 * http://github.com/thebigsmileXD/BossBarAPI
 * Sending the Bossbar independ from the Server software
 *
 * Command and some API added by solo5star
 * porting to nukkit by solo5star
 */
namespace xenialdan\BossBarAPI;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\EventHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;

use xenialdan\BossBarAPI\network\BossBarValues;
use xenialdan\BossBarAPI\network\BossEventPacket;
use xenialdan\BossBarAPI\network\EvenePacket;
use xenialdan\BossBarAPI\network\UpdateAttributesPacket;
use xenialdan\BossBarAPI\network\SetEntityDataPacket;

use xenialdan\BossBarAPI\task\BossBarTask;

class Main extends PluginBase implements Listener{

	private static $instance = null;

	public $hide = [];

	public $lastMove = [];

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$config = new Config($this->getDataFolder() . "/hide.yml", Config::YAML);
		$this->hide = $config->getAll();

		$bossBarConfig = new Config($this->getDataFolder() . "/bossBar.yml", Config::YAML);
		$bossBarData = $bossBarConfig->getAll();
		foreach($bossBarData as $k => $dat){
			$bossBar = new BossBar($dat["owner"]);
			$bossBar->title = $dat["title"];
			$bossBar->maxHealth = $dat["maxHealth"];
			$bossBar->currentHealth = $dat["currentHealth"];
			$bossBar->visible = $dat["visible"];
			$bossBar->startTime = $dat["startTime"];
			$bossBar->endTime = $dat["endTime"];
			$bossBar->showRemainTime = $dat["showRemainTime"];
			BossBarAPI::registerBossBar($bossBar);
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getNetwork()->registerPacket(BossEventPacket::NETWORK_ID, BossEventPacket::class);
		$this->getServer()->getNetwork()->registerPacket(UpdateAttributesPacket::NETWORK_ID, UpdateAttributesPacket::class);
		$this->getServer()->getNetwork()->registerPacket(SetEntityDataPacket::NETWORK_ID, SetEntityDataPacket::class);

		$this->getServer()->getScheduler()->scheduleRepeatingTask(new BossBarTask($this), 10);
	}

	public static function getInstance(){
		return self::$instance;
	}

	public function onLoad(){
		self::$instance = $this;
	}

	public function onDisable(){
		$this->save();
	}

	public function save(){
		$config = new Config($this->getDataFolder() . "/hide.yml", Config::YAML);
		$config->setAll($this->hide);
		$config->save();

		$bossBarConfig = new Config($this->getDataFolder() . "/bossBar.yml", Config::YAML);
		$data = [];
		foreach(BossBarAPI::getAllBossBar() as $bossBar){
			$data[$bossBar->owner] = [
				"owner" => $bossBar->owner,
				"title" => $bossBar->title,
				"maxHealth" => $bossBar->maxHealth,
				"currentHealth" => $bossBar->currentHealth,
				"visible" => $bossBar->visible,
				"startTime" => $bossBar->startTime,
				"endTime" => $bossBar->endTime,
				"showRemainTime" => $bossBar->showRemainTime
			];
		}
		$bossBarConfig->setAll($data);
		$bossBarConfig->save();
	}

	public function message(CommandSender $sender, $msg){
		$sender->sendMessage("§b§o[ 알림 ] §7" . $msg);
	}

	public function onPreLogin(PlayerPreLoginEvent $event){
		$this->lastMove[strtolower($event->getPlayer()->getName())] = new Vector3(0, 0, 0);
	}

	public function onJoin(PlayerJoinEvent $event){
		BossBarAPI::updateBossBarToPlayer($event->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $event){
		unset($this->lastMove[strtolower($event->getPlayer()->getName())]);
	}

	public function onMove(PlayerMoveEvent $event){
		$p = $event->getPlayer();
		if($this->lastMove[strtolower($p->getName())]->distance($p) > 20){
			BossBarAPI::updateBossBarToPlayer($p);
			$this->lastMove[strtolower($p->getName())] = new Vector3($p->x, $p->y, $p->z);
		}
	}

	public function onTeleport(EntityTeleportEvent $event){
		if($event->getEntity() instanceof Player){
			BossBarAPI::updateBossBarToPlayer($event->getEntity());
		}
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($command->getName() === "보스바"){
			if(!isset($args[0])){
				$args[0] = "x";
			}
			if(! $sender instanceof Player){
				$sender->sendMessage("인게임에서만 가능합니다.");
				return true;
			}
			$name = strtolower($sender->getName());

			switch($args[0]){
				case "켜기":
					if(!isset($this->hide[$name])){
						$this->message($sender, "이미 보스바가 켜져있습니다.");
						return true;
					}
					unset($this->hide[$name]);
					BossBarAPI::updateBossBarToPlayer($sender);
					$this->message($sender, "보스바를 켰습니다.");
					return true;

				case "끄기":
					if(isset($this->hide[$name])){
						$this->message($sender, "이미 보스바가 꺼져있습니다.");
						return true;
					}
					$this->hide[$name] = true;
					BossBarAPI::removeBossBarToPlayer($sender);
					$this->message($sender, "보스바를 껐습니다.");
					return true;

				case "생성":
					if($sender->isOp()){
						if(!isset($args[1])){
							$this->message($sender, "사용법 : /보스바 생성 [타이틀]");
							return true;
						}
						unset($args[0]);
						$title = implode(" ", $args);
						$owner = "";
						for($id = 1; true; $id++){
							if(BossBarAPI::getBossBar($id) === null){
								$owner = $id;
								break;
							}
						}
						BossBarAPI::registerBossBar(new BossBar($owner, $title));
						$this->message($sender, "성공적으로 보스바를 생성하였습니다.");
						return true;
					}

				case "목록":
					if($sender->isOp()){
						$this->message($sender, "====== 등록된 보스바 목록 ======");
						foreach(BossBarAPI::getAllBossBar() as $bossBar){
							$this->message($sender, "id : " . $bossBar->getOwner() . ", 타이틀 : " . $bossBar->getTitle());
						}
						return true;
					}

				case "삭제":
					if($sender->isOp()){
						if(!isset($args[1])){
							$this->message($sender, "사용법 : /보스바 삭제 [id]");
							return true;
						}
						if(BossBarAPI::unregisterBossBar($args[1])){
							$this->message($sender, "성공적으로 보스바를 삭제하였습니다.");
							return true;
						}
						$this->message($sender, "해당 id의 보스바가 없습니다.");
						return true;
					}

				case "타이틀":
					if($sender->isOp()){
						if(!isset($args[2])){
							$this->message($sender, "사용법 : /보스바 타이틀 [id] [타이틀...]");
							return true;
						}
						$bossBar = BossBarAPI::getBossBar($args[1]);
						if($bossBar === null){
							$this->message($sender, "해당 id의 보스바가 없습니다.");
							return true;
						}
						unset($args[0]);
						unset($args[1]);
						$title = implode(" ", $args);
						$bossBar->setTitle($title);
						$this->message($sender, "성공적으로 타이틀을 변경하였습니다 : " . $title);
						return true;
					}


				case "체력설정":
					if($sender->isOp()){
						if(!isset($args[2]) || !is_numeric($args[2])){
							$this->message($sender, "사용법 : /보스바 체력설정 [id] [퍼센트(1~100)]");
							return true;
						}
						$bossBar = BossBarAPI::getBossBar($args[1]);
						if($bossBar === null){
							$this->message($sender, "해당 id의 보스바가 없습니다.");
							return true;
						}
						$bossBar->setHealth($bossBar->getMaxHealth() * $args[2] / 100);
						$this->message($sender, "성공적으로 체력을 변경하였습니다.");
						return true;
					}


				case "타이머":
					if($sender->isOp()){
						if(!isset($args[2]) || !is_numeric($args[2])){
							$this->message($sender, "사용법 : /보스바 타이머 [id] [시간(단위:초)]");
							return true;
						}
						$bossBar = BossBarAPI::getBossBar($args[1]);
						if($bossBar === null){
							$this->message($sender, "해당 id의 보스바가 없습니다.");
							return true;
						}
						$bossBar->setTimer($args[2]);
						$this->message($sender, "성공적으로 타이머를 설정하였습니다.");
						return true;
					}

				default:
					$this->message($sender, "/보스바 [켜기/끄기]");
					if($sender->isOp()){
						$this->message($sender, "/보스바 생성 [타이틀] - 해당 타이틀로 보스바를 생성합니다.");
						$this->message($sender, "/보스바 목록 - 등록된 보스바 목록을 봅니다.");
						$this->message($sender, "/보스바 삭제 [id] - 해당 보스바를 삭제합니다.");
						$this->message($sender, "/보스바 타이틀 [id] [타이틀] - 보스바 타이틀을 설정합니다.");
						$this->message($sender, "/보스바 체력설정 [id] [퍼센트(0~100)] - 보스바의 체력을 설정합니다.");
						$this->message($sender, "/보스바 타이머 [id] [시간(단위:초)] - 타이머를 설정합니다.");
					}
					return true;
			}
		}
	}
}