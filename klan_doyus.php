<?php

session_start();

require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$my_id = (int)$_SESSION['user_id'];
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



/*
==========================================================
 GET PARAMETRİ
==========================================================
*/

$go = isset($_GET['go']) ? $_GET['go'] : '';


/*
|--------------------------------------------------------------------------
| OYUNU TƏRK ET
|--------------------------------------------------------------------------
*/

if ($go == 'cix') {
    header("Location:menu.php?");
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>

<meta name="robots" content="ALL" />

<meta name="keywords"
content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar" />

<meta name="description"
content="Azerbaycanda ilk Mobil Online oyunu." />

<link rel="stylesheet" href="css.css">

<meta content="text/html; charset=utf-8"
http-equiv="content-type" />

<meta name="viewport"
content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<title>Döyüş</title>

<script>
function goGeri() {
    window.history.back();
}
</script>

</head>

<body>

<div class="main" style="word-wrap:break-word;">

<!-- HEADER -->

<div id="header">

<?php
if ($go == 'qrup' || $go == 'yaz' || ($go == 'qrup' && isset($_GET['hazir']))) {
?>

    <a href="klan_doyus.php?go=qrup">
        <img src="img/logo.png">
    </a>

<?php
} else {
?>

    <a href="menu.php?">
        <img src="img/logo.png">
    </a>

<?php
}
?>

    <div class="icons"></div>

    <div class="main_foot">
        <div class="grey">

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
<div style="background:#888686; height:1px;"></div>


<?php

/*
|--------------------------------------------------------------------------
| HAZIR MESAJI
|--------------------------------------------------------------------------
| go=qrup&hazir=1 olduqda 24%-in ALTINDA görünür.
| Progress bar-a toxunmur.
|--------------------------------------------------------------------------
*/

if ($go == 'qrup' && isset($_GET['hazir'])) {

    $doyuscu_sayi = 2;

    if ($doyuscu_sayi < 3) {

?>

<div style="text-align:left; padding:5px 3px;">
    Döyüşə başlamaq üçün klanınızdan minimum 3 döyüşçü olmalıdır.
</div>

<?php

    } else {

?>

<div style="text-align:center; padding:5px 3px;">
    Klan döyüşə hazırdır.
</div>

<?php

    }
}

?>


<?php

/*
|--------------------------------------------------------------------------
| YENİ KLAN DÖYÜŞÜ
|--------------------------------------------------------------------------
*/

if ($go == 'neww') {

?>

<div class="info">

<form method="post" action="klan_doyus.php?go=qrup">

    <b>Oyunçu Tutumu</b>
    <br/>

    <select name="bb">

        <option value="6">6</option>
        <option value="8">8</option>
        <option value="10">10</option>
        <option value="12">12</option>
        <option value="16">16</option>

    </select>

    <br/><br/>

    <b>Minimum Merhele:</b>
    <br/>

    <select name="nn">

        <option value="nn">Limitsiz</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>
        <option value="11">11</option>

    </select>

    <br/><br/>

    <b>Maxsimum Merhele:</b>
    <br/>

    <select name="mm">

        <option value="0">Limitsiz</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>

    </select>

    <br/><br/>

    <b>Qızıl:</b>
    <br/>

    <select name="aa">

        <option value="0">Qızılsız</option>
        <option value="10">10 Qızıl</option>
        <option value="50">50 Qızıl</option>

    </select>

    <br/><br/>

    <input type="hidden"
           name="action"
           value="save"/>

    <input type="submit"
           class="button"
           value="Ok"/>

    <br/>

    <hr/>

    <input type="button"
           class="button"
           value="Geri"
           onclick="goGeri()"/>

</form>

</div>


<?php

/*
|--------------------------------------------------------------------------
| QRUP SƏHİFƏSİ
|--------------------------------------------------------------------------
*/

} elseif ($go == 'qrup' || $go == 'yaz') {

?>

<div class="info">

<div class="center">

    <div class="block_line">

        <img src="klan_img/1.gif" alt="Klan"/>

        YaLQuZaQ05

    </div>

</div>

<br/>

<div class="menu">

    <li>
        <a href="klan_doyus.php?go=qrup&hazir=1&lis=32324564">
            Klan döyüşə hazırdır
        </a>
    </li>

    <li>
        <a href="klan_doyus.php?go=qrup">
            Səhifəni Yenilə
        </a>
    </li>

    <li>
        <a href="klan_doyus.php?go=cagir&lis=32324564">
            Döyüşçü çağır
        </a>
    </li>

    <li>
        <a href="klan_doyus.php?go=cix&lis=32324564">
            Oyunu tərk et
        </a>
    </li>

</div>


<div class="center">

    <div class="block_line">
        <b>Döyüş Məlumatları</b>
    </div>

</div>

<br/>

<b>Döyüşçü tutumu:</b> 6
<br/>

<b>Qoyulan qızıl:</b> 0
<br/>

<b>Qızıl fondu:</b> 0
<br/>

<b>Minimum mərhələ:</b> Limitsiz
<br/>

<b>Maksimum mərhələ:</b> Limitsiz
<br/>


<div class="center">

    <div class="block_line">

        <img src="klan_img/1.gif" alt="Klan"/>

        YaLQuZaQ05

    </div>

</div>

<br/>


<!-- YUXARIDAKI OYUNÇU -->

<div class="point-line"></div>

<p>

    <img src="muxtelif/gold.png" alt=" "/>

    <a href="infoforce.php?uid=1000749">

        <font color="green">
            <u>***YALQUZAQ*** [14]</u>
        </font>

    </a>

</p>

<div class="point-line"></div>

<br/>

<i>Rəqib klan yoxdur...</i>

<br/>

<div class="point-line"></div>

<br/>


<!-- MESAJ FORMU -->

<form method="post"
      action="klan_doyus.php?go=yaz&lis=32324564">

    <input name="message"
           value=""
           maxlength="300"/>

    <br/>

    <input type="submit"
           class="button"
           value="Gönder"/>

</form>

<hr>


<!-- MESAJ BURADA GÖRÜNƏCƏK -->

<?php

if (isset($_POST['message']) && trim($_POST['message']) != '') {

    $message = htmlspecialchars(
        trim($_POST['message']),
        ENT_QUOTES,
        'UTF-8'
    );

?>

<p>

    <a href="infoforce.php?uid=1000749">

        <font color="green">
            <u>***YALQUZAQ*** [14]</u>
        </font>

    </a>

    <b>»</b>

    <?php echo ' ' . $message; ?>

</p>

<?php

}

?>

</div>


<?php

/*
|--------------------------------------------------------------------------
| ƏSAS KLAN DÖYÜŞLƏRİ SƏHİFƏSİ
|--------------------------------------------------------------------------
*/

} else {

?>

<div class="info">

    <div class="menu">

        <br/>

        <div class="center">

            <div class="block_line">

                <b>Klan Döyüşləri</b>

            </div>

        </div>

        <br/>
        <br/>

        <div class="line"></div>

        <li>

            <a href="klan_doyus.php?go=neww">

                [Yeni Klan Döyüşü Yarad]

            </a>

        </li>

        <br/>

    </div>

</div>

<?php

}

?>


<!-- ===================================== -->
<!-- FOOTER -->
<!-- ===================================== -->

<div class="main_foot">

    <div class="center">

        <div class="grey">

            <div class="small">

                <div class="foot">

<?php
if ($go == 'qrup' || $go == 'yaz') {
?>

    [<b>
        <a href="klan_doyus.php?go=qrup">Menu</a>
    </b>]

<?php
} else {
?>

    [<b>
        <a href="menu.php?">Menu</a>
    </b>]

<?php
}
?>

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

                    <a href="cixis.php?">
                        Çıxış (***YALQUZAQ***)
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
                        © Klanaz.com 2026
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

</div>

</body>
</html>