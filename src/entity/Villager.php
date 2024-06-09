<?php

declare(strict_types=1);

namespace pocketmine\entity;

use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\world\BlockTransaction;
use pocketmine\block\BlockLegacyIds;

class Villager extends Living implements Ageable {
    public const PROFESSION_FARMER = 0;
    public const PROFESSION_LIBRARIAN = 1;
    public const PROFESSION_PRIEST = 2;
    public const PROFESSION_BLACKSMITH = 3;
    public const PROFESSION_BUTCHER = 4;

    private const TAG_PROFESSION = "Profession"; //TAG_Int

    public static function getNetworkTypeId() : string {
        return EntityIds::VILLAGER;
    }

    private bool $baby = false;
    private int $profession = self::PROFESSION_FARMER;

    protected function getInitialSizeInfo() : EntitySizeInfo {
        return new EntitySizeInfo(1.8, 0.6); // Sesuaikan dengan tinggi mata jika diperlukan
    }

    public function getName() : string {
        return "Villager";
    }

    protected function initEntity(CompoundTag $nbt) : void {
        parent::initEntity($nbt);
        $this->setProfession($nbt->getInt(self::TAG_PROFESSION, self::PROFESSION_FARMER));
    }

    public function saveNBT() : CompoundTag {
        $nbt = parent::saveNBT();
        $nbt->setInt(self::TAG_PROFESSION, $this->getProfession());
        return $nbt;
    }

    public function setProfession(int $profession) : void {
        if ($profession < self::PROFESSION_FARMER || $profession > self::PROFESSION_BUTCHER) {
            $profession = self::PROFESSION_FARMER;
        }
        $this->profession = $profession;
        $this->networkPropertiesDirty = true;
    }

    public function getProfession() : int {
        return $this->profession;
    }

    public function isBaby() : bool {
        return $this->baby;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties) : void {
        parent::syncNetworkData($properties);
        $properties->setGenericFlag(EntityMetadataFlags::BABY, $this->baby);
        $properties->setInt(EntityMetadataProperties::VARIANT, $this->profession);
    }

    public function onUpdate(int $currentTick) : bool {
        if($this->isClosed()){
            return false;
        }

        if($this->profession === self::PROFESSION_FARMER){
            $this->performFarming();
        }

        return parent::onUpdate($currentTick);
    }

    private function performFarming() : void {
        $randomX = mt_rand(-5, 5);
        $randomZ = mt_rand(-5, 5);
        $position = $this->getPosition()->add($randomX, 0, $randomZ);
        $block = $this->getWorld()->getBlock($position);

        if ($block->getId() === BlockLegacyIds::DIRT || $block->getId() === BlockLegacyIds::GRASS) {
            $this->getWorld()->setBlock($position, BlockFactory::getInstance()->get(BlockLegacyIds::FARMLAND, 0));
            $seed = ItemFactory::getInstance()->get(ItemIds::WHEAT_SEEDS);
            $this->getWorld()->setBlock($position->up(), $seed->getBlock());
        }
    }
}
