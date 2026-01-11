<?php

declare(strict_types=1);

namespace SurvivalKits;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SurvivalKits\Manager\KitManager;

class Main extends PluginBase {

    private static self $instance;
    private KitManager $kitManager;

    protected function onEnable(): void {
        self::$instance = $this;
        $this->saveDefaultConfig();

        $this->kitManager = new KitManager($this);

        // Registro de Placeholders (Integração ScoreHud/PAPI)
        if($this->getServer()->getPluginManager()->getPlugin("PlaceholderAPI") !== null){
            // Exemplo: %survivalkits_cooldown_vip%
            \poggit\libasynql\libasynql::register($this); // Apenas se usar SQL, mas mantemos simples
            // A lógica real de placeholders seria registrada aqui via hook
        }

        $this->getLogger()->info("SurvivalKits+ ativado com sucesso!");
    }

    public static function getInstance(): self {
        return self::$instance;
    }

    public function getKitManager(): KitManager {
        return $this->kitManager;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("Use in-game.");
            return true;
        }

        if ($command->getName() === "kit") {
            if (isset($args[0])) {
                // Tentativa direta: /kit vip
                $this->kitManager->attemptClaim($sender, $args[0]);
            } else {
                // Abrir GUI
                $this->kitManager->openKitForm($sender);
            }
            return true;
        }
        return false;
    }
}
