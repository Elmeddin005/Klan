<?php

// ======================================
// ƏSAS ZƏRBƏ HESABLAMA SİSTEMİ
// ======================================

function zerbeHesabla($min_zerbe, $max_zerbe, $mudafie)
{
    $zerbe = random_int(
        (int)$min_zerbe,
        (int)$max_zerbe
    );

    $vurulan_zerbe = $zerbe - (int)$mudafie;

    if ($vurulan_zerbe < 0) {
        $vurulan_zerbe = 0;
    }

    return $vurulan_zerbe;
}


// ======================================
// KRİT ZƏRBƏ HESABLAMA SİSTEMİ
// ======================================

function kritZerbeHesabla($min_zerbe, $max_zerbe, $mudafie)
{
    $zerbe = random_int(
        (int)$min_zerbe,
        (int)$max_zerbe
    );

    // Krit zərbəsi 2 qatdır
    $krit_zerbe = $zerbe * 2;

    // Rəqibin müdafiəsi çıxılır
    $vurulan_krit = $krit_zerbe - (int)$mudafie;

    if ($vurulan_krit < 0) {
        $vurulan_krit = 0;
    }

    return $vurulan_krit;
}


// ======================================
// KRİT TUTMA FAİZİ
// ======================================

function kritFaiziHesabla($menim_krit, $reqib_anti_krit)
{
    // Mənim kritimdən rəqibin anti-kriti çıxılır
    $netice = (int)$menim_krit - (int)$reqib_anti_krit;

    // Bərabər və ya aşağıdırsa krit şansı yoxdur
    if ($netice <= 0) {
        return 0;
    }

    // Qalan nəticə 100-ə bölünür
    $faiz = $netice / 100;

    // 100%-dən çox ola bilməz
    if ($faiz > 100) {
        $faiz = 100;
    }

    return $faiz;
}

// ======================================
// KRİT DÜŞÜB-DÜŞMƏDİ
// ======================================

function kritOldu($krit_faizi)
{
    // 0.01% dəqiqliklə təsadüfi rəqəm
    $random = random_int(1, 10000) / 100;

    return $random <= $krit_faizi;
}

// ======================================
// UVOROT FAİZİ HESABLAMA
// ======================================

function uvorotFaiziHesabla($menim_uvorot, $reqib_anti_uvorot)
{
    // Mənim uvorotumdan rəqibin anti-uvarotu çıxılır
    $netice = (int)$menim_uvorot - (int)$reqib_anti_uvorot;

    // Bərabər və ya aşağıdırsa yayınma şansı yoxdur
    if ($netice <= 0) {
        return 0;
    }

    // Qalan nəticə 100-ə bölünür
    $faiz = $netice / 100;

    // 100%-dən çox ola bilməz
    if ($faiz > 100) {
        $faiz = 100;
    }

    return $faiz;
}


// ======================================
// UVOROT DÜŞÜB-DÜŞMƏDİ
// ======================================

function uvorotOldu($uvorot_faizi)
{
    // 0.01% dəqiqliklə təsadüfi rəqəm
    $random = random_int(1, 10000) / 100;

    // Faizə uyğun yayınma yoxlaması
    return $random <= $uvorot_faizi;
}