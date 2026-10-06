<?php

session_start();

require_once "config.php";
require_once "user_data.php";

function smaylikleri_cevir($metn)
{
    $smaylikler = [

        // EMOSİYALAR
        '.bee.'       => 'muxtelif/smaylikler/emosiyalar/bee.gif',
        '.asqir.'     => 'muxtelif/smaylikler/emosiyalar/asqirmaq.gif',
        '.555.'       => 'muxtelif/smaylikler/emosiyalar/555.gif',
        '.blev2.'     => 'muxtelif/smaylikler/emosiyalar/blev2.gif',
        '.blink.'     => 'muxtelif/smaylikler/emosiyalar/blink.gif',
        '.cay.'       => 'muxtelif/smaylikler/emosiyalar/cay.gif',
        '.deli.'      => 'muxtelif/smaylikler/emosiyalar/deli.gif',
        '.dil.'       => 'muxtelif/smaylikler/emosiyalar/dil.gif',
        '.dil2.'      => 'muxtelif/smaylikler/emosiyalar/dil2.gif',
        '.esebi.'     => 'muxtelif/smaylikler/emosiyalar/esebi.gif',
        '.esnemek.'   => 'muxtelif/smaylikler/emosiyalar/esnemek.gif',
        '.gulmek1.'   => 'muxtelif/smaylikler/emosiyalar/gulmek1.gif',
        '.gulmek5.'   => 'muxtelif/smaylikler/emosiyalar/gulmek5.gif',
        '.haha.'      => 'muxtelif/smaylikler/emosiyalar/haha.gif',
        '.invalid.'   => 'muxtelif/smaylikler/emosiyalar/invalid.gif',
        '.kuku.'      => 'muxtelif/smaylikler/emosiyalar/Kuku1.gif',
        '.razi.'      => 'muxtelif/smaylikler/emosiyalar/razi.gif',
        '.ujas.'      => 'muxtelif/smaylikler/emosiyalar/Ujas.gif',
        '.yemek.'     => 'muxtelif/smaylikler/emosiyalar/Yemek.gif',
        '.yuxu1.'     => 'muxtelif/smaylikler/emosiyalar/yuxu1.gif',

        // GÜLMƏK
        '.g3.'        => 'muxtelif/smaylikler/gulmek/g3.gif',
        '.g5.'        => 'muxtelif/smaylikler/gulmek/g5.gif',
        '.g8.'        => 'muxtelif/smaylikler/gulmek/g8.gif',
        '.g11.'       => 'muxtelif/smaylikler/gulmek/g11.gif',
        '.g12.'       => 'muxtelif/smaylikler/gulmek/g12.gif',
        '.g14.'       => 'muxtelif/smaylikler/gulmek/g14.gif',
        '.g15.'       => 'muxtelif/smaylikler/gulmek/g15.gif',
        '.hihi.'      => 'muxtelif/smaylikler/gulmek/hihi.gif',

        // ƏSƏB
        '.doyuw.'      => 'muxtelif/smaylikler/eseb/doyuw.gif',
        '.h1.'         => 'muxtelif/smaylikler/eseb/h1.gif',
        '.qezeb.'      => 'muxtelif/smaylikler/eseb/qezeb.gif',
        '.agresiv.'    => 'muxtelif/smaylikler/eseb/agresiv.gif',
        '.vur.'        => 'muxtelif/smaylikler/eseb/vur.gif',
        '.doyus.'      => 'muxtelif/smaylikler/eseb/doyus.gif',
        '.qezebli.'    => 'muxtelif/smaylikler/eseb/qezebli.gif',
        '.grr.'        => 'muxtelif/smaylikler/eseb/grr.gif',
        '.uff.'        => 'muxtelif/smaylikler/eseb/uff.gif',
        '.part.'       => 'muxtelif/smaylikler/eseb/part.gif',
        '.bezdim.'     => 'muxtelif/smaylikler/eseb/bezdim.gif',
        '.yox.'        => 'muxtelif/smaylikler/eseb/yox.gif',
        '.qisqir.'     => 'muxtelif/smaylikler/eseb/qisqir.gif',
        '.bagir.'      => 'muxtelif/smaylikler/eseb/bagir.gif',
        '.savas.'      => 'muxtelif/smaylikler/eseb/savas.gif',
        '.dayan.'      => 'muxtelif/smaylikler/eseb/dayan.gif',
        '.pis.'        => 'muxtelif/smaylikler/eseb/pis.gif',
        '.qirmizi.'    => 'muxtelif/smaylikler/eseb/qirmizi.gif',
        '.sinir.'      => 'muxtelif/smaylikler/eseb/sinir.gif',

        // KEFSİZ
        '.agla.'       => 'muxtelif/smaylikler/kefsiz/agla.gif',
        '.sorry.'      => 'muxtelif/smaylikler/kefsiz/sorry.gif',

        // SEVGİ
        '.urek.'       => 'muxtelif/smaylikler/sevgi/urek.gif',
        '.urek1.'      => 'muxtelif/smaylikler/sevgi/urek1.gif',
        '.opdum.'      => 'muxtelif/smaylikler/sevgi/opdum.gif',
        '.love.'       => 'muxtelif/smaylikler/sevgi/love.gif',
        '.love1.'      => 'muxtelif/smaylikler/sevgi/love1.gif',
        '.love2.'      => 'muxtelif/smaylikler/sevgi/love2.gif',
        '.love3.'      => 'muxtelif/smaylikler/sevgi/love3.gif',
        '.love4.'      => 'muxtelif/smaylikler/sevgi/love4.gif',
        '.love5.'      => 'muxtelif/smaylikler/sevgi/love5.gif',
        '.love6.'      => 'muxtelif/smaylikler/sevgi/love6.gif',
        '.loveme.'     => 'muxtelif/smaylikler/sevgi/loveme.gif',

        // YUXU
        '.yuxu.'       => 'muxtelif/smaylikler/yuxu/yuxu.gif',
        '.laylay.'     => 'muxtelif/smaylikler/yuxu/laylay.gif',
        '.yuxu3.'      => 'muxtelif/smaylikler/yuxu/yuxu3.gif',

        // ÖPÜŞ
        '.zaluyu.'     => 'muxtelif/smaylikler/opush/zaluyu.gif',
        '.cem.'        => 'muxtelif/smaylikler/opush/cem.gif',
        '.4mak.'       => 'muxtelif/smaylikler/opush/4mak.gif',
        '.kiss4.'      => 'muxtelif/smaylikler/opush/kiss4.gif',
        '.oblom.'      => 'muxtelif/smaylikler/opush/oblom.gif',
        '.ops.'        => 'muxtelif/smaylikler/opush/ops.gif',
        '.ops2.'       => 'muxtelif/smaylikler/opush/ops2.gif',

        // QARIŞIQ
        '.a128.'       => 'muxtelif/smaylikler/qarisiq/a128.gif',
        '.bicaq.'      => 'muxtelif/smaylikler/qarisiq/bicaq.gif',
        '.dash.'       => 'muxtelif/smaylikler/qarisiq/dash.gif',
        '.duel.'       => 'muxtelif/smaylikler/qarisiq/duel.gif',
        '.gitar.'      => 'muxtelif/smaylikler/qarisiq/gitar.gif',
        '.kuku2.'      => 'muxtelif/smaylikler/qarisiq/kuku.gif',
        '.nn36.'       => 'muxtelif/smaylikler/qarisiq/nn36.gif',
        '.pooh.'       => 'muxtelif/smaylikler/qarisiq/pooh.gif',
        '.qiz.'        => 'muxtelif/smaylikler/qarisiq/qiz.gif',
        '.qiz1.'       => 'muxtelif/smaylikler/qarisiq/qiz1.gif',
        '.qiz3.'       => 'muxtelif/smaylikler/qarisiq/qiz3.gif',
        '.qiz4.'       => 'muxtelif/smaylikler/qarisiq/qiz4.gif',
        '.qiz5.'       => 'muxtelif/smaylikler/qarisiq/qiz5.gif',
        '.song.'       => 'muxtelif/smaylikler/qarisiq/song.gif',
        '.tort.'       => 'muxtelif/smaylikler/qarisiq/tort.gif',
        '.yeriyox.'    => 'muxtelif/smaylikler/qarisiq/yeriyox.gif'
    ];

    foreach ($smaylikler as $kod => $yol) {

        $img = '<img src="' . htmlspecialchars($yol, ENT_QUOTES, 'UTF-8') .
               '" alt="' . htmlspecialchars($kod, ENT_QUOTES, 'UTF-8') . '">';

        $metn = str_replace($kod, $img, $metn);
    }

    return $metn;
}




if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GİRİŞ EDƏN İSTİFADƏÇİ
|--------------------------------------------------------------------------
*/

$my_id = (int)$_SESSION['user_id'];
/* OXUNMAMIŞ MESAJ SAYI */

$stmt_unread = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE mesaji_alan_nik = :alan
      AND oxundu = 0
      AND silindi_alan = 0
");

$stmt_unread->execute([
    ':alan' => $my_id
]);

$unread_count = (int)$stmt_unread->fetchColumn();


$show_inbox = isset($_GET['go']) && $_GET['go'] === 'goster';

if ($show_inbox) {

  $stmt_inbox = $pdo->prepare("
    SELECT
        m.id,
        m.mesaji_gonderen_nik,
        m.mesaji_alan_nik,
        m.mesaj,
        m.tarix,
        m.oxundu,
        u.login,
        u.oyuncunun_seviyyesi,
        u.movqe
    FROM mesajlar m
    INNER JOIN users u
        ON u.id = CASE
            WHEN m.mesaji_gonderen_nik = :my_id1
                THEN m.mesaji_alan_nik
            ELSE m.mesaji_gonderen_nik
        END
    WHERE
        (
            m.mesaji_gonderen_nik = :my_id2
            OR m.mesaji_alan_nik = :my_id3
        )
        AND m.id = (
            SELECT MAX(x.id)
            FROM mesajlar x
            WHERE
                (
                    x.mesaji_gonderen_nik = m.mesaji_gonderen_nik
                    AND x.mesaji_alan_nik = m.mesaji_alan_nik
                )
                OR
                (
                    x.mesaji_gonderen_nik = m.mesaji_alan_nik
                    AND x.mesaji_alan_nik = m.mesaji_gonderen_nik
                )
        )
    ORDER BY m.id DESC
");

$stmt_inbox->execute([
    ':my_id1' => $my_id,
    ':my_id2' => $my_id,
    ':my_id3' => $my_id
]);

$inbox_messages = $stmt_inbox->fetchAll(PDO::FETCH_ASSOC);

} else {

    $target_id = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;

    if ($target_id <= 0) {
        exit('İstifadəçi seçilməyib.');
    }
}

/* ---------------------------------------------------------
   GƏLƏN MƏKTUBLAR
--------------------------------------------------------- */

$show_inbox = isset($_GET['go']) && $_GET['go'] === 'goster';

$inbox_messages = [];

if ($show_inbox) {

    $stmt_inbox = $pdo->prepare("
        SELECT
            m.id,
            m.mesaji_gonderen_nik,
            m.mesaji_alan_nik,
            m.mesaj,
            m.tarix,
            m.oxundu,

            u.login,
            u.oyuncunun_seviyyesi,
            u.movqe

        FROM mesajlar m

        INNER JOIN users u
            ON u.id = CASE
                WHEN m.mesaji_gonderen_nik = :my1
                THEN m.mesaji_alan_nik
                ELSE m.mesaji_gonderen_nik
            END

        WHERE
            (
                m.mesaji_gonderen_nik = :my2
                AND m.silindi_gonderen = 0
            )
            OR
            (
                m.mesaji_alan_nik = :my3
                AND m.silindi_alan = 0
            )

        ORDER BY m.id DESC
    ");

    $stmt_inbox->execute([
        ':my1' => $my_id,
        ':my2' => $my_id,
        ':my3' => $my_id
    ]);

    $inbox_messages = $stmt_inbox->fetchAll(PDO::FETCH_ASSOC);

    $unique_inbox = [];

    foreach ($inbox_messages as $msg) {

        $sender_id = (
            (int)$msg['mesaji_gonderen_nik'] === $my_id
        )
            ? (int)$msg['mesaji_alan_nik']
            : (int)$msg['mesaji_gonderen_nik'];
            $msg['qarshi_id'] = $sender_id;


        /* -------------------------------------------------
           SƏNİN GÖNDƏRDİYİN OXUNMAMIŞ MESAJ VARMI?
        ------------------------------------------------- */

$stmt_men = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE mesaji_gonderen_nik = :menim_id
      AND mesaji_alan_nik = :qarshi_id
      AND oxundu = 0
      AND silindi_gonderen = 0
");



        $stmt_men->execute([
            ':menim_id'  => $my_id,
            ':qarshi_id' => $sender_id
        ]);

        $msg['men_unread'] = (
            (int)$stmt_men->fetchColumn() > 0
        );

        /* -------------------------------------------------
           QARŞI TƏRƏFİN SƏNƏ GÖNDƏRDİYİ OXUNMAMIŞ MESAJLAR
        ------------------------------------------------- */

        $stmt_count = $pdo->prepare("
            SELECT COUNT(*)
            FROM mesajlar
            WHERE mesaji_gonderen_nik = :sender
              AND mesaji_alan_nik = :receiver
              AND oxundu = 0
              AND silindi_alan = 0
        ");

        $stmt_count->execute([
            ':sender'   => $sender_id,
            ':receiver' => $my_id
        ]);

        $msg['mesaj_sayi'] = (
            (int)$stmt_count->fetchColumn()
        );

        /* -------------------------------------------------
           HƏR PROFİL YALNIZ 1 DƏFƏ
        ------------------------------------------------- */

        if (!isset($unique_inbox[$sender_id])) {
            $unique_inbox[$sender_id] = $msg;
        }
    }

    $inbox_messages = array_values($unique_inbox);
}


/*
|--------------------------------------------------------------------------
| QARŞI TƏRƏFİN ID-Sİ
|--------------------------------------------------------------------------
*/

$target_id = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;

if (!$show_inbox && $target_id <= 0) {
    exit('İstifadəçi seçilməyib.');
}

/*
|--------------------------------------------------------------------------
| ÖZÜNƏ MESAJ YAZMAĞIN QARŞISINI ALIRIQ
|--------------------------------------------------------------------------
*/

if ($target_id === $my_id) {
    exit('Özünüzə mesaj yaza bilməzsiniz.');
}

/*
|--------------------------------------------------------------------------
| QARŞI TƏRƏFİ USERS CƏDVƏLİNDƏN TAPIRIQ
|--------------------------------------------------------------------------
*/

if (!$show_inbox) {

   $stmt_target = $pdo->prepare("
    SELECT
        id,
        login,
        oyuncunun_seviyyesi,
        movqe,
        online_oyuncu_vaxti,
        mesaj_qebulu
    FROM users
    WHERE id = :id
    LIMIT 1
");


    $stmt_target->execute([
        ':id' => $target_id
    ]);

    $target_user = $stmt_target->fetch(PDO::FETCH_ASSOC);

    if (!$target_user) {
        exit('İstifadəçi tapılmadı.');
    }
/*
|--------------------------------------------------------------------------
| MESAJ QƏBULU YOXLAMASI
|--------------------------------------------------------------------------
*/

$mesaj_yaza_biler = true;

if ((int)$target_user['mesaj_qebulu'] === 1) {

    $stmt_dost = $pdo->prepare("
        SELECT id
        FROM dostluq
        WHERE status = 1
          AND (
                (gonderen_id = :men_id1 AND alan_id = :target_id1)
                OR
                (gonderen_id = :target_id2 AND alan_id = :men_id2)
          )
        LIMIT 1
    ");

    $stmt_dost->execute([
        ':men_id1'    => $my_id,
        ':target_id1' => $target_id,
        ':target_id2' => $target_id,
        ':men_id2'    => $my_id
    ]);

    if (!$stmt_dost->fetchColumn()) {
        $mesaj_yaza_biler = false;
    }
}

}

/*
|--------------------------------------------------------------------------
| RƏNG
|--------------------------------------------------------------------------
*/

if (!$show_inbox) {

    switch ((int)$target_user['movqe']) {

        case 1:
            // İnsan
            $renk = '#FF0000';
            break;

        case 2:
            // Vampir
            $renk = '#008000';
            break;

        case 3:
            // Neytral
            $renk = '#0000FF';
            break;

        default:
            // Naməlum
            $renk = '#FFFFFF';
            break;
    }

    if ((int)$target_user['online_oyuncu_vaxti'] >= time() - 180) {
        $online_status = 'Online';
    } else {
        $online_status = 'Offline';
    }

    /*
    |--------------------------------------------------------------------------
    | İQNOR YOXLAMASI
    |--------------------------------------------------------------------------
    */

    $ignore_edib = false;

    $stmt_ignore = $pdo->prepare("
        SELECT id
        FROM iqnor
        WHERE user_id = :user_id
          AND iqnor_id = :iqnor_id
        LIMIT 1
    ");

    $stmt_ignore->execute([
        ':user_id'  => $target_id,
        ':iqnor_id' => $my_id
    ]);

    if ($stmt_ignore->fetchColumn()) {
        $ignore_edib = true;
    }
}


/*
|--------------------------------------------------------------------------
| MESAJ GÖNDƏR
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | İQNOR EDİLİBSƏ MESAJ GÖNDƏRMƏ
    |--------------------------------------------------------------------------
    */

    if ($ignore_edib) {

$_SESSION['message_error'] =
    ' ' . $target_user['login'] . ' Ləqəbli şəxs sizi iqnor edib,' .
    'Siz ona məktub yaza bilməzsiz';




        header("Location: arxiv.php?uid=" . $target_id);
        exit;
    }

    $message = trim($_POST['message'] ?? '');
$reng = (int)($_POST['reng'] ?? 0);

if ($reng < 0 || $reng > 4) {
    $reng = 0;
}

    /*
    |--------------------------------------------------------------------------
    | BOŞ MESAJ
    |--------------------------------------------------------------------------
    */

    if ($message === '') {

        $_SESSION['message_error'] = 'Mesaj boş ola bilməz!';

        header("Location: arxiv.php?uid=" . $target_id);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | 3000 SIMVOL LIMITI
    |--------------------------------------------------------------------------
    */

    if (mb_strlen($message, 'UTF-8') > 3000) {
        $message = mb_substr($message, 0, 3000, 'UTF-8');
    }

    /*



    /*
    |--------------------------------------------------------------------------
    | MYSQL-Ə MESAJI YAZ
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO mesajlar
        (
            mesaji_gonderen_nik,
            mesaji_alan_nik,
            mesaj,
            tarix,
            oxundu,
            reng
        )
        VALUES
        (
            :gonderen,
            :alan,
            :mesaj,
            :tarix,
            :oxundu,
            :reng
        )
    ");

    $stmt->execute([
        ':gonderen' => $my_id,
        ':alan'     => $target_id,
        ':mesaj'    => $message,
        ':tarix'    => time(),
        ':oxundu'   => 0,
        ':reng'     => $reng
    ]);
    $_SESSION['last_message_id'] = $pdo->lastInsertId();

$_SESSION['message_success'] =
    'Sizin mektub ' . $target_user['login'] . ', üçün gönderildi...';

header("Location: arxiv.php?uid=" . $target_id);
exit;

    /*
    |--------------------------------------------------------------------------
    | UĞURLU MESAJ
    |--------------------------------------------------------------------------
    */

    $_SESSION['message_success'] =
        'Sizin mektub ' . $target_user['login'] . ', üçün gönderildi...';

    header("Location: arxiv.php?uid=" . $target_id);
    exit;
}
/*
/*
|--------------------------------------------------------------------------
| SƏHİFƏ YENİLƏNDİKDƏ ERROR
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| REFRESH YOXLAMASI
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    && isset($_SESSION['last_message_id'])
    && !isset($_SESSION['message_success'])
) {

    $_SESSION['message_error'] =
        'Siz Artıq Bu Mesajı Göndərmisiniz!';

    unset($_SESSION['last_message_id']);
}

/*
/*
|--------------------------------------------------------------------------
| MESAJLARI GƏTİR
|--------------------------------------------------------------------------
*/

$messages = [];

$page = isset($_GET['p']) ? max(0, (int)$_GET['p']) : 0;
$limit = 10;
$offset = $page * $limit;
$has_next_page = false;
$stmt = $pdo->prepare("
    SELECT
        id,
        mesaji_gonderen_nik,
        mesaji_alan_nik,
        mesaj,
        tarix,
        oxundu,
        reng
    FROM mesajlar
    WHERE
        (
            mesaji_gonderen_nik = :men1
            AND mesaji_alan_nik = :target1
            AND silindi_gonderen = 0
        )
        OR
        (
            mesaji_gonderen_nik = :target2
            AND mesaji_alan_nik = :men2
            AND silindi_alan = 0
        )
    ORDER BY id DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':men1', $my_id, PDO::PARAM_INT);
$stmt->bindValue(':target1', $target_id, PDO::PARAM_INT);
$stmt->bindValue(':target2', $target_id, PDO::PARAM_INT);
$stmt->bindValue(':men2', $my_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($messages) === $limit) {
    $has_next_page = true;
}

/*
|--------------------------------------------------------------------------
| QARŞI TƏRƏFDƏN GƏLƏN MESAJLARI OXUNDU ET
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    UPDATE mesajlar
    SET oxundu = 1
    WHERE
        mesaji_gonderen_nik = :sender
        AND mesaji_alan_nik = :receiver
        AND oxundu = 0
");

$stmt->execute([
    ':sender'   => $target_id,
    ':receiver' => $my_id
]);

/*
|--------------------------------------------------------------------------
| MƏNİM İSTİFADƏÇİ MƏLUMATLARIM
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        login,
        qızıl,
        brılyant,
        enerjı,
        vip
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
$user_login = $user['login'];
?>

<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN""http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="az" lang="az">
<head>
<meta name="robots" content="ALL" /> 
<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 
<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 

<link rel="stylesheet" href="css.css">

 <meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"><title>Arxiv Mektublar | Klan.Az Qanli Efsane</title>
<script>
    function goGeri() {
        window.history.back();
    }
</script>

</head><body><div class='main' style='word-wrap:break-word;'><div id="header">
  <a href="menu.php?">
   <img src="img/logo.png">
  </a>
<div class="icons"></div>


    <div class=main_foot><div class=grey>
    <img src="img/coin.png" title="Qızıl" alt=""/>
<?php echo (int)$user['qızıl']; ?>

<img src="img/brill.png" title="Brilliant" alt=""/>
<?php echo (int)$user['brılyant']; ?>

<img src="img/energy.png" title="Enerji" alt=""/>
<?php echo (int)$user['enerjı']; ?><?php if ($unread_count > 0) { ?>
    <a href="arxiv.php?go=goster">
        <img src="img/mektub.gif" title="Yeni mesaj" alt="Mesaj"/>
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
<br>
<div class='info'>

<?php if ($show_inbox) { ?>

<div class="center">
    <div class="block_line">
        Size gelen mektublar
    </div>
</div>

<br/>

<?php if (empty($inbox_messages)) { ?>

<div class="menu">
    <br/>
    <div class="battle_log">
        <li>
            Mektub yoxdur.
        </li>
    </div>
</div>

<?php } else { ?>

<?php foreach ($inbox_messages as $msg) { ?>

<div class="menu">
    <br/>
    <div class="battle_log">
        <li>

            <a href="arxiv.php?uid=<?php echo (int)$msg['qarshi_id']; ?>">

                <font color="<?php
                    if ((int)$msg['movqe'] === 2) {
                        // Vampir
                        echo 'green';

                    } elseif ((int)$msg['movqe'] === 1) {
                        // İnsan
                        echo 'red';

                    } elseif ((int)$msg['movqe'] === 3) {
                        // Neytral
                        echo 'blue';

                    } else {
                        echo 'white';
                    }
                ?>">

                    <?php echo htmlspecialchars($msg['login'], ENT_QUOTES, 'UTF-8'); ?>
                    [<?php echo (int)$msg['oyuncunun_seviyyesi']; ?>]

                </font>

                <?php
                $mesaj_vaxti = (int)$msg['tarix'];

                $bugun_baslangic = strtotime('today');
                $dunen_baslangic = strtotime('yesterday');

                $gunler = [
                    1 => 'Bazar ertəsi',
                    2 => 'Çərşənbə axşamı',
                    3 => 'Çərşənbə',
                    4 => 'Cümə axşamı',
                    5 => 'Cümə',
                    6 => 'Şənbə',
                    7 => 'Bazar'
                ];

                if ($mesaj_vaxti >= $bugun_baslangic) {
                    $tarix_yazisi = 'Bu gün';

                } elseif ($mesaj_vaxti >= $dunen_baslangic) {
                    $tarix_yazisi = 'Dünən';

                } elseif ($mesaj_vaxti >= strtotime('-7 days')) {
                    $gun = (int)date('N', $mesaj_vaxti);
                    $tarix_yazisi = $gunler[$gun];

                } else {
                    $tarix_yazisi = date('d.m.Y', $mesaj_vaxti);
                }

                echo '(' . $tarix_yazisi . ' | ' . date('H:i', $mesaj_vaxti) . ')';
                ?>

                <br/>

                <?php
                $mesaj_metni = trim($msg['mesaj']);

                if (mb_strlen($mesaj_metni, 'UTF-8') > 14) {
                    $qisa_mesaj = mb_substr(
                        $mesaj_metni,
                        0,
                        14,
                        'UTF-8'
                    ) . '...';
                } else {
                    $qisa_mesaj = $mesaj_metni;
                }

                echo htmlspecialchars(
                    $qisa_mesaj,
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

                <?php if ((int)$msg['mesaj_sayi'] > 0) { ?>
                    (+<?php echo (int)$msg['mesaj_sayi']; ?>)
                <?php } ?>

            </a>

        </li>
    </div>
</div>

<?php } ?>

<?php } ?>


<?php } else { ?>



<img src="muxtelif/send.png" alt="Mesaj"/>

<a href="infoforce.php?uid=<?php echo (int)$target_user['id']; ?>">
    <font color="<?php echo $renk; ?>">
        <?php echo htmlspecialchars($target_user['login']); ?>
        [<?php echo (int)$target_user['oyuncunun_seviyyesi']; ?>]
    </font>
</a>

(<?php echo $online_status; ?>)

<?php if ($mesaj_yaza_biler) { ?>

<form method="post" action="arxiv.php?go=pn&amp;uid=<?php echo (int)$target_user['id']; ?>&amp;arxiv=arxiv">
<textarea
    name="message"
    class="text longest"
    rows="3"
    cols="30"
    maxlength="3000"
></textarea>

<a href="smaylikler.php?">
    <img src="smile_new/smile.png" alt=""/>
</a>

<input
    type="hidden"
    name="towhom"
    value="<?php echo htmlspecialchars(
        $target_user['login'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
/>

<?php if ((int)($user['vip'] ?? 0) === 1) { ?>

<b>Rəng</b>
<br/>

<select name="reng">
    <option value="0">Adi</option>
    <option value="1">Qırmızı</option>
    <option value="2">Yaşıl</option>
    <option value="3">Göy</option>
    <option value="4">Narıncı</option>
</select>

<br/><br/>

<input
    type="submit"
    class="button"
    value="Gönder"
/>

<?php } else { ?>

<input
    type="submit"
    class="button"
    value="Gönder"
/>

<?php } ?>

</form>
<?php } else { ?>
<div class="error" style="padding:5px 6px;">

    <span style="
        color:#DA1515;
        display:block;
        font-size:13px;
        line-height:16px;
        text-align:left;
    ">

        <img
            src="muxtelif/eror.png"
            alt=""
            style="vertical-align:middle;"
        >

        Bu istifadəçi dostlarından mesaj qəbul edir.

    </span>

</div>

<br/>

<?php } ?>
<div class=menu><br/><li>
<a href="arxiv.php?uid=<?php echo (int)$target_user['id']; ?>">
<img src="muxtelif/refresh.png" alt=" "/>Sehifeni Yenile
</a>
</li></div>


<?php if (isset($_SESSION['message_success'])) { ?>

<div class="success">
    <img src="muxtelif/okey.png" alt="">
    <span style="color:#259C00;">
        <?php echo htmlspecialchars(
            $_SESSION['message_success'],
            ENT_QUOTES,
            'UTF-8'
        ); ?>
    </span>
</div>

<br/>

<?php unset($_SESSION['message_success']); ?>

<?php } elseif (isset($_SESSION['message_error'])) { ?>

<div class="error" style="padding:5px 6px;">

    <span style="
        color:#DA1515;
        display:block;
        font-size:13px;
        line-height:16px;
        text-align:left;
        word-wrap:break-word;
    ">

        <img src="muxtelif/eror.png" alt="" style="vertical-align:middle;">

        <?php echo nl2br(htmlspecialchars(
            $_SESSION['message_error'],
            ENT_QUOTES,
            'UTF-8'
        )); ?>

    </span>

</div>

<br/>

<?php unset($_SESSION['message_error']); ?>


<?php } ?>



<div style="background:#fce6a3">


<?php if (empty($messages)) { ?>

Mesaj yoxdur.<br/><br/><br/>

<?php } else { ?>

<?php foreach ($messages as $msg) { ?>

<?php if ((int)$msg['mesaji_gonderen_nik'] === $my_id) { ?>

<!-- MƏNİM MESAJIM -->
<div style="background:#FCEBB5; padding:3px 10px 3px 10px; float:right; margin:0 7px 3px 0; max-width:80%; position:relative; border-radius:5px;">

<?php
$mesaj_html = htmlspecialchars(
    $msg['mesaj'],
    ENT_QUOTES,
    'UTF-8'
);

$mesaj_html = smaylikleri_cevir($mesaj_html);

$mesaj_rengi = '';

switch ((int)($msg['reng'] ?? 0)) {
    case 1:
        $mesaj_rengi = '#DC143C';
        break;

    case 2:
        $mesaj_rengi = '#008000';
        break;

    case 3:
        $mesaj_rengi = '#0000FF';
        break;

    case 4:
        $mesaj_rengi = '#FF8C00';
        break;

    default:
        $mesaj_rengi = '';
        break;
}

if ($mesaj_rengi !== '') {
    echo '<span style="color:' . $mesaj_rengi . ';">';
}

echo nl2br($mesaj_html);

if ($mesaj_rengi !== '') {
    echo '</span>';
}
?>

<br/>

<font style="font-size:10px; float:right">

<?php echo date('H:i', (int)$msg['tarix']); ?>

<?php if ((int)$msg['oxundu'] === 1) { ?>

<img src="img/oxunub.png" alt=""/>

<?php } else { ?>

<img src="img/oxunmayib.png" alt=""/>

<?php } ?>

</font>

</div>

<?php } else { ?>

<!-- QARŞI TƏRƏFİN MESAJI -->
<div style="background:#FFF5CB; padding:3px 10px 3px 10px; float:left; margin:0 0 3px 7px; max-width:80%; position:relative; border-radius:5px;">

<?php
$mesaj_html = htmlspecialchars(
    $msg['mesaj'],
    ENT_QUOTES,
    'UTF-8'
);

$mesaj_html = smaylikleri_cevir($mesaj_html);

$mesaj_rengi = '';

switch ((int)($msg['reng'] ?? 0)) {
    case 1:
        $mesaj_rengi = '#DC143C';
        break;

    case 2:
        $mesaj_rengi = '#008000';
        break;

    case 3:
        $mesaj_rengi = '#0000FF';
        break;

    case 4:
        $mesaj_rengi = '#FF8C00';
        break;

    default:
        $mesaj_rengi = '';
        break;
}

if ($mesaj_rengi !== '') {
    echo '<span style="color:' . $mesaj_rengi . ';">';
}

echo nl2br($mesaj_html);

if ($mesaj_rengi !== '') {
    echo '</span>';
}
?>


<br/>

<font style="font-size:10px; float:left">
<?php echo date('H:i', (int)$msg['tarix']); ?>
</font>

</div>

<?php } ?>

<div style="clear:both;"></div>
<br/>

<?php } ?>

<?php } ?>


<!-- KEÇİD -->

<!-- SƏHİFƏ KEÇİDLƏRİ -->

<!-- SƏHİFƏ KEÇİDLƏRİ -->

<!-- SƏHİFƏ KEÇİDLƏRİ -->

<!-- SƏHİFƏ KEÇİDLƏRİ -->

<?php if ($page > 0 || $has_next_page) { ?>

<div style="text-align:left; white-space:nowrap; padding:5px 0;">

    <?php if ($page > 0) { ?>

        <span style="display:inline;">
            <a href="arxiv.php?uid=<?php echo (int)$target_user['id']; ?>&amp;p=<?php echo $page - 1; ?>"
               style="display:inline; font-weight:normal; font-size:12px; text-decoration:none;">
                &lt;&lt; &lt;&lt;
            </a>
        </span>

    <?php } ?>

    <?php if ($page > 0 && $has_next_page) { ?>

        <span style="display:inline; font-weight:normal; font-size:12px;">
            &nbsp;|&nbsp;
        </span>

    <?php } ?>

    <?php if ($has_next_page) { ?>

        <span style="display:inline;">
            <a href="arxiv.php?uid=<?php echo (int)$target_user['id']; ?>&amp;p=<?php echo $page + 1; ?>"
               style="display:inline; font-weight:normal; font-size:12px; text-decoration:none;">
                &gt;&gt; &gt;&gt;
            </a>
        </span>

    <?php } ?>

</div>

<?php } ?>

</div>
</div>

</div>
<?php } ?>
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

</body>
</html>


