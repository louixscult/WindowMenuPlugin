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
        if ($command->getName() !== 'windowtest') {
            return;
        }
        if (!$sender instanceof Player) {
            return;
        }
        $type = $args[0] ?? '';
        if (empty($type)) {
            $sender->sendMessage('§8- §eUse: §7/windowtest <single|double>');
            return;
        }
        $typeInv = null;
        if ($type == 'single') {
            $typeInv = WindowInventory::SINGLE_CHEST;
        } else if ($type == 'double') {
            $typeInv = WindowInventory::DOUBLE_CHEST;
        } else {
            $sender->sendMessage('§8- §cType invalid! §7Types: single, double.');
        }
        $callback = function (WindowInventory $window, Player $player, Item $item, InventoryTransactionEvent $event) {
            if ($item->getId() === Item::EMERALD) {
                $player->sendMessage('§8- §eYou clicked in emerald!');
                $window->onClose($player);
            }
            $event->setCancelled(true);
        };
        $window = new WindowInventory(
            $sender->getPosition(),
            '§eWindow Test',
            $typeInv,
            [Item::get(Item::EMERALD, 0, 1)],
            $callback
        );
        $sender->addWindow($window);
    }
}
