<?php
declare(strict_types=1);


namespace App\Config;

/**
 * Class AppConfig
 * @package App\Config
 */
class AppConfig
{
    const CATEGORIES_MAP = [
        4 => 121,
        1 => 122,
        2 => 123,
        3 => 124,
        5 => 125,
        6 => 126,
        7 => 127,
        9 => 128,
        10 => 129
    ];


    const ATTRIBUTES_DESIGN_MAPS = [
        1 => "Bistrot",
        2 => "Contemporain",
        4 => "Rustique",
        5 => "Style",
        6 => "Terrasse",
        7 => "Classique",
        8 => "Vintage",
        9 => "Premium",
    ];

    const ATTRIBUTES_MAPS = [

        2 => "Design", // Rustique , vintage etc...
        3 => "Collection", //
        4 => "Type de mobilier ", // Interieur / Exterieur
    ];

    const DEFAULT_CATEGORY = 120;

}
