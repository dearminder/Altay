<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\entity\hostile;

use pocketmine\entity\behavior\JumpAttackBehavior;
use pocketmine\entity\behavior\MeleeAttackBehavior;
use pocketmine\entity\Entity;
use pocketmine\entity\WaterAnimal;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use function atan2;
use function mt_rand;
use function sqrt;
use const M_PI;

class Dolphin extends WaterAnimal{
	public const NETWORK_ID = self::DOLPHIN;

	public $width = 0.9;
	public $height = 0.6;
	//TODO: Add Jumping from water and attacking

	/** @var Vector3 */
	public $swimDirection = null;
	public $swimSpeed = 1;

	private $switchDirectionTicker = 0;

	public function initEntity() : void{
		$this->setMaxHealth(10);

		parent::initEntity();
	}

	public function getName() : string{
		return "Dolphin";
	}

	public function attack(EntityDamageEvent $source) : void{
		parent::attack($source);
		if($source->isCancelled()){
			return;
		}

		if($source instanceof EntityDamageByEntityEvent){
			$this->swimSpeed = mt_rand(350, 650) / 2000;
			$e = $source->getDamager();
			if($e !== null){
				$this->swimDirection = (new Vector3($this->x - $e->x, $this->y - $e->y, $this->z - $e->z))->normalize();
			}

		}
	}

	private function generateRandomDirection() : Vector3{
		return new Vector3(mt_rand(-1000, 1000) / 1000, mt_rand(-500, 500) / 1000, mt_rand(-1000, 1000) / 1000);
	}


	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		if(++$this->switchDirectionTicker === 100 or $this->isCollided){
			$this->switchDirectionTicker = 0;
			if(mt_rand(0, 100) < 50){
				$this->swimDirection = null;
			}
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->isAlive()){

			if($this->y > 62 and $this->swimDirection !== null){
				$this->swimDirection->y = -0.5;
			}

			$inWater = $this->isUnderwater();
			if(!$inWater){
				$this->swimDirection = null;
			}elseif($this->swimDirection !== null){
				if($this->motion->lengthSquared() <= $this->swimDirection->lengthSquared()){
					$this->motion = $this->swimDirection->multiply($this->swimSpeed);
				}
			}else{
				$this->swimDirection = $this->generateRandomDirection();
				$this->swimSpeed = mt_rand(50, 100) / 2000;
			}

			$f = sqrt(($this->motion->x ** 2) + ($this->motion->z ** 2));
			$this->yaw = (-atan2($this->motion->x, $this->motion->z) * 180 / M_PI);
			$this->pitch = (-atan2($f, $this->motion->y) * 1 / M_PI);
		}

		return $hasUpdate;
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(Item::RAW_SALMON, 0, 1),
			//TODO: Add percentage drop for bone
		];
	}

	protected function applyGravity() : void{
		if(!$this->isUnderwater()){
			parent::applyGravity();
		}
	}


}