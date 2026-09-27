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

namespace WindowMenu\manager;

use pocketmine\Player;
use WindowMenu\inventory\WindowInventory;

class WindowManager
{

    /** @var array<string, WindowInventory> */
    protected static $players = [];

    /**
     * @param Player $player
     * @param WindowInventory $inventory
     * @return WindowInventory
     */
    public static function addPlayerWindow(Player $player, WindowInventory $inventory): WindowInventory
    {
        return self::$players[strtolower($player->getName())] = $inventory;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function removePlayerWindow(Player $player): bool
    {
        if (isset(self::$players[strtolower($player->getName())])) {
            unset(self::$players[strtolower($player->getName())]);
        }
        return true;
    }

    /**
     * @param Player $player
     * @return WindowInventory|null
     */
    public static function getPlayerWindow(Player $player)
    {
        return self::$players[strtolower($player->getName())] ?? null;
    }
}