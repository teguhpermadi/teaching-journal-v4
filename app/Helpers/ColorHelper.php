<?php

namespace App\Helpers;

class ColorHelper
{
    /**
     * Convert RGB color string to HEX format
     *
     * @param string $rgb RGB color string (e.g., 'rgb(255, 99, 132)')
     * @return string HEX color (e.g., '#ff6384')
     */
    public static function rgbToHex(string $rgb): string
    {
        if (preg_match('/^rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $rgb, $matches)) {
            return sprintf(
                "#%02x%02x%02x",
                $matches[1],
                $matches[2],
                $matches[3]
            );
        }
        
        return $rgb; // Return as is if not in RGB format
    }

    /**
     * Normalize color to proper HEX format
     * 
     * @param string $color Input color (RGB, HEX with or without #)
     * @return string Normalized HEX color with # prefix
     */
    public static function normalizeColor(string $color): string
    {
        // If it's already in valid HEX format (3 or 6 hex digits, optional # prefix)
        if (preg_match('/^#?([a-f0-9]{3}|[a-f0-9]{6})$/i', $color)) {
            $hex = ltrim($color, '#');
            // Convert 3-digit hex to 6-digit
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            return '#' . $hex;
        }
        
        // If it's in RGB format, convert to HEX
        if (str_starts_with(strtolower($color), 'rgb')) {
            return self::rgbToHex($color);
        }
        
        // Default color if format is not recognized
        return '#3b82f6';
    }
}
