<?php

#               Copyright (C) 2016 MadeAja/BaliGamerz
##    ███╗░░░███╗░█████╗░██████╗░███████╗░█████╗░░░░░░██╗░█████╗░
##    ████╗░████║██╔══██╗██╔══██╗██╔════╝██╔══██╗░░░░░██║██╔══██╗
##    ██╔████╔██║███████║██║░░██║█████╗░░███████║░░░░░██║███████║
##    ██║╚██╔╝██║██╔══██║██║░░██║██╔══╝░░██╔══██║██╗░░██║██╔══██║
##    ██║░╚═╝░██║██║░░██║██████╔╝███████╗██║░░██║╚█████╔╝██║░░██║
##    ╚═╝░░░░░╚═╝╚═╝░░╚═╝╚═════╝░╚══════╝╚═╝░░╚═╝░╚════╝░╚═╝░░╚═╝


namespace BaliGamerz\SkyBlock\Menu;


use BaliGamerz\SkyBlock\Entity\NpcClass;
use BaliGamerz\SkyBlock\FunctionLogic\ListFunction;
use BaliGamerz\SkyBlock\island\Island;
use BaliGamerz\SkyBlock\libraries\MadeForm\CustomForm;
use BaliGamerz\SkyBlock\libraries\MadeForm\ModalForm;
use BaliGamerz\SkyBlock\libraries\MadeForm\SimpleForm;
use BaliGamerz\SkyBlock\Main;
use BaliGamerz\SkyBlock\SkyBlockManager\SetupListener;
use BaliGamerz\SkyBlock\Utils;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\Server;

class PublicMenu
{
    public function getPlugin(): Main
    {
        return Main::getInstance();
    }

    public function sendMessageForm($sender, $message)
    {
        $sender->sendMessage("§f[§aSKYBLOCK§f] §f» §b" . $message);
    }


    public function listLogic(): ListFunction
    {
        return new ListFunction();
    }


    public function showRunningQuests(Player $player)
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) return;
            switch ($data) {
                case 0;
                    $this->playerFormClicked($player);
                    break;
            }
        });
        $playerData = $this->getPlugin()->hasPlayerQuest($player);
        if ($playerData !== null) {
            $progressTotal = ((int)$playerData['objectiveData']['progress'] / (int)$playerData['objectiveData']['item']['amount']) * 100;
            $arrayContent = [
                "§6=====================§r",
                "§fQuests Name: §a" . $playerData['objectiveData']['name'],
                "       ",
                "§fQuestId: §d" . $playerData['objectiveData']['questID'],
                "§fQuest Type: §c" . $playerData['objectiveData']['type'],
                "       ",
                "§fProgress: §6" . $this->formatInt($playerData['objectiveData']['progress']) . "§7/§a" . $this->formatInt($playerData['objectiveData']['item']['amount']),
                "§7[§r" . Utils::getProgress($progressTotal) . "§7]",
                "       ",
                "§fItem: ",
                "  §fId: §a" . $playerData['objectiveData']['item']['id'],
                "  §fMeta: §a" . $playerData['objectiveData']['item']['meta'],
                "§6====================="
            ];
        } else {
            $arrayContent = [
                "§6=====================§r",
                "§fQuests Name: §aNO Quest",
                "        ",
                "§fQuestId: §dNO Quest",
                "§fQuest Type: §cNO Quest",
                "       ",
                "§fProgress: §6NO Quest",
                "§7[§7]",
                "       ",
                "§fItem: ",
                "  §fId: §aNO Quest",
                "  §fMeta: §aNO Quest",
                "§6====================="
            ];
        }
        $form->setTitle("§a» §8Running Quests §a«");
        $form->setContent(implode("\n", $arrayContent));
        $form->addButton("§cBack\n§7Click to use");
        $form->sendToPlayer($player);
    }

    public function depositIslandMoney(Player $player)
    {
        $form = new CustomForm(function (Player $player, $data = null) {
            if ($data === null) $this->moneyMenu($player);
            if (!isset($data[1])) {
                $this->sendMessageForm($player, "§cPlease input integer");
                return;
            }
            $mentahanMoney = (int)$data[1];
            $moneyInteger = (int)str_replace('-', '', $mentahanMoney);
            if (!is_int($moneyInteger)) {
                $this->sendMessageForm($player, "§cPlease input integer not string");
                return;
            }
            $island = $this->getPlugin()->getIslandManager()->getIslandByPlayer($player);
            if ($island !== null) {
                if ($island->reduceMoney($moneyInteger)) {
                    $this->getPlugin()->economy->addMoney($player,$moneyInteger);
                    $this->sendMessageForm($player, "§aSend " . $this->formatInt($moneyInteger) . " money to you has been successfully");
                    Utils::addSound($player, 2, 1, 'random.levelup');
                } else {
                    $this->sendMessageForm($player, "§cIsland money not have " . $this->formatInt($moneyInteger));
                }
            }
        });
        $form->setTitle("§a» §8Deposit §a«");
        $form->addLabel("§b» §3Island Money: §7[§a" . $this->formatInt($this->getPlugin()->getIslandMoney($player)) . "§7/§e" . $this->formatInt($this->getPlugin()->getIslandMaxMoney($player)) . "§7]\n§b» §3Your Money: §a" . $this->formatInt($this->getPlugin()->economy->myMoney($player)) . "\n");
        $form->addInput("Input your take amount", "Write here", 1000);
        $form->sendToPlayer($player);
    }

    /**
     * @param Player $player
     */
    public function buyIsland(Player $player)
    {
        $dataIsland = $this->getPlugin()->dataThemas['list'];
        $rows = [];
        foreach ($dataIsland as $slot => $value) {
            if ($value['payment'] !== false) {
                $rows[$slot] = $value;
            }
        }
        $form = new SimpleForm(function (Player $player, $data = null) use ($rows) {
            if ($data === null) return;
            if ($data === 0) {
                $this->sendMessageForm($player, "Thanks for see");
            } else {
                $economy = $this->getPlugin()->economy;
                $array = array_keys($rows);
                $button = $array[$data - 1];
                $dataTheme = $rows[$button];
                if ($economy->myMoney($player) >= $dataTheme['payment']['price']) {
                    $this->getPlugin()->economy->reduceMoney($player, (int)$dataTheme['payment']['price']);
                    $item = Item::get((int)$dataTheme['item']['id'], (int)$dataTheme['item']['meta']);
                    $tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
                    $tag->setFloat($dataTheme['payment']['id'], 1.0);
                    $item->setNamedTag($tag)->setCustomName($dataTheme['item']['name'] . " Island")->setLore($dataTheme["item"]["lore"]);
                    $player->getInventory()->addItem($item);
                    Utils::addSound($player, 400, 1, "random.levelup");
                } else {
                    $this->sendMessageForm($player, "You don't have enough money");
                }
            }
        });
        $economy = $this->getPlugin()->economy;
        $form->setTitle("§a» §8Buy a island §a«");
        $form->addButton("§cExit\n§7Click to use");
        foreach ($rows as $slot => $value) {
            if ($economy->myMoney($player) >= $value['payment']['price']) {
                $form->addButton("§a» §3" . $value['name'] . "\n§aClick to buy", $value['button-type'], $value['button-img']);
            } else {
                $form->addButton("§a» §3" . $value['name'] . "\n§cPrice: " . $this->formatInt($value['payment']['price']), $value['button-type'], $value['button-img']);
            }
        }
        $form->sendToPlayer($player);
    }


    /**
     * @param Player $player
     * @param $type
     * @return Item|null
     */
    public function hasPlayerVoucher(Player $player, $type): ?Item
    {
        foreach ($player->getInventory()->getContents() as $slot => $items) {
            if ($items->getNamedTag()->hasTag($type)) {
                return $items;
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @param $type
     * @return array|null
     */
    public function getArrayVoucher(Player $player, $type): ?array
    {
        foreach ($player->getInventory()->getContents() as $slot => $items) {
            if ($items->getNamedTag()->hasTag($type)) {
                return ['item' => $items, 'index' => $slot];
            }
        }
        return null;
    }


    /**
     * @param Player $player
     */
    public function buyMana(Player $player)
    {
        $form = new CustomForm(function (Player $player, $data = null) {
            if ($data === null) return;
            if (!isset($data[1])) {
                $this->sendMessageForm($player, "§cPlease input integer");
                return;
            }
            $mentahanMoney = (int)$data[1];
            $moneyInteger = (int)str_replace('-', '', $mentahanMoney);
            if (!is_int($moneyInteger)) {
                $this->sendMessageForm($player, "§cPlease input integer not string");
                return;
            }

            $price = $this->getPlugin()->config['user']['mana_price'];
            $money = $this->getPlugin()->economy;
            $result = $price * $data[1];
            if ($money->myMoney($player) >= $result) {
                if ($this->getPlugin()->addPlayerMana($player, $moneyInteger)) {
                    $this->sendMessageForm($player, "§aSuccessfully buy §b" . $moneyInteger . "x mana");
                    $this->getPlugin()->economy->reduceMoney($player, $result);
                    Utils::addSound($player, 2, 6, 'note.bit');
                } else {
                    $this->sendMessageForm($player, "§cYour mana capacity is full.");
                }
            } else {
                $this->sendMessageForm($player, "§cYou don't have money");
            }
        });
        $form->setTitle("§a» §8Buy a mana §a«");
        $form->addLabel("§b» §3Your Money: §a" . $this->formatInt($this->getPlugin()->economy->myMoney($player)) . "\n    \n§bPrice of mana\n§b1x mana = §e" . $this->formatInt($this->getPlugin()->config['user']['mana_price']));
        $form->addInput("Input amount", "Write here", 1000);
        $form->sendToPlayer($player);
    }

    public function addIslandMoney(Player $player)
    {
        $form = new CustomForm(function (Player $player, $data = null) {
            if ($data === null) $this->moneyMenu($player);
            if (!isset($data[1])) {
                $this->sendMessageForm($player, "§cPlease input integer");
                return;
            }
            $mentahanMoney = (int)$data[1];
            $moneyInteger = (int)str_replace('-', '', $mentahanMoney);
            if (!is_int($moneyInteger)) {
                $this->sendMessageForm($player, "§cPlease input integer not string");
                return;
            }
            $moneyInteger = (int)$moneyInteger;
            $island = $this->getPlugin()->getIslandManager()->getIslandByPlayer($player);
            if ($island !== null) {
                if ($island->addMoney($moneyInteger)) {
                    if ($this->getPlugin()->economy->myMoney($player) >= $moneyInteger) {
                        $this->getPlugin()->economy->reduceMoney($player, $moneyInteger);
                        $this->sendMessageForm($player, "§aSuccessfully saving money in island");
                        Utils::addSound($player, 2, 1, 'random.levelup');
                    } else {
                        $this->sendMessageForm($player, "§cYou don't have money");
                    }
                } else {
                    $this->sendMessageForm($player, "§cIsland money capacity is full. Please update a island capacity");
                }
            }
        });
        $form->setTitle("§a» §8Add Money §a«");
        $form->addLabel("§b» §3Island Money: §7[§a" . $this->formatInt($this->getPlugin()->getIslandMoney($player)) . "§7/§e" . $this->formatInt($this->getPlugin()->getIslandMaxMoney($player)) . "§7]\n§b» §3Your Money: §a" . $this->formatInt($this->getPlugin()->economy->myMoney($player)) . "\n");
        $form->addInput("Input your save amount", "Write here", 1000);
        $form->sendToPlayer($player);
    }

    /**
     * @param Player $player
     */
    public function moneyMenu(Player $player)
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    $this->playerFormClicked($player);
                    break;
                case 1:
                    $this->addIslandMoney($player);
                    break;
                case 2:
                    $this->depositIslandMoney($player);
                    break;
                case 3:
                    $this->updateTier($player);
                    break;
            }
        });
        $form->setTitle("§a» §8Island Bank §a«");
        $form->setContent("§b» §3Island Money: §7[§a" . $this->formatInt($this->getPlugin()->getIslandMoney($player)) . "§7/§e" . $this->formatInt($this->getPlugin()->getIslandMaxMoney($player)) . "§7]\n§b» §3Island Tier: §d" . $this->getPlugin()->getIslandMoneyTier($player));
        $form->addButton("§cBack\n§7Click to use");
        $form->addButton("§b» §2Add island money\n§7Click to use");
        $form->addButton("§b» §2Take island money\n§7Click to use");
        $form->addButton("§b» §2Update Bank\n§7Click to update");
        $form->sendToPlayer($player);
    }

    public function updateTier(Player $player)
    {
        $form = new ModalForm(function (Player $player, $data = null) {
            if ($data === null) $this->moneyMenu($player);
            switch ($data) {
                case 1:
                    $island = $this->getPlugin()->getIslandManager()->getIslandByPlayer($player);
                    if ($island instanceof Island) {
                        $money = $this->getPlugin()->economy->myMoney($player);
                        if ($money >= $island->getPriceTier()) {
                            if ($island->updateMoneyTier()) {
                                Utils::addSound($player, 2, 1, "random.levelup");
                                $this->sendMessageForm($player, "§aUpdate bank tier successfully");
                            } else {
                                $this->sendMessageForm($player, "§cYour island has been max tier");
                            }
                        } else {
                            $this->sendMessageForm($player, "§cYou don't have enought a money");
                        }
                    }
                    break;
            }
        });
        $tierArray = $this->getPlugin()->getNextIslandMoneyTier($player);
        $form->setTitle("§a» §8Update Tier §a«");
        $form->setContent("§eApakah kamu akan update\n§etier ke tier §d" . $tierArray['next-int'] . "\n§edengan penambahan capatity sebesar: §a" . $tierArray['next-money'] . "\n§edengan harga: §f" . $tierArray['price'] . "\n\n§eClick OK untuk lanjut.\n§eClick Cancel untuk membatalkan");
        $form->setButton1("§b» §2OK");
        $form->setButton2("§b» §cCancel");
        $form->sendToPlayer($player);
    }


    /**
     * @param $n
     * @param int $precision
     * @return string
     */
    public function formatInt($n, $precision = 1): string
    {
        $n = is_numeric($n) ? $n : 0;
        if ($n < 900) {
            // 0 - 900
            $n_format = number_format($n, $precision);
            $suffix = 'C';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            // 0.9t+
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }
        if ($precision > 0) {
            $dotzero = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }

        return $n_format . $suffix;
    }


    /**
     * @param Player $player
     * @param array $result
     */
    public function selectedFreeOrPaid(Player $player, array $result)
    {
        $form = new SimpleForm(function (Player $player, $data = null) use ($result) {
            switch ($data) {
                case 0:
                    $this->paidConfigure($player, $result);
                    break;
                case 1:
                    $name = $result['name'];
                    $dirname = $result['dirname'];
                    $button_img = $result['button-img'];
                    $button_type = $result['button-type'];
                    $this->getPlugin()->queue[strtolower($player->getName())] = true;
                    $resultData = [
                        'name' => $name,
                        'index' => 1,
                        "dirname" => $dirname,
                        "spawn" => null,
                        "button-type" => $button_type,
                        "button-img" => $button_img,
                        "npcPosition" => null,
                        "signPosition" => null,
                        "item" => [],
                        "payment" => false];
                    $this->getPlugin()->getServer()->getPluginManager()->registerEvents(new SetupListener($this->getPlugin(), $player, $resultData), $this->getPlugin());
                    $this->sendMessageForm($player, "Create themes with name " . ucfirst($name) . " Successfully\nPlease break block to set island spawn");
                    break;
            }
        });
        $form->setTitle("§a» §8Select Mode payment §a«");
        $form->addButton("§b» §3Is It Paid?");
        $form->addButton("§b» §3This is free?");
        $form->sendToPlayer($player);
    }


    public function paidConfigure(Player $player, array $result, $content = "§ePlease configure of\npayment manager")
    {
        $form = new CustomForm(function (Player $player, $data = null) use ($result) {
            if ($data === null) return;
            if ($data[1] === null) {
                $this->paidConfigure($player, $result, "§cPlease configure price");
                return;
            }
            if ($data[3] === null) {
                $this->paidConfigure($player, $result, "§cPlease configure data item");
                return;
            }
            $explode = explode(":", $data[3]);
            $arrayToString = implode(":", array_slice($explode, 3));
            $explodeLore = explode(":", $arrayToString);
            if ($explode[0] === null) {
                $this->paidConfigure($player, $result, "§cPlease configure data item");
                return;
            }
            if ($explode[1] === null) {
                $this->paidConfigure($player, $result, "§cPlease configure data item");
                return;
            }
            if ($explode[2] === null) {
                $this->paidConfigure($player, $result, "§cPlease configure data item");
                return;
            }
            $item = [
                "name" => $explode[0],
                "id" => $explode[1],
                "meta" => $explode[2],
                "lore" => $explodeLore
            ];
            $name = $result['name'];
            $dirname = $result['dirname'];
            $button_img = $result['button-img'];
            $button_type = $result['button-type'];
            $payment = [
                "id" => $name,
                "price" => $data[1]
            ];
            $this->getPlugin()->queue[strtolower($player->getName())] = true;
                $resultData = [
                'name' => $name,
                'index' => 1,
                "dirname" => $dirname,
                "spawn" => null,
                "button-type" => $button_type,
                "button-img" => $button_img,
                "npcPosition" => null,
                "signPosition" => null,
                "item" => $item,
                "payment" => $payment];
            $this->getPlugin()->getServer()->getPluginManager()->registerEvents(new SetupListener($this->getPlugin(), $player, $resultData), $this->getPlugin());
            $this->sendMessageForm($player, "§eCreate themes with name " . ucfirst($name) . " Successfully\nPlease break block to set island spawn");
        });
        $form->setTitle("§a» §8Paid configure §a«");
        $form->addLabel($content);
        $form->addInput("Price of theme", "write here");
        $form->addLabel("\nItem data\nExample:\nCustomName:ID:Meta:Lore1:Lore2:Lore3");
        $form->addInput("Item Data", "write Item data", "BasicIsland:339:0:I Have you enjoy:in this skyblock:      :Use this item if you create a island");
        $form->sendToPlayer($player);
    }


    public function createThemesForm(Player $player, $content = '')
    {
        $form = new CustomForm(function (Player $player, $data = null) {
            if ($data === null) return;
            if ($data[1] === null) {
                $this->themesManager($player, "§cPlease valid configure");
                return;
            }
            if ($data[2] === null) {
                $this->themesManager($player, "§cPlease valid configure");
                return;
            }
            if (isset($this->getPlugin()->queue[strtolower($player->getName())])) {
                $this->themesManager($player, "§cYou already create setup mode. Please cancel.\nTo create other themes");
                return;
            }
            $name = $data[1];
            if (isset($this->getPlugin()->dataThemas['list'][$data[2]])) {
                $this->themesManager($player, "§cWorld name already register this server");
                return;
            }
            $player->getLevel()->save(true);
            $this->selectedFreeOrPaid($player, ["name" => $name, "dirname" => $data[2], "button-type" => $data[3] ?? 0, "button-img" => $data[4] ?? "textures/ui/icon_trailer"]);
        });
        $form->setTitle("§a» §8Theme configure §a«");
        $form->addLabel($content);
        $form->addInput("Write theme name", "Write here");
        $form->addInput("Write world name", "Write here", $player->getLevel()->getFolderName());
        $form->addInput("Write Button Type", "0 or 1", "0");
        $form->addInput("Write Button Image", "Write here", "textures/ui/icon_trailer");
        $form->sendToPlayer($player);
    }

    public function themesManager(Player $player, $content = '')
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    if (!isset($this->getPlugin()->queue[strtolower($player->getName())])) {
                        $this->themesManager($player, "§cYou can't this setup mode ");
                        return;
                    }
                    unset($this->getPlugin()->queue[strtolower($player->getName())]);
                    $this->themesManager($player, "§aYou success cancel setup mode");
                    break;
                case 1:
                    $this->createThemesForm($player);
                    break;
                case 2:
                    $this->settingForm($player);
                    break;
            }
        });
        $form->setTitle("§a» §8Themes Manager §a«");
        $form->setContent($content);
        $form->addButton("§dCancel Queue\n§7Click to cancel");
        $form->addButton("§dAdd theme\n§7Click to use");
        $form->addButton("§cBack\n§7Click to use");
        $form->sendToPlayer($player);
    }

    public function successCreateTheme(Player $player, $content, $theme)
    {
        $form = new SimpleForm(function (Player $player, $data = null) use ($theme){
            if ($data === null) return;
            switch ($data) {
                case 0:
                    if (!isset($this->getPlugin()->queue[strtolower($player->getName())])) {
                        $this->settingForm($player, "You can't this setup mode ");
                        return;
                    }
                    $dir = $theme['dirname'];
                    Utils::copy(Server::getInstance()->getDataPath() . "worlds/" . $dir, $this->getPlugin()->getDataFolder() . $dir);
                    $this->settingForm($player, "Success setup themes");
                    unset($this->getPlugin()->queue[strtolower($player->getName())]);
                    break;
                case 1:
                    if (!isset($this->getPlugin()->queue[strtolower($player->getName())])) {
                        $this->settingForm($player, "You can't this setup mode ");
                        return;
                    }
                    unset($this->getPlugin()->queue[strtolower($player->getName())]);
                    $this->settingForm($player, "You success cancel setup mode");
                    break;
            }
        });
        $form->setTitle("§c» §8Option Theme §c«");
        $form->setContent($content);
        $form->addButton("§dFinish\n§7Click to use");
        $form->addButton("§dCancel\n§7Click to use");
        $form->sendToPlayer($player);
    }


    public function createIslandForm($player, $content = "§aCreated a new island\n")
    {
        $form = new CustomForm(function (Player $player, $data) {
            if ($data === null) {
                return;
            }
            $island_name = str_replace($this->onProtect(), $this->onReplace(), $data[1]);
            if (file_exists($this->getPlugin()->getDataFolder() . "islands/{$island_name}.json")) {
                $this->onNameAlready($player);
                return;
            }
            if (is_dir($this->getPlugin()->getServer()->getDataPath() . "worlds/" . $island_name)) {
                $this->onNameAlready($player);
                return;
            }
            if (is_numeric($data[1])) {
                $this->onNameAlready($player, 'Not use int name');
                return;
            }
            $this->selectedThemasIsland($player, $island_name);
        });
        $form->setTitle("§a» §8Create a your island §a«");
        $form->addLabel($content);
        $form->addInput("\n§aPlease enter the name of \n§ayour island in the column below\n", "Type String");
        $form->sendToPlayer($player);
    }

    public function onNameAlready($player, $content = 'Island name already in use')
    {
        $form = new CustomForm(function (Player $player, $data) {
            if ($data === null) {
                return;
            }
            $island_name = str_replace($this->onProtect(), $this->onReplace(), $data[1]);
            if (file_exists($this->getPlugin()->getDataFolder() . "islands/{$island_name}.json")) {
                $this->onNameAlready($player);
                return;
            }
            if (is_dir($this->getPlugin()->getServer()->getDataPath() . "worlds/" . $island_name)) {
                $this->onNameAlready($player);
                return;
            }
            if (is_numeric($data[1])) {
                $this->onNameAlready($player, 'Not use int name');
                return;
            }
            $this->selectedThemasIsland($player, $island_name);
        });
        $form->setTitle("§a» §8Create a your island §a«");
        $form->addLabel("§eWarning!. §c{$content}\n§cplease input something name");
        $form->addInput("\n§aPlease enter the name of \n§ayour island in the column below\n", "Type String");
        $form->sendToPlayer($player);
    }

    public function settingForm(Player $player, $content = '')
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    $this->themesManager($player);
                    break;
                case 1:
                    $this->addNpc($player);
                    break;
                case 2:
                    $this->getPlugin()->lobbyData['lobbyPosition'] = [
                        'vector' => $player->getX() . ":" . ($player->getY() + 2) . ":" . $player->getZ(),
                        'level' => $player->getLevel()->getFolderName()];
                    $this->getPlugin()->setLobby();
                    $this->sendMessageForm($player, "§aSet lobby server successfully");
                    break;
            }
        });
        $form->setTitle("§a» §8Setting Manager §a«");
        $form->setContent($content);
        $form->addButton("§3Theme Manager\n§7Click to select!");
        $form->addButton("§3Npc Manager\n§7Click to select!");
        $form->addButton("§3Set Lobby Server\n§7Click to select!");
        $form->addButton("§cExit\n§7Click to select!");
        $form->sendToPlayer($player);
    }

    public function playerFormClicked(Player $player, $content = '')
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    $lobby = $this->getPlugin()->lobbyServer;
                    if ($lobby !== null) {
                        if (!$this->getPlugin()->getServer()->isLevelLoaded($lobby->getLevel()->getFolderName())) {
                            $this->getPlugin()->getServer()->loadLevel($lobby->getLevel()->getFolderName());
                        }
                        $player->teleport($lobby);
                    } else {
                        $this->sendMessageForm($player, "§cLobby not register");
                    }
                    break;
                case 1:
                    $this->showRunningQuests($player);
                    break;
                case 2:
                    $this->onMenu($player);
                    break;
                case 3:
                    $this->showAchievement($player);
                    break;
                case 4:
                    $this->islandStats($player);
                    break;
                case 5:
                    $this->moneyMenu($player);
                    break;
                case 6:
                    (new ShopLogic())->onOpen($player);
                    break;
                case 7:
                    $this->playerFormClicked($player);
                    break;
            }
        });
        $form->setTitle("§a» §8Hello §6" . $player->getName() . " §a«");
        $form->setContent($content);
        $form->addButton("§3Go to lobby\n§7Click to select!");
        $form->addButton("§3Quests\n§7Click to select!");
        $form->addButton("§3Island Menu\n§7Click to select!");
        $form->addButton("§3Achievement\n§7Click to select!");
        $form->addButton("§3Island Stats\n§7Click to select!");
        $form->addButton("§3Island Money\n§7Click to select!");
        $form->addButton("§3Shops\n§7Click to select!");
        $form->addButton("§bBack\n§7Click to select!");
        $form->addButton("§cExit\n§7Click to select!");
        $form->sendToPlayer($player);
    }


    public function islandStats(Player $player)
    {
        $main = $this->getPlugin();
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) return;
        });
        $form->setTitle("§a» §8Island Stats §a«");
        $island = $this->getPlugin()->getIslandManager()->getIslandByPlayer($player);
        $content = '';
        $format = [];
        if ($island !== null) {
            $members = $island->getAllMembers();
            foreach ($members as $member) {
                $content .= "§d- §a" . $member . "§r\n";
            }
            $format = [
                "§fLevel: §7" . $island->getLevel() . "",
                "        ",
                "§fMine: §a" . $island->getMine(),
                "§fProgress: §b" . $main->getIntProgress($player) . "§7/§a" . $main->getProgressSize($player),
                "§7[" . $main->getProgress($player) . "§7]",
                "         ",
                "§fBalance: §6" . $this->formatInt($island->money['money']),
                "§fSize: §c" . $main->getIslandSize($player),
                "         ",
                "§fMembers: §e" . count($island->getPlayersOnline()) . "§7/§a" . count($island->getAllMembers()),
                "§fList of members" => $content
            ];
        }
        $form->setContent(implode("\n", $format));
        $form->addButton("§cExit\n§7Click to select!");
        $form->sendToPlayer($player);
    }

    public function showAchievement(Player $player)
    {
        $from = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) return;
            switch ($data) {
                case 0;
                    $this->playerFormClicked($player);
                    break;
            }
        });
        $arrayQuests = $this->getPlugin()->quests['quests'];
        $dataPlayer = $this->getPlugin()->playerDataPath[strtolower($player->getName())]['objectives']['success'];
        $content = "\n";
        foreach ($arrayQuests as $slot => $value) {
            if (in_array($value['questID'], $dataPlayer)) {
                $content .= "§e" . $value['name'] . "\n §b>> §aCompleted §b<<\n\n";
            } else {
                $content .= "§e" . $value['name'] . "\n §b>> §cNo Completed §b<<\n\n";
            }
        }
        $from->setTitle("§a» §8Achievement §a«");
        $from->setContent($content);
        $from->addButton("§b» §eOK");
        $from->sendToPlayer($player);
    }


    public function addNpc(Player $player, $content = '')
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    $this->listLogic()->createLeaderBoard($player, "&6&lYour Island|Player Profile\n      \n&fLevel: &7{level}\n&fMine: &a{mine}\n&fProgress: &b{progress_int}&7/&a{progress_size}\n&7[{progress}&7]\n&fIsland Name: &a{sky_name}\n&fBalance: &a{balance}\n&fCompletedTask: &a{task}\n&fSize: &c{size}\n       \n&l&eCLICK FOR STATS", false);
                    break;
                case 1:
                    $this->listLogic()->createLeaderBoard($player, "{mine_top}", true, 1.5);
                    break;
                case 2:
                    $this->listLogic()->createLeaderBoard($player, "{size_top}", true, 1.5);
                    break;
                case 3:
                    $this->listLogic()->createLeaderBoard($player, "{level_top}", true, 1.5);
                    break;
                case 4:
                    $this->listLogic()->createLeaderBoard($player, "{money_top}", true, 1.5);
                    break;
                case 5:
                    $entity = $this->getNearFloating($player);
                    if ($entity !== null) {
                        $entity->close();
                        $this->sendMessageForm($player, "§aSuccess removing npc");
                    } else {
                        $this->sendMessageForm($player, "§cNot found npc");
                    }
                    break;
                case 6:
                    $this->settingForm($player);
                    break;
            }
        });
        $form->setTitle("§b» §8Spawn NPC §b«");
        $form->setContent($content);
        $form->addButton("§dNpc Statistic\n§7Click to spawn!");
        $form->addButton("§dTop Mine\n§7Click to spawn!");
        $form->addButton("§dTop Size\n§7Click to spawn!");
        $form->addButton("§dTop Level\n§7Click to spawn!");
        $form->addButton("§dTop Money\n§7Click to spawn!");
        $form->addButton("§dRemove Npc Radius\n§7Click to remove!");
        $form->addButton("§cBack\n§7Click to select!");
        $form->sendToPlayer($player);
    }


    public function getNearFloating(Player $player): ?NpcClass
    {
        $level = $player->getLevel();
        foreach ($level->getEntities() as $entity) {
            if ($entity instanceof NpcClass) {
                if ($player->distance($entity) <= 2 && $entity->distance($player) > 0) {
                    return $entity;
                }
            }
        }
        return null;
    }

    /**
     * @param Player $sender
     * @param $island
     */
    public function selectedThemasIsland(Player $sender, $island)
    {
        if (count($this->getPlugin()->dataThemas['list']) < 1) {
            $this->sendMessageForm($sender, "This server don't have island");
            return;
        }
        $dataIsland = $this->getPlugin()->dataThemas['list'];
        $rows = [];
        foreach ($dataIsland as $name => $valueOfIsland) {
            if ($valueOfIsland['payment'] === false) {
                $rows[$name] = $valueOfIsland;
            } else {
                if ($this->hasPlayerVoucher($sender, $valueOfIsland['payment']['id']) !== null) {
                    $rows[$name] = $valueOfIsland;
                }
            }
        }
        if (count($rows) < 0) {
            $this->sendMessageForm($sender, "§cYou don't have a island theme. Please buy. Usage /buyisland");
            return;
        }
        $form = new SimpleForm(function (Player $sender, $data = null) use ($island, $rows) {
            if ($data === null) {
                $this->createIslandForm($sender, "§cPlease selected the island themas\n");
                return;
            }
            if ($data === 0) {
                $this->createIslandForm($sender, "§cPlease selected the island themas\n");
            } else {
                $array = array_keys($rows);
                $button = $array[$data - 1];
                $islandName = $island ?? $sender->getName();
                $function = new ListFunction();
                $theme = $rows[$button];
                if ($function->createFunction($sender, $islandName, $rows[$button])) {
                    if ($theme['payment'] !== false) {
                        $item = $this->getArrayVoucher($sender, $theme['payment']['id']);
                        $item['item']->setCount($item['item']->getCount() - 1);
                        $sender->getInventory()->setItem($item['index'], $item['item']);
                    }
                }
            }
        });
        $form->setTitle("§a» §8Selected Theme §a«");
        $form->addButton("§cBack\n§7Click to select!");
        foreach ($rows as $slot => $value) {
            $form->addButton("§b» §3" . $value['name'] . "\n§8Click To Select", $value['button-type'], $value['button-img']);
        }
        $form->sendToPlayer($sender);
    }

    public function onMenu($sender, $content = '')
    {
        $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
        if (empty($config["island"])) {
            $this->createIslandForm($sender);
        } else {
            $form = new SimpleForm(function (Player $sender, $data) {
                if ($data === null) return;
                switch ($data) {
                    case 0:
                        $this->listLogic()->joinFunction($sender);
                        break;
                    case 1:
                        $this->listLogic()->homeFunction($sender);
                        break;
                    case 2:
                        $this->manageIsland($sender);
                        break;
                    case 3:
                        $this->managePlayer($sender);
                        break;
                    case 4:
                        $this->visitIsland($sender);
                        break;
                }
            });
            $form->setTitle("§a» §8SkyBlock Menu §a«");
            $form->setContent($content);
            $form->addButton("Join\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
            $form->addButton("Home\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
            $form->addButton("Island Management\n§d§l»§r §7Tap to select!", 0, "textures/ui/icon_recipe_item");
            $form->addButton("Member Management\n§d§l»§r §7Tap to select!", 0, "textures/ui/icon_multiplayer");
            $form->addButton("Visit Island\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
            $form->addButton("§cExit", 0, "textures/blocks/barrier");
            $form->sendToPlayer($sender);
        }
    }

    public function manageIsland($sender, $content = '')
    {
        $form = new SimpleForm(function (Player $sender, $data) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    $this->listLogic()->lockFunction($sender);
                    break;
                case 1:
                    $this->listLogic()->setHomeFunction($sender);
                    break;
                case 2:
                    $this->listLogic()->resetFunction($sender);
                    break;
                case 3:
                    $this->listLogic()->disbandFunction($sender);
                    break;
                case 4:
                    $this->visitIsland($sender);
                    break;
                case 5:
                    $this->onMenu($sender);
                    break;
                case 6:
                    break;
            }
        });
        $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
        $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);

        $form->setTitle("§a» §8Island Manager §a«");
        $form->setContent($content);
        if ($island instanceof Island) {
            if ($island->isLocked()) {
                $form->addButton("Unlock Island\n§d§l»§r §7Tap to select!", 0, "textures/ui/icon_unlocked");
            } else {
                $form->addButton("§8Lock Island\n§d§l»§r §7Tap to select!", 0, "textures/ui/icon_lock");
            }

        }
        $form->addButton("Sethome\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
        $form->addButton("Reset\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
        $form->addButton("Delete Island\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
        $form->addButton("Visit Other Island\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
        $form->addButton("Back\n§d§l»§r §7Tap to select!", 0, "textures/ui/book_arrowleft_hover");
        $form->addButton("§cExit", 0, "textures/blocks/barrier");
        $form->sendToPlayer($sender);
    }


    /**
     * @param $sender
     * @param string $content
     */
    public function managePlayer($sender, $content = '')
    {
        $form = new SimpleForm(function (Player $sender, $data) {
            if ($data === null) return;

            switch ($data) {
                case 0:
                    $this->kickTarget($sender);
                    break;
                case 1:
                    $this->inviteTarget($sender);
                    break;
                case 2:
                    $this->acceptTarget($sender);
                    break;
                case 3:
                    $this->rejectTarget($sender);
                    break;
                case 4:
                    $this->listLogic()->leaveFunction($sender);
                    break;
                case 5:
                    $this->removeTarget($sender);
                    break;
                case 6:
                    $this->onMenu($sender);
                    break;
                case 7:
                    break;
            }
        });
        $form->setTitle("§a» §8Member Manager §a«");
        $form->setContent($content);
        $form->addButton("Kick Player\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
        $form->addButton("Invite Player\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
        $form->addButton("Accept Invite\n§d§l»§r §7Tap to select!", 0, "textures/ui/check");
        $form->addButton("Deny Invite\n§d§l»§r §7Tap to select!", 0, "textures/ui/cancel");
        $form->addButton("Leave Island\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
        $form->addButton("Remove Player\n§d§l»§r §7Tap to select!", 0, "textures/blocks/barrel_side");
        $form->addButton("Back\n§d§l»§r §7Tap to select!", 0, "textures/ui/book_arrowleft_hover");
        $form->addButton("§cExit", 0, "textures/blocks/barrier");
        $form->sendToPlayer($sender);
    }

    private $array = [
        "break",
        "craft",
        "place",
        "buy"
    ];

    /**
     * @param Player $player
     * @param $result_name
     */
    public function addQuests(Player $player, $result_name)
    {
        $form = new CustomForm(function (Player $player, $data = null) {
            if ($data === null) return;
            if ($data[1] === null) {
                $this->sendMessageForm($player, "Please change name");
                return;
            }
            if ($data[3] === null) {
                $this->sendMessageForm($player, "Please change item data");
                return;
            }
            if ($data[6] === null) {
                $this->sendMessageForm($player, "Please change reward command");
                return;
            }
            if ($data[4] === false) {
                $img_type = 0;
            } else {
                $img_type = 1;
            }
            if ($data[6] === null) {
                $explode = [];
            } else {
                $explode = explode(":", $data[6]);
            }
            $itemData = explode(":", $data[3]);
            $array = [
                "name" => $data[1],
                "questID" => time() . "-" . $data[1],
                "type" => $this->array[$data[2]],
                "progress" => 0,
                "item" => ["id" => (int)$itemData[0], "meta" => (int)$itemData[1], "amount" => (int)$itemData[2]],
                "type-img" => $img_type,
                "url-img" => $data[5],
                "rewardCommands" => $explode
            ];
            $this->getPlugin()->quests["quests"][] = $array;
            $this->sendMessageForm($player, "Success add quest with name: " . $data[1]);
        });
        $form->setTitle("Add quest");
        $form->addLabel('');
        $form->addInput("Input a Quest name", '', $result_name);
        $form->addDropdown("Choose a type quest", $this->array);
        $form->addInput("Input a item data\nExample: ID:META:AMOUNT", '', "1:0:10");
        $form->addToggle("Is path? or Url?", false);
        $form->addInput("Input a img source");
        $form->addInput("Input a quest rewards\nExample: command1:command2:ect\n{player} to send to player\n{name} to use display name", '', "givemoney {player} 1000:say {name} Congratulation");
        $form->sendToPlayer($player);
    }

    public function kickTarget($sender)
    {
        $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
        $playerData = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $value) {
            if ($value->getName() !== $sender->getName() and $value->getLevel()->getName() === $config['island']) {
                $playerData[] = $value->getName();
            }
        }
        if (count($playerData) < 1) {
            $this->sendMessageForm($sender, "Not found player in your island");
            return;
        }
        $form = new CustomForm(function (Player $sender, $data) use ($playerData) {
            if (isset($data[1])) {
                $this->listLogic()->kickFunction($sender, $playerData[$data[1]]);
            }
            unset($playerData);
        });
        $form->setTitle("§a» §8Kick Player §a«");
        $form->addLabel('');
        $form->addDropdown("Choose a player", $playerData);
        $form->sendToPlayer($sender);
        $playerData = null;
    }

    public function inviteTarget($sender)
    {
        $playerData = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $value) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($value);
            if (empty($config["island"])) {
                if ($value->getName() !== $sender->getName()) {
                    $playerData[] = $value->getName();
                }
            }
        }
        if (count($playerData) < 1) {
            $this->sendMessageForm($sender, "Not found player empty island");
            return;
        }
        $form = new CustomForm(function (Player $sender, $data) use ($playerData) {
            if (isset($data[1])) {
                $this->listLogic()->inviteFunction($sender, $playerData[$data[1]]);
            }
            unset($playerData);
        });
        $form->setTitle("§a» §8Invite Player §a«");
        $form->addLabel('');
        $form->addDropdown("Choose a player", $playerData);
        $form->sendToPlayer($sender);
        $playerData = null;
    }

    public function acceptTarget($sender)
    {
        $form = new CustomForm(function (Player $sender, $data) {
            if (isset($data[1])) {
                $this->listLogic()->acceptFunction($sender, $data[1]);
            }
        });
        $form->setTitle("§a» §8Accept Invite §a«");
        $form->addLabel('');
        $form->addInput("Choose player name", "Write here");
        $form->sendToPlayer($sender);
        $playerData = null;
    }

    public function rejectTarget($sender)
    {
        $form = new CustomForm(function (Player $sender, $data) {
            if (isset($data[1])) {
                $this->listLogic()->rejectFunction($sender, $data[1]);
            }
        });
        $form->setTitle("§a» §8Reject Invite §a«");
        $form->addLabel('');
        $form->addInput("Choose player name", "Write here");
        $form->sendToPlayer($sender);
        $playerData = null;
    }

    public function removeTarget($sender)
    {
        $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
        $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
        $playerData = [];
        if ($island instanceof Island) {
            foreach ($island->getMembers() as $value) {
                if ($value !== $sender->getName()) {
                    $playerData[] = $value;
                }
            }
        }
        if (count($playerData) < 1) {
            $this->sendMessageForm($sender, "Not found player in you members");
            return;
        }
        $form = new CustomForm(function (Player $sender, $data) use ($playerData) {
            if (isset($data[1])) {
                $this->listLogic()->removeFunction($sender, $playerData[$data[1]]);
            }
            unset($playerData);
        });
        $form->setTitle("§a» §8Remove Player §a«");
        $form->addLabel('');
        $form->addDropdown("Choose a player", $playerData);
        $form->sendToPlayer($sender);
        $playerData = null;
    }

    public function makeLeader($sender)
    {
        $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
        $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
        $playerData = [];
        if ($island instanceof Island) {
            foreach ($island->getMembers() as $value) {
                if ($value !== $sender->getName()) {
                    $playerData[] = $value;
                }
            }
        }
        if (count($playerData) < 1) {
            $this->sendMessageForm($sender, "Not found player in you members");
            return;
        }
        $form = new CustomForm(function (Player $sender, $data) use ($playerData) {
            if (isset($data[1])) {
                $this->listLogic()->makeleaderFunction($sender, $playerData[$data[1]]);
            }
            unset($playerData);
        });
        $form->setTitle("§a» §8Make a leader §a«");
        $form->addLabel('');
        $form->addDropdown("Choose a player", $playerData);
        $form->sendToPlayer($sender);
        $playerData = null;
    }

    public function visitIsland($sender)
    {
        $playerData = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $value) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($value);
            if (!empty($config["island"])) {
                if ($value->getName() !== $sender->getName()) {
                    $playerData[] = $value->getName();
                }
            }
        }
        if (count($playerData) < 1) {
            $this->sendMessageForm($sender, "Not found player island");
            return;
        }
        $form = new CustomForm(function (Player $sender, $data) use ($playerData) {
            if (isset($data[1])) {
                $target = $this->getPlugin()->getServer()->getPlayer($playerData[$data[1]]);
                if ($target !== null) {
                    $this->listLogic()->tpFunction($sender, $target);
                }else{
                    $sender->sendMessage("§cPlayer not found!. Usage: /visit [playername] | /visit");
                }
            }
        });
        $form->setTitle("§a» §8Visit Island by player §a«");
        $form->addLabel('');
        $form->addDropdown("Choose a player", $playerData);
        $form->sendToPlayer($sender);
        $playerData = null;
    }


    public function onProtect(): array
    {
        return array(
            '@',
            '#',
            '%',
            '&',
            '_',
            ';',
            "'",
            '"',
            ',',
            '~',
            '`',
            '|',
            '!',
            '$',
            '^',
            '*',
            '(',
            ')',
            '-',
            '+',
            '=',
            '{',
            '}',
            '[',
            ']',
            ':',
            '<',
            '>',
            '?',
            '.',
            '/',
        );
    }

    public function onReplace(): array
    {
        return array(
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        );
    }
}