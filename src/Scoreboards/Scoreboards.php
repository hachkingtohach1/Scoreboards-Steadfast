<?php
declare(strict_types=1);

namespace Scoreboards;

use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\protocol\v310\RemoveObjectivePacket;
use pocketmine\network\protocol\v310\SetDisplayObjectivePacket;
use pocketmine\network\protocol\v310\SetScorePacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class Scoreboards extends PluginBase{

	/** @var Scoreboards $instance */
	private static $instance;
	/** @var array $scoreboards */
	private $scoreboards = [];

	public function onLoad(): void{
		self::$instance = $this;
	}

	public static function getInstance(): Scoreboards{
		return self::$instance;
	}

	public function new(Player $player, string $objectiveName, string $displayName): void{
		if(isset($this->scoreboards[$player->getName()])){
			$this->remove($player);
		}
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$player->dataPacket($pk);
		$this->scoreboards[$player->getName()] = $objectiveName;
	}

	public function remove(Player $player): void{
		$objectiveName = $this->getObjectiveName($player);
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objectiveName;
		$player->dataPacket($pk);
		unset($this->scoreboards[$player->getName()]);
	}

	public function setLine(Player $player, int $score, string $message): void{
		if(!isset($this->scoreboards[$player->getName()])){
			$this->getLogger()->error("Cannot set a score to a player with no scoreboard");
			return;
		}
		if($score > 15 || $score < 1){
			$this->getLogger()->error("Score must be between the value of 1-15. $score out of range");
			return;
		}
		$objectiveName = $this->getObjectiveName($player);
		$entries[] = [
				'scoreboardId' => $score,
				'objectiveName' => $objectiveName,
				'score' => $score,
				'type' => SetScorePacket::ENTRY_TYPE_FAKE_PLAYER,
				'customName' => $message,
			];
		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_CHANGE;
		$pk->entries = $entries;
		$player->dataPacket($pk);
	}

	public function getObjectiveName(Player $player): ?string{
		return isset($this->scoreboards[$player->getName()]) ? $this->scoreboards[$player->getName()] : null;
	}

	public function onQuit(PlayerQuitEvent $event): void{
		if(isset($this->scoreboards[($player = $event->getPlayer()->getName())])) unset($this->scoreboards[$player]);
	}
}