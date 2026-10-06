<?php

session_start();

require_once "config.php";
require_once "user_data.php";
if (isset($_GET['kordinat'])) {
    $kordinat = (int)$_GET['kordinat'];

    if ($kordinat < 1) {
        $kordinat = 1;
    }

    $_SESSION['son_kordinat'] = $kordinat;
} elseif (isset($_SESSION['son_kordinat'])) {
    $kordinat = (int)$_SESSION['son_kordinat'];
} else {
    $kordinat = 1;
}

/* =========================================================
   GİRİŞ YOXLAMASI
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


$my_id = (int)$_SESSION['user_id'];


/* =========================================================
   OXUNMAMIŞ MƏKTUBLAR
========================================================= */

$unread_count = 0;

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
   OYUNÇUNUN SƏVİYYƏSİNƏ UYĞUN MOB
========================================================= */

$player_level = (int)$user['oyuncunun_seviyyesi'];


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
        zerbe,
        krit,
        anti_krit,
        uvarotu,
        anti_uvarotu
    FROM botlar
    WHERE seviyye = :seviyye
    ORDER BY RAND()
    LIMIT 1
");


$stmt_bot->execute([
    ':seviyye' => $player_level
]);


$bot = $stmt_bot->fetch(PDO::FETCH_ASSOC);


if (!$bot) {
    exit('Bu səviyyə üçün mob tapılmadı.');
}

?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN""http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="az" lang="az">
<head>
<meta name="robots" content="ALL" /> 
<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 
<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 

<link rel="stylesheet" href="css.css">

<meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"><title>kordinat</title>

<script>
    function goGeri() {
        window.history.back();
    }

</script>

<style>
.mob-14-gray img,
.mob-14-green img,
.mob-14-wild img {
    width: 120px;
    height: auto;
}

.mob-14-green img {
    mix-blend-mode: multiply;
    filter: hue-rotate(90deg) saturate(2);
}

.mob-14-wild img {
    mix-blend-mode: multiply;
    filter: hue-rotate(315deg) saturate(6) sepia(1);
}
</style>
</head>
<body>

<div class='main' style='word-wrap:break-word;'>
<div id="header">

<a href="menu.php?">
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
        <img src="img/mektub.gif" title="Yeni mesaj" alt="Mesaj"/>
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

<div style="background: none repeat scroll 0 0 #888686; height: 1px;"></div>

<div class="fl b exp_count">
    <div style="margin-top: -2px;">
        <span style="color: #ff3333">
            <b><?php echo $progress; ?>%</b>
        </span>
    </div>
</div>

<div class="experience">
    <div class="exp_bg">

        <div class="exp_left fl"></div>
        <div class="exp_right fr"></div>

        <div style="width: <?php echo $progress; ?>%; height: 10px;">
            <div class="exp_line"></div>
            <div class="exp_point"></div>
        </div>

    </div>
</div>

<div style="background: none repeat scroll 0 0 #888686; height: 1px;"></div>




<?php

/* =========================================================
   QALAYA GİRİŞ
   ========================================================= */

if (isset($_GET['go']) && $_GET['go'] == 'deyish') {

?>

<div class='info'>

<div class=center>
<b>Qalanın adı-</b>The Castle of Light  <br/>
<b>Dayandıgınız Kordinat-</b><?php echo (int)$kordinat; ?> <br/>
<br/>

<i>
<a href="kordinat.php?go=qala&amp;semt=ireli&amp;kordinat=<?php echo (int)$kordinat; ?>">İreli &#187; </a>
<br/>

<a href="kordinat.php?go=qala&amp;semt=geri&amp;kordinat=<?php echo (int)$kordinat; ?>">&#171; Geri </a>
</i>

<br/><br/>

<?php

/* =========================================================
   GO=QALA
   ========================================================= */

} elseif (isset($_GET['go']) && $_GET['go'] == 'qala') {

    $kordinat = isset($_GET['kordinat']) ? intval($_GET['kordinat']) : 1;
    $semt = isset($_GET['semt']) ? $_GET['semt'] : '';

    if ($kordinat < 1) {
        $kordinat = 1;
    }

    if ($semt == 'ireli') {

        $yeni_kordinat = $kordinat + 1;

    } elseif ($semt == 'geri') {

        $yeni_kordinat = $kordinat - 1;

        if ($yeni_kordinat < 1) {
            $yeni_kordinat = 1;
        }

    } else {

        $yeni_kordinat = $kordinat;

    }

    /* Növbəti koordinat */
    if ($semt == 'ireli') {

        $yol_kordinat = $yeni_kordinat + 1;

    } elseif ($semt == 'geri') {

        $yol_kordinat = $yeni_kordinat - 1;

        if ($yol_kordinat < 1) {
            $yol_kordinat = 1;
        }

    } else {

        $yol_kordinat = $yeni_kordinat;

    }

?>
<div class='info'>

<div class=center><b>Qalanın adı-</b>The Castle of Light  <br/>
<b>Dayandıgınız Kordinat-</b><?php echo (int)$yeni_kordinat; ?> <br/>
<b>Yolunuz ireliye Kordinat-</b><?php echo (int)$yol_kordinat; ?><br/><br/><br/>



<b>

<?php
$mob_class = '';

if ((int)$bot['seviyye'] === 14) {

    if ($bot['ad'] === 'Gray-Aligator') {
        $mob_class = 'mob-14-gray';

    } elseif ($bot['ad'] === 'Green-Aligator') {
        $mob_class = 'mob-14-green';

    } elseif ($bot['ad'] === 'Wild-Aligator') {
        $mob_class = 'mob-14-wild';
    }
}
?>
<div class="<?php echo $mob_class; ?>">

    <b>
      <img
    width="69" height="46"
    src="./img/botlar/<?php echo htmlspecialchars($bot['img'], ENT_QUOTES, 'UTF-8'); ?>"
    alt="<?php echo htmlspecialchars($bot['ad'], ENT_QUOTES, 'UTF-8'); ?>"
>

<b>
    <?php echo htmlspecialchars($bot['ad'], ENT_QUOTES, 'UTF-8'); ?>
    [<?php echo (int)$bot['seviyye']; ?>]
</b>

sizin qarşınızı kəsdi.<br>
</div>

<form method="post" action="bot_hucum.php?go=gonder&amp;kordinat=<?php echo (int)$yeni_kordinat; ?>&amp;semt=<?php echo urlencode($semt); ?>&amp;uid=<?php echo (int)$bot['id']; ?>">
    <input type="hidden" name="action" value="save"/>

    <input type="submit" class="button" value="Hucum et"/><br/>

    <br/>

    <a href="kordinat.php?go=deyish&amp;semt=ireli">
        Semtini Deyiş
    </a><br/>

</form>

<?php

} else {

?>

<div class='info'>

<div class=center>
The Castle of Light<br/>
<b>Qalanın adı:</b> The Castle of Light<br/>
<b>Kordinat: </b>1-900<br/>

<div class=menu>
<br/>
<li><a href="kordinat.php?go=deyish&amp;semt=">Qalaya Giriş</a></li>
</div>

<br/>

<?php
}
?>

<div class="main_foot">
    <div class="center">
        <div class="grey">
            <div class="small">
                <div class="foot">
    
[<b><a href="menu.php?">Menu</a></b>] 
[<b><a href="axtar.php?">Axtarış</a></b>] 
[<a href="forum/mozu2.php?">Forum</a>] 
[<a href="shexsi_sehife.php?">Qurğular</a>]

<br/><br/>
    
<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>
<br/>

<a href="index.php?">
    Çıxış (<?php echo htmlspecialchars($user_login, ENT_QUOTES, 'UTF-8'); ?>)
</a>

<br/><br/>  
    
<a href="menu.php?dil=tr">Türkce: <img alt="türkce" src="http://macera.az/klan/muxtelif/tr.gif" title="Türkce"/></a><br/>
Sciript name: Qanlı efsane(modern version)<br/>
    
<a href="http://klanaz.com/klan/" class="xgame.az">&#169; Klanaz.com 2026</a>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>