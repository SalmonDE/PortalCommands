<?php
declare(strict_types = 1);

namespace SalmonDE\PortalCommands\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use SalmonDE\PortalCommands\Loader;

class WorldTPCommand extends BaseCommand {

	public function __construct(Loader $plugin){
		parent::__construct('worldtp', $plugin);

		$this->setUsage('/worldtp <world> [player]');
		$this->setPermission('cmd.worldtp');
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $params = []): bool{
		if(!isset($params[0])){
			return false;
		}

		if(!$this->getPlugin()->getServer()->isLevelLoaded($params[0])){
			$sender->sendMessage('§cWorld "'.$params[0].'" not found');
			return true;
		}

		$world = $this->getPlugin()->getServer()->getLevelByName($params[0]);

		if(isset($params[1]) and $params[1] !== ''){
			$player = $this->getPlugin()->getServer()->getPlayer($params[1]);

			if(!($player instanceof Player)){
				$sender->sendMessage('§cPlayer "'.$params[1].'" not found');
				return true;
			}
		}elseif($sender instanceof Player){
			$player = $sender;
		}else{
			$sender->sendMessage('§cYou need to specify a player');
			return true;
		}

		$player->teleport($world->getSafeSpawn());
		return true;
	}
}
