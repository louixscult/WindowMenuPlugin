<h1 align="center"> WindowMenuPlugin v0.2.1 </h1>
<p align="center"> A small library for creating interactive windows. </p>

# What is this plugin?
WindowMenuPlugin It is a plugin library that enables the creation of windows in PocketMine-MP 2.0.0. The plugin was based on library code and then transformed into a plugin because it was more practical to use.

# How use this plugin
## 1. Plugin dependecies:
To use the library, we first need to specify it in the `plugin.yml` file of your plugin. Follow the example below:

```yaml
name: YourPlugin
version: 0.1
depend: WindowMenuPlugin # <-- inform with a depedencie
main: My\Plugin
api: 2.0.0
```

## 2. Window closure:
The closure in the library is used to add a function without having to manually specify the Windows event. The closure has 4 perimeters: the inventory, the player, the item, and the inventory transaction event.

We have this example at below:

```php
$callback = function (
  // -- Window Inventory --
  WindowInventory $inventory,
  // -- Window Player --
  Player $player,
  // -- Windows Item --
  Item $item
  // Window Listener Event (Inventory Transaction)
  InventoryTransactionEvent $event
) {
  if($item->getId() == Item::EMERALD){
    $inventory->onClose($player); // close window
    $player->sendMessage("§aYou used window!"); // send message to player
    $event->setCanceled(true); // cancel event
  }
};
```

## 3. Send window to player:
To send the window to the player, we need to create the inventory object, specifying the inventory location as required and optionally the name, type of chest, inventory itens and Inventory callable.

We have the following example:
```php
$window = new WindowInventory(
  $player->getPosition(),
  '§eWindow Test',
  WindowInventory::SINGLE_CHEST,
  [Item::get(Item::EMERALD, 0, 1)],
  $callback
);
$player->addWindow($window);
```
