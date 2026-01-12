<?php

namespace SurvivalKits\Expansion;

use MohamadRZ4\Placeholder\expansion\PlaceholderExpansion;
use pocketmine\player\Player;
use SurvivalKits\Main;

class KitExpansion extends PlaceholderExpansion {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function getIdentifier(): string {
        return "kit"; // O que vem antes do underline: %kit_...%
    }

    public function getVersion(): string {
        return "1.0.0";
    }

    public function getAuthor(): string {
        return "gabriqlna";
    }

    public function onPlaceholderRequest(?Player $player, string $placeholder): ?string {
        if ($player === null) {
            return null;
        }

        // O $placeholder aqui é o que vem depois de %kit_
        // Exemplo: se usar %kit_membro%, $placeholder será "membro"
        return $this->plugin->getKitManager()->getCooldownString($player, $placeholder);
    }
}
