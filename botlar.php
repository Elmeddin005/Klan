<?php

session_start();

require_once "config.php";
require_once "user_data.php";
$user_login = '';

if (isset($user) && is_array($user) && isset($user['login'])) {
    $user_login = $user['login'];
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];

/* =========================================================
   MOB SEÇİMİ
   0 = Vəhşi moblar: öz leveli + 2
   1 = Hamısı: 1-14 arası random
   ========================================================= */

$player_level = (int)$user['oyuncunun_seviyyesi'];

/* Qurğudan mob seçimini oxuyuruq */

$stmt_mob_qurgu = $pdo->prepare("
    SELECT mob_qebulu
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_mob_qurgu->execute([
    ':id' => $my_id
]);

$mob_qebulu = (int)$stmt_mob_qurgu->fetchColumn();

if ($mob_qebulu !== 1) {
    $mob_qebulu = 0;
}


/* =========================================================
   VƏHŞİ MOBLAR
   Məsələn level 3 → 3,4,5
   ========================================================= */

if ($mob_qebulu === 0) {

    $max_level = $player_level + 2;

    $stmt_botlar = $pdo->prepare("
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
        WHERE seviyye BETWEEN :min_level AND :max_level
        ORDER BY RAND()
        LIMIT 9
    ");

    $stmt_botlar->execute([
        ':min_level' => $player_level,
        ':max_level' => $max_level
    ]);

}


/* =========================================================
   HAMISI
   1-14 arası bütün moblardan random
   ========================================================= */

else {

    $stmt_botlar = $pdo->prepare("
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
        WHERE seviyye BETWEEN 1 AND 14
        ORDER BY RAND()
        LIMIT 9
    ");

    $stmt_botlar->execute();

}


$botlar = $stmt_botlar->fetchAll(PDO::FETCH_ASSOC);



/* =========================================================
   SEÇİLMİŞ BOT
   ========================================================= */

$bot_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$selected_bot = null;

if ($bot_id > 0) {

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
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_bot->execute([
        ':id' => $bot_id
    ]);

    $selected_bot = $stmt_bot->fetch(PDO::FETCH_ASSOC);

    if (!$selected_bot) {
        exit('Bot tapılmadı.');
    }
}


/* =========================================================
   OXUNMAMIŞ MESAJLAR
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

?>

<!DOCTYPE html>

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

<title>Moblar</title>


<script>

function goGeri() {
    window.history.back();
}

</script>


<style>

.mob-14-gray {
    width: 100px !important;
    height: auto !important;
    object-fit: contain;
}

.mob-14-green {
    width: 100px !important;
    height: auto !important;
    object-fit: contain;
    mix-blend-mode: multiply;
    filter: hue-rotate(90deg) saturate(2);
}

.mob-14-wild {
    width: 100px !important;
    height: auto !important;
    object-fit: contain;
    mix-blend-mode: multiply;
    filter: hue-rotate(315deg) saturate(6) sepia(1);
}

</style>

</head>


<body>

<div
    class="main"
    style="word-wrap:break-word;"
>


<!-- =====================================================
     HEADER
     ===================================================== -->

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


<!-- =====================================================
     MOBLAR
     ===================================================== -->

<div class="info">


<br/>


<div class="center">

<div class="block_line">

Vehşi Moblar

</div>

</div>


<br/>


<div class="line"></div>


<?php foreach ($botlar as $bot): ?>


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


/* MySQL-dan gələn şəkil adı */
$mob_img = trim($bot['img']);

?>


<div class="menu">

<br/>


<div class="battle_log">

<li>


<a
    href="bot_info.php?go=al&amp;uid=<?php echo (int)$bot['id']; ?>&amp;vahsi=1"
>


<img
    width="55"
    height="40"
    class="<?php echo $mob_class; ?>"
    src="img/botlar/<?php echo htmlspecialchars($mob_img, ENT_QUOTES, 'UTF-8'); ?>"
    alt="<?php echo htmlspecialchars($bot['ad'], ENT_QUOTES, 'UTF-8'); ?>"
>


<?php echo htmlspecialchars($bot['ad'], ENT_QUOTES, 'UTF-8'); ?>

[<?php echo (int)$bot['seviyye']; ?>]


</a>


</li>

</div>

</div>


<?php endforeach; ?>


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


<a href="index.php?">
    Çıxış (<?php echo htmlspecialchars($user_login, ENT_QUOTES, 'UTF-8'); ?>)
</a>



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