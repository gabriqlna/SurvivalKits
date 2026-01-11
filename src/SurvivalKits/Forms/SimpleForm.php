<?php

declare(strict_types=1);

namespace SurvivalKits\Forms;

use pocketmine\form\Form;
use pocketmine\player\Player;

class SimpleForm implements Form {
    private array $data = ["type" => "form", "title" => "", "content" => "", "buttons" => []];
    private $callable;

    public function __construct(?callable $callable) {
        $this->callable = $callable;
    }

    public function setTitle(string $title): void { $this->data["title"] = $title; }
    public function setContent(string $content): void { $this->data["content"] = $content; }
    public function addButton(string $text, int $imageType = -1, string $imagePath = ""): void {
        $content = ["text" => $text];
        if ($imageType !== -1) {
            $content["image"] = ["type" => $imageType === 0 ? "path" : "url", "data" => $imagePath];
        }
        $this->data["buttons"][] = $content;
    }

    public function handleResponse(Player $player, $data): void {
        $callable = $this->callable;
        if ($callable !== null) $callable($player, $data);
    }

    public function jsonSerialize(): array { return $this->data; }
}
