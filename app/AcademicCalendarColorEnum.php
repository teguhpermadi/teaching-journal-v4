<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum AcademicCalendarColorEnum: string implements HasLabel
{
    case LAVENDER = '#7986CB';
    case SAGE = '#33B679';
    case GRAPE = '#8E24AA';
    case FLAMINGO = '#E67C73';
    case BANANA = '#F6C026';
    case TANGERINE = '#F5511D';
    case PEACOCK = '#039BE5';
    case GRAPHITE = '#616161';
    case BLUEBERRY = '#3F51B5';
    case BASIL = '#0B8043';
    case TOMATO = '#D50000';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LAVENDER => 'Lavender',
            self::SAGE => 'Sage',
            self::GRAPE => 'Grape',
            self::FLAMINGO => 'Flamingo',
            self::BANANA => 'Banana',
            self::TANGERINE => 'Tangerine',
            self::PEACOCK => 'Peacock',
            self::GRAPHITE => 'Graphite',
            self::BLUEBERRY => 'Blueberry',
            self::BASIL => 'Basil',
            self::TOMATO => 'Tomato',
        };
    }

    public static function optionsWithPreview(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = sprintf(
                '<span style="display:inline-block;width:14px;height:14px;border-radius:9999px;background:%s;margin-right:8px;vertical-align:middle;border:1px solid rgba(0,0,0,0.1);"></span> %s',
                $case->value,
                $case->getLabel()
            );
        }

        return $options;
    }

    public function getColorId(): int
    {
        return match ($this) {
            self::LAVENDER => 1,
            self::SAGE => 2,
            self::GRAPE => 3,
            self::FLAMINGO => 4,
            self::BANANA => 5,
            self::TANGERINE => 6,
            self::PEACOCK => 7,
            self::GRAPHITE => 8,
            self::BLUEBERRY => 9,
            self::BASIL => 10,
            self::TOMATO => 11,
        };
    }

    public static function fromColorId(?int $colorId): ?self
    {
        if ($colorId === null) {
            return null;
        }

        return match ($colorId) {
            1 => self::LAVENDER,
            2 => self::SAGE,
            3 => self::GRAPE,
            4 => self::FLAMINGO,
            5 => self::BANANA,
            6 => self::TANGERINE,
            7 => self::PEACOCK,
            8 => self::GRAPHITE,
            9 => self::BLUEBERRY,
            10 => self::BASIL,
            11 => self::TOMATO,
            default => null,
        };
    }
}
