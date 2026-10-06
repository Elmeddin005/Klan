
<?php

session_start();
require_once "config.php";
require_once "user_data.php";
require_once "doyus_sistem.php";
require_once "guc_parametrləri.php";



if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
/* ==========================================================
   1-1 OYUNÇU PARAMETRLƏRİ
   ========================================================== */

$stmt_oyuncu = $pdo->prepare("
    SELECT
        can,
        mudafie,
        min_zerbe,
        max_zerbe,
        krit,
        anti_krit,
        uvorot,
        anti_uvorot
    FROM oyuncu_parametrleri
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_oyuncu->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$oyuncu = $stmt_oyuncu->fetch(PDO::FETCH_ASSOC);
/* ==========================================================
   1-1 OYUNÇU GÜC BONUSLARI
   ========================================================== */

$stmt_guc = $pdo->prepare("
    SELECT
        zerbe,
        mudafie,
        can,
        krit,
        anti_krit,
        uvorot,
        anti_uvorot
    FROM oyuncu_guc_bonuslari
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_guc->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$guc_bonus = $stmt_guc->fetch(PDO::FETCH_ASSOC);

if (!$guc_bonus) {
    $guc_bonus = [
        'zerbe' => 0,
        'mudafie' => 0,
        'can' => 0,
        'krit' => 0,
        'anti_krit' => 0,
        'uvorot' => 0,
        'anti_uvorot' => 0
    ];
}
/* ==========================================================
   GEYİMDƏKİ ƏŞYA BONUSLARI
   ========================================================== */

$esya_bonus = [
    'can' => 0,
    'mudafie' => 0,
    'min_zerbe' => 0,
    'max_zerbe' => 0,
    'krit' => 0,
    'krit_faiz' => 0,
    'anti_krit' => 0,
    'anti_krit_faiz' => 0,
    'uvorot' => 0,
    'uvorot_faiz' => 0,
    'anti_uvorot' => 0,
    'anti_uvorot_faiz' => 0
];

$stmt_esya_bonus = $pdo->prepare("
    SELECT
        COALESCE(SUM(random_can), 0) AS can,
        COALESCE(SUM(random_mudafie), 0) AS mudafie,
        COALESCE(SUM(random_min_zerbe), 0) AS min_zerbe,
        COALESCE(SUM(random_max_zerbe), 0) AS max_zerbe,

        COALESCE(SUM(random_krit), 0) AS krit,
        COALESCE(SUM(random_krit_faiz), 0) AS krit_faiz,

        COALESCE(SUM(random_anti_krit), 0) AS anti_krit,
        COALESCE(SUM(random_anti_krit_faiz), 0) AS anti_krit_faiz,

        COALESCE(SUM(random_uvorot), 0) AS uvorot,
        COALESCE(SUM(random_uvorot_faiz), 0) AS uvorot_faiz,

        COALESCE(SUM(random_anti_uvorot), 0) AS anti_uvorot,
        COALESCE(SUM(random_anti_uvorot_faiz), 0) AS anti_uvorot_faiz

    FROM canta
    WHERE user_id = :user_id
      AND geyimde = 1
      AND say > 0
");


$stmt_esya_bonus->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$esya_bonus_db = $stmt_esya_bonus->fetch(PDO::FETCH_ASSOC);

if ($esya_bonus_db) {
    $esya_bonus = array_merge(
        $esya_bonus,
        array_map('intval', $esya_bonus_db)
    );
}

/* ==========================================================
   DÖYÜŞ NƏTİCƏSİ
   ========================================================== */

$vuruldu = isset($_POST['action']);

/* ==========================================================
   BIG CASTLE / VAHSI MOB
   ========================================================== */

$is_big_castle = (
    isset($_GET['qala']) &&
    $_GET['qala'] === 'bigcastle'
);

$is_vahsi_mob = (
    isset($_GET['vahsi']) &&
    $_GET['vahsi'] === '1'
);
/* ==========================================================
   ÜMUMİ KOORDİNAT
   ========================================================== */

$kordinat = isset($_SESSION['son_kordinat'])
    ? (int)$_SESSION['son_kordinat']
    : 1;

if ($kordinat < 1) {
    $kordinat = 1;
}

/* ==========================================================
   BIG CASTLE KOORDİNATI
   ========================================================== */

if ($is_big_castle && isset($_GET['kordinat'])) {
    $_SESSION['bigcastle_kordinat'] = (int)$_GET['kordinat'];
}

$bigcastle_kordinat = isset($_SESSION['bigcastle_kordinat'])
    ? (int)$_SESSION['bigcastle_kordinat']
    : 1;

/* ==========================================================
   MOBU MYSQL-DAN GƏTİR
   ========================================================== */

$bot_id = isset($_GET['uid'])
    ? (int)$_GET['uid']
    : 0;

if ($bot_id <= 0) {
    exit('Bot seçilməyib.');
}

$stmt_bot = $pdo->prepare("
    SELECT
        id,
        ad,
        seviyye,
        qizil,
        tecrube,
        img,
        can,
        mudafie,
        zerbe_min,
        zerbe_max,
        krit,
        anti_krit,
        uvarotu,
        anti_uvarotu
    FROM botlar
    WHERE id = :id
    LIMIT 1
");


$stmt_bot->execute([
    ':id' => $bot_id
]);

$bot = $stmt_bot->fetch(PDO::FETCH_ASSOC);

if (!$bot) {
    exit('Bot tapılmadı.');
}
/* ==========================================================
   ENERJİ YOXLAMASI
   ========================================================== */

$menim_enerjim = isset($user['enerjı'])
    ? (int)$user['enerjı']
    : 0;


$enerji_bitib = (
    isset($_GET['enerji_bitib']) &&
    $_GET['enerji_bitib'] === '1'
);







/* ==========================================================
   MOB MƏLUMATLARI
   ========================================================== */

$bot_ad = $bot['ad'];

$bot_seviyye = (int)$bot['seviyye'];

$bot_max_can = (int)$bot['can'];

$bot_qizil = (int)$bot['qizil'];

$bot_tecrube = (int)$bot['tecrube'];

$bot_mudafie = (int)$bot['mudafie'];

$bot_min_zerbe = (int)$bot['zerbe_min'];

$bot_max_zerbe = (int)$bot['zerbe_max'];

$bot_krit = (int)$bot['krit'];

$bot_anti_krit = (int)$bot['anti_krit'];

$bot_uvarotu = (int)$bot['uvarotu'];

$bot_anti_uvarotu = (int)$bot['anti_uvarotu'];
/* ==========================================================
   VIP STATUSU
   ========================================================== */

$stmt_vip = $pdo->prepare("
    SELECT vip, vip_bitme_vaxti
    FROM users
    WHERE id = :user_id
    LIMIT 1
");

$stmt_vip->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$vip_melumat = $stmt_vip->fetch(PDO::FETCH_ASSOC);

$vip_status = (int)($vip_melumat['vip'] ?? 0);
$vip_bitme_vaxti = $vip_melumat['vip_bitme_vaxti'] ?? null;


/* VIP vaxtı bitibsə */
if (
    $vip_status === 1 &&
    !empty($vip_bitme_vaxti) &&
    strtotime($vip_bitme_vaxti) <= time()
) {
    $vip_status = 0;
}
/* ==========================================================
   TƏCRÜBƏ MÜKAFATI
   Adi istifadəçi: 16
   VIP istifadəçi: 32
   ========================================================== */

$verilecek_tecrube = ($vip_status === 1) ? 32 : 16;


/* ==========================================================
   DÖYÜŞ SESSION
   ========================================================== */

if (!isset($_SESSION['bot_battle'])) {
    $_SESSION['bot_battle'] = array();
}


/* ==========================================================
   DÖYÜŞ ID
   ========================================================== */

if (
    isset($_GET['doyus_id']) &&
    $_GET['doyus_id'] !== ''
) {
    $mecun_doyus_key = (string)$_GET['doyus_id'];
} else {
    $mecun_doyus_key = uniqid('doyus_', true);
}


/* ==========================================================
   HƏR MOB + HƏR DÖYÜŞ ÜÇÜN AYRI SESSION
   ========================================================== */

$battle_key = 'bot_' . $bot_id . '_' . $mecun_doyus_key;


/* ==========================================================
   YENİ DÖYÜŞ
   ========================================================== */

if (!isset($_SESSION['bot_battle'][$battle_key])) {

    $_SESSION['bot_battle'][$battle_key] = array(

        'can' => $bot_max_can,

        'menim_can' => null,

        'raund' => 0,

        'qalib' => false

    );
}


/* ==========================================================
   OYUNÇUNUN PARAMETRLƏRİ
   ========================================================== */

$stmt_oyuncu = $pdo->prepare("
    SELECT
        can,
        can_faiz,
        mudafie,
        min_zerbe,
        max_zerbe,
        krit,
        krit_faiz,
        anti_krit,
        anti_krit_faiz,
        uvorot,
        uvorot_faiz,
        anti_uvorot,
        anti_uvorot_faiz,
        zerbe_faiz
    FROM oyuncu_parametrleri
    WHERE user_id = :user_id
    LIMIT 1
");


$stmt_oyuncu->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$oyuncu = $stmt_oyuncu->fetch(PDO::FETCH_ASSOC);

/* ==========================================================
   OYUNÇUNUN GÜC BONUSLARI
   ========================================================== */

$stmt_guc = $pdo->prepare("
    SELECT
        zerbe,
        mudafie,
        can,
        krit,
        anti_krit,
        uvorot,
        anti_uvorot
    FROM oyuncu_guc_bonuslari
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_guc->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$guc_bonus = $stmt_guc->fetch(PDO::FETCH_ASSOC);

if (!$guc_bonus) {
    $guc_bonus = [
        'zerbe' => 0,
        'mudafie' => 0,
        'can' => 0,
        'krit' => 0,
        'anti_krit' => 0,
        'uvorot' => 0,
        'anti_uvorot' => 0
    ];
}

/* ==========================================================
   PARAMETR YOXDURSA İLKİN PARAMETRLƏRDƏN İSTİFADƏ ET
   ========================================================== */

if (!$oyuncu) {

    $oyuncu = [
        'can' => $ilkin_can,
        'mudafie' => $ilkin_mudafie,
        'min_zerbe' => $ilkin_zerbe_min,
        'max_zerbe' => $ilkin_zerbe_max,
        'krit' => $ilkin_krit,
        'anti_krit' => $ilkin_antikrit,
        'uvorot' => $ilkin_uvorot,
       'anti_uvorot' => $ilkin_antiuvorot

    ];

}



/* ==========================================================
   YEKUN OYUNÇU PARAMETRLƏRİ
   İLKİN + OYUNCU PARAMETRİ + GÜC BONUSU + ƏŞYA
   ========================================================== */

/* ==========================================================
   CAN
   INFOFORCE İLƏ EYNİ MƏNTİQ
   ========================================================== */

$menim_can_max =
    (int)$ilkin_can +
    (int)$oyuncu['can'] +
    (int)$guc_bonus['can'];

/* ==========================================================
   MÜDAFİƏ
   INFOFORCE İLƏ EYNİ MƏNTİQ
   ========================================================== */

$menim_mudafie =
    (int)$ilkin_mudafie +
    (int)$oyuncu['mudafie'] +
    (int)$guc_bonus['mudafie'];

/* ==========================================================
   YEKUN ZƏRBƏ — INFOFORCE İLƏ EYNİ MƏNTİQ
   ========================================================== */

$zerbe_1 =
    (int)$ilkin_zerbe_min +
    (int)$oyuncu['min_zerbe'] +
    (int)$guc_bonus['zerbe'];

$zerbe_2 =
    (int)$ilkin_zerbe_max +
    (int)$oyuncu['max_zerbe'] +
    (int)$guc_bonus['zerbe'];

$menim_min_zerbem = min($zerbe_1, $zerbe_2);
$menim_max_zerbem = max($zerbe_1, $zerbe_2);


/* ==========================================================
   KRİT
   INFOFORCE İLƏ EYNİ MƏNTİQ
   ========================================================== */

/* ==========================================================
   KRİT FAİZİ — INFOFORCE İLƏ EYNİ
   ========================================================== */

$menim_krit_faiz =
    (int)$esya_bonus['krit_faiz'];


$menim_krit_esas =
    (int)$ilkin_krit
    + (int)$oyuncu['krit']
    + (int)$guc_bonus['krit'];

$menim_krit =
    $menim_krit_esas
    + (
        $menim_krit_esas
        * $menim_krit_faiz
        / 100
    );

$menim_krit = (int)round($menim_krit);


/* ==========================================================
   ANTI KRİT — INFOFORCE İLƏ EYNİ
   ========================================================== */

$menim_anti_krit_esas =
    (int)$ilkin_antikrit
    + (int)$oyuncu['anti_krit']
    + (int)$guc_bonus['anti_krit'];

$menim_anti_krit_faiz =
    (int)$esya_bonus['anti_krit_faiz'];

$menim_anti_krit =
    $menim_anti_krit_esas
    + (
        $menim_anti_krit_esas
        * $menim_anti_krit_faiz
        / 100
    );

$menim_anti_krit = (int)round($menim_anti_krit);


/* ==========================================================
   UVOROT — INFOFORCE İLƏ EYNİ
   ========================================================== */

$menim_uvorot_esas =
    (int)$ilkin_uvorot
    + (int)$oyuncu['uvorot']
    + (int)$guc_bonus['uvorot'];

$menim_uvorot_faiz =
    (int)$esya_bonus['uvorot_faiz'];

$menim_uvorot =
    $menim_uvorot_esas
    + (
        $menim_uvorot_esas
        * $menim_uvorot_faiz
        / 100
    );

$menim_uvorot = (int)round($menim_uvorot);


/* ==========================================================
   ANTI UVOROT — INFOFORCE İLƏ EYNİ
   ========================================================== */

$menim_anti_uvorot_esas =
    (int)$ilkin_antiuvorot
    + (int)$oyuncu['anti_uvorot']
    + (int)$guc_bonus['anti_uvorot'];

$menim_anti_uvorot_faiz =
    (int)$esya_bonus['anti_uvorot_faiz'];

$menim_anti_uvorot =
    $menim_anti_uvorot_esas
    + (
        $menim_anti_uvorot_esas
        * $menim_anti_uvorot_faiz
        / 100
    );

$menim_anti_uvorot = (int)round($menim_anti_uvorot);



/* ==========================================================
   İLK DÖYÜŞDƏ OYUNÇUNUN YEKUN CANINI TƏYİN ET
   ========================================================== */

if (
    !isset($_SESSION['bot_battle'][$battle_key]['menim_can']) ||
    $_SESSION['bot_battle'][$battle_key]['menim_can'] === null
) {

    $_SESSION['bot_battle']
    [$battle_key]
    ['menim_can'] = $menim_can_max;
}


/* ==========================================================
   OYUNÇUNUN CANI
   ========================================================== */

if (
    !isset(
        $_SESSION['bot_battle']
        [$battle_key]
        ['menim_can']
    )
) {

    $_SESSION['bot_battle']
    [$battle_key]
    ['menim_can'] = $menim_can_max;
}


/* ==========================================================
   CANLAR
   ========================================================== */

$bot_can = (int)
    $_SESSION['bot_battle']
    [$battle_key]
    ['can'];

$menim_can = (int)
    $_SESSION['bot_battle']
    [$battle_key]
    ['menim_can'];


/* ==========================================================
   RAUND
   ========================================================== */


if ($vuruldu && $bot_can > 0 && $menim_can > 0) {

    $_SESSION['bot_battle']
    [$battle_key]
    ['raund']++;

}


$raund = (int)
    $_SESSION['bot_battle']
    [$battle_key]
    ['raund'];


/* ==========================================================
   DÖYÜŞ NƏTİCƏLƏRİ ÜÇÜN DƏYİŞƏNLƏR
   ========================================================== */

$hucum_yeri = 0;
$hucum_yazi = '';

$mudafie_yeri = 0;
$mudafie_yazi = '';

$bot_hucum_yeri = 0;
$bot_hucum_yazi = '';

$bot_mudafie_yeri = 0;
$bot_mudafie_yazi = '';

$def_edildi = false;
$bot_def_edildi = false;

$vurulan_zerbe = 0;
$bot_vurdugu_zerbe = 0;

$krit_oldu = false;
$bot_krit_oldu = false;

$uvorot_oldu = false;
$bot_uvorot_oldu = false;

$bot_yayindi = false;
$menim_yayindim = false;


/* ==========================================================
   HÜCUM / MÜDAFİƏ YAZILARI
   ========================================================== */

$hucum_yazilari = array(
    0 => 'Başdan',
    1 => 'Sinədən',
    2 => 'Gövdədən',
    3 => 'Ayaqdan'
);

$mudafie_yazilari = array(
    0 => 'Baş və Sinə',
    1 => 'Sinə və Gövdə',
    2 => 'Gövdə və Ayaq',
    3 => 'Ayaq və Baş'
);


/* ==========================================================
   KRİT FAİZLƏRİ
   ========================================================== */

/*
   Sənin kritin -> botun anti-kriti
*/
$menim_krit_faizi = kritFaiziHesabla(
    $menim_krit,
    $bot_anti_krit
);


/*
   Sənin uvorotun -> botun anti-uvarotu
*/
$menim_uvorot_faizi = uvorotFaiziHesabla(
    $menim_uvorot,
    $bot_anti_uvarotu
);


/*
   Botun kriti -> sənin anti-kritin
*/
$bot_krit_faizi = kritFaiziHesabla(
    $bot_krit,
    $menim_anti_krit
);


/*
   Botun uvorotu -> sənin anti-uvarotun
*/
$bot_uvorot_faizi = uvorotFaiziHesabla(
    $bot_uvarotu,
    $menim_anti_uvorot
);




/* ==========================================================
   DÖYÜŞ HESABLAMASI
   ========================================================== */



if (
    $vuruldu &&
    $bot_can > 0 &&
    $menim_can > 0
) {


    /* ======================================================
       OYUNÇUNUN HÜCUMU
       ====================================================== */

    $hucum_yeri = isset($_POST['hucum'])
        ? (int)$_POST['hucum']
        : 0;

    $mudafie_yeri = isset($_POST['mudafie'])
        ? (int)$_POST['mudafie']
        : 0;


    /* ======================================================
       DÜZGÜN ARALIQ
       ====================================================== */

    if ($hucum_yeri < 0 || $hucum_yeri > 3) {
        $hucum_yeri = 0;
    }

    if ($mudafie_yeri < 0 || $mudafie_yeri > 3) {
        $mudafie_yeri = 0;
    }


    /* ======================================================
       YAZILAR
       ====================================================== */

    $hucum_yazi =
        $hucum_yazilari[$hucum_yeri];

    $mudafie_yazi =
        $mudafie_yazilari[$mudafie_yeri];


    /* ======================================================
       BOTUN TƏSADÜFİ HÜCUMU
       ====================================================== */

    $bot_hucum_yeri = random_int(0, 3);

    $bot_hucum_yazi =
        $hucum_yazilari[$bot_hucum_yeri];


    /* ======================================================
       BOTUN TƏSADÜFİ MÜDAFİƏSİ
       ====================================================== */

    $bot_mudafie_yeri = random_int(0, 3);

    $bot_mudafie_yazi =
        $mudafie_yazilari[$bot_mudafie_yeri];


    /* ======================================================
       UVOROT ŞANS YOXLAMASI
       ====================================================== */

    $bot_uvorot_oldu = uvorotOldu(
        $bot_uvorot_faizi
    );

    $menim_yayindim = uvorotOldu(
        $menim_uvorot_faizi
    );


    /* ======================================================
       SƏNİN ZƏRBƏNƏ BOTUN UVOROTU
       ====================================================== */

/* ==================================================
   SƏNİN ZƏRBƏNƏ BOTUN UVOROTU
   ================================================== */

if ($bot_uvorot_oldu) {

    $def_edildi = true;
    $vurulan_zerbe = 0;

} else {

    /* ==================================================
       BOTUN MÜDAFİƏ ETDİYİ YERLƏR
       ================================================== */

    $bot_mudafie_yerleri = [

        0 => [0, 1], // Baş və Sinə
        1 => [1, 2], // Sinə və Gövdə
        2 => [2, 3], // Gövdə və Ayaq
        3 => [3, 0]  // Ayaq və Baş

    ];

    /*
     * Botun müdafiə etdiyi 2 yeri götürürük
     */

    $bot_mudafie_bloklari =
        $bot_mudafie_yerleri[$bot_mudafie_yeri] ?? [0, 1];


    /* ==================================================
       BOT SƏNİN VURDUĞUN YERİ TUTURSA
       ================================================== */

    if (
        in_array(
            $hucum_yeri,
            $bot_mudafie_bloklari,
            true
        )
    ) {

        $def_edildi = true;

        $vurulan_zerbe = 0;

    } else {

        /*
         * Bot müdafiə etmədiyi yerə vuruldu.
         * İndi krit yoxlanılır.
         */

        $krit_oldu = kritOldu(
            $menim_krit_faizi
        );


        /* ==================================================
           KRİT ZƏRBƏ
           ================================================== */

        if ($krit_oldu) {

            $vurulan_zerbe = kritZerbeHesabla(
                $menim_min_zerbem,
                $menim_max_zerbem,
                $bot_mudafie
            );

        }

        /* ==================================================
           NORMAL ZƏRBƏ
           ================================================== */

        else {

            $vurulan_zerbe = zerbeHesabla(
                $menim_min_zerbem,
                $menim_max_zerbem,
                $bot_mudafie
            );

        }


        /* ==================================================
           BOTUN CANINI AZALT
           ================================================== */

        $bot_can -= $vurulan_zerbe;

        if ($bot_can < 0) {
            $bot_can = 0;
        }

    }

}

    /* ======================================================
       BOTUN ZƏRBƏSİ
       ====================================================== */

    /*
       Əgər sənin uvorotun tutubsa,
       bot sənə vura bilmir.
    */

   if ($bot_can <= 0) {

    // Mob sənin son zərbəndə öldüsə, artıq geri vura bilməz
    $bot_def_edildi = true;
    $bot_vurdugu_zerbe = 0;

} elseif ($menim_yayindim) {

    // Sən uvorot etdinsə
    $bot_def_edildi = true;
    $bot_vurdugu_zerbe = 0;

} elseif (
    ($mudafie_yeri === 0 && ($bot_hucum_yeri === 0 || $bot_hucum_yeri === 1)) ||
    ($mudafie_yeri === 1 && ($bot_hucum_yeri === 1 || $bot_hucum_yeri === 2)) ||
    ($mudafie_yeri === 2 && ($bot_hucum_yeri === 2 || $bot_hucum_yeri === 3)) ||
    ($mudafie_yeri === 3 && ($bot_hucum_yeri === 3 || $bot_hucum_yeri === 0))
) {

    // Bot sənin müdafiə etdiyin yeri vurdu
    $bot_def_edildi = true;
    $bot_vurdugu_zerbe = 0;

} else {

    /*
       Sən botun hücum etdiyi yeri müdafiə etmədinsə,
       botun kriti yoxlanılır.
    */

    $bot_krit_oldu = kritOldu(
        $bot_krit_faizi
    );

    if ($bot_krit_oldu) {

        $bot_vurdugu_zerbe = kritZerbeHesabla(
            $bot_min_zerbe,
            $bot_max_zerbe,
            $menim_mudafie
        );

    } else {

        $bot_vurdugu_zerbe = zerbeHesabla(
            $bot_min_zerbe,
            $bot_max_zerbe,
            $menim_mudafie
        );

    }

    $menim_can -= $bot_vurdugu_zerbe;

    if ($menim_can < 0) {
        $menim_can = 0;
    }


        /*
           Sən yayınmadınsa,
           botun kriti yoxlanılır.
        */

        $bot_krit_oldu = kritOldu(
            $bot_krit_faizi
        );


        /* ==================================================
           BOTUN ZƏRBƏSİ
           ================================================== */

        if ($bot_krit_oldu) {

            $bot_vurdugu_zerbe = kritZerbeHesabla(
                $bot_min_zerbe,
                $bot_max_zerbe,
                $menim_mudafie
            );

        } else {

            $bot_vurdugu_zerbe = zerbeHesabla(
                $bot_min_zerbe,
                $bot_max_zerbe,
                $menim_mudafie
            );

        }


        /* ==================================================
           SƏNİN CANINI AZALT
           ================================================== */

        $menim_can -= $bot_vurdugu_zerbe;

        if ($menim_can < 0) {
            $menim_can = 0;
        }
    }


    /* ======================================================
       CANLARI SESSION-DA SAXLA
       ====================================================== */

    $_SESSION['bot_battle']
    [$battle_key]
    ['can'] = $bot_can;

    $_SESSION['bot_battle']
    [$battle_key]
    ['menim_can'] = $menim_can;
}
/* ======================================================
   VURMA ÜÇÜN 1 ENERJİ ÇIX
   ====================================================== */

if (
    $vuruldu &&
    !isset($_SESSION['bot_battle'][$battle_key]['enerji_verildi']) &&
    $menim_enerjim > 0
) {

    $stmt_enerji = $pdo->prepare("
        UPDATE users
        SET `enerjı` = GREATEST(`enerjı` - 1, 0)
        WHERE id = :user_id
        LIMIT 1
    ");

    $stmt_enerji->execute([
        ':user_id' => (int)$_SESSION['user_id']
    ]);

    $menim_enerjim = max(0, $menim_enerjim - 1);

    $user['enerjı'] = $menim_enerjim;

    $_SESSION['bot_battle'][$battle_key]['enerji_verildi'] = true;
}

/* ==========================================================
   DÖYÜŞÜN SONU
   ========================================================== */

$qalib = false;
$meglub = false;


/* ==========================================================
   BOT ÖLDÜ
   ========================================================== */

if ($bot_can <= 0) {

    $bot_can = 0;

    $_SESSION['bot_battle']
    [$battle_key]
    ['can'] = 0;

    $_SESSION['bot_battle']
    [$battle_key]
    ['qalib'] = true;

    $qalib = true;

    /* ==================================================
       MOB ÖLDÜ — KORDİNAT 1 ARTIR
       ================================================== */

if (
    !$is_big_castle &&
    !$is_vahsi_mob &&
    !isset($_SESSION['bot_battle'][$battle_key]['kordinat_artdi'])
) {

    // Hazırkı koordinatdan yalnız 1 artır
    $yeni_kordinat = $kordinat + 1;

    // Yeni koordinatı yadda saxla
    $_SESSION['son_kordinat'] = $yeni_kordinat;

    // Bu döyüş üçün də yadda saxla
    $_SESSION['bot_battle'][$battle_key]['kordinat'] = $yeni_kordinat;
    $_SESSION['bot_battle'][$battle_key]['kordinat_artdi'] = true;
}

}


/* ==========================================================
   OYUNÇU ÖLDÜ
   ========================================================== */

elseif ($menim_can <= 0) {

    $menim_can = 0;

    $_SESSION['bot_battle']
    [$battle_key]
    ['menim_can'] = 0;

    $_SESSION['bot_battle']
    [$battle_key]
    ['qalib'] = false;

    $meglub = true;

}


/* ======================================================
   MOB MÜKAFATI + MOB REYTİNQİ
   YALNIZ 1 DƏFƏ
   ====================================================== */

if (
    $qalib &&
    !isset($_SESSION['bot_battle'][$battle_key]['mukafat_verildi'])
) {

    /* ==================================================
       MOB MÜKAFATI
       ================================================== */




$stmt_reward = $pdo->prepare("
    UPDATE users
    SET
        oyuncunun_tecrubesi = oyuncunun_tecrubesi + :tecrube,
        qızıl = qızıl + 15
    WHERE id = :user_id
    LIMIT 1
");

$stmt_reward->execute([
    ':tecrube' => $verilecek_tecrube,
    ':user_id' => (int)$_SESSION['user_id']
]);



    /* ==================================================
       MOB REYTİNQİ — +1
       ================================================== */

    $stmt_mob_reytinq = $pdo->prepare("
        INSERT INTO mob_reytinq
        (
            user_id,
            tarix,
            say
        )
        VALUES
        (
            :user_id,
            CURDATE(),
            1
        )
        ON DUPLICATE KEY UPDATE
            say = say + 1
    ");

    $stmt_mob_reytinq->execute([
        ':user_id' => (int)$_SESSION['user_id']
    ]);


    /* ==================================================
       MÜKAFAT VERİLDİ
       ================================================== */

    $_SESSION['bot_battle']
    [$battle_key]
    ['mukafat_verildi'] = true;
}

/* ==========================================================
   SESSION-DAN QALİB MƏLUMATI
   ========================================================== */

if (
    isset(
        $_SESSION['bot_battle']
        [$battle_key]
        ['qalib']
    ) &&
    $_SESSION['bot_battle']
    [$battle_key]
    ['qalib'] === true
) {

    $qalib = true;
}


/* ==========================================================
   GET İLƏ QALİB
   ========================================================== */

if (
    isset($_GET['qalib']) &&
    $_GET['qalib'] === 'ok'
) {

    $qalib = true;
}

/* ==========================================================
   BOT ŞƏKLİ
   ========================================================== */

$bot_img = $bot['img'];


$stmt_mecunlar = $pdo->prepare("
    SELECT id, ad, img
    FROM esyalar
    WHERE tip = 'mecun'
      AND aktiv = 1
      AND ad IN (
          'Can Məcunu 5%',
          'Can Məcunu 10%',
          'Zərbə Məcunu 5%',
          'Zərbə Məcunu 10%',
          'Müdafiə Məcunu 5%',
          'Müdafiə Məcunu 10%',
          'Sehirli Mecun 20%'
      )
    ORDER BY id ASC
");

$stmt_mecunlar->execute();

$mecunlar = $stmt_mecunlar->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   ƏŞYALAR
   ========================================================== */

$stmt_esyalar = $pdo->prepare("
    SELECT
        id,
        ad,
        seviyye,
        reng,
        img,
        tip,
        sekil,
        goruntu_adi,
        min_zerbe,
        max_zerbe,
        can,
        mudafie,
        krit,
        anti_krit,
        uvorot,
        anti_uvorot,
        davamliliq,
        qiymet,
        mexsusdur,
        max_can,
        max_mudafie,
        min_zerbe_araliq,
        max_zerbe_araliq,
        max_krit,
        max_anti_krit,
        max_uvorot,
        max_anti_uvorot
    FROM esyalar
    WHERE aktiv = 1
    AND seviyye = :seviyye
    AND reng IN ('goy', 'yasil')
    AND tip != 'cekic'
    AND ad != 'Ametist Gürz'
");

$stmt_esyalar->execute([
    ':seviyye' => $bot_seviyye
]);

$esyalar = $stmt_esyalar->fetchAll(PDO::FETCH_ASSOC);


/* ==========================================================
   MÜKAFAT SESSION
   ========================================================== */

if (!isset($_SESSION['bigcastle_reward'])) {
    $_SESSION['bigcastle_reward'] = array();
}


/* ==========================================================
   ÇANTA TUTUMU
   ========================================================== */

$canta_tutumu = 34;

$stmt_canta_tutum = $pdo->prepare("
    SELECT tutum, bitme_vaxti
    FROM istifadeci_cantalari
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_canta_tutum->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$canta_melumat = $stmt_canta_tutum->fetch(PDO::FETCH_ASSOC);

if (
    $canta_melumat &&
    (int)$canta_melumat['bitme_vaxti'] > time()
) {
    $canta_tutumu = (int)$canta_melumat['tutum'];
}


/* ==========================================================
   CANTADAKI ƏŞYALARIN SAYI
   ========================================================== */

$stmt_canta_sayi = $pdo->prepare("
    SELECT COALESCE(SUM(c.say), 0)
    FROM canta c
    INNER JOIN esyalar e ON e.id = c.esya_id
    WHERE c.user_id = :user_id
      AND c.say > 0
      AND c.geyimde = 0
      AND e.tip != 'mecun'
");

$stmt_canta_sayi->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$canta_sayi = (int)$stmt_canta_sayi->fetchColumn();

$canta_doludur = ($canta_sayi >= $canta_tutumu);

/* ==========================================================
   MÜKAFAT SİSTEMİ
   ========================================================== */

if ($vuruldu && $qalib) {

    if (
        !isset(
            $_SESSION['bigcastle_reward']
            [$mecun_doyus_key]
        )
    ) {

        /* ==================================================
           95% BOŞ / 5% MÜKAFAT
           ================================================== */

        $reward_chance = rand(1, 100);

        /* ==================================================
           BOŞ - 95%
           ================================================== */

        if ($reward_chance <= 0) {

            $_SESSION['bigcastle_reward']
            [$mecun_doyus_key] = array(

                'type' => 'empty',

                'has_reward' => false

            );

        }

        /* ==================================================
           MÜKAFAT - 5%
           ================================================== */

        else {

            /*
             * 5% mükafatın içində:
             *
             * 1 = ƏŞYA
             * 2 = MƏCUN
             *
             * 50% / 50%
             */

            $reward_type = 1;


            /* ==================================================
               ƏŞYA
               ================================================== */

            if ($reward_type === 1) {

                /*
                 * ÇANTA DOLUDURSA ƏŞYA VERİLMİR
                 */

                if ($canta_doludur) {

                    $_SESSION['bigcastle_reward']
                    [$mecun_doyus_key] = array(

                        'type' => 'empty',

                        'has_reward' => false,

                        'canta_doludur' => true

                    );

                }

                elseif (!empty($esyalar)) {

                    /*
                     * RANDOM ƏŞYA
                     */
/*
 * RANDOM ƏŞYA
 * Ametist Gürz (339, 349) mobdan düşmür
 */

do {

    $random_esya_index =
        array_rand($esyalar);

    $random_esya =
        $esyalar[$random_esya_index];

    $esya_id =
        (int)$random_esya['id'];

} while (
    in_array($esya_id, [339, 349], true)
);



                    /*
                     * MOBUN SƏVİYYƏSİ OYUNÇUDAN KİÇİKDİRSƏ
                     * ƏŞYA VERİLMİR
                     */

                    if (
                        $bot_seviyye <
                        (int)$user['oyuncunun_seviyyesi']
                    ) {

                        $_SESSION['bigcastle_reward']
                        [$mecun_doyus_key] = array(

                            'type' => 'empty',

                            'has_reward' => false,

                            'seviyye_kicik_mob' => true

                        );

                    }

                    else {

                        /* ==========================================
                           ZƏRBƏ
                           ========================================== */

                        $esya_min_zerbe =
                            (int)$random_esya['min_zerbe'];

                        $esya_min_zerbe_araliq =
                            (int)$random_esya['min_zerbe_araliq'];

                        $random_min_zerbe = rand(
                            $esya_min_zerbe,
                            $esya_min_zerbe_araliq
                        );


                        $esya_max_zerbe =
                            (int)$random_esya['max_zerbe'];

                        $esya_max_zerbe_araliq =
                            (int)$random_esya['max_zerbe_araliq'];

                        $random_max_zerbe = rand(
                            $esya_max_zerbe,
                            $esya_max_zerbe_araliq
                        );


                        /* ==========================================
                           CAN
                           ========================================== */

                        $esya_can =
                            (int)$random_esya['can'];

                        $esya_max_can =
                            (int)$random_esya['max_can'];

                        $random_can = rand(
                            $esya_can,
                            $esya_max_can
                        );


                        /* ==========================================
                           MÜDAFİƏ
                           ========================================== */

                        $esya_mudafie =
                            (int)$random_esya['mudafie'];

                        $esya_max_mudafie =
                            (int)$random_esya['max_mudafie'];

                        $random_mudafie = rand(
                            $esya_mudafie,
                            $esya_max_mudafie
                        );


                        /* ==========================================
                           KRİT / ANTI-KRİT / UVOROT
                           ========================================== */

                        $esya_max_krit =
                            (int)$random_esya['max_krit'];

                        $esya_max_anti_krit =
                            (int)$random_esya['max_anti_krit'];

                        $esya_max_uvorot =
                            (int)$random_esya['max_uvorot'];

                        $esya_max_anti_uvorot =
                            (int)$random_esya['max_anti_uvorot'];


                        $random_max_4 = min(
                            $esya_max_krit,
                            $esya_max_anti_krit,
                            $esya_max_uvorot,
                            $esya_max_anti_uvorot
                        );


                        if ($random_max_4 < 0) {

                            $random_max_4 = 0;

                        }


                        $random_4_parametr =
                            rand(0, $random_max_4);


                        $random_krit =
                            $random_4_parametr;

                        $random_anti_krit =
                            $random_4_parametr;

                        $random_uvorot =
                            $random_4_parametr;

                        $random_anti_uvorot =
                            $random_4_parametr;

$random_krit_faiz = random_int(0, 24);
$random_anti_krit_faiz = random_int(0, 24);
$random_uvorot_faiz = random_int(0, 24);
$random_anti_uvorot_faiz = random_int(0, 24);


                        /* ==========================================
                           CANTAYA ƏŞYA ƏLAVƏ ET
                           ========================================== */

                        $stmt_elave = $pdo->prepare("
                            INSERT INTO canta
                            (
                                user_id,
                                esya_id,
                                say,
                                geyimde,
                                son_daxil_olma_vaxti,
                                random_min_zerbe,
                                random_max_zerbe,
                                random_can,
                                random_max_can,
                                random_mudafie,
                                random_max_mudafie,
                                random_krit,
                                random_anti_krit,
                                random_uvorot,
                                random_anti_uvorot,
                                random_krit_faiz,
                                random_anti_krit_faiz,
                                random_uvorot_faiz,
                                random_anti_uvorot_faiz
                            )
                            VALUES
                            (
                                :user_id,
                                :esya_id,
                                1,
                                0,
                                :son_daxil_olma_vaxti,
                                :random_min_zerbe,
                                :random_max_zerbe,
                                :random_can,
                                :random_max_can,
                                :random_mudafie,
                                :random_max_mudafie,
                                :random_krit,
                                :random_anti_krit,
                                :random_uvorot,
                                :random_anti_uvorot,

                                 :random_krit_faiz,
                                 :random_anti_krit_faiz,
                                 :random_uvorot_faiz,
                                 :random_anti_uvorot_faiz
                            )
                        ");


                        $stmt_elave->execute([

                            ':user_id' =>
                                (int)$_SESSION['user_id'],

                            ':esya_id' =>
                                $esya_id,

                            ':son_daxil_olma_vaxti' =>
                                time(),

                            ':random_min_zerbe' =>
                                $random_min_zerbe,

                            ':random_max_zerbe' =>
                                $random_max_zerbe,

                            ':random_can' =>
                                $random_can,

                            ':random_max_can' =>
                                $esya_max_can,

                            ':random_mudafie' =>
                                $random_mudafie,

                            ':random_max_mudafie' =>
                                $esya_max_mudafie,

                            ':random_krit' =>
                                $random_krit,

                            ':random_anti_krit' =>
                                $random_anti_krit,

                            ':random_uvorot' =>
                                $random_uvorot,

                            ':random_anti_uvorot' =>
                                $random_anti_uvorot,

                                ':random_krit_faiz' =>
    $random_krit_faiz,

':random_anti_krit_faiz' =>
    $random_anti_krit_faiz,

':random_uvorot_faiz' =>
    $random_uvorot_faiz,

':random_anti_uvorot_faiz' =>
    $random_anti_uvorot_faiz

                        ]);


                        /* ==========================================
                           ƏŞYA MÜKAFATINI SESSION-A YAZ
                           ========================================== */

                        $_SESSION['bigcastle_reward']
                        [$mecun_doyus_key] = array(

                            'type' => 'esya',

                            'has_reward' => true,

                            'id' => $esya_id,

                            'name' =>
                                $random_esya['ad'],

                            'image' =>
                                $random_esya['img']

                        );

                    }

                }

                else {

                    $_SESSION['bigcastle_reward']
                    [$mecun_doyus_key] = array(

                        'type' => 'empty',

                        'has_reward' => false

                    );

                }

            }


            /* ==================================================
               MƏCUN
               ================================================== */

            else {

                /*
                 * MƏCUNLAR BOŞDURSA XƏTA VERMƏSİN
                 */

                if (empty($mecunlar)) {

                    $_SESSION['bigcastle_reward']
                    [$mecun_doyus_key] = array(

                        'type' => 'empty',

                        'has_reward' => false

                    );

                }

                else {

                    /*
                     * RANDOM MƏCUN
                     */

                    $random_index =
                        array_rand($mecunlar);

                    $random_mecun =
                        $mecunlar[$random_index];


                    $stmt_mecun = $pdo->prepare("
                        SELECT id
                        FROM esyalar
                        WHERE ad = :ad
                        AND aktiv = 1
                        LIMIT 1
                    ");


                    $stmt_mecun->execute([

                        ':ad' =>
                            $random_mecun['ad']

                    ]);


                    $mecun_esya =
                        $stmt_mecun->fetch(PDO::FETCH_ASSOC);


                    if ($mecun_esya) {

                        $mecun_esya_id =
                            (int)$mecun_esya['id'];


                        /*
                         * OYUNÇUNUN ÇANTASINDA BU MƏCUN VARMI?
                         */

                        $stmt_canta = $pdo->prepare("
                            SELECT id
                            FROM canta
                            WHERE user_id = :user_id
                            AND esya_id = :esya_id
                            LIMIT 1
                        ");


                        $stmt_canta->execute([

                            ':user_id' =>
                                (int)$_SESSION['user_id'],

                            ':esya_id' =>
                                $mecun_esya_id

                        ]);


                        $canta_mecun =
                            $stmt_canta->fetch(PDO::FETCH_ASSOC);


                        /*
                         * VARSA SAYINI +1 ET
                         */

                        if ($canta_mecun) {

                            $stmt_artir = $pdo->prepare("
                                UPDATE canta
                                SET say = say + 1
                                WHERE id = :id
                            ");


                            $stmt_artir->execute([

                                ':id' =>
                                    (int)$canta_mecun['id']

                            ]);

                        }

                        /*
                         * YOXDURSA YENİ ƏLAVƏ ET
                         */

                        else {

                            $stmt_elave = $pdo->prepare("
                                INSERT INTO canta
                                (
                                    user_id,
                                    esya_id,
                                    say,
                                    geyimde
                                )
                                VALUES
                                (
                                    :user_id,
                                    :esya_id,
                                    1,
                                    0
                                )
                            ");


                            $stmt_elave->execute([

                                ':user_id' =>
                                    (int)$_SESSION['user_id'],

                                ':esya_id' =>
                                    $mecun_esya_id

                            ]);

                        }


                        /*
                         * MƏCUN MÜKAFATINI SESSION-A YAZ
                         */

                        $_SESSION['bigcastle_reward']
                        [$mecun_doyus_key] = array(

                            'type' => 'mecun',

                            'has_reward' => true,

                            'name' =>
                                $random_mecun['ad'],

                            'image' =>
                                $random_mecun['img']

                        );

                    }

                    else {

                        $_SESSION['bigcastle_reward']
                        [$mecun_doyus_key] = array(

                            'type' => 'empty',

                            'has_reward' => false

                        );

                    }

                }

            }

        }

    }

}

?>
<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL">

<meta
name="keywords"
content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar"
>

<meta
name="description"
content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры"
>

<link rel="stylesheet" href="css.css">

<meta
    content="text/html; charset=utf-8"
    http-equiv="content-type"
>

<meta
    name="viewport"
    content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"
>

<title>MOB | QANLI EFSANE</title>

</head>

<body>

<div class="main" style="
    width:580px;
    max-width:580px;
    min-width:0;
    margin:0 auto;
    box-sizing:border-box;
    overflow:hidden;
    word-wrap:break-word;
    overflow-wrap:anywhere;
">


<div class="main">

<?php if ($enerji_bitib && !$vuruldu) { ?>




    <div class="main" style="word-wrap:break-word;">



<b> Sizin enerjiniz bitib.Enerjiniz olmadigi halda doyushe bilmezsiz.</b><br> Qeyd: Her bir deqiqe erzinde size 1 enerji verilir<br>

    

    </div>


</div>

<?php } else { ?>



<?php
/*
==========================================================
QALİBİN MÜKAFAT SƏHİFƏSİ
==========================================================
Yalnız Əldə etdikləriniz düyməsinə basıldıqda açılır.
*/
?>

<?php if ($qalib && isset($_GET['qalib']) && $_GET['qalib'] === 'ok') { ?>


<br>

<div class="center">

    <div class="block_line">

     <span class="green" style="color:#55AE3A !important;">

    <b style="color:#55AE3A !important;">Siz Qalib Geldiz!</b>

</span>
    </div>

</div>

<br>

<div class="point-line"></div>


<img src="img/tec.png" alt=" ">

<span class="grey">

    Tecrübe:

<?php if ($vip_status === 1) { ?>

    <img src="img/vip.png" alt=" ">

    <b>
        <span class="green">
            +<?php echo (int)$verilecek_tecrube; ?>
        </span>
    </b>

<?php } else { ?>

    +<?php echo (int)$verilecek_tecrube; ?>

<?php } ?>


</span>

<br>

<img src="img/coin.png" alt=" ">

<span class="grey">

    Qızıl: +<?php echo (int)$bot_qizil; ?>

</span>

<br>

<div class="point-line"></div>


<?php

$reward = null;

if (
    isset(
        $_SESSION['bigcastle_reward']
        [$mecun_doyus_key]
    )
) {

    $reward =
        $_SESSION['bigcastle_reward']
        [$mecun_doyus_key];

}

?>


<?php if (
    $reward &&
    isset($reward['type']) &&
    $reward['type'] === 'mecun' &&
    isset($reward['has_reward']) &&
    $reward['has_reward'] === true
) { ?>


<div class="battle_log">

    <div
        style="
            display:inline-flex;
            align-items:center;
            max-width:100%;
            box-sizing:border-box;
        "
    >

        <img
            src="<?php
                echo htmlspecialchars(
                    $reward['image'],
                    ENT_QUOTES,
                    'UTF-8'
                );
            ?>"
            alt=""
            style="
                width:40px;
                height:40px;
                object-fit:contain;
                display:block;
                margin-right:5px;
            "
        >

        <div>

            <b>

                Təbriklər Siz Mecun Tapdınız:

            </b>

            <br>

            <a href="canta.php">

                <?php
                echo htmlspecialchars(
                    $reward['name'],
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </a>

            <br>

            <span>

                Mecun Çantanıza Göndərildi.

            </span>

        </div>

    </div>

</div>


<?php } elseif (
    $reward &&
    isset($reward['type']) &&
    $reward['type'] === 'esya' &&
    isset($reward['has_reward']) &&
    $reward['has_reward'] === true
) { ?>


<div class="battle_log">

    <div
        style="
            display:inline-flex;
            align-items:center;
            max-width:100%;
            box-sizing:border-box;
        "
    >

        <img
            src="<?php
                echo htmlspecialchars(
                    $reward['image'],
                    ENT_QUOTES,
                    'UTF-8'
                );
            ?>"
            alt=""
            style="
                width:40px;
                height:40px;
                object-fit:contain;
                display:block;
                margin-right:5px;
            "
        >

        <div>

            <b>

                Təbriklər Siz Esya Tapdınız:

            </b>

            <br>

            <a href="chantam.php?go=eshya">

                <?php
                echo htmlspecialchars(
                    $reward['name'],
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </a>

            <br>

            <span>

                Esya Çantanıza Göndərildi.

            </span>

        </div>

    </div>

</div>


<?php } else { ?>

<div class="battle_log">

    <?php if ($bot_seviyye < (int)$user['oyuncunun_seviyyesi']) { ?>

    Öz mərhələnizdən kiçik moblara qalib gəldikdə sizə əşya verilmir.

    <?php } elseif (
        $reward &&
        isset($reward['canta_doludur']) &&
        $reward['canta_doludur'] === true
    ) { ?>

        <u>Sizin çantanız dolub hər hansısa əşyanı satmalısınız</u>

    <?php } else { ?>

        Siz heçbir əşya elde etmediniz!

    <?php } ?>

    <br>

</div>

<?php } ?>

<div class="menu">

    <br>

<?php if ($is_big_castle) { ?>

    <li>

        <a href="kordinat1.php?go=deyish&amp;kordinat=<?php echo (int)$kordinat; ?>">

            <img 
                src="img/go_next.png"
                alt=" "
            >

            Big Castle

        </a>

    </li>

<?php } elseif (!$is_vahsi_mob) { ?>

    <li>

        <a href="kordinat.php?go=qala&amp;semt=ireli&amp;kordinat=<?php echo (int)$kordinat; ?>">

            <img
                src="img/go_next.png"
                alt=" "
            >

            İreli

        </a>

    </li>

    <li>

        <a href="kordinat.php?go=qala&amp;semt=geri&amp;kordinat=<?php echo (int)$kordinat; ?>">

            <img
                src="img/go_back.png"
                alt=" "
            >

            Geri

        </a>

    </li>

    <br>

<?php } ?>


    <li>

        <a href="menu.php?">

            Ana sehife

        </a>

    </li>

</div>


<?php

/*
==========================================================
BURADA QALİB MÜKAFAT SƏHİFƏSİ BİTİR
==========================================================
*/

} elseif ($bot_can <= 0) {

?>

<br>

<div class="center">

    <div class="block_line">

        <span class="green">
            Siz Qalib Geldiz!
        </span>

    </div>

</div>

<br>
<br>

<?php if ($vuruldu) { ?>

<?php if ($def_edildi || $vurulan_zerbe <= 0) { ?>

Siz vurdunuz

<b>
    <?php
    echo htmlspecialchars(
        $hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
</b>

reqib zerbeni def etdi

<br>

<?php } elseif ($krit_oldu) { ?>

Siz

<b>
    <span style="color:#ff0000;">
        <?php echo (int)$vurulan_zerbe; ?> (Krit)
    </span>
</b>

zerbe vurdunuz

<b>
    <?php
    echo htmlspecialchars(
        $hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
</b>

<br>

<?php } else { ?>

Siz

<b>
    <?php echo (int)$vurulan_zerbe; ?>
</b>

zerbe vurdunuz

<b>
    <?php
    echo htmlspecialchars(
        $hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
</b>

<br>

<?php } ?>


<div class="battle_log">

Sizin canınız:<div style="
    display:inline-block;
    position:relative;
    width:100px;
    height:12px;
    vertical-align:middle;
    background:#ff0000;
    overflow:hidden;
">

    <img
        src="img/can.png"
        alt=""
        style="
            position:absolute;
            left:0;
            top:0;
            height:12px;
            width:<?php
                echo ($menim_can_max > 0)
                    ? ($menim_can / $menim_can_max * 100)
                    : 0;
            ?>%;
        "
    >

</div>

<br>

</div>


<?php if ($bot_def_edildi || $bot_vurdugu_zerbe <= 0) { ?>

Reqib vurdu

<b>
    <?php
    echo htmlspecialchars(
        $bot_hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
</b>

siz zerbeni def etdiniz

<br>

<?php } elseif ($bot_krit_oldu) { ?>

Reqib

<b>
    <span style="color:#ff0000;">
        <?php echo (int)$bot_vurdugu_zerbe; ?> (Krit)
    </span>
</b>

zerbe vurdu

<b>
    <?php
    echo htmlspecialchars(
        $bot_hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
</b>

<br>

<?php } else { ?>

Reqib

<b>
    <?php echo (int)$bot_vurdugu_zerbe; ?>
</b>

zerbe vurdu

<b>
    <?php
    echo htmlspecialchars(
        $bot_hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
</b>

<br>

<?php } ?>


<div class="battle_log">

Reqibin canı:<div style="
    display:inline-block;
    position:relative;
    width:100px;
    height:12px;
    vertical-align:middle;
    background:#ff0000;
    overflow:hidden;
">

    <img
        src="img/can.png"
        alt=""
        style="
            position:absolute;
            left:0;
            top:0;
            height:12px;
            width:<?php
                echo ($bot_max_can > 0)
                    ? ($bot_can / $bot_max_can * 100)
                    : 0;
            ?>%;
        "
    >

</div>

<br>

</div>

<?php } ?>


<div class="menu">

    <hr>

    <li>

        <a href="bot_fig.php?qalib=ok&amp;semt=ireli&amp;kordinat=<?php echo (int)$kordinat; ?>&amp;uid=<?php echo (int)$bot['id']; ?>&amp;lis=401847954&amp;doyus_id=<?php echo urlencode($mecun_doyus_key); ?><?php echo $is_big_castle ? '&amp;qala=bigcastle' : ''; ?><?php echo $is_vahsi_mob ? '&amp;vahsi=1' : ''; ?>">

            <img
                src="muxtelif/okey.png"
                alt=" "
            >Elde etdikleriniz

        </a>
    </li>
<hr>
</div>


<?php

/*
==========================================================
MƏĞLUBİYYƏT
==========================================================
*/

} elseif ($meglub) {

?>

<br>

<div class="center">

    <div class="block_line">

        <span style="color:#ff0000 !important;">

    <b style="color:#ff0000 !important;">Siz Meğlub Oldunuz!</b>

</span>
    </div>

</div>

<br><br>




<?php if ($vuruldu) { ?>


<?php if ($def_edildi || $vurulan_zerbe <= 0) { ?>

Siz vurdunuz

<b>

    <?php
    echo htmlspecialchars(
        $hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

reqib zerbeni def etdi

<br>

<?php } elseif ($krit_oldu) { ?>

Siz

<b>

    <span style="color:#ff0000;">

        <?php echo (int)$vurulan_zerbe; ?> (Krit)

    </span>

</b>

zerbe vurdunuz

<b>

    <?php
    echo htmlspecialchars(
        $hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

<br>

<?php } else { ?>

Siz

<b>

    <?php echo (int)$vurulan_zerbe; ?>

</b>

zerbe vurdunuz

<b>

    <?php
    echo htmlspecialchars(
        $hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

<br>

<?php } ?>





<div class="battle_log">

Sizin canınız:

<div style="
    display:inline-block;
    position:relative;
    width:100px;
    height:12px;
    vertical-align:middle;
    background:#ff0000;
    overflow:hidden;
">

    <img
        src="img/can.png"
        alt=""
        style="
            position:absolute;
            left:0;
            top:0;
            height:12px;
            width:<?php
                echo ($menim_can_max > 0)
                    ? ($menim_can / $menim_can_max * 100)
                    : 0;
            ?>%;
        "
    >

</div>

<br>

</div>


<?php if ($bot_def_edildi || $bot_vurdugu_zerbe <= 0) { ?>

Reqib vurdu

<b>

    <?php
    echo htmlspecialchars(
        $bot_hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

siz zerbeni def etdiniz

<br>

<?php } elseif ($bot_krit_oldu) { ?>

Reqib

<b>

    <span style="color:#ff0000;">

        <?php echo (int)$bot_vurdugu_zerbe; ?> (Krit)

    </span>

</b>

zerbe vurdu

<b>

    <?php
    echo htmlspecialchars(
        $bot_hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

<br>

<?php } else { ?>

Reqib

<b>

    <?php echo (int)$bot_vurdugu_zerbe; ?>

</b>

zerbe vurdu

<b>

    <?php
    echo htmlspecialchars(
        $bot_hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

<br>

<?php } ?>





<div class="battle_log">

Reqibin canı:

<div style="
    display:inline-block;
    position:relative;
    width:100px;
    height:12px;
    vertical-align:middle;
    background:#ff0000;
    overflow:hidden;
">

    <img
        src="img/can.png"
        alt=""
        style="
            position:absolute;
            left:0;
            top:0;
            height:12px;
            width:<?php
                echo ($bot_max_can > 0)
                    ? ($bot_can / $bot_max_can * 100)
                    : 0;
            ?>%;
        "
    >

</div>

<br>

</div>


<?php } ?>


<div class="menu">

    <br>


<?php if ($is_big_castle) { ?>

    <li>

        <a href="kordinat1.php?go=deyis">

            <img
                src="img/go_next.png"
                alt=" "
            >

            Big Castle

        </a>

    </li>


<?php } elseif (!$is_vahsi_mob) { ?>

    <li>

        <a href="kordinat.php?go=qala&amp;semt=ireli&amp;kordinat=<?php echo (int)$kordinat; ?>">

            <img
                src="img/go_next.png"
                alt=" "
            >

            İreli

        </a>

    </li>


    <li>

        <a href="kordinat.php?go=qala&amp;semt=geri&amp;kordinat=<?php echo (int)$kordinat; ?>">

            <img
                src="img/go_back.png"
                alt=" "
            >

            Geri

        </a>

    </li>

    <br>

<?php } ?>


    <li>

        <a href="menu.php?">

            Ana sehife

        </a>

    </li>


    

</div>


<?php


/*
==========================================================
NORMAL DÖYÜŞ
==========================================================
*/

} else {

?>

<br>

<!-- ======================================================
     DÖYÜŞ LOGU
     ====================================================== -->

<?php if ($vuruldu) { ?>


<?php if ($def_edildi || $vurulan_zerbe <= 0) { ?>

Siz vurdunuz

<b>

    <?php
    echo htmlspecialchars(
        $hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

reqib zerbeni def etdi

<br>


<?php } elseif ($krit_oldu) { ?>


Siz

<b>

    <span style="color:#ff0000;">

        <?php echo (int)$vurulan_zerbe; ?> (Krit)

    </span>

</b>

zerbe vurdunuz

<b>

    <?php
    echo htmlspecialchars(
        $hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

<br>


<?php } else { ?>


Siz

<b>

    <?php echo (int)$vurulan_zerbe; ?>

</b>

zerbe vurdunuz

<b>

    <?php
    echo htmlspecialchars(
        $hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

<br>


<?php } ?>


<?php if ($bot_def_edildi || $bot_vurdugu_zerbe <= 0) { ?>


Reqib vurdu

<b>

    <?php
    echo htmlspecialchars(
        $bot_hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

siz zerbeni def etdiniz

<br>


<?php } elseif ($bot_krit_oldu) { ?>


Reqib

<b>

    <span style="color:#ff0000;">

        <?php echo (int)$bot_vurdugu_zerbe; ?> (Krit)

    </span>

</b>

zerbe vurdu

<b>

    <?php
    echo htmlspecialchars(
        $bot_hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

<br>


<?php } else { ?>


Reqib

<b>

    <?php echo (int)$bot_vurdugu_zerbe; ?>

</b>

zerbe vurdu

<b>

    <?php
    echo htmlspecialchars(
        $bot_hucum_yazi,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</b>

<br>


<?php } ?>

<?php } ?>


<br>

<!-- ======================================================
     OYUNÇU
     ====================================================== -->

<div class="battle_log">

<b>

<a href="infoforce.php?uid=<?php echo (int)$_SESSION['user_id']; ?>">

    <?php
    echo htmlspecialchars(
        $user_login,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

</a>

[<?php echo (int)$user['oyuncunun_seviyyesi']; ?>]
</b>

(<?php echo (int)$menim_can; ?>/
<?php echo (int)$menim_can_max; ?>)



</div>


<small>

<b>VS</b>

</small>

<br>


<!-- ======================================================
     BOT
     ====================================================== -->

<div class="battle_log">

<b>

    <?php
    echo htmlspecialchars(
        $bot_ad,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>

    [<?php echo (int)$bot_seviyye; ?>]

</b>

(

<?php echo (int)$bot_can; ?>

/

<?php echo (int)$bot_max_can; ?>

)

</div>


<span class="dark-brown">

    Raund: <?php echo (int)$raund; ?>

</span>

<br>


<!-- ======================================================
     DÖYÜŞ FORMU
     ====================================================== -->

<form
    method="post"

    action="bot_fig.php?go=vurdum&amp;semt=ireli&amp;kordinat=<?php echo (int)$kordinat; ?>&amp;uid=<?php echo (int)$bot['id']; ?>&amp;lis=235222552&amp;doyus_id=<?php echo urlencode($mecun_doyus_key); ?><?php echo $is_big_castle ? '&amp;qala=bigcastle' : ''; ?><?php echo $is_vahsi_mob ? '&amp;vahsi=1' : ''; ?>"

    style="
        max-width:100%;
        box-sizing:border-box;
        overflow:hidden;
    "
>


<b>

    Hucum:

</b>

<br>


<select name="hucum">

    <option value="0">Başdan</option>

    <option value="1">Sineden</option>

    <option value="2">Gövdədən</option>

    <option value="3">Ayaqdan</option>

</select>

<br>


<b>

    Müdafie:

</b>

<br>


<select name="mudafie">

    <option value="0">Baş ve Sine</option>

    <option value="1">Sine ve Gövde</option>

    <option value="2">Gövde ve Ayaq</option>

    <option value="3">Ayaq ve Baş</option>

</select>

<br>


<input
    type="hidden"
    name="action"
    value="save"
>


<button
    type="submit"
    style="
        position:relative;
        border:0;
        background:none;
        padding:0;
        margin:0;
        width:68px;
        height:30px;
        line-height:0;
        cursor:pointer;
    "
>

    <img
        src="img/style2/button_smallest.gif"
        alt=""
        style="
            width:68px;
            height:30px;
            display:block;
        "
    >

    <span
        style="
            position:absolute;
            top:50%;
            left:50%;
            transform:translate(-50%,-50%);
            color:#fff;
            font-weight:bold;
            font-size:12px;
            text-shadow:1px 1px 2px #000;
            white-space:nowrap;
            line-height:normal;
        "
    >

        Vur

    </span>

</button>

<br>

<hr>


<a
    href="bot_fig.php?go=mecun_ic&amp;semt=ireli&amp;uid=<?php echo (int)$bot['id']; ?>&amp;nov=100&amp;lis=235222552<?php echo $is_big_castle ? '&amp;qala=bigcastle' : ''; ?><?php echo $is_vahsi_mob ? '&amp;vahsi=1' : ''; ?>"
>

    Can Mecunu 20%

</a>

<br>


<small>

    <i>

        Qalib geldiyiniz halda tecrübe,
        Qızıl ve eşya qazanmaq şansınız var.

    </i>

</small>

<br>


</form>

<?php } ?>

<?php } ?>


</div>

</body>

</html>
