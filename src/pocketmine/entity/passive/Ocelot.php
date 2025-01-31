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

use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\FollowOwnerBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\MateBehavior;
use pocketmine\entity\behavior\MeleeAttackBehavior;
use pocketmine\entity\behavior\NearestAttackableTargetBehavior;
use pocketmine\entity\behavior\PanicBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\RandomStrollBehavior;
use pocketmine\entity\behavior\StayWhileSittingBehavior;
use pocketmine\entity\behavior\TemptBehavior;
use pocketmine\entity\passive\Chicken;
use pocketmine\entity\Tamable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;
use function mt_rand;

class Ocelot extends Tamable{
	public const NETWORK_ID = self::OCELOT;

	public $width = 0.6;
	public $height = 0.7;
	/** @var StayWhileSittingBehavior */
	protected $behaviorSitting;

	protected function addBehaviors() : void{
		$this->behaviorPool->setBehavior(0, new FloatBehavior($this));
		$this->behaviorPool->setBehavior(1, new PanicBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(2, $this->behaviorSitting = new StayWhileSittingBehavior($this));
		$this->behaviorPool->setBehavior(3, new MateBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(4, new TemptBehavior($this, [
			Item::RAW_SALMON,
			Item::RAW_FISH
		], 1.0));
		$this->behaviorPool->setBehavior(5, new FollowOwnerBehavior($this, 1, 10, 2));
		$this->behaviorPool->setBehavior(6, new LookAtPlayerBehavior($this, 14.0));
		$this->behaviorPool->setBehavior(7, new RandomLookAroundBehavior($this));
		$this->behaviorPool->setBehavior(8, new RandomStrollBehavior($this, 0.8));
		$this->behaviorPool->setBehavior(9, new MeleeAttackBehavior($this, 1.0));

		$this->targetBehaviorPool->setBehavior(1, new NearestAttackableTargetBehavior($this, Chicken::class, false));
		//$this->targetBehaviorPool->setBehavior(9, new NearestAttackableTargetBehavior($this, SeaTurtle::class, false));
		//Also  i have no idea why attack dont works
	}

	protected function initEntity() : void{
		$this->setMaxHealth(10);
		$this->setMovementSpeed(0.3);
		$this->setFollowRange(16);
		$this->setAttackDamage(3);
		$this->propertyManager->setInt(self::DATA_VARIANT, intval($this->namedtag->getInt("CatType", 0)));
		$this->propertyManager->setInt(self::DATA_COLOR, intval($this->namedtag->getInt("CollarColor", mt_rand(0, 15))));

		parent::initEntity();
	}

	public function getName() : string{
		return "Ocelot";
	}

	public function onInteract(Player $player, Item $item, Vector3 $clickPos) : bool{
		if(!$this->isImmobile()){
			if($item->getId() == Item::RAW_SALMON || $item->getId() == Item::RAW_FISH){
				if($player->isSurvival()){
					$item->pop();
				}
				if($this->isTamed()){
					$this->setTargetEntity(null);
					$this->setInLove(true);
					$this->setHealth(min($this->getMaxHealth(), $this->getHealth() + 2));
				}elseif(mt_rand(0, 2) == 0){
					$this->setOwningEntity($player);
					$this->setTamed();
					$this->broadcastEntityEvent(ActorEventPacket::TAME_SUCCESS);
				}else{
					$this->broadcastEntityEvent(ActorEventPacket::TAME_FAIL);
				}
				return true;
			}
		}
		return parent::onInteract($player, $item, $clickPos);
	}

	public function getXpDropAmount() : int{
		$damage = $this->getLastDamageCause();
		if($damage instanceof EntityDamageByEntityEvent){
			$damager = $damage->getDamager();
			if($damager instanceof Player || ($damager instanceof Wolf && $damager->isTamed())){
				return rand(1, ($this->isInLove() ? 7 : 3));
			}
		}
		return 0;
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(Item::STRING, 0, rand(0, 2)),
		];
	}


	public function attack(EntityDamageEvent $source) : void{
		if($source->getCause() !== EntityDamageEvent::CAUSE_FALL){
			parent::attack($source);
		}
	}
}