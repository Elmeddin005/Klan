<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}




/* ==========================================================
   PARAMETRLƏR
   ========================================================== */

$go = isset($_GET['go'])
    ? (string)$_GET['go']
    : '';

$group_number = isset($_GET['qrup_num'])
    ? (int)$_GET['qrup_num']
    : 0;

$battle_return = (
    isset($_GET['battle_return']) &&
    $_GET['battle_return'] === '1'
);

$logo_return = (
    isset($_GET['go']) &&
    $_GET['go'] === 'logo_qayit'
);

$is_gift_open = (
    isset($_GET['ac']) &&
    $_GET['ac'] === 'ok'
);


/* ==========================================================
   OYUNÇU
   ========================================================== */

$player_id = isset($_SESSION['player_id'])
    ? (int)$_SESSION['player_id']
    : 1000749;

$player_name = '***YALQUZAQ***';


/* ==========================================================
   ZOMBİ QRUPLARINI YARAT
   ========================================================== */

if (
    !isset($_SESSION['zombie_groups']) ||
    !is_array($_SESSION['zombie_groups'])
) {
    $_SESSION['zombie_groups'] = array();
}


/* ==========================================================
   OYUNÇUNUN QRUPU VARMI?
   ========================================================== */

$player_has_group = false;
$player_group_number = 0;

foreach (
    $_SESSION['zombie_groups']
    as $gid => $group
) {

    if (
        !isset($group['members']) ||
        !is_array($group['members'])
    ) {
        continue;
    }

    foreach (
        $group['members']
        as $member
    ) {

        if (
            isset($member['id']) &&
            (int)$member['id'] === $player_id
        ) {

            $player_has_group = true;

            $player_group_number =
                (int)$gid;

            break 2;
        }
    }
}


/* ==========================================================
   DÖYÜŞ SİSTEMİNİ YARAT
   ========================================================== */

if (
    !isset($_SESSION['zombie_battle']) ||
    !is_array($_SESSION['zombie_battle'])
) {
    $_SESSION['zombie_battle'] = array();
}


/* ==========================================================
   DÖYÜŞ MESAJLARINI YARAT
   ========================================================== */

if (
    !isset($_SESSION['zombie_battle_messages']) ||
    !is_array($_SESSION['zombie_battle_messages'])
) {
    $_SESSION['zombie_battle_messages'] = array();
}


/* ==========================================================
   DÖYÜŞ MÜDDƏTİ
   ========================================================== */

$battle_timeout = 300;


/* ==========================================================
   QRUPU GÖTÜR
   ========================================================== */

$current_group = null;

if (
    $group_number > 0 &&
    isset(
        $_SESSION['zombie_groups']
        [$group_number]
    ) &&
    is_array(
        $_SESSION['zombie_groups']
        [$group_number]
    )
) {

    $current_group =
        $_SESSION['zombie_groups']
        [$group_number];
}


/* ==========================================================
   QRUP YOXDURSA
   ========================================================== */

if (
    $group_number <= 0 ||
    $current_group === null
) {

    header(
        'Location: zombi_yarad.php?go=qruplar'
    );

    exit;
}


/* ==========================================================
   LƏĞV OLUNMUŞ QRUP
   ========================================================== */

if (
    isset($current_group['cancelled']) &&
    $current_group['cancelled'] === true
) {

    header(
        'Location: zombi_yarad.php?go=grup&qrup_num=' .
        (int)$group_number
    );

    exit;
}


/* ==========================================================
   OYUN BAŞLAMAYIBSA
   ========================================================== */

if (
    (
        !isset($current_group['started']) ||
        $current_group['started'] !== true
    ) &&
    !$logo_return &&
    !$battle_return &&
    !$is_gift_open &&
    $go !== 'logo_cix' &&
    $go !== 'logo_cix_yes' &&
    $go !== 'logo_qayit'
) {

    header(
        'Location: zombi_yarad.php?go=q&qrup_num=' .
        (int)$group_number
    );

    exit;
}


/* ==========================================================
   LƏĞV OLUNMUŞ QRUP
   ========================================================== */

if (
    isset($current_group['cancelled']) &&
    $current_group['cancelled'] === true
) {

    header(
        'Location: zombi_yarad.php?go=grup&qrup_num=' .
        (int)$group_number
    );

    exit;
}


/* ==========================================================
   OYUN BAŞLAMAYIBSA
   ========================================================== */

if (
    (
        !isset($current_group['started']) ||
        $current_group['started'] !== true
    ) &&
    !$logo_return &&
    !$battle_return &&
    !$is_gift_open &&
    $go !== 'logo_cix' &&
    $go !== 'logo_cix_yes' &&
    $go !== 'logo_qayit'
) {

    header(
        'Location: zombi_yarad.php?go=q&qrup_num=' .
        (int)$group_number
    );

    exit;
}
/* ==========================================================
   OYUNÇU QRUPUN ÜZVÜDÜRMÜ?
   ========================================================== */

$player_is_member = false;

if (
    isset($current_group['members']) &&
    is_array($current_group['members'])
) {

    foreach (
        $current_group['members']
        as $member
    ) {

        if (
            isset($member['id']) &&
            (int)$member['id'] === (int)$player_id
        ) {

            $player_is_member = true;
            break;
        }
    }
}


/* ==========================================================
   OYUNÇU ƏVVƏL QRUPDAN ÇIXIBMI?
   ========================================================== */

$player_left_group = false;

if (
    isset($current_group['left_players']) &&
    is_array($current_group['left_players'])
) {

    if (
        in_array(
            $player_id,
            $current_group['left_players'],
            true
        )
    ) {

        $player_left_group = true;
    }
}


/* ==========================================================
   LOGO ÇIXIŞ XƏBƏRDARLIĞI
   ========================================================== */

$logo_exit_warning = false;

if (
    $go === 'logo_cix' &&
    $current_group !== null
) {

    $logo_exit_warning = true;

    $_SESSION['zombie_logo_return'] =
        'kordinat3.php?go=deyis&qrup_num=' .
        (int)$group_number;
}


/* ==========================================================
   LOGO ÇIXIŞINI TƏSDİQLƏ
   ========================================================== */

if (
    $go === 'logo_cix_yes' &&
    $current_group !== null
) {

    /* ======================================================
       QRUP HƏLƏ VARMI?
       ====================================================== */

    if (
        !isset(
            $_SESSION['zombie_groups'][$group_number]
        )
    ) {

        header(
            'Location: zombi_yarad.php?go=qruplar'
        );

        exit;
    }


    /* ======================================================
       ƏN SON QRUP MƏLUMATINI GÖTÜR
       ====================================================== */

    $current_group =
        $_SESSION['zombie_groups'][$group_number];


    /* ======================================================
       OYUNÇUNU QRUPDAN ÇIXART
       ====================================================== */

    $new_members = array();

    if (
        isset(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        ) &&
        is_array(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        )
    ) {

        foreach (
            $_SESSION['zombie_groups']
            [$group_number]['members']
            as $member
        ) {

            if (
                !isset($member['id']) ||
                (int)$member['id'] !== (int)$player_id
            ) {

                $new_members[] = $member;
            }
        }
    }


    $_SESSION['zombie_groups']
    [$group_number]['members'] =
        $new_members;
if (
    count(
        $_SESSION['zombie_groups']
        [$group_number]['members']
    ) === 0
) {

    $_SESSION['zombie_groups']
    [$group_number]['started'] = false;

    $_SESSION['zombie_groups']
    [$group_number]['empty_group_time'] = time();

    $_SESSION['zombie_groups']
    [$group_number]['battle_delete_time'] = 0;
}

    /* ======================================================
       ÇIXAN OYUNÇUNU YADDA SAXLA
       ====================================================== */

    if (
        !isset(
            $_SESSION['zombie_groups']
            [$group_number]['left_players']
        ) ||
        !is_array(
            $_SESSION['zombie_groups']
            [$group_number]['left_players']
        )
    ) {

        $_SESSION['zombie_groups']
        [$group_number]['left_players'] =
            array();
    }


    if (
        !in_array(
            $player_id,
            $_SESSION['zombie_groups']
            [$group_number]['left_players'],
            true
        )
    ) {

        $_SESSION['zombie_groups']
        [$group_number]['left_players'][] =
            $player_id;
    }


    /* ======================================================
       OWNER YOXDURSA BAŞQA OYUNÇUNU OWNER ET
       ====================================================== */

    $owner_exists = false;

    foreach (
        $_SESSION['zombie_groups']
        [$group_number]['members']
        as $member
    ) {

        if (
            isset($member['owner']) &&
            $member['owner'] === true
        ) {

            $owner_exists = true;
            break;
        }
    }


    if (
        !$owner_exists &&
        count(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        ) > 0
    ) {

        $_SESSION['zombie_groups']
        [$group_number]['members'][0]['owner'] =
            true;
    }


    /* ======================================================
       DÖYÜŞ QRUPU SİLİNMƏ VAXTI
       ====================================================== */

    if (
        !isset(
            $_SESSION['zombie_groups']
            [$group_number]['battle_delete_time']
        ) ||
        (int)$_SESSION['zombie_groups']
        [$group_number]['battle_delete_time'] <= 0
    ) {

        /*
         * Qrup 15 saniyə aktiv siyahıda qalsın.
         */
        $_SESSION['zombie_groups']
        [$group_number]['battle_delete_time'] =
            time() + 15;
    }


    /* ======================================================
       AKTİV QRUPLARDA 15 SANİYƏ GÖRÜNSÜN
       ====================================================== */

    $_SESSION['zombie_groups']
    [$group_number]['active_list_until'] =
        time() + 15;


    /* ======================================================
       ÇIXIŞ STATUSU
       ====================================================== */

    $_SESSION['zombie_exit_group'] =
        $group_number;


    /* ======================================================
       QRUP SESSION-DA QALIR
       ====================================================== */

    header(
        'Location: zombi_yarad.php?go=exit_done&qrup_num=' .
        (int)$group_number
    );

    exit;
}


/* ==========================================================
   DÖYÜŞ QRUPUNUN SİLİNMƏ VAXTINI YOXLAMA
   ========================================================== */

if (
    isset(
        $_SESSION['zombie_groups']
        [$group_number]['battle_delete_time']
    ) &&
    (int)$_SESSION['zombie_groups']
    [$group_number]['battle_delete_time'] > 0
) {

    $battle_delete_time =
        (int)$_SESSION['zombie_groups']
        [$group_number]['battle_delete_time'];


    if (
        time() >= $battle_delete_time
    ) {

        unset(
            $_SESSION['zombie_groups']
            [$group_number]
        );


        if (
            isset(
                $_SESSION['zombie_messages']
                [$group_number]
            )
        ) {

            unset(
                $_SESSION['zombie_messages']
                [$group_number]
            );
        }


        if (
            isset(
                $_SESSION['zombie_battle']
                [$group_number]
            )
        ) {

            unset(
                $_SESSION['zombie_battle']
                [$group_number]
            );
        }


        if (
            isset(
                $_SESSION['zombie_battle_messages']
                [$group_number]
            )
        ) {

            unset(
                $_SESSION['zombie_battle_messages']
                [$group_number]
            );
        }


        header(
            'Location: zombi_yarad.php?go=qruplar'
        );

        exit;
    }
}


/* ==========================================================
   BU QRUP ÜÇÜN DÖYÜŞ YOXDURSA YARAT
   ========================================================== */

if (
    !isset(
        $_SESSION['zombie_battle']
        [$group_number]
    ) ||
    !is_array(
        $_SESSION['zombie_battle']
        [$group_number]
    )
) {

    $_SESSION['zombie_battle']
    [$group_number] = array(

        'stage' => 1,

        'zombie_count' => 5,

        'score' => 0,

        'last_attack' => time(),

        'finished' => false

    );
}


/* ==========================================================
   DÖYÜŞ MƏLUMATI
   ========================================================== */

$battle_data =
    $_SESSION['zombie_battle']
    [$group_number];


/* ==========================================================
   BOŞ / ƏSKİ DƏYƏRLƏRİ TAMAMLA
   ========================================================== */

if (
    !isset($battle_data['stage'])
) {

    $battle_data['stage'] = 1;
}


if (
    !isset($battle_data['zombie_count'])
) {

    $battle_data['zombie_count'] = 5;
}


if (
    !isset($battle_data['score'])
) {

    $battle_data['score'] = 0;
}


if (
    !isset($battle_data['last_attack']) ||
    (int)$battle_data['last_attack'] <= 0
) {

    $battle_data['last_attack'] = time();
}


if (
    !isset($battle_data['finished'])
) {

    $battle_data['finished'] = false;
}


/* ==========================================================
   XAL LİMİTİ
   ========================================================== */

if (
    (int)$battle_data['score'] > 70000
) {

    $battle_data['score'] = 70000;
}


/* ==========================================================
   SESSION-A GERİ YAZ
   ========================================================== */

$_SESSION['zombie_battle']
[$group_number] =
    $battle_data;


/* ==========================================================
   DÖYÜŞ DƏYƏRLƏRİ
   ========================================================== */

$stage =
    (int)$battle_data['stage'];

$zombie_count =
    (int)$battle_data['zombie_count'];

$score =
    (int)$battle_data['score'];


/* ==========================================================
   DÖYÜŞ VAXTI
   ========================================================== */

$last_attack =
    (int)$battle_data['last_attack'];

$passed_attack_time =
    time() - $last_attack;


/* ==========================================================
   VAXT BİTİBSƏ
   ========================================================== */

if (
    !$is_gift_open &&
    $battle_data['finished'] !== true &&
    $passed_attack_time >= $battle_timeout
) {

    /*
     * OYUNÇUNU MƏĞLUB OLDUĞU QRUPDAN ÇIXART
     */

    if (
        isset(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        ) &&
        is_array(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        )
    ) {

        $new_members = array();

        foreach (
            $_SESSION['zombie_groups']
            [$group_number]['members']
            as $member
        ) {

            if (
                !isset($member['id']) ||
                (int)$member['id'] !== (int)$player_id
            ) {

                $new_members[] = $member;
            }
        }

        $_SESSION['zombie_groups']
        [$group_number]['members'] =
            $new_members;
    }


    /*
     * OWNER ÇIXIBSA YENİ OWNER VER
     */

    $owner_exists = false;

    foreach (
        $_SESSION['zombie_groups']
        [$group_number]['members']
        as $member
    ) {

        if (
            isset($member['owner']) &&
            $member['owner'] === true
        ) {

            $owner_exists = true;
            break;
        }
    }


    if (
        !$owner_exists &&
        count(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        ) > 0
    ) {

        $_SESSION['zombie_groups']
        [$group_number]['members'][0]['owner'] =
            true;
    }


    /*
     * ƏGƏR QRUP TAM BOŞ QALIBSA
     */

    if (
        count(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        ) === 0
    ) {

        $_SESSION['zombie_groups']
        [$group_number]['started'] = false;

        $_SESSION['zombie_groups']
        [$group_number]['empty_group_time'] =
            time();
    }


    /*
     * MƏĞLUBİYYƏT SƏHİFƏSİNƏ KEÇ
     */

    header(
        'Location: zombi_yarad.php?go=meglub&qrup_num=' .
        (int)$group_number .
        '&xal=' .
        (int)$score
    );

    exit;
}


/* ==========================================================
   QALAN VAXT
   ========================================================== */

$battle_time =
    $battle_timeout -
    $passed_attack_time;


if (
    $battle_time < 0
) {

    $battle_time = 0;
}


/* ==========================================================
   POST — MESAJ GÖNDƏRMƏ
   ========================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['message'])
) {

    /*
     * Vaxt bitibsə mesaj qəbul etmə.
     */
    if (
        $battle_time <= 0
    ) {

        header(
            'Location: zombi_yarad.php?go=meglub&qrup_num=' .
            (int)$group_number .
            '&xal=' .
            (int)$score
        );

        exit;
    }


    $message =
        trim((string)$_POST['message']);


    if (
        $message !== ''
    ) {

        if (
            function_exists('mb_substr')
        ) {

            $message =
                mb_substr(
                    $message,
                    0,
                    120,
                    'UTF-8'
                );

        } else {

            $message =
                substr(
                    $message,
                    0,
                    120
                );
        }


        if (
            !isset(
                $_SESSION['zombie_battle_messages']
                [$group_number]
            ) ||
            !is_array(
                $_SESSION['zombie_battle_messages']
                [$group_number]
            )
        ) {

            $_SESSION['zombie_battle_messages']
            [$group_number] =
                array();
        }


        $_SESSION['zombie_battle_messages']
        [$group_number][] = array(

            'id' =>
                $player_id,

            'name' =>
                $player_name,

            'message' =>
                $message,

            'time' =>
                time()

        );
    }


    header(
        'Location: kordinat3.php?go=deyis&qrup_num=' .
        (int)$group_number
    );

    exit;
}


/* ==========================================================
   SON QRUP MƏLUMATINI GÖTÜR
   ========================================================== */

if (
    isset(
        $_SESSION['zombie_groups']
        [$group_number]
    )
) {

    $current_group =
        $_SESSION['zombie_groups']
        [$group_number];
}

?>
<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL">

<meta
name="keywords"
content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar"
>

<meta
name="description"
content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры"
>

<link rel="stylesheet" href="css.css">

<meta
content="text/html; charset=utf-8"
http-equiv="content-type"
>

<meta
name="viewport"
content="width=device-width, initial-scale=1.0, maximum-scale=3.0"
>

<title>Zombie Castle</title>


<style>

.zombie-logo-warning {
    text-align: center;
    line-height: 1.7;
}

.zombie-logo-warning a {
    color: #850E0E;
    font-weight: bold;
    text-decoration: none;
}

.zombie-logo-warning a:hover {
    color: #850E0E;
    text-decoration: none;
}

</style>


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


<!-- ======================================================
     HEADER
     ====================================================== -->

<div id="header">


<?php if ($current_group !== null) { ?>

<a
href="kordinat3.php?go=logo_cix&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

<img
src="img/logo.png"
alt="Zombie Castle"
>

</a>

<?php } else { ?>

<a
href="kordinat3.php?go=deyis&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

<img
src="img/logo.png"
alt="Zombie Castle"
>

</a>

<?php } ?>


<div class="icons"></div>


<div class="main_foot">

<div class="grey">


<img src="img/coin.png" title="Qızıl" alt=""/>
<?php echo (int)$user['qızıl']; ?>

<img src="img/brill.png" title="Brilliant" alt=""/>
<?php echo (int)$user['brılyant']; ?>

<img src="img/energy.png" title="Enerji" alt=""/>
<?php echo (int)$user['enerjı']; ?>


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
/* GO = MELUMAT */
if (
    $go === 'melumat' &&
    $current_group !== null
) {
?>

<div class="info">

    <b>Qalanın adı-</b>Zombie Castle <br/>

    <b>Qrupun adı:</b>
    <?php
    echo htmlspecialchars(
        $current_group['name'],
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
    <br/>

    <b>Oyunun Gerginliyi:</b> Sade <br/>

    <b>Döyüşçü Tutumu:</b>
    <?php echo (int)$current_group['capacity']; ?>
    <br/>

    <b>Etab:</b> 1/9 <br/>

    <b>Zombi:</b> 5 <br/>

    <b>Qrupda olanlar:</b><br/>

    <div class="menu">

        <?php foreach (
            $current_group['members'] as $member
        ) { ?>

        <li>
            <a href="infoforce.php?uid=<?php
                echo (int)$member['id'];
            ?>">
                <img src="klan_img/1.gif" alt=""/>
                <?php
                echo htmlspecialchars(
                    $member['name'],
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
                [14] (Xal:0)
            </a>
        </li>

        <?php } ?>

    </div>

    <hr/>

    <b>Hediyyeler:</b><br/>

    <div class="battle_log">
        <img width="48" height="42"
             src="zombi/qutu1.png" alt=""/>
        = (3000 Xal)<br/>
    </div>

    <div class="battle_log">
        <img width="48" height="42"
             src="zombi/qutu2.png" alt=""/>
        = (5000 Xal)<br/>
    </div>

    <div class="battle_log">
        <img width="48" height="42"
             src="zombi/qutu3.png" alt=""/>
        = (10000 Xal)<br/>
    </div>

    <div class="battle_log">
        <img width="48" height="42"
             src="zombi/qutu4.png" alt=""/>
        = (50000 Xal)<br/>
    </div>

    <div class="battle_log">
        <img width="48" height="42"
             src="zombi/qutu5.png" alt=""/>
        = (70000 Xal)<br/>
    </div>

    <div class="menu">
        <br/>

        <li>
            <a href="kordinat3.php?go=deyis&amp;qrup_num=<?php
                echo (int)$group_number;
            ?>">
                <img src="img/go_next.png" alt=" "/>
                Zombie Castle
            </a>
        </li>
    </div>

</div>

<?php
}
?>
<?php
/* ======================================================
   HƏDİYYƏ BÖLMƏSİ
   ====================================================== */

if (
    isset($_GET['ac']) &&
    $_GET['ac'] === 'ok'
) {

    $qutu = isset($_GET['qutu'])
        ? (int)$_GET['qutu']
        : 0;

/* ======================================================
   AÇILMIŞ QUTULARI YADDA SAXLA
   ====================================================== */

if (
    !isset($_SESSION['zombie_opened_boxes']) ||
    !is_array($_SESSION['zombie_opened_boxes'])
) {
    $_SESSION['zombie_opened_boxes'] = array();
}

if (
    !isset($_SESSION['zombie_opened_boxes'][$group_number]) ||
    !is_array($_SESSION['zombie_opened_boxes'][$group_number])
) {
    $_SESSION['zombie_opened_boxes'][$group_number] = array();
}

if ($qutu >= 1 && $qutu <= 5) {

    $_SESSION['zombie_opened_boxes']
        [$group_number]
        [$qutu] = true;
}
/* ======================================================
   QUTU MÜKAFATINI YADDA SAXLA
   ====================================================== */

if (
    !isset($_SESSION['zombie_box_rewards']) ||
    !is_array($_SESSION['zombie_box_rewards'])
) {
    $_SESSION['zombie_box_rewards'] = array();
}

if (
    !isset($_SESSION['zombie_box_rewards'][$group_number]) ||
    !is_array($_SESSION['zombie_box_rewards'][$group_number])
) {
    $_SESSION['zombie_box_rewards'][$group_number] = array();
}
/* ======================================================
   QUTUDAN TƏSADÜFİ MÜKAFAT
   ====================================================== */



/* ======================================================
   1-Cİ QUTU — YALNIZ GÖY ƏŞYALAR
   ====================================================== */

$random_goy_esyalar = array(

    array(
        'ad' => 'Xrom qılınc',
        'img' => 'img/esyalar/14qilincgoy.jpg'
    ),

    array(
        'ad' => 'Xrom Balta',
        'img' => 'img/esyalar/14baltagoy.jpg'
    ),

    array(
        'ad' => 'Xrom Amulet',
        'img' => 'img/esyalar/14amuletgoy.jpg'
    ),

    array(
        'ad' => 'Xrom Ayaqqabı',
        'img' => 'img/esyalar/14ayaqqabigoy.jpg'
    ),

    array(
        'ad' => 'Xrom Kəmər',
        'img' => 'img/esyalar/14kemergoy.jpg'
    ),

    array(
        'ad' => 'Xrom Zireh',
        'img' => 'img/esyalar/14zirehgoy.jpg'
    ),

    array(
        'ad' => 'Xrom Üzük',
        'img' => 'img/esyalar/14uzukgoy.jpg'
    ),

    array(
        'ad' => 'Xrom Əlcək',
        'img' => 'img/esyalar/14elcekgoy.jpg'
    ),

    array(
        'ad' => 'Xrom Dəbilqə',
        'img' => 'img/esyalar/14debilqegoy.jpg'
    )

);


/* ======================================================
   3-CÜ VƏ 4-CÜ QUTU — YALNIZ YAŞIL ƏŞYALAR
   ====================================================== */

$random_yasil_esyalar = array(

    array(
        'ad' => 'Xrom qılınc',
        'img' => 'img/esyalar/14qilincyasil.jpg'
    ),

    array(
        'ad' => 'Xrom Balta',
        'img' => 'img/esyalar/14baltayasil.jpg'
    ),

    array(
        'ad' => 'Xrom Amulet',
        'img' => 'img/esyalar/14amuletyasil.jpg'
    ),

    array(
        'ad' => 'Xrom Ayaqqabı',
        'img' => 'img/esyalar/14ayaqqabiyasil.jpg'
    ),

    array(
        'ad' => 'Xrom Kəmər',
        'img' => 'img/esyalar/14kemeryasil.jpg'
    ),

    array(
        'ad' => 'Xrom Zireh',
        'img' => 'img/esyalar/14zirehyasil.jpg'
    ),

    array(
        'ad' => 'Xrom Üzük',
        'img' => 'img/esyalar/14uzukyasil.jpg'
    ),

    array(
        'ad' => 'Xrom Əlcək',
        'img' => 'img/esyalar/14elcekyasil.jpg'
    ),

    array(
        'ad' => 'Xrom Dəbilqə',
        'img' => 'img/esyalar/14debilqeyasil.jpg'
    )

);


/* ======================================================
   2-Cİ QUTU — YALNIZ 5% VƏ 10% MƏCUNLAR
   ====================================================== */

$random_mecunlar_2 = array(

    array(
        'ad' => '5% Can Mecunu',
        'img' => 'img/mecunlar/can5.jpg'
    ),

    array(
        'ad' => '5% Mudafie Mecunu',
        'img' => 'img/mecunlar/mudafie5.jpg'
    ),

    array(
        'ad' => '5% Zerbe Mecunu',
        'img' => 'img/mecunlar/zerbe5.jpg'
    ),

    array(
        'ad' => '10% Zerbe Mecunu',
        'img' => 'img/mecunlar/zerbe10.jpg'
    ),

    array(
        'ad' => '10% Mudafie Mecunu',
        'img' => 'img/mecunlar/mudafie10.jpg'
    ),

    array(
        'ad' => '10% Can Mecunu',
        'img' => 'img/mecunlar/can10.jpg'
    )

);


/* ======================================================
   5-Cİ QUTU — YALNIZ 30% VƏ 40% MƏCUNLAR
   ====================================================== */

$random_mecunlar_5 = array(

    array(
        'ad' => '30% Can Mecunu',
        'img' => 'img/mecunlar/can30.jpg'
    ),

    array(
        'ad' => '30% Mudafie Mecunu',
        'img' => 'img/mecunlar/mudafie30.jpg'
    ),

    array(
        'ad' => '30% Zerbe Mecunu',
        'img' => 'img/mecunlar/zerbe30.jpg'
    ),

    array(
        'ad' => '40% Zerbe Mecunu',
        'img' => 'img/mecunlar/zerbe40.jpg'
    ),

    array(
        'ad' => '40% Mudafie Mecunu',
        'img' => 'img/mecunlar/mudafie40.jpg'
    ),

    array(
        'ad' => '40% Can Mecunu',
        'img' => 'img/mecunlar/can40.jpg'
    )

);


/* ======================================================
   QUTU MÜKAFATINI SEÇ / YADDA SAXLA
   ====================================================== */

$secim = array();


/* ======================================================
   ƏVVƏL AÇILIBSA — EYNİ MÜKAFATI GÖSTƏR
   ====================================================== */

if (
    isset(
        $_SESSION['zombie_box_rewards']
        [$group_number]
        [$qutu]
    ) &&
    is_array(
        $_SESSION['zombie_box_rewards']
        [$group_number]
        [$qutu]
    )
) {

    $secim =
        $_SESSION['zombie_box_rewards']
        [$group_number]
        [$qutu];

}


/* ======================================================
   İLK DƏFƏ AÇILIRSA — RANDOM SEÇ
   ====================================================== */

else {

    /* 1-ci qutu — Göy əşya */

    if ($qutu === 1) {

        $secim =
            $random_goy_esyalar[
                array_rand($random_goy_esyalar)
            ];

    }


    /* 2-ci qutu — 5% / 10% məcun */

    elseif ($qutu === 2) {

        $secim =
            $random_mecunlar_2[
                array_rand($random_mecunlar_2)
            ];

    }


    /* 3-cü qutu — Yaşıl əşya */

    elseif ($qutu === 3) {

        $secim =
            $random_yasil_esyalar[
                array_rand($random_yasil_esyalar)
            ];

    }


    /* 4-cü qutu — Yaşıl əşya */

    elseif ($qutu === 4) {

        $secim =
            $random_yasil_esyalar[
                array_rand($random_yasil_esyalar)
            ];

    }


    /* 5-ci qutu — 30% / 40% məcun */

    elseif ($qutu === 5) {

        $secim =
            $random_mecunlar_5[
                array_rand($random_mecunlar_5)
            ];

    }


    /* ==================================================
       SEÇİLƏN MÜKAFATI SESSION-DA SAXLA
       ================================================== */

    $_SESSION['zombie_box_rewards']
        [$group_number]
        [$qutu] =
            $secim;
}
?>

<div class="info">

        <div class="center">

            <b>
                Siz Qalib oldunuz!
                (Xal: <?php echo (int)$score; ?>)
            </b>

        </div>

        <br>

        Göstərilən əşya daha əvvəl çantanıza göndərilib

        <br>

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
        $secim['img'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
    alt="<?php echo htmlspecialchars(
        $secim['ad'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
>

                        </td>

                        <td>

                            <b>Əldə etdiniz:</b>

                            <br>

<?php echo htmlspecialchars(
    $secim['ad'],
    ENT_QUOTES,
    'UTF-8'
); ?>                     <br>

                            <a
                                href="chantam.php?go=eshya&amp;qrup_num=<?php
                                echo (int)$group_number;
                                ?>"
                            >
                                Əşyalar
                            </a>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

<input
type="button"
class="button"
value="Geri"
onclick="window.location.href='zombi_yarad.php?go=meglub&amp;qrup_num=<?php echo (int)$group_number; ?>&amp;xal=<?php echo isset($_SESSION['zombie_battle'][$group_number]['score']) ? (int)$_SESSION['zombie_battle'][$group_number]['score'] : 0; ?>';"
>
</div>

    <?php

    exit;
}
?>

<?php if ($go === 'melumat') { ?>
    </div>
</div>

<?php exit; ?>

<?php } ?>
<!-- ======================================================
     LOGO ÇIXIŞ XƏBƏRDARLIĞI
     ====================================================== -->

<?php if ($logo_exit_warning) { ?>


<div class="info">


<div class="center">

<div class="block_line">

<b>
Zombie Castle
</b>

</div>

</div>


<br>


<div class="zombie-logo-warning">


Siz digər səhifələrə keçmək üçün qrupu tərk etməlisiniz!


<br>


Qrupu tərk etmək istəyirsiz?


<br><br>


<a
href="kordinat3.php?go=logo_cix_yes&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>
Hə
</a>


&nbsp; | &nbsp;


<a
href="<?php
echo isset($_SESSION['zombie_logo_return'])
    ? htmlspecialchars(
        $_SESSION['zombie_logo_return'],
        ENT_QUOTES,
        'UTF-8'
    )
    : 'kordinat3.php?go=deyis&qrup_num=' . (int)$group_number;
?>"
>
Yox
</a>


</div>

</div>


<?php } else { ?>


<!-- ======================================================
     DÖYÜŞ SƏHİFƏSİ
     ====================================================== -->

<div class="info">


<center>


<b>Qalanın adı-</b>

Zombie Castle

<br>


Zərbə atmaq üçün

<span id="battle_timer">

<?php
echo (int)$battle_time;
?>

</span>

san. vaxtınız var!


<br>


Etab:

<?php
echo (int)$stage;
?>

|

Zombi:

<?php
echo (int)$zombie_count;
?>

|

Xal:

<?php
echo (int)$score;
?>


<br>


<a
href="kordinat3.php?go=melumat&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

Qrup melumatları

</a>


<br>


<!-- ======================================================
     ZOMBİ XƏRİTƏSİ
     ====================================================== -->

<div
style="
position:relative;
overflow:hidden;
width:240px;
margin:0 auto;
background:url('zombi/fon.png');
"
>


<!-- 1-ci sıra -->

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<br>


<!-- 2-ci sıra -->

<img
src="zombi/warrior.png"
height="40"
width="40"
border="0"
alt=""
>


<a
href="zombi_fig.php?qrup_num=<?php
echo (int)$group_number;
?>"
>

<img
src="zombi/right.png"
height="40"
width="40"
border="0"
alt="Zərbə"
>

</a>


<img
src="zombi/2.png"
height="40"
width="40"
border="0"
alt=""
>

<img
src="zombi/2.png"
height="40"
width="40"
border="0"
alt=""
>

<br>


<!-- 3-cü sıra -->

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<img
src="zombi/101.png"
height="40"
width="40"
border="0"
alt=""
>

<br>


</div>


<br>


<!-- ======================================================
     MESAJ YAZ
     ====================================================== -->

<form
method="post"
action="kordinat3.php?go=deyis&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>


<input
type="text"
name="message"
value=""
maxlength="120"
>


<br>


<input
type="submit"
class="button"
value="Gönder"
>


</form>


<hr>
<br>

<!-- ======================================================
     MESAJLAR
     ====================================================== -->

<?php

if (
    isset($_SESSION['zombie_battle_messages']) &&
    isset(
        $_SESSION['zombie_battle_messages']
        [$group_number]
    ) &&
    is_array(
        $_SESSION['zombie_battle_messages']
        [$group_number]
    )
) {

    foreach (
        $_SESSION['zombie_battle_messages']
        [$group_number]
        as $chat_message
    ) {

        $chat_id =
            isset($chat_message['id'])
            ? (int)$chat_message['id']
            : 0;

        $chat_name =
            isset($chat_message['name'])
            ? (string)$chat_message['name']
            : '';

        $chat_text =
            isset($chat_message['message'])
            ? (string)$chat_message['message']
            : '';

?>

<div
style="
width:100%;
text-align:left;
word-wrap:break-word;
overflow-wrap:anywhere;
margin-bottom:1px;
"
>


<a
href="infoforce.php?uid=<?php
echo $chat_id;
?>"
>


<u>

<font color="green">

<?php

echo htmlspecialchars(
    $chat_name,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);

?>

</font>

</u>

</a>


»


<small>

<?php

echo htmlspecialchars(
    $chat_text,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);

?>

</small>


</div>


<?php

    }

}

?>


</center>


<div class="line"></div>


<div class="menu">


<li>


<li>

<a href="zombi_yarad.php?go=meglub_logo_confirm&amp;qrup_num=<?php echo (int)$group_number; ?>&amp;xal=<?php echo (int)$score; ?>">
Ana sehife
</a>

</li>
</li>


</div>


</div>


<?php } ?>


</div>


<!-- ======================================================
     DÖYÜŞ GERİ SAYIMI
     ====================================================== -->

<script type="text/javascript">

var battleTime =
    <?php echo (int)$battle_time; ?>;


var battleScore =
    <?php echo (int)$score; ?>;


var groupNumber =
    <?php echo (int)$group_number; ?>;


function battleCountdown() {

    var timer =
        document.getElementById('battle_timer');


    if (!timer) {

        return;

    }


    if (battleTime <= 0) {

        window.location =
            'zombi_yarad.php?go=meglub&qrup_num=' +
            groupNumber +
            '&xal=' +
            battleScore;

        return;

    }


    timer.innerHTML =
        battleTime;


    battleTime--;


    setTimeout(
        battleCountdown,
        1000
    );

}


window.onload =
    battleCountdown;

</script>


</body>

</html>