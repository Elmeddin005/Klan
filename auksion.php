<?php

session_start();

require_once "config.php";
require_once "user_data.php";

$go = $_GET['go'] ?? '';

$my_id = (int)($_SESSION['user_id'] ?? 0);


/* OXUNMAMIŞ MƏKTUBLAR */

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


$aa = (int)($_POST['aa'] ?? $_GET['aa'] ?? 0);


$alish_min = (int)(
    $_POST['alish_min']
    ?? $_GET['alish_min']
    ?? 0
);


/*
|--------------------------------------------------------------------------
| ƏŞYA RƏNGİ
|--------------------------------------------------------------------------
*/

function esya_reng($reng)
{
    $rengler = [
        'sari'    => '#FFD700',
        'sarı'    => '#FFD700',
        'goy'     => '#0000FF',
        'göy'     => '#0088FF',
        'yasil'   => '#00AA00',
        'yaşıl'   => '#00AA00',
        'qirmizi' => '#FF0000',
        'qırmızı' => '#FF0000',
        'beyaz'   => '#FFFFFF',
        'ag'      => '#FFFFFF',
        'ağ'      => '#FFFFFF',
        'qara'    => '#000000'
    ];

    $reng = trim(
        mb_strtolower((string)$reng, 'UTF-8')
    );

    return $rengler[$reng] ?? '#FFFFFF';
}


/*
|--------------------------------------------------------------------------
| DƏYİŞƏNLƏR
|--------------------------------------------------------------------------
*/

$auksionlar = [];

$izle_auksion = null;


/*
|--------------------------------------------------------------------------
| MÜDDƏTİ BİTMİŞ AUKSİONLARI BAĞLA
|--------------------------------------------------------------------------
|
| bitme_vaxti UNIX timestamp olduğu üçün UNIX_TIMESTAMP() istifadə olunur.
|
*/

try {

    $stmt_expire = $pdo->prepare("
        DELETE FROM auksion
        WHERE bitme_vaxti > 0
          AND bitme_vaxti <= UNIX_TIMESTAMP()
    ");

    $stmt_expire->execute();

} catch (Exception $e) {

}

/*
|--------------------------------------------------------------------------
| TİP VƏ MƏRHƏLƏ FİLTRİ
|--------------------------------------------------------------------------
*/

function auksion_tip_filtri(
    &$sql,
    &$params,
    $aa,
    $alish_min
) {

    if ($aa > 0) {

        if ($aa == 99) {

            $sql .= " AND e.tip = 'mecun'";

        } elseif ($aa == 49) {

            $sql .= " AND e.tip = 'almaz'";

        } elseif ($aa == 1) {

            $sql .= " AND e.tip IN ('balta','qilinc')";

        } elseif ($aa == 2) {

            $sql .= " AND e.tip = 'debilqe'";

        } elseif ($aa == 3) {

            $sql .= " AND e.tip = 'amulet'";

        } elseif ($aa == 4) {

            $sql .= " AND e.tip = 'zireh'";

        } elseif ($aa == 5) {

            $sql .= " AND e.tip = 'kemer'";

        } elseif ($aa == 6) {

            $sql .= " AND e.tip = 'elcek'";

        } elseif ($aa == 7) {

            $sql .= " AND e.tip = 'ayaqqabi'";

        } elseif ($aa == 8) {

            $sql .= " AND e.tip = 'uzuk'";
        }
    }


    if ($alish_min > 0) {

        $sql .= " AND e.seviyye = :seviyye";

        $params[':seviyye'] = $alish_min;
    }
}


/*
|--------------------------------------------------------------------------
| MƏRKƏZ AUKSİON
|--------------------------------------------------------------------------
*/

if ($go === '') {

    $sql = "
        SELECT
            a.id,
            a.user_id,
            a.esya_id,
            a.say,
            a.qiymet_min,
            a.qiymet_max,
            a.tarix,
            a.bitme_vaxti,
            a.aktiv,

            e.ad,
            e.seviyye,
            e.reng,
            e.img,
            e.tip

        FROM auksion a

        INNER JOIN esyalar e
            ON e.id = a.esya_id

        WHERE a.aktiv = 1
    ";

    $params = [];

    auksion_tip_filtri(
        $sql,
        $params,
        $aa,
        $alish_min
    );

    $sql .= "
        ORDER BY a.id DESC
        LIMIT 20
    ";

    $stmt_auksion = $pdo->prepare($sql);

    $stmt_auksion->execute($params);

    $auksionlar = $stmt_auksion->fetchAll(
        PDO::FETCH_ASSOC
    );
}


/*
|--------------------------------------------------------------------------
| MƏNİM AUKSİONUM
|--------------------------------------------------------------------------
*/

if ($go === 'menim') {

    $sql = "
        SELECT
            a.id,
            a.user_id,
            a.esya_id,
            a.say,
            a.qiymet_min,
            a.qiymet_max,
            a.tarix,
            a.bitme_vaxti,
            a.aktiv,

            e.ad,
            e.seviyye,
            e.reng,
            e.img,
            e.tip

        FROM auksion a

        INNER JOIN esyalar e
            ON e.id = a.esya_id

        WHERE a.user_id = :user_id
    ";

    $params = [
        ':user_id' => $my_id
    ];

    auksion_tip_filtri(
        $sql,
        $params,
        $aa,
        $alish_min
    );

    $sql .= "
        ORDER BY a.id DESC
        LIMIT 20
    ";

    $stmt_menim = $pdo->prepare($sql);

    $stmt_menim->execute($params);

    $auksionlar = $stmt_menim->fetchAll(
        PDO::FETCH_ASSOC
    );
}


/*
|--------------------------------------------------------------------------
| AUKSİON İZLƏ
|--------------------------------------------------------------------------
|
| Eyni auksion.php daxilində:
|
| auksion.php?go=izle&uid=21
|
|--------------------------------------------------------------------------
*/

if ($go === 'izle') {

    $uid = (int)($_GET['uid'] ?? 0);

    if ($uid > 0) {

        $stmt_izle = $pdo->prepare("
            SELECT
                a.id,
                a.user_id,
                a.esya_id,
                a.say,
                a.qiymet_min,
                a.qiymet_max,
                a.tarix,
                a.bitme_vaxti,
                a.aktiv,

                e.ad,
                e.seviyye,
                e.reng,
                e.img,
                e.tip

            FROM auksion a

            INNER JOIN esyalar e
                ON e.id = a.esya_id

            WHERE a.id = :id

            LIMIT 1
        ");

        $stmt_izle->execute([
            ':id' => $uid
        ]);

        $izle_auksion = $stmt_izle->fetch(
            PDO::FETCH_ASSOC
        );
    }
}

?>

<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL">

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

<title>Auksion | Klan.Az Qanli Efsane</title>

<script>

function goGeri() {
    window.history.back();
}

</script>

</head>


<body>

<div
    class="main"
    style="word-wrap:break-word;"
>


<!-- ========================================================= -->
<!-- HEADER -->
<!-- ========================================================= -->

<div id="header">

<a href="menu.php?">

<img
    src="img/logo.png"
    alt=""
>

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


<div
    style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<!-- ========================================================= -->
<!-- EXP -->
<!-- ========================================================= -->

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
    style="width:<?php echo $progress; ?>%;height:10px;"
>

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>

</div>

</div>


<div
    style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<div class="info">


<?php
/*
|--------------------------------------------------------------------------
| AUKSİON İZLƏ
|--------------------------------------------------------------------------
*/

if ($go === 'izle') {

?>


<br>


<div class="list1">


<div class="center">

<div class="block_line">

<span class="green">

<b>Eşyanın göstəriciləri</b>

</span>

</div>

</div>


<br>


<?php if (empty($izle_auksion)): ?>


<div class="center">

<div class="block_line">

<span class="red">

Auksion tapılmadı və ya artıq mövcud deyil!

</span>

</div>

</div>


<?php else: ?>


<?php

if ($izle_auksion['tip'] === 'almaz') {

    $reng = '#000000';

} else {

    $reng = esya_reng(
        $izle_auksion['reng']
    );

}

?>


<div class="battle_log">

<div class="content">


<table
    border="0"
    cellpadding="0"
    cellspacing="0"
>


<tr>


<td>

<?php if (!empty($izle_auksion['img'])): ?>

<img
    src="<?php
        echo htmlspecialchars(
            $izle_auksion['img'],
            ENT_QUOTES,
            'UTF-8'
        );
    ?>"
    alt="foto"
/>

<?php endif; ?>

</td>


<td>


<font
    color="<?php
        echo htmlspecialchars(
            $reng,
            ENT_QUOTES,
            'UTF-8'
        );
    ?>"
>

<b>

<?php

echo htmlspecialchars(
    $izle_auksion['ad'],
    ENT_QUOTES,
    'UTF-8'
);

?>

</b>

</font>


<br><br>


<?php if ((int)$izle_auksion['seviyye'] > 0): ?>

<b>Merhələ:</b>

<?php echo (int)$izle_auksion['seviyye']; ?>

<br>

<?php endif; ?>


<?php if ((int)$izle_auksion['say'] > 1): ?>

<b>Miqdar:</b>

<?php echo (int)$izle_auksion['say']; ?>

<br>

<?php endif; ?>


</td>


</tr>


</table>


</div>

</div>


<br>


<div class="center">

<div class="block_line">

<span class="green">

<b>Satış Məlumatları</b>

</span>

</div>

</div>


<br>


<?php

/*
|--------------------------------------------------------------------------
| SATAN OYUNÇUNUN NİKİ
|--------------------------------------------------------------------------
*/

$satan_nik = 'Naməlum';


try {

    $stmt_satan = $pdo->prepare("
        SELECT login
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_satan->execute([
        ':id' => (int)$izle_auksion['user_id']
    ]);

    $satan = $stmt_satan->fetch(
        PDO::FETCH_ASSOC
    );

    if ($satan && !empty($satan['login'])) {

        $satan_nik = $satan['login'];

    }

} catch (Exception $e) {
}

?>


<b>Satışa çıxardıb:</b>

<?php

echo htmlspecialchars(
    $satan_nik,
    ENT_QUOTES,
    'UTF-8'
);

?>


<br>


<b>Minimum məbləğ:</b>

<?php

echo number_format(
    (int)$izle_auksion['qiymet_min'],
    0,
    '',
    ' '
);

?>

 Qızıl


<br>


<b>Maksimum məbləğ:</b>

<?php

echo number_format(
    (int)$izle_auksion['qiymet_max'],
    0,
    '',
    ' '
);

?>

 Qızıl


<br>


<b>Müddət:</b>

<?php

$bitme = (int)$izle_auksion['bitme_vaxti'];

if ($bitme > 0) {

    $qalan = $bitme - time();

    if ($qalan > 0) {

        $saat = floor($qalan / 3600);

        $deqiqe = floor(
            ($qalan % 3600) / 60
        );

        echo $saat . " saat ";

        echo $deqiqe . " dəqiqə";

    } else {

        echo "Müddət bitib";

    }

} else {

    echo "Müddət müəyyən edilməyib";

}

?>


<br>


<?php if ((int)$izle_auksion['tarix'] > 0): ?>

<b>Satış tarixi:</b>

<?php

echo date(
    'd.m.Y H:i',
    (int)$izle_auksion['tarix']
);

?>

<br>

<?php endif; ?>


<br>


<div class="line"></div>


<i>

Qiymət qoyan olmayıb

</i>


<br>


<br>


<b>Qiymət qoy:</b>


<form
    method="post"
    action="auksion.php?go=mebleq_ok&amp;tid=<?php echo (int)$izle_auksion['id']; ?>"
>


<input
    type="text"
    size="7"
    name="alish_min"
    maxlength="10"
    value="0"
/>


<input
    type="hidden"
    name="action"
    value="save"
/>


<input
    type="submit"
    class="button"
    value="Ok"
/>


</form>


<br>


<div class="menu">


<li>

<a
    href="auksion.php?go=al&amp;tid=<?php echo (int)$izle_auksion['id']; ?>"
>

<img
    src="muxtelif/auction.png"
    alt=""
>

Nəğd al:

<?php

echo number_format(
    (int)$izle_auksion['qiymet_max'],
    0,
    '',
    ' '
);

?>

 qızıl

</a>

</li>


</div>


<?php endif; ?>


</div>


<br>


<!-- ========================================================= -->
<!-- İZLƏ AŞAĞI MENYU -->
<!-- ========================================================= -->

<div class="menu">


<br>


<li>

<a href="auksion.php?">

<img
    src="muxtelif/auction.png"
    alt=""
>

Auksion

</a>

</li>


<li>

<a href="auksion.php?go=menim">

<img
    src="muxtelif/auction.png"
    alt=""
>

Mənim Auksionum

</a>

</li>


<li>

<a
    href="chantam.php?go=satish&amp;satiw=auksion"
>

<img
    src="muxtelif/sandiq.png"
    alt=""
>

Satış Yeri

</a>

</li>


</div>


<?php


/*
|--------------------------------------------------------------------------
| MƏRKƏZ AUKSİON
|--------------------------------------------------------------------------
*/

} elseif ($go === '') {

?>


<br>


<div class="list1">


<div class="center">

<div class="block_line">

<b>Mərkəz auksion</b>

<a href="auksion.php?go=melumat">?</a>

</div>

</div>


<br>


<form
    method="post"
    action="auksion.php?"
>


<div class="center">

Tip:

<br>


<select name="aa">

<option value="0">Hamsı</option>

<option value="99">Sehirli Mecun</option>

<option value="49">Daşlar</option>

<option value="1">Silah</option>

<option value="2">Debilge</option>

<option value="3">Amulet</option>

<option value="4">Zireh</option>

<option value="5">Kemer</option>

<option value="6">Elcek</option>

<option value="7">Ayaqqabı</option>

<option value="8">Üzük</option>

</select>


<br>


Merhələ:

<br>


<select name="alish_min">

<option value="0">Hamsı</option>


<?php for ($i = 1; $i <= 13; $i++): ?>

<option
    value="<?php echo $i; ?>"
    <?php echo (
        $alish_min == $i
        ? 'selected'
        : ''
    ); ?>
>

Merhələ[<?php echo $i; ?>]

</option>

<?php endfor; ?>


</select>


<br>


<input
    type="hidden"
    name="action"
    value="save"
>


<input
    type="submit"
    class="button"
    value="Axtar"
/>


</div>


</form>


<br>


<?php if (empty($auksionlar)): ?>


<div class="center">

<div class="block_line">

<span class="red">

Hal Hazırda Auksiona bu növ əşya çıxartılmayıb!

</span>

</div>

</div>


<br>


<!-- ƏŞYA YOXDURSA MENYU ORTADA -->


<div class="center">

<div class="menu">


<br>


<li>

<a href="auksion.php?go=menim">

<img
    src="muxtelif/auction.png"
    alt=""
>

Mənim Auksionum

</a>

</li>


<li>

<a
    href="chantam.php?go=satish&amp;satiw=auksion"
>

<img
    src="muxtelif/sandiq.png"
    alt=""
>

Satış Yeri

</a>

</li>


</div>

</div>


<?php else: ?>


<div class="center">

<div class="block_line">

<span class="green">

<b>Auksiona çıxarılanlar</b>

(<?php echo count($auksionlar); ?>)

</span>

</div>

</div>


<br>


<?php foreach ($auksionlar as $auk): ?>


<?php

if ($auk['tip'] === 'almaz') {

    $reng = '#000000';

} else {

    $reng = esya_reng(
        $auk['reng']
    );

}

?>


<div class="battle_log">

<div class="content">


<table
    border="0"
    cellpadding="0"
    cellspacing="0"
>


<tr>


<td>

<?php if (!empty($auk['img'])): ?>

<img
    src="<?php
        echo htmlspecialchars(
            $auk['img'],
            ENT_QUOTES,
            'UTF-8'
        );
    ?>"
    alt="foto"
/>

<?php endif; ?>

</td>


<td>


<a
    href="auksion.php?go=izle&amp;aa=<?php echo (int)$aa; ?>&amp;uid=<?php echo (int)$auk['id']; ?>"
>


<font
    color="<?php
        echo htmlspecialchars(
            $reng,
            ENT_QUOTES,
            'UTF-8'
        );
    ?>"
>


<?php

echo htmlspecialchars(
    $auk['ad'],
    ENT_QUOTES,
    'UTF-8'
);

?>


</font>

</a>


<br>


<b>Min.</b>

<?php

echo number_format(
    (int)$auk['qiymet_min'],
    0,
    '',
    ' '
);

?>

Qızıl


<br>


<b>Max.</b>

<?php

echo number_format(
    (int)$auk['qiymet_max'],
    0,
    '',
    ' '
);

?>

Qızıl


</td>


</tr>


</table>


</div>

</div>


<?php endforeach; ?>


<br>


<!-- ƏŞYA VARSA MENYU ƏŞYALARIN ALTINDA -->


<div class="list1">

<div class="menu">


<br>


<li>

<a href="auksion.php?go=menim">

<img
    src="muxtelif/auction.png"
    alt=""
>

Mənim Auksionum

</a>

</li>


<li>

<a
    href="chantam.php?go=satish&amp;satiw=auksion"
>

<img
    src="muxtelif/sandiq.png"
    alt=""
>

Satış Yeri

</a>

</li>


</div>

</div>


<?php endif; ?>


<?php


/*
|--------------------------------------------------------------------------
| MƏNİM AUKSİONUM
|--------------------------------------------------------------------------
*/

} elseif ($go === 'menim') {

?>


<br>


<div class="list1">


<div class="center">

<div class="block_line">

<b>Mənim Auksionum</b>

<a href="auksion.php?go=melumat">?</a>

</div>

</div>


<br>


<form
    method="post"
    action="auksion.php?go=menim"
>


<div class="center">

Tip:

<br>


<select name="aa">

<option value="0">Hamsı</option>

<option value="99">Sehirli Mecun</option>

<option value="49">Daşlar</option>

<option value="1">Silah</option>

<option value="2">Debilge</option>

<option value="3">Amulet</option>

<option value="4">Zireh</option>

<option value="5">Kemer</option>

<option value="6">Elcek</option>

<option value="7">Ayaqqabı</option>

<option value="8">Üzük</option>

</select>


<br>


Merhələ:

<br>


<select name="alish_min">

<option value="0">Hamsı</option>


<?php for ($i = 1; $i <= 13; $i++): ?>

<option
    value="<?php echo $i; ?>"
    <?php echo (
        $alish_min == $i
        ? 'selected'
        : ''
    ); ?>
>

Merhələ[<?php echo $i; ?>]

</option>

<?php endfor; ?>


</select>


<br>


<input
    type="hidden"
    name="action"
    value="save"
>


<input
    type="submit"
    class="button"
    value="Axtar"
/>


</div>


</form>


<br>


<?php if (empty($auksionlar)): ?>


<div class="center">

<div class="block_line">

<span class="red">

Hal-Hazırda Auksiona Əşya Çıxartmamısınız.!

</span>

</div>

</div>


<br>


<!-- ƏŞYA YOXDURSA ORTADA -->


<div class="center">

<div class="menu">


<br>


<li>

<a href="auksion.php?">

<img
    src="muxtelif/auction.png"
    alt=""
>

Auksion

</a>

</li>


<li>

<a
    href="chantam.php?go=satish&amp;satiw=auksion"
>

<img
    src="muxtelif/sandiq.png"
    alt=""
>

Satış Yeri

</a>

</li>


</div>

</div>


<?php else: ?>


<div class="center">

<div class="block_line">

<span class="green">

<b>Auksiona çıxardıqlarım</b>

(<?php echo count($auksionlar); ?>)

</span>

</div>

</div>


<br>


<?php foreach ($auksionlar as $auk): ?>


<?php

if ($auk['tip'] === 'almaz') {

    $reng = '#000000';

} else {

    $reng = esya_reng(
        $auk['reng']
    );

}

?>


<div class="battle_log">

<div class="content">


<table
    border="0"
    cellpadding="0"
    cellspacing="0"
>


<tr>


<td>

<?php if (!empty($auk['img'])): ?>

<img
    src="<?php
        echo htmlspecialchars(
            $auk['img'],
            ENT_QUOTES,
            'UTF-8'
        );
    ?>"
    alt="foto"
/>

<?php endif; ?>

</td>


<td>


<a
    href="auksion.php?go=izle&amp;aa=<?php echo (int)$aa; ?>&amp;uid=<?php echo (int)$auk['id']; ?>"
>


<font
    color="<?php
        echo htmlspecialchars(
            $reng,
            ENT_QUOTES,
            'UTF-8'
        );
    ?>"
>


<?php

echo htmlspecialchars(
    $auk['ad'],
    ENT_QUOTES,
    'UTF-8'
);

?>


</font>

</a>


<br>


<b>Min.</b>

<?php

echo number_format(
    (int)$auk['qiymet_min'],
    0,
    '',
    ' '
);

?>

Qızıl


<br>


<b>Max.</b>

<?php

echo number_format(
    (int)$auk['qiymet_max'],
    0,
    '',
    ' '
);

?>

Qızıl


<br>


<?php if ((int)$auk['aktiv'] === 1): ?>



<?php else: ?>



<?php endif; ?>


</td>


</tr>


</table>


</div>

</div>


<?php endforeach; ?>


<br>


<div class="list1">

<div class="menu">


<br>


<li>

<a href="auksion.php?">

<img
    src="muxtelif/auction.png"
    alt=""
>

Auksion

</a>

</li>


<li>

<a
    href="chantam.php?go=satish&amp;satiw=auksion"
>

<img
    src="muxtelif/sandiq.png"
    alt=""
>

Satış Yeri

</a>

</li>


</div>

</div>


<?php endif; ?>


<?php


/*
|--------------------------------------------------------------------------
| MƏLUMAT
|--------------------------------------------------------------------------
*/

} elseif ($go === 'melumat') {

?>


<br>


<div class="center">

<div class="block_line">

<b>Mərkəz auksion</b>

</div>

</div>


<br>


Auksionda əşyalar digər oyunçular tərəfindən satışa çıxarılır.


<br><br>


<div class="center">

<div class="menu">


<li>

<a href="auksion.php?">

Auksion

</a>

</li>


<li>

<a href="auksion.php?go=menim">

Mənim Auksionum

</a>

</li>


</div>

</div>


<?php

}

?>


</div>


<!-- ========================================================= -->
<!-- FOOTER -->
<!-- ========================================================= -->

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


<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>


<br><br>


<a href="cixis.php?">

Çıxış
(<?php

echo htmlspecialchars(
    $user_login ?? '',
    ENT_QUOTES,
    'UTF-8'
);

?>)

</a>


<br><br>


<a href="menu.php?dil=tr">

Türkce:

<img
    alt="türkce"
    src="http://macera.az/klan/muxtelif/tr.gif"
    title="Türkce"
/>

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