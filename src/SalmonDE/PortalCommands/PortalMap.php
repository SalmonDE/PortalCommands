<?php
declare(strict_types = 1);

namespace SalmonDE\PortalCommands;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;

class PortalMap {

	private $portals = [];
	private $chunkMap = [];

	public function getPortals(): array{
		return $this->portals;
	}

	public function getPortal(string $name): ?Portal{
		return $this->portals[strtolower($name)] ?? null;
	}

	public function addPortal(Portal $portal): void{
		$this->portals[strtolower($portal->getName())] = $portal;
		$this->registerChunks($portal);
	}

	public function removePortal(string $name): void{
		if($this->portalExists($name)){
			$this->unregisterChunks($this->portals[strtolower($name)]);
			unset($this->portals[strtolower($name)]);
		}
	}

	public function portalExists(string $name): bool{
		return ($this->portals[strtolower($name)] ?? null) instanceof Portal;
	}

	protected function registerChunks(Portal $portal): void{
		$worldName = $portal->getWorldName();

		foreach($this->getAABBChunkHashes($portal->getBB()) as $chunkHash){
			$this->chunkMap[$worldName][$chunkHash][] = $portal;
		}
	}

	protected function unregisterChunks(Portal $portal): void{
		$worldName = $portal->getWorldName();

		foreach($this->getAABBChunkHashes($portal->getBB()) as $chunkHash){
			foreach($this->chunkMap[$worldName][$chunkHash] as $key => $p){
				if($portal === $p){
					unset($this->chunkMap[$worldName][$chunkHash][$key]);
					break;
				}
			}
		}
	}

	public function getChunkPortals(Position $pos): array{
		if(!$pos->isValid()){
			throw new \RuntimeException('Invalid position given');
		}

		return $this->chunkMap[$pos->level->getFolderName()][Level::chunkHash($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)] ?? [];
	}

	static public function getAABBChunkHashes(AxisAlignedBB $bb): \Generator{
		$chunkMinX = $bb->minX >> 4;
		$chunkMinZ = $bb->minZ >> 4;

		$chunkMaxX = $bb->maxX >> 4;
		$chunkMaxZ = $bb->maxZ >> 4;

		for($chunkX = $chunkMinX; $chunkX <= $chunkMaxX; ++$chunkX){
			for($chunkZ = $chunkMinZ; $chunkZ <= $chunkMaxZ; ++$chunkZ){
				yield Level::chunkHash($chunkX, $chunkZ);
			}
		}
	}
}
