<?php

$dimensions = [
    ['hauteur' => '81'],
    ['hauteur assise' => '46' ],
    ['hauteur accoudoire' =>  '65']
];

foreach ($dimensions as $dimension) {
    foreach ($dimension as $key => $value) {
        echo $key . " => " .  $value;
    }
}