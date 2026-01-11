<?php

declare(strict_types=1);

namespace SurvivalKits\Utils;

class TimeUtils {

    /**
     * Converte segundos em um formato legível (HH:MM:SS)
     * * @param int $seconds
     * @return string
     */
    public static function formatTime(int $seconds): string {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }

    /**
     * Converte segundos em uma string detalhada por extenso
     * Exemplo: 3661 -> 1h 1m 1s
     * * @param int $seconds
     * @return string
     */
    public static function formatShort(int $seconds): string {
        if ($seconds <= 0) return "0s";

        $h = floor($seconds / 3600);
        $m = floor(($seconds / 60) % 60);
        $s = $seconds % 60;

        $parts = [];
        if ($h > 0) $parts[] = $h . "h";
        if ($m > 0) $parts[] = $m . "m";
        if ($s > 0 || empty($parts)) $parts[] = $s . "s";

        return implode(" ", $parts);
    }
}
