<?php

#               Copyright (C) 2016 MadeAja/BaliGamerz
##    ███╗░░░███╗░█████╗░██████╗░███████╗░█████╗░░░░░░██╗░█████╗░
##    ████╗░████║██╔══██╗██╔══██╗██╔════╝██╔══██╗░░░░░██║██╔══██╗
##    ██╔████╔██║███████║██║░░██║█████╗░░███████║░░░░░██║███████║
##    ██║╚██╔╝██║██╔══██║██║░░██║██╔══╝░░██╔══██║██╗░░██║██╔══██║
##    ██║░╚═╝░██║██║░░██║██████╔╝███████╗██║░░██║╚█████╔╝██║░░██║
##    ╚═╝░░░░░╚═╝╚═╝░░╚═╝╚═════╝░╚══════╝╚═╝░░╚═╝░╚════╝░╚═╝░░╚═╝


namespace BaliGamerz\SkyBlock\invitation;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use BaliGamerz\SkyBlock\island\Island;

class Invitation {

    /** @var InvitationHandler */
    private $handler;

    /** @var Player */
    private $sender;

    /** @var Player */
    private $receiver;

    /** @var Island */
    private $island;

    /** @var int */
    private $time = 100;

    /**
     * Invitation constructor.
     *
     * @param InvitationHandler $handler
     * @param Player $sender
     * @param Player $receiver
     * @param Island $island
     */
    public function __construct(InvitationHandler $handler, Player $sender, Player $receiver, Island $island) {
        $this->handler = $handler;
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->island = $island;
    }

    /**
     * Return invitation sender
     *
     * @return Player
     */
    public function getSender(): Player
    {
        return $this->sender;
    }

    /**
     * Return invitation receiver
     *
     * @return Player
     */
    public function getReceiver(): Player
    {
        return $this->receiver;
    }

    public function accept() {
        $config = $this->handler->getPlugin()->getSkyBlockManager()->getPlayerConfig($this->receiver);
        if(empty($config["island"])) {
            $this->handler->getPlugin()->playerDataPath[strtolower($this->receiver->getName())]["island"] = $this->island->getIdentifier();
            $this->island->addMember($this->receiver);
            $this->sender->sendMessage(TextFormat::RED . "* " . TextFormat::YELLOW . "{$this->receiver->getName()} accepted your invitation!");
            $this->receiver->sendMessage(TextFormat::RED . "* " . TextFormat::YELLOW . "You joined {$this->sender->getName()} island!");
        }
        else {
            $this->sender->sendMessage(TextFormat::RED . "* " . TextFormat::YELLOW . "{$this->receiver->getName()} is already in island!");
        }
        $this->handler->removeInvitation($this);
    }

    public function deny() {
        $this->sender->sendMessage(TextFormat::RED . "* " . TextFormat::YELLOW . "{$this->receiver->getName()} denied your invitation!");
        $this->receiver->sendMessage(TextFormat::RED . "* " . TextFormat::YELLOW . "You denied {$this->sender->getName()}'s invitation!");
        $this->handler->removeInvitation($this);
    }

    public function expire() {
        $this->sender->sendMessage(TextFormat::RED . "* " . TextFormat::YELLOW . "The invitation to {$this->receiver->getName()} expired!");
        $this->handler->removeInvitation($this);
    }

    public function getTime(): int
    {
        return $this->time - time();
    }

}