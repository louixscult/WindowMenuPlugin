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
use pocketmine\network\protocol\BlockEntityDataPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\scheduler\CallbackTask;
use pocketmine\Server;
use WindowMenu\manager\WindowManager;

class WindowInventory extends BaseInventory
{

    const SINGLE_CHEST = 1;
    const DOUBLE_CHEST = 2;

    /** @var string */
    protected $customName = '';

    /** @var int */
    protected $type = InventoryType::CHEST;

    /** @var int */
    protected $size = 27;

    /** @var Position */
    protected $pos = null;

    /** @var FakeBlockMenu */
    protected $holder = null;

    /** @var callable */
    protected $closure = null;

    /**
     * Window constructor.
     *
     * @param Position $position
     * @param string $name
     * @param callable $context
     * @param int $type
     * @param int $size
     */
    public function __construct(
        Position $position,
        string $name,
        int $size = self::SINGLE_CHEST,
        callable $context = null,
        int $type = InventoryType::CHEST
    ) {
        $this->pos = $position->add(0, 2);
        $this->customName = $name;
        $this->closure = $context;
        $this->type = $type;
        $this->size = $size;

        $this->holder = new FakeBlockMenu($this, $this->pos);
        parent::__construct($this->holder, InventoryType::get($type));
    }

    /**
     * @param Player $who
     * @return void
     */
    public function onOpen(Player $who)
    {
        if (!$who->isOnline()) {
            return;
        }

        $pk = new UpdateBlockPacket();
        $pk->x = $this->pos->x;
        $pk->y = $this->pos->y;
        $pk->z = $this->pos->z;
        $pk->blockId = 54;
        $pk->blockData = 0;
        $pk->flags = UpdateBlockPacket::FLAG_ALL;
        $who->dataPacket($pk);

        $compound = new CompoundTag("", [
            new StringTag("id", InventoryType::CHEST),
            new StringTag("CustomName", $this->customName),
            new IntTag("x", (int) $this->pos->x),
            new IntTag("y", (int) $this->pos->y),
            new IntTag("z", (int) $this->pos->z)
        ]);

        if($this->size >= self::DOUBLE_CHEST) {
            $compound['pairx'] = new IntTag("pairx", ((int) $this->pos->getX()) + 1);
            $compound['pairz'] = new IntTag("pairz", (int) $this->pos->getZ());
        }

        $nbt = new NBT(NBT::LITTLE_ENDIAN);
        $nbt->setData($compound);

        $pk = new BlockEntityDataPacket();
        $pk->x = $this->holder->x;
        $pk->y = $this->holder->y;
        $pk->z = $this->holder->z;
        $pk->namedtag = $nbt->write();
        $who->dataPacket($pk);

        $inventory = $this;
        $inventory->viewers[spl_object_hash($who)] = $who;

        $this->process(
            function () use ($who, $inventory) {
                $pk = new ContainerOpenPacket();
                $pk->windowid = $who->getWindowId($inventory);
                $pk->type = $inventory->getType()->getNetworkType();
                $pk->slots = $inventory->getSize();
                $pk->x = $inventory->pos->getX();
                $pk->y = $inventory->pos->getY();
                $pk->z = $inventory->pos->getZ();

                $who->dataPacket($pk);
                $this->sendContents($who);
                WindowManager::addPlayerWindow($who, $inventory);
            }
        );
    }

    /**
     * @param Player $who
     * @return void
     */
    public function onClose(Player $who)
    {
        if (!$who->isOnline()) {
            return;
        }

        if (WindowManager::getPlayerWindow($who) !== null and WindowManager::getPlayerWindow($who) !== $this) {
            return;
        }

        $pk = new UpdateBlockPacket();
        $pk->x = $this->holder->x;
        $pk->y = $this->holder->y;
        $pk->z = $this->holder->z;
        $pk->blockId = $who->getLevel()->getBlockIdAt($this->holder->x, $this->holder->y, $this->holder->z);
        $pk->blockData = $who->getLevel()->getBlockDataAt($this->holder->x, $this->holder->y, $this->holder->z);
        $pk->flags = UpdateBlockPacket::FLAG_NONE;
        $who->dataPacket($pk);

        $inventory = $this;

        $this->process(
            function () use ($who, $inventory) {
                unset($inventory->viewers[spl_object_hash($who)]);
                $pk = new ContainerClosePacket();
                $pk->windowid = $who->getWindowId($inventory);
                $who->dataPacket($pk);
                WindowManager::removePlayerWindow($who);
            }
        );
    }

    /**
     * @param callable $callable
     * @param array $arguments
     * @param int $delay
     * @return void
     */
    public function process(callable $callable, array $arguments = [], int $delay = 5) {
        if($delay > 0) {
            Server::getInstance()->getScheduler()->scheduleDelayedTask(new CallbackTask($callable, $arguments), $delay);
        } else {
            $callable(...$arguments);
        }
    }

    /**
     * @param callable $callable
     * @returnh void
     */
    public function setClosure(callable $callable)
    {
        return $this->closure = $callable;
    }

    /** @return callable */
    public function getClosure()
    {
        return $this->closure;
    }
}