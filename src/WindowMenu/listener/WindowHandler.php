<?php

namespace WindowMenu\listener;

use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\item\Item;
use WindowMenu\inventory\WindowInventory;
use WindowMenu\manager\WindowManager;
use WindowMenu\WindowMenuPlugin;

class WindowHandler implements Listener
{
    /** @var WindowMenuPlugin */
    protected $owner = null;

    /** @var array<string, Item> */
    protected $transitionedItems = [];

    public function __construct(WindowMenuPlugin $plugin)
    {
        $this->owner = $plugin;
    }

    /**
     * @priority HIGHEST
     */
    public function drop(PlayerDropItemEvent $event) {
        $item = $event->getItem();
        if(isset($this->transitionedItems[$item->getCustomName()])) {
            unset($this->transitionedItems[$item->getCustomName()]);
            $event->setCancelled(true);
        }
    }

    public function close (InventoryCloseEvent $event) {
        $player = $event->getPlayer();
        if (WindowManager::getPlayerWindow($player)) {
            WindowManager::removePlayerWindow($player);
        }
    }

    public function transaction(InventoryTransactionEvent $event): bool
    {
        /** @var \pocketmine\inventory\SimpleTransactionQueue */
        $transaction = $event->getQueue();
        $player = $transaction->getPlayer();

        if ($event->isCancelled()) {
            return false;
        }

        if ($window = WindowManager::getPlayerWindow($player)) {

            foreach ($transaction->getTransactions() as $trans) {
                /** @var Item $item */
                $item = $trans->getTargetItem();
                try {
                    if ($item->getId() > 0) { // WIN10 ED BE LIKE

                        $window->getClosure()->call($window, $window, $player, $item, $event);

                        if($event->isCancelled()) {
                            if($item->getCustomName() != "") {
                                $player->getInventory()->remove($item);
                                $this->transitionedItems[$item->getCustomName()] = $item;
                            }
                            $player->getFloatingInventory()->remove($item);
                        }

                    }
                } catch (\Exception $exception) {
                    var_dump($exception->getMessage());
                }
            }

        }

        return true;
    }
}