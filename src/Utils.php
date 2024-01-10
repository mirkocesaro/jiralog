<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console;

class Utils
{
    public static function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $formattedTime = [];
        if($hours) $formattedTime[] = $hours . "h";
        if($minutes) $formattedTime[] = $minutes . "m";

        return implode(" ", $formattedTime);
    }
}