<?php
/*
 *               _ _
 *         /\   | | |
 *        /  \  | | |_ __ _ _   _
 *       / /\ \ | | __/ _` | | | |
 *      / ____ \| | || (_| | |_| |
 *     /_/    \_|_|\__\__,_|\__, |
 *                           __/ |
 *                          |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Altay
 *
 */

declare(strict_types=1);

namespace pocketmine\entity\passive;

use pocketmine\entity\behavior\AvoidMobTypeBehavior;
use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\MateBehavior;
use pocketmine\entity\behavior\PanicBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\TemptBehavior;
use pocketmine\entity\Tamable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\Player;
use function mt_rand;

class Rabbit extends Tamable{
	public const NETWORK_ID = self::RABBIT;

	public $width = 0.4;
	public $height = 0.5;

	protected function addBehaviors() : void{
		$this->behaviorPool->setBehavior(0, new FloatBehavior($this));
		$this->behaviorPool->setBehavior(1, new MateBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(2, new PanicBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(3, new LookAtPlayerBehavior($this, 14.0));
		$this->behaviorPool->setBehavior(4, new RandomLookAroundBehavior($this));
		$this->behaviorPool->setBehavior(5, new TemptBehavior($this, [Item::CARROT], 1.0));
		$this->behaviorPool->setBehavior(6, new AvoidMobTypeBehavior($this, Player::class, null, 8, 1, 1));
		// TODO: going back to player when they see carrot, and movement fix so they dont SLIDE On ground
	}

	protected function initEntity() : void{
		$this->setMaxHealth(3);
		$this->setMovementSpeed(0.3);
		$this->setFollowRange(16);
		$this->propertyManager->setInt(self::DATA_VARIANT, intval($this->namedtag->getInt("RabbitType", mt_rand(1, 6))));

		parent::initEntity();
	}

	public function getName() : string{
		return "Rabbit";
	}


	public function getXpDropAmount() : int{
		return rand(1, 3);
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(Item::RABBIT_HIDE, 0, rand(0, 1)),
			($this->isOnFire() ? ItemFactory::get(Item::COOKED_RABBIT, 0, rand(0, 1)) : ItemFactory::get(Item::RAW_RABBIT, 0, rand(0, 1)))
		];
	}
}