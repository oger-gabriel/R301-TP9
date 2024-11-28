<?php

/*
 * This file is part of the OpenClassRoom PHP Object Course.
 *
 * (c) Grégoire Hébert <contact@gheb.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

abstract class AbstractPlayer
{
    protected string $name;
    protected float $ratio;

    public function __construct(string $name, float $ratio = 400.0)
    {
        $this->name = $name;
        $this->ratio = max(0, $ratio); // Assurer que le ratio est positif.
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRatio(): float
    {
        return $this->ratio;
    }

    abstract public function updateRatioAgainst(AbstractPlayer $player, int $result): void;
}

final class Player extends AbstractPlayer
{
    private function probabilityAgainst(self $player): float
    {
        return 1 / (1 + 10 ** (($player->getRatio() - $this->getRatio()) / 400));
    }

    public function updateRatioAgainst(AbstractPlayer $player, int $result): void
    {
        $this->ratio += 32 * ($result - $this->probabilityAgainst($player));
    }
}

final class QueuingPlayer extends AbstractPlayer
{
    private int $range;

    public function __construct(AbstractPlayer $player, int $range = 100)
    {
        parent::__construct($player->getName(), $player->getRatio());
        $this->setRange($range);
    }

    public function getRange(): int
    {
        return $this->range;
    }

    public function setRange(int $range): void
    {
        if ($range < 0) {
            throw new InvalidArgumentException("Range must be non-negative.");
        }
        $this->range = $range;
    }

    public function updateRatioAgainst(AbstractPlayer $player, int $result): void
    {
        throw new LogicException("QueuingPlayer does not implement `updateRatioAgainst`.");
    }
}

final class BlitzPlayer extends AbstractPlayer
{
    public function __construct(string $name)
    {
        parent::__construct($name, 1200); // Début à 1200
    }

    private function probabilityAgainst(self $player): float
    {
        return 1 / (1 + 10 ** (($player->getRatio() - $this->getRatio()) / 400));
    }

    public function updateRatioAgainst(AbstractPlayer $player, int $result): void
    {
        $this->ratio += 4 * 32 * ($result - $this->probabilityAgainst($player)); // Évolution rapide
    }
}

final class Lobby
{
    /** @var array<QueuingPlayer> */
    public array $queuingPlayers = [];

    public function addPlayer(AbstractPlayer $player, int $range = 100): void
    {
        $this->queuingPlayers[] = new QueuingPlayer($player, $range);
    }

    public function addPlayers(AbstractPlayer ...$players): void
    {
        foreach ($players as $player) {
            $this->addPlayer($player);
        }
    }

    public function findOponents(QueuingPlayer $player): array
    {
        $minLevel = round($player->getRatio() / 100);
        $maxLevel = $minLevel + $player->getRange();

        return array_filter($this->queuingPlayers, static function (QueuingPlayer $potentialOponent) use (
            $minLevel,
            $maxLevel,
            $player
        ) {
            $playerLevel = round($potentialOponent->getRatio() / 100);

            return $player !== $potentialOponent && $minLevel <= $playerLevel && $playerLevel <= $maxLevel;
        });
    }
}

// Example usage
$greg = new Player("Greg", 400);
$jade = new BlitzPlayer("Jade");

$lobby = new Lobby();
$lobby->addPlayers($greg, $jade);

var_dump($lobby->findOponents($lobby->queuingPlayers[0]));

exit(0);
