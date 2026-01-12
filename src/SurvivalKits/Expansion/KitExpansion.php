<?php

namespace SurvivalKits\Expansion;

use MohamadRZ4\Placeholder\expansion\PlaceholderExpansion;
use pocketmine\player\Player;
use SurvivalKits\Main;

class KitExpansion extends PlaceholderExpansion {

    /**
     * IMPORTANTE: Não declaramos "protected $plugin" aqui, 
     * pois a classe PlaceholderExpansion já o faz.
     */

    public function __construct(Main $plugin) {
        // Passamos o plugin para o construtor da classe pai
        parent::__construct($plugin);
    }

    public function getIdentifier(): string {
        return "kit";
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

        /** @var Main $plugin */
        $plugin = $this->plugin;

        // Acessamos o KitManager através do plugin herdado
        return $plugin->getKitManager()->getCooldownString($player, $placeholder);
    }
}
    }
}
