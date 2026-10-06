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
   VIP STATUSU
========================================================= */

$stmt_vip_status = $pdo->prepare("
    SELECT vip, vip_bitme_vaxti
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_vip_status->execute([
    ':id' => $my_id
]);

$vip_data = $stmt_vip_status->fetch(PDO::FETCH_ASSOC);

$vip_status = (int)($vip_data['vip'] ?? 0);
$vip_bitme_vaxti = $vip_data['vip_bitme_vaxti'] ?? null;


/* =========================================================
   VIP VAXTI BİTİBSƏ
========================================================= */

if (
    $vip_status === 1 &&
    !empty($vip_bitme_vaxti) &&
    strtotime($vip_bitme_vaxti) <= time()
) {

    $stmt_vip_bitdi = $pdo->prepare("
        UPDATE users
        SET vip = 0,
            vip_bitme_vaxti = NULL
        WHERE id = :id
    ");

    $stmt_vip_bitdi->execute([
        ':id' => $my_id
    ]);

    $vip_status = 0;
    $vip_bitme_vaxti = null;
}


/* =========================================================
   VIP ALIŞI
========================================================= */

$vip_alindi = false;

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'ok' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {

    /*
       Əgər artıq VIP-dirsə,
       yenidən almağa icazə vermirik.
    */

    if ($vip_status !== 1) {

        $tipi = isset($_POST['tipi'])
            ? (int)$_POST['tipi']
            : 0;


        /* =====================================================
           QİYMƏT
        ===================================================== */

        if ($tipi === 0) {

            $qiymet = 150;
            $gun = 1;

        } else {

            $qiymet = 1200;
            $gun = 30;

        }


        /* =====================================================
           BRİLLİANT YOXLANILIR
        ===================================================== */

        $stmt_vip_user = $pdo->prepare("
            SELECT brılyant
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $stmt_vip_user->execute([
            ':id' => $my_id
        ]);

        $brilyant = (int)$stmt_vip_user->fetchColumn();


        if ($brilyant < $qiymet) {

            die('Sizin kifayət qədər Brilyantınız yoxdur.');

        }


        /* =====================================================
           VIP BİTMƏ VAXTI
        ===================================================== */

        $bitme_vaxti = date(
            'Y-m-d H:i:s',
            time() + ($gun * 86400)
        );


        /* =====================================================
           BRİLLİANT ÇIXILIR + VIP AKTİV EDİLİR
        ===================================================== */

        $stmt_vip_al = $pdo->prepare("
            UPDATE users
            SET
                brılyant = brılyant - :qiymet,
                vip = 1,
                vip_bitme_vaxti = :bitme_vaxti
            WHERE id = :id
        ");

        $stmt_vip_al->execute([
            ':qiymet' => $qiymet,
            ':bitme_vaxti' => $bitme_vaxti,
            ':id' => $my_id
        ]);


        /*
           Alış uğurlu oldu.
        */

        $vip_alindi = true;
    }
}


/* =========================================================
   YENİ MESAJ SAYI
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

/* Mövqeni bazadan götürürük */
$stmt_movqe = $pdo->prepare("
    SELECT movqe
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_movqe->execute([
    ':id' => $my_id
]);

$user_movqe = (int)$stmt_movqe->fetchColumn();

/* Mövqeyə uyğun rəng */
if ($user_movqe === 1) {
    $movqe_reng = 'red';        // İnsan
} elseif ($user_movqe === 2) {
    $movqe_reng = '#0F7100';    // Vampir
} elseif ($user_movqe === 3) {
    $movqe_reng = 'blue';       // Neytral
} else {
    $movqe_reng = 'white';
}



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
   ONLINE VAXTINI YENİLƏYİRİK
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
   ONLINE OYUNCULARIN SAYI
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

?>
<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL" />

<meta
name="keywords"
content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, "
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

<title>klanaz.com/klan | Vip</title>

<script>
function goGeri() {
    window.history.back();
}
</script>

</head>

<body>

<div class='main' style='word-wrap:break-word;'>

<div id="header">

<a href="menu.php?">
    <img src="img/logo.png" />
</a>

<div class="icons"></div>

<div class=main_foot>

<div class=grey>

<img
src="img/coin.png"
title="Qızıl"
alt=""
/>

<?php echo (int)$user['qızıl']; ?>


<img
src="img/brill.png"
title="Brilliant"
alt=""
/>

<?php echo (int)$user['brılyant']; ?>


<img
src="img/energy.png"
title="Enerji"
alt=""
/>

<?php echo (int)$user['enerjı']; ?>


<?php if ($new_message_count > 0) { ?>

<a href="arxiv.php?go=goster">

<img
src="img/mektub.gif"
title="Məktub"
alt="Məktub"
/>

</a>

(<?php echo $new_message_count; ?>)

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
style="background: none repeat scroll 0 0 #888686; height: 1px;"
></div>


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

<div
style="width: <?php echo $progress; ?>%; height: 10px;"
>

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>

</div>

</div>


<div
style="background: none repeat scroll 0 0 #888686; height: 1px;"
></div>


<div class='info'>


<?php if ($vip_alindi === true) { ?>

<!-- =====================================================
     VIP ALINDI
===================================================== -->

<br>

<div class="success">

<img
src="muxtelif/okey.png"
alt=""
>

Tebrikler !!! Siz vip istifadeçi oldunuz

<br>

</div>

<br>

<div class="menu">

<br>

<li>

<a href="vip.php">

<img
src="muxtelif/dukan.png"
alt=" "
>

Vip Panel

</a>

</li>

</div>


<?php } elseif (
    isset($_GET['go']) &&
    $_GET['go'] === 'ok'
) { ?>

<!-- =====================================================
     go=ok AÇILIB, AMMA POST YOXDUR
===================================================== -->

<br>

<div class="error">

<img
src="muxtelif/eror.png"
alt=""
>

Siz artıq Vip istifadeçisiz

</div>

<br>

<div class="menu">

<br>

<li>

<a href="vip.php">

<img
src="muxtelif/dukan.png"
alt=" "
>

Vip Panel

</a>

</li>

</div>


<?php } else { ?>

<!-- =====================================================
     İLKİN VIP PANELİ
     
     vip.php AÇILANDA HƏMİŞƏ BURA GƏLİR
===================================================== -->

<br/>

<div class=center>

<div class='block_line'>

Vip istifadeçi ol

</div>

</div>

<br/>

<div class='line'></div>

<br/>

<br/>

<div class=point-line></div>

<p>

<b>

<img
src='muxtelif/star.png'
alt=''
/>

Vip istifadeçilerin xususiyyetleri

</b>

</p>

<div class=point-line></div>


<div class=battle_log>

<p>

<img
src="muxtelif/krit.png"
alt="krit"
/>

Vip Nick:

<font class="vip_account">

<font color="<?php echo $movqe_reng; ?>">

<?php echo htmlspecialchars($user_login); ?>

</font>

</font>


<br/>


<img
src="muxtelif/krit.png"
alt="krit"
/>

Tecrübe:

<span class=dark-blue>

+100%

</span>

<br/>


<img
src="muxtelif/krit.png"
alt="krit"
/>

Auto Bot Qalasına giriş:

<span class=dark-blue>

Pulsuz

</span>

<br/>

<img
src="muxtelif/krit.png"
alt="krit"
/>

Avatar:

<span class=dark-blue>

Pulsuz

</span>

<br/>


<img
src="muxtelif/krit.png"
alt="krit"
/>

Rengli yazı:

<span class=dark-blue>

Pulsuz

</span>
<br/>

</p>

</div>
<br/>

<!-- =====================================================
     VIP ALMA FORMU
===================================================== -->

<?php if ($vip_status === 1) { ?>

<div class="point-line"></div>

<p>
<b>

<img src="img/vip.png" alt=""/>

Siz vip istifadeçisiz bitmesine qalıb

<?php

$qalma_saniye = strtotime($vip_bitme_vaxti) - time();

if ($qalma_saniye < 0) {
    $qalma_saniye = 0;
}

$qalan_saat = floor($qalma_saniye / 3600);

$qalan_deqiqe = floor(
    ($qalma_saniye % 3600) / 60
);

?>

<?php echo $qalan_saat; ?> saat.
<?php echo $qalan_deqiqe; ?> deq.

</b>
</p>

<div class="point-line"></div>

<?php } else { ?>

<form
method="post"
action="vip.php?go=ok"
>

<b>Qiymet:</b>

<br/>

<select name="tipi">

<option value="0">
1 gün = 150 Brilliant
</option>

<option value="1">
1 ay = 1200 Brilliant
</option>

</select>

<br/>

<input
type="hidden"
name="action"
value="save"
/>

<br>

<input
type="submit"
class="button"
value="Vip al"
/>

</form>

<?php } ?>

<br/>


<div class='menu'>

<br/>

<div class=center></div>

</div>


<?php } ?>


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


<br/>
<br/>

<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>


<br/>


<a href="index.php?">

Çıxış
(<?php echo htmlspecialchars($user_login); ?>)

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