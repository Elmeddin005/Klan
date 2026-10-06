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
/* =========================================================
   PROFİL MƏLUMATLARI
========================================================= */

$stmt_profile = $pdo->prepare("
    SELECT
        id,
        login,
        password,
        password_plain,
        ad,
        cins,
        haqqinda,
        dogum_tarixi,
        oyuncunun_seviyyesi,
        oyuncunun_tecrubesi
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_profile->execute([
    ':id' => $my_id
]);

$profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);
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
   PROFİL MƏLUMATLARINI YENİLƏ
========================================================= */

if (isset($_GET['go']) && $_GET['go'] === 'ok') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $pass = trim($_POST['pass'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $cins = isset($_POST['cins']) ? (int)$_POST['cins'] : 1;
        $infa = trim($_POST['infa'] ?? '');

        $days = trim($_POST['days'] ?? '');
$months = trim($_POST['months'] ?? '');
$years = trim($_POST['years'] ?? '');

$dogum_tarixi = $days . '-' . $months . '-' . $years;


        if ($cins !== 0 && $cins !== 1) {
            $cins = 1;
        }

        if ($name === '') {
            $name = $profile['ad'];
        }

        if ($pass === '') {
            $pass = $profile['password_plain'];
        }

        $stmt_update = $pdo->prepare("
            UPDATE users
            SET
                password_plain = :password_plain,
                password = :password,
                ad = :ad,
                cins = :cins,
                haqqinda = :haqqinda,
                dogum_tarixi = :dogum_tarixi

            WHERE id = :id
        ");

        $stmt_update->execute([
            ':password_plain' => $pass,
            ':password' => password_hash($pass, PASSWORD_DEFAULT),
            ':ad' => $name,
            ':cins' => $cins,
            ':haqqinda' => $infa,
            ':dogum_tarixi' => $dogum_tarixi,
            ':id' => $my_id
        ]);
    }
}

?>
<!DOCTYPE>
<html >
<head>
<meta name="robots" content="ALL" /> 
<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 
<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 

<link rel="stylesheet" href="css.css">

 <meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"><title>Profil oyuncular</title>
<script>
    function goGeri() {
        window.history.back();
    }
</script>

</head><body><div class='main' style='word-wrap:break-word;'><div id="header">
 <a href="menu.php?">
    <img src="img/logo.png" />
  </a>
<div class="icons"></div>

    <div class=main_foot><div class=grey>
  <img src="img/coin.png" title="Qızıl" alt=""/> <?php echo (int)$user['qızıl']; ?>

<img src="img/brill.png" title="Brilliant" alt=""/> <?php echo (int)$user['brılyant']; ?>

<img src="img/energy.png" title="Enerji" alt=""/> <?php echo (int)$user['enerjı']; ?>
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
</div></div></div>
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

<div class='info'>

<?php if (isset($_GET['go']) && $_GET['go'] === 'ok') { ?>

Məlumat Yeniləndi<br><br>
<a href="menu.php?">Ana Səhifə</a><br>

<?php } else { ?>

<form method="post" action="profile.php?go=ok">

<b>Sizin Profil</b><br/><br/>

*Parol:<br/>
<input
    type="text"
    name="pass"
    maxlength="20"
    value="<?php echo htmlspecialchars($profile['password_plain']); ?>"
    title="pass"
    emptyok="false"
/></br>

*Adınız:<br/>
<input
    name="name"
    maxlength="15"
    value="<?php echo htmlspecialchars($profile['ad']); ?>"
    title="name"
    emptyok="false"
/><br/>

Cinsiniz:<br/>
<select name="cins">

<option value="1" <?php if ((int)$profile['cins'] === 1) echo 'selected'; ?>>
Kişi
</option>

<option value="0" <?php if ((int)$profile['cins'] === 0) echo 'selected'; ?>>
Xanım
</option>

</select><br/>

*Do&#287;um Tarixiniz:<br/>

<?php
$dogum_hisseleri = explode(
    '-',
    $profile['dogum_tarixi'] ?? '00-00-0000'
);

$dogum_gun = $dogum_hisseleri[0] ?? '00';
$dogum_ay = $dogum_hisseleri[1] ?? '00';
$dogum_il = $dogum_hisseleri[2] ?? '0000';
?>

<input
    size="2"
    name="days"
    value="<?php echo htmlspecialchars($dogum_gun); ?>"
    maxlength="2"
    format="*N"
    emptyok="false"
/>-

<input
    size="2"
    name="months"
    value="<?php echo htmlspecialchars($dogum_ay); ?>"
    maxlength="2"
    format="*N"
    emptyok="false"
/>-

<input
    size="4"
    name="years"
    value="<?php echo htmlspecialchars($dogum_il); ?>"
    maxlength="4"
    format="*N"
    emptyok="false"
/><br/>

*Haqqınızda qeydiniz:<br/>
<input
    name="infa"
    maxlength="500"
    value="<?php echo htmlspecialchars($profile['haqqinda']); ?>"
    title="infa"
    emptyok="false"
/><br/>

Auto Cavab (Mektublarda-Mesajda):<br/>
<input
    name="avtootvet"
    maxlength="200"
    value=""
    title="avtootvet"
    emptyok="true"
/><br/>

<input type="hidden" name="action" value="save"/>
<input type="submit" class="button" value="Deyiş"/><br/>

<?php } ?>


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

<a href="index.php?">Çıxış (<?php echo htmlspecialchars($user_login); ?>)</a>

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


