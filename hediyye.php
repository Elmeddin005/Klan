<?php
session_start();
require_once "config.php";
require_once "user_data.php";

$my_id = (int)($_SESSION['user_id'] ?? 0);

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

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;

$page = isset($_GET['s']) ? (int)$_GET['s'] : 1;

if ($page < 1) {
    $page = 1;
}

$total_gifts = 109;
$per_page = 10;

$total_pages = ceil($total_gifts / $per_page);

if ($page > $total_pages) {
    $page = $total_pages;
}

$start = (($page - 1) * $per_page) + 1;
$end = min($start + $per_page - 1, $total_gifts);


/* =========================
   HƏDİYYƏ SEÇİMİ
   ========================= */

$h = isset($_GET['h']) ? (int)$_GET['h'] : 0;

$sekil = '';

if ($h > 0) {

    if (file_exists('hediyye/' . $h . '.gif')) {

        $sekil = 'hediyye/' . $h . '.gif';

    } elseif (file_exists('hediyye/' . $h . '.png')) {

        $sekil = 'hediyye/' . $h . '.png';

    } elseif (file_exists('hediyye/' . $h . '.jpg')) {

        $sekil = 'hediyye/' . $h . '.jpg';
    }
}

$secili_hediyye = ($h > 0 && $sekil != '');


/* =========================
   OK BASILDI
   ========================= */

$gonderildi = false;

$alici_nik = 'Vahidou';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {

    $gonderildi = true;

    if (isset($_POST['h_nik']) && trim($_POST['h_nik']) != '') {
        $alici_nik = trim($_POST['h_nik']);
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

<title>Hediyyeler</title>

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

<img src="img/coin.png" title="Qızıl" alt=''/> 199 367 053

<img src="img/brill.png" title="Brilliant" alt=''/> 16503

<img src="img/energy.png" title="Enerji" alt=''/> 50
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
<b>25%</b>
</span>

</div>
</div>

<div class="experience">

<div class="exp_bg">

<div class="exp_left fl"></div>

<div class="exp_right fr"></div>

<div style="width: 25.00%; height: 10px;">

<div class="exp_line"></div>
<div class="exp_point"></div>

</div>

</div>
</div>

<div style="background: none repeat scroll 0 0 #888686; height: 1px;"></div>


<div class="info">


<?php if ($gonderildi): ?>

<!-- =================================
     HƏDİYYƏ GÖNDƏRİLDİ
     ================================= -->

Hediyyeniz gönderildi!, Teşekkür edirik!

<br>
<hr>

<a href="infoforce.php?uid=<?php echo $uid; ?>">
<?php echo htmlspecialchars($alici_nik); ?>
</a>


<?php elseif ($secili_hediyye): ?>

<!-- =================================
     SEÇİLMİŞ HƏDİYYƏ
     ================================= -->

<br/>

<img
src="<?php echo htmlspecialchars($sekil); ?>"
alt="<?php echo $h; ?>"
/>

<br/>

<hr/>

<form
method="post"
action="hediyye.php?uid=<?php echo $uid; ?>&amp;h=<?php echo $h; ?>"
>

Hediyye göndərəceyiniz Legeb.<br/>

<input
type="text"
size="12"
name="h_nik"
class="text long"
maxlength="25"
value="Vahidou"
/>

<br/>

Ürek sözünüz.<br/>

<input
type="text"
size="12"
name="text"
class="text long"
maxlength="600"
value=""
/>

<br/>

<input
type="hidden"
name="action"
value="save"
/>

<input
type="submit"
class="button"
value="OK"
/>

<br/>

</form>

<br/>
<hr/>

<a href="hediyye.php?uid=<?php echo $uid; ?>&amp;go=hediyye">
Hediyye seç
</a>


<?php else: ?>

<!-- =================================
     HƏDİYYƏLƏR SİYAHISI
     ================================= -->

Cemi: (<b><?php echo $total_gifts; ?></b>) hediyye var.

<br/>
<hr/>


<?php

for ($i = $start; $i <= $end; $i++) {

    $sekil_list = '';

    if (file_exists('hediyye/' . $i . '.gif')) {

        $sekil_list = 'hediyye/' . $i . '.gif';

    } elseif (file_exists('hediyye/' . $i . '.png')) {

        $sekil_list = 'hediyye/' . $i . '.png';

    } elseif (file_exists('hediyye/' . $i . '.jpg')) {

        $sekil_list = 'hediyye/' . $i . '.jpg';
    }


    if ($sekil_list != '') {

?>

<?php echo $i; ?>.-

<a href="hediyye.php?h=<?php echo $i; ?>&amp;uid=<?php echo $uid; ?>">

<img
src="<?php echo htmlspecialchars($sekil_list); ?>"
alt="<?php echo $i; ?>"
/>

</a>

<div class="line"></div>

<?php

    }

}

?>


<?php if ($page < $total_pages): ?>

<br/>

<a href="hediyye.php?go=hediyye&amp;s=<?php echo $page + 1; ?>&amp;uid=<?php echo $uid; ?>">

&gt;&gt;

<?php echo $end + 1; ?>-<?php echo min($end + $per_page, $total_gifts); ?>

&gt;&gt;

</a>

<?php endif; ?>


<?php if ($page > 1): ?>

<br/>

<a href="hediyye.php?go=hediyye&amp;s=<?php echo $page - 1; ?>&amp;uid=<?php echo $uid; ?>">

&lt;&lt;

<?php echo $start - $per_page; ?>-<?php echo $start - 1; ?>

&lt;&lt;

</a>

<?php endif; ?>


<?php endif; ?>


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

<a href="http://klanaz.com/klan/" class="xgame.az">
&#169; Klanaz.com 2026
</a>

</div>
</div>
</div>
</div>

</div>

</body>
</html>