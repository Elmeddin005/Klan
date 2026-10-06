<?php

session_start();

require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}


/* =====================================================
   İSTİFADƏÇİ MƏLUMATLARI
   ===================================================== */

$stmt = $pdo->prepare("
    SELECT qızıl, brılyant, enerjı
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $_SESSION['user_id']
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit('İstifadəçi tapılmadı.');
}
$my_id = (int)$_SESSION['user_id'];

$stmt_unread = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE mesaji_alan_nik = :my_id
      AND oxundu = 0
");

$stmt_unread->execute([
    ':my_id' => $my_id
]);

$unread_count = (int)$stmt_unread->fetchColumn();


/* =====================================================
   SEÇİLƏN MOBUN ID-Sİ
   botlar.php-dən uid gəlir
   ===================================================== */

$bot_id = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;

if ($bot_id <= 0) {
    exit('Bot seçilməyib.');
}


/* =====================================================
   MOBU MYSQL-DAN GƏTİR
   ===================================================== */

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
    exit('Bot tapılmadı. ID: ' . $bot_id);
}


/* =====================================================
   ŞƏKİL
   ===================================================== */

$bot_img = trim($bot['img']);


/* =====================================================
   14-CÜ SƏVİYYƏ MOBLARININ RƏNGLƏRİ
   ===================================================== */

$bot_style = '';

if ((int)$bot['seviyye'] === 14) {

    if ($bot['ad'] === 'Green-Aligator') {

        $bot_style = 'mix-blend-mode:multiply; filter:hue-rotate(90deg) saturate(2);';

    } elseif ($bot['ad'] === 'Wild-Aligator') {

        $bot_style = 'mix-blend-mode:multiply; filter:hue-rotate(315deg) saturate(6) sepia(1);';

    } elseif ($bot['ad'] === 'Gray-Aligator') {

        $bot_style = 'mix-blend-mode:multiply;';
    }
}

?>

<!DOCTYPE html PUBLIC>
<html>

<head>

<meta name="robots" content="ALL" />

<meta
    name="keywords"
    content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar"
/>

<meta
    name="description"
    content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры"
/>

<link rel="stylesheet" href="css.css">

<meta
    content="text/html; charset=utf-8"
    http-equiv="content-type"
/>

<meta
    name="viewport"
    content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"
>

<title>Bot info</title>

<script>

function goGeri() {
    window.history.back();
}

</script>

</head>


<body>

<div
    class="main"
    style="word-wrap:break-word;"
>


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


<div class="info">


<!-- =====================================================
     MOB MƏLUMATLARI
     ===================================================== -->


<b>

<u>

<?php echo htmlspecialchars($bot['ad'], ENT_QUOTES, 'UTF-8'); ?>

</u>

[<?php echo (int)$bot['seviyye']; ?>]

</b>


<br/>
<br/>


<img
    width="110"
    height="auto"
    src="img/botlar/<?php echo htmlspecialchars($bot_img, ENT_QUOTES, 'UTF-8'); ?>"
    alt="<?php echo htmlspecialchars($bot['ad'], ENT_QUOTES, 'UTF-8'); ?>"
    style="<?php echo $bot_style; ?> object-fit:contain;"
>

<br/>

<form
    method="post"
    action="bot_hucum.php?go=gonder&amp;uid=<?php echo (int)$bot['id']; ?>&amp;vahsi=1"
>

<input
    type="hidden"
    name="action"
    value="save"
/>


<input type="submit" class="button" value="Hucum et"/>


<br/>


&#187; <b>Merhele:</b>
<?php echo (int)$bot['seviyye']; ?>

<br/>


&#187; <b>Canı:</b>
<?php echo (int)$bot['can']; ?>

<br/>


&#187; <b>Müdafiesi:</b>
<?php echo (int)$bot['mudafie']; ?>

<br/>


&#187; <b>Zerbesi:</b>
<?php echo (int)$bot['zerbe_min']; ?>
-
<?php echo (int)$bot['zerbe_max']; ?>

<br/>


&#187; krit:

<font style="color: #4466ff">

<?php echo (int)$bot['krit']; ?>

| 0% (0)

</font>

<br/>


&#187; Uvorot:

<font style="color: #4466ff">

<?php echo (int)$bot['uvarotu']; ?>

| 0% (0)

</font>

<br/>


&#187; Anti krit:

<font style="color: #4466ff">

<?php echo (int)$bot['anti_krit']; ?>

| 0% (0)

</font>

<br/>


&#187; Anti uvorot:

<font style="color: #4466ff">

<?php echo (int)$bot['anti_uvarotu']; ?>

| 0% (0)

</font>


<br/>


<div class="line"></div>


<div class="menu">

<br/>

<li>

<a href="botlar.php?">

Vehşi Moblar

</a>

</li>

</div>


</form>


<!-- =====================================================
     FOOTER
     ===================================================== -->


<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">


[<b>

<a href="menu.php?">

Menu

</a>

</b>]


[<b>

<a href="axtar.php?">

Axtarış

</a>

</b>]


[

<a href="forum/mozu2.php?">

Forum

</a>

]


[

<a href="shexsi_sehife.php?">

Qurğular

</a>

]


<br/>
<br/>


<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>


<br/>


<a href="index.php?">Çıxış (<?php echo htmlspecialchars($user_login); ?>)</a>



<br/>
<br/>


<a href="menu.php?dil=tr">

Türkce:

<img
    alt="türkce"
    src="http://macera.az/klan/muxtelif/tr.gif"
    title="Türkce"
/>

</a>


<br/>


Sciript name: Qanlı efsane(modern version)


<br/>


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

</body>

</html>