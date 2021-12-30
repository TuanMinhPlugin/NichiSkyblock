<?php

#               Copyright (C) 2016 MadeAja/BaliGamerz
##    ███╗░░░███╗░█████╗░██████╗░███████╗░█████╗░░░░░░██╗░█████╗░
##    ████╗░████║██╔══██╗██╔══██╗██╔════╝██╔══██╗░░░░░██║██╔══██╗
##    ██╔████╔██║███████║██║░░██║█████╗░░███████║░░░░░██║███████║
##    ██║╚██╔╝██║██╔══██║██║░░██║██╔══╝░░██╔══██║██╗░░██║██╔══██║
##    ██║░╚═╝░██║██║░░██║██████╔╝███████╗██║░░██║╚█████╔╝██║░░██║
##    ╚═╝░░░░░╚═╝╚═╝░░╚═╝╚═════╝░╚══════╝╚═╝░░╚═╝░╚════╝░╚═╝░░╚═╝


namespace BaliGamerz\SkyBlock\FunctionLogic;


use BaliGamerz\SkyBlock\events\LeaderboardNpcCreateEvent;
use BaliGamerz\SkyBlock\Utils;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use BaliGamerz\SkyBlock\invitation\Invitation;
use BaliGamerz\SkyBlock\island\Island;
use BaliGamerz\SkyBlock\Main;

class ListFunction
{

    #Plugin Var
    public function getPlugin(): ?Main
    {
        return Main::getInstance();
    }

    #Message
    public function sendMessage(Player $sender, $message)
    {
        $sender->sendMessage("§f[§aSKYBLOCK§f] §f» §b" . $message);
    }


    #Function Join
    public function joinFunction($sender)
    {
        if ($sender->hasPermission('sbpe.cmd.join')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You haven't a island!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                if ($island instanceof Island) {
                    $server = $this->getPlugin()->getServer();
                    if (!$server->isLevelLoaded($island->getIdentifier())) {
                        $server->loadLevel($island->getIdentifier());
                    }
                    $sender->teleport(new Position($island->getPosition()->x, $island->getPosition()->y + 2, $island->getPosition()->z, $this->getPlugin()->getServer()->getLevelByName($island->getIdentifier())));
                    $this->sendMessage($sender, "You were teleported to your island home");
                } else {
                    $this->sendMessage($sender, "You haven't a island!!");
                }
            }
        }
    }

    #Function Create
    public function createFunction(Player $sender, $name, $array): bool
    {
        if ($sender->hasPermission('sbpe.cmd.create')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $reset = $config['last-creation'];
                    if (($reset - time()) > 1) {
                        $minutes = Utils::printSeconds($reset - time());
                        if(!$sender->hasPermission("sbpe.countdown.bypass")) {
                            $this->sendMessage($sender, "You'll be able to create a new island in {$minutes} minutes");
                            return false;
                        }
                    }
                    $this->getPlugin()->getSkyBlockManager()->generateIsland($sender, $name, $array);
                    return true;
            } else {
                $this->sendMessage($sender, "You already got a sb island!");
                return false;
            }
        }
        return false;
    }

    #Function Home
    public function homeFunction($sender)
    {
        if ($sender->hasPermission('sbpe.cmd.home')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You haven't a island!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                if ($island instanceof Island) {
                    $home = $island->getHomePosition();
                    if ($home !== null) {
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
    }

    #Function setHome
    public function setHomeFunction($sender)
    {
        if ($sender->hasPermission('sbpe.cmd.sethome')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You haven't a island!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
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
    }

    #Function Kick
    public function kickFunction($sender, $target)
    {
        if ($sender->hasPermission('sbpe.cmd.kick')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You haven't a island!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                if ($island instanceof Island) {
                    if ($island->getOwnerName() == strtolower($sender->getName())) {
                        $player = $this->getPlugin()->getServer()->getPlayer($target);
                        if ($player instanceof Player and $player->isOnline()) {
                            if ($player->getLevel()->getName() == $island->getIdentifier()) {
                                $player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSafeSpawn());
                                $this->sendMessage($sender, "{$player->getName()} has been kicked from your island!");
                            } else {
                                $this->sendMessage($sender, "The player isn't in your island!");
                            }
                        } else {
                            $this->sendMessage($sender, "That isn't a valid player");
                        }
                    } else {
                        $this->sendMessage($sender, "You must the island owner to expel anySkyBlock");
                    }
                } else {
                    $this->sendMessage($sender, "You haven't a island!");
                }
            }
        }
    }

    #Function Lock
    public function lockFunction($sender)
    {
        if ($sender->hasPermission('sbpe.cmd.lock')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You haven't a island!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                if ($island instanceof Island) {
                    if ($island->getOwnerName() == strtolower($sender->getName())) {
                        $island->setLocked(!$island->isLocked());
                        $locked = ($island->isLocked()) ? "locked" : "unlocked";
                        $this->sendMessage($sender, "Your island has been {$locked}!");
                    } else {
                        $this->sendMessage($sender, "You must be the island owner to do this!");
                    }
                } else {
                    $this->sendMessage($sender, "You haven't a island!");
                }
            }
        }
    }

    #Function Invite
    public function inviteFunction($sender, $target)
    {
        if ($sender->hasPermission('sbpe.cmd.invite')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You haven't a island!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                if ($island instanceof Island) {
                    if ($island->getOwnerName() == strtolower($sender->getName())) {
                        if (count($island->getMembers()) < (int)$this->getPlugin()->config['island']['max_members']) {
                            $player = $this->getPlugin()->getServer()->getPlayer($target);
                            if ($player instanceof Player and $player->isOnline()) {
                                $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($player);
                                if (empty($config["island"])) {
                                    $this->getPlugin()->getInvitationHandler()->addInvitation($sender, $player, $island);
                                    $this->sendMessage($sender, "You sent a invitation to {$player->getName()}!");
                                    $this->sendMessage($player, "{$sender->getName()} invited you to his island! Do /sb <accept/reject> {$sender->getName()}");
                                } else {
                                    $this->sendMessage($sender, "This player is already in a island!");
                                }
                            } else {
                                $this->sendMessage($sender, "{$target} isn't a valid player!");
                            }
                        } else {
                            $this->sendMessage($sender, "Your members full, Max member: " . $this->getPlugin()->config['island']['max_members']);
                        }
                    } else {
                        $this->sendMessage($sender, "You must be the island owner to do this!");
                    }
                } else {
                    $this->sendMessage($sender, "You haven't a island!!");
                }
            }
        }
    }

    #Accept Function
    public function acceptFunction($sender, $target)
    {
        if ($sender->hasPermission('sbpe.cmd.invite.accept')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $player = $this->getPlugin()->getServer()->getPlayer($target);
                if ($player instanceof Player and $player->isOnline()) {
                    $invitation = $this->getPlugin()->getInvitationHandler()->getInvitation($player);
                    if ($invitation instanceof Invitation) {
                        if ($invitation->getSender()->getName() == $player->getName()) {
                            if ($invitation->getTime() > 0) {
                                $invitation->accept();
                            } else {
                                $invitation->expire();
                                $this->sendMessage($sender, "This invitation has been expired!, please contact owner to invite");
                            }
                        } else {
                            $this->sendMessage($sender, "You haven't a invitation from {$player->getName()}!");
                        }
                    } else {
                        $this->sendMessage($sender, "You haven't a invitation from {$player->getName()}");
                    }
                } else {
                    $this->sendMessage($sender, "{$target} is not a valid player");
                }
            } else {
                $this->sendMessage($sender, "You cannot be in a island if you want join another island!");
            }
        }
    }

    #Reject Function
    public function rejectFunction($sender, $target)
    {
        if ($sender->hasPermission('sbpe.cmd.invite.deny')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $player = $this->getPlugin()->getServer()->getPlayer($target);
                if ($player instanceof Player and $player->isOnline()) {
                    $invitation = $this->getPlugin()->getInvitationHandler()->getInvitation($player);
                    if ($invitation instanceof Invitation) {
                        if ($invitation->getSender()->getName() == $player->getName()) {
                            $invitation->deny();
                        } else {
                            $this->sendMessage($sender, "You haven't a invitation from {$player->getName()}!");
                        }
                    } else {
                        $this->sendMessage($sender, "You haven't a invitation from {$player->getName()}");
                    }
                } else {
                    $this->sendMessage($sender, "{$target} is not a valid player");
                }
            } else {
                $this->sendMessage($sender, "You cannot be in a island if you want reject another island!");
            }
        }
    }

    #Disband Function
    public function disbandFunction($sender)
    {
        if ($sender->hasPermission('sbpe.cmd.remove')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You must be in a island to disband it!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                if ($island instanceof Island) {
                    if ($island->getOwnerName() == strtolower($sender->getName())) {
                        foreach ($island->getAllMembers() as $member) {
                            $this->getPlugin()->playerDataPath[strtolower($member)] = Utils::getFormatPlayerDataPath();
                            $memberObject = $this->getPlugin()->getServer()->getPlayer($member);
                            $this->getPlugin()->economy->addMoney($memberObject, $island->getShareMoney());
                        }
                        $this->getPlugin()->getIslandManager()->removeIsland($island);
                        $this->getPlugin()->playerDataPath[strtolower($sender->getName())]['last-creation'] = time() + $this->getPlugin()->config['island']['creation-time'];
                        $this->sendMessage($sender, "You successfully deleted the island!");
                    } else {
                        $this->sendMessage($sender, "You must be the owner to disband the island!");
                    }
                } else {
                    $this->sendMessage($sender, "You must be in a island to disband it!!");
                }
            }
        }
    }


    /**
     * @param $sender
     * @return CompoundTag
     */
    public function getSkinTag($sender): CompoundTag
    {
        $path = $this->getPlugin()->getDataFolder() . "Skin/leaderboard.png";
        $img = @imagecreatefrompng($path);
        $skinbytes = "";
        $s = @getimagesize($path);
        for ($y = 0; $y < $s[1]; $y++) {
            for ($x = 0; $x < $s[0]; $x++) {
                $colorat = @imagecolorat($img, $x, $y);
                $a = ((~((int)($colorat >> 24))) << 1) & 0xff;
                $r = ($colorat >> 16) & 0xff;
                $g = ($colorat >> 8) & 0xff;
                $b = $colorat & 0xff;
                $skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        $skinTag = new CompoundTag("Skin", [
            "Name" => new StringTag("Name", $sender->getSkin()->getSkinId()),
            "Data" => new ByteArrayTag("Data", $skinbytes),
            "GeometryName" => new StringTag("GeometryName", "geometry.leaderboard"),
            "GeometryData" => new ByteArrayTag("GeometryData", file_get_contents($this->getPlugin()->getDataFolder() . "Skin/leaderboard.json"))
        ]);
        return $skinTag;
    }

    public function createLeaderBoard(Player $player, $name, $leaderboard = true, $boundingBox = 2.5)
    {

        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        $nbt->setShort("Health", 1);
        $nbt->setString("MenuName", "");
        $nbt->setString("CustomName", $name);
        $nbt->setFloat("height", $boundingBox);
        if ($leaderboard) {
            $nbt->setFloat("leaderboard", 1.0);
            $nbt->setTag($this->getSkinTag($player));
        } else {
            $nbt->setFloat("npcStats", 1.0);
            $nbt->setTag(clone $player->namedtag->getCompoundTag('Skin'));
        }
        $player->saveNBT();
        $human = Entity::createEntity("NpcClass", $player->level, $nbt);
        if ($leaderboard) {
            (new LeaderboardNpcCreateEvent($human))->call();
        }
        $human->spawnToAll();
    }

    #Makeleader Function
    public function makeleaderFunction($sender, $target)
    {
        if ($sender->hasPermission('sbpe.cmd.makeleader')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You must be in a island to set a new leader!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                if ($island instanceof Island) {
                    if ($island->getOwnerName() == strtolower($sender->getName())) {
                        $player = $this->getPlugin()->getServer()->getPlayer($target);
                        if ($player instanceof Player and $player->isOnline()) {
                            $playerConfig = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($player);
                            $playerIsland = $this->getPlugin()->getIslandManager()->getOnlineIsland($playerConfig["island"]);
                            if ($island->getIdentifier() == $playerIsland->getIdentifier()) {
                                $island->setOwnerName($player);
                                $this->sendMessage($sender, "You sent the ownership to {$player->getName()}");
                                $this->sendMessage($player, "You get your island ownership by {$sender->getName()}");
                            } else {
                                $this->sendMessage($sender, "The player should be on your island!");
                            }
                        }
                    } else {
                        $this->sendMessage($sender, "You must be the island leader to do this!");
                    }
                } else {
                    $this->sendMessage($sender, "You must be in a island to set a new leader!!");
                }
            }
        }
    }

    #Leave Function
    public function leaveFunction($sender)
    {
        if ($sender->hasPermission('sbpe.cmd.leave')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You must be in a island to leave it!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                if ($island instanceof Island) {
                    if ($island->getOwnerName() == strtolower($sender->getName())) {
                        $this->sendMessage($sender, "You cannot leave a island if your the owner! Maybe you can try use /sb disband");
                    } else {
                        $this->getPlugin()->playerDataPath[strtolower($sender->getName())] = Utils::getFormatPlayerDataPath();
                        $island->removeMember(strtolower($sender->getName()));
                        $this->sendMessage($sender, "You leave the island!!");
                    }
                } else {
                    $this->sendMessage($sender, "You must be in a island to leave it!!");
                }
            }
        }
    }

    #Remove Function
    public function removeFunction($sender, $target)
    {
        if ($sender->hasPermission('sbpe.cmd.remove')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You must be in a island to leave it!");
            } else {
                $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                if ($island instanceof Island) {
                    if ($island->getOwnerName() == strtolower($sender->getName())) {
                        if (in_array(strtolower($target), $island->getMembers())) {
                            $island->removeMember(strtolower($target));
                            $this->getPlugin()->economy->addMoney($target, $island->getShareMoney());
                            $this->getPlugin()->playerDataPath[strtolower($target)] = Utils::getFormatPlayerDataPath();
                            $this->sendMessage($sender, "{$target} was removed from your team!");
                        } else {
                            $this->sendMessage($sender, "{$target} isn't a player of your island!");
                        }
                    } else {
                        $this->sendMessage($sender, "You must be the island owner to do this!");
                    }
                } else {
                    $this->sendMessage($sender, "You must be in a island to leave it!!");
                }
            }
        }
    }

    #Tp Function
    public function tpFunction(Player $sender, Player $target)
    {
        if ($sender->hasPermission('sbpe.cmd.tp')) {
            $island = $this->getPlugin()->getIslandManager()->getIslandByPlayer($target);
            if ($island instanceof Island) {
                if ($island->isLocked()) {
                    $this->sendMessage($sender, "This island is locked, you cannot join it!");
                } else {
                    $sender->teleport(new Position($island->getPosition()->x, $island->getPosition()->y + 2, $island->getPosition()->z, $this->getPlugin()->getServer()->getLevelByName($island->getIdentifier())));
                    $this->sendMessage($sender, "You joined the island successfully");
                }
            } else {
                $this->sendMessage($sender, "At least SkyBlock island member must be active if you want see the island!");
            }
        }
    }

    #Reset Function
    public function resetFunction($sender)
    {
        if ($sender->hasPermission('sbpe.cmd.reset')) {
            $config = $this->getPlugin()->getSkyBlockManager()->getPlayerConfig($sender);
            if (empty($config["island"])) {
                $this->sendMessage($sender, "You must be in a island to reset it!");
            } else {
                $reset = $config['last-creation'];
                if (($reset - time()) > 1) {
                    $minutes = Utils::printSeconds($reset - time());
                    $this->sendMessage($sender, "You'll be able to reset a new in {$minutes} minutes");
                } else {
                    $island = $this->getPlugin()->getIslandManager()->getOnlineIsland($config["island"]);
                    if ($island instanceof Island) {
                        if ($island->getOwnerName() == strtolower($sender->getName())) {
                            foreach ($island->getAllMembers() as $member) {
                                $this->getPlugin()->playerDataPath[strtolower($member)] = Utils::getFormatPlayerDataPath();
                                $memberObject = $this->getPlugin()->getServer()->getPlayer($member);
                                $this->getPlugin()->economy->addMoney($memberObject, $island->getShareMoney());
                            }
                            $name = $island->getIdentifier();
                            $generator = $island->getGenerator();
                            $this->getPlugin()->getIslandManager()->removeIsland($island);
                            $this->getPlugin()->getSkyBlockManager()->generateIsland($sender, $name, $this->getPlugin()->dataThemas['list'][$generator]);
                            $this->getPlugin()->playerDataPath[strtolower($sender->getName())]['last-creation'] = time() + $this->getPlugin()->config['island']['creation-time'];
                            $this->sendMessage($sender, "You successfully reset the island!");
                        } else {
                            $this->sendMessage($sender, "You must be the owner to reset the island!");
                        }
                    } else {
                        $this->sendMessage($sender, "You must be in a island to reset it!!");
                    }
                }
            }
        }
    }
}