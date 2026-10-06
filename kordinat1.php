<?php

session_start();
require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
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

/* ==========================================================
   BIG CASTLE / KOORDİNAT SİSTEMİ
   ========================================================== */

$obstacle_x = isset($_GET['x']) ? (int)$_GET['x'] : -1;
$obstacle_y = isset($_GET['y']) ? (int)$_GET['y'] : -1;

$obstacle_key = $obstacle_x . '_' . $obstacle_y;

$castle_result = null;
$castle_remaining = 0;
$cooldown = 30;


/* ==========================================================
   WARRIOR BAŞLANĞIC KOORDİNATI
   ========================================================== */

if (!isset($_SESSION['bigcastle_warrior_x'])) {
    $_SESSION['bigcastle_warrior_x'] = 3;
}

if (!isset($_SESSION['bigcastle_warrior_y'])) {
    $_SESSION['bigcastle_warrior_y'] = 4;
}


/* ==========================================================
   KOORDİNAT HƏRƏKƏTİ
   ========================================================== */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'deyis' &&
    isset($_GET['semt'])
) {

    $wx = (int)$_SESSION['bigcastle_warrior_x'];
    $wy = (int)$_SESSION['bigcastle_warrior_y'];

    switch ($_GET['semt']) {

        case 'yuxari':
            $wy--;
            break;

        case 'ashaqi':
            $wy++;
            break;

        case 'geri':
            $wx--;
            break;

        case 'ireli':
            $wx++;
            break;
    }


    /* X sərhədi */

    if ($wx < 0) {
        $wx = 0;
    }

    if ($wx > 8) {
        $wx = 8;
    }


    /* Y sərhədi */

    if ($wy < 0) {
        $wy = 0;
    }

    if ($wy > 10) {
        $wy = 10;
    }


    $_SESSION['bigcastle_warrior_x'] = $wx;
    $_SESSION['bigcastle_warrior_y'] = $wy;


    header('Location: kordinat1.php?go=deyis');
    exit;
}


/* ==========================================================
   WARRIOR KOORDİNATINI XÜSUSİ OLARAQ YADDA SAXLA
   ========================================================== */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'save_warrior' &&
    isset($_GET['wx']) &&
    isset($_GET['wy'])
) {

    $save_x = (int)$_GET['wx'];
    $save_y = (int)$_GET['wy'];

    if (
        $save_x >= 0 &&
        $save_x <= 8 &&
        $save_y >= 0 &&
        $save_y <= 10
    ) {

        $_SESSION['bigcastle_warrior_x'] = $save_x;
        $_SESSION['bigcastle_warrior_y'] = $save_y;
    }

    exit;
}


/* ==========================================================
   MANEƏ NƏTİCƏLƏRİ
   ========================================================== */

$castle_results = [

    '1_1' => 'random',
    '3_1' => 'random',
    '6_2' => 'random',
    '1_3' => 'random',
    '4_3' => 'random',
    '6_4' => 'random',
    '2_5' => 'random',
    '4_6' => 'random',
    '1_6' => 'random',
    '6_7' => 'random',
    '2_8' => 'random',
    '5_9' => 'random'

];


/* ==========================================================
   SESSION MANEƏLƏRİ
   ========================================================== */

if (!isset($_SESSION['bigcastle_obstacles'])) {
    $_SESSION['bigcastle_obstacles'] = [];
}


/* ==========================================================
   SANDIQ MÜKAFATLARI
   ========================================================== */

if (!isset($_SESSION['bigcastle_chest_rewards'])) {
    $_SESSION['bigcastle_chest_rewards'] = [];
}


/* ==========================================================
   KÖHNƏ MANEƏLƏRİ TƏMİZLƏ
   ========================================================== */

foreach ($_SESSION['bigcastle_obstacles'] as $key => $data) {

    if (!isset($data['time'])) {

        unset($_SESSION['bigcastle_obstacles'][$key]);
        continue;
    }

    $passed = time() - $data['time'];

    if ($passed >= $cooldown) {

        unset($_SESSION['bigcastle_obstacles'][$key]);

        if (isset($_SESSION['bigcastle_chest_rewards'][$key])) {

            unset($_SESSION['bigcastle_chest_rewards'][$key]);
        }
    }
}


/* ==========================================================
   SANDIQ AÇMA
   ========================================================== */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'ac_sandiq' &&
    $obstacle_x >= 0 &&
    $obstacle_y >= 0
) {

    /* Həqiqi sandıq yoxdursa */

    if (
        !isset($_SESSION['bigcastle_obstacles'][$obstacle_key]) ||
        !isset($_SESSION['bigcastle_obstacles'][$obstacle_key]['result']) ||
        $_SESSION['bigcastle_obstacles'][$obstacle_key]['result'] !== 'sandiq'
    ) {

        header('Location: kordinat1.php?go=deyis');
        exit;
    }


    /* Sandıq artıq açılıbsa */

    if (
        isset(
            $_SESSION['bigcastle_chest_rewards'][$obstacle_key]
        )
    ) {

        header(
            'Location: kordinat1.php?go=sandiq&x=' .
            $obstacle_x .
            '&y=' .
            $obstacle_y
        );

        exit;
    }


    /* ======================================================
   SANDIQ MÜKAFATI

   92% = BOŞ
   8%  = AĞ ALMAZ
   ====================================================== */

$chest_random = rand(1, 100);

if ($chest_random <= 92) {

    $_SESSION['bigcastle_chest_rewards'][$obstacle_key] = [

        'name' => 'Təəssüf ki, Sandıq Boşdur',

        'image' => 'qala/sandiq/3.png',

        'type' => 'empty',

        'time' => time()
    ];

} else {

    /* ==================================================
       AĞ ALMAZ
       ================================================== */

    $agalmaz_esya_id = 368; // BURADA Ağ Almazın esya_id-si olmalıdır

    /* Əvvəl çantada Ağ Almaz varmı yoxla */

    $stmt_agalmaz = $pdo->prepare("
        SELECT id
        FROM canta
        WHERE user_id = :user_id
          AND esya_id = :esya_id
        LIMIT 1
    ");

    $stmt_agalmaz->execute([
        ':user_id' => $my_id,
        ':esya_id' => $agalmaz_esya_id
    ]);

    $canta_agalmaz_id = $stmt_agalmaz->fetchColumn();


    if ($canta_agalmaz_id) {

        /* Çantada varsa sayını artır */

        $stmt_agalmaz_artir = $pdo->prepare("
            UPDATE canta
            SET say = say + 1
            WHERE id = :id
              AND user_id = :user_id
        ");

        $stmt_agalmaz_artir->execute([
            ':id' => $canta_agalmaz_id,
            ':user_id' => $my_id
        ]);

    } else {

        /* Çantada yoxdursa yeni sətir yarat */

        $stmt_agalmaz_elave = $pdo->prepare("
            INSERT INTO canta
            (
                user_id,
                esya_id,
                say,
                geyimde,
                son_daxil_olma_vaxti
            )
            VALUES
            (
                :user_id,
                :esya_id,
                1,
                0,
                :vaxt
            )
        ");

        $stmt_agalmaz_elave->execute([
            ':user_id' => $my_id,
            ':esya_id' => $agalmaz_esya_id,
            ':vaxt' => time()
        ]);
    }


    /* Ekranda mükafat göstər */

    $_SESSION['bigcastle_chest_rewards'][$obstacle_key] = [

        'name' => 'Təbriklər! Ağ Almaz Qazandınız!',

        'image' => 'img/almazlar/agalmaz.jpg',

        'type' => 'agalmaz',

        'time' => time()
    ];
}


header(
    'Location: kordinat1.php?go=sandiq&x=' .
    $obstacle_x .
    '&y=' .
    $obstacle_y
);

exit;
}

/* ==========================================================
   MANEƏYƏ BASILDI
   ========================================================== */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'manea' &&
    $obstacle_x >= 0 &&
    $obstacle_y >= 0
) {

    /* Əvvəl açılıbsa */

    if (
        isset(
            $_SESSION['bigcastle_obstacles'][$obstacle_key]
        )
    ) {

        $opened_time =
            $_SESSION['bigcastle_obstacles'][$obstacle_key]['time'];

        $passed = time() - $opened_time;

        if ($passed < $cooldown) {

            $castle_remaining =
                $cooldown - $passed;

            header(
                'Location: kordinat1.php?go=qalabagli' .
                '&x=' . $obstacle_x .
                '&y=' . $obstacle_y .
                '&time=' . $castle_remaining
            );

            exit;
        }


        unset(
            $_SESSION['bigcastle_obstacles'][$obstacle_key]
        );


        if (
            isset(
                $_SESSION['bigcastle_chest_rewards'][$obstacle_key]
            )
        ) {

            unset(
                $_SESSION['bigcastle_chest_rewards'][$obstacle_key]
            );
        }
    }


    /* ======================================================
       MANEƏ EHTİMALLARI

       40% = BOŞ
       52% = MOB
       8%  = SANDIQ
       ====================================================== */

    $random_result = rand(1, 100);

    if ($random_result <= 40) {

        /* 1 - 40 = 40% BOŞ */

        $castle_result = 'bos';

    } elseif ($random_result <= 92) {

        /* 41 - 92 = 52% MOB */

        $castle_result = 'mob';

    } else {

        /* 93 - 100 = 8% SANDIQ */

        $castle_result = 'sandiq';
    }


    $_SESSION['bigcastle_obstacles'][$obstacle_key] = [

        'time' => time(),

        'result' => $castle_result
    ];


    /* ======================================================
       MOB
       ====================================================== */

    if ($castle_result === 'mob') {

        header(
            'Location: bot_hucum.php' .
            '?go=gonder' .
            '&qala=bigcastle' .
            '&x=' . $obstacle_x .
            '&y=' . $obstacle_y
        );

        exit;
    }


    /* ======================================================
       SANDIQ
       ====================================================== */

    if ($castle_result === 'sandiq') {

        header(
            'Location: kordinat1.php' .
            '?go=sandiq' .
            '&x=' . $obstacle_x .
            '&y=' . $obstacle_y
        );

        exit;
    }


    /* ======================================================
       BOŞ
       ====================================================== */

    if ($castle_result === 'bos') {

        header(
            'Location: kordinat1.php' .
            '?go=qalabos' .
            '&x=' . $obstacle_x .
            '&y=' . $obstacle_y
        );

        exit;
    }
}


/* ==========================================================
   AKTİV MANEƏLƏRİ JS-Ə VER
   ========================================================== */

$bigcastle_cooldowns = [];

foreach ($_SESSION['bigcastle_obstacles'] as $key => $data) {

    if (!isset($data['time'])) {
        continue;
    }

    $passed = time() - $data['time'];

    $remaining = $cooldown - $passed;

    if ($remaining > 0) {

        $bigcastle_cooldowns[$key] = $remaining;
    }
}


/* ==========================================================
   WARRIOR KOORDİNATI
   ========================================================== */

$bigcastle_warrior_x =
    (int)$_SESSION['bigcastle_warrior_x'];

$bigcastle_warrior_y =
    (int)$_SESSION['bigcastle_warrior_y'];


/* ==========================================================
   SANDIQ MÜKAFATI
   ========================================================== */

$current_chest_reward = null;

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'sandiq' &&
    isset(
        $_SESSION['bigcastle_chest_rewards'][$obstacle_key]
    )
) {

    $current_chest_reward =
        $_SESSION['bigcastle_chest_rewards'][$obstacle_key];
}

?>

<script>

window.bigcastleCooldowns =
<?php
echo json_encode(
    $bigcastle_cooldowns,
    JSON_UNESCAPED_UNICODE
);
?>;

window.bigcastleWarrior = {
    x: <?php echo $bigcastle_warrior_x; ?>,
    y: <?php echo $bigcastle_warrior_y; ?>
};

</script>


<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL">

<meta name="keywords"
content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar">

<meta name="description"
content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры">

<meta
content="text/html; charset=utf-8"
http-equiv="content-type">

<meta
name="viewport"
content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<link rel="stylesheet" href="css.css">

<title>kordinat</title>


<style>

.castle-map {

    height: 200px !important;
    min-height: 0 !important;
    padding: 5px !important;
    margin: 0 !important;
    overflow: hidden !important;
}

.world-map {

    position: relative;
    overflow: hidden;
    width: 187px;
    height: 200px;
    margin: 0 auto;

    background: url(img/world/bg.png);
    background-repeat: repeat;
}

.world-map img {

    vertical-align: top;
}

</style>


<script src="js/bigcastle.js"></script>

</head>


<body>


<div
class="main"
style="
word-wrap:break-word;
overflow-wrap:anywhere;
width:100%;
max-width:100%;
min-width:0;
box-sizing:border-box;
overflow-x:hidden;
">


<!-- ======================================================
     HEADER
     ====================================================== -->

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


/* ==========================================================
   BIG CASTLE XƏRİTƏSİ
   ========================================================== */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'deyis'
) {

?>

<div class="info castle-map">

<div class="center">

<b>Qalanın adı:</b> Big Castle

<br>

<b>
Warrior koordinatı:
X<?php echo $bigcastle_warrior_x; ?>,
Y<?php echo $bigcastle_warrior_y; ?>
</b>

<br><br>


<center>

<div class="world-map">


<!-- SƏTİR 1 -->

<img
src="img/world/16.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/12.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<br>


<!-- SƏTİR 2 -->

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<a
href="kordinat1.php?go=deyis&semt=yuxari"
>

<img
src="img/world/move/up.png"
height="40"
width="40"
border="0"
>

</a>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<br>


<!-- SƏTİR 3 -->

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<a
href="kordinat1.php?go=deyis&semt=geri"
>

<img
src="img/world/move/left.png"
height="40"
width="40"
border="0"
>

</a>


<img
src="img/world/warrior.png"
height="40"
width="40"
border="0"
>


<a
href="kordinat1.php?go=deyis&semt=ireli"
>

<img
src="img/world/move/right.png"
height="40"
width="40"
border="0"
>

</a>

<br>


<!-- SƏTİR 4 -->

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<a
href="kordinat1.php?go=deyis&semt=ashaqi"
>

<img
src="img/world/move/down.png"
height="40"
width="40"
border="0"
>

</a>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<br>


<!-- SƏTİR 5 -->

<img
src="img/world/19.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/13.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/8.png"
height="40"
width="40"
border="0"
>

<img
src="img/world/empty.png"
height="40"
width="40"
border="0"
>


</div>

</center>


<br>


<div class="menu">

<li>

<a href="kordinat1.php?">

Geri

</a>

</li>

</div>


</div>

</div>


<?php


/* ==========================================================
   BOŞ MANEƏ
   ========================================================== */

} elseif (
    isset($_GET['go']) &&
    $_GET['go'] === 'qalabos'
) {

?>

<div class="info">

<div class="center">

<span style="color:#AF0000">

Bu maneənin altı boşdur.

</span>

<br><br>


<div class="menu">

<li>

<a href="kordinat1.php?go=deyis">

<img
src="img/go_next.png"
alt=" "
>

Big Castle

</a>

</li>

</div>

</div>

</div>


<?php


/* ==========================================================
   SANDIQ
   ========================================================== */

} elseif (
    isset($_GET['go']) &&
    $_GET['go'] === 'sandiq'
) {

?>

<div class="info">

<div class="center">


<?php

if (!$current_chest_reward) {

?>

<br>

<div class="center">

<div class="block_line">

<span class="green">

Siz Sandıq Tapdınız!

</span>

</div>

</div>

<br>


<img
src="qala/sandiq/3.png"
alt="Sandıq"
width="50"
height="50"
>


<div class="menu">

<li>

<a
href="kordinat1.php?go=ac_sandiq&amp;x=<?php
echo $obstacle_x;
?>&amp;y=<?php
echo $obstacle_y;
?>"
>

<br>

<img
src="img/go_next.png"
alt=" "
>

Sandığı Aç

</a>

</li>

</div>


<?php

} else {


/* ======================================================
   AĞ ALMAZ
   ====================================================== */

if (
    isset($current_chest_reward['type']) &&
    $current_chest_reward['type'] === 'agalmaz'
) {

?>

<br>

<div class="center">

<div class="block_line">

<span class="green">

Təbriklər! Ağ Almaz Qazandınız!

</span>

</div>

</div>

<br>

<div class="point-line"></div>


<div class="battle_log">

<img
src="<?php
echo htmlspecialchars(
    $current_chest_reward['image'],
    ENT_QUOTES,
    'UTF-8'
);
?>"
alt="Ağ Almaz"
width="40"
height="40"
style="vertical-align:middle;"
>

<span class="grey">

Ağ Almaz çantanıza göndərildi

<a
href="canta.php"
style="
color:#008000;
font-weight:bold;
text-decoration:underline;
"
>

Ağ Almaz

</a>

</span>

</div>


<div class="point-line"></div>


<div class="menu">

<br>

<li>

<a href="kordinat1.php?go=deyis">

<img
src="img/go_next.png"
alt=" "
>

Big Castle

</a>

</li>

</div>


<?php


/* ======================================================
   SANDIQ BOŞ
   ====================================================== */

} elseif (
    isset($current_chest_reward['type']) &&
    $current_chest_reward['type'] === 'empty'
) {

?>

<br>

<div class="center">

<div class="block_line">

<span style="color:#AF0000;">

Təəssüf ki, Sandıq Boşdur

</span>

</div>

</div>


<div class="menu">

<br>

<li>

<a href="kordinat1.php?go=deyis">

<img
src="img/go_next.png"
alt=" "
>

Big Castle

</a>

</li>

</div>


<?php

}

}

?>

</div>

</div>


<?php


/* ==========================================================
   MANEƏ BAĞLI
   ========================================================== */

} elseif (
    isset($_GET['go']) &&
    $_GET['go'] === 'qalabagli'
) {

    $time =
        isset($_GET['time'])
        ? intval($_GET['time'])
        : 5;

?>

<div class="info">

<div class="center">

<br>

<span class="red">

<b><?php echo $time; ?></b>

san sonra bu maneəni axtara bilərsiniz.

</span>

<br><br>


<div class="menu">

<br>

<li>

<a href="kordinat1.php?go=deyis">

<img
src="img/go_next.png"
alt=" "
>

Big Castle

</a>

</li>

</div>

</div>

</div>


<?php


/* ==========================================================
   KÖHNƏ QALA SƏHİFƏSİ
   ========================================================== */

} elseif (
    isset($_GET['go']) &&
    $_GET['go'] === 'qala'
) {

?>

<div class="info">

<div class="center">

<span class="red">

<b>5</b>

san sonra bu manieni axtara bilersiz

</span>

<br>


<div class="menu">

<br>

<li>

<a href="kordinat1.php?go=deyis">

<img
src="img/go_next.png"
alt=" "
>

Big Castle

</a>

</li>

</div>

</div>

</div>


<?php


/* ==========================================================
   ƏSAS SƏHİFƏ
   ========================================================== */

} else {

?>

<div class="info">

<div class="center">

<b>Qalanın adı:</b> Big Castle

<br><br>

<b>Haqqında:</b>

Siz bu qaladan sandıqlar axtaracaqsınız.
Sandığın içində qiymətli daşlar, sehrli məcunlar və
<b>BRİLLİANT</b> olacaq.

Bunun üçün bütün maneələrin üstünə vurmalısınız.

Maneələrin altında sandıqlar və onların açarları var.
Əvvəlcə sandığı, sonra isə onun kilidini aça biləcək
açarı tapmalısınız.

Bu halda qalanı qoruyan moblar sizə hücum edəcək.


<br>


<div class="menu">

<br>

<li>

<a href="kordinat1.php?go=deyis">

Qalaya Giriş

</a>

</li>

</div>

<br>

</div>

</div>


<?php

}

?>


<!-- ======================================================
     FOOTER
     ====================================================== -->

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

<br>


<a href="index.php?">
    Çıxış (<?php echo htmlspecialchars($user_login, ENT_QUOTES, 'UTF-8'); ?>)
</a>

<br><br>


<a href="menu.php?dil=tr">

Türkce:

<img
alt="türkce"
src="http://macera.az/klan/muxtelif/tr.gif"
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

</div>


</body>

</html>