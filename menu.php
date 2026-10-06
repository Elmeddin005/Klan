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
$qebul_neticesi = false;

if (isset($_GET['qebul']) && (int)$_GET['qebul'] === 1) {
    $qebul_neticesi = true;
}

$my_id = (int)$_SESSION['user_id'];


/* =========================================================
   QRUP DÖYÜŞÜ DƏVƏTLƏRİ
   QRUP BAZASINDAN DƏVƏTLƏRİ GƏTİR
========================================================= */

$qrup_devetleri = [];

$qrupPdo = new PDO(
    "mysql:host=localhost;dbname=qrup_doyus;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

$stmt_qrup_devetleri = $qrupPdo->prepare("
    SELECT
        qd.id,
        qd.qrup_id,
        qd.gonderen_id,
        qd.alan_id,
        qd.status,
        qd.yaradildi
    FROM qrup_devetleri qd
    WHERE qd.alan_id = :alan_id
      AND qd.status = 0
    ORDER BY qd.id DESC
");

$stmt_qrup_devetleri->execute([
    ':alan_id' => $my_id
]);

$qrup_devetleri = $stmt_qrup_devetleri->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   DƏVƏT GÖNDƏRƏN OYUNÇULARIN USER MƏLUMATLARI
   users əsas bazadadır
========================================================= */

foreach ($qrup_devetleri as &$devet) {

    $stmt_devet_user = $pdo->prepare("
        SELECT
            login,
            oyuncunun_seviyyesi
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_devet_user->execute([
        ':id' => (int)$devet['gonderen_id']
    ]);

    $devet_user = $stmt_devet_user->fetch(PDO::FETCH_ASSOC);

    if ($devet_user) {

        $devet['gonderen_login'] =
            $devet_user['login'];

        $devet['gonderen_level'] =
            (int)$devet_user['oyuncunun_seviyyesi'];

    } else {

        $devet['gonderen_login'] = 'Naməlum';
        $devet['gonderen_level'] = 0;
    }
}

unset($devet);



/* =========================================================
   GÖNDƏRDİYİM DUEL QƏBUL EDİLİBSƏ FIGHT.PHP-YƏ KEÇ
========================================================= */

$stmt_qebul_duel = $pdo->prepare("
    SELECT id
    FROM duel
    WHERE (oyuncu1_id = :my_id OR oyuncu2_id = :my_id)
      AND qebul_edildi = 1
      AND qalib_id IS NULL
      AND bitdi = 0
    ORDER BY id DESC
    LIMIT 1
");


$stmt_qebul_duel->execute([
    ':my_id' => $my_id
]);

$qebul_edilmis_duel = $stmt_qebul_duel->fetch(PDO::FETCH_ASSOC);

if (
    $qebul_edilmis_duel &&
    !isset($_GET['hec_hece']) &&
    !$qebul_neticesi
) {

    header(
        "Location: fight.php?duel_id=" .
        (int)$qebul_edilmis_duel['id']
    );

    exit;
}

/* =========================================================
   GƏLƏN DUELİ YOXLAYIRIQ
========================================================= */

$stmt_gelen_duel = $pdo->prepare("
    SELECT
        d.id,
        d.oyuncu1_id,
        d.oyuncu2_id,
        d.yaradilis_tarixi,

        p1.login AS player1_login,
        p1.oyuncunun_seviyyesi AS player1_level

    FROM duel d

    INNER JOIN users p1
        ON p1.id = d.oyuncu1_id

 WHERE d.oyuncu2_id = :my_id
  AND d.qalib_id IS NULL
  AND d.bitdi = 0
    ORDER BY d.id DESC

    LIMIT 1
");


$stmt_gelen_duel->execute([
    ':my_id' => $my_id
]);

$gelen_duel = $stmt_gelen_duel->fetch(PDO::FETCH_ASSOC);

$gelen_duel_remaining = 0;

if (isset($_GET['hec_hece']) && (int)$_GET['hec_hece'] === 1) {
    $gelen_duel = false;
    $gelen_duel_remaining = 0;
}


/* =========================================================
   180 SANİYƏLİK DUEL SAYĞACI
========================================================= */

if ($gelen_duel) {

   $created_time = strtotime($gelen_duel['yaradilis_tarixi']);


    $gelen_duel_remaining =
        180 - (time() - $created_time);


    /* 180 saniyə bitibsə */
    if ($gelen_duel_remaining <= 0) {

        $stmt_delete_duel = $pdo->prepare("
            DELETE FROM duel
            WHERE id = :id
             AND oyuncu2_id = :my_id
AND qalib_id IS NULL

            LIMIT 1
        ");

        $stmt_delete_duel->execute([
            ':id'    => $gelen_duel['id'],
            ':my_id' => $my_id
        ]);

        $gelen_duel = false;
        $gelen_duel_remaining = 0;
    }
}

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
$qalan_guc_bonus = 0;

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
   GÜC BONUSLARI - MENU ÜÇÜN
========================================================= */

/*
 * Cari səviyyəyə qədər bütün mərhələ bonuslarının cəmi
 */
$stmt_umumi_merhele_bonus = $pdo->prepare("
    SELECT COALESCE(SUM(bonus), 0)
    FROM merhele_bonuslari
    WHERE merhele <= :merhele
");

$stmt_umumi_merhele_bonus->execute([
    ':merhele' => $oyuncu_seviyyesi
]);

$umumi_guc_bonus = (int)$stmt_umumi_merhele_bonus->fetchColumn();

if ($qalan_guc_bonus < 0) {
    $qalan_guc_bonus = 0;
}

/* =========================================================
   OYUNÇUNUN İNDİYƏ QƏDƏR VERDİYİ GÜC BONUSU
========================================================= */

$stmt_verilen_bonus = $pdo->prepare("
    SELECT
        zerbe,
        can,
        krit,
        uvorot
    FROM oyuncu_guc_bonuslari
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_verilen_bonus->execute([
    ':user_id' => $my_id
]);

$verilen_bonus = $stmt_verilen_bonus->fetch(PDO::FETCH_ASSOC);

$istifade_edilen_bonus = 0;

if ($verilen_bonus) {
    $istifade_edilen_bonus =
        (int)$verilen_bonus['zerbe'] +
        (int)$verilen_bonus['can'] +
        (int)$verilen_bonus['krit'] +
        (int)$verilen_bonus['uvorot'];
}
$qalan_guc_bonus = $umumi_guc_bonus - $istifade_edilen_bonus;

if ($qalan_guc_bonus < 0) {
    $qalan_guc_bonus = 0;
}


/*
 * Növbəti mərhələnin bonusu
 */
$novbeti_merhele = $oyuncu_seviyyesi + 1;

$stmt_novbeti_bonus = $pdo->prepare("
    SELECT COALESCE(SUM(bonus), 0)
    FROM merhele_bonuslari
    WHERE merhele = :merhele
");

$stmt_novbeti_bonus->execute([
    ':merhele' => $novbeti_merhele
]);

$novbeti_merhele_bonus = (int)$stmt_novbeti_bonus->fetchColumn();


/* =========================================================
   NÖVBƏTİ MƏRHƏLƏNİN VERƏCƏYİ BONUS
========================================================= */

$novbeti_merhele = $oyuncu_seviyyesi + 1;

$stmt_novbeti_bonus = $pdo->prepare("
    SELECT COALESCE(SUM(bonus), 0)
    FROM merhele_bonuslari
    WHERE merhele = :merhele
");

$stmt_novbeti_bonus->execute([
    ':merhele' => $novbeti_merhele
]);

$novbeti_merhele_bonus = (int)$stmt_novbeti_bonus->fetchColumn();

?>
<!DOCTYPE html>
<html>
<head>
<meta name="robots" content="ALL" /> 
<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 
<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 

<link rel="stylesheet" href="css.css">

 <meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"><title>Klan.Az Qanlı Efsane-Azerbaycanın ilk WAP Onlayn Oyunu</title>
<script>
    function goGeri() {
        window.history.back();
    }
</script>

</head><body><div class='main' style='word-wrap:break-word;'><div id="header">
  <?php if (isset($_GET['hec_hece']) && (int)$_GET['hec_hece'] === 1): ?>

<a href="menu.php?hec_hece=1">
    <img src="img/logo.png" />
</a>

<?php else: ?>

<a href="menu.php">
    <img src="img/logo.png" />
</a>

<?php endif; ?>  </a>
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

<?php
/* =========================================================
  QRUP DÖYÜŞÜ DƏVƏTLƏRİ
========================================================= */
 if (!empty($qrup_devetleri)) { ?>

    <?php foreach ($qrup_devetleri as $devet) { ?>

        <?php
        $devet_gonderen_id = (int)$devet['gonderen_id'];

        $devet_gonderen_login = htmlspecialchars(
            $devet['gonderen_login'],
            ENT_QUOTES,
            'UTF-8'
        );

        $devet_id = (int)$devet['id'];

    
        ?>

        <hr>

        <div class="center">
            <div class="block_line">
                <b>Qrup Döyüşü</b>
            </div>
        </div>

        <b>
            <a href="infoforce.php?uid=<?php echo $devet_gonderen_id; ?>">
                <?php echo $devet_gonderen_login; ?>
            </a>
        </b>

        sizi qrupa çağırır.

        <br>
<br>
        [<a href="qrup_yarad.php?go=devet_qebul&amp;devet_id=<?php echo $devet_id; ?>">Qəbul edirəm</a>]

        <br>

        [<a href="qrup_yarad.php?go=devet_imtina&amp;devet_id=<?php echo $devet_id; ?>">İmtina edirəm</a>]

        <hr>

    <?php } ?>

<?php } ?>



<?php if ($qebul_neticesi) { ?>

<b>Eməliyyat yerinə yetirildi</b>

<br>

<div class="menu">
    <li>
        <a href="menu.php?">
            <img src="muxtelif/home.png" alt="">
            Ana səhifə
        </a>
    </li>
</div>

<div class="line"></div>

<div class="menu">

    <input
        type="button"
        class="button"
        value="Geri"
        onclick="goGeri()"
    >

    <li>
        <a href="infoforce.php?uid=<?php echo (int)$my_id; ?>">
            <img src="muxtelif/doyuscu1.png" alt="">
            Mənim döyüşçüm
        </a>
    </li>

</div>

<?php } ?>
<?php if (!$qebul_neticesi && $gelen_duel) { ?>

<!-- =====================================================
     GƏLƏN DUEL EKRANI
     BU HALDA PROGRESS, MENU VƏ FOOTER GÖRÜNMÜR
====================================================== -->

<div class="info">

    <div class="center">

        <b>
            <a href="javascript:location.reload();">
                <?php echo htmlspecialchars(
                    $gelen_duel['player1_login'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </a>
            [<?php echo (int)$gelen_duel['player1_level']; ?>]
        </b>

        Sizi döyüşə dəvət edir.

        <br>

        <b>
            <span id="gelen_duel_saygac">
                <?php echo (int)$gelen_duel_remaining; ?>
            </span>
        </b>

        saniyə ərzində qəbul edə bilərsiniz.

        <br>

        [
        <a href="doyush_gonderildi.php?go=qebul&amp;duel_id=<?php echo (int)$gelen_duel['id']; ?>">
            Qəbul edirəm
        </a>
        ]

        <br>

        [
        <a href="doyush_gonderildi.php?go=redd&amp;duel_id=<?php echo (int)$gelen_duel['id']; ?>">
            İmtina edirəm
        </a>
        ]

    </div>

</div>
<script>
function gelenDuelTimer()
{
    const timer =
        document.getElementById("gelen_duel_saygac");

    if (!timer) {
        return;
    }

    if (gelenDuelSeconds <= 0)
    {
        window.location.reload();
        return;
    }

    timer.innerHTML =
        gelenDuelSeconds;

    gelenDuelSeconds--;
}

gelenDuelTimer();

setInterval(
    gelenDuelTimer,
    1000
);

</script>

<?php } elseif (!$qebul_neticesi) { ?>



<?php
/*
|--------------------------------------------------------------------------
| AKTİV DUEL
|--------------------------------------------------------------------------
*/

$stmt_duel = $pdo->prepare("
    SELECT
        d.id,
        d.oyuncu1_id,
        d.oyuncu2_id,
        d.yaradilis_tarixi,

        p1.login AS player1_login,
        p1.oyuncunun_seviyyesi AS player1_level,

        p2.login AS player2_login,
        p2.oyuncunun_seviyyesi AS player2_level

    FROM duel d

    INNER JOIN users p1
        ON p1.id = d.oyuncu1_id

    INNER JOIN users p2
        ON p2.id = d.oyuncu2_id

    WHERE d.oyuncu1_id = :my_id
      AND d.qalib_id IS NULL

    ORDER BY d.yaradilis_tarixi DESC

    LIMIT 1
");


$stmt_duel->execute([
    ':my_id' => $my_id
]);

$aktiv_duel = $stmt_duel->fetch(PDO::FETCH_ASSOC);

$duel_remaining = 0;

if ($aktiv_duel) {

    $created_time = strtotime($aktiv_duel['yaradilis_tarixi']);

    $duel_remaining = 180 - (time() - $created_time);

    if ($duel_remaining <= 0) {

     $stmt_delete_duel = $pdo->prepare("
    DELETE FROM duel
    WHERE id = :id
      AND oyuncu1_id = :my_id
      AND qalib_id IS NULL
    LIMIT 1
");


        $stmt_delete_duel->execute([
            ':id'    => $aktiv_duel['id'],
            ':my_id' => $my_id
        ]);

        $aktiv_duel = false;
        $duel_remaining = 0;
    }
}
?>


<?php if ($aktiv_duel) { ?>

<hr>

<b>

<a href="infoforce.php?uid=<?php echo (int)$aktiv_duel['oyuncu1_id']; ?>">

<?php echo htmlspecialchars(
    $aktiv_duel['player1_login'],
    ENT_QUOTES,
    'UTF-8'
); ?>

[<?php echo (int)$aktiv_duel['player1_level']; ?>]

</a>

VS

</b>

<b>

<a href="infoforce.php?uid=<?php echo (int)$aktiv_duel['oyuncu2_id']; ?>">

<?php echo htmlspecialchars(
    $aktiv_duel['player2_login'],
    ENT_QUOTES,
    'UTF-8'
); ?>

[<?php echo (int)$aktiv_duel['player2_level']; ?>]

</a>

</b>

<br>

Reqibinizin qebul etmesini gözleyin...

<br>

<b>

<span id="menu_duel_saygac">
<?php echo (int)$duel_remaining; ?>
</span>

</b>

saniyə

<br>

<a href="doyush_gonderildi.php?go=redd&amp;duel_id=<?php echo (int)$aktiv_duel['id']; ?>">

İmtina et

</a>

<hr>

<script>

let menuDuelSeconds =
    <?php echo (int)$duel_remaining; ?>;

function menuDuelTimer()
{
    const timer =
        document.getElementById("menu_duel_saygac");

    if (!timer) {
        return;
    }

    if (menuDuelSeconds <= 0)
    {
        timer.innerHTML = "0";

        window.location.reload();

        return;
    }

    timer.innerHTML =
        menuDuelSeconds;

    menuDuelSeconds--;
}

menuDuelTimer();

setInterval(
    menuDuelTimer,
    1000
);

</script>

<?php } ?>

<?php

/*
|--------------------------------------------------------------------------
| QARŞI TƏRƏFDƏN GƏLƏN DUEL
|--------------------------------------------------------------------------
*/

$stmt_gelen_duel = $pdo->prepare("
    SELECT
        d.id,
        d.oyuncu1_id,
        d.oyuncu2_id,
        d.yaradilis_tarixi,

        p1.login AS player1_login,
        p1.oyuncunun_seviyyesi AS player1_level,

        p2.login AS player2_login,
        p2.oyuncunun_seviyyesi AS player2_level

    FROM duel d

    INNER JOIN users p1
        ON p1.id = d.oyuncu1_id

    INNER JOIN users p2
        ON p2.id = d.oyuncu2_id

    WHERE d.oyuncu2_id = :my_id
      AND d.qalib_id IS NULL

    ORDER BY d.yaradilis_tarixi DESC

    LIMIT 1
");


$stmt_gelen_duel->execute([
    ':my_id' => $my_id
]);

$gelen_duel = $stmt_gelen_duel->fetch(PDO::FETCH_ASSOC);

$gelen_duel_remaining = 0;

if ($gelen_duel) {

    $created_time = strtotime($gelen_duel['yaradilis_tarixi']);


    $gelen_duel_remaining =
        180 - (time() - $created_time);

    if ($gelen_duel_remaining <= 0) {

        $stmt_delete_gelen = $pdo->prepare("
            DELETE FROM duel
            WHERE id = :id
             AND oyuncu2_id = :my_id
AND qalib_id IS NULL

            LIMIT 1
        ");

        $stmt_delete_gelen->execute([
            ':id'    => $gelen_duel['id'],
            ':my_id' => $my_id
        ]);

        $gelen_duel = false;
        $gelen_duel_remaining = 0;
    }
}

?>


<div class='info'>

<?php if ($oyuncu_seviyyesi === 1) { ?>

<div class='block' style='background-color: #fffeb0;font-size: 0.9em;text-align:justify;border-bottom:#F0BF9B dotted 1px;'>
    <div style='float:left;width:90px;'>
        <img src='muxtelif/melumat.png' border='0' />
    </div>

KLAN.AZ döyüş oyununa xoş gelmisiz!
Men bu oyunda müeyyen yere geder size kömeklik edeceyem.

Size ilk kömeyim. Siz <b>Qorxulu qalalar</b> bölmesine daxil olub,
ilk tapşırıqı keçmelisiz.

Verilen <b>Tapşırıqlardan</b> size en yaxını sizden irelide olan
312-ci kordinatdı. Siz bu kordinanta çataraq tecrübe ve qızıl qazanacaqsız.

Bunun üçün <b>Qorxulu qalalar</b> bölmesinden qarşınızı kesen
moblara hucum edib qalib gelmeniz lazımdır.

İlk tapşırıqda size uğurlar.

<br/>


</div>

<?php } ?>


<div class='line'></div><div class='menu'><li><a href="online.php?">
<img src="muxtelif/on.png" alt=" "/>Hal-hazırda Online (<?php echo $online_sayi; ?>)</a>
<?php
$stmt_arena = $pdo->query("
    SELECT COUNT(*)
    FROM duel
    WHERE qebul_edildi = 1
      AND qalib_id IS NULL
");

$arena_sayi = (int)$stmt_arena->fetchColumn();
?>
</li>
<li>
    <a href="log_izle.php?">
        <img src="muxtelif/doyush.png" alt=" "/>
        Arena (<?php echo $arena_sayi; ?>)
    </a>
</li>

<li> <a href="log_qrup.php?go=qruplar"><img src="muxtelif/qrupda.png" alt=" "/>Qrup Döyüşleri</a></li> <li> <a href="klan_doyus.php?go=klanlar"><img src="muxtelif/qrupda.png" alt=" "/>Klan Döyüşleri</a></li>  <li><a href="chat.php?"><img src="muxtelif/chat.png" alt=" "/>Çat Söhbet</a> </li> <li><a href="forum/mozu2.php?"><img src="muxtelif/forum.png" alt=" "/>Forum Müzakire </a> </li><div class='line'></div><li><li><a href="qala.php?"><img src="muxtelif/castle.png" alt=" "/>Qorxulu Qalalar </a></li><li><a href="botlar.php?"><img src="muxtelif/canavar.png" alt=" "/>Vehşi Moblar</a></li><div class='line'></div><li> <a href="simt.php?uid=1000786"><img src="img/brill.png" alt=" "/>Brilliant Hesabını artır </a></li> <li> <a href="infoforce.php?uid=<?php echo (int)$_SESSION['user_id']; ?>"><img src="muxtelif/doyuscu1.png" alt=" "/>Menim Döyüşçüm [<?php echo $oyuncu_seviyyesi; ?>]</a></li> <li> <a href="chantam.php?"><img src="muxtelif/sandiq.png" alt=" "/>Eşya Çantası </a></li> <li> <a href="eshya_pilus.php?"><img src="muxtelif/demir.png" alt=" "/>Demirçixana</a></li>
<li>
<a href="merhele_pilus.php?">
<img src="muxtelif/gucplus.png" alt=" "/>Güc Bonusları
<?php if ($qalan_guc_bonus > 0) { ?>
+<?php echo $qalan_guc_bonus; ?>
<?php } ?>

</a>
</li>

 <li><a href="shexsi_sehife.php?"><img src="muxtelif/settings.png" alt=" "/>Şexsi Ayarlar</a></li><div class='line'></div> <li><a href="sheherler.php?"><img src="muxtelif/weher.png" alt=" "/>Menim Şeherim  </a></li> <li><a href="vip.php?"><img src="muxtelif/v.png" alt=" "/>Vip istifadeçi ol</a></li> <li><a href="eshyalar.php?"><img src="muxtelif/dukan.png" alt=" "/>Merkezi Dükan </a></li><li> <a href="auksion.php?"><img src="muxtelif/auction.png" alt=" "/>Auksion (0)</a></li> <li> <a href="bank_xidmeti.php?"><img src="muxtelif/bank.png" alt=" "/>Bank Xidmetleri</a></li><div class='line'></div><li> <a href="qrup.php?"><img src="muxtelif/clan.png" alt=" "/>Klanlar (6)</a></b></li> <li> <a href="statistik.php?"><img src="muxtelif/reytinq.png" alt=" "/>Top Reyting</a></li><li> <a href="vezife.php?"><img src="muxtelif/vezife.png" alt=" "/>İdare heyyeti</a></li><li><a href="komek.php?"><img src="muxtelif/info.png" alt=" "/>Melumat ve Qaydalar</a></li></div>
 <?php } ?>
 <?php if (!$gelen_duel || $qebul_neticesi) { ?>

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

<?php } ?>

</body>
</html>


