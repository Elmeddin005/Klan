<?php

session_start();

require_once "config.php";
require_once "user_data.php";

/* =========================================================
   GİRİŞ YOXLAMASI
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];

/* =========================================================
   YENİ MESAJLAR
========================================================= */

$stmt_new_message = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE mesaji_alan_nik = :my_id
      AND oxundu = 0
");

$stmt_new_message->execute([
    ':my_id' => $my_id
]);

$new_message_count = (int)$stmt_new_message->fetchColumn();

/* =========================================================
   İSTİFADƏÇİ MƏLUMATLARI
========================================================= */

$user_id = $user['id'];
$user_login = $user['login'];
$user_ad = $user['ad'];

/* =========================================================
   SƏVİYYƏ
========================================================= */

$stmt_seviyye = $pdo->prepare("
    SELECT oyuncunun_seviyyesi
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_seviyye->execute([
    ':id' => $my_id
]);

$oyuncu_seviyyesi = (int)$stmt_seviyye->fetchColumn();

if ($oyuncu_seviyyesi < 1) {
    $oyuncu_seviyyesi = 1;
}

/* =========================================================
   ONLINE VAXTI
========================================================= */

$stmt_online = $pdo->prepare("
    UPDATE users
    SET online_oyuncu_vaxti = :vaxt
    WHERE id = :id
");

$stmt_online->execute([
    ':vaxt' => time(),
    ':id' => $user_id
]);

/* =========================================================
   ONLINE OYUNCULAR
========================================================= */

$stmt_online_count = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE online_oyuncu_vaxti >= :vaxt
");

$stmt_online_count->execute([
    ':vaxt' => time() - 180
]);

$online_sayi = $stmt_online_count->fetchColumn();

/* =========================================================
   EMOSİYALAR
========================================================= */

$emosiyalar = [

    ['file' => 'bee.gif',      'alt' => 'bee',      'code' => '.bee.'],
    ['file' => 'asqirmaq.gif',        'alt' => 'asqir',        'code' => '.asqir.'],
    ['file' => '555.gif',       'alt' => '555',       'code' => '.555.'],
    ['file' => 'blev2.gif',     'alt' => 'blev',     'code' => '.blev2.'],
    ['file' => 'blink.gif',      'alt' => 'blink',      'code' => '.blink.'],
    ['file' => 'cay.gif',       'alt' => 'cay',       'code' => '.cay.'],
    ['file' => 'deli.gif', 'alt' => 'deli', 'code' => '.deli.'],
    ['file' => 'dil.gif',       'alt' => 'dil',       'code' => '.dil.'],
    ['file' => 'dil2.gif',        'alt' => 'dil2',        'code' => '.dil2.'],
    ['file' => 'esebi.gif',  'alt' => 'esebi',  'code' => '.esebi.'],
    ['file' => 'esnemek.gif',      'alt' => 'esnemek',      'code' => '.esnemek.'],
    ['file' => 'gulmek1.gif',   'alt' => 'gulmek1',   'code' => '.gulmek1.'],
    ['file' => 'gulmek5.gif',    'alt' => 'gulmek5',    'code' => '.gulmek5.'],
    ['file' => 'haha.gif',    'alt' => 'haha',    'code' => '.haha.'],
    ['file' => 'invalid.gif',   'alt' => 'invalid',   'code' => '.invalid.'],
    ['file' => 'Kuku1.gif',       'alt' => 'kuku',       'code' => '.kuku.'],
    ['file' => 'razi.gif',        'alt' => 'razi',        'code' => '.razi.'],
    ['file' => 'Ujas.gif',   'alt' => 'ujas',   'code' => '.ujas.'],
    ['file' => 'Yemek.gif',   'alt' => 'yemek',   'code' => '.yemek.'],
    ['file' => 'yuxu1.gif',   'alt' => 'yuxu',   'code' => '.yuxu1.']

];
/* =========================================================
   GÜLMƏK SMAYLİKLƏRİ
========================================================= */

$gulmek = [
    ['file' => 'g3.gif', 'alt' => 'g3', 'code' => '.g3.'],
    ['file' => 'g5.gif', 'alt' => 'g5', 'code' => '.g5.'],
    ['file' => 'g8.gif', 'alt' => 'g8', 'code' => '.g8.'],
    ['file' => 'g11.gif', 'alt' => 'g11', 'code' => '.g11.'],
    ['file' => 'g12.gif', 'alt' => 'g12', 'code' => '.g12.'],
    ['file' => 'g14.gif', 'alt' => 'g14', 'code' => '.g14.'],
    ['file' => 'g15.gif', 'alt' => 'g15', 'code' => '.g15.'],
    ['file' => 'hihi.gif', 'alt' => 'hihi', 'code' => '.hihi.']
];
/* =========================================================
   ƏSƏB SMAYLİKLƏRİ
========================================================= */

$eseb = [
    ['file' => 'doyus.gif',     'alt' => 'doyus',     'code' => '.doyus.'],
    ['file' => 'h1.gif',    'alt' => 'h1',    'code' => '.h1.'],
    ['file' => 'h4.gif',    'alt' => 'h4',    'code' => '.h4.'],
    ['file' => 'h5.gif',  'alt' => 'h5',  'code' => '.h5.'],
    ['file' => 'h8.gif',      'alt' => 'h8',      'code' => '.h8.'],
    ['file' => 'h9.gif',    'alt' => 'h9',    'code' => '.h9.'],
    ['file' => 'h17.gif',  'alt' => 'h17',  'code' => '.h17.'],
    ['file' => 'pis2.gif',      'alt' => 'pis2',      'code' => '.pis2.']
];
/* =========================================================
   KEFSİZ SMAYLİKLƏRİ
========================================================= */

$kefsiz = [
    ['file' => 'agla.gif', 'alt' => 'agla', 'code' => '.agla.'],
    ['file' => 'sorry.gif', 'alt' => 'sorry', 'code' => '.sorry.']
];
/* =========================================================
   SEVGİ, ÜRƏKLƏR SMAYLİKLƏRİ
========================================================= */

$sevgi = [
    ['file' => 'urek.gif', 'alt' => 'urek', 'code' => '.urek.'],
    ['file' => 'urek1.gif', 'alt' => 'urek1', 'code' => '.urek1.'],
    ['file' => 'opdum.gif', 'alt' => 'opdum', 'code' => '.opdum.'],
    ['file' => 'love.gif', 'alt' => 'love', 'code' => '.love.'],
    ['file' => 'love1.gif', 'alt' => 'love1', 'code' => '.love1.'],
    ['file' => 'love2.gif', 'alt' => 'love2', 'code' => '.love2.'],
    ['file' => 'love3.gif', 'alt' => 'love3', 'code' => '.love3.'],
     ['file' => 'love4.gif', 'alt' => 'love4', 'code' => '.love4.'],
    ['file' => 'love5.gif', 'alt' => 'love5', 'code' => '.love5.'],
     ['file' => 'love6.gif', 'alt' => 'love6', 'code' => '.love6.'],
    ['file' => 'loveme.gif', 'alt' => 'loveme', 'code' => '.loveme.']
];
/* =========================================================
   YUXU SMAYLİKLƏRİ
========================================================= */

$yuxu = [
    ['file' => 'yuxu.gif',  'alt' => 'yuxu',  'code' => '.yuxu.'],
    ['file' => 'laylay.gif', 'alt' => 'laylay', 'code' => '.laylay.'],
    ['file' => 'yuxu3.gif', 'alt' => 'yuxu3', 'code' => '.yuxu3.']
];
/* =========================================================
   ÖPÜŞ SMAYLİKLƏRİ
========================================================= */

$opush = [
    ['file' => 'zaluyu.gif',  'alt' => 'zaluyu',  'code' => '.zaluyu.'],
    ['file' => 'cem.gif', 'alt' => 'cem', 'code' => '.cem.'],
    ['file' => '4mak.gif', 'alt' => '4mak', 'code' => '.4mak.'],
    ['file' => 'kiss4.gif', 'alt' => 'kiss4', 'code' => '.kiss4.'],
    ['file' => 'oblom.gif', 'alt' => 'oblom', 'code' => '.oblom.'],
    ['file' => 'ops.gif', 'alt' => 'ops', 'code' => '.ops.'],
    ['file' => 'ops2.gif', 'alt' => 'ops2', 'code' => '.ops2.']
];
/* =========================================================
   QARIŞIQ SMAYLİKLƏRİ
========================================================= */

$qarisiq = [
    ['file' => 'a128.gif', 'alt' => 'a128', 'code' => '.a128.'],
    ['file' => 'bicaq.gif', 'alt' => 'bicaq', 'code' => '.bicaq.'],
    ['file' => 'dash.gif', 'alt' => 'dash', 'code' => '.dash.'],
    ['file' => 'duel.gif', 'alt' => 'duel', 'code' => '.duel.'],
    ['file' => 'gitar.gif', 'alt' => 'gitar', 'code' => '.gitar.'],
     ['file' => 'kuku.gif', 'alt' => 'kuku', 'code' => '.kuku.'],
    ['file' => 'nn36.gif', 'alt' => 'nn36', 'code' => '.nn36.'],
     ['file' => 'pooh.gif', 'alt' => 'pooh', 'code' => '.;pooh.'],
    ['file' => 'qiz.gif', 'alt' => 'qiz', 'code' => '.qiz.'],
     ['file' => 'qiz1.gif', 'alt' => 'qiz1', 'code' => '.qiz1.'],
    ['file' => 'qiz3.gif', 'alt' => 'qiz3', 'code' => '.qiz3.'],
     ['file' => 'qiz4.gif', 'alt' => 'qiz4', 'code' => '.qiz4.'],
    ['file' => 'qiz5.gif', 'alt' => 'qiz5', 'code' => '.qiz5.'],
     ['file' => 'song.gif', 'alt' => 'song', 'code' => '.song.'],
    ['file' => 'tort.gif', 'alt' => 'tort', 'code' => '.tort.'],
     ['file' => 'yeriyox.gif', 'alt' => 'yeriyox', 'code' => '.yeriyox.']
];

/* =========================================================
   QARIŞIQ SƏHİFƏLƏMƏ
========================================================= */

$qarisiq_per_page = 10;

$qarisiq_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($qarisiq_page < 1) {
    $qarisiq_page = 1;
}

$qarisiq_total = count($qarisiq);

$qarisiq_total_pages = (int)ceil(
    $qarisiq_total / $qarisiq_per_page
);

if ($qarisiq_total_pages < 1) {
    $qarisiq_total_pages = 1;
}

if ($qarisiq_page > $qarisiq_total_pages) {
    $qarisiq_page = $qarisiq_total_pages;
}

$qarisiq_start = ($qarisiq_page - 1) * $qarisiq_per_page;

$qarisiq_current_emojis = array_slice(
    $qarisiq,
    $qarisiq_start,
    $qarisiq_per_page
);

/* =========================================================
   ÖPÜŞ SƏHİFƏLƏMƏ
========================================================= */

$opush_per_page = 10;

$opush_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($opush_page < 1) {
    $opush_page = 1;
}

$opush_total = count($opush);

$opush_total_pages = (int)ceil(
    $opush_total / $opush_per_page
);

if ($opush_total_pages < 1) {
    $opush_total_pages = 1;
}

if ($opush_page > $opush_total_pages) {
    $opush_page = $opush_total_pages;
}

$opush_start = ($opush_page - 1) * $opush_per_page;

$opush_current_emojis = array_slice(
    $opush,
    $opush_start,
    $opush_per_page
);


/* =========================================================
   YUXU SƏHİFƏLƏMƏ
========================================================= */

$yuxu_per_page = 10;

$yuxu_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($yuxu_page < 1) {
    $yuxu_page = 1;
}

$yuxu_total = count($yuxu);

$yuxu_total_pages = (int)ceil(
    $yuxu_total / $yuxu_per_page
);

if ($yuxu_total_pages < 1) {
    $yuxu_total_pages = 1;
}

if ($yuxu_page > $yuxu_total_pages) {
    $yuxu_page = $yuxu_total_pages;
}

$yuxu_start = ($yuxu_page - 1) * $yuxu_per_page;

$yuxu_current_emojis = array_slice(
    $yuxu,
    $yuxu_start,
    $yuxu_per_page
);


/* =========================================================
   SEVGİ SƏHİFƏLƏMƏ
========================================================= */

$sevgi_per_page = 10;

$sevgi_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($sevgi_page < 1) {
    $sevgi_page = 1;
}

$sevgi_total = count($sevgi);

$sevgi_total_pages = (int)ceil(
    $sevgi_total / $sevgi_per_page
);

if ($sevgi_total_pages < 1) {
    $sevgi_total_pages = 1;
}

if ($sevgi_page > $sevgi_total_pages) {
    $sevgi_page = $sevgi_total_pages;
}

$sevgi_start = ($sevgi_page - 1) * $sevgi_per_page;

$sevgi_current_emojis = array_slice(
    $sevgi,
    $sevgi_start,
    $sevgi_per_page
);

/* =========================================================
   KEFSİZ SƏHİFƏLƏMƏ
========================================================= */

$kefsiz_per_page = 10;

$kefsiz_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($kefsiz_page < 1) {
    $kefsiz_page = 1;
}

$kefsiz_total = count($kefsiz);

$kefsiz_total_pages = (int)ceil(
    $kefsiz_total / $kefsiz_per_page
);

if ($kefsiz_total_pages < 1) {
    $kefsiz_total_pages = 1;
}

if ($kefsiz_page > $kefsiz_total_pages) {
    $kefsiz_page = $kefsiz_total_pages;
}

$kefsiz_start = ($kefsiz_page - 1) * $kefsiz_per_page;

$kefsiz_current_emojis = array_slice(
    $kefsiz,
    $kefsiz_start,
    $kefsiz_per_page
);


/* =========================================================
   ƏSƏB SƏHİFƏLƏMƏ
========================================================= */

$eseb_per_page = 10;

$eseb_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($eseb_page < 1) {
    $eseb_page = 1;
}

$eseb_total = count($eseb);

$eseb_total_pages = (int)ceil(
    $eseb_total / $eseb_per_page
);

if ($eseb_total_pages < 1) {
    $eseb_total_pages = 1;
}

if ($eseb_page > $eseb_total_pages) {
    $eseb_page = $eseb_total_pages;
}

$eseb_start = ($eseb_page - 1) * $eseb_per_page;

$eseb_current_emojis = array_slice(
    $eseb,
    $eseb_start,
    $eseb_per_page
);


/* =========================================================
   GÜLMƏK SƏHİFƏLƏMƏ
========================================================= */

$gulmek_per_page = 10;

$gulmek_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($gulmek_page < 1) {
    $gulmek_page = 1;
}

$gulmek_total = count($gulmek);

$gulmek_total_pages = (int)ceil(
    $gulmek_total / $gulmek_per_page
);

if ($gulmek_total_pages < 1) {
    $gulmek_total_pages = 1;
}

if ($gulmek_page > $gulmek_total_pages) {
    $gulmek_page = $gulmek_total_pages;
}

$gulmek_start = ($gulmek_page - 1) * $gulmek_per_page;

$gulmek_current_emojis = array_slice(
    $gulmek,
    $gulmek_start,
    $gulmek_per_page
);


/* =========================================================
   SƏHİFƏLƏMƏ
========================================================= */

$per_page = 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$total_emojis = count($emosiyalar);

$total_pages = (int)ceil($total_emojis / $per_page);

if ($total_pages < 1) {
    $total_pages = 1;
}

if ($page > $total_pages) {
    $page = $total_pages;
}

$start = ($page - 1) * $per_page;

$current_emojis = array_slice(
    $emosiyalar,
    $start,
    $per_page
);

/* =========================================================
   GERİ
========================================================= */

function goGeri()
{
    echo "window.history.back();";
}

?>
<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL">

<meta name="keywords"
content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar">

<meta name="description"
content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры">

<link rel="stylesheet" href="css.css">

<meta content="text/html; charset=utf-8"
http-equiv="content-type">

<meta name="viewport"
content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<title>Smayliklər</title>

<script>

function goGeri() {
    window.history.back();
}

</script>

</head>

<body>

<div class="main" style="word-wrap:break-word;">

<div id="header">

<a href="menu.php?">
<img src="img/logo.png">
</a>

<div class="icons"></div>

<div class="main_foot">

<div class="grey">

<img src="img/coin.png" title="Qızıl" alt="">
<?php echo (int)$user['qızıl']; ?>

<img src="img/brill.png" title="Brilliant" alt="">
<?php echo (int)$user['brılyant']; ?>

<img src="img/energy.png" title="Enerji" alt="">
<?php echo (int)$user['enerjı']; ?>


<?php if ($new_message_count > 0) { ?>

<a href="arxiv.php?go=goster">

<img src="img/mektub.gif"
title="Məktub"
alt="Məktub">

</a>

(<?php echo $new_message_count; ?>)

<?php } ?>


<?php if ($dostluq_sayi > 0) { ?>

<a href="dostlar.php">

<img src="muxtelif/dost_pilus.png"
title="Dost"
alt="Dost">

</a>

(<?php echo $dostluq_sayi; ?>)

<?php } ?>

</div>
</div>
</div>

<div class="space"></div>

<div style="background:none repeat scroll 0 0 #888686;height:1px;"></div>


<div class="fl b exp_count">

<div style="margin-top:-2px;">

<span style="color:#ff3333">

<b><?php echo $progress; ?>%</b>

</span>

</div>

</div>


<div class="experience">

<div class="exp_bg">

<div class="exp_left fl"></div>

<div class="exp_right fr"></div>

<div style="width:<?php echo $progress; ?>%;height:10px;">

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>

</div>

</div>


<div style="background:none repeat scroll 0 0 #888686;height:1px;"></div>


<div class="info">

<?php

/* =========================================================
   GO=1 → EMOSİYALAR
========================================================= */

if (isset($_GET['go']) && $_GET['go'] == '1') {

?>

<u>Emosiya smaylikləri</u>
<br><br>

<?php foreach ($current_emojis as $smile) { ?>

<img
src="muxtelif/smaylikler/emosiyalar/<?php echo htmlspecialchars($smile['file'], ENT_QUOTES, 'UTF-8'); ?>"
alt="<?php echo htmlspecialchars($smile['alt'], ENT_QUOTES, 'UTF-8'); ?>"
>

<b><?php echo htmlspecialchars($smile['code'], ENT_QUOTES, 'UTF-8'); ?></b>

<div class="line"></div>

<?php } ?>


<?php if ($total_pages > 1) { ?>

<?php for ($i = 1; $i <= $total_pages; $i++) { ?>

<?php if ($i == $page) { ?>

<b><?php echo $i; ?></b>

<?php } else { ?>

<a href="smaylikler.php?go=1&page=<?php echo $i; ?>">
<?php echo $i; ?>
</a>

<?php } ?>

<?php if ($i < $total_pages) { ?>
|
<?php } ?>

<?php } ?>

<div class="line"></div>

<?php } ?>


<input
type="button"
class="button"
value="Geri"
onclick="goGeri()">


<?php

} elseif (isset($_GET['go']) && $_GET['go'] == '2') {

/* =========================================================
   GO=2 → GÜLMƏK
========================================================= */

?>
<u>Gülmək smaylikləri</u>
<br><br>

<?php

foreach ($gulmek_current_emojis as $smile) {

?>

<img
src="muxtelif/smaylikler/gulmek/<?php echo htmlspecialchars($smile['file'], ENT_QUOTES, 'UTF-8'); ?>"
alt="<?php echo htmlspecialchars($smile['alt'], ENT_QUOTES, 'UTF-8'); ?>"
>

<b><?php echo htmlspecialchars($smile['code'], ENT_QUOTES, 'UTF-8'); ?></b>

<div class="line"></div>

<?php

}

if ($gulmek_total_pages > 1) {

    for ($i = 1; $i <= $gulmek_total_pages; $i++) {

        if ($i == $gulmek_page) {

            echo '<b>' . $i . '</b>';

        } else {

            echo '<a href="smaylikler.php?go=2&page=' . $i . '">' . $i . '</a>';

        }

        if ($i < $gulmek_total_pages) {
            echo '|';
        }
    }

    echo '<div class="line"></div>';
}

?>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()">

<?php

} elseif (isset($_GET['go']) && $_GET['go'] == '3') {

/* =========================================================
   GO=3 → ƏSƏB
========================================================= */

?>
<u>Əsəb smaylikləri</u>
<br><br>

<?php foreach ($eseb_current_emojis as $smile) { ?>

<img
src="muxtelif/smaylikler/eseb/<?php echo htmlspecialchars($smile['file'], ENT_QUOTES, 'UTF-8'); ?>"
alt="<?php echo htmlspecialchars($smile['alt'], ENT_QUOTES, 'UTF-8'); ?>"
>

<b><?php echo htmlspecialchars($smile['code'], ENT_QUOTES, 'UTF-8'); ?></b>

<div class="line"></div>

<?php } ?>

<?php if ($eseb_total_pages > 1) { ?>

<?php for ($i = 1; $i <= $eseb_total_pages; $i++) { ?>

<?php if ($i == $eseb_page) { ?>

<b><?php echo $i; ?></b>

<?php } else { ?>

<a href="smaylikler.php?go=3&page=<?php echo $i; ?>">
<?php echo $i; ?>
</a>

<?php } ?>

<?php if ($i < $eseb_total_pages) echo '|'; ?>

<?php } ?>

<div class="line"></div>

<?php } ?>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()">

<?php
} elseif (isset($_GET['go']) && $_GET['go'] == '4') {

/* =========================================================
   GO=4 → KEFSİZ
========================================================= */

?>

<u>Kefsiz smaylikləri</u>
<br><br>

<?php foreach ($kefsiz_current_emojis as $smile) { ?>

<img
src="muxtelif/smaylikler/kefsiz/<?php echo htmlspecialchars($smile['file'], ENT_QUOTES, 'UTF-8'); ?>"
alt="<?php echo htmlspecialchars($smile['alt'], ENT_QUOTES, 'UTF-8'); ?>"
>

<b><?php echo htmlspecialchars($smile['code'], ENT_QUOTES, 'UTF-8'); ?></b>

<div class="line"></div>

<?php } ?>

<?php if ($kefsiz_total_pages > 1) { ?>

<?php for ($i = 1; $i <= $kefsiz_total_pages; $i++) { ?>

<?php if ($i == $kefsiz_page) { ?>

<b><?php echo $i; ?></b>

<?php } else { ?>

<a href="smaylikler.php?go=4&page=<?php echo $i; ?>">
<?php echo $i; ?>
</a>

<?php } ?>

<?php if ($i < $kefsiz_total_pages) echo '|'; ?>

<?php } ?>

<div class="line"></div>

<?php } ?>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()">

<?php
} elseif (isset($_GET['go']) && $_GET['go'] == '5') {

/* =========================================================
   GO=5 → SEVGİ, ÜRƏKLƏR
========================================================= */

?>

<u>Sevgi, ürəklər smaylikləri</u>
<br><br>

<?php foreach ($sevgi_current_emojis as $smile) { ?>

<img
src="muxtelif/smaylikler/sevgi/<?php echo htmlspecialchars($smile['file'], ENT_QUOTES, 'UTF-8'); ?>"
alt="<?php echo htmlspecialchars($smile['alt'], ENT_QUOTES, 'UTF-8'); ?>"
>

<b><?php echo htmlspecialchars($smile['code'], ENT_QUOTES, 'UTF-8'); ?></b>

<div class="line"></div>

<?php } ?>

<?php if ($sevgi_total_pages > 1) { ?>

<?php for ($i = 1; $i <= $sevgi_total_pages; $i++) { ?>

<?php if ($i == $sevgi_page) { ?>

<b><?php echo $i; ?></b>

<?php } else { ?>

<a href="smaylikler.php?go=5&page=<?php echo $i; ?>">
<?php echo $i; ?>
</a>

<?php } ?>

<?php if ($i < $sevgi_total_pages) echo '|'; ?>

<?php } ?>

<div class="line"></div>

<?php } ?>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()">

<?php
} elseif (isset($_GET['go']) && $_GET['go'] == '6') {

/* =========================================================
   GO=6 → YUXU
========================================================= */

?>

<u>Yuxu smaylikləri</u>
<br><br>

<?php foreach ($yuxu_current_emojis as $smile) { ?>

<img
src="muxtelif/smaylikler/yuxu/<?php echo htmlspecialchars($smile['file'], ENT_QUOTES, 'UTF-8'); ?>"
alt="<?php echo htmlspecialchars($smile['alt'], ENT_QUOTES, 'UTF-8'); ?>"
>

<b><?php echo htmlspecialchars($smile['code'], ENT_QUOTES, 'UTF-8'); ?></b>

<div class="line"></div>

<?php } ?>

<?php if ($yuxu_total_pages > 1) { ?>

<?php for ($i = 1; $i <= $yuxu_total_pages; $i++) { ?>

<?php if ($i == $yuxu_page) { ?>

<b><?php echo $i; ?></b>

<?php } else { ?>

<a href="smaylikler.php?go=6&page=<?php echo $i; ?>">
<?php echo $i; ?>
</a>

<?php } ?>

<?php if ($i < $yuxu_total_pages) echo '|'; ?>

<?php } ?>

<div class="line"></div>

<?php } ?>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()">

<?php
} elseif (isset($_GET['go']) && $_GET['go'] == '7') {

/* =========================================================
   GO=7 → ÖPÜŞ
========================================================= */

?>

<u>Öpüş smaylikləri</u>
<br><br>

<?php foreach ($opush_current_emojis as $smile) { ?>

<img
src="muxtelif/smaylikler/opush/<?php echo htmlspecialchars($smile['file'], ENT_QUOTES, 'UTF-8'); ?>"
alt="<?php echo htmlspecialchars($smile['alt'], ENT_QUOTES, 'UTF-8'); ?>"
>

<b><?php echo htmlspecialchars($smile['code'], ENT_QUOTES, 'UTF-8'); ?></b>

<div class="line"></div>

<?php } ?>

<?php if ($opush_total_pages > 1) { ?>

<?php for ($i = 1; $i <= $opush_total_pages; $i++) { ?>

<?php if ($i == $opush_page) { ?>

<b><?php echo $i; ?></b>

<?php } else { ?>

<a href="smaylikler.php?go=7&page=<?php echo $i; ?>">
<?php echo $i; ?>
</a>

<?php } ?>

<?php if ($i < $opush_total_pages) echo '|'; ?>

<?php } ?>

<div class="line"></div>

<?php } ?>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()">

<?php
} elseif (isset($_GET['go']) && $_GET['go'] == '8') {

/* =========================================================
   GO=8 → QARIŞIQ
========================================================= */

?>

<u>Qarışıq smaylikləri</u>
<br><br>

<?php foreach ($qarisiq_current_emojis as $smile) { ?>

<img
src="muxtelif/smaylikler/qarisiq/<?php echo htmlspecialchars($smile['file'], ENT_QUOTES, 'UTF-8'); ?>"
alt="<?php echo htmlspecialchars($smile['alt'], ENT_QUOTES, 'UTF-8'); ?>"
>

<b><?php echo htmlspecialchars($smile['code'], ENT_QUOTES, 'UTF-8'); ?></b>

<div class="line"></div>

<?php } ?>

<?php if ($qarisiq_total_pages > 1) { ?>

<?php for ($i = 1; $i <= $qarisiq_total_pages; $i++) { ?>

<?php if ($i == $qarisiq_page) { ?>

<b><?php echo $i; ?></b>

<?php } else { ?>

<a href="smaylikler.php?go=8&page=<?php echo $i; ?>">
<?php echo $i; ?>
</a>

<?php } ?>

<?php if ($i < $qarisiq_total_pages) echo '|'; ?>

<?php } ?>

<div class="line"></div>

<?php } ?>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()">

<?php


} else {


?>

Smayıllar (295)
<br><br>

1)
<a href="smaylikler.php?go=1">
Emosiyalar
</a>
(<?php echo count($emosiyalar); ?>)

<br>

2)
<a href="smaylikler.php?go=2">
Gülmek
</a>
(<?php echo count($gulmek); ?>)

<br>

3)
<a href="smaylikler.php?go=3">
Eseb
</a>
(<?php echo count($eseb); ?>)


<br>

4)
<a href="smaylikler.php?go=4">
Kefsiz
</a>
(<?php echo count($kefsiz); ?>)


<br>

5)
<a href="smaylikler.php?go=5">
Sevgi,ürekler
</a>
(<?php echo count($sevgi); ?>)

<br>

6)
<a href="smaylikler.php?go=6">
Yuxu
</a>
(<?php echo count($yuxu); ?>)

<br>

7)
<a href="smaylikler.php?go=7">
Öpüş
</a>
(<?php echo count($opush); ?>)

<br>

8)
<a href="smaylikler.php?go=8">
Qarışıq
</a>
(<?php echo count($qarisiq); ?>)

<br><br>

<div class="line"></div>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()">

<?php } ?>


</div>


<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">


[<b><a href="menu.php?">Menu</a></b>]

[<b><a href="axtar.php?">Axtarış</a></b>]

[<a href="forum/mozu2.php?">Forum</a>]

[<a href="shexsi_sehife.php?">Qurğular</a>]

<br><br>

<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>

<br>

<a href="index.php?">Çıxış (<?php echo htmlspecialchars($user_login); ?>)</a>

<br><br>

<a href="menu.php?dil=tr">

Türkce:

<img
alt="türkce"
src="http://macera.az/klan/muxtelif/tr.gif"
title="Türkce">

</a>

<br>

Sciript name: Qanlı efsane(modern version)

<br>

<a
href="http://klanaz.com/klan/"
class="xgame.az">

&#169; Klanaz.com 2026

</a>

</div>
</div>
</div>
</div>

</div>

</div>

</body>
</html>
