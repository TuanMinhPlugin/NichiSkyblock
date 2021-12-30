<?php

#               Copyright (C) 2016 MadeAja/BaliGamerz
##    ███╗░░░███╗░█████╗░██████╗░███████╗░█████╗░░░░░░██╗░█████╗░
##    ████╗░████║██╔══██╗██╔══██╗██╔════╝██╔══██╗░░░░░██║██╔══██╗
##    ██╔████╔██║███████║██║░░██║█████╗░░███████║░░░░░██║███████║
##    ██║╚██╔╝██║██╔══██║██║░░██║██╔══╝░░██╔══██║██╗░░██║██╔══██║
##    ██║░╚═╝░██║██║░░██║██████╔╝███████╗██║░░██║╚█████╔╝██║░░██║
##    ╚═╝░░░░░╚═╝╚═╝░░╚═╝╚═════╝░╚══════╝╚═╝░░╚═╝░╚════╝░╚═╝░░╚═╝


namespace BaliGamerz\SkyBlock\command;


use BaliGamerz\SkyBlock\Menu\PublicMenu;
use BaliGamerz\SkyBlock\Menu\ShopLogic;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;

use BaliGamerz\SkyBlock\invitation\Invitation;
use BaliGamerz\SkyBlock\island\Island;
use BaliGamerz\SkyBlock\Main;

class SkyBlockCommand extends Command
{
    /** @var Main */
    private $plugin;

    /**
     * SkyBlockBlockCommand constructor.
     *
     * @param Main $plugin
     */
    public function __construct(string $name, Main $plugin)
    {
        $this->plugin = $plugin;
        parent::__construct($name);
        $this->setDescription('Main SkyBlock command');
        $this->setUsage('Usage: /skyblock');
        $this->setAliases(['sb', 'is']);
    }

    public function sendMessage($sender, $message)
    {
        $sender->sendMessage("§f[§aSKYBLOCK§f] §f» §b".$message);
    }

    public function execute(CommandSender $sender, $commandLabel, array $args)
    {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                switch ($args[0]) {
                    case "score":
                        $name = strtolower($sender->getName());
                        if(isset($this->plugin->disablePlayer[$name])){
                            unset($this->plugin->disablePlayer[$name]);
                            $sender->sendMessage("§aSuccessfully enabled ScoreHud.");
                        }else{
                            $this->plugin->disablePlayer[$name] = 1;
                            $this->plugin->getScore()->unBuild($sender);
                            $sender->sendMessage("§cSuccessfully disabled ScoreHud.");
                        }
                        break;
                    case "setting":
                        if($sender->hasPermission("sbpe.edit")) {
                            (new PublicMenu())->settingForm($sender);
                        }else{
                            $this->sendMessage($sender, "You cannot use this command");
                        }
                        break;
                    case "shop":
                        (new ShopLogic())->onOpen($sender);
                        break;
                    case "home":
                        if ($sender->hasPermission('sbpe.cmd.home')) {
                            $config = $this->plugin->getSkyBlockManager()->getPlayerConfig($sender);
                            if (empty($config["island"])) {
                                $this->sendMessage($sender, "You haven't a island!");
                            } else {
                                $island = $this->plugin->getIslandManager()->getOnlineIsland($config["island"]);
                                if ($island instanceof Island) {
                                    $home = $island->getHomePosition();
                                    if ($home instanceof Position) {
                                        $sender->teleport($home);
                                        $this->sendMessage($sender, "You have been teleported to your island home");
                                    } else {
                                        $this->sendMessage($sender, "Your island haven't a home position set!");
                                    }
                                } else {
                                    $this->sendMessage($sender, "You haven't a island!!");
                                }
                            }
                        }
                        break;
                    case "sethome":
                        if ($sender->hasPermission('sbpe.cmd.sethome')) {
                            $config = $this->plugin->getSkyBlockManager()->getPlayerConfig($sender);
                            if (empty($config["island"])) {
                                $this->sendMessage($sender, "You haven't a island!");
                            } else {
                                $island = $this->plugin->getIslandManager()->getOnlineIsland($config["island"]);
                                if ($island instanceof Island) {
                                    if ($island->getOwnerName() == strtolower($sender->getName())) {
                                        if ($sender->getLevel()->getName() == $config["island"]) {
                                            $island->setHomePosition($sender->getPosition());
                                            $this->sendMessage($sender, "You set your island home successfully!");
                                        } else {
                                            $this->sendMessage($sender, "You must be in your island to set home!");
                                        }
                                    } else {
                                        $this->sendMessage($sender, "You must be the island leader to do this!");
                                    }
                                } else {
                                    $this->sendMessage($sender, "You haven't a island!!");
                                }
                            }
                        }
                    case "accept":
                        if ($sender->hasPermission('sbpe.cmd.invite.accept')) {
                            if (isset($args[1])) {
                                $config = $this->plugin->getSkyBlockManager()->getPlayerConfig($sender);
                                if (empty($config["island"])) {
                                    $player = $this->plugin->getServer()->getPlayer($args[1]);
                                    if ($player instanceof Player and $player->isOnline()) {
                                        $invitation = $this->plugin->getInvitationHandler()->getInvitation($player);
                                        if ($invitation instanceof Invitation) {
                                            if ($invitation->getSender() == $player) {
                                                $invitation->accept();
                                            } else {
                                                $this->sendMessage($sender, "You haven't a invitation from {$player->getName()}!");
                                            }
                                        } else {
                                            $this->sendMessage($sender, "You haven't a invitation from {$player->getName()}");
                                        }
                                    } else {
                                        $this->sendMessage($sender, "{$args[1]} is not a valid player");
                                    }
                                } else {
                                    $this->sendMessage($sender, "You cannot be in a island if you want join another island!");
                                }
                            } else {
                                $this->sendMessage($sender, "Usage: /SkyBlockBlock accept <sender name>");
                            }
                        }
                        break;
                    case "reload":
                        $this->sendMessage($sender, "Reloading Users.yml");
                        $this->sendMessage($sender, "Reloading Islands");
                        $this->sendMessage($sender, "Reloading Config.yml");
                        $this->sendMessage($sender, "Reloading data.yml");
                        $this->plugin->scoreReload();
                        $this->plugin->getIslandManager()->updateDisableIslandServer();
                        break;
                    default:
                        $ui = new PublicMenu();
                        $ui->onMenu($sender);
                        break;
                }
            } else {
                $ui = new PublicMenu();
                $ui->onMenu($sender);
            }
        } else {
            if (isset($args[0])) {
                switch ($args[0]) {
                    case "reload":
                        $this->sendMessage($sender, "Reloading Users.yml");
                        $this->sendMessage($sender, "Reloading Config.yml");
                        $this->sendMessage($sender, "Reloading Islands");
                        $this->sendMessage($sender, "Reloading data.yml");
                        $this->plugin->scoreReload();
                        $this->plugin->getIslandManager()->updateDisableIslandServer();
                        break;
                    default:
                        $this->sendMessage($sender, "Use /sb reload to reloading data!");
                        break;
                }
            } else {
                $this->sendMessage($sender, "Use /sb reload to reloading data!");
            }
        }
    }
}
