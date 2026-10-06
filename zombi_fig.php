<?php

session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT qızıl, brılyant, enerjı,oyuncunun_seviyyesi,oyuncunun_tecrubesi
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $_SESSION['user_id']
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit('İstifadəçi tapılmadı.');
}

/*
==========================================================
 ZOMBIE CASTLE
==========================================================
*/

$vuruldu = (
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
);

$qalib = (
    isset($_GET['qalib']) &&
    (
        $_GET['qalib'] === 'ok' ||
        $_GET['qalib'] === '1'
    )
);

$group_number = isset($_GET['qrup_num'])
    ? (int)$_GET['qrup_num']
    : 0;
if (
    $group_number > 0 &&
    isset($_SESSION['zombie_battle'][$group_number]) &&
    isset($_SESSION['zombie_battle'][$group_number]['finished']) &&
    $_SESSION['zombie_battle'][$group_number]['finished'] === true &&
    !$qalib
) {

    header(
        'Location: zombi_yarad.php?go=meglub' .
        '&qrup_num=' . (int)$group_number .
        '&xal=' .
        (int)$_SESSION['zombie_battle'][$group_number]['score'] .
        '&qalib=1'
    );

    exit;
}

/*
==========================================================
 OYUNCU
==========================================================
*/

$player_id = isset($_SESSION['player_id'])
    ? (int)$_SESSION['player_id']
    : 1000749;

$player_name = '***YALQUZAQ***';

$player_level = 14;

$player_full_hp = 34587;

$player_hp_after = 34583;


/*
==========================================================
 ZOMBİ
==========================================================
*/

$zombie_id = 1056;

$zombie_name = 'Zombie';

$zombie_level = 14;

$zombie_full_hp = 16033;

$zombie_hp_after = 0;


/*
==========================================================
 HEADER
==========================================================
*/

require_once "seviyyeler.php";

$tecrube = (int)$user['oyuncunun_tecrubesi'];
$seviyye = tecrubeye_gore_seviyye($tecrube);

$baslangic_tecrubesi = (int)$seviyye_tecrubeleri[$seviyye]['min'];
$bitis_tecrubesi = (int)$seviyye_tecrubeleri[$seviyye]['max'];

if ($bitis_tecrubesi > $baslangic_tecrubesi) {
    $experience_percent = (
        ($tecrube - $baslangic_tecrubesi) /
        ($bitis_tecrubesi - $baslangic_tecrubesi)
    ) * 100;
} else {
    $experience_percent = 0;
}

$experience_percent = max(0, min(100, $experience_percent));
$experience_percent = round($experience_percent);
$gold = 198999008;

$brilliant = 16868;

$energy = 50;


/*
==========================================================
 DÖYÜŞ MƏLUMATLARI
==========================================================
*/

$experience_reward = 16;

$gold_reward = 190;

$damage = 25637;


/*
==========================================================
 MƏCUNLAR
==========================================================
*/

$mecunlar = array(

    array(
        'name'  => 'Can Mecunu 5%',
        'image' => 'img/mecunlar/can5.jpg'
    ),

    array(
        'name'  => 'Can Mecunu 10%',
        'image' => 'img/mecunlar/can10.jpg'
    ),

    array(
        'name'  => 'Zərbə Mecunu 5%',
        'image' => 'img/mecunlar/zerbe5.jpg'
    ),

    array(
        'name'  => 'Zərbə Mecunu 10%',
        'image' => 'img/mecunlar/zerbe10.jpg'
    ),

    array(
        'name'  => 'Müdafiə Mecunu 5%',
        'image' => 'img/mecunlar/mudafie5.jpg'
    ),

    array(
        'name'  => 'Müdafiə Mecunu 10%',
        'image' => 'img/mecunlar/mudafie10.jpg'
    ),

    array(
        'name'  => 'Sehrli Mecun 20%',
        'image' => 'img/mecunlar/sehirli20.jpg'
    )

);


/*
==========================================================
 ƏŞYALAR - 14 SƏVİYYƏ
==========================================================
*/

$esyalar = array(

    array(
        'name'  => 'Göy Amulet',
        'image' => 'img/esyalar/14amuletgoy.jpg'
    ),

    array(
        'name'  => 'Göy Ayaqqabı',
        'image' => 'img/esyalar/14ayaqqabigoy.jpg'
    ),

    array(
        'name'  => 'Göy Balta',
        'image' => 'img/esyalar/14baltagoy.jpg'
    ),

    array(
        'name'  => 'Göy Dəbilqə',
        'image' => 'img/esyalar/14debilqegoy.jpg'
    ),

    array(
        'name'  => 'Göy Əlcək',
        'image' => 'img/esyalar/14elcekgoy.jpg'
    ),

    array(
        'name'  => 'Göy Qılınc',
        'image' => 'img/esyalar/14qilincgoy.jpg'
    ),

    array(
        'name'  => 'Göy Kəmər',
        'image' => 'img/esyalar/14kemergoy.jpg'
    ),

    array(
        'name'  => 'Göy Üzük',
        'image' => 'img/esyalar/14uzukgoy.jpg'
    ),

    array(
        'name'  => 'Yaşıl Amulet',
        'image' => 'img/esyalar/14amuletyasil.jpg'
    ),

    array(
        'name'  => 'Yaşıl Ayaqqabı',
        'image' => 'img/esyalar/14ayaqqabiyasil.jpg'
    ),

    array(
        'name'  => 'Yaşıl Balta',
        'image' => 'img/esyalar/14baltayasil.jpg'
    ),

    array(
        'name'  => 'Yaşıl Dəbilqə',
        'image' => 'img/esyalar/14debilqeyasil.jpg'
    ),

    array(
        'name'  => 'Yaşıl Əlcək',
        'image' => 'img/esyalar/14elcekyasil.jpg'
    ),

    array(
        'name'  => 'Yaşıl Qılınc',
        'image' => 'img/esyalar/14qilincyasil.jpg'
    ),

    array(
        'name'  => 'Yaşıl Kəmər',
        'image' => 'img/esyalar/14kemeryasil.jpg'
    ),

    array(
        'name'  => 'Yaşıl Üzük',
        'image' => 'img/esyalar/14uzukyasil.jpg'
    )

);


/*
==========================================================
 DÖYÜŞ ID
==========================================================
*/

if (
    isset($_GET['doyus_id']) &&
    $_GET['doyus_id'] !== ''
) {

    $doyus_id = (string)$_GET['doyus_id'];

} else {

    $doyus_id = uniqid('zombie_', true);

}


/*
==========================================================
 QRUP DÖYÜŞ STATE
==========================================================
*/

if (!isset($_SESSION['zombie_battle'])) {

    $_SESSION['zombie_battle'] = array();

}


if (
    $group_number > 0 &&
    !isset($_SESSION['zombie_battle'][$group_number])
) {

    $_SESSION['zombie_battle'][$group_number] = array(

        'stage' => 1,

        'zombie_count' => 5,

        'score' => 0,

        'last_attack' => time(),

        'started' => true,

        'defeated' => false,

        'finished' => false,

        /*
         * Bu döyüş artıq hesablanıbmı?
         */
        'rewarded' => false

    );

}


if (
    $vuruldu &&
    $group_number > 0
) {

    if (
        isset($_SESSION['zombie_battle'][$group_number])
    ) {

        $group_battle =
            &$_SESSION['zombie_battle'][$group_number];


        /*
        ==================================================
        QRUP HƏLƏ BİTMƏYİBSƏ
        ==================================================
        */

        if (
            !isset($group_battle['finished']) ||
            $group_battle['finished'] !== true
        ) {


            /*
            ==============================================
            MOB ÖLDÜRÜLDÜ
            +500 XAL
            ==============================================
            */

            $group_battle['score'] =
                (int)$group_battle['score'] + 10000;


            /*
            ==============================================
            MOB SAYINI 1 AZALT
            ==============================================
            */

            $group_battle['zombie_count'] =
                (int)$group_battle['zombie_count'] - 1;
if ((int)$group_battle['score'] == 70000) {

    $group_battle['score'] = 70000;
    $group_battle['finished'] = true;
    $group_battle['started'] = false;
    $group_battle['zombie_count'] = 0;

    header(
        'Location: zombi_yarad.php?go=meglub'
        . '&qrup_num=' . (int)$group_number
        . '&xal=70000'
        . '&qalib=1'
    );

    exit;
}

            /*
            ==============================================
            SON MOB ÖLDÜRÜLDÜ VƏ XAL 70.000 OLDU
            ==============================================
            */

            if (
                (int)$group_battle['score'] >= 70000 &&
                (int)$group_battle['zombie_count'] <= 0
            ) {

                $group_battle['score'] = 70000;

                $group_battle['zombie_count'] = 0;

                $group_battle['finished'] = true;

                $group_battle['started'] = false;

                $group_battle['defeated'] = false;


                /*
                ==========================================
                QALİBLİK SƏHİFƏSİNƏ KEÇ
                ==========================================
                */

                header(
                    'Location: zombi_yarad.php?go=meglub' .
                    '&qrup_num=' . (int)$group_number .
                    '&xal=70000' .
                    '&qalib=1'
                );

                exit;
            }


            /*
            ==============================================
            MOB BİTDİ, AMMA QRUP HƏLƏ DAVAM EDİR
            ==============================================
            */

            if (
                (int)$group_battle['zombie_count'] <= 0
            ) {

                $group_battle['stage'] =
                    (int)$group_battle['stage'] + 1;

                $group_battle['zombie_count'] = 5;
            }

        }

    }

}


/*
==========================================================
 REWARD SESSION
==========================================================
*/

if (!isset($_SESSION['zombie_reward'])) {

    $_SESSION['zombie_reward'] = array();

}


/*
==========================================================
 MÜKAFAT SİSTEMİ

 90% = BOŞ
 5% = ƏŞYA
 5% = MƏCUN
==========================================================
*/

if ($vuruldu) {

    if (
        !isset(
            $_SESSION['zombie_reward'][$doyus_id]
        )
    ) {

        $reward_chance = rand(1, 100);


        if ($reward_chance <= 90) {

            $_SESSION['zombie_reward'][$doyus_id] = array(

                'type' => 'empty',

                'has_reward' => false

            );


        } elseif ($reward_chance <= 95) {

            $random_esya_index =
                array_rand($esyalar);

            $random_esya =
                $esyalar[$random_esya_index];


            $_SESSION['zombie_reward'][$doyus_id] = array(

                'type' => 'esya',

                'has_reward' => true,

                'name' =>
                    $random_esya['name'],

                'image' =>
                    $random_esya['image']

            );


        } else {

            $random_mecun_index =
                array_rand($mecunlar);

            $random_mecun =
                $mecunlar[$random_mecun_index];


            $_SESSION['zombie_reward'][$doyus_id] = array(

                'type' => 'mecun',

                'has_reward' => true,

                'name' =>
                    $random_mecun['name'],

                'image' =>
                    $random_mecun['image']

            );

        }

    }

}


/*
==========================================================
 HEADER
==========================================================
*/

function showZombieHeader(
    $player_id,
    $experience_percent,
    $gold,
    $brilliant,
    $energy
) {

    global $user;

?>

<div class="top">

<a href="menu.php?id=<?php echo $player_id; ?>">

<img
src="img/tec.png"
title="Tecrübe"
alt=""
/>

</a>

<?php echo $experience_percent; ?>%

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



<?php

}

?>

<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL" />

<meta
name="keywords"
content="klan.az, azgame, online oyun, qrup döyüşləri, Zombie Castle"
/>

<meta
name="description"
content="Azerbaycanda ilk Mobil Online oyunu"
/>

<link rel="stylesheet" href="css.css">

<meta
content="text/html; charset=utf-8"
http-equiv="content-type"
/>

<meta
name="viewport"
content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"
/>

<title>Zombie Castle</title>

</head>

<body>

<div
class="main"
style="word-wrap:break-word;"
>

<?php


/*
==========================================================
 QRUP MƏLUMATLARINI GÖTÜR
==========================================================
*/

if ($group_number > 0) {

    if (
        isset(
            $_SESSION['zombie_battle'][$group_number]
        )
    ) {

        $group_battle =
            $_SESSION['zombie_battle'][$group_number];

        $group_stage =
            isset($group_battle['stage'])
            ? (int)$group_battle['stage']
            : 1;

        $group_score =
            isset($group_battle['score'])
            ? (int)$group_battle['score']
            : 0;

    } else {

        $group_stage = 1;

        $group_score = 0;

    }

} else {

    $group_stage = 1;

    $group_score = 0;

}


/*
==========================================================
 1. ƏLDƏ ETDİKLƏRİNİZ
==========================================================
*/

if ($qalib) {

    showZombieHeader(
        $player_id,
        $experience_percent,
        $gold,
        $brilliant,
        $energy
    );

?>

<br/>

<div class="center">

<div class="block_line">

<span class="green">

Siz Qalib Geldiz!

</span>

</div>

</div>

<br/>

<div class="point-line"></div>

<img
src="img/tec.png"
alt=" "
/>

<span class="grey">

Tecrübe: +<?php
echo $experience_reward;
?>

</span>

<br/>

<img
src="img/coin.png"
alt=" "
/>

<span class="grey">

Qızıl: +<?php
echo $gold_reward;
?>

</span>

<br/>

<div class="point-line"></div>


<?php

$reward = null;

if (
    isset(
        $_SESSION['zombie_reward'][$doyus_id]
    )
) {

    $reward =
        $_SESSION['zombie_reward'][$doyus_id];

}


if (
    $reward &&
    isset($reward['type']) &&
    $reward['type'] === 'mecun' &&
    isset($reward['has_reward']) &&
    $reward['has_reward'] === true
) {

?>

<div
class="battle_log"
style="
max-width:100%;
box-sizing:border-box;
overflow:hidden;
word-wrap:break-word;
overflow-wrap:anywhere;
"
>

<div
style="
display:inline-flex;
width:auto;
max-width:100%;
align-items:center;
box-sizing:border-box;
vertical-align:middle;
"
>

<img
src="<?php
echo htmlspecialchars(
    $reward['image'],
    ENT_QUOTES,
    'UTF-8'
);
?>"
alt=""
style="
width:40px;
height:40px;
object-fit:contain;
display:block;
flex:none;
margin-right:5px;
"
/>

<div
style="
width:auto;
max-width:100%;
font-size:13px;
line-height:normal;
word-wrap:break-word;
overflow-wrap:anywhere;
"
>

<b>

Təbriklər! Siz məcun tapdınız:

</b>

<br/>

<a href="canta.php">

<?php

echo htmlspecialchars(
    $reward['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

</a>

<br/>

<span>

Məcun çantanıza göndərildi.

</span>

</div>

</div>

</div>


<?php

} elseif (
    $reward &&
    isset($reward['type']) &&
    $reward['type'] === 'esya' &&
    isset($reward['has_reward']) &&
    $reward['has_reward'] === true
) {

?>

<div
class="battle_log"
style="
max-width:100%;
box-sizing:border-box;
overflow:hidden;
word-wrap:break-word;
overflow-wrap:anywhere;
"
>

<div
style="
display:inline-flex;
width:auto;
max-width:100%;
align-items:center;
box-sizing:border-box;
vertical-align:middle;
"
>

<img
src="<?php
echo htmlspecialchars(
    $reward['image'],
    ENT_QUOTES,
    'UTF-8'
);
?>"
alt=""
style="
width:40px;
height:40px;
object-fit:contain;
display:block;
flex:none;
margin-right:5px;
"
/>

<div
style="
width:auto;
max-width:100%;
font-size:13px;
line-height:normal;
word-wrap:break-word;
overflow-wrap:anywhere;
"
>

<b>

Təbriklər! Siz əşya tapdınız:

</b>

<br/>

<a href="canta.php">

<?php

echo htmlspecialchars(
    $reward['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

</a>

<br/>

<span>

Əşya çantanıza göndərildi.

</span>

</div>

</div>

</div>


<?php

} else {

?>

<div class="battle_log">

Siz heç bir əşya əldə etmədiniz!

<br/>

</div>

<?php

}

?>

<div class="menu">

<br/>

<li>

<a
href="kordinat3.php?go=deyis&amp;qrup_num=<?php
echo $group_number;
?>"
>

<img
src="img/go_next.png"
alt=" "
/>

Zombie Castle

</a>

</li>

<br/>

</div>


<?php


/*
==========================================================
 2. ZƏRBƏDƏN SONRA
==========================================================
*/

} elseif (
    $vuruldu &&
    $group_number > 0 &&
    isset($_SESSION['zombie_battle'][$group_number]) &&
    isset($_SESSION['zombie_battle'][$group_number]['finished']) &&
    $_SESSION['zombie_battle'][$group_number]['finished'] === true
) {

    header(
        'Location: zombi_yarad.php?go=meglub' .
        '&qrup_num=' . (int)$group_number .
        '&xal=' .
        (int)$_SESSION['zombie_battle'][$group_number]['score'] .
        '&qalib=1'
    );

    exit;

} elseif ($vuruldu) {

    showZombieHeader(
        $player_id,
        $experience_percent,
        $gold,
        $brilliant,
        $energy
    );

?>

<br/>

<div class="center">

<div class="block_line">

<span class="green">

Siz Qalib Geldiz!

</span>

</div>

</div>

<br/>

<br/>

Siz

<b>

<font style="color:#FF0000">

<?php echo $damage; ?> (Krit)

</font>

</b>

zərbə vurdunuz

<br/>

<div class="battle_log">

Sizin canınız:

<img
src="img/can.png"
alt="Can"
/>


<br/>

</div>

Rəqib vurdu

<b>

başa

</b>

siz zərbəni dəf etdiniz

<br/>

<div class="battle_log">

Rəqibin canı:

<img
src="img/mobcan.png"
alt="Can"
/>



<br/>

</div>


<div class="menu">

<hr/>

<li>

<a
href="zombi_fig.php?qalib=ok&amp;uid=<?php
echo $zombie_id;
?>&amp;lis=647178575&amp;qrup_num=<?php
echo $group_number;
?>&amp;doyus_id=<?php
echo urlencode($doyus_id);
?>"
>

<img
src="muxtelif/okey.png"
alt=" "
/>

Əldə etdikləriniz

</a>

</li>

<hr/>

</div>


<?php


/*
==========================================================
 3. İLK DÖYÜŞ SƏHİFƏSİ
==========================================================
*/

} else {

    showZombieHeader(
        $player_id,
        $experience_percent,
        $gold,
        $brilliant,
        $energy
    );

?>

<br/>

<div class="battle_log">

<b>

<a
href="infoforce.php?uid=<?php
echo $player_id;
?>"
>

<?php echo $player_name; ?>

</a>

[<?php echo $player_level; ?>]

</b>

(<?php
echo $player_full_hp;
?>/<?php
echo $player_full_hp;
?>)

<br/>

</div>

<small>

<b>VS</b>

</small>

<br/>

<div class="battle_log">

<b>

<?php echo $zombie_name; ?>

[<?php echo $zombie_level; ?>]

</b>

(<?php
echo $zombie_full_hp;
?>/<?php
echo $zombie_full_hp;
?>)

<br/>

</div>

<span class="dark-brown">

Raund: 1

</span>

<form
method="post"
action="zombi_fig.php?go=vurdum&amp;uid=<?php
echo $zombie_id;
?>&amp;lis=647178575&amp;qrup_num=<?php
echo $group_number;
?>&amp;doyus_id=<?php
echo urlencode($doyus_id);
?>"
>

<b>

Hücum:

</b>

<br/>

<select name="hucum">

<option value="0">
Başdan
</option>

<option value="1">
Sinədən
</option>

<option value="2">
Gövdədən
</option>

<option value="3">
Ayaqdan
</option>

</select>

<br/>

<b>

Müdafiə:

</b>

<br/>

<select name="mudafie">

<option value="0">
Baş və Sinə
</option>

<option value="1">
Sinə və Gövdə
</option>

<option value="2">
Gövdə və Ayaq
</option>

<option value="3">
Ayaq və Baş
</option>

</select>

<br/>

<input
type="hidden"
name="action"
value="save"
/>

<input
type="submit"
class="button"
value="Zərbə Vur"
/>

<br/>

<hr/>

<small>

<i>

Qalib gəldiyiniz halda təcrübə,
Qızıl və əşya qazanmaq şansınız var.

</i>

</small>

<br/>

</form>

<?php

}

?>

</div>

</body>

</html>