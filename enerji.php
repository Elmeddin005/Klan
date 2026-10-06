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


/* =========================================================
   ENERJİ ALMA
========================================================= */

if (isset($_GET['go']) && ($_GET['go'] === '25' || $_GET['go'] === '50')) {

    $hazirki_enerji = (int)$user['enerjı'];

    if ($hazirki_enerji >= 10) {

        $enerji_mesaj = "Sizin 10-dan çox enerjiniz var.";

    } else {

        if ($_GET['go'] === '25') {

            $alınacaq_enerji = 25;
            $qiymet = 7;

        } else {

            $alınacaq_enerji = 50;
            $qiymet = 15;

        }

        if ((int)$user['brılyant'] < $qiymet) {

            $enerji_mesaj = "Sizin kifayət qədər brilyantınız yoxdur.";

        } else {

            $stmt_al = $pdo->prepare("
              UPDATE users
SET brılyant = brılyant - :qiymet,
    enerjı = LEAST(enerjı + :enerji, 50)
WHERE id = :id
            ");

            $stmt_al->execute([
                ':qiymet' => $qiymet,
                ':enerji' => $alınacaq_enerji,
                ':id' => $my_id
            ]);

            $enerji_mesaj = "Təbriklər Siz {$alınacaq_enerji} enerji Aldınız.";
        }
    }
}

?>


<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL" /> 

<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 

<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 


<link rel="stylesheet" href="css.css">

<meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<title>Enerji panel</title>

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


<?php if ($new_message_count > 0) { ?>

<a href="arxiv.php?go=goster">
    <img src="img/mektub.gif" title="Məktub" alt="Məktub"/>
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
     ENERJİ HİSSƏSİ
===================================================== -->

<div class='info'>


<?php if (isset($enerji_mesaj)) { ?>



<b><?php echo htmlspecialchars($enerji_mesaj); ?></b>
<br><br>
<div class="line"></div>
<br/>


<div class="menu">

<li>
<a href="enerji.php?">Enerji Panel</a>
</li>

</div>


<?php } elseif (isset($_GET['go']) && $_GET['go'] === '25_t') { ?>


</br>

<div class="center">

<div class="block_line">

<span class="green">Enerji al</span>

</div>

</div>

<br/>


Siz 7 brilliant qarşılığında 25 enerji alırsız


<div class="menu">

<li>
<a href="enerji.php?go=25">Al</a>
</li>

<li>
<a href="enerji.php?">Geri</a>
</li>

</div>





<div class="line"></div>


<div class="menu">

<li>
<a href="enerji.php?">Enerji Panel</a>
</li>

</div>


<?php } elseif (isset($_GET['go']) && $_GET['go'] === '50_t') { ?>


<br/>

<div class="center">

<div class="block_line">

<span class="green">Enerji al</span>

</div>

</div>

<br/>


Siz 15 brilliant qarşılığında 50 enerji alırsız


<div class="menu">

<li>
<a href="enerji.php?go=50">Al</a>
</li>

<li>
<a href="enerji.php?">Geri</a>
</li>

</div>



<div class="line"></div>


<div class="menu">

<li>
<a href="enerji.php?">Enerji Panel</a>
</li>

</div>


<?php } else { ?>


<br/>

<div class="center">

<div class="block_line">

<span class="green">Enerji al</span>

</div>

</div>

<br/>

<div class="line"></div>


<div class="menu">

<li>
<a href="enerji.php?go=25_t">25 enerji 7 brilliant</a>
</li>

<li>
<a href="enerji.php?go=50_t">50 enerji 15 brilliant</a>
</li>

<li>
</li>

</div>

<br>

<div class="line"></div>


<div class="menu">

<li>
<a href="enerji.php?">Enerji Panel</a>
</li>

</div>


<?php } ?>


</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">
    
[<b><a href="menu.php?">Menu</a></b>] 

[<b><a href="axtar.php?">Axtarış</a></b>] 

[<a href="forum/mozu2.php?">Forum</a>] 

[<a href="shexsi_sehife.php?">Qurğular</a>]

<br/>
<br/>
    
<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>

<br/>

<a href="index.php?">
Çıxış (<?php echo htmlspecialchars($user_login); ?>)
</a>

<br/>
<br/>  
    

<a href="menu.php?dil=tr">

Türkce:

<img alt="türkce" src="http://macera.az/klan/muxtelif/tr.gif" title="Türkce"/>

</a>

<br/>

Sciript name: Qanlı efsane(modern version)

<br/>
    
<a href="http://klanaz.com/klan/" class="xgame.az">
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