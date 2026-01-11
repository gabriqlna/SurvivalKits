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
use jojoe77777\FormAPI\SimpleForm;

class KitManager {

    private Main $plugin;
    private Config $data;
    private array $cooldowns = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        // Salva cooldowns em arquivo para não perder ao reiniciar
        $this->data = new Config($plugin->getDataFolder() . "cooldowns.json", Config::JSON);
        $this->cooldowns = $this->data->getAll();
    }

    public function saveData(): void {
        $this->data->setAll($this->cooldowns);
        $this->data->save();
    }
       // No KitManager.php
public function getCooldownString(Player $player, string $kitName): string {
    $kits = $this->plugin->getConfig()->get("kits");
    if(!isset($kits[$kitName])) return "N/A";
    
    $left = $this->getCooldownLeft($player, $kitName, (int)$kits[$kitName]['cooldown']);
    if($left <= 0) return "§aPronto";
    
    return gmdate("H:i:s", $left);
}


    // --- LÓGICA DE COOLDOWN ---

    public function getCooldownLeft(Player $player, string $kitName, int $cooldownSeconds): int {
        $name = strtolower($player->getName());
        if (!isset($this->cooldowns[$name][$kitName])) {
            return 0;
        }

        $lastUsed = $this->cooldowns[$name][$kitName];
        $timePassed = time() - $lastUsed;

        if ($timePassed >= $cooldownSeconds) {
            unset($this->cooldowns[$name][$kitName]);
            $this->saveData(); // Limpa lixo do JSON
            return 0;
        }

        return $cooldownSeconds - $timePassed;
    }

    private function setCooldown(Player $player, string $kitName): void {
        $this->cooldowns[strtolower($player->getName())][$kitName] = time();
        $this->saveData();
    }
        

    // --- LÓGICA DE ENTREGA ---

    public function attemptClaim(Player $player, string $kitKey): void {
        $config = $this->plugin->getConfig();
        $kit = $config->getNested("kits.$kitKey");

        if ($kit === null) {
            $player->sendMessage($config->getNested("settings.prefix") . "§cKit não encontrado.");
            return;
        }

        // 1. Verifica Permissão (PurePerms/Nativo)
        if (isset($kit['permission']) && !$player->hasPermission($kit['permission'])) {
            $player->sendMessage($config->getNested("settings.prefix") . $config->getNested("messages.no-permission"));
            return;
        }

        // 2. Verifica Nível (XP)
        if (isset($kit['unlock-level']) && $player->getXpManager()->getXpLevel() < $kit['unlock-level']) {
            $msg = str_replace("{LEVEL}", (string)$kit['unlock-level'], $config->getNested("messages.locked-level"));
            $player->sendMessage($config->getNested("settings.prefix") . $msg);
            return;
        }

        // No KitManager.php, dentro do attemptClaim ou openKitForm:

$timeLeft = $this->getCooldownLeft($player, $kitKey, (int)$kit['cooldown']);

if ($timeLeft > 0) {
    // Em vez de gmdate, usamos nossa Utils
    $timeStr = TimeUtils::formatTime($timeLeft); 
    
    $msg = str_replace("{TIME}", $timeStr, $config->getNested("messages.cooldown"));
    $player->sendMessage($config->getNested("settings.prefix") . $msg);
    return;
}


        // 4. Entrega Itens
        $inv = $player->getInventory();
        $itemsToAdd = [];
        
        foreach ($kit['items'] as $itemStr) {
            // Formato: "minecraft:stone_sword:1" ou "stone_sword:1"
            $parts = explode(":", $itemStr);
            $name = $parts[0] . ":" . ($parts[1] ?? "0"); // Resolve namespace se houver
            if(count($parts) === 3) $name = $parts[0] . ":" . $parts[1]; // Handle minecraft:item
            
            $count = (int)end($parts);
            
            // Parser de itens da API 5
            $item = StringToItemParser::getInstance()->parse($itemStr);
            if ($item instanceof Item) {
                // Se a string já tinha quantidade no parser, ok, senão força
                // Como o parser as vezes ignora quantidade no final da string se não for padrão:
                $itemsToAdd[] = $item; 
            }
        }
        
        // Verifica espaço
        if (!$inv->canAddItem(...$itemsToAdd)) {
            $player->sendMessage($config->getNested("settings.prefix") . $config->getNested("messages.inventory-full"));
            return;
        }

        $inv->addItem(...$itemsToAdd);

        // 5. Aplica Buffs
        if (isset($kit['buffs'])) {
            foreach ($kit['buffs'] as $effectName => $level) {
                if ($effectName === "duration") continue;
                
                $effect = StringToEffectParser::getInstance()->parse($effectName);
                if ($effect !== null) {
                    $duration = (int)($kit['buffs']['duration'] ?? 60) * 20; // Converte para ticks
                    $player->getEffects()->add(new EffectInstance($effect, $duration, $level - 1));
                }
            }
        }

        // 6. Finalização
        $this->setCooldown($player, $kitKey);
        $msg = str_replace("{KIT}", $kit['name'], $config->getNested("messages.received"));
        $player->sendMessage($config->getNested("settings.prefix") . $msg);
        
        // Toca som
        $sound = $config->getNested("settings.sound-success", "random.levelup");
        // Implementação simplificada de som via pacote (omitida para brevidade)
    }

    // --- GUI FORMAPI ---

    public function openKitForm(Player $player): void {
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        if ($api === null) return;

        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) return;
            $this->attemptClaim($player, $data);
        });

        $config = $this->plugin->getConfig();
        $form->setTitle($config->getNested("gui.title"));
        $form->setContent($config->getNested("gui.content"));

        $kits = $config->get("kits", []);
        
        foreach ($kits as $key => $kit) {
            $name = $kit['name'];
            $timeLeft = $this->getCooldownLeft($player, $key, (int)$kit['cooldown']);
            
            // Adiciona status visual no botão
            if ($timeLeft > 0) {
                $name .= "\n§cEm Cooldown: " . gmdate("H:i:s", $timeLeft);
            } elseif (isset($kit['permission']) && !$player->hasPermission($kit['permission'])) {
                $name .= "\n§cBloqueado (Rank)";
            } else {
                $name .= "\n§aDisponível";
            }

            // Ícone
            $iconType = -1;
            $iconPath = "";
            if (isset($kit['icon'])) {
                $iconType = str_contains($kit['icon'], "http") ? 1 : 0;
                $iconPath = $kit['icon'];
            }

            // O $key é enviado como data para o callback saber qual kit pegar
            $form->addButton($name, $iconType, $iconPath, $key);
        }

        $player->sendForm($form);
    }
}
