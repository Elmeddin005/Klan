<?php

session_start();

require_once "config.php";
require_once "user_data.php";
require_once "guc_parametrləri.php";
$kordinat = isset($_GET['kordinat']) ? (int)$_GET['kordinat'] : 1;

if ($kordinat < 1) {
    $kordinat = 1;
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
/* =====================================================
   İSTİFADƏÇİ ADI
   ===================================================== */

$stmt_user = $pdo->prepare("
    SELECT login, oyuncunun_seviyyesi
    FROM users
    WHERE id = :user_id
    LIMIT 1
");

$stmt_user->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit('İstifadəçi tapılmadı.');
}

$user_login = $user['login'];
$player_level = (int)$user['oyuncunun_seviyyesi'];
/* =====================================================
   ENERJİ YOXLAMASI
   ===================================================== */

$stmt_enerji = $pdo->prepare("
    SELECT enerjı
    FROM users
    WHERE id = :user_id
    LIMIT 1
");

$stmt_enerji->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$enerji = (int)$stmt_enerji->fetchColumn();

if ($enerji <= 0) {
    header(
        'Location: bot_fig.php?enerji_bitib=1' .
        (isset($_GET['uid']) ? '&uid=' . (int)$_GET['uid'] : '') .
        (isset($_GET['vahsi']) ? '&vahsi=1' : '') .
        (isset($_GET['qala']) && $_GET['qala'] === 'bigcastle' ? '&qala=bigcastle' : '') .
        (isset($_GET['kordinat']) ? '&kordinat=' . (int)$_GET['kordinat'] : '') .
        (isset($_GET['semt']) ? '&semt=' . urlencode($_GET['semt']) : '')
    );
    exit;
}


/* =====================================================
   BIG CASTLE MOBU — OYUNÇUNUN LEVELİNƏ UYĞUN
   ===================================================== */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'gonder' &&
    isset($_GET['qala']) &&
    $_GET['qala'] === 'bigcastle'
) {

    /* Oyunçunun səviyyəsini götür */
    $stmt_level = $pdo->prepare("
        SELECT oyuncunun_seviyyesi
        FROM users
        WHERE id = :user_id
        LIMIT 1
    ");

  $stmt_level->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

    $player_level = (int)$stmt_level->fetchColumn();

    if ($player_level <= 0) {
        exit('Oyunçu səviyyəsi tapılmadı.');
    }


    /* Oyunçunun səviyyəsinə uyğun təsadüfi mob */
    $stmt_mob = $pdo->prepare("
        SELECT id
        FROM botlar
        WHERE seviyye = :seviyye
        ORDER BY RAND()
        LIMIT 1
    ");

    $stmt_mob->execute([
        ':seviyye' => $player_level
    ]);

    $bot_id = (int)$stmt_mob->fetchColumn();

    if ($bot_id <= 0) {
        exit(
            'Bu səviyyəyə uyğun mob tapılmadı. Level: ' .
            $player_level
        );
    }


 /* Mob ID-ni döyüş səhifəsinə göndər */
$kordinat = isset($_GET['kordinat']) 
    ? (int)$_GET['kordinat'] 
    : 1;

$semt = isset($_GET['semt'])
    ? $_GET['semt']
    : 'ireli';

/* Big Castle koordinatını və istiqaməti yadda saxla */
$_SESSION['bigcastle_kordinat'] = $kordinat;
$_SESSION['bigcastle_semt'] = $semt;

header(
    'Location: bot_fig.php' .
    '?uid=' . $bot_id .
    '&qala=bigcastle' .
    '&kordinat=' . $kordinat .
    '&semt=' . urlencode($semt)
);

exit;
}
$is_big_castle = (
    isset($_GET['qala']) &&
    $_GET['qala'] === 'bigcastle'
);
/* ==========================================================
   BIG CASTLE KOORDİNATI
   ========================================================== */

if ($is_big_castle && isset($_GET['kordinat'])) {
    $_SESSION['bigcastle_kordinat'] = (int)$_GET['kordinat'];
}

$bigcastle_kordinat = isset($_SESSION['bigcastle_kordinat']) 
    ? (int)$_SESSION['bigcastle_kordinat'] 
    : 1;
    
$is_vahsi_mob = (
    isset($_GET['vahsi']) &&
    $_GET['vahsi'] == '1'
);


/* =====================================================
   MOB ID
   ===================================================== */

$bot_id = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;

if ($bot_id <= 0) {
    exit('Bot ID tapılmadı.');
}


/* =====================================================
   MOB MYSQL-DAN GƏLİR
   ===================================================== */

$stmt_bot = $pdo->prepare("
    SELECT
        id,
        ad,
        seviyye,
        img,
        can,
        mudafie,
        zerbe,
        krit,
        anti_krit,
        uvarotu,
        anti_uvarotu,
        qizil,
        tecrube
    FROM botlar
    WHERE id = :id
    LIMIT 1
");

$stmt_bot->execute([
    ':id' => $bot_id
]);

$bot = $stmt_bot->fetch(PDO::FETCH_ASSOC);

if (!$bot) {
    exit('Bot Tapılmadı. ID: ' . $bot_id);
}


/* =====================================================
   MOB MƏLUMATLARI
   ===================================================== */

$bot_ad = htmlspecialchars(
    $bot['ad'],
    ENT_QUOTES,
    'UTF-8'
);

$bot_seviyye = (int)$bot['seviyye'];

$bot_can = (int)$bot['can'];

$bot_img = htmlspecialchars(
    $bot['img'],
    ENT_QUOTES,
    'UTF-8'
);

$bot_qizil = (int)$bot['qizil'];

$bot_tecrube = (int)$bot['tecrube'];
/* =====================================================
   OYUNÇUNUN CANI
   ===================================================== */
/* =====================================================
   OYUNÇUNUN PARAMETRLƏRİ
   İLKİN + OYUNÇU PARAMETRLƏRİ + GÜC BONUSU + ƏŞYA
   ===================================================== */

/* -----------------------------------------------------
   OYUNÇU PARAMETRLƏRİ
----------------------------------------------------- */

$stmt_oyuncu = $pdo->prepare("
  SELECT
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
    min_zerbe,
    max_zerbe,
    zerbe_faiz
FROM oyuncu_parametrleri
WHERE user_id = :user_id
LIMIT 1

");

$stmt_oyuncu->execute([
    ':user_id' => (int)$_SESSION['user_id']
]);

$oyuncu = $stmt_oyuncu->fetch(PDO::FETCH_ASSOC);

if (!$oyuncu) {
    $oyuncu = [
        'can' => 0,
        'can_faiz' => 0,
        'mudafie' => 0,
        'krit' => 0,
        'krit_faiz' => 0,
        'anti_krit' => 0,
        'anti_krit_faiz' => 0,
        'uvorot' => 0,
        'uvorot_faiz' => 0,
        'anti_uvorot' => 0,
        'anti_uvorot_faiz' => 0,
        'min_zerbe' => 0,
        'max_zerbe' => 0,
        'zerbe_faiz' => 0
    ];
}


/* -----------------------------------------------------
   GÜC BONUSLARI
----------------------------------------------------- */

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
    ':user_id' => (int)$_SESSION['user_id']
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


/* -----------------------------------------------------
   GEYİNİLMİŞ ƏŞYALARIN BONUSLARI
----------------------------------------------------- */

/* =========================================================
   OYUNÇUNUN GEYİNDİYİ ƏŞYALAR
   ========================================================= */

$stmt_esyalar = $pdo->prepare("
    SELECT
        c.id,
        c.esya_id,
        c.say,
        c.geyimde,
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
    ':user_id' => (int)$_SESSION['user_id']
]);

$esyalar = $stmt_esyalar->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   ƏŞYALARDAN FAİZ BONUSLARI
   ========================================================= */

$esya_krit_faiz = 0;
$esya_anti_krit_faiz = 0;
$esya_uvorot_faiz = 0;
$esya_anti_uvorot_faiz = 0;

foreach ($esyalar as $esya) {

    $esya_krit_faiz += (int)($esya['krit_faiz'] ?? 0);

    $esya_anti_krit_faiz +=
        (int)($esya['anti_krit_faiz'] ?? 0);

    $esya_uvorot_faiz +=
        (int)($esya['uvorot_faiz'] ?? 0);

    $esya_anti_uvorot_faiz +=
        (int)($esya['anti_uvorot_faiz'] ?? 0);
}



/* =====================================================
   YEKUN ZƏRBƏ
   ===================================================== */

$zerbe_1 =
    (int)$ilkin_zerbe_min +
    (int)$oyuncu['min_zerbe'] +
    (int)$guc_bonus['zerbe'];

$zerbe_2 =
    (int)$ilkin_zerbe_max +
    (int)$oyuncu['max_zerbe'] +
    (int)$guc_bonus['zerbe'];

$menim_min_zerbe = min($zerbe_1, $zerbe_2);
$menim_max_zerbe = max($zerbe_1, $zerbe_2);


/* =====================================================
   CAN
   ===================================================== */

$menim_can =
    (int)$ilkin_can +
    (int)$oyuncu['can'] +
    (int)$guc_bonus['can'];


/* =====================================================
   MÜDAFİƏ
   ===================================================== */

$menim_mudafie =
    (int)$ilkin_mudafie +
    (int)$oyuncu['mudafie'] +
    (int)$guc_bonus['mudafie'];


/* =====================================================
   KRİT ƏSAS
   ===================================================== */

$menim_krit_esas =
    (int)$ilkin_krit +
    (int)$oyuncu['krit'] +
    (int)$guc_bonus['krit'];


/* =====================================================
   KRİT FAİZİ
   ===================================================== */

$menim_krit_faiz =
    (int)$oyuncu['krit_faiz'] +
    (int)$esya_krit_faiz;


/* =====================================================
   YEKUN KRİT
   ===================================================== */

$menim_krit =
    $menim_krit_esas +
    (
        $menim_krit_esas *
        $menim_krit_faiz /
        100
    );

$menim_krit = (int)round($menim_krit);


/* =====================================================
   ANTİ KRİT ƏSAS
   ===================================================== */

$menim_anti_krit_esas =
    (int)$ilkin_antikrit +
    (int)$oyuncu['anti_krit'] +
    (int)$guc_bonus['anti_krit'];


/* =====================================================
   ANTİ KRİT FAİZİ
   ===================================================== */

$menim_anti_krit_faiz =
    (int)$oyuncu['anti_krit_faiz'] +
    (int)$esya_anti_krit_faiz;


/* =====================================================
   YEKUN ANTİ KRİT
   ===================================================== */

$menim_anti_krit =
    $menim_anti_krit_esas +
    (
        $menim_anti_krit_esas *
        $menim_anti_krit_faiz /
        100
    );

$menim_anti_krit = (int)round($menim_anti_krit);


/* =====================================================
   UVOROT ƏSAS
   ===================================================== */

$menim_uvorot_esas =
    (int)$ilkin_uvorot +
    (int)$oyuncu['uvorot'] +
    (int)$guc_bonus['uvorot'];


/* =====================================================
   UVOROT FAİZİ
   ===================================================== */

$menim_uvorot_faiz =
    (int)$oyuncu['uvorot_faiz'];


/* =====================================================
   YEKUN UVOROT
   ===================================================== */

$menim_uvorot =
    $menim_uvorot_esas +
    (
        $menim_uvorot_esas *
        $menim_uvorot_faiz /
        100
    );

$menim_uvorot = (int)round($menim_uvorot);


/* =====================================================
   ANTİ UVOROT ƏSAS
   ===================================================== */

$menim_anti_uvorot_esas =
    (int)$ilkin_antiuvorot +
    (int)$oyuncu['anti_uvorot'] +
    (int)$guc_bonus['anti_uvorot'];


/* =====================================================
   ANTİ UVOROT FAİZİ
   ===================================================== */

$menim_anti_uvorot_faiz =
    (int)$oyuncu['anti_uvorot_faiz'];


/* =====================================================
   YEKUN ANTİ UVOROT
   ===================================================== */

$menim_anti_uvorot =
    $menim_anti_uvorot_esas +
    (
        $menim_anti_uvorot_esas *
        $menim_anti_uvorot_faiz /
        100
    );

$menim_anti_uvorot = (int)round($menim_anti_uvorot);


/* =====================================================
   14-CÜ LEVEL ÜÇÜN RƏNG
   ===================================================== */

$bot_style = '';

if ($bot_seviyye === 14) {

    if ($bot['ad'] === 'Green-Aligator') {

        $bot_style =
            'mix-blend-mode:multiply; filter:hue-rotate(90deg) saturate(2);';

    } elseif ($bot['ad'] === 'Wild-Aligator') {

        $bot_style =
            'mix-blend-mode:multiply; filter:hue-rotate(315deg) saturate(6) sepia(1);';

    } elseif ($bot['ad'] === 'Gray-Aligator') {

        $bot_style =
            'mix-blend-mode:multiply;';

    }
}

?>

<html>

<head>

<meta name="robots" content="ALL">

<meta
    name="keywords"
    content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar"
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


<body
style="
margin:0 !important;
padding:0 !important;
width:100% !important;
max-width:none !important;
text-align:left !important;
display:flex !important;
flex-direction:column !important;
align-items:center !important;
"
>


<div
class="main"
style="
word-wrap:break-word;
width:580px !important;
max-width:580px !important;
min-width:580px !important;
margin:0 auto !important;
padding:0 !important;
float:none !important;
box-sizing:border-box !important;
overflow:hidden !important;
"
>


<br>


<div class="battle_log">

<b>

<a href="infoforce.php?uid=<?php echo (int)$_SESSION['user_id']; ?>">

    <?php echo htmlspecialchars($user_login, ENT_QUOTES, 'UTF-8'); ?>

</a>

[<?php echo $player_level; ?>]

</b>

(<?php echo $menim_can; ?>/<?php echo $menim_can; ?>)

<br>

</div>


<small>

<b>VS</b>

</small>

<br>


<!-- =====================================================
     MYSQL-DAN MOB
     ===================================================== -->

<div class="battle_log">

<b>

<?php echo $bot_ad; ?>

[<?php echo $bot_seviyye; ?>]

</b>

(<?php echo $bot_can; ?>/<?php echo $bot_can; ?>)

<br>

</div>


<span class="dark-brown">

Raund: 1

</span>


<form
method="post"
action="bot_fig.php?go=vurdum&amp;semt=ireli&amp;kordinat=<?php echo (int)$kordinat; ?>&amp;uid=<?php echo $bot_id; ?>&amp;lis=235222552<?php echo $is_big_castle ? '&amp;qala=bigcastle' : ''; ?><?php echo $is_vahsi_mob ? '&amp;vahsi=1' : ''; ?>"
>


<b>

Hucum:

</b>

<br>


<select name="hucum">

<option value="0">
Başdan
</option>

<option value="1">
Sineden
</option>

<option value="2">
Gövdeden
</option>

<option value="3">
Ayaqdan
</option>

</select>


<br>


<b>

Müdafie:

</b>

<br>


<select name="mudafie">

<option value="0">
Baş ve Sine
</option>

<option value="1">
Sine ve Gövde
</option>

<option value="2">
Gövde ve Ayaq
</option>

<option value="3">
Ayaq ve Baş
</option>

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
href="bot_fig.php?go=mecun_ic&amp;semt=ireli&amp;kordinat=<?php echo (int)$kordinat; ?>&amp;uid=<?php echo $bot_id; ?>&amp;nov=100&amp;lis=235222552<?php echo $is_big_castle ? '&amp;qala=bigcastle' : ''; ?><?php echo $is_vahsi_mob ? '&amp;vahsi=1' : ''; ?>"
>


Can Mecunu 20%

</a>


<br>


<small>

<i>

Qalib geldiyiniz halda tecrübe,Qızıl ve eşya qazanmaq şansınız var.

</i>

</small>


<br>


</form>


</div>


</body>

</html>