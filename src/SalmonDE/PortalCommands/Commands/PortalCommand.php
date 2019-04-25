<?php
declare(strict_types = 1);

namespace SalmonDE\PortalCommands\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\Player;
use SalmonDE\PortalCommands\Loader;
use SalmonDE\PortalCommands\Portal;
use SalmonDE\PortalCommands\PortalCmd;

class PortalCommand extends BaseCommand implements Listener {

	private $portalCreating = [];
	private $portalEditing = [];

	public function __construct(Loader $plugin){
		parent::__construct('portal', $plugin);

		$this->setUsage('/portal <create|remove|enable|disable|edit|list> [name]');
		$this->setPermission('cmd.portal');
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $params = []): bool{
		if(!isset($params[0])){
			return false;
		}

		switch(strtolower($params[0])){
			case 'create':
				if(!($sender instanceof Player)){
					$sender->sendMessage('§cYou can only create portals in-game');
					return true;
				}elseif($this->isCreatingPortal($sender)){
					$sender->sendMessage('§cYou\'re already creating a portal');
					return true;
				}elseif(!isset($params[1]) or $params[1] === ''){
					return false;
				}else{
					$this->portalCreating[$sender->getUniqueId()->toString()] = ['name' => $params[1], 'world' => $sender->getLevel()->getFolderName(), 'pos1' => null, 'pos2' => null];
					$sender->sendMessage('§eCreating portal "'.$params[1].'" now. Please mark the portal location by breaking two diagonal corners of it.');
					return true;
				}

			case 'remove':
				if(!isset($params[1]) or $params[1] === ''){
					return false;
				}

				if(!$this->getPlugin()->getPortalMap()->portalExists($params[1])){
					$sender->sendMessage('§cThere\'s no portal called "'.$params[1].'"');
					return true;
				}

				$portal = $this->getPlugin()->getPortalMap()->getPortal($params[1]);
				$this->getPlugin()->getPortalMap()->removePortal($portal->getName());

				$sender->sendMessage('§aPortal "'.$portal->getName().'" was deleted');
				return true;

			case 'enable':
			case 'disable':
				if(!isset($params[1]) or $params[1] === ''){
					return false;
				}

				if(!$this->getPlugin()->getPortalMap()->portalExists($params[1])){
					$sender->sendMessage('§cThere\'s no portal called "'.$params[1].'"');
					return true;
				}

				$portal = $this->getPlugin()->getPortalMap()->getPortal($params[1]);
				$portal->setEnabled(strtolower($params[1]) === 'enable');

				$sender->sendMessage('§aPortal "'.$portal->getName().'" was '.($portal->isEnabled() ? 'enabled' : 'disabled'));
				return true;

			case 'edit':
				if(!($sender instanceof Player)){
					$sender->sendMessage('§cYou can only edit portals in-game');
					return true;
				}elseif($this->isEditingPortal($sender)){
					$sender->sendMessage('§cYou\'re already editing a portal');
					return true;
				}

				if(!isset($params[1]) or $params[1] === ''){
					return false;
				}

				if(!$this->getPlugin()->getPortalMap()->portalExists($params[1])){
					$sender->sendMessage('§cThere\'s no portal called "'.$params[1].'"');
					return true;
				}

				$this->portalEditing[$sender->getUniqueId()->toString()] = $this->getPlugin()->getPortalMap()->getPortal($params[1]);
				$portal = $this->getEditingPortal($sender);

				$msg = '§bYou\'re now editing the portal "'.$portal->getName().'"';
				$msg .= "\n".'§7- §eTo list all current commands, write "list"';
				$msg .= "\n".'§7- §eTo quit editing, write "quit"';
				$msg .= "\n".'§7- §aTo add a command, write: §fadd §btype command';
				$msg .= "\n".'  §aAvailable types: §fplayer, console';
				$msg .= "\n".'  §aYou may use {PLAYER} as placeholder for the player name';
				$msg .= "\n".'§7- §cTo remove a command, write a chat message like this: §fremove §bnumber';

				$sender->sendMessage($msg);
				return true;

			case 'list':
				$msg = '§eExisting portals:';
				$vec = new Vector3();

				foreach($this->getPlugin()->getPortalMap()->getPortals() as $portal){
					$vec->setComponents($portal->getBB()->minX, $portal->getBB()->minY, $portal->getBB()->minZ);
					$msg .= "\n".'§b'.$portal->getName().' in world "'.$portal->getWorldName().'" at ('.$this->getPositionString($vec).'§b)';
				}

				$sender->sendMessage($msg);
				return true;

			default:
				return false;
		}
	}

	public function isCreatingPortal(Player $player): bool{
		return isset($this->portalCreating[$player->getUniqueId()->toString()]);
	}

	public function onBlockBreak(BlockBreakEvent $event): void{
		if(!$this->isCreatingPortal($event->getPlayer())){
			return;
		}

		$creationData = &$this->portalCreating[$event->getPlayer()->getUniqueId()->toString()];

		if($creationData['world'] !== $event->getBlock()->level->getFolderName()){
			return;
		}

		if($creationData['pos1'] === null){
			$creationData['pos1'] = $event->getBlock()->asVector3();
			$event->getPlayer()->sendMessage('§aFirst corner at ('.$this->getPositionString($creationData['pos1']).'§a)');
		}else{
			$creationData['pos2'] = $event->getBlock()->asVector3();
			$event->getPlayer()->sendMessage('§aSecond corner at ('.$this->getPositionString($creationData['pos1']).'§a)');

			$portal = new Portal($creationData['name'], $creationData['world'], $creationData['pos1'], $creationData['pos2']);

			if($this->getPlugin()->getPortalMap()->portalExists($portal->getName())){
				$event->getPlayer()->sendMessage('§cThere\'s already a portal called "'.$portal->getName().'"');
				return;
			}

			unset($creationData, $this->portalCreating[$event->getPlayer()->getUniqueId()->toString()]);

			$this->getPlugin()->getPortalMap()->addPortal($portal);
			$event->getPlayer()->sendMessage('§aPortal "'.$portal->getName().'" created');
			$this->getPlugin()->savePortals();
		}

		$event->setCancelled();
	}

	public function isEditingPortal(Player $player): bool{
		return $this->getEditingPortal($player) instanceof Portal;
	}

	public function getEditingPortal(Player $player): ?Portal{
		return $this->portalEditing[$player->getUniqueId()->toString()] ?? null;
	}

	public function onPlayerChat(PlayerChatEvent $event): void{
		if(!$this->isEditingPortal($event->getPlayer())){
			return;
		}else{
			$event->setCancelled();
		}

		$player = $event->getPlayer();
		$args = explode(' ', $event->getMessage());

		switch(strtolower(array_shift($args) ?? '')){
			case 'quit':
				unset($this->portalEditing[$player->getUniqueId()->toString()]);
				$player->sendMessage('§aYou are no longer editing mode');
				break;

			case 'list':
				$portal = $this->getEditingPortal($player);
				$msg = '§7Commands of portal '.$portal->getName().':';

				foreach($portal->getCommands() as $i => $cmd){
					$msg .= "\n§f".$i.'. "'.$cmd->getCommandString().'" §7(type: "'.$cmd->getType().'")';
				}

				$player->sendMessage($msg);
				break;

			case 'add':
				if(!isset($args[0])){
					$player->sendMessage('§cYou must specify the command type. (Either "player" or "console")');
					return;
				}

				$type = strtolower(array_shift($args));

				if($type !== 'console' and $type !== 'player'){
					$player->sendMessage('§cCommand type must be either "player" or "console")');
					return;
				}

				$commandString = trim(implode(' ', $args));
				if($commandString === ''){
					$player->sendMessage('§cYou must specify a command');
					return;
				}

				$command = new PortalCmd($commandString, $type);

				$portal = $this->getEditingPortal($player);
				$portal->addCommand($command);
				$player->sendMessage('§aSuccessfully added the command "'.$command->getCommandString().'" with type "'.$command->getType().'" to the portal "'.$portal->getName().'"');
				$this->getPlugin()->savePortals();
				break;

			case 'remove':
				if(!ctype_digit($args[0] ?? '')){
					$player->sendMessage('§cYou must specify the correct command number as seen in "list"');
					return;
				}

				$portal = $this->getEditingPortal($player);
				$portal->removeCommand($i = (int) $args[0]);
				$this->getPlugin()->savePortals();

				$player->sendMessage('§aRemoved command '.$i.' from portal "'.$portal->getName().'"');
				break;

			default:
				$player->sendMessage('§cUnknown action, try "quit", "list", "add" or "remove"');
				break;
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event): void{
		$uuid = $event->getPlayer()->getUniqueId()->toString();
		unset($this->portalCreating[$uuid], $this->portalEditing[$uuid]);
	}

	static public function getPositionString(Vector3 $pos): string{
		return '§b'.$pos->getFloorX().'§7|§b'.$pos->getFloorY().'§7|§b'.$pos->getFloorZ();
	}
}
