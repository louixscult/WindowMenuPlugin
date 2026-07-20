<?php

namespace WindowMenu;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use WindowMenu\inventory\WindowInventory;
use WindowMenu\listener\WindowHandler;

class WindowMenuPlugin extends PluginBase
{

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new WindowHandler($this), $this);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        if ($command->getName() !== 'windowwtest') {
            return;
        }

        if (!$sender instanceof Player) {
            return;
        }

        $callback = function (WindowInventory $window, Player $player, Item $item, InventoryTransactionEvent $event) {
            if ($item->getId() === Item::DIAMOND) {
                $player->sendMessage('§8- §eYou clicked in diamond!');
                $window->onClose($player);
            }

            $event->setCancelled(true);
        };

        $window = new WindowInventory(
            $sender->getPosition(),
            '§eWindow test',
            WindowInventory::SINGLE_CHEST,
            $callback
        );
        $window->setItem(0, Item::get(Item::DIAMOND, 0, 1));
    }
}