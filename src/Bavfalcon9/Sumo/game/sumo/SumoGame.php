<?php

namespace Bavfalcon9\Sumo\game\sumo;

use Bavfalcon9\Sumo\Main;
use Bavfalcon9\Sumo\game\BaseGame;
use Bavfalcon9\Sumo\game\match\MatchTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\level\Position;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;

class SumoGame extends BaseGame
{
    /** @var Position */
    private $pos1;
    /** @var Position */
    private $pos2;
    /** @var Position */
    private $spawn;
    /** @var string[] */
    private $contestants;
    /** @var string[] */
    private $currentMatch;
    /** @var TaskHandler|null */
    private $matchTask;

    public function __construct(Main $plugin, Position $pos1, Position $pos2, Position $spawn, int $maxPlayers = 50)
    {
        parent::__construct($plugin, $maxPlayers);
        $this->pos1 = $pos1;
        $this->pos2 = $pos2;
        $this->spawn = $spawn;
        $this->contestants = [];
        $this->currentMatch = [];
        $this->matchTask = null;
    }

    public function start(): void
    {
        if (count($this->getOnlinePlayers()))
        foreach ($this->getOnlinePlayers() as $player) {
            // Teleport the player to the spectator position
            $player->teleport($this->spawn);
        }
        $this->startMatch();
    }

    public function startMatch(): void
    {
        if ($this->matchTask !== null) {
            return;
        }
        if (count($this->contestants) < 2) {
            return; // can not start a match.
        }
        $one = array_shift($this->contestants);
        $two = array_shift($this->contestants);
        $this->currentMatch = [$one, $two];
        $this->matchTask = $this->plugin->getScheduler()->scheduleRepeatingTask(new MatchTask($this), 20);
    }

    /**
     * Players on the platform (time expired) removes them from the game.
     *
     * @param string $winner The winner of the match.
     * @return void
     */
    public function endCurrentMatch(string $winner = null): void
    {
        // Ends the match (if its taking too long)
        $one = $this->contestants[0];
        $two = $this->contestants[1];
        $msg = "Nobody won!";
        if ($winner === $one) {
            $this->contestants[] = $one;
            $msg = "$winner won the match against $two!";
        }
        if ($winner === $two) {
            $this->contestants[] = $two;
            $msg = "$winner won the match against $one!";
        }
        foreach ($this->getCurrentPlayersOnline() as $player) {
            $player->teleport($this->spawn);
        }
        foreach ($this->getOnlinePlayers() as $player) {
            $player->addTitle("§a" . $msg);
        }
        $this->currentMatch = [];
        if (count($this->contestants) === 1) {
            $this->stop();
            return;
        }
        $this->startMatch();
    }

    /**
     * Stops the game.
     * @return bool
     */
    public function stop(): bool
    {
        if (!$this->running) {
            // TODO Get spawn from config and tp all in game there.
        } else {
            // TODO Determine the winner and tp everyone to spawn
        }
    }

    /**
     * @return string[]
     */
    public function getCurrentPlayers(): array
    {
        return $this->currentMatch;
    }

    /**
     * @return Player[]
     */
    public function getCurrentPlayersOnline(): array {
        return array_filter([
            $this->plugin->getServer()->getPlayerExact($this->currentMatch[0]),
            $this->plugin->getServer()->getPlayerExact($this->currentMatch[1])
        ], function($element) {
            return $element !== null;
        });
    }
}