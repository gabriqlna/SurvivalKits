<?php

declare(strict_types=1);

namespace SurvivalKits;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use SurvivalKits\Manager\KitManager;
use SurvivalKits\Expansion\KitExpansion; // Importe a classe nova
use MohamadRZ4\Placeholder\PlaceholderAPI; // Importe a API

class Main extends PluginBase {

    private static self $instance;
    private KitManager $kitManager;

    protected function onEnable(): void {
        self::$instance = $this;
        $this->saveDefaultConfig();

        $this->kitManager = new KitManager($this);

        // Registro da Expansão na PlaceholderAPI do MohamadRZ4
        $papi = $this->getServer()->getPluginManager()->getPlugin("PlaceholderAPI");
        if($papi !== null){
            $api = PlaceholderAPI::getAPI();
            if($api !== null){
                $api->registerExpansion(new KitExpansion($this));
                $this->getLogger()->info("§aExpansão de Kits registrada na PlaceholderAPI!");
            }
        }

        $this->getLogger()->info("SurvivalKits+ ativado!");
    }

    public static function getInstance(): self { return self::$instance; }
    public function getKitManager(): KitManager { return $this->kitManager; }

        public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "kit") {
            if (isset($args[0]) && $args[0] === "reload") {
                if (!$sender->hasPermission("kit.admin")) {
                    $sender->sendMessage("§cSem permissão.");
                    return true;
                }
                $this->reloadConfig();
                $this->kitManager = new KitManager($this); // Recarrega os kits na memória
                $sender->sendMessage("§aConfiguração de Kits recarregada!");
                return true;
            }

            if (!$sender instanceof Player) {
                $sender->sendMessage("Use in-game.");
                return true;
            }

            if (isset($args[0])) {
                $this->kitManager->attemptClaim($sender, $args[0]);
            } else {
                $this->kitManager->openKitForm($sender);
            }
            return true;
        }
        return false;
    }
}

