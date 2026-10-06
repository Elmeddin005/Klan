<?php

session_start();

require_once "config.php";
require_once "user_data.php";
require_once "guc_parametrləri.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];



/* =====================================================
   BAXILAN İSTİFADƏÇİNİN ID-SİNİ GÖTÜR
===================================================== */

$uid = isset($_GET['uid']) 
    ? (int)$_GET['uid'] 
    : 0;

if ($uid <= 0) {
    exit('Oyunçu tapılmadı.');
}

/* =====================================================
   DÖYÜŞ STATİSTİKALARINI BAZADAN GÖTÜR
===================================================== */

$stmt_doyus_stat = $pdo->prepare("
    SELECT
        qelebeler,
        meglubiyyetler,
        hec_heceler,
        cem_doyusler,
        rank
    FROM oyuncu_doyus_statistikasi
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_doyus_stat->execute([
    ':user_id' => $uid
]);

$doyus_stat = $stmt_doyus_stat->fetch(PDO::FETCH_ASSOC);


/* Statistikası yoxdursa 0 göstər */
$rank           = (int)($doyus_stat['rank'] ?? 0);
$qelebeler      = (int)($doyus_stat['qelebeler'] ?? 0);
$meglubiyyetler = (int)($doyus_stat['meglubiyyetler'] ?? 0);
$hec_heceler    = (int)($doyus_stat['hec_heceler'] ?? 0);
$cem_doyusler   = (int)($doyus_stat['cem_doyusler'] ?? 0);



/* =========================================================
   AÇILAN PROFİLİN ID-Sİ
========================================================= */

$my_id = (int)$_SESSION['user_id'];

/* =========================================================
   AÇILAN PROFİLİN ID-Sİ
========================================================= */

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;

if ($uid <= 0) {
    exit('Oyunçu tapılmadı.');
}





/* =========================================================
   DUEL STATUSU — BÜTÜN İSTİFADƏÇİLƏR ÜÇÜN ÜMUMİ
========================================================= */

$duel_status = null;
$duel_data = null;

/*
=========================================================
AKTİV DUELİ TAPIRIQ

Artıq $my_id və ya $uid ilə məhdudlaşdırmırıq.
Saytda aktiv olan son duel hamıya görünəcək.
=========================================================
*/

$stmt_duel = $pdo->prepare("
    SELECT
        id,
        oyuncu1_id,
        oyuncu2_id,
        yaradilis_tarixi,
        qebul_edildi,
        qalib_id
    FROM duel
    WHERE qalib_id IS NULL
    ORDER BY id DESC
    LIMIT 1
");

$stmt_duel->execute();

$duel_data = $stmt_duel->fetch(PDO::FETCH_ASSOC);


/*
=========================================================
180 SANİYƏLİK DUEL TƏKLİFİ
=========================================================

YALNIZ QƏBUL EDİLMƏMİŞ DUEL 180 SANİYƏDƏN
SONRA LƏĞV OLUNUR.

QƏBUL EDİLMİŞ DUELƏ TOXUNMUR.
=========================================================
*/

if ($duel_data) {

    $created_time = strtotime(
        $duel_data['yaradilis_tarixi']
    );

    if (
        (int)$duel_data['qebul_edildi'] === 0 &&
        $created_time > 0 &&
        time() - $created_time >= 180
    ) {

        $stmt_duel_delete = $pdo->prepare("
            DELETE FROM duel
            WHERE id = :id
              AND qebul_edildi = 0
              AND qalib_id IS NULL
            LIMIT 1
        ");

        $stmt_duel_delete->execute([
            ':id' => (int)$duel_data['id']
        ]);

        $duel_data = null;
    }
}


/*
=========================================================
DUEL STATUSU
=========================================================
*/

if ($duel_data) {

    if ((int)$duel_data['qebul_edildi'] === 0) {

        $duel_status = 'gozleyir';

    } elseif ((int)$duel_data['qebul_edildi'] === 1) {

        $duel_status = 'qebul_edilib';
    }
}


/* =========================================================
   DOSTLUQ STATUSU
========================================================= */

$dostluq_status = null;

if ($uid != $my_id) {

    $stmt_dostluq_status = $pdo->prepare("
        SELECT status, gonderen_id, alan_id
        FROM dostluq
        WHERE
        (
            gonderen_id = :my_id
            AND alan_id = :uid
        )
        OR
        (
            gonderen_id = :uid
            AND alan_id = :my_id
        )
        LIMIT 1
    ");

    $stmt_dostluq_status->execute([
        ':my_id' => $my_id,
        ':uid' => $uid
    ]);

    $dostluq_status = $stmt_dostluq_status->fetch(PDO::FETCH_ASSOC);
}


/* =========================================================
   AÇILAN PROFİLİN MƏLUMATLARI
========================================================= */

$stmt_profile = $pdo->prepare("
    SELECT *
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_profile->execute([
    ':id' => $uid
]);

$profile_user = $stmt_profile->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    exit('Oyunçu tapılmadı.');
}
$stmt_avatar = $pdo->prepare("
    SELECT sekil
    FROM avatarlar
    WHERE id = :avatar_id
    LIMIT 1
");

$stmt_avatar->execute([
    ':avatar_id' => (int)($profile_user['avatar_id'] ?? 0)
]);

$profil_avatar = $stmt_avatar->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   PROFİL ADI
========================================================= */

$oyuncu_adi = isset($profile_user['login'])
    ? $profile_user['login']
    : 'Naməlum';


/* =========================================================
   PROFİL MÖVQEYİ
========================================================= */

$movqe = isset($profile_user['movqe'])
    ? (int)$profile_user['movqe']
    : 0;

if ($movqe === 1) {

    $movqe_adi = 'Vampir';
    $movqe_rengi = 'red';

} elseif ($movqe === 2) {

    $movqe_adi = 'İnsan';
    $movqe_rengi = 'green';

} elseif ($movqe === 3) {

    $movqe_adi = 'Neytral';
    $movqe_rengi = 'blue';

} else {

    $movqe_adi = 'Naməlum';
    $movqe_rengi = 'white';
}



/* =========================================================
   AÇILAN PROFİLİN LEVEL-İ
   BÜTÜN PROFİL GÖRÜNTÜSÜ VƏ ƏŞYALAR BUNDAN İSTİFADƏ EDİR
========================================================= */

$profil_seviyye = isset($profile_user['oyuncunun_seviyyesi'])
    ? (int)$profile_user['oyuncunun_seviyyesi']
    : 1;


/* =========================================================
   AÇILAN PROFİLİN TƏCRÜBƏSİ
========================================================= */

$profil_tecrube = isset($profile_user['oyuncunun_tecrubesi'])
    ? (int)$profile_user['oyuncunun_tecrubesi']
    : 0;


/* =========================================================
   PROGRESS
   BURA TOXUNMURUQ.
   HƏMİŞƏ GİRİŞ EDƏN İSTİFADƏÇİNİN ÖZ PROGRESS-İDİR.
========================================================= */

$progress_seviyye = isset($user['oyuncunun_seviyyesi'])
    ? (int)$user['oyuncunun_seviyyesi']
    : 1;

$progress_tecrube = isset($user['oyuncunun_tecrubesi'])
    ? (int)$user['oyuncunun_tecrubesi']
    : 0;


$progress_baslangic = 0;
$progress_bitis = 0;

if (
    isset($seviyye_tecrubeleri) &&
    is_array($seviyye_tecrubeleri) &&
    isset($seviyye_tecrubeleri[$progress_seviyye])
) {

    $progress_baslangic = isset(
        $seviyye_tecrubeleri[$progress_seviyye]['min']
    )
        ? (int)$seviyye_tecrubeleri[$progress_seviyye]['min']
        : 0;

    $progress_bitis = isset(
        $seviyye_tecrubeleri[$progress_seviyye]['max']
    )
        ? (int)$seviyye_tecrubeleri[$progress_seviyye]['max']
        : 0;
}


$progress = 0;

if ($progress_bitis > $progress_baslangic) {

    $progress = (
        ($progress_tecrube - $progress_baslangic) /
        ($progress_bitis - $progress_baslangic)
    ) * 100;
}

$progress = max(0, min(100, $progress));
$progress = round($progress);


/* =========================================================
   PROFİLİN TƏCRÜBƏSİ
========================================================= */

$tecrube = $profil_tecrube;


/* =========================================================
   PROFİL LEVELİNİN MAX TƏCRÜBƏSİ
========================================================= */

$baslangic_tecrubesi = 0;
$bitis_tecrubesi = 0;

if (
    isset($seviyye_tecrubeleri) &&
    is_array($seviyye_tecrubeleri) &&
    isset($seviyye_tecrubeleri[$profil_seviyye])
) {

    $baslangic_tecrubesi = isset(
        $seviyye_tecrubeleri[$profil_seviyye]['min']
    )
        ? (int)$seviyye_tecrubeleri[$profil_seviyye]['min']
        : 0;

    $bitis_tecrubesi = isset(
        $seviyye_tecrubeleri[$profil_seviyye]['max']
    )
        ? (int)$seviyye_tecrubeleri[$profil_seviyye]['max']
        : 0;
}


/* =========================================================
   OXUNMAMIŞ MƏKTUBLAR
   GİRİŞ EDƏN İSTİFADƏÇİYƏ AİDDİR
========================================================= */

$stmt_unread = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE mesaji_alan_nik = :alan
      AND oxundu = 0
");

$stmt_unread->execute([
    ':alan' => $my_id
]);

$unread_count = (int)$stmt_unread->fetchColumn();


/* =========================================================
   PROFİL SAHİBİNİN ƏŞYALARI
   MÜTLƏQ PROFİL LEVELİ İLƏ ÇƏKİLİR
========================================================= */

/*
=========================================================
PROFİL SAHİBİNİN GEYİNDİYİ ƏŞYALAR
=========================================================
*/

/* =========================================================
   OYUNÇUNUN GEYİNDİYİ ƏŞYALAR
   SƏVİYYƏ FİLTRİ YOXDUR.
   YALNIZ canta.geyimde = 1 OLANLAR GÖRÜNÜR.
========================================================= */

$stmt_esyalar = $pdo->prepare("
    SELECT
        c.id,
        c.esya_id,
        c.say,
        c.geyimde,
        e.ad,
        e.img,
        e.tip,
        e.reng,
        e.seviyye,
        e.goruntu_adi,
       e.krit,
e.krit_faiz,
e.anti_krit_faiz,
e.uvorot_faiz,
e.anti_uvorot_faiz,
e.max_krit

    FROM canta c
    INNER JOIN esyalar e
        ON e.id = c.esya_id
    WHERE c.user_id = :user_id
      AND c.say > 0
      AND c.geyimde = 1
    ORDER BY c.id ASC
");

$stmt_esyalar->execute([
    ':user_id' => $uid
]);

$esyalar = $stmt_esyalar->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   BALTA VARSA QILINCI GÖSTƏRMƏ
========================================================= */

$balta_var = false;

foreach ($esyalar as $esya) {

    if ($esya['ad'] === 'Gümüş Balta') {
        $balta_var = true;
        break;
    }
}

if ($balta_var) {

    $esyalar = array_values(
        array_filter(
            $esyalar,
            function ($esya) {
                return $esya['ad'] !== 'Gümüş Qılınc';
            }
        )
    );
}


/* =========================================================
   VIP / HƏDİYYƏ
========================================================= */

$is_vip = ((int)($profile_user['vip'] ?? 0) === 1);

$gifts = [
    'hediyye/10.png',
    'hediyye/6.png',
    'hediyye/20.png',
    'hediyye/7.png',
    'hediyye/104.gif'
];

$gift_count = count($gifts);

$last_three_gifts = array_slice($gifts, -3);

?>
<?php

$param_user_id = $uid;

$stmt_param = $pdo->prepare("
    SELECT
        min_zerbe,
        max_zerbe,
        can,
        can_faiz,
        mudafie,
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

$stmt_param->execute([
    ':user_id' => $uid
]);

$param = $stmt_param->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   PARAMETRLƏRİ İLKİN DƏYƏRLƏRLƏ BAŞLAT
========================================================= */

if (!$param) {
    $param = [];
}

$param['min_zerbe'] = (int)($param['min_zerbe'] ?? 0);

$param['max_zerbe'] = (int)($param['max_zerbe'] ?? 0);

$param['zerbe_faiz'] = (int)($param['zerbe_faiz'] ?? 0);

$param['can'] = (int)($param['can'] ?? 0);

$param['mudafie'] = (int)($param['mudafie'] ?? 0);

$param['krit'] = (int)($param['krit'] ?? 0);

$param['krit_faiz'] = (int)($param['krit_faiz'] ?? 0);

$param['anti_krit'] = (int)($param['anti_krit'] ?? 0);

$param['uvorot'] = (int)($param['uvorot'] ?? 0);

$param['anti_uvorot'] = (int)($param['anti_uvorot'] ?? 0);
$param['can_faiz'] = (int)($param['can_faiz'] ?? 0);

$param['anti_krit_faiz'] = (int)($param['anti_krit_faiz'] ?? 0);

$param['uvorot_faiz'] = (int)($param['uvorot_faiz'] ?? 0);

$param['anti_uvorot_faiz'] = (int)($param['anti_uvorot_faiz'] ?? 0);



/* =========================================================
   GEYİNİLMİŞ ƏŞYALARIN BONUS FAİZLƏRİ
========================================================= */
/* =========================================================
   GEYİNİLMİŞ ƏŞYALARIN FAİZ BONUSLARI
========================================================= */

$esya_krit = 0;
$esya_krit_faiz = 0;
$esya_anti_krit_faiz = 0;
$esya_uvorot_faiz = 0;
$esya_anti_uvorot_faiz = 0;

foreach ($esyalar as $esya) {
$esya_krit += (int)($esya['krit'] ?? 0);
    $esya_krit_faiz += (int)($esya['krit_faiz'] ?? 0);

    $esya_anti_krit_faiz += (int)($esya['anti_krit_faiz'] ?? 0);

    $esya_uvorot_faiz += (int)($esya['uvorot_faiz'] ?? 0);

    $esya_anti_uvorot_faiz += (int)($esya['anti_uvorot_faiz'] ?? 0);
}


/* =========================================================
   OYUNCUNUN DAİMİ GÜC BONUSLARI
========================================================= */

$stmt_guc_bonus = $pdo->prepare("
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

$stmt_guc_bonus->execute([
    ':user_id' => $uid
]);

$guc_bonus = $stmt_guc_bonus->fetch(PDO::FETCH_ASSOC);

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



/* =========================================================
   ZƏRBƏ
   FAİZ HESABLANMIR
========================================================= */

$zerbe_1 =
    $ilkin_zerbe_min
    + (int)$param['min_zerbe']
    + (int)$guc_bonus['zerbe'];

$zerbe_2 =
    $ilkin_zerbe_max
    + (int)$param['max_zerbe']
    + (int)$guc_bonus['zerbe'];


/* =========================================================
   KİÇİK DƏYƏR MIN, BÖYÜK DƏYƏR MAX OLSUN
========================================================= */

$yekun_min_zerbe = min($zerbe_1, $zerbe_2);
$yekun_max_zerbe = max($zerbe_1, $zerbe_2);



/* =========================================================
   CAN
========================================================= */

$yekun_can =
    $ilkin_can
    + (int)$param['can']
    + (int)$guc_bonus['can'];

/* =========================================================
   MUDAFİƏ
========================================================= */

$yekun_mudafie =
    $ilkin_mudafie
    + (int)$param['mudafie']
    + (int)$guc_bonus['mudafie'];




/* =========================================================
   KRİT
========================================================= */

$yekun_krit_esas =
    $ilkin_krit
    + (int)$param['krit']
    + (int)$guc_bonus['krit'];


/* =========================================================
   OYUNCU PARAMETRİNDƏN GƏLƏN KRİT FAİZİ
========================================================= */

$yekun_krit_faiz =
    (int)$param['krit_faiz']
    + (int)$esya_krit_faiz;



/* =========================================================
   ƏŞYALARDAN GƏLƏN KRİT FAİZİ
========================================================= */

$esya_krit_faiz =
    (int)$esya_krit_faiz;


/* =========================================================
   OYUNCU KRİT FAİZİ
========================================================= */

$yekun_krit =
    $yekun_krit_esas
    + (
        $yekun_krit_esas
        * $yekun_krit_faiz
        / 100
    );

$yekun_krit = (int)round($yekun_krit);



/* =========================================================
   ANTİ KRİT
========================================================= */

$yekun_anti_krit_esas =
    $ilkin_antikrit
    + (int)$param['anti_krit']
    + (int)$guc_bonus['anti_krit'];

$yekun_anti_krit_faiz =
    (int)$param['anti_krit_faiz']
    + (int)$esya_anti_krit_faiz;

$yekun_anti_krit =
    $yekun_anti_krit_esas
    + (
        $yekun_anti_krit_esas
        * $yekun_anti_krit_faiz
        / 100
    );

$yekun_anti_krit = (int)round($yekun_anti_krit);



/* =========================================================
   UVOROT
========================================================= */

$yekun_uvorot_esas =
    $ilkin_uvorot
    + (int)$param['uvorot']
    + (int)$guc_bonus['uvorot'];

$yekun_uvorot_faiz =
    (int)$param['uvorot_faiz'];

$yekun_uvorot =
    $yekun_uvorot_esas
    + (
        $yekun_uvorot_esas
        * $yekun_uvorot_faiz
        / 100
    );

$yekun_uvorot = (int)round($yekun_uvorot);


/* =========================================================
   ANTİ UVOROT
========================================================= */

$yekun_anti_uvorot_esas =
    $ilkin_antiuvorot
    + (int)$param['anti_uvorot']
    + (int)$guc_bonus['anti_uvorot'];

$yekun_anti_uvorot_faiz =
    (int)$param['anti_uvorot_faiz'];

$yekun_anti_uvorot =
    $yekun_anti_uvorot_esas
    + (
        $yekun_anti_uvorot_esas
        * $yekun_anti_uvorot_faiz
        / 100
    );

$yekun_anti_uvorot = (int)round($yekun_anti_uvorot);

    ?>
<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL" />

<meta name="keywords" content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar" />

<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu." />

<link rel="stylesheet" href="css.css">

<meta
    content="text/html; charset=utf-8"
    http-equiv="content-type"
/>

<meta
    name="viewport"
    content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"
/>

<script>
function goGeri() {
    window.history.back();
}
</script>

<title>
KLAN.AZ | <?php echo htmlspecialchars($oyuncu_adi, ENT_QUOTES, 'UTF-8'); ?>
</title>

</head>

<body>

<div
    class="main"
    style="word-wrap:break-word;"
>

<div id="header">


<a href="menu.php">
    <img src="img/logo.png">
</a>


<div class="icons"></div>


<div class=main_foot>

<div class=grey>

<img src="img/coin.png" title="Qızıl" alt=""/>
<?php echo (int)$user['qızıl']; ?>

<img src="img/brill.png" title="Brilliant" alt=""/>
<?php echo (int)$user['brılyant']; ?>

<img src="img/energy.png" title="Enerji" alt=""/>
<?php echo (int)$user['enerjı']; ?>
<?php if ($unread_count > 0) { ?>
    <a href="arxiv.php?go=goster">
        <img src="img/mektub.gif" title="Məktub" alt="Məktub"/>
    </a>
    (<?php echo $unread_count; ?>)
<?php } ?>
<?php if ($dostluq_sayi > 0) { ?>
    <a href="dostlar.php">
        <img
            src="muxtelif/dost_pilus.png"
            title="Dost"
            alt="Dost"
        />
    </a>
    (<?php echo $dostluq_sayi; ?>)
<?php } ?>


</div>

</div>

</div>

<div class="space"></div>

<div
    style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<!-- =====================================================
     PROFİL SAHİBİNİN PROGRESS-İ
===================================================== -->

<div class="fl b exp_count">

<div style="margin-top:-2px;">

<span style="color:#ff3333">

<b>
<?php echo $progress; ?>%
</b>

</span>

</div>

</div>


<div class="experience">

<div class="exp_bg">

<div class="exp_left fl"></div>

<div class="exp_right fr"></div>

<div
    style="width:<?php echo $progress; ?>%;height:10px;"
>

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>

</div>

</div>


<div
    style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<div class="info">

<br />
<br />


<table
    align="center"
    border="0"
    cellpadding="0"
    cellspacing="0"
>

<tr>


<!-- =====================================================
     SOL TƏRƏF
===================================================== -->

<td valign="middle">

<?php

$sol_esyalar = [];

foreach ($esyalar as $esya) {

    $tip = trim($esya['tip']);

    if ($tip === 'debilqe') {

        $sol_esyalar['debilqe'] = $esya;

    } elseif ($tip === 'zireh') {

        $sol_esyalar['zireh'] = $esya;

    } elseif (
        $tip === 'qilinc' &&
        !isset($sol_esyalar['silah'])
    ) {

        $sol_esyalar['silah'] = $esya;

    } elseif (
        $tip === 'balta' &&
        !isset($sol_esyalar['silah'])
    ) {

        $sol_esyalar['silah'] = $esya;

    } elseif ($tip === 'uzuk') {

        $sol_esyalar['uzuk'] = $esya;
    }
}


foreach (
    ['debilqe', 'zireh', 'silah', 'uzuk']
    as $tip
) {

    if (!isset($sol_esyalar[$tip])) {
        continue;
    }

    $esya = $sol_esyalar[$tip];

    $img_yol = implode(
        '/',
        array_map(
            'rawurlencode',
            explode('/', $esya['img'])
        )
    );

?>

<img
    src="<?php echo htmlspecialchars(
        $img_yol,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
    width="40"
    height="40"
    alt="<?php echo htmlspecialchars(
        $esya['ad'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
/>

<br />

<?php

}

?>

</td>
<!-- =====================================================
     ORTA DÖYÜŞÇÜ
===================================================== -->

<td valign="middle">

<?php

/* =====================================================
   DÖYÜŞÇÜNÜN LEVELİ
===================================================== */

/* =====================================================
   DÖYÜŞÇÜNÜN LEVELİ
   PROFİLİN LEVELİNDƏN GƏLİR
===================================================== */

$esya_level = 1;

foreach ($esyalar as $esya) {

    $tip = trim($esya['tip']);

    if (in_array($tip, [
        'debilqe',
        'zireh',
        'balta',
        'kemer',
        'elcek',
        'qilinc',
        'ayaqqabi'
    ], true)) {

        $esya_level = (int)$esya['seviyye'];
        break;
    }
}

$doyuscu_level = max(1, min(14, (int)$profil_seviyye));
/* =====================================================
   GEYİNİLMİŞ ƏŞYALARI TAPIRIQ
===================================================== */

$geyinmis = [];

$balta_var = false;

foreach ($esyalar as $esya) {

    $tip = trim($esya['tip']);

    if ($tip === 'balta') {
        $balta_var = true;
    }
}

foreach ($esyalar as $esya) {

    $tip = trim($esya['tip']);

    if ($tip === 'qilinc' && $balta_var) {
        continue;
    }


    if (in_array($tip, [
        'debilqe',
        'zireh',
        'balta',
        'kemer',
        'elcek',
        'qilinc',
        'ayaqqabi'
    ], true)) {

        $geyinmis[$tip] = true;
    }
}


/* =====================================================
   ƏŞYA YOXDURSA
===================================================== */

if (empty($geyinmis)) {

    $goruntu_yolu = 'img/esya_goruntusu/esyasiz.jpg';

} else {


    /* =================================================
       KOMBİNASİYA SIRASI
    ================================================= */

    $goruntu_sirasi = [
        'debilqe',
        'zireh',
        'balta',
        'kemer',
        'elcek',
        'qilinc',
        'ayaqqabi'
    ];

    $kombinasiya_hisseleri = [];

    foreach ($goruntu_sirasi as $tip) {

        if (isset($geyinmis[$tip])) {

            $kombinasiya_hisseleri[] = $tip;
        }
    }


    /* =================================================
       KOMBİNASİYA ADI
    ================================================= */

    $kombinasiya = implode(
        '_',
        $kombinasiya_hisseleri
    );

    /* =================================================
       SQL-DƏN GÖRÜNTÜNÜ TAPIRIQ
    ================================================= */



/* =================================================
   GEYİNİLƏN ƏŞYALARIN LEVELİ
   BÜTÜN GEYİNİLƏN ƏŞYALARIN LEVELİNƏ BAXIRIQ
================================================= */

$esya_seviyyesi = 1;

foreach ($esyalar as $esya) {

    if (
        isset($esya['seviyye']) &&
        (int)$esya['seviyye'] > $esya_seviyyesi
    ) {

        $esya_seviyyesi = (int)$esya['seviyye'];
    }
}

$esya_seviyyesi = max(
    1,
    min(14, $esya_seviyyesi)
);




/* =================================================
   ƏŞYANIN LEVELİNƏ UYĞUN QOVLUQ
================================================= */

$doyuscu_qovluq =
    'img/esya_goruntusu/'
    . $esya_seviyyesi
    . 'level/';


/* =================================================
   SQL-DƏN KOMBİNASİYANI TAPIRIQ
   SQL-DƏ YALNIZ 1 LEVEL KOMBİNASİYALARI VAR
================================================= */

$stmt_goruntu = $pdo->prepare("
    SELECT img
    FROM esya_goruntuleri
    WHERE kombinasiya = :kombinasiya
      AND seviyye = 1
    LIMIT 1
");

$stmt_goruntu->execute([
    ':kombinasiya' => $kombinasiya
]);

$goruntu = $stmt_goruntu->fetch(PDO::FETCH_ASSOC);

    /* =================================================
       GÖRÜNTÜ TAPILDI
    ================================================= */

    if ($goruntu && !empty($goruntu['img'])) {

        $goruntu_yolu =
            $doyuscu_qovluq .
            trim($goruntu['img']);

    } else {

        $goruntu_yolu =
            'img/esya_goruntusu/esyasiz.jpg';
    }
}

?>

<div style="position:relative; width:100px; height:160px;">

    <img
        src="<?php echo htmlspecialchars(
            $goruntu_yolu,
            ENT_QUOTES,
            'UTF-8'
        ); ?>"
        width="100"
        height="160"
        alt="Döyüşçü"
    />

    <?php if ($profil_avatar) { ?>

    <img
    src="<?php echo htmlspecialchars(
        $profil_avatar['sekil'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
    width="100"
    height="160"
    alt="Avatar"
    style="position:absolute; top:0; left:0;"
/>

    <?php } ?>

</div>

</td>
<!-- =====================================================
     SAĞ TƏRƏF
===================================================== -->

<td valign="middle">

<?php

$sag_tiplər = [
    'amulet',
    'kemer',
    'elcek',
    'ayaqqabi'
];

$sag_esyalar = [];

foreach ($esyalar as $esya) {

    $tip = trim($esya['tip']);

    if (
        in_array(
            $tip,
            $sag_tiplər,
            true
        )
    ) {

        $sag_esyalar[$tip] = $esya;
    }
}


foreach ($sag_tiplər as $tip) {

    if (!isset($sag_esyalar[$tip])) {
        continue;
    }

    $esya = $sag_esyalar[$tip];

    $img_yol = implode(
        '/',
        array_map(
            'rawurlencode',
            explode('/', $esya['img'])
        )
    );

?>

<img
    src="<?php echo htmlspecialchars(
        $img_yol,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
    width="40"
    height="40"
    alt="<?php echo htmlspecialchars(
        $esya['ad'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
/>

<br />

<?php

}

?>

</td>

</tr>

</table>


<!-- =====================================================
     OYUNÇUNUN ADI + SƏVİYYƏSİ
===================================================== -->

<span
style="
color:<?php echo $movqe_rengi; ?>;
<?php if ((int)($profile_user['vip'] ?? 0) === 1) { ?>
text-decoration:underline;
text-shadow:1px 1px 1px #888;
<?php } ?>
"
>

<?php echo htmlspecialchars(
    $oyuncu_adi,
    ENT_QUOTES,
    'UTF-8'
); ?>

[<?php echo (int)$profil_seviyye; ?>]

</span>


<?php if ($uid != $my_id): ?>

<a href="arxiv.php?uid=<?php echo $uid; ?>">

<img
    src="muxtelif/send.png"
    alt="Mesaj"
/>

Mesaj

</a>

<?php endif; ?>


<br />

<div class="point-line"></div>

<p>
<b>
<img
    src="muxtelif/star.png"
    alt=""
/>
Esas Melumatlar
</b>
</p>

<div class="point-line"></div>

<div class="battle_log">

<p>


<?php if ($is_vip): ?>

<img
    src="muxtelif/v.png"
    alt="vip"
/>

Vip istifadeçi:

<img
    src="img/vip.png"
    alt="vip"
/>

<br />

<?php endif; ?>


<img
    src="muxtelif/elave.png"
    alt="zerbe"
/>

Oyunda mövgeyi:

<font color="<?php echo $movqe_rengi; ?>">

<?php echo htmlspecialchars(
    $movqe_adi,
    ENT_QUOTES,
    'UTF-8'
); ?>

</font>

<br />


<img
    src="img/158.gif"
    alt="YaLQuZaQ05"
/>

Üzv olduqu klan:

<a href="qrup.php?go=info&amp;idi=8">

<span class="dark-blue">
YaLQuZaQ05
</span>

</a>

<br />


<img
    src="muxtelif/elave.png"
    alt="zerbe"
/>

Status:

<span class="dark-blue">
<?php

$stmt_qalxan = $pdo->prepare("
    SELECT qalxan_statusu, qalxan_vaxti
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_qalxan->execute([
    ':id' => $uid
]);

$qalxan_data = $stmt_qalxan->fetch(PDO::FETCH_ASSOC);

$qalxan_statusu = (int)($qalxan_data['qalxan_statusu'] ?? 0);
$qalxan_vaxti = (int)($qalxan_data['qalxan_vaxti'] ?? 0);

/*
=========================================================
QALXAN VAXTI BİTİBSƏ
=========================================================
*/

if (
    $qalxan_statusu === 1 &&
    $qalxan_vaxti > 0 &&
    $qalxan_vaxti <= time()
) {

    $stmt_qalxan_bitdi = $pdo->prepare("
        UPDATE users
        SET qalxan_statusu = 0,
            qalxan_vaxti = 0,
            qalxan_terk_vaxti = 0
        WHERE id = :id
    ");

    $stmt_qalxan_bitdi->execute([
        ':id' => $uid
    ]);

    $qalxan_statusu = 0;
}


/*
=========================================================
STATUS
=========================================================
*/

if ($qalxan_statusu === 1) {
    echo 'Qalxan';
} else {
    echo 'Qılınc';
}

?>
</span>

<br />


<!-- =====================================================
     MƏRHƏLƏ — PROFİL SAHİBİNİN SƏVİYYƏSİ
===================================================== -->

<img
    src="muxtelif/vezife.png"
    alt="zerbe"
/>

Merhele:


<span class="dark-blue">
    <?php echo $profil_seviyye; ?>
</span>


<br />


<!-- =====================================================
     TƏCRÜBƏ — PROFİL SAHİBİNİN TƏCRÜBƏSİ
===================================================== -->

<img
    src="img/tec.png"
    alt="zerbe"
/>

Tecrübe:

<span class="dark-blue">

<?php echo $tecrube; ?>

(<?php echo $bitis_tecrubesi; ?>)

</span>

<br />


</p>

</div>


<div class="point-line"></div>

<p>

<b>

<img
    src="muxtelif/star.png"
    alt=""
/>

Parametrleri

</b>

</p>

<div class="point-line"></div>

<div class="battle_log">

<p>

<img 
    src="muxtelif/udar.png" 
    alt="zerbe" 
/> 

<font style="color:#ff66cc">
Zerbe:
<?php echo $yekun_min_zerbe; ?>
-
<?php echo $yekun_max_zerbe; ?>
| (<?php echo (int)$param['zerbe_faiz']; ?>%)
</font>

<br />

<img 
    src="muxtelif/can.png" 
    alt="can" 
/> 

<font style="color:#FF0000"> 
Can:
<?php echo $yekun_can; ?>
| (<?php echo (int)$param['can_faiz']; ?>%)
</font> 

<br />


<img 
    src="muxtelif/zashita.png" 
    alt="mudafie" 
/> 

<font style="color:#4466ff"> 
Mudafie: <?php echo $yekun_mudafie; ?> | (0%)
</font> 

<br />


<img 
    src="muxtelif/krit.png" 
    alt="krit" 
/> 

Krit: 

<font style="color:#4466ff"> 
<?php echo $yekun_krit; ?> | (<?php echo $yekun_krit_faiz; ?>%)
</font>


<br />


<img 
    src="muxtelif/antikrit.png" 
    alt="antikrit" 
/> 

Anti Krit: 

<font style="color:#4466ff"> 
<?php echo $yekun_anti_krit; ?> | (<?php echo (int)$param['anti_krit_faiz']; ?>%)
</font> 

<br />


<img 
    src="muxtelif/uvorot.png" 
    alt="uvorot" 
/> 

Uvorot: 

<font style="color:#4466ff"> 
<?php echo $yekun_uvorot; ?> | (<?php echo (int)$param['uvorot_faiz']; ?>%)
</font> 

<br />

<img 
    src="muxtelif/antiuvorot.png" 
    alt="antiuvorot" 
/> 

Anti Uvorot: 

<font style="color:#4466ff"> 
<?php echo $yekun_anti_uvorot; ?> |(<?php echo (int)$param['anti_uvorot_faiz']; ?>%)
</font> 

<br />

</p>

</div>


<!-- =====================================================
     DÖYÜŞ DÜYMƏLƏRİ
===================================================== -->



<?php if ($my_id != $uid): ?>

<div class="battle_log">

<div class="center">

<?php

/*
=========================================================
AKTİV DUELİ YOXLAYIRIQ
=========================================================
*/

$profil_duel = null;

if ($duel_data) {

    $duel_oyuncu1 = (int)$duel_data['oyuncu1_id'];
    $duel_oyuncu2 = (int)$duel_data['oyuncu2_id'];

    /*
    GİRİŞ EDƏN İSTİFADƏÇİ VƏ AÇILAN PROFİL
    EYNİ DUELDƏDİRSƏ
    */
/*
=========================================================
AKTİV DUEL VARSA HAMININ PROFİLİNDƏ GÖSTƏR
=========================================================
*/

if ($duel_data) {

    $profil_duel = $duel_data;
}
}


/*
=========================================================
AKTİV DUEL VAR
=========================================================
*/

if ($profil_duel) {

    $duel_oyuncu1 = (int)$profil_duel['oyuncu1_id'];
    $duel_oyuncu2 = (int)$profil_duel['oyuncu2_id'];


    /*
    =====================================================
    OYUNCU 1
    =====================================================
    */

    $stmt_duel_user1 = $pdo->prepare("
        SELECT
            login,
            oyuncunun_seviyyesi
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_duel_user1->execute([
        ':id' => $duel_oyuncu1
    ]);

    $duel_user1 = $stmt_duel_user1->fetch(PDO::FETCH_ASSOC);

    $duel_user1_adi = $duel_user1['login'] ?? '';

    $duel_user1_seviyye = (int)(
        $duel_user1['oyuncunun_seviyyesi'] ?? 0
    );


    /*
    =====================================================
    OYUNCU 2
    =====================================================
    */

    $stmt_duel_user2 = $pdo->prepare("
        SELECT
            login,
            oyuncunun_seviyyesi
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_duel_user2->execute([
        ':id' => $duel_oyuncu2
    ]);

    $duel_user2 = $stmt_duel_user2->fetch(PDO::FETCH_ASSOC);

    $duel_user2_adi = $duel_user2['login'] ?? '';

    $duel_user2_seviyye = (int)(
        $duel_user2['oyuncunun_seviyyesi'] ?? 0
    );


    /*
    =====================================================
    DUEL HƏLƏ QƏBUL EDİLMƏYİB
    =====================================================
    */

    if ((int)$profil_duel['qebul_edildi'] === 0) {

        /*
        PROFİL SAHİBİNİ SOLDa GÖSTƏR
        */

        if ($uid === $duel_oyuncu1) {

            $oz_adi = $duel_user1_adi;
            $oz_seviyye = $duel_user1_seviyye;

        } else {

            $oz_adi = $duel_user2_adi;
            $oz_seviyye = $duel_user2_seviyye;
        }

        ?>

        <?php echo htmlspecialchars(
            $oz_adi,
            ENT_QUOTES,
            'UTF-8'
        ); ?>

        [<?php echo $oz_seviyye; ?>]

        VS

        []

        <br>

        Hal Hazırda dueldedir

        <br>


    <?php

    /*
    =====================================================
    DUEL QƏBUL EDİLİB
    =====================================================
    */

    } else {

        /*
        PROFİL SAHİBİ HƏMİŞƏ SOLDa OLSUN
        */

        if ($uid === $duel_oyuncu1) {

            $sol_id = $duel_oyuncu1;
            $sol_adi = $duel_user1_adi;
            $sol_seviyye = $duel_user1_seviyye;

            $sag_id = $duel_oyuncu2;
            $sag_adi = $duel_user2_adi;
            $sag_seviyye = $duel_user2_seviyye;

        } else {

            $sol_id = $duel_oyuncu2;
            $sol_adi = $duel_user2_adi;
            $sol_seviyye = $duel_user2_seviyye;

            $sag_id = $duel_oyuncu1;
            $sag_adi = $duel_user1_adi;
            $sag_seviyye = $duel_user1_seviyye;
        }

        ?>

        <a href="profil.php?uid=<?php echo $sol_id; ?>">

            <?php echo htmlspecialchars(
                $sol_adi,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </a>

        [<?php echo $sol_seviyye; ?>]

        VS

        <a href="profil.php?uid=<?php echo $sag_id; ?>">

            <?php echo htmlspecialchars(
                $sag_adi,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </a>

        [<?php echo $sag_seviyye; ?>]

        <br>

        Hal Hazırda dueldedir

        <br>

    <?php

    }

} else {

    ?>


    <!-- =================================================
         AKTİV DUEL YOXDUR
    ================================================== -->

    <form
        action="doyush_gonderildi.php?go=gonder&amp;uid=<?php echo $uid; ?>"
        method="post"
    >

        <input
            type="hidden"
            name="uid"
            value="<?php echo $uid; ?>"
        >

        <input
            type="hidden"
            name="title"
            value="sec711203377"
        >

        <input
            type="hidden"
            name="action"
            value="send"
        >

        <input
            type="submit"
            class="button_big"
            value="Duele devet et"
        >

    </form>


    <form
        action="hucum.php?go=gonder&amp;uid=<?php echo $uid; ?>"
        method="post"
    >

        <input
            type="hidden"
            name="uid"
            value="<?php echo $uid; ?>"
        >

        <input
            type="hidden"
            name="title"
            value="sec711203377"
        >

        <input
            type="hidden"
            name="action"
            value="send"
        >

        <input
            type="submit"
            class="button_big"
            value="Hucum et"
        >

    </form>


<?php

}

?>

   



</div>

</div>

<?php endif; ?>






<div class="point-line"></div>

<p>

<b>

<img
    src="muxtelif/star.png"
    alt=""
/>

Medalları

</b>

</p>

<div class="point-line"></div>


<div class="center">

<a href="medal.php?go=medal_info&medal=1">
<img src="medal/39567.gif" alt="" />
</a>

<a href="medal.php?go=medal_info&medal=2">
<img src="medal/12852.gif" alt="" />
</a>

<a href="medal.php?go=medal_info&medal=3">
<img src="medal/22252.gif" alt="" />
</a>

<a href="medal.php?go=medal_info&medal=4">
<img src="medal/63427.gif" alt="" />
</a>

<a href="medal.php?go=medal_info&medal=5">
<img src="medal/42926.gif" alt="" />
</a>

</div>


<br />

<div class="point-line"></div>

<p>

<b>

<img
    src="muxtelif/star.png"
    alt=""
/>

Anketi

</b>

</p>

<div class="point-line"></div>

<div class="battle_log">

<p>

Adı:
<span class="brown">
<?php echo htmlspecialchars($profile_user['ad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
</span>

<br />

Cinsi:
<span class="brown">
<?php
if ((int)($profile_user['cins'] ?? 0) === 1) {
    echo 'Kişi';
} elseif ((int)($profile_user['cins'] ?? 0) === 0) {
    echo 'Xanım';
} else {
    echo 'Naməlum';
}
?>
</span>

<br />

Doğum Tarixi:
<span class="brown">
<?php
echo htmlspecialchars(
    $profile_user['dogum_tarixi'] ?? '00-00-0000',
    ENT_QUOTES,
    'UTF-8'
);
?>
</span>

<br />

Haqqında:
<span class="brown">
<?php echo htmlspecialchars($profile_user['haqqinda'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
</span>

<br />

</p>

</div>


<!-- =====================================================
     MENYU
===================================================== -->

<div class="menu">

<li>
<a href="qayda_pozuntular.php?uid=<?php echo $uid; ?>">
<img src="muxtelif/wtraf.png" alt="" />
Qayda pozuntuları (0)
</a>
</li>


<li>
<a href="eshya_gonder.php?uid=<?php echo $uid; ?>">
<img src="muxtelif/gonder.png" alt="" />
Eşya gönder
</a>
</li>


<li>

<?php if (!empty($dostluq_mesaji)): ?>

<?php echo $dostluq_mesaji; ?>

<div class="line"></div>

<div class="menu">

<li>
<a href="dostlar.php?yenile=">
<img src="muxtelif/on.gif" alt="">
Dostlar
</a>
</li>

</div>

<?php endif; ?>

<li>



<a href="dostlar.php?mod=add&amp;nk=<?php echo $uid; ?>">
    <img src="muxtelif/dost.png" alt="" />
    Dostluq gönder
</a>


</li>
<li>

<a href="ignor.php?mod=add&amp;nick=<?php echo urlencode($oyuncu_adi); ?>">
    <img src="muxtelif/iqnor.png" alt="" />
    İqnor et
</a>


</li>


<?php if ($gift_count > 0): ?>

<li>



<a href="padarka.php?uid=<?php echo $uid; ?>">

<img 
    src="muxtelif/gifts.gif" 
    alt="" 
/>

Hediyyeleri
(<?php echo $gift_count; ?>)

</a>



</li>

<br />

<div class="center">

<?php foreach ($last_three_gifts as $gift): ?>

<img
    src="<?php echo htmlspecialchars(
        $gift,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
    alt="Hediyye"
/>

<?php endforeach; ?>

</div>

<?php endif; ?>

</div>



<br />


<!-- =====================================================
     GEYİNDİRİLMİŞ ƏŞYALAR
===================================================== -->

<div class="battle_log">

<?php if (empty($esyalar)): ?>

    <font style="color:#656565">
        Heç bir əşya geyinilməyib.
    </font>

<?php else: ?>

<?php

$tip_adlari = [
    'debilqe'  => 'Debilqe',
    'qilinc'   => 'Sag el',
    'balta'    => 'Sag el',
    'amulet'   => 'Amulet',
    'zireh'    => 'Zireh',
    'kemer'    => 'Kemer',
    'elcek'    => 'Elcek',
    'uzuk'     => 'Uzuk',
    'ayaqqabi' => 'Ayaqqabi'
];

foreach ($esyalar as $esya):

    $tip = $esya['tip'];

    $tip_adi = $tip_adlari[$tip] ?? $tip;

    /* RƏNG */

    $reng = '#FFFFFF';

    if (
        $esya['reng'] === 'sari' ||
        $esya['reng'] === 'sarı'
    ) {

        $reng = '#FFD700';

    } elseif (
        $esya['reng'] === 'goy' ||
        $esya['reng'] === 'göy'
    ) {

        $reng = '#0088FF';

    } elseif (
        $esya['reng'] === 'yasil' ||
        $esya['reng'] === 'yaşıl'
    ) {

        $reng = '#00AA00';

    } elseif (
        $esya['reng'] === 'qirmizi' ||
        $esya['reng'] === 'qırmızı'
    ) {

        $reng = '#FF0000';

    } elseif ($esya['reng'] === 'qara') {

        $reng = '#000000';

    }

?>

<font style="color:#656565">

<b>
&#8226;<?php
echo htmlspecialchars(
    $tip_adi,
    ENT_QUOTES,
    'UTF-8'
);
?>:</b>

</font>


<a
    href="chantam.php?go=info&amp;rid=<?php
        echo (int)$esya['id'];
    ?>"
>

<font
    style="color:<?php
        echo htmlspecialchars(
            $reng,
            ENT_QUOTES,
            'UTF-8'
        );
    ?>"
>

<?php

echo htmlspecialchars(
    $esya['ad'],
    ENT_QUOTES,
    'UTF-8'
);

?>

[<?php echo (int)$esya['seviyye']; ?>]

</font>

</a>


<a
    href="chantam.php?go=chixart&amp;idi=<?php
        echo (int)$esya['id'];
    ?>"
>
    [Çixart]
</a>


<hr>

<?php endforeach; ?>

<?php endif; ?>

</div>

</div>

<div class="line"></div>


<div class="menu">

<li>



<a href="chantam.php?">

<img 
    src="muxtelif/sandiq.png" 
    alt="" 
/>

Menim çantam

</a>


</li>

</div>



<div class="line"></div>

<div class="battle_log">

<img
    src="muxtelif/rank.png"
    alt=""
/>

Rank:

<span class="dark-blue">
<?php echo $rank; ?>
</span>

<br />


<img
    src="muxtelif/qelebe.png"
    alt=""
/>

Qelebeler:

<span class="dark-blue">
<?php echo $qelebeler; ?>
</span>

<br />


<img
    src="muxtelif/meglub.png"
    alt=""
/>

Meglubiyyetler:

<span class="dark-blue">
<?php echo $meglubiyyetler; ?>
</span>

<br />


<img
    src="muxtelif/hechece.png"
    alt=""
/>

Heç-heçeler:

<span class="dark-blue">
<?php echo $hec_heceler; ?>
</span>

<br />


<img
    src="muxtelif/2xencer.png"
    alt=""
/>

Cemi döyüşler:

<span class="dark-blue">
<?php echo $cem_doyusler; ?>
</span>

</div>


<div class="line"></div>

<?php

/*
|--------------------------------------------------------------------------
| BAXILAN PROFİL SAHİBİNİN AKTİVLİK MƏLUMATLARI
|--------------------------------------------------------------------------
|
| MÜTLƏQ $profile_user istifadə olunur.
| $user = sayta daxil olan şəxsin öz məlumatıdır.
| $profile_user = baxılan profilin sahibidir.
|--------------------------------------------------------------------------
*/

$profil_son_online = isset($profile_user['online_oyuncu_vaxti'])
    ? (int)$profile_user['online_oyuncu_vaxti']
    : 0;

$profil_aktivlik = isset($profile_user['aktivlik_saniye'])
    ? (int)$profile_user['aktivlik_saniye']
    : 0;

$profil_son_online = isset($profile_user['online_oyuncu_vaxti'])
    ? (int)$profile_user['online_oyuncu_vaxti']
    : 0;

$indi = time();

/*
|--------------------------------------------------------------------------
| PROFİL SAHİBİ HAZIRDA ONLINE-DIRSA
|--------------------------------------------------------------------------
|
| Bazadakı aktivliyə son heartbeat-dən keçən saniyələri də əlavə edirik.
| Beləliklə refresh zamanı sayğac geriyə qayıtmır.
|--------------------------------------------------------------------------
*/

$is_online = (
    $profil_son_online > 0 &&
    ($indi - $profil_son_online) <= 180
);

if ($is_online) {

    $son_heartbeatden_kecen =
        max(0, $indi - $profil_son_online);

    /*
     * Heartbeat-in bazaya yazdığı aktivlik
     * + heartbeat-dən bu ana qədər keçən vaxt.
     */
    $profil_aktivlik_goster =
        $profil_aktivlik + $son_heartbeatden_kecen;

} else {

    /*
     * Offline-dırsa artıq heç nə əlavə edilmir.
     * Sayğac dayandığı yerdə qalır.
     */
    $profil_aktivlik_goster =
        $profil_aktivlik;
}

$indi = time();


/*
|--------------------------------------------------------------------------
| PROFİL SAHİBİ HAZIRDA ONLAYNDIR?
|--------------------------------------------------------------------------
|
| heartbeat.php son 180 saniyə ərzində vaxtı yeniləyirsə,
| istifadəçi online hesab olunur.
|--------------------------------------------------------------------------
*/

$is_online = (
    $profil_son_online > 0 &&
    ($indi - $profil_son_online) <= 180
);


/*
|--------------------------------------------------------------------------
| AKTİVLİK MƏTNİ
|--------------------------------------------------------------------------
*/

function aktivlik_metni($saniye)
{
    $saniye = max(0, (int)$saniye);

    $gun = intdiv($saniye, 86400);

    $qalan = $saniye % 86400;

    $saat = intdiv($qalan, 3600);

    $qalan %= 3600;

    $deqiqe = intdiv($qalan, 60);

    $san = $qalan % 60;

    $hisseler = [];

    if ($gun > 0) {
        $hisseler[] = $gun . ' gün';
    }

    if ($saat > 0) {
        $hisseler[] = $saat . ' saat';
    }

    if ($deqiqe > 0) {
        $hisseler[] = $deqiqe . ' dəqiqə';
    }

    if ($san > 0 || empty($hisseler)) {
        $hisseler[] = $san . ' saniyə';
    }

    return implode(', ', $hisseler);
}

?>


<?php if ($is_online) { ?>

<div class="battle_log">

<img
    src="muxtelif/on.gif"
    alt=""
/>

<span class="dark-blue">
Hal hazırda saytdadır
</span>

<br />

<img
    src="muxtelif/on.gif"
    alt=""
/>

<span class="dark-blue">
Aktivliyi:

<span id="profil_aktivlik_saygac">
<?php
echo htmlspecialchars(
    aktivlik_metni($profil_aktivlik),
    ENT_QUOTES,
    'UTF-8'
);
?>
</span>

</span>

<br />

</div>


<?php } else { ?>


<div class="battle_log">

<span class="dark-blue">

Son daxil olma:

<?php

if ($profil_son_online > 0) {

$aylar = [
    1  => 'Yanvar',
    2  => 'Fevral',
    3  => 'Mart',
    4  => 'Aprel',
    5  => 'May',
    6  => 'İyun',
    7  => 'İyul',
    8  => 'Avqust',
    9  => 'Sentyabr',
    10 => 'Oktyabr',
    11 => 'Noyabr',
    12 => 'Dekabr'
];

$gun = date('d', $profil_son_online);
$ay = (int)date('m', $profil_son_online);
$saat = date('H:i', $profil_son_online);

echo $gun . ' ' . $aylar[$ay] . ' ' . $saat;


} else {

    echo "məlum deyil";

}

?>

</span>

<br />

</div>


<?php } ?>



<!-- =====================================================
     MESAJLAR / ADMİN
===================================================== -->

<a href="smsb.php?game_chat=mektublar&amp;id=<?php echo $uid; ?>">
Mektublarını oxu
</a>

<br />


<a href="adminka.php?go=mesaj_bagla&amp;id=<?php echo $uid; ?>&amp;uid=<?php echo $uid; ?>">
Mesajını Bağla
</a>

<br />


<a href="adminka_m.php?go=chat_bagla&amp;id=<?php echo $uid; ?>&amp;uid=<?php echo $uid; ?>">
Çatdan blok et
</a>

<br />


<a href="adminka.php?go=ban&amp;id=<?php echo $uid; ?>&amp;uid=<?php echo $uid; ?>">
Oyundan Ban Et
</a>

<br />


<a href="adminka_m.php?go=haqqinda&amp;id=<?php echo $uid; ?>&amp;uid=<?php echo $uid; ?>">
Haqqında yazılanı sil.
</a>

<br />


<a href="adminka.php?go=panel&amp;id=<?php echo $uid; ?>&amp;uid=<?php echo $uid; ?>">
Şexsi melumatları
</a>

<hr />


<a href="adminka_m.php?go=cix&amp;id=<?php echo $uid; ?>&amp;uid=<?php echo $uid; ?>">
Doyushden cixart
</a>

<hr />

<br />


<div class="menu">

<br />

<li>

<a href="shikayet.php?uid=<?php echo $uid; ?>">

<img
    src="muxtelif/iqnor.png"
    alt=""
/>

Şikayet et

</a>

</li>


<li>

<a href="online.php?">

<img 
    src="muxtelif/on.gif" 
    alt="" 
/>

Online döyüşcüler

</a>


</li>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">

[<b>
<a href="menu.php?">Menu</a>
</b>]

[<b>
<a href="axtar.php?">Axtarış</a>
</b>]

[<a href="forum/mozu2.php?">Forum</a>]

[<a href="shexsi_sehife.php?">Qurğular</a>]


<br />
<br />


<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>


<br />


<a href="index.php?">

Çıxış
(<?php echo htmlspecialchars(
    $user_login,
    ENT_QUOTES,
    'UTF-8'
); ?>)

</a>


<br />
<br />


<a href="menu.php?dil=tr">

Türkce:

<img
    alt="türkce"
    src="http://macera.az/klan/muxtelif/tr.gif"
    title="Türkce"
/>

</a>


<br />

Sciript name: Qanlı efsane(modern version)

<br />


<a
    href="http://klanaz.com/klan/"
    class="xgame.az"
>

&#169; Klanaz.com 2026

</a>


</div>

</div>

</div>

</div>

</div>


</div>
<script>
setInterval(function () {
    fetch('heartbeat.php', {
        method: 'POST',
        cache: 'no-store'
    }).catch(function () {});
}, 10000);
</script>
</body>

</html>

