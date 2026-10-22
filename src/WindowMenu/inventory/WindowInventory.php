<?php

/*
*  ██╗░░░░░███████╗░█████╗░██████╗░██╗░░██╗██████╗░
*  ██║░░░░░██╔════╝██╔══██╗██╔══██╗╚██╗██╔╝██╔══██╗
*  ██║░░░░░█████╗░░███████║██████╔╝░╚███╔╝░██║░░██║
*  ██║░░░░░██╔══╝░░██╔══██║██╔══██╗░██╔██╗░██║░░██║
*  ███████╗███████╗██║░░██║██║░░██║██╔╝╚██╗██████╔╝
*  ╚══════╝╚══════╝╚═╝░░╚═╝╚═╝░░╚═╝╚═╝░░╚═╝╚═════╝░
*              Author: LearXD#1044
*/

namespace WindowMenu\inventory;

use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\FakeBlockMenu;
use pocketmine\inventory\InventoryType;
use pocketmine\level\Position;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use pocketmine\network\protocol\BlockEntityDataPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\Player;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Server;
use WindowMenu\manager\WindowManager;

class WindowInventory extends BaseInventory
{

    const SINGLE_CHEST = 0;
    const DOUBLE_CHEST = 1;

    const CHEST_SIZE = [
        self::SINGLE_CHEST => 27,
        self::DOUBLE_CHEST => 54
    ];

    /** @var string */
    protected $invCustomName = '';

    /** @var integer */
    protected $invType = self::SINGLE_CHEST;

    /** @var Position */
    protected $invPos = null;

    /** @var FakeBlockMenu */
    protected $invHolder = null;

    /** @var callable */
    protected $invClosure = null;

    /**
     * @param Position $invPos
     * @param string $invName
     * @param int $invType - default -> 1 (single)
     * @param Item[] $invItems
     * @param callable $context - `(WindowInventory $window, Player $player, Item $item, InventoryTransactionEvent $event)`
     */
    public function __construct(
        Position $invPos,
        string $invName = null,
        int $invType = self::SINGLE_CHEST,
        array $invItems = [],
        callable $invClosure = null
    ) {
        $this->invPos = $invPos->add(0, 2, 0);
        $this->invCustomName = $invName;
        $this->invClosure = $invClosure;
        $this->invType = $invType;
        $this->invHolder = new FakeBlockMenu($this, $this->invPos);
        parent::__construct(
            $this->invHolder,
            InventoryType::get(InventoryType::CHEST),
            $invItems,
            self::CHEST_SIZE[$invType]
        );
    }

    protected function createChest(Player $player, int $xPos = 0)
    {
        $x = (int) $this->invPos->getX() + $xPos;
        $y = (int) $this->invPos->getY();
        $z = (int) $this->invPos->getZ();

        $pk = new UpdateBlockPacket();
        $pk->x = $x;
        $pk->y = $y;
        $pk->z = $z;
        $pk->blockId = 54;
        $pk->blockData = 0;
        $pk->flags = UpdateBlockPacket::FLAG_ALL;

        $player->dataPacket($pk);

        $compound = new CompoundTag("", [
            new StringTag("id", Tile::CHEST),
            new IntTag("x", $x + $xPos),
            new IntTag("y", $y),
            new IntTag("z", $z)
        ]);

        if (!is_null($this->invCustomName)) {
            $compound->CustomName = new StringTag("CustomName", $this->invCustomName);
        }

        if ($xPos === 0) {
            $compound->pairx = new IntTag("pairx", (int) $this->invPos->getX() + 1);
            $compound->pairz = new IntTag("pairz", $z);
        }

        $nbt = new NBT(NBT::LITTLE_ENDIAN);
        $nbt->setData($compound);

        $pk = new BlockEntityDataPacket();
        $pk->x = $x + $xPos;
        $pk->y = $y;
        $pk->z = $z;
        $pk->namedtag = $nbt->write();
        $player->dataPacket($pk);
    }

    protected function removeChest(Player $player, int $xPos = 0)
    {
        $blockX = (int) $this->invPos->getX() + $xPos;
        $blockY = (int) $this->invPos->getY();
        $blockZ = (int) $this->invPos->getZ();

        $pk = new UpdateBlockPacket();
        $pk->x = $blockX;
        $pk->y = $blockY;
        $pk->z = $blockZ;
        $pk->blockId = $player->getLevel()->getBlockIdAt($blockX, $blockY, $blockZ);
        $pk->blockData = $player->getLevel()->getBlockDataAt($blockX, $blockY, $blockZ);
        $pk->flags = UpdateBlockPacket::FLAG_NONE;

        $player->dataPacket($pk);
    }

    public function onOpen(Player $who)
    {
        if (!$who->isOnline()) {
            return;
        }

        $this->createChest($who);

        if ($this->invType == self::DOUBLE_CHEST) {
            $this->createChest($who, 1);
        }

        $inventory = $this;

        $this->process(function () use ($who, $inventory) {
            if (!$who->isOnline()) {
                return;
            }

            $inventory->viewers[spl_object_hash($who)] = $who;

            $pk = new ContainerOpenPacket();
            $pk->windowid = $who->getWindowId($inventory);
            $pk->type = $inventory->getType()->getNetworkType();
            $pk->slots = $inventory->getSize();
            $pk->x = (int) $inventory->invPos->getX();
            $pk->y = (int) $inventory->invPos->getY();
            $pk->z = (int) $inventory->invPos->getZ();

            $who->dataPacket($pk);
            $inventory->sendContents($who);

            WindowManager::addPlayerWindow($who, $inventory);
        });
    }

    public function onClose(Player $who)
    {
        if (!$who->isOnline()) {
            return;
        }

        if (
            WindowManager::getPlayerWindow($who) !== null &&
            WindowManager::getPlayerWindow($who) !== $this
        ) {
            return;
        }

        $this->removeChest($who);

        if ($this->invType == self::DOUBLE_CHEST) {
            $this->removeChest($who, 1);
        }

        $inventory = $this;

        $this->process(function () use ($who, $inventory) {
            unset($inventory->viewers[spl_object_hash($who)]);

            $pk = new ContainerClosePacket();
            $pk->windowid = $who->getWindowId($inventory);

            $who->dataPacket($pk);
            WindowManager::removePlayerWindow($who);
        });
    }

    public function process(callable $callable, array $arguments = [], int $delay = 10)
    {
        if ($delay > 0) {
            Server::getInstance()->getScheduler()->scheduleDelayedTask(
                new CallbackTask($callable, $arguments),
                $delay
            );
        } else {
            call_user_func_array($callable, $arguments);
        }
    }

    public function setClosure(callable $callable)
    {
        $this->invClosure = $callable;
        return $this;
    }

    public function getClosure(): callable
    {
        return $this->invClosure;
    }
}
