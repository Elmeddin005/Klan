<?php

session_start();

require_once "config.php";
require_once "user_data.php";
require_once "doyus_sistem.php";
require_once "guc_parametrləri.php";
function guc_bonus_getir(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare("
        SELECT
            zerbe,
            mudafie,
            can,
            krit,
            anti_krit,
            uvorot,
            anti_uvorot
        FROM oyuncu_guc_bonuslari
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $bonus = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bonus) {
        return [
            'zerbe'       => 0,
            'mudafie'     => 0,
            'can'         => 0,
            'krit'        => 0,
            'anti_krit'   => 0,
            'uvorot'      => 0,
            'anti_uvorot' => 0
        ];
    }

    return $bonus;
}
/*
|--------------------------------------------------------------------------
| LOGIN YOXLAMASI
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];

$go = $_GET['go'] ?? '';
if ($go === 'duel_yoxla') {

    $duel_id = isset($_GET['duel_id'])
        ? (int)$_GET['duel_id']
        : 0;

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    if ($duel_id <= 0) {

        echo json_encode([
            'ok'       => false,
            'accepted' => false
        ]);

        exit;
    }


$stmt_check = $pdo->prepare("
    SELECT
        id,
        oyuncu1_id,
        oyuncu2_id,
        qebul_edildi,
        qalib_id
    FROM duel
    WHERE id = :duel_id
      AND oyuncu1_id = :my_id
    LIMIT 1
");

    $stmt_check->execute([
        ':duel_id' => $duel_id,
        ':my_id'   => $my_id
    ]);

    $duel_check = $stmt_check->fetch(PDO::FETCH_ASSOC);


    if (!$duel_check) {

        echo json_encode([
            'ok'       => false,
            'accepted' => false,
            'message'  => 'Duel tapılmadı'
        ]);

        exit;
    }


echo json_encode([
    'ok'          => true,
    'accepted'    => ((int)$duel_check['qebul_edildi'] === 1),
    'duel_id'     => (int)$duel_check['id'],
    'oyuncu1_id'  => (int)$duel_check['oyuncu1_id'],
    'oyuncu2_id'  => (int)$duel_check['oyuncu2_id'],
    'qalib_id'   => $duel_check['qalib_id']
]);


    exit;
}

/*
|--------------------------------------------------------------------------
| İSTİFADƏÇİ MƏLUMATLARI
|--------------------------------------------------------------------------
*/

$stmt_user = $pdo->prepare("
    SELECT
        id,
        login,
        qızıl,
        brılyant,
        enerjı,
        oyuncunun_seviyyesi
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_user->execute([
    ':id' => $my_id
]);

$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit("İstifadəçi tapılmadı.");
}

$user_login = $user['login'];


/*
|--------------------------------------------------------------------------
| OXUNMAMIŞ MƏKTUBLAR
|--------------------------------------------------------------------------
*/

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
|--------------------------------------------------------------------------
| =========================================================
| DUEL GÖNDƏR
| =========================================================
|--------------------------------------------------------------------------
|
| doyush_gonderildi.php?go=gonder
|
*/

if ($go === 'gonder') {

    $enemy_id = isset($_POST['uid'])
        ? (int)$_POST['uid']
        : 0;

    if ($enemy_id <= 0 || $enemy_id === $my_id) {
        exit("Yanlış rəqib.");
    }


    /*
    | Qarşı tərəfə artıq aktiv duel göndərilibmi?
    */

$stmt_check = $pdo->prepare("
    SELECT id
    FROM duel
    WHERE oyuncu1_id = :my_id
      AND oyuncu2_id = :enemy_id
      AND qalib_id IS NULL
      AND bitdi = 0
    ORDER BY id DESC
    LIMIT 1
");

$stmt_check->execute([
    ':my_id'    => $my_id,
    ':enemy_id' => $enemy_id
]);


    $old_duel = $stmt_check->fetch(PDO::FETCH_ASSOC);


    if ($old_duel) {

        $duel_id = (int)$old_duel['id'];

        header(
            "Location: doyush_gonderildi.php?go=gozle&duel_id="
            . $duel_id
        );

        exit;
    }


    /*
    | Yeni duel yarat
    */
/*
| Yeni duel üçün tam canları hesabla
*/

/*
| Yeni duel üçün oyunçuların parametrlərini götür
*/

$stmt_players = $pdo->prepare("
    SELECT
        u.id,
        op.can
    FROM users u
    LEFT JOIN oyuncu_parametrleri op
        ON op.user_id = u.id
    WHERE u.id IN (:player1_id, :player2_id)
");

$stmt_players->execute([
    ':player1_id' => $my_id,
    ':player2_id' => $enemy_id
]);

$players = [];

while ($row = $stmt_players->fetch(PDO::FETCH_ASSOC)) {
    $players[(int)$row['id']] = $row;
}


/*
| Güc bonusları
*/

$player1_guc_bonus = guc_bonus_getir($pdo, $my_id);
$player2_guc_bonus = guc_bonus_getir($pdo, $enemy_id);


/*
| Başlanğıc can
*/

$ilkin_can = 100;


/*
| Yeni duelin tam canları
*/

$oyuncu1_can =
    $ilkin_can
    + (int)($players[$my_id]['can'] ?? 0)
    + (int)($player1_guc_bonus['can'] ?? 0);

$oyuncu2_can =
    $ilkin_can
    + (int)($players[$enemy_id]['can'] ?? 0)
    + (int)($player2_guc_bonus['can'] ?? 0);


/*
| Yeni duel yarat
*/

$stmt_insert = $pdo->prepare("
    INSERT INTO duel
    (
        oyuncu1_id,
        oyuncu2_id,
        qalib_id,
        yaradilis_tarixi,
        novbe_baslama_tarixi,
        raund,
        oyuncu1_can,
        oyuncu2_can
    )
    VALUES
    (
        :oyuncu1_id,
        :oyuncu2_id,
        NULL,
        NOW(),
        NOW(),
        0,
        :oyuncu1_can,
        :oyuncu2_can
    )
");

$stmt_insert->execute([
    ':oyuncu1_id' => $my_id,
    ':oyuncu2_id' => $enemy_id,
    ':oyuncu1_can' => $oyuncu1_can,
    ':oyuncu2_can' => $oyuncu2_can
]);

$duel_id = (int)$pdo->lastInsertId();

/*
| Gözləmə səhifəsinə keç
*/

header(
    "Location: doyush_gonderildi.php?go=gozle&duel_id="
    . $duel_id
);

exit;
}


/*
|--------------------------------------------------------------------------
| =========================================================
| DUEL QƏBUL ET
| =========================================================
|--------------------------------------------------------------------------
|
| Qəbul edirəm düyməsi:
|
| doyush_gonderildi.php?go=qebul&duel_id=123
|
| Buradan fight.php-yə gedir.
|
*/

if ($go === 'qebul') {

    $duel_id = isset($_GET['duel_id'])
        ? (int)$_GET['duel_id']
        : 0;

    if ($duel_id <= 0) {
        header("Location: online.php");
        exit;
    }


    /*
    | Duel mənə göndərilibmi?
    */

$stmt_qebul = $pdo->prepare("
    SELECT
        d.id,
        d.oyuncu1_id,
        d.oyuncu2_id,
        d.qebul_edildi,
        d.qalib_id,

        p1.login AS oyuncu1_login,
p1.oyuncunun_seviyyesi AS oyuncu1_level,

p2.login AS oyuncu2_login,
p2.oyuncunun_seviyyesi AS oyuncu2_level


    FROM duel d

    INNER JOIN users p1
        ON p1.id = d.oyuncu1_id

    INNER JOIN users p2
        ON p2.id = d.oyuncu2_id

    WHERE d.id = :duel_id
      AND d.oyuncu2_id = :my_id
      AND d.qalib_id IS NULL

    LIMIT 1
");


    $stmt_qebul->execute([
        ':duel_id' => $duel_id,
        ':my_id'   => $my_id
    ]);

    $duel_qebul = $stmt_qebul->fetch(PDO::FETCH_ASSOC);


    /*
    | Duel tapılmadı
    */

    if (!$duel_qebul) {
        header("Location: online.php");
        exit;
    }


/*
|--------------------------------------------------------------------------
| DUELİ QƏBUL EDİLDİ KİMİ İŞARƏLƏ
|--------------------------------------------------------------------------
*/

$stmt_accept = $pdo->prepare("
    UPDATE duel
    SET qebul_edildi = 1
    WHERE id = :duel_id
      AND oyuncu2_id = :my_id
      AND qalib_id IS NULL
");

$stmt_accept->execute([
    ':duel_id' => (int)$duel_qebul['id'],
    ':my_id'   => $my_id
]);


/*
|--------------------------------------------------------------------------
| QƏBUL EDƏN TƏRƏF FIGHT.PHP-YƏ GEDİR
|--------------------------------------------------------------------------
*/

header(
    "Location: fight.php?go=duel"
    . "&duel_id=" . (int)$duel_qebul['id']
);

exit;
}


/*
|--------------------------------------------------------------------------
| =========================================================
| DUELİ İMTİNA ET
| =========================================================
|--------------------------------------------------------------------------
*/

if ($go === 'redd') {

    $duel_id = isset($_GET['duel_id'])
        ? (int)$_GET['duel_id']
        : 0;


    if ($duel_id > 0) {

        /*
        | Yalnız mənə gələn dueli sil
        */

$stmt = $pdo->prepare("
    DELETE FROM duel
    WHERE id = :id
      AND (oyuncu1_id = :my_id OR oyuncu2_id = :my_id)
      AND qalib_id IS NULL
    LIMIT 1
");

        $stmt->execute([
            ':id'    => $duel_id,
            ':my_id' => $my_id
        ]);
    }

    ?>

<!DOCTYPE html>
<html>

<head>

<meta charset="utf-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0, maximum-scale=3.0;">

<link rel="stylesheet" href="css.css">

<title>Duel</title>

</head>

<body>

<div class="main" style="word-wrap:break-word;">

<div id="header">

<a href="menu.php">
<img src="img/logo.png">
</a>

<div class="icons"></div>

<div class="main_foot">

<div class="grey">

<img
    src="img/coin.png"
    title="Qızıl"
    alt=""
>

<?php echo (int)$user['qızıl']; ?>

<img
    src="img/brill.png"
    title="Brilliant"
    alt=""
>

<?php echo (int)$user['brılyant']; ?>

<img
    src="img/energy.png"
    title="Enerji"
    alt=""
>

<?php echo (int)$user['enerjı']; ?>

<?php if ($unread_count > 0) { ?>

<a href="arxiv.php?go=goster">

<img
    src="img/mektub.gif"
    title="Məktub"
    alt="Məktub"
>

</a>

(<?php echo $unread_count; ?>)

<?php } ?>

<?php if ($dostluq_sayi > 0) { ?>

<a href="dostlar.php">

<img
    src="muxtelif/dost_pilus.png"
    title="Dost"
    alt="Dost"
>

</a>

(<?php echo $dostluq_sayi; ?>)

<?php } ?>

</div>

</div>

</div>


<div class="space"></div>

<div style="background:#888686;height:1px;"></div>


<div class="fl b exp_count">

<div style="margin-top:-2px;">

<span style="color:#ff3333">

<b><?php echo $progress; ?>%</b>

</span>

</div>

</div>


<div class="experience">

<div class="exp_bg">

<div class="exp_left fl"></div>

<div class="exp_right fr"></div>

<div style="
    width:<?php echo $progress; ?>%;
    height:10px;
">

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>

</div>

</div>


<div style="background:#888686;height:1px;"></div>


<div class="info">

<div class="success">

<img
    src="muxtelif/okey.png"
    alt=""
>

Duel ləğv edildi.

<br>

</div>

</div>


<hr>

<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">

[<b><a href="menu.php?">Menu</a></b>]

[<b><a href="axtar.php?">Axtarış</a></b>]

[<a href="forum/mozu2.php?">Forum</a>]

[<a href="shexsi_sehife.php?">Qurğular</a>]

<br><br>

<img
    src="muxtelif/saat.ico"
    width="20"
    height="20"
    title="Vaxt"
    alt="Vaxt"
>

<?php echo date("H:i"); ?>

<br>

<a href="index.php?">

Çıxış
(<?php echo htmlspecialchars($user_login, ENT_QUOTES, 'UTF-8'); ?>)

</a>

<br><br>

<a href="menu.php?dil=tr">

Türkce:

<img
    alt="türkce"
    src="muxtelif/tr.gif"
    title="Türkce"
>

</a>

<br>

Sciript name: Qanlı efsane(modern version)

<br>

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

<?php

    exit;
}




/*
|--------------------------------------------------------------------------
| =========================================================
| GÖZLƏMƏ SƏHİFƏSİ
| =========================================================
|--------------------------------------------------------------------------
*/

$duel_id = isset($_GET['duel_id'])
    ? (int)$_GET['duel_id']
    : 0;


if ($duel_id <= 0) {

    header("Location: online.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| DUELİ GƏTİR
|--------------------------------------------------------------------------
*/

$stmt_duel = $pdo->prepare("
    SELECT
        d.id,
        d.oyuncu1_id,
        d.oyuncu2_id,
        d.qalib_id,
        d.qebul_edildi,
        d.yaradilis_tarixi,

        p1.login AS oyuncu1_login,
        p1.oyuncunun_seviyyesi AS oyuncu1_level,

        p2.login AS oyuncu2_login,
        p2.oyuncunun_seviyyesi AS oyuncu2_level

    FROM duel d

    INNER JOIN users p1
        ON p1.id = d.oyuncu1_id

    INNER JOIN users p2
        ON p2.id = d.oyuncu2_id

    WHERE d.id = :duel_id
      AND d.oyuncu1_id = :my_id

    LIMIT 1
");

$stmt_duel->execute([
    ':duel_id' => $duel_id,
    ':my_id'   => $my_id
]);

$duel = $stmt_duel->fetch(PDO::FETCH_ASSOC);



if (!$duel) {

    header("Location: online.php");

    exit;
}
if ((int)$duel['qebul_edildi'] === 1) {

    header(
        "Location: fight.php?go=duel"
        . "&duel_id=" . (int)$duel['id']
        . "&uid=" . (int)$duel['oyuncu2_id']
    );

    exit;
}

if ((int)$duel['qalib_id'] > 0) {
    exit('Bu duel artıq bitib.');
}

if (
    $my_id !== (int)$duel['oyuncu1_id']
    && $my_id !== (int)$duel['oyuncu2_id']
) {
    exit('Bu duel sizə aid deyil.');
}






/*
|--------------------------------------------------------------------------
| 180 SANİYƏ
|--------------------------------------------------------------------------
*/

$created_time = strtotime($duel['yaradilis_tarixi']);

$remaining = 180 - (time() - $created_time);


/*
|--------------------------------------------------------------------------
| VAXT BİTİB
|--------------------------------------------------------------------------
*/

if ($remaining <= 0) {

    $stmt_delete = $pdo->prepare("
        DELETE FROM duel
        WHERE id = :id
          AND oyuncu1_id = :my_id
          AND qalib_id IS NULL
        LIMIT 1
    ");

    $stmt_delete->execute([
        ':id'    => $duel_id,
        ':my_id' => $my_id
    ]);

    header("Location: online.php");

    exit;
}

?>

<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL">

<meta name="keywords"
content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar">

<meta name="description"
content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры">

<link rel="stylesheet" href="css.css">

<meta
content="text/html; charset=utf-8"
http-equiv="content-type"
>

<meta
name="viewport"
content="width=device-width, initial-scale=1.0, maximum-scale=3.0;"
>

<title>duel</title>


<script>

let saniye = <?php echo (int)$remaining; ?>;

const duelId = <?php echo (int)$duel['id']; ?>;


/*
|--------------------------------------------------------------------------
| SAYĞAC
|--------------------------------------------------------------------------
*/

function saygac()
{
    const element =
        document.getElementById("duel_saygac");

    if (!element) {
        return;
    }


    if (saniye <= 0)
    {
        element.innerHTML = "0";

        window.location.href = "online.php";

        return;
    }


    element.innerHTML = saniye;

    saniye--;
}


/*
|--------------------------------------------------------------------------
| DUEL QƏBUL EDİLİB-EDİLMƏDİYİNİ YOXLAYIR
|--------------------------------------------------------------------------
*/

function duelYoxla()
{
    fetch(
        "doyush_gonderildi.php?go=duel_yoxla&duel_id="
        + duelId
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

        console.log("DUEL YOXLA:", data);

        if (data.accepted === true)
        {
            window.location.href =
                "fight.php?go=duel&duel_id="
                + duelId
                + "&uid="
                + <?php echo (int)$duel['oyuncu2_id']; ?>;
        }

    })
    .catch(function(error) {
        console.log("DUEL YOXLA XƏTA:", error);
    });
}




/*
|--------------------------------------------------------------------------
| SƏHİFƏ AÇILANDA
|--------------------------------------------------------------------------
*/

window.onload = function()
{
    saygac();

    duelYoxla();
};


/*
|--------------------------------------------------------------------------
| HƏR 1 SANİYƏDƏ SAYĞAC
|--------------------------------------------------------------------------
*/

setInterval(
    saygac,
    1000
);


/*
|--------------------------------------------------------------------------
| HƏR 2 SANİYƏDƏ DUELİ YOXLAYIR
|--------------------------------------------------------------------------
*/

setInterval(
    duelYoxla,
    2000
);

</script>


</head>


<body>

<div
class="main"
style="word-wrap:break-word;"
>


<!-- =====================================================
     HEADER
===================================================== -->

<div id="header">

<a href="menu.php">

<img src="img/logo.png">

</a>

<div class="icons"></div>


<div class="main_foot">

<div class="grey">


<img
src="img/coin.png"
title="Qızıl"
alt=""
>

<?php echo (int)$user['qızıl']; ?>


<img
src="img/brill.png"
title="Brilliant"
alt=""
>

<?php echo (int)$user['brılyant']; ?>


<img
src="img/energy.png"
title="Enerji"
alt=""
>

<?php echo (int)$user['enerjı']; ?>


<?php if ($unread_count > 0) { ?>

<a href="arxiv.php?go=goster">

<img
src="img/mektub.gif"
title="Məktub"
alt="Məktub"
>

</a>

(<?php echo $unread_count; ?>)

<?php } ?>


<?php if ($dostluq_sayi > 0) { ?>

<a href="dostlar.php">

<img
src="muxtelif/dost_pilus.png"
title="Dost"
alt="Dost"
>

</a>

(<?php echo $dostluq_sayi; ?>)

<?php } ?>


</div>

</div>

</div>


<!-- =====================================================
     EXPERIENCE
===================================================== -->

<div class="space"></div>

<div
style="background:#888686;height:1px;"
></div>


<div class="fl b exp_count">

<div style="margin-top:-2px;">

<span style="color:#ff3333">

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
style="
width:<?php echo $progress; ?>%;
height:10px;
"
>

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>


</div>

</div>


<div
style="background:#888686;height:1px;"
></div>


<!-- =====================================================
     DUEL GÖZLƏMƏ
===================================================== -->

<div class="info">

<div class="mini-line">


<p>

<b>


<a
    href="infoforce.php?uid=<?php echo (int)$duel['oyuncu1_id']; ?>"
>

<?php echo htmlspecialchars(
    $duel['oyuncu1_login'],
    ENT_QUOTES,
    'UTF-8'
); ?>

[<?php echo (int)$duel['oyuncu1_level']; ?>]

</a>


VS


<a
    href="infoforce.php?uid=<?php echo (int)$duel['oyuncu2_id']; ?>"
>

<?php echo htmlspecialchars(
    $duel['oyuncu2_login'],
    ENT_QUOTES,
    'UTF-8'
); ?>

[<?php echo (int)$duel['oyuncu2_level']; ?>]

</a>



</b>


<br><br>


<div class="battle_log">

Reqibinizin qebulunu gozleyin...

<br>


<b>

<span id="duel_saygac">

<?php echo (int)$remaining; ?>

</span>

</b>

san.


</div>


<br>


<div class="menu">


<li>

<a
href="doyush_gonderildi.php?go=gozle&amp;duel_id=<?php echo (int)$duel['id']; ?>"
>

Sehifeni yenile

</a>

</li>


<li>

<a
href="doyush_gonderildi.php?go=redd&amp;duel_id=<?php echo (int)$duel['id']; ?>"
>

İmtina et

</a>

</li>


</div>


</p>


</div>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<hr>


<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">


[<b><a href="menu.php?">Menu</a></b>]

[<b><a href="axtar.php?">Axtarış</a></b>]

[<a href="forum/mozu2.php?">Forum</a>]

[<a href="shexsi_sehife.php?">Qurğular</a>]


<br><br>


<img
src="muxtelif/saat.ico"
width="20"
height="20"
title="Vaxt"
alt="Vaxt"
>

<?php echo date("H:i"); ?>


<br>


<a href="index.php?">

Çıxış
(<?php echo htmlspecialchars(
    $user_login,
    ENT_QUOTES,
    'UTF-8'
); ?>)

</a>


<br><br>


<a href="menu.php?dil=tr">

Türkce:

<img
alt="türkce"
src="muxtelif/tr.gif"
title="Türkce"
>

</a>


<br>


Sciript name: Qanlı efsane(modern version)


<br>


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
