<?php

session_start();

require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   DAXİL OLMUŞ İSTİFADƏÇİNİN MƏLUMATLARI
========================================================= */

$my_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT qızıl, brılyant, enerjı,oyuncunun_seviyyesi
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $my_id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit('İstifadəçi tapılmadı.');
}


/* =========================================================
   DAXİL OLMUŞ İSTİFADƏÇİNİN ADI
========================================================= */

$user_login = '';

$stmt_user = $pdo->prepare("
    SELECT login
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_user->execute([
    ':id' => $my_id
]);

$current_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if ($current_user) {
    $user_login = $current_user['login'];
}


/* =========================================================
   MƏNİM ONLINE VAXTIMI YENİLƏ
========================================================= */

$stmt_online = $pdo->prepare("
    UPDATE users
    SET online_oyuncu_vaxti = :vaxt
    WHERE id = :id
");

$stmt_online->execute([
    ':vaxt' => time(),
    ':id'   => $my_id
]);


/* =========================================================
   OXUNMAMIŞ MƏKTUBLARIN SAYI
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


/* =========================================================
   ONLINE OYUNÇULARI MYSQL-DƏN GƏTİR
========================================================= */

$stmt = $pdo->prepare("
    SELECT 
        id,
        login,
        movqe,
        oyuncunun_seviyyesi,
        vip,
        online_oyuncu_vaxti
    FROM users
    WHERE online_oyuncu_vaxti >= :vaxt
    ORDER BY id DESC
");

$stmt->execute([
    ':vaxt' => time() - 180
]);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

<title>online oyuncular</title>

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

<div style="background: none repeat scroll 0 0 #888686; height: 1px;"></div>
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
        p1.oyuncunun_seviyyesi AS player1_level

    FROM duel d

    INNER JOIN users p1
        ON p1.id = d.oyuncu1_id

    WHERE d.oyuncu2_id = :my_id
      AND d.qalib_id IS NULL
      AND d.qebul_edildi = 0

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

<?php if ($gelen_duel) { ?>

<div class="info">

    <div class="center">

        <b>
            <a href="infoforce.php?uid=<?php echo (int)$gelen_duel['oyuncu1_id']; ?>">
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
            <span id="online_gelen_duel_saygac">
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

let duelSeconds = <?php echo (int)$duel_remaining; ?>;

const aktivDuelId =
    <?php echo (int)$aktiv_duel['id']; ?>;

const aktivDuelEnemyId =
    <?php echo (int)$aktiv_duel['oyuncu2_id']; ?>;


/*
|--------------------------------------------------------------------------
| DUEL SAYĞACI
|--------------------------------------------------------------------------
*/

function duelTimer()
{
    const timer =
        document.getElementById("duel_saygac");

    if (!timer) {
        return;
    }

    if (duelSeconds <= 0) {

        timer.innerHTML = "0";

        window.location.reload();

        return;
    }

    timer.innerHTML =
        duelSeconds;

    duelSeconds--;
}


/*
|--------------------------------------------------------------------------
| GÖNDƏRƏN TƏRƏF ACCEPTED YOXLAYIR
|--------------------------------------------------------------------------
*/

function duelQebulYoxla()
{
    fetch(
        "doyush_gonderildi.php?go=duel_yoxla"
        + "&duel_id="
        + aktivDuelId
        + "&t="
        + Date.now(),
        {
            method: "GET",
            cache: "no-store"
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        /*
        | accepted = 1 oldusa fight.php
        */

        if (data.accepted === true)
        {
            window.location.href =
                "fight.php?go=duel"
                + "&duel_id="
                + aktivDuelId
                + "&uid="
                + aktivDuelEnemyId;

            return;
        }

    })
    .catch(function(error) {

        console.log(
            "DUEL ACCEPT YOXLAMA XƏTASI:",
            error
        );

    });
}


/*
|--------------------------------------------------------------------------
| BAŞLANĞIC
|--------------------------------------------------------------------------
*/

duelTimer();

duelQebulYoxla();


/*
|--------------------------------------------------------------------------
| HƏR 1 SANİYƏ SAYĞAC
|--------------------------------------------------------------------------
*/

setInterval(
    duelTimer,
    1000
);


/*
|--------------------------------------------------------------------------
| HƏR 2 SANİYƏ ACCEPTED YOXLAMASI
|--------------------------------------------------------------------------
*/

setInterval(
    duelQebulYoxla,
    2000
);

</script>


<?php } ?>

<?php if (!$gelen_duel) { ?>

<?php
/*
|--------------------------------------------------------------------------
| GÖNDƏRİLMİŞ DUEL
|--------------------------------------------------------------------------
*/

$stmt_duel = $pdo->prepare("
    SELECT
        d.id,
        d.oyuncu1_id,
        d.oyuncu2_id,
        d.qebul_edildi,
        d.yaradilis_tarixi,
        d.bitdi,

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
  AND d.bitdi = 0

    ORDER BY d.yaradilis_tarixi DESC

    LIMIT 1
");


$stmt_duel->execute([
    ':my_id' => $my_id
]);

$aktiv_duel = $stmt_duel->fetch(PDO::FETCH_ASSOC);
$duel_remaining = 0;

if ($aktiv_duel) {

$created_time = strtotime(
    $aktiv_duel['yaradilis_tarixi']
);



    $duel_remaining = 180 - (time() - $created_time);

    if ($duel_remaining <= 0) {

$stmt_bitir_duel = $pdo->prepare("
    UPDATE duel
    SET bitdi = 1
    WHERE id = :id
");

$stmt_bitir_duel->execute([
    ':id' => $aktiv_duel['id']
]);

$aktiv_duel = false;
    }
}

/*
|--------------------------------------------------------------------------
| DUEL QƏBUL EDİLİBSƏ BİRBAŞA FIGHT.PHP
|--------------------------------------------------------------------------
*/

if (
    $aktiv_duel &&
   (int)$aktiv_duel['qebul_edildi'] === 1

) {

    header(
        "Location: fight.php?go=duel"
        . "&duel_id=" . (int)$aktiv_duel['id']
        . "&uid=" . (int)$aktiv_duel['oyuncu2_id']
    );

    exit;
}

?>

<?php if ($aktiv_duel && (int)$aktiv_duel['qebul_edildi'] !== 1) { ?>

<hr>

<b>

<a href="infoforce.php?uid=<?php echo (int)$aktiv_duel['oyuncu1_id']; ?>">

<?php echo htmlspecialchars($aktiv_duel['player1_login']); ?>

[<?php echo (int)$aktiv_duel['player1_level']; ?>]

</a>

VS

</b>

<b>

<a href="infoforce.php?uid=<?php echo (int)$aktiv_duel['oyuncu2_id']; ?>">

<?php echo htmlspecialchars($aktiv_duel['player2_login']); ?>

[<?php echo (int)$aktiv_duel['player2_level']; ?>]

</a>

</b>

<br>

Reqibinizin qebul etmesini gözleyin...

<br>

<b>

<span id="duel_saygac">
<?php echo (int)$duel_remaining; ?>
</span>


</b>

saniye

<br>

<a href="doyush_gonderildi.php?go=redd&amp;duel_id=<?php echo (int)$aktiv_duel['id']; ?>">
İmtina et
</a>

<hr>

<script>

let duelSeconds = <?php echo (int)$duel_remaining; ?>;

function duelTimer()
{
    const timer = document.getElementById("duel_saygac");

    if (!timer) {
        return;
    }

    if (duelSeconds <= 0) {

        timer.innerHTML = "0";

        window.location.reload();

        return;
    }

    timer.innerHTML = duelSeconds;

    duelSeconds--;
}

duelTimer();

setInterval(duelTimer, 1000);

</script>

<?php } ?>







<div class='info'>


<div class=mini-line>

<div class='menu'>

<li>
<a href="online.php?">
<img src="muxtelif/on.png" alt=" "/>
Hal-hazırda Online (<?php echo count($users); ?>)
</a>
</li>

</div>

<div class='line'></div>

<?php foreach ($users as $online_user) { ?>

<?php

if ((int)$online_user['movqe'] === 2) {
    // Vampir
    $renk = '#0F7100';

} elseif ((int)$online_user['movqe'] === 1) {
    // İnsan
    $renk = 'red';

} elseif ((int)$online_user['movqe'] === 3) {
    // Neytral
    $renk = 'rgb(0, 0, 255)';

} else {
    $renk = 'white';
}

?>

<div class="standart2">

<font color="<?php echo $renk; ?>">

<a href="infoforce.php?uid=<?php echo (int)$online_user['id']; ?>"
style="
color:<?php echo $renk; ?> !important;
<?php if ((int)$online_user['vip'] === 1) { ?>
text-decoration: underline !important;
text-shadow: 1px 1px 1px #888;
<?php } ?>
">
<?php echo htmlspecialchars($online_user['login']); ?> [<?php echo (int)$online_user['oyuncunun_seviyyesi']; ?>]
</a>
<?php if ((int)$online_user['id'] != (int)$_SESSION['user_id']) { ?>
    <a href="arxiv.php?uid=<?php echo (int)$online_user['id']; ?>">
        <img src="muxtelif/send.png" alt="Mesaj"/>
    </a>
<?php } ?>

</font>

<div class="point-line"></div>

</div>

<?php } ?>


<hr/>


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
<?php } ?>

</body>

</html>