<?php

declare(strict_types=1);

namespace SurvivalKits\Manager;

use SurvivalKits\Main;
use SurvivalKits\Utils\TimeUtils;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\item\StringToItemParser;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\item\Item;

// --- ALTERADO: Agora aponta para a pasta interna do SurvivalKits ---
use SurvivalKits\Forms\SimpleForm; 
// ------------------------------------------------------------------

class KitManager {
    // ... resto do código igual ...
    private Main $plugin;
    private Config $data;
    private array $cooldowns = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->data = new Config($plugin->getDataFolder() . "cooldowns.json", Config::JSON);
        $this->cooldowns = $this->data->getAll();
    }

    public function saveData(): void {
        $this->data->setAll($this->cooldowns);
        $this->data->save();
    }

    public function getCooldownLeft(Player $player, string $kitName, int $cooldownSeconds): int {
        $name = strtolower($player->getName());
        if (!isset($this->cooldowns[$name][$kitName])) return 0;

        $lastUsed = $this->cooldowns[$name][$kitName];
        $timePassed = time() - $lastUsed;

        if ($timePassed >= $cooldownSeconds) {
            unset($this->cooldowns[$name][$kitName]);
            $this->saveData();
            return 0;
        }
        return $cooldownSeconds - $timePassed;
    }

    private function setCooldown(Player $player, string $kitName): void {
        $this->cooldowns[strtolower($player->getName())][$kitName] = time();
        $this->saveData();
    }

    public function attemptClaim(Player $player, string $kitKey): void {
        $config = $this->plugin->getConfig();
        $kit = $config->getNested("kits.$kitKey");

        if ($kit === null) return;

        // 1. Permissão
        if (isset($kit['permission']) && !$player->hasPermission($kit['permission'])) {
            $player->sendMessage($config->getNested("settings.prefix") . $config->getNested("messages.no-permission"));
            return;
        }

        // 2. Nível XP
        if (isset($kit['unlock-level']) && $player->getXpManager()->getXpLevel() < $kit['unlock-level']) {
            $msg = str_replace("{LEVEL}", (string)$kit['unlock-level'], $config->getNested("messages.locked-level"));
            $player->sendMessage($config->getNested("settings.prefix") . $msg);
            return;
        }

        // 3. Cooldown
        $timeLeft = $this->getCooldownLeft($player, $kitKey, (int)$kit['cooldown']);
        if ($timeLeft > 0) {
            $timeStr = TimeUtils::formatTime($timeLeft);
            $msg = str_replace("{TIME}", $timeStr, $config->getNested("messages.cooldown"));
            $player->sendMessage($config->getNested("settings.prefix") . $msg);
            return;
        }

                // 4. Inventário e Itens
        $inv = $player->getInventory();
        $itemsToAdd = [];
        
        foreach ($kit['items'] as $itemStr) {
            $item = StringToItemParser::getInstance()->parse($itemStr);
            if ($item instanceof Item) {
                $itemsToAdd[] = $item;
            }
        }

        // CORREÇÃO: Verifica se a lista não está vazia antes de chamar canAddItem
        if (empty($itemsToAdd)) {
            $this->plugin->getLogger()->warning("O kit " . $kitKey . " foi configurado sem itens válidos!");
            $player->sendMessage("§cEste kit está vazio ou configurado incorretamente.");
            return;
        }

        // O uso de ...$itemsToAdd exige que o array tenha ao menos 1 item
        if (!$inv->canAddItem(...$itemsToAdd)) {
            $player->sendMessage($config->getNested("settings.prefix") . $config->getNested("messages.inventory-full"));
            return;
        }

        $inv->addItem(...$itemsToAdd);

        // 5. Buffs
        if (isset($kit['buffs'])) {
            $duration = (int)($kit['buffs']['duration'] ?? 60) * 20;
            foreach ($kit['buffs'] as $effectName => $level) {
                if ($effectName === "duration") continue;
                $effect = StringToEffectParser::getInstance()->parse((string)$effectName);
                if ($effect !== null) {
                    $player->getEffects()->add(new EffectInstance($effect, $duration, (int)$level - 1));
                }
            }
        }

        $this->setCooldown($player, $kitKey);
        $msg = str_replace("{KIT}", $kit['name'], $config->getNested("messages.received"));
        $player->sendMessage($config->getNested("settings.prefix") . $msg);
    }

    public function openKitForm(Player $player): void {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) return;
            $this->attemptClaim($player, (string)$data);
        });

        $config = $this->plugin->getConfig();
        $form->setTitle($config->getNested("gui.title", "§l§6Kits"));
        $form->setContent($config->getNested("gui.content", "Escolha seu kit:"));

        $kits = $config->get("kits", []);
        if (empty($kits)) {
            $player->sendMessage("§cNão há kits configurados.");
            return;
        }

        foreach ($kits as $key => $kit) {
            $timeLeft = $this->getCooldownLeft($player, (string)$key, (int)$kit['cooldown']);
            $status = ($timeLeft > 0) ? "§c" . TimeUtils::formatTime($timeLeft) : "§aDisponível";
            
            $iconPath = $kit['icon'] ?? "";
            $iconType = str_contains($iconPath, "http") ? 1 : 0;
            if(empty($iconPath)) $iconType = -1;

            $form->addButton($kit['name'] . "\n" . $status, $iconType, $iconPath, (string)$key);
        }

        $player->sendForm($form);
    }

    public function getCooldownString(Player $player, string $kitName): string {
        $kits = $this->plugin->getConfig()->get("kits", []);
        if (!isset($kits[$kitName])) return "N/A";
        $left = $this->getCooldownLeft($player, $kitName, (int)$kits[$kitName]['cooldown']);
        return ($left <= 0) ? "§aPronto" : TimeUtils::formatTime($left);
    }
}


