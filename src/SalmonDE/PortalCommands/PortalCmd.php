<?php
declare(strict_types = 1);

namespace SalmonDE\PortalCommands;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

class PortalCmd {

	public const TYPE_CONSOLE = 'console';
	public const TYPE_PLAYER = 'player';

	private $cmd;
	private $type;

	public function __construct(string $cmd, string $type){
		$this->cmd = $cmd;
		$this->type = strtolower($type);
	}

	public function getCommandString(): string{
		return $this->cmd;
	}

	public function getType(): string{
		return $this->type;
	}

	public function executeCommand(Player $player): void{
		$cmdString = $this->getCommandString();
		$cmdString = str_replace(['{PLAYER}'], [$player->getName()], $cmdString);

		if($this->getType() === self::TYPE_CONSOLE){
			$player->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmdString, true);
		}elseif($this->getType() === self::TYPE_PLAYER){
			$player->getServer()->dispatchCommand($player, $cmdString, true);
		}
	}

	public function toArray(): array{
		return ['cmd' => $this->getCommandString(), 'type' => $this->getType()];
	}

	static public function fromArray(array $cmd): self{
		return new self($cmd['cmd'], $cmd['type']);
	}
}
