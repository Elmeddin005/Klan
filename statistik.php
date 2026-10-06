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

$user_id = (int)$user['id'];
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

?>
<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL" />

<meta
    name="keywords"
    content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar"
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

<title>statistika | klan.az</title>

<script>
function goGeri() {
    window.location.href = 'statistik.php?';
}
</script>


</head>

<body>

<div class="main" style="word-wrap:break-word;">

<div id="header">

<a href="menu.php?">
    <img src="img/logo.png" />
</a>

<div class="icons"></div>

<div class="main_foot">

<div class="grey">

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


<div class="info">

<?php


/* =========================================================
   1. ÜMUMİ REYTİNQ
========================================================= */

if (isset($_GET['mod']) && $_GET['mod'] === 'reyting') {

    /*
     * Ən çox təcrübəsi olan yuxarıda olacaq.
     */

    $stmt_umumi_reytinq = $pdo->prepare("
        SELECT
            id AS user_id,
            login,
            oyuncunun_seviyyesi,
            movqe,
            vip,
            oyuncunun_tecrubesi
        FROM users
        WHERE oyuncunun_tecrubesi > 0
        ORDER BY
            oyuncunun_tecrubesi DESC,
            id ASC
        LIMIT 20
    ");

    $stmt_umumi_reytinq->execute();

    $umumi_reytinq = $stmt_umumi_reytinq->fetchAll(
        PDO::FETCH_ASSOC
    );

?>

<br/>

<div class="center">

<div class="block_line">

<span class="green">
Ümumi Reytinq
</span>

</div>

</div>

<br/>

<div class="line"></div>

<p>

<?php

$sira = 1;

if (count($umumi_reytinq) > 0) {

    foreach ($umumi_reytinq as $reyting_user) {

?>

<?php echo $sira; ?>)

<?php

$reyting_movqe = (int)$reyting_user['movqe'];

if ($reyting_movqe === 2) {
    $renk = 'green';
} elseif ($reyting_movqe === 1) {
    $renk = 'red';
} elseif ($reyting_movqe === 3) {
    $renk = 'blue';
} else {
    $renk = 'white';
}

?>

<a
    href="infoforce.php?uid=<?php echo (int)$reyting_user['user_id']; ?>"
    style="
        color:<?php echo $renk; ?> !important;
        <?php if ((int)$reyting_user['vip'] === 1) { ?>
        text-decoration: underline !important;
        text-shadow: 1px 1px 1px #888;
        <?php } ?>
    "
>

<?php echo htmlspecialchars(
    $reyting_user['login'],
    ENT_QUOTES,
    'UTF-8'
); ?>

 [<?php echo (int)$reyting_user['oyuncunun_seviyyesi']; ?>]

</a>



<br>

<?php

        $sira++;

    }

} else {

?>

<center>

<span class="grey">
Hal-Hazırda Təcrübə Yoxdur.
</span>

</center>

<?php

}

?>

</p>

<br/>

<div class="line"></div>

<br/>

<a href="statistik.php?">
    <input
        type="button"
        class="button"
        value="Geri"
    >
</a>



<div class="menu">

<li>

<a href="statistik.php?">

<img
    src="muxtelif/reytinq.png"
    alt=""
/>

Top Reyting

</a>

</li>

</div>


<?php


/* =========================================================
   2. RANG
========================================================= */

} elseif (isset($_GET['mod']) && $_GET['mod'] === 'rang') {


/* Burada sonra düzəldəcəyik */


/* =========================================================
   3. QIZIL
========================================================= */

} elseif (isset($_GET['mod']) && $_GET['mod'] === 'qizil') {

    /*
     * Ən çox qızılı olan yuxarıda olacaq.
     */

    $stmt_qizil_reytinq = $pdo->prepare("
        SELECT
            id AS user_id,
            login,
            oyuncunun_seviyyesi,
            movqe,
            vip,
            qızıl
        FROM users
        WHERE qızıl > 0
        ORDER BY
            qızıl DESC,
            id ASC
        LIMIT 20
    ");

    $stmt_qizil_reytinq->execute();

    $qizil_reytinq = $stmt_qizil_reytinq->fetchAll(
        PDO::FETCH_ASSOC
    );

?>

<br/>

<div class="center">

<div class="block_line">

<span class="green">
Qızılda Liderlər
</span>

</div>

</div>

<br/>

<div class="line"></div>

<p>

<?php

$sira = 1;

if (count($qizil_reytinq) > 0) {

    foreach ($qizil_reytinq as $qizil_user) {

?>

<?php echo $sira; ?>)

<?php

$qizil_movqe = (int)$qizil_user['movqe'];

if ($qizil_movqe === 2) {
    $renk = 'green';
} elseif ($qizil_movqe === 1) {
    $renk = 'red';
} elseif ($qizil_movqe === 3) {
    $renk = 'blue';
} else {
    $renk = 'white';
}

?>

<a
    href="infoforce.php?uid=<?php echo (int)$qizil_user['user_id']; ?>"
    style="
        color: <?php echo $renk; ?> !important;
        text-decoration: none !important;
        border-bottom: 1px solid <?php echo $renk; ?> !important;
        <?php if ((int)$qizil_user['vip'] === 1) { ?>
        text-shadow: 1px 1px 1px #888;
        <?php } ?>
    "
>



<?php echo htmlspecialchars(
    $qizil_user['login'],
    ENT_QUOTES,
    'UTF-8'
); ?>

[<?php echo (int)$qizil_user['oyuncunun_seviyyesi']; ?>]

</a>

(<?php echo (int)$qizil_user['qızıl']; ?> Qızıl)

<br/>


<?php

        $sira++;

    }

} else {

?>

<center>

<span class="grey">
Hal-Hazırda Qızılda Lider Yoxdur.
</span>

</center>

<?php

}

?>

</p>

<br/>

<div class="line"></div>

<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<div class="menu">

<li>

<a href="statistik.php?">

<img
    src="muxtelif/reytinq.png"
    alt=""
/>

Top Reyting

</a>

</li>

</div>


<?php


/* =========================================================
   4. MOB
========================================================= */

} elseif (isset($_GET['mod']) && $_GET['mod'] === 'mob') {

    /*
     * BUGÜNKÜ MOB REYTİNQİ
     */

    $bugun = date('Y-m-d');

    $stmt_mob_reytinq = $pdo->prepare("
        SELECT
            mr.id,
            mr.say,
            u.id AS user_id,
            u.login,
            u.oyuncunun_seviyyesi,
            u.movqe,
            u.vip
        FROM mob_reytinq mr
        INNER JOIN users u
            ON u.id = mr.user_id
        WHERE mr.tarix = :tarix
          AND mr.say > 0
        ORDER BY
            mr.say DESC,
            mr.id ASC
        LIMIT 20
    ");

    $stmt_mob_reytinq->execute([
        ':tarix' => $bugun
    ]);

    $mob_reytinq = $stmt_mob_reytinq->fetchAll(
        PDO::FETCH_ASSOC
    );

?>

<br/>

<div class="center">

<div class="block_line">

<span class="green">
Gün ərzində ən çox mob öldürənlər
</span>

</div>

</div>

<br/>

<div class="line"></div>

<p>

<?php

$sira = 1;

if (count($mob_reytinq) > 0) {

    foreach ($mob_reytinq as $mob) {

?>

<?php echo $sira; ?>)

<?php

$mob_movqe = (int)$mob['movqe'];

if ($mob_movqe === 2) {
    $renk = 'green';
} elseif ($mob_movqe === 1) {
    $renk = 'red';
} elseif ($mob_movqe === 3) {
    $renk = 'blue';
} else {
    $renk = 'white';
}

?>

<a
    href="infoforce.php?uid=<?php echo (int)$mob['user_id']; ?>"
    style="
        color: <?php echo $renk; ?> !important;
        text-decoration: none !important;
        border-bottom: 1px solid <?php echo $renk; ?> !important;
        <?php if ((int)$mob['vip'] === 1) { ?>
        text-shadow: 1px 1px 1px #888;
        <?php } ?>
    "
>

<?php echo htmlspecialchars(
    $mob['login'],
    ENT_QUOTES,
    'UTF-8'
); ?>

 [<?php echo (int)$mob['oyuncunun_seviyyesi']; ?>]

</a>

(<?php echo (int)$mob['say']; ?> Mob)

<br/>


<?php

        $sira++;

    }

} else {

?>

<center>

<span class="grey">
Hal-Hazırda Mob Döyən Yoxdur.
</span>

</center>

<?php

}

?>

</p>

<br/>

<div class="line"></div>

<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<div class="menu">

<li>

<a href="statistik.php?">

<img
    src="muxtelif/reytinq.png"
    alt=""
/>

Top Reyting

</a>

</li>

</div>


<?php


/* =========================================================
   5. DÜŞMƏN
========================================================= */

} elseif (isset($_GET['mod']) && $_GET['mod'] === 'dushmen') {


/* Burada sonra düzəldəcəyik */


/* =========================================================
   6. AKTİV
========================================================= */

} elseif (isset($_GET['mod']) && $_GET['mod'] === 'aktiv') {

    /*
     * ƏN ÇOX AKTİV OLANLAR
     *
     * aktivlik_saniye = istifadəçinin ümumi
     * saytda keçirdiyi aktiv vaxt.
     */

    $stmt_aktiv_reytinq = $pdo->prepare("
        SELECT
            id AS user_id,
            login,
            oyuncunun_seviyyesi,
            movqe,
            vip,
            aktivlik_saniye
        FROM users
        WHERE aktivlik_saniye > 0
        ORDER BY
            aktivlik_saniye DESC,
            id ASC
        LIMIT 20
    ");

    $stmt_aktiv_reytinq->execute();

    $aktiv_reytinq = $stmt_aktiv_reytinq->fetchAll(
        PDO::FETCH_ASSOC
    );


    /*
     * Saniyəni oxunaqlı formata çevirir.
     */

    function aktivlik_reytinq_metni($saniye)
    {
        $saniye = max(0, (int)$saniye);

        $gun = intdiv($saniye, 86400);

        $qalan = $saniye % 86400;

        $saat = intdiv($qalan, 3600);

        $qalan %= 3600;

        $deqiqe = intdiv($qalan, 60);

        $hisseler = [];

        if ($gun > 0) {
            $hisseler[] = $gun . ' gün';
        }

        if ($saat > 0) {
            $hisseler[] = $saat . ' saat';
        }

        if ($deqiqe > 0) {
            $hisseler[] = $deqiqe . ' dəqiqə';
        }

        if (empty($hisseler)) {
            $hisseler[] = '0 dəqiqə';
        }

        return implode(', ', $hisseler);
    }

?>

<br/>

<div class="center">

<div class="block_line">

<span class="green">
Ən çox aktiv olanlar
</span>

</div>

</div>

<br/>

<div class="line"></div>

<p>

<?php

$sira = 1;

if (count($aktiv_reytinq) > 0) {

    foreach ($aktiv_reytinq as $aktiv_user) {

?>

<?php echo $sira; ?>)

<?php

$aktiv_movqe = (int)$aktiv_user['movqe'];

if ($aktiv_movqe === 2) {

    $renk = 'green';

} elseif ($aktiv_movqe === 1) {

    $renk = 'red';

} elseif ($aktiv_movqe === 3) {

    $renk = 'blue';

} else {

    $renk = 'white';

}

?>

<a
    href="infoforce.php?uid=<?php echo (int)$aktiv_user['user_id']; ?>"
    style="
        color: <?php echo $renk; ?> !important;
        text-decoration: none !important;
        border-bottom: 1px solid <?php echo $renk; ?> !important;
        <?php if ((int)$aktiv_user['vip'] === 1) { ?>
        text-shadow: 1px 1px 1px #888;
        <?php } ?>
    "
>

<?php echo htmlspecialchars(
    $aktiv_user['login'],
    ENT_QUOTES,
    'UTF-8'
); ?>

[<?php echo (int)$aktiv_user['oyuncunun_seviyyesi']; ?>]

</a>

(<?php echo htmlspecialchars(
    aktivlik_reytinq_metni(
        $aktiv_user['aktivlik_saniye']
    ),
    ENT_QUOTES,
    'UTF-8'
); ?>)

<br/>

<?php

        $sira++;

    }

} else {

?>

<center>

<span class="grey">
Hələ heç kim aktivlik toplamayıb.
</span>

</center>

<?php

}

?>

</p>

<br/>

<div class="line"></div>

<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<div class="menu">

<li>

<a href="statistik.php?">

<img
    src="muxtelif/reytinq.png"
    alt=""
/>

Top Reyting

</a>

</li>

</div>
<?php

/* =========================================================
   7. LIKE
========================================================= */

} elseif (isset($_GET['mod']) && $_GET['mod'] === 'like') {


/* Burada sonra düzəldəcəyik */


/* =========================================================
   8. BRİLLİANT
========================================================= */

} elseif (isset($_GET['mod']) && $_GET['mod'] === 'brilyant') {

    /*
     * Ən çox brilyantı olan yuxarıda olacaq.
     */

    $stmt_brilyant_reytinq = $pdo->prepare("
        SELECT
            id AS user_id,
            login,
            oyuncunun_seviyyesi,
            movqe,
            vip,
            brılyant
        FROM users
        WHERE brılyant > 0
        ORDER BY
            brılyant DESC,
            id ASC
        LIMIT 20
    ");

    $stmt_brilyant_reytinq->execute();

    $brilyant_reytinq = $stmt_brilyant_reytinq->fetchAll(
        PDO::FETCH_ASSOC
    );

?>

<br/>

<div class="center">

<div class="block_line">

<span class="green">
Brilyantda Liderlər
</span>

</div>

</div>

<br/>

<div class="line"></div>

<p>

<?php

$sira = 1;

if (count($brilyant_reytinq) > 0) {

    foreach ($brilyant_reytinq as $brilyant_user) {

?>

<?php echo $sira; ?>)

<?php

$brilyant_movqe = (int)$brilyant_user['movqe'];

if ($brilyant_movqe === 2) {
    $renk = 'green';   // Vampir
} elseif ($brilyant_movqe === 1) {
    $renk = 'red';     // İnsan
} elseif ($brilyant_movqe === 3) {
    $renk = 'blue';    // Neytral
} else {
    $renk = 'white';
}

?>

<a
    href="infoforce.php?uid=<?php echo (int)$brilyant_user['user_id']; ?>"
    style="
        color: <?php echo $renk; ?> !important;
        text-decoration: none !important;
        border-bottom: 1px solid <?php echo $renk; ?> !important;
        <?php if ((int)$brilyant_user['vip'] === 1) { ?>
        text-shadow: 1px 1px 1px #888;
        <?php } ?>
    "
>

<?php echo htmlspecialchars(
    $brilyant_user['login'],
    ENT_QUOTES,
    'UTF-8'
); ?>

 [<?php echo (int)$brilyant_user['oyuncunun_seviyyesi']; ?>]

</a>

(<?php echo (int)$brilyant_user['brılyant']; ?> Brilyant)

<br/>


<?php

        $sira++;

    }

} else {

?>

<center>

<span class="grey">
Hələ heç kimdə brilyant yoxdur.
</span>

</center>

<?php

}

?>

</p>

<br/>

<div class="line"></div>

<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<div class="menu">

<li>

<a href="statistik.php?">

<img
    src="muxtelif/reytinq.png"
    alt=""
/>

Top Reyting

</a>

</li>

</div>


<?php


/* =========================================================
   STATİSTİKA MENYUSU
========================================================= */

} else {

?>

<br/>

<div class="center">

<div class="block_line">

<span class="green">
Oyunda Liderlər
</span>

</div>

</div>

<br/>

<div class="line"></div>

<div class="menu">

<li>

<a href="statistik.php?mod=reyting">
Ümumi Reyting
</a>

</li>


<li>

<a href="statistik.php?mod=rang">
Rangda Liderlər
</a>

</li>


<li>

<a href="statistik.php?mod=qizil">
Qızılda Liderlər
</a>

</li>


<li>

<a href="statistik.php?mod=mob">
En çox mob öldürənlər
</a>

</li>


<li>

<a href="statistik.php?mod=dushmen">
En çox düşmən öldürənlər
</a>

</li>


<li>

<a href="statistik.php?mod=aktiv">
En çox aktiv olanlar
</a>

</li>


<li>

<a href="statistik.php?mod=like">
En çox like yığanlar
</a>

</li>


<li>

<a href="statistik.php?mod=brilyant">
En çox brilliantı olanlar
</a>

</li>

</div>

<br/>

<div class="line"></div>

<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<div class="menu"></div>


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
Çıxış (<?php echo htmlspecialchars($user_login); ?>)
</a>


<br/><br/>


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

</div>

</body>

</html>