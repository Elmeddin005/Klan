
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
$legv_sifesi = false;

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'del' &&
    !isset($_POST['action'])
) {
    $legv_sifesi = true;
}
$legv_yerine_yetirildi = false;

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'del' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {
    $stmt_legv = $pdo->prepare("
        UPDATE users
        SET avatar_id = 0
        WHERE id = :id
    ");

    $stmt_legv->execute([
        ':id' => $my_id
    ]);

    $legv_yerine_yetirildi = true;
}

/* =========================================================
   YENİ MƏKTUBLAR
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

$stmt_vip = $pdo->prepare("
    SELECT vip
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_vip->execute([
    ':id' => $my_id
]);

$vip = (int)$stmt_vip->fetchColumn();



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

$online_sayi = (int)$stmt_online_count->fetchColumn();

$emeliyyat_yerine_yetirildi = false;

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'ok' &&
    isset($_GET['idi'])
) {

    $avatar_id = (int)$_GET['idi'];

    // Avatarı tapırıq
    $stmt_avatar_al = $pdo->prepare("
        SELECT id, ad, sekil, qiymet
        FROM avatarlar
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_avatar_al->execute([
        ':id' => $avatar_id
    ]);

    $avatar_al = $stmt_avatar_al->fetch(PDO::FETCH_ASSOC);

    if (!$avatar_al) {
        header("Location: avatars.php");
        exit;
    }

    // VIP statusunu yoxlayırıq
    $stmt_vip = $pdo->prepare("
        SELECT vip
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_vip->execute([
        ':id' => $my_id
    ]);

    $vip = (int)$stmt_vip->fetchColumn();

    /*
     * VIP = 1
     * Avatar pulsuzdur.
     * Brilliant çıxılmır.
     */

    if ($vip == 1) {

        $stmt_avatar_yadda_saxla = $pdo->prepare("
            UPDATE users
            SET avatar_id = :avatar_id
            WHERE id = :id
        ");

        $stmt_avatar_yadda_saxla->execute([
            ':avatar_id' => (int)$avatar_al['id'],
            ':id' => $my_id
        ]);

        $emeliyyat_yerine_yetirildi = true;

    } else {

        /*
         * VIP deyilsə avatarın öz qiyməti çıxılır.
         */

        $qiymet = (int)$avatar_al['qiymet'];

        $stmt_al = $pdo->prepare("
            UPDATE users
            SET brılyant = brılyant - :qiymet
            WHERE id = :id
              AND brılyant >= :qiymet
        ");

        $stmt_al->execute([
            ':qiymet' => $qiymet,
            ':id' => $my_id
        ]);

        if ($stmt_al->rowCount() > 0) {

            $stmt_avatar_yadda_saxla = $pdo->prepare("
                UPDATE users
                SET avatar_id = :avatar_id
                WHERE id = :id
            ");

            $stmt_avatar_yadda_saxla->execute([
                ':avatar_id' => (int)$avatar_al['id'],
                ':id' => $my_id
            ]);

            $emeliyyat_yerine_yetirildi = true;
        }
    }
}


/* =========================================================
   AVATAR BAXIŞI
========================================================= */

$baxilan_avatar = null;

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'bax' &&
    isset($_GET['idi'])
) {

    $avatar_id = (int)$_GET['idi'];

    $stmt_avatar = $pdo->prepare("
        SELECT id, ad, sekil, qiymet
        FROM avatarlar
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_avatar->execute([
        ':id' => $avatar_id
    ]);

    $baxilan_avatar = $stmt_avatar->fetch(PDO::FETCH_ASSOC);

    if (!$baxilan_avatar) {
        header("Location: avatars.php");
        exit;
    }
}
$stmt_menim_avatar = $pdo->prepare("
    SELECT a.id, a.ad, a.sekil
    FROM users u
    LEFT JOIN avatarlar a ON a.id = u.avatar_id
    WHERE u.id = :id
    LIMIT 1
");

$stmt_menim_avatar->execute([
    ':id' => $my_id
]);

$menim_avatar = $stmt_menim_avatar->fetch(PDO::FETCH_ASSOC);
if ($legv_yerine_yetirildi) {
    $menim_avatar = null;
}

// BURADAN SONRA avatar siyahısı kodun gəlir

$sayfa = isset($_GET['page']) ? (int)$_GET['page'] : 0;

if ($sayfa < 0) {
    $sayfa = 0;
}

$limit = 20;
$baslangic = $sayfa * $limit;

$stmt_avatarlar = $pdo->prepare("
    SELECT id, ad, sekil, qiymet
    FROM avatarlar
    ORDER BY id DESC
    LIMIT :baslangic, :limit
");
$stmt_avatar_istifade = $pdo->prepare("
    SELECT avatar_id, COUNT(*) AS say
    FROM users
    WHERE avatar_id > 0
    GROUP BY avatar_id
");
$stmt_avatar_istifade->execute();

$avatar_istifade_saylari = [];

while ($row = $stmt_avatar_istifade->fetch(PDO::FETCH_ASSOC)) {
    $avatar_istifade_saylari[(int)$row['avatar_id']] = (int)$row['say'];
}

/* =========================================================
   AVATAR SİYAHISI
========================================================= */

$sayfa = isset($_GET['page']) ? (int)$_GET['page'] : 0;

if ($sayfa < 0) {
    $sayfa = 0;
}

$limit = 20;
$baslangic = $sayfa * $limit;

$stmt_avatarlar = $pdo->prepare("
    SELECT id, ad, sekil, qiymet
    FROM avatarlar
    ORDER BY id DESC
    LIMIT :baslangic, :limit
");

$stmt_avatarlar->bindValue(
    ':baslangic',
    $baslangic,
    PDO::PARAM_INT
);

$stmt_avatarlar->bindValue(
    ':limit',
    $limit,
    PDO::PARAM_INT
);

$stmt_avatarlar->execute();

$avatarlar = $stmt_avatarlar->fetchAll(PDO::FETCH_ASSOC);

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

<title>Avatarlar | Klan.Az Qanli Efsane</title>


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

<img src="img/coin.png" title="Qızıl" alt=""/>
<?php echo (int)$user['qızıl']; ?>

<img src="img/brill.png" title="Brilliant" alt=""/>
<?php echo (int)$user['brılyant']; ?>

<img src="img/energy.png" title="Enerji" alt=""/>
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
<?php if ($menim_avatar && $baxilan_avatar === null && !$emeliyyat_yerine_yetirildi && !$legv_sifesi && !$legv_yerine_yetirildi && $sayfa == 0 && $menim_avatar['id'] > 0) { ?>
<div class="battle_log">

<div class="content">

<table border="0" cellpadding="0" cellspacing="2">
<tr>

<td>
<img
width="50"
height="60"
src="<?php echo htmlspecialchars($menim_avatar['sekil']); ?>"
alt="<?php echo htmlspecialchars($menim_avatar['ad']); ?>"
/>
</td>

<td>
Sizin hal hazırki avatarınız<br/>
<a href="avatars.php?go=del">Ləğv et</a>
</td>

</tr>
</table>

</div>

</div>
<?php } ?>
<?php if ($baxilan_avatar === null && !$emeliyyat_yerine_yetirildi && !$legv_sifesi && $sayfa == 0) { ?>
<div class="line"></div>
<?php } ?>


<?php if ($legv_yerine_yetirildi) { ?>

<div class="info">
<br>
<b>Emelyat yerine yetirildi</b><br/>

<div class="menu">
<br/>
<li><a href="avatars.php?">Avatarlar</a></li>
<li><a href="infoforce.php?uid=<?php echo $my_id; ?>">Menim döyüşçüm</a></li>
</div>

</div>

<?php } elseif ($legv_sifesi) { ?>

<div class="info">

<form method="post" action="avatars.php?go=del">
Siz avatarınızı legv etmek isteyirsiz ?<br/>

<input type="hidden" name="action" value="save"/>

<input type="submit" class="button" value="Bəli"/><br/>

<div class="menu">
<br/>
<li><a href="avatars.php?">Avatarlar</a></li>
<li><a href="infoforce.php?uid=<?php echo $my_id; ?>">Menim döyüşçüm</a></li>
</div>

</form>
</div>

<?php } elseif ($emeliyyat_yerine_yetirildi) { ?>


<div class="info">



<b>Emelyat yerine yetirildi</b>

<br/>

<div class="menu">

<br/>

<li>
<a href="avatars.php?">Avatarlar</a>
</li>

<li>
<a href="infoforce.php?uid=<?php echo $my_id; ?>">
Menim döyüşçüm
</a>
</li>

</div>

</div>

<?php } elseif ($baxilan_avatar !== null) { ?>

<!-- =====================================================
     AVATAR BAXIŞ SƏHİFƏSİ
===================================================== -->


<div class="mini-line">

<div class="center">




</div>

</div>

</div>


<br/>


<table
align="center"
border="0"
cellpadding="0"
cellspacing="0"
>

<tr>

<td>

<img
width="40"
height="40"
src="img/world/empty.png"
alt="X"
/>

<br/>

<img
width="40"
height="40"
src="img/world/empty.png"
alt="X"
/>

<br/>

<img
width="40"
height="40"
src="img/world/empty.png"
alt="X"
/>

<br/>

<img
width="40"
height="40"
src="img/world/empty.png"
alt="X"
/>

</td>


<td>

<table
border="0"
cellpadding="0"
cellspacing="0"
>

<tr>

<td>

<img
width="100"
height="160"
src="<?php echo htmlspecialchars($baxilan_avatar['sekil']); ?>"
alt="<?php echo htmlspecialchars($baxilan_avatar['ad']); ?>"
/>

</td>


<td>

<table
border="0"
cellpadding="0"
cellspacing="0"
>

<tr>

<td>

<img
width="40"
height="40"
src="img/world/empty.png"
alt="X"
/>

<br/>

<img
width="40"
height="40"
src="img/world/empty.png"
alt="X"
/>

<br/>

<img
width="40"
height="40"
src="img/world/empty.png"
alt="X"
/>

<br/>

<img
width="40"
height="40"
src="img/world/empty.png"
alt="X"
/>

</td>

<td></td>

</tr>

</table>

</td>

</tr>

</table>

</td>

</tr>

</table>





<input
type="button"
class="button"
value="Geri"
onclick="goGeri()"
/>


<div class="menu">

<br/>

<li>
<a href="avatars.php?">
Avatarlar
</a>
</li>


<li>
<a href="infoforce.php?uid=<?php echo $my_id; ?>">
Menim döyüşçüm
</a>
</li>

</div>


<?php } else { ?>

<!-- =====================================================
     NORMAL AVATAR SİYAHISI
===================================================== -->
<br>

<div class="mini-line">

<div class="center">

<div class="block_line">
Avatarlar <b>[146]</b>

</div>

</div>

</div>


<br/>


<?php foreach ($avatarlar as $avatar) { ?>


<div class="battle_log">

<div class="content">

<table
border="0"
cellpadding="0"
cellspacing="2"
>

<tr>


<td>

<!-- AVATARIN ŞƏKLİNƏ BASANDA BAXIŞ SƏHİFƏSİ -->

<a href="avatars.php?go=bax&amp;idi=<?php echo (int)$avatar['id']; ?>">

<img
width="50"
height="60"
src="<?php echo htmlspecialchars($avatar['sekil']); ?>"
alt="<?php echo htmlspecialchars($avatar['ad']); ?>"
/>

</a>

</td>


<td>

<?php
$istifade_sayi = $avatar_istifade_saylari[(int)$avatar['id']] ?? 0;

if ($istifade_sayi == 0) {
    echo 'Istifade eden yoxdur';
} else {
    echo '<b>' . $istifade_sayi . ' nefer istifade edir</b>';
}
?>

<br/>


Qiymet

<?php
if ($vip === 1) {
    echo '0';
} else {
    echo (int)$avatar['qiymet'];
}
?>

brilliant


<br/>


<a href="avatars.php?go=ok&amp;idi=<?php echo (int)$avatar['id']; ?>">

Satın al

</a>


<br/>

</td>


</tr>

</table>

</div>

</div>


<?php } ?>


<hr/>

<br/>


<!-- =====================================================
     SƏHİFƏLƏR
===================================================== -->


<?php

if (count($avatarlar) == $limit) {

?>

<b>

<a href="avatars.php?page=<?php echo $sayfa + 1; ?>">

Növbeti

</a>

</b>

<br/>

<?php

}


for ($i = 0; $i < 8; $i++) {

    if ($i == $sayfa) {

        echo '<b>' . ($i + 1) . '</b>';

    } else {

        echo '<a href="avatars.php?page=' . $i . '">' . ($i + 1) . '</a>';

    }

    if ($i < 7) {
        echo ',';
    }

}

?>


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


[<b><a href="dehliz">Menu</a></b>]

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


</div>

</body>

</html>