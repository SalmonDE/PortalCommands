<?php
declare(strict_types = 1);

namespace SalmonDE\PortalCommands\Commands;

use pocketmine\command\CommandExecutor;
use pocketmine\command\PluginCommand;
use SalmonDE\PortalCommands\Loader;

abstract class BaseCommand extends PluginCommand implements CommandExecutor {

	public function __construct(string $cmd, Loader $plugin){
		parent::__construct($cmd, $plugin);
		$this->setExecutor($this);
	}
}
