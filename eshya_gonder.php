<?php

session_start();

require_once "config.php";
require_once "user_data.php";

/* =====================================================
   GİRİŞ YOXLAMASI
===================================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];


/* =====================================================
   QARŞI TƏRƏF
===================================================== */

$uid = (int)($_GET['uid'] ?? 0);

if ($uid <= 0 || $uid === $my_id) {
    exit('İstifadəçi düzgün deyil.');
}


/* =====================================================
   QARŞI TƏRƏFİN MƏLUMATLARI
===================================================== */

$stmt_user = $pdo->prepare("
    SELECT
        id,
        login
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_user->execute([
    ':id' => $uid
]);

$qarshi_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$qarshi_user) {
    exit('İstifadəçi tapılmadı.');
}


/* =====================================================
   SƏHİFƏLƏMƏ
===================================================== */

$s = isset($_GET['s']) ? (int)$_GET['s'] : 0;

if ($s < 0) {
    $s = 0;
}


/* =====================================================
   İSTİFADƏÇİNİN ÇANTASINDAKI ƏŞYALAR
===================================================== */

$stmt_esyalar = $pdo->prepare("
    SELECT
        c.id AS canta_id,
        c.esya_id,
        c.say,

        e.ad,
        e.img,
        e.reng

    FROM canta AS c

    INNER JOIN esyalar AS e
        ON e.id = c.esya_id

    WHERE c.user_id = :user_id
      AND c.say > 0

    ORDER BY c.id DESC

    LIMIT :baslangic, 10
");

$stmt_esyalar->bindValue(
    ':user_id',
    $my_id,
    PDO::PARAM_INT
);

$stmt_esyalar->bindValue(
    ':baslangic',
    $s,
    PDO::PARAM_INT
);

$stmt_esyalar->execute();

$cantadaki_esyalar =
    $stmt_esyalar->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   GÖNDƏRİLƏCƏK ƏŞYALAR
===================================================== */

foreach ($cantadaki_esyalar as $esya) {

    $canta_id = (int)$esya['canta_id'];
    $esya_id  = (int)$esya['esya_id'];
    $say      = (int)$esya['say'];

    if ($canta_id <= 0 || $esya_id <= 0 || $say <= 0) {
        continue;
    }

}
/* =====================================================
   NÖVBƏTİ SƏHİFƏ VARMI?
===================================================== */

$stmt_say = $pdo->prepare("
    SELECT COUNT(*)
    FROM canta AS c

    INNER JOIN esyalar AS e
        ON e.id = c.esya_id

    WHERE c.user_id = :user_id
      AND c.say > 0
");

$stmt_say->execute([
    ':user_id' => $my_id
]);

$canta_esya_sayi =
    (int)$stmt_say->fetchColumn();

$novbeti_say =
    $s + 10;

$novbeti_var =
    $novbeti_say < $canta_esya_sayi;

?>
<?php

/* =====================================================
   ƏŞYA GÖNDƏRMƏYƏ BAŞLA
   idi = canta.id
===================================================== */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'ok'
) {

    /* -------------------------------------------------
       QARŞI TƏRƏF
    ------------------------------------------------- */

    $uid = (int)($_GET['uid'] ?? 0);

    if ($uid <= 0 || $uid === $my_id) {
        exit('Qarşı tərəf düzgün deyil.');
    }


    /* -------------------------------------------------
       CANTA ID
       Burada idi artıq ESYA ID deyil.
       idi = canta.id
    ------------------------------------------------- */

    $canta_id = (int)($_GET['idi'] ?? 0);

    if ($canta_id <= 0) {
        exit('Çanta ID-si düzgün deyil.');
    }


    /* -------------------------------------------------
       GÖNDƏRƏNİN ÇANTASINDAN ƏŞYANI TAP
    ------------------------------------------------- */

    $stmt_canta = $pdo->prepare("
        SELECT
            c.id AS canta_id,
            c.user_id,
            c.esya_id,
            c.say

        FROM canta AS c

        WHERE c.id = :canta_id
          AND c.user_id = :user_id
          AND c.say > 0

        LIMIT 1

        FOR UPDATE
    ");

    $stmt_canta->execute([
        ':canta_id' => $canta_id,
        ':user_id' => $my_id
    ]);

    $canta = $stmt_canta->fetch(PDO::FETCH_ASSOC);


    /* -------------------------------------------------
       ÇANTA SƏTRİ TAPILMADI
    ------------------------------------------------- */

    if (!$canta) {
        exit(
            'Bu əşya sizin çantanızda yoxdur.'
        );
    }


    /* -------------------------------------------------
       DÜZGÜN ESYA ID
       ARTİQ BURADA BİLİNİR
    ------------------------------------------------- */

    $gonderilen_esya_id =
        (int)$canta['esya_id'];


    if ($gonderilen_esya_id <= 0) {
        exit(
            'Əşyanın ID-si düzgün deyil.'
        );
    }


  /* =====================================================
   ƏŞYA GÖNDƏRİŞİNİ YARAT
===================================================== */

$qiymet = isset($_POST['alish_max'])
    ? (int)$_POST['alish_max']
    : 0;

if ($qiymet < 0) {
    $qiymet = 0;
}

$stmt_gonder = $pdo->prepare("
    INSERT INTO esya_gondermeler
    (
        esya_id,
        canta_id,
        gonderen_id,
        alan_id,
        qiymet,
        yaradilis_vaxti,
        status
    )
    VALUES
    (
        :esya_id,
        :canta_id,
        :gonderen_id,
        :alan_id,
        :qiymet,
        :yaradilis_vaxti,
        0
    )
");

$stmt_gonder->execute([
    ':esya_id'        => (int)$canta['esya_id'],
    ':canta_id'       => (int)$canta['canta_id'],
    ':gonderen_id'    => $my_id,
    ':alan_id'        => $uid,
    ':qiymet'         => $qiymet,
    ':yaradilis_vaxti'=> time()
]);
header("Location: menu.php");
exit;
}

?>


<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN"
"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">

<html xmlns="http://www.w3.org/1999/xhtml"
      xml:lang="az"
      lang="az">

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

<link
    rel="stylesheet"
    href="css.css"
    type="text/css"
/>

<meta
 content="text/html; charset=utf-8"
 http-equiv="content-type"
/>

<meta
name="viewport"
content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"

>

<title>esya gonder</title>

<script>

function goGeri()
{
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


<a href="menu.php">

    <img
        alt="macera.az"
        id="logo"
        src="img/logo.png"
    />

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

        <b>
            <?php echo $progress; ?>%
        </b>

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

<div class="center">


<div class="block_line">

    <?php echo htmlspecialchars(
        $qarshi_user['login'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>

    ləqəbli şəxsə eşya göndər

</div>


</div>

<br/>

<b>
    Diqqət: Qiymət 0 yazıldıqda qarşı tərəfə eşya hədiyyə olaraq göndərilir
</b>

<?php if (!$cantadaki_esyalar) { ?>


<br/><br/>

<b>
    Çantanızda göndəriləcək əşya yoxdur.
</b>


<?php } else { ?>

<?php foreach ($cantadaki_esyalar as $esya) { ?>

<div class="battle_log">

<div class="content">

<table
    border="0"
    cellpadding="0"
    cellspacing="0"
>

<tr>

<td>


<img
    src="<?php echo htmlspecialchars(
        $esya['img'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
    alt="foto"
/>


</td>

<td>

<u>

<a
href="eshyalar.php?go=mod_info&rid=<?php echo (int)$esya['esya_id']; ?>"

>

<font
style="color: <?php echo htmlspecialchars(
     $esya['reng'] ?: '#0000FF',
     ENT_QUOTES,
     'UTF-8'
 ); ?>"

>

<?php echo htmlspecialchars(
    $esya['ad'],
    ENT_QUOTES,
    'UTF-8'
); ?>

</font>

</a>

</u>

<?php if ((int)$esya['say'] > 1) { ?>


(<?php echo (int)$esya['say']; ?>)


<?php } ?>

<br/>

<form
    method="post"
    action="eshya_gonder.php?go=ok&amp;uid=<?php echo $uid; ?>&amp;idi=<?php echo (int)$esya['canta_id']; ?>"
>


Təklif edəcəyiniz qiymət

<br/>

<input
    type="text"
    size="4"
    name="alish_max"
    maxlength="8"
    value="0"
/>

<input
    type="submit"
    class="button_small"
    value="OK"
/>


</form>

</td>

</tr>

</table>

</div>

</div>

<?php } ?>

<div class="menu">

<?php if ($novbeti_var) { ?>


<br/>

<li>

    <a
        href="eshya_gonder.php?go=eshya&amp;uid=<?php echo $uid; ?>&amp;s=<?php echo $novbeti_say; ?>"
    >

        <img
            src="img/go_next.png"
            alt=""
        />

        Növbəti

    </a>

</li>


<?php } ?>

</div>

<?php } ?>

<div class="line"></div>

<div class="menu">


<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<li>

    <a
        href="infoforce.php?uid=<?php echo $my_id; ?>"
    >

        <img
            src="muxtelif/doyuscu1.png"
            alt=""
        />

        Mənim döyüşçüm

    </a>

</li>


</div>

</div>

<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">

[<b> <a href="menu.php?">Menu</a> </b>]

[<b> <a href="axtar.php?">Axtarış</a> </b>]

[ <a href="forum/mozu2.php?">Forum</a>
]

[ <a href="shexsi_sehife.php?">Qurğular</a>
]

<br/><br/>

<img
 alt="."
 height="15"
 src="muxtelif/saat.ico"
 title="vaxt"
 width="15"
/>

<?php echo date("H:i", time()); ?>

<br/>

<a href="index.php?">

Çıxış
(<?php echo htmlspecialchars(
 $user['login'],
 ENT_QUOTES,
 'UTF-8'
); ?>)

</a>

<br/><br/>

<a href="menu.php?dil=tr">

Türkce:

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
