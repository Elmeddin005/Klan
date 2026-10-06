<?php

$seviyye_tecrubeleri = [

    1 => [
        'min' => 10,
        'max' => 512
    ],

    2 => [
        'min' => 512,
        'max' => 2048
    ],

    3 => [
        'min' => 2048,
        'max' => 4096
    ],

    4 => [
        'min' => 4096,
        'max' => 8192
    ],

    5 => [
        'min' => 8192,
        'max' => 16384
    ],

    6 => [
        'min' => 16384,
        'max' => 32768
    ],

    7 => [
        'min' => 32768,
        'max' => 65536
    ],

    8 => [
        'min' => 65536,
        'max' => 131072
    ],

    9 => [
        'min' => 131072,
        'max' => 262144
    ],

    10 => [
        'min' => 262144,
        'max' => 524288
    ],

    11 => [
        'min' => 524288,
        'max' => 1048576
    ],

    12 => [
        'min' => 1048576,
        'max' => 2097152
    ],

    13 => [
        'min' => 2097152,
        'max' => 4194304
    ],

    14 => [
        'min' => 4194304,
        'max' => 8388608
    ]

];
function tecrubeye_gore_seviyye($tecrube)
{
    global $seviyye_tecrubeleri;

    $tecrube = (int)$tecrube;

    foreach ($seviyye_tecrubeleri as $seviyye => $araliq) {

        if (
            $tecrube >= $araliq['min'] &&
            $tecrube < $araliq['max']
        ) {
            return (int)$seviyye;
        }
    }

    /*
     * 14-cü səviyyənin maksimumuna çatdıqda
     * 14-də saxla.
     */
    if (
        isset($seviyye_tecrubeleri[14]) &&
        $tecrube >= $seviyye_tecrubeleri[14]['max']
    ) {
        return 14;
    }

    return 1;
}