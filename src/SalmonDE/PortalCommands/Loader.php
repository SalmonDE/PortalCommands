<?php
declare(strict_types = 1);

namespace SalmonDE\PortalCommands;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use SalmonDE\PortalCommands\Commands\PortalCommand;
use SalmonDE\PortalCommands\Commands\WorldTPCommand;

class Loader extends PluginBase implements Listener {

	private $portalMap;

	public function onEnable(): void{
		$this->saveResource('config.yml');

		$this->portalMap = new PortalMap();
		$this->loadPortals();

		$this->registerCommands();

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable(): void{
		if(isset($this->portalMap) and count($this->getPortalMap()->getPortals()) > 0){
			$this->savePortals();
		}
	}

	protected function registerCommands(): void{
		$this->getServer()->getCommandMap()->register('PortalCommands', $cmd = new PortalCommand($this));
		$this->getServer()->getPluginManager()->registerEvents($cmd, $this);

		if((bool) $this->getConfig()->get('worldtp-command', true)){
			$this->getServer()->getCommandMap()->register('PortalCommands', new WorldTPCommand($this));
		}
	}

	protected function loadPortals(): void{
		if(file_exists($this->getDataFolder().'portals.json')){
			$portalData = json_decode(file_get_contents($this->getDataFolder().'portals.json'), true);

			foreach($portalData as $portal){
				$this->getPortalMap()->addPortal(Portal::fromArray($portal));
			}
		}
	}

	public function savePortals(): void{
		if(!isset($this->portalMap)){
			return;
		}

		$portalData = [];
		foreach($this->getPortalMap()->getPortals() as $portal){
			$portalData[] = $portal->toArray();
		}

		file_put_contents($this->getDataFolder().'portals.json', json_encode($portalData, JSON_PRETTY_PRINT));
	}

	public function getPortalMap(): PortalMap{
		return $this->portalMap;
	}

	/**
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onPlayerMove(PlayerMoveEvent $event): void{
		if($event->getFrom()->distance($event->getTo()) === 0.0){
			return;
		}

		foreach($this->getPortalMap()->getChunkPortals($event->getTo()) as $portal){
			$toFloor = new Position($event->getTo()->getFloorX(), $event->getTo()->getFloorY(), $event->getTo()->getFloorZ(), $event->getTo()->level);
			if($portal->isEnabled() and $portal->isInside($toFloor)){
				$fromFloor = new Position($event->getFrom()->getFloorX(), $event->getFrom()->getFloorY(), $event->getFrom()->getFloorZ(), $event->getFrom()->level);
				if(!$portal->isInside($fromFloor)){
					$portal->enterPlayer($event->getPlayer());
				}
				break;
			}
		}
	}
}
