<?php
declare(strict_types = 1);

namespace SalmonDE\PortalCommands;

use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Portal {

	private $name;

	private $world;
	private $bb;

	private $commands = [];

	private $enabled = true;

	public function __construct(string $name, string $world, Vector3 $pos1, Vector3 $pos2){
		$this->name = $name;
		$this->world = $world;

		$minPos = self::minPos($pos1 = $pos1->floor(), $pos2 = $pos2->floor());
		$maxPos = self::maxPos($pos1, $pos2);
		$this->bb = new AxisAlignedBB($minPos->x, $minPos->y, $minPos->z, $maxPos->x, $maxPos->y, $maxPos->z);
	}

	public function getName(): string{
		return $this->name;
	}

	public function getWorldName(): string{
		return $this->world;
	}

	public function getBB(): AxisAlignedBB{
		return $this->bb;
	}

	public function isInside(Position $pos): bool{
		return $pos->isValid() and $pos->level->getFolderName() === $this->getWorldName() and $this->bb->isVectorInXZ($pos) and $pos->y >= $this->bb->minY and $pos->y <= $this->bb->maxY;
	}

	public function isEnabled(): bool{
		return $this->enabled;
	}

	public function setEnabled(bool $value = true): void{
		$this->enabled = $value;
	}

	public function getCommands(): array{
		return $this->commands;
	}

	public function addCommand(PortalCmd $cmd): void{
		$this->commands[] = $cmd;
	}

	public function removeCommand(int $index): void{
		unset($this->commands[$index]);
		$this->commands = array_values($this->commands);
	}

	public function enterPlayer(Player $player): void{
		foreach($this->getCommands() as $cmd){
			$cmd->executeCommand($player);
		}
	}

	public function toArray(): array{
		$bb = $this->getBB();

		$cmds = [];
		foreach($this->getCommands() as $cmd){
			$cmds[] = $cmd->toArray();
		}

		return [
			'name' => $this->getName(),
			'world' => $this->getWorldName(),
			'minPos' => [
				'x' => $bb->minX,
				'y' => $bb->minY,
				'z' => $bb->minZ
			],
			'maxPos' => [
				'x' => $bb->maxX,
				'y' => $bb->maxY,
				'z' => $bb->maxZ
			],
			'commands' => $cmds,
			'enabled' => $this->isEnabled()
		];
	}

	static public function fromArray(array $portalData): self{
		$pos1 = new Vector3($portalData['minPos']['x'], $portalData['minPos']['y'], $portalData['minPos']['z']);
		$pos2 = new Vector3($portalData['maxPos']['x'], $portalData['maxPos']['y'], $portalData['maxPos']['z']);

		$portal = new Portal($portalData['name'], $portalData['world'], $pos1, $pos2);

		$portal->setEnabled($portalData['enabled']);

		foreach($portalData['commands'] as $cmdData){
			$portal->addCommand(PortalCmd::fromArray($cmdData));
		}

		return $portal;
	}

	/*
	Future: https://github.com/pmmp/Math/commit/52a92d6d5c665528a9fc597b1f10d6e15e7d861a
	*/
	static public function minPos(Vector3 $v1, Vector3 $v2): Vector3{
		return new Vector3(min($v1->x, $v2->x), min($v1->y, $v2->y), min($v1->z, $v2->z));
	}

	static public function maxPos(Vector3 $v1, Vector3 $v2): Vector3{
		return new Vector3(max($v1->x, $v2->x), max($v1->y, $v2->y), max($v1->z, $v2->z));
	}
}
