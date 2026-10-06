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
$my_id = (int)$_SESSION['user_id'];





/*
|--------------------------------------------------------------------------
| VIP RESET - TEST ÜÇÜN
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['vip_reset']) &&
    $_GET['vip_reset'] === '1'
) {

    unset($_SESSION['vip_castle_start']);
    unset($_SESSION['vip_castle_end']);
    unset($_SESSION['vip_castle_success']);
    unset($_SESSION['vip_castle_finished']);

    unset($_SESSION['vip_castle_rewards']);
    unset($_SESSION['vip_castle_open_index']);

    echo 'VIP Castle sıfırlandı.';
    exit;
}


/*
|--------------------------------------------------------------------------
| VIP STATUS
|--------------------------------------------------------------------------
*/

$vip_status = ((int)($user['vip'] ?? 0) === 1);


/*
|--------------------------------------------------------------------------
| GO PARAMETRİ
|--------------------------------------------------------------------------
*/

$go = isset($_GET['go'])
    ? $_GET['go']
    : '';



/*
|--------------------------------------------------------------------------
| VIP ƏŞYA SİYAHISI
|--------------------------------------------------------------------------
|
| color ayrıca sonradan sistem tərəfindən veriləcək.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| VIP ƏŞYALARINI BAZADAN GƏTİR
|--------------------------------------------------------------------------
*/

$vip_esyalar_goy = [];
$vip_esyalar_yasil = [];

$stmt_vip_esyalar = $pdo->prepare("
    SELECT
        id AS rid,
        ad AS name,
        img,
        reng,
        seviyye
    FROM esyalar
    WHERE seviyye = :seviyye
      AND reng IN ('goy', 'yasil')
      AND aktiv = 1
    ORDER BY id ASC
");

$stmt_vip_esyalar->execute([
    ':seviyye' => $seviyye
]);

$vip_db_esyalar = $stmt_vip_esyalar->fetchAll(PDO::FETCH_ASSOC);

foreach ($vip_db_esyalar as $esya) {

    if ($esya['reng'] === 'goy') {

        $vip_esyalar_goy[] = [
            'name' => $esya['name'],
            'img'  => $esya['img'],
            'rid'  => (int)$esya['rid'],
            'seviyye' => (int)$esya['seviyye']
        ];

    } elseif ($esya['reng'] === 'yasil') {

        $vip_esyalar_yasil[] = [
            'name' => $esya['name'],
            'img'  => $esya['img'],
            'rid'  => (int)$esya['rid'],
             'seviyye' => (int)$esya['seviyye']
        ];
    }
}



/*
|--------------------------------------------------------------------------
| VIP ƏŞYALARINI YARAT
|--------------------------------------------------------------------------
*/

function vip_castle_create_rewards()
{

    global $vip_esyalar_goy;
    global $vip_esyalar_yasil;


    /*
     * ƏVVƏLDƏN YARADILIBSA YENİDƏN YARATMA
     */

    if (
        isset($_SESSION['vip_castle_rewards']) &&
        is_array($_SESSION['vip_castle_rewards']) &&
        count($_SESSION['vip_castle_rewards']) > 0
    ) {

        return;
    }


    $goy = $vip_esyalar_goy;
    $yasil = $vip_esyalar_yasil;


    shuffle($goy);
    shuffle($yasil);


    /*
     * 10 - 15 ƏŞYA
     */

    $item_count = mt_rand(10, 15);


    $rewards = array();


    for (
        $i = 0;
        $i < $item_count;
        $i++
    ) {


        /*
         * 70% GÖY
         * 30% YAŞIL
         */

        $rand = mt_rand(1, 100);


        if (
            $rand <= 70 &&
            !empty($goy)
        ) {

            $item = array_pop($goy);

            $item['color'] = 'goy';

        } elseif (
            !empty($yasil)
        ) {

            $item = array_pop($yasil);

            $item['color'] = 'yasil';

        } elseif (
            !empty($goy)
        ) {

            $item = array_pop($goy);

            $item['color'] = 'goy';

      } else {

    /*
     * Əgər siyahı çatmasa digər mövcud əşyadan seç
     */

    $all = array_merge(
        $vip_esyalar_goy,
        $vip_esyalar_yasil
    );

    if (empty($all)) {
        break;
    }

    $item = $all[array_rand($all)];

    $item['color'] =
        $item['reng'] ?? (
            mt_rand(1, 100) <= 70
                ? 'goy'
                : 'yasil'
        );
}
/*
|--------------------------------------------------------------------------
| AMETIST GURZ VIP QALASINDA QADAĞANDIR
|--------------------------------------------------------------------------
*/

if (
    in_array((int)$item['rid'], [339, 349], true)
) {
    $i--;
    continue;
}


        $rewards[] = $item;
    }


    $_SESSION['vip_castle_rewards'] =
        $rewards;


    $_SESSION['vip_castle_open_index'] =
        0;
}



/*
|--------------------------------------------------------------------------
| QALADA OLUB-OLMADIĞINI YOXLAYIRIQ
|--------------------------------------------------------------------------
*/

$qalada = false;

$qalan_saniye = 0;


if (isset($_SESSION['vip_castle_end'])) {

    $qalan_saniye =
        $_SESSION['vip_castle_end'] -
        time();


    if ($qalan_saniye > 0) {

        $qalada = true;

    } else {

        /*
         * MÜDDƏT TAMAMLANIB
         */

        $_SESSION['vip_castle_finished'] =
            true;


        unset(
            $_SESSION['vip_castle_start']
        );


        unset(
            $_SESSION['vip_castle_end']
        );


        /*
         * ƏŞYALARI BİR DƏFƏ YARAT
         */

        vip_castle_create_rewards();


        $qalada = false;

        $qalan_saniye = 0;

    }

}



?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN""http://www.wapforum.org/DTD/xhtml-mobile10.dtd">

<html
xmlns="http://www.w3.org/1999/xhtml"
xml:lang="az"
lang="az"
>

<head>

<meta
name="robots"
content="ALL"
/>

<meta
name="keywords"
content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, "
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

<title>Vip Castle</title>


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
    <img src="img/logo.png" alt=""/>
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

<?php if ($unread_count > 0) { ?>

<a href="arxiv.php?go=goster">
    <img src="img/mektub.gif" title="Yeni mesaj" alt="Mesaj"/>
</a>

(<?php echo $unread_count; ?>)

<?php } ?>

</div>
</div>

</div>


<div class="space"></div>

<div style="background: none repeat scroll 0 0 #888686; height: 1px;"></div>


<!-- PROGRESS -->

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


<?php
if ($go == '') {


    if ($qalada) {

        $bitme_vaxti =
            $_SESSION['vip_castle_end'];

?>


<div class="center">

<b>Vip Castle</b>

<br/>

<b>Qalanın adı:</b> Vip Castle

<br/>

<b>Haqqında: </b>

Qalaya yalnız <b>Vip istifadeçiler</b> daxil ola biler.

Sizin oyunçu 24 saat erzinde 1 defe qalaya daxil ola biler.

Qalada sizin qurduğunuz sxem esasında oyunçunuz özü döyüşür,
sizin müdaxileniz olmadan.

Bu vaxtı siz oyunun diger funksiyalarından istifade ede bilersiz

<br/>


<div class="menu">

<br/>


<div class="error">

<span
style="color:#DA1515; white-space:nowrap;"
>

<img
src="muxtelif/eror.png"
alt=""
>

Sizin oyunçu hal hazırda qalada döyüşür,
Bitməsinə qalıb:

<span
id="qala_saygac"
style="color:#DA1515 !important; white-space:nowrap;"
></span>

</span>

</div>


<br/>


</div>


</div>


<script>

var bitmeVaxti =
<?php echo $bitme_vaxti; ?> * 1000;


function qalaSaygaci() {

    var indi =
        new Date().getTime();

    var ferq =
        bitmeVaxti - indi;


    if (ferq <= 0) {

        document
            .getElementById("qala_saygac")
            .innerHTML =
            "0 saat. 00 deq.";

        return;

    }


    var saat =
        Math.floor(
            ferq /
            (1000 * 60 * 60)
        );


    var deqiqe =
        Math.floor(
            (
                ferq %
                (1000 * 60 * 60)
            ) /
            (1000 * 60)
        );


    document
        .getElementById("qala_saygac")
        .innerHTML =
        " " +
        saat +
        " saat. " +
        deqiqe +
        " deq.";

}


setInterval(
    qalaSaygaci,
    1000
);


qalaSaygaci();

</script>


<?php


    }

    else {


?>


<div class=center>

<b>Vip Castle</b>

<br/>

<b>Qalanın adı:</b> Vip Castle

<br/>

<b>Haqqında: </b>

Qalaya yalnız <b>Vip istifadeçiler</b> daxil ola biler.

Sizin oyunçu 24 saat erzinde 1 defe qalaya daxil ola biler.

Qalada sizin qurduğunuz sxem esasında oyunçunuz özü döyüşür,
sizin müdaxileniz olmadan.

Bu vaxtı siz oyunun diger funksiyalarından istifade ede bilersiz

<br/>


<div class=menu>

<br/>


<?php if (
    isset($_SESSION['vip_castle_finished']) &&
    $_SESSION['vip_castle_finished'] === true
) { ?>

<li>

<a href="kordinat4.php?go=end">

Əldə etdikləriniz

</a>

</li>

<?php } else { ?>

<li>

<a href="kordinat4.php?go=daxil">

Qalaya Giriş

</a>

</li>

<?php } ?>


</div>


</div>


<br/>


<?php


    }

}



/*
|--------------------------------------------------------------------------
| QALAYA GİRİŞ
|--------------------------------------------------------------------------
*/

elseif ($go == 'daxil') {


    if ($qalada) {

        $bitme_vaxti =
            $_SESSION['vip_castle_end'];

?>


<br>


<div class="error">

<span
style="color:#DA1515; white-space:nowrap;"
>

<img
src="muxtelif/eror.png"
alt=""
>

Sizin oyunçu hal hazırda qalada döyüşür,
Bitməsinə qalıb:

<span
id="qala_saygac"
style="color:#DA1515 !important; white-space:nowrap;"
></span>

</span>

</div>


<br>


<input
type="button"
class="button"
value="Geri"
onclick="goGeri()"
>


<script>

var bitmeVaxti =
<?php echo $bitme_vaxti; ?> * 1000;


function qalaSaygaci() {

    var indi =
        new Date().getTime();

    var ferq =
        bitmeVaxti - indi;


    if (ferq <= 0) {

        document
            .getElementById("qala_saygac")
            .innerHTML =
            "0 saat 00 deq.";

        return;

    }


    var saat =
        Math.floor(
            ferq /
            (1000 * 60 * 60)
        );


    var deqiqe =
        Math.floor(
            (
                ferq %
                (1000 * 60 * 60)
            ) /
            (1000 * 60)
        );


    document
        .getElementById("qala_saygac")
        .innerHTML =
        " " +
        saat +
        " saat " +
        deqiqe +
        " deq.";

}


setInterval(
    qalaSaygaci,
    1000
);


qalaSaygaci();

</script>


<?php


    }


    elseif ($vip_status == false) {


?>


<br/>


<div class="error">


<img
src="muxtelif/eror.png"
alt=""
/>


Daxil olmaq üçün Vip statusu teleb olunur.


</div>


<br/>


<input
type="button"
class="button"
value="Geri"
onclick="goGeri()"
>


<?php


    }


    else {


?>


<form
method="post"
action="kordinat4.php?go=ok"
>


<div class=battle_log>

<b>Müddet</b>

<br/>

<input
type="text"
readonly="readonly"
size="12"
name="alish_min"
class="text long"
value="10 saat"
/>

<br/>

Mob: 500

<br/>

Qelebe: 450

<br/>

Tecrübe: 16000

<br/>

Qızıl: 5000

<br/>

Eşya: 10-15

</div>


<input
type="hidden"
name="action"
value="save"
/>


<input
type="submit"
class="button"
value="Başla"
/>


<br/>


<div class='line'></div>


</form>


<?php


    }

}



/*
|--------------------------------------------------------------------------
| ƏLDƏ ETDİKLƏRİNİZ - GO=END
|--------------------------------------------------------------------------
*/

elseif ($go == 'end') {

    if (
        !isset($_SESSION['vip_castle_finished']) ||
        $_SESSION['vip_castle_finished'] !== true
    ) {

        header('Location: kordinat4.php');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | VIP QALA TECRÜBƏ MÜKAFATI
    |--------------------------------------------------------------------------
    | 450 mob x 32 tecrübe = 14400 tecrübe
    | Yalnız bir dəfə verilir.
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_SESSION['vip_castle_tecrube_verildi']) ||
        $_SESSION['vip_castle_tecrube_verildi'] !== true
    ) {

        $vip_castle_tecrube = 500 * 32;

        $stmt_vip_tecrube = $pdo->prepare("
            UPDATE users
            SET oyuncunun_tecrubesi =
                oyuncunun_tecrubesi + :tecrube
            WHERE id = :user_id
        ");

        $stmt_vip_tecrube->execute([
            ':tecrube' => $vip_castle_tecrube,
            ':user_id' => $my_id
        ]);

        $_SESSION['vip_castle_tecrube_verildi'] = true;
    }

/*
|--------------------------------------------------------------------------
| VIP QALA MOB REYTİNQİ
|--------------------------------------------------------------------------
| VIP Qala tamamlandıqda 500 mob statistikaya əlavə olunur.
| Yalnız bir dəfə əlavə edilir.
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['vip_castle_mob_reytinq_verildi']) ||
    $_SESSION['vip_castle_mob_reytinq_verildi'] !== true
) {

    $vip_castle_mob = 500;
    $bugun = date('Y-m-d');

    $stmt_mob_reytinq = $pdo->prepare("
        SELECT id
        FROM mob_reytinq
        WHERE user_id = :user_id
          AND tarix = :tarix
        LIMIT 1
    ");

    $stmt_mob_reytinq->execute([
        ':user_id' => $my_id,
        ':tarix' => $bugun
    ]);

    $mob_reytinq_id = $stmt_mob_reytinq->fetchColumn();

    if ($mob_reytinq_id) {

        $stmt_mob_artir = $pdo->prepare("
            UPDATE mob_reytinq
            SET say = say + :say
            WHERE id = :id
        ");

        $stmt_mob_artir->execute([
            ':say' => $vip_castle_mob,
            ':id' => (int)$mob_reytinq_id
        ]);

    } else {

        $stmt_mob_elave = $pdo->prepare("
            INSERT INTO mob_reytinq
            (
                user_id,
                tarix,
                say
            )
            VALUES
            (
                :user_id,
                :tarix,
                :say
            )
        ");

        $stmt_mob_elave->execute([
            ':user_id' => $my_id,
            ':tarix' => $bugun,
            ':say' => $vip_castle_mob
        ]);
    }

    $_SESSION['vip_castle_mob_reytinq_verildi'] = true;
}

    vip_castle_create_rewards();

    $rewards = $_SESSION['vip_castle_rewards'];

    $open_index =
        isset($_SESSION['vip_castle_open_index'])
            ? (int)$_SESSION['vip_castle_open_index']
            : 0;

    $total_items = count($rewards);

    $unopened = $total_items - $open_index;

/*
|--------------------------------------------------------------------------
| VIP QALA QIZIL MÜKAFATI
|--------------------------------------------------------------------------
| 5000 qızıl yalnız bir dəfə verilir.
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['vip_castle_qizil_verildi']) ||
    $_SESSION['vip_castle_qizil_verildi'] !== true
) {

    $vip_castle_qizil = 5000;

    $stmt_vip_qizil = $pdo->prepare("
        UPDATE users
        SET qızıl = qızıl + :qizil
        WHERE id = :user_id
    ");

    $stmt_vip_qizil->execute([
        ':qizil' => $vip_castle_qizil,
        ':user_id' => $my_id
    ]);

    $_SESSION['vip_castle_qizil_verildi'] = true;
}

?>


<div class="center">

<div class="block_line">

Elde etdikleriniz

</div>

</div>

<br/>


<div class="battle_log">

Qalibiyyet: 450

<br/>

Tecrübe: 16000

<br/>

Qızıl: 5000

<br/>

Qazandığınız eşyalar
(<?php echo $unopened; ?>/<?php echo $total_items; ?>)

<br/>

</div>


<?php if ($unopened > 0) { ?>

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
src="muxtelif/qutu.png"
alt="qutu"
>

</td>

<td>

<img
src="muxtelif/gifts.png"
alt=""
>

<a
href="kordinat4.php?go=bax"
>

<?php echo $unopened; ?>
hediyye açılmayıb

</a>

<br/>

<a
href="kordinat4.php?go=bax"
>

Qutunu aç

</a>

<br/>

</td>

</tr>

</table>

</div>

</div>

<?php } else { ?>

<?php } ?>


<?php

}


/*
|--------------------------------------------------------------------------
| QUTUNU AÇ - GO=BAX
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| QUTUNU AÇ - GO=BAX
|--------------------------------------------------------------------------
*/

elseif ($go == 'bax') {

    vip_castle_create_rewards();

    $rewards = $_SESSION['vip_castle_rewards'];

    $open_index = isset($_SESSION['vip_castle_open_index'])
        ? (int)$_SESSION['vip_castle_open_index']
        : 0;

    $total_items = count($rewards);


    /*
    |--------------------------------------------------------------------------
    | BÜTÜN ƏŞYALAR GÖTÜRÜLÜBSƏ
    |--------------------------------------------------------------------------
    */

    if ($open_index >= $total_items) {

        ?>

        <br/>

        <div class="error">

            <img
                src="muxtelif/eror.png"
                alt=""
            />

            Siz bütün eşyalarınızı götürmüsüz.

        </div>

        <br/>

        <?php

    } else {

        /*
        |--------------------------------------------------------------------------
        | NÖVBƏTİ ƏŞYA
        |--------------------------------------------------------------------------
        */

       $item = $rewards[$open_index];

/*
|--------------------------------------------------------------------------
| VIP QALA ƏŞYASINI ÇANTAYA ƏLAVƏ ET
|--------------------------------------------------------------------------
*/

$stmt_vip_esya = $pdo->prepare("
    SELECT
        krit,
        max_krit,
        anti_krit,
        max_anti_krit,
        uvorot,
        max_uvorot,
        anti_uvorot,
        max_anti_uvorot
    FROM esyalar
    WHERE id = :esya_id
    LIMIT 1
");

$stmt_vip_esya->execute([
    ':esya_id' => (int)$item['rid']
]);

$vip_esya_param = $stmt_vip_esya->fetch(PDO::FETCH_ASSOC);

if (!$vip_esya_param) {
    throw new Exception('VIP esya bazada tapilmadi.');
}


/*
|--------------------------------------------------------------------------
| RANDOM KRIT / ANTI-KRIT / UVOROT
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| KRIT / ANTI-KRIT / UVOROT / ANTI-UVOROT
| 4 parametrin RƏQƏMİ eyni olacaq
|--------------------------------------------------------------------------
*/

$random_krit = random_int(
    (int)$vip_esya_param['krit'],
    (int)$vip_esya_param['max_krit']
);

$random_anti_krit = $random_krit;
$random_uvorot = $random_krit;
$random_anti_uvorot = $random_krit;


/*
|--------------------------------------------------------------------------
| FAIZLER AYRI RANDOM
|--------------------------------------------------------------------------
*/

$random_krit_faiz = random_int(0, 24);

$random_anti_krit_faiz = random_int(0, 24);

$random_uvorot_faiz = random_int(0, 24);

$random_anti_uvorot_faiz = random_int(0, 24);

$random_krit_faiz = random_int(0, 24);
$random_anti_krit_faiz = random_int(0, 24);
$random_uvorot_faiz = random_int(0, 24);
$random_anti_uvorot_faiz = random_int(0, 24);


/*
|--------------------------------------------------------------------------
| CANTAYA ELAVE ET
|--------------------------------------------------------------------------
*/

$stmt_vip_canta = $pdo->prepare("
    INSERT INTO canta
    (
        user_id,
        esya_id,
        say,
        geyimde,
        son_daxil_olma_vaxti,
        random_krit,
        random_anti_krit,
        random_uvorot,
        random_anti_uvorot,
        random_krit_faiz,
        random_anti_krit_faiz,
        random_uvorot_faiz,
        random_anti_uvorot_faiz
    )
    VALUES
    (
        :user_id,
        :esya_id,
        1,
        0,
        :vaxt,
        :random_krit,
        :random_anti_krit,
        :random_uvorot,
        :random_anti_uvorot,
        :random_krit_faiz,
        :random_anti_krit_faiz,
        :random_uvorot_faiz,
        :random_anti_uvorot_faiz
    )
");

$stmt_vip_canta->execute([
    ':user_id' => $my_id,
    ':esya_id' => (int)$item['rid'],
    ':vaxt' => time(),

    ':random_krit' => $random_krit,
    ':random_anti_krit' => $random_anti_krit,
    ':random_uvorot' => $random_uvorot,
    ':random_anti_uvorot' => $random_anti_uvorot,

    ':random_krit_faiz' => $random_krit_faiz,
    ':random_anti_krit_faiz' => $random_anti_krit_faiz,
    ':random_uvorot_faiz' => $random_uvorot_faiz,
    ':random_anti_uvorot_faiz' => $random_anti_uvorot_faiz
]);

$_SESSION['vip_castle_open_index'] = $open_index + 1;

$remaining_after =
    $total_items -
    $_SESSION['vip_castle_open_index'];




        /*
        |--------------------------------------------------------------------------
        | SON ƏŞYADIRSA
        |--------------------------------------------------------------------------
        */

        if ($remaining_after <= 0) {

            $_SESSION['vip_castle_finished'] = false;

            ?>

            <br/>

            <div class="error">

                <img
                    src="muxtelif/eror.png"
                    alt=""
                />

                Siz bütün eşyalarınızı götürmüsüz.

            </div>

            <br/>

            <?php

        } else {

            ?>

            <div class="success">

                <img
                    src="muxtelif/okey.png"
                    alt=""
                />

                Eşya çantanıza gönderildi.

                <br/>

            </div>

            <br/>


            <div class="battle_log">

                <div class="content">

                    <table
                        border="1"
                        cellpadding="1"
                        cellspacing="1"
                    >

                        <tr>

                            <td>

                                <img
                                    src="<?php
                                    echo htmlspecialchars(
                                        $item['img'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>"
                                    alt="Shekil"
                                />

                            </td>


                            <td>

                                <a
                                    href="eshyalar.php?go=mod_info&amp;rid=<?php
                                    echo (int)$item['rid'];
                                    ?>"
                                    style="<?php
                                    echo (
                                        $item['color'] === 'yasil'
                                            ? 'color:green;'
                                            : 'color:blue;'
                                    );
                                    ?>"
                                >

                                  <?php
echo htmlspecialchars(
    $item['name'],
    ENT_QUOTES,
    'UTF-8'
);
?>

[<?php echo (int)$item['seviyye']; ?>]


                                </a>

                                <br/>

                                <a href="kordinat4.php?go=bax">
                                    Növbeti eşyani aç
                                </a>

                                <br/>

                            </td>

                        </tr>

                    </table>

                </div>

            </div>

            <?php

        }
    }
}


/*
|--------------------------------------------------------------------------
| BAŞLA - GO=OK
|--------------------------------------------------------------------------
*/

elseif ($go == 'ok') {


    if ($vip_status == false) {


?>


<br/>


<div class="error">

<img
src="muxtelif/error.png"
alt=""
/>

Daxil olmaq üçün Vip statusu teleb olunur.

</div>


<br/>


<input
type="button"
class="button"
value="Geri"
onclick="goGeri()"
>


<?php


    }


    elseif ($qalada) {


        $bitme_vaxti =
            $_SESSION['vip_castle_end'];

?>


<br>


<div class="error">

<span
style="color:#DA1515; white-space:nowrap;"
>

<img
src="muxtelif/eror.png"
alt=""
>

Sizin oyunçu hal hazırda qalada döyüşür,
Bitməsinə qalıb:

<span
id="qala_saygac"
style="color:#DA1515 !important; white-space:nowrap;"
></span>

</span>

</div>


<br>


<input
type="button"
class="button"
value="Geri"
onclick="goGeri()"
>


<script>

var bitmeVaxti =
<?php echo $bitme_vaxti; ?> * 1000;


function qalaSaygaci() {

    var indi =
        new Date().getTime();

    var ferq =
        bitmeVaxti - indi;


    if (ferq <= 0) {

        document
            .getElementById("qala_saygac")
            .innerHTML =
            "0 saat 00 deq.";

        return;

    }


    var saat =
        Math.floor(
            ferq /
            (1000 * 60 * 60)
        );


    var deqiqe =
        Math.floor(
            (
                ferq %
                (1000 * 60 * 60)
            ) /
            (1000 * 60)
        );


    document
        .getElementById("qala_saygac")
        .innerHTML =
        " " +
        saat +
        " saat " +
        deqiqe +
        " deq.";

}


setInterval(
    qalaSaygaci,
    1000
);


qalaSaygaci();

</script>


<?php


    }


    else {


        /*
         * TEST ÜÇÜN 5 SANİYƏ
         *
         * SONRA:
         *
         * time() + (10 * 60 * 60)
         */

       $_SESSION['vip_castle_start'] =
    time();

$_SESSION['vip_castle_end'] =
    time() + (10 * 60 * 60);


        /*
         * YENİ QALA BAŞLADILANDA
         * KÖHNƏ NƏTİCƏLƏRİ TƏMİZLƏ
         */

        unset(
            $_SESSION['vip_castle_finished']
        );


        unset(
            $_SESSION['vip_castle_rewards']
        );


        unset(
            $_SESSION['vip_castle_open_index']
        );
unset(
    $_SESSION['vip_castle_tecrube_verildi']
);
unset(
    $_SESSION['vip_castle_qizil_verildi']
);
unset(
    $_SESSION['vip_castle_mob_reytinq_verildi']
);

?>


<div class="success">


<img
src="muxtelif/okey.png"
alt=""
>


Sizin oyunçu Vip qalasına yola düşdü,
10 saat sonra oyunçunu qaladan çıxarta bilersiz.


<br>


</div>


<br/>


<?php


    }

}


?>


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

<br/><br/>

<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>


<br>


<a href="index.php?">Çıxış (<?php echo htmlspecialchars($user_login); ?>)</a>


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


</body>


</html>
