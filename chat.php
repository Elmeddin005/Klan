<?php
date_default_timezone_set('Asia/Baku');
session_start();

require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$my_id = (int)$_SESSION['user_id'];
$unread_count = 0;

function smaylikleri_cevir($metn, $smaylikler)
{
    foreach ($smaylikler as $smile) {

        $kod = $smile['code'];
        $fayl = $smile['file'];
        $qovluq = $smile['folder'];

        $img = '<img src="muxtelif/smaylikler/'
             . $qovluq . '/'
             . $fayl
             . '" alt="'
             . htmlspecialchars($kod, ENT_QUOTES, 'UTF-8')
             . '">';

        $metn = str_replace($kod, $img, $metn);
    }

    return $metn;
}


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
   CHAT BÖLMƏSİ
========================================================= */

$go = isset($_GET['go']) ? $_GET['go'] : '';
/* =========================================================
   SMAYLİKLƏR
========================================================= */

/* =========================================================
   BÜTÜN SMAYLİKLƏR
========================================================= */

$smaylikler = [

    /* EMOSİYALAR */
    ['file' => 'bee.gif',       'code' => '.bee.',       'folder' => 'emosiyalar'],
    ['file' => 'asqirmaq.gif',  'code' => '.asqir.',     'folder' => 'emosiyalar'],
    ['file' => '555.gif',       'code' => '.555.',       'folder' => 'emosiyalar'],
    ['file' => 'blev2.gif',     'code' => '.blev2.',     'folder' => 'emosiyalar'],
    ['file' => 'blink.gif',     'code' => '.blink.',     'folder' => 'emosiyalar'],
    ['file' => 'cay.gif',       'code' => '.cay.',       'folder' => 'emosiyalar'],
    ['file' => 'deli.gif',      'code' => '.deli.',      'folder' => 'emosiyalar'],
    ['file' => 'dil.gif',       'code' => '.dil.',       'folder' => 'emosiyalar'],
    ['file' => 'dil2.gif',      'code' => '.dil2.',      'folder' => 'emosiyalar'],
    ['file' => 'esebi.gif',     'code' => '.esebi.',     'folder' => 'emosiyalar'],
    ['file' => 'esnemek.gif',  'code' => '.esnemek.',   'folder' => 'emosiyalar'],
    ['file' => 'gulmek1.gif',  'code' => '.gulmek1.',   'folder' => 'emosiyalar'],
    ['file' => 'gulmek5.gif',  'code' => '.gulmek5.',   'folder' => 'emosiyalar'],
    ['file' => 'haha.gif',      'code' => '.haha.',      'folder' => 'emosiyalar'],
    ['file' => 'invalid.gif',   'code' => '.invalid.',   'folder' => 'emosiyalar'],
    ['file' => 'Kuku1.gif',     'code' => '.kuku.',      'folder' => 'emosiyalar'],
    ['file' => 'razi.gif',      'code' => '.razi.',      'folder' => 'emosiyalar'],
    ['file' => 'Ujas.gif',      'code' => '.ujas.',      'folder' => 'emosiyalar'],
    ['file' => 'Yemek.gif',     'code' => '.yemek.',     'folder' => 'emosiyalar'],
    ['file' => 'yuxu1.gif',     'code' => '.yuxu1.',     'folder' => 'emosiyalar'],

    /* GÜLMƏK */
    ['file' => 'g3.gif',        'code' => '.g3.',        'folder' => 'gulmek'],
    ['file' => 'g5.gif',        'code' => '.g5.',        'folder' => 'gulmek'],
    ['file' => 'g8.gif',        'code' => '.g8.',        'folder' => 'gulmek'],
    ['file' => 'g11.gif',       'code' => '.g11.',       'folder' => 'gulmek'],
    ['file' => 'g12.gif',       'code' => '.g12.',       'folder' => 'gulmek'],
    ['file' => 'g14.gif',       'code' => '.g14.',       'folder' => 'gulmek'],
    ['file' => 'g15.gif',       'code' => '.g15.',       'folder' => 'gulmek'],
    ['file' => 'hihi.gif',      'code' => '.hihi.',      'folder' => 'gulmek'],

    /* ƏSƏB */
    ['file' => 'doyus.gif',     'code' => '.doyus.',     'folder' => 'eseb'],
    ['file' => 'h1.gif',        'code' => '.h1.',        'folder' => 'eseb'],
    ['file' => 'h4.gif',        'code' => '.h4.',        'folder' => 'eseb'],
    ['file' => 'h5.gif',        'code' => '.h5.',        'folder' => 'eseb'],
    ['file' => 'h8.gif',        'code' => '.h8.',        'folder' => 'eseb'],
    ['file' => 'h9.gif',        'code' => '.h9.',        'folder' => 'eseb'],
    ['file' => 'h17.gif',       'code' => '.h17.',       'folder' => 'eseb'],
    ['file' => 'pis2.gif',      'code' => '.pis2.',      'folder' => 'eseb'],

    /* KEFSİZ */
    ['file' => 'agla.gif',      'code' => '.agla.',      'folder' => 'kefsiz'],
    ['file' => 'sorry.gif',     'code' => '.sorry.',     'folder' => 'kefsiz'],

    /* SEVGİ */
    ['file' => 'urek.gif',      'code' => '.urek.',      'folder' => 'sevgi'],
    ['file' => 'urek1.gif',     'code' => '.urek1.',     'folder' => 'sevgi'],
    ['file' => 'opdum.gif',     'code' => '.opdum.',     'folder' => 'sevgi'],
    ['file' => 'love.gif',      'code' => '.love.',      'folder' => 'sevgi'],
    ['file' => 'love1.gif',     'code' => '.love1.',     'folder' => 'sevgi'],
    ['file' => 'love2.gif',     'code' => '.love2.',     'folder' => 'sevgi'],
    ['file' => 'love3.gif',     'code' => '.love3.',     'folder' => 'sevgi'],
    ['file' => 'love4.gif',     'code' => '.love4.',     'folder' => 'sevgi'],
    ['file' => 'love5.gif',     'code' => '.love5.',     'folder' => 'sevgi'],
    ['file' => 'love6.gif',     'code' => '.love6.',     'folder' => 'sevgi'],
    ['file' => 'loveme.gif',    'code' => '.loveme.',    'folder' => 'sevgi'],

    /* YUXU */
    ['file' => 'yuxu.gif',      'code' => '.yuxu.',      'folder' => 'yuxu'],
    ['file' => 'laylay.gif',    'code' => '.laylay.',    'folder' => 'yuxu'],
    ['file' => 'yuxu3.gif',    'code' => '.yuxu3.',     'folder' => 'yuxu'],

    /* ÖPÜŞ */
    ['file' => 'zaluyu.gif',   'code' => '.zaluyu.',    'folder' => 'opush'],
    ['file' => 'cem.gif',      'code' => '.cem.',       'folder' => 'opush'],
    ['file' => '4mak.gif',     'code' => '.4mak.',      'folder' => 'opush'],
    ['file' => 'kiss4.gif',    'code' => '.kiss4.',     'folder' => 'opush'],
    ['file' => 'oblom.gif',    'code' => '.oblom.',     'folder' => 'opush'],
    ['file' => 'ops.gif',      'code' => '.ops.',       'folder' => 'opush'],
    ['file' => 'ops2.gif',     'code' => '.ops2.',      'folder' => 'opush'],

    /* QARIŞIQ */
    ['file' => 'a128.gif',     'code' => '.a128.',       'folder' => 'qarisiq'],
    ['file' => 'bicaq.gif',    'code' => '.bicaq.',      'folder' => 'qarisiq'],
    ['file' => 'dash.gif',     'code' => '.dash.',       'folder' => 'qarisiq'],
    ['file' => 'duel.gif',     'code' => '.duel.',       'folder' => 'qarisiq'],
    ['file' => 'gitar.gif',    'code' => '.gitar.',      'folder' => 'qarisiq'],
    ['file' => 'kuku.gif',     'code' => '.kuku2.',      'folder' => 'qarisiq'],
    ['file' => 'nn36.gif',     'code' => '.nn36.',       'folder' => 'qarisiq'],
    ['file' => 'pooh.gif',     'code' => '.pooh.',       'folder' => 'qarisiq'],
    ['file' => 'qiz.gif',      'code' => '.qiz.',        'folder' => 'qarisiq'],
    ['file' => 'qiz1.gif',     'code' => '.qiz1.',       'folder' => 'qarisiq'],
    ['file' => 'qiz3.gif',     'code' => '.qiz3.',       'folder' => 'qarisiq'],
    ['file' => 'qiz4.gif',     'code' => '.qiz4.',       'folder' => 'qarisiq'],
    ['file' => 'qiz5.gif',     'code' => '.qiz5.',       'folder' => 'qarisiq'],
    ['file' => 'song.gif',     'code' => '.song.',       'folder' => 'qarisiq'],
    ['file' => 'tort.gif',     'code' => '.tort.',       'folder' => 'qarisiq'],
    ['file' => 'yeriyox.gif',  'code' => '.yeriyox.',    'folder' => 'qarisiq']

];




if ($go == 'klan') {
    $bolme = 'klan';
} else {
    $bolme = 'umumi';
}


/* =========================================================
   MESAJ GÖNDƏRİLİR
========================================================= */

if (
    isset($_GET['ok']) &&
    $_GET['ok'] == 'yaz' &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $mesaj = isset($_POST['message'])
        ? trim($_POST['message'])
        : '';
$reng = (int)($_POST['reng'] ?? 0);

if ($reng < 0 || $reng > 4) {
    $reng = 0;
}
    if ($mesaj !== '') {

        /* Maksimum 1200 simvol */

        if (mb_strlen($mesaj, 'UTF-8') > 1200) {
            $mesaj = mb_substr(
                $mesaj,
                0,
                1200,
                'UTF-8'
            );
        }


        /* =================================================
           MESAJI MYSQL-A YAZ
        ================================================= */

$stmt_mesaj = $pdo->prepare("
    INSERT INTO chat_messages
    (
        mesaji_yazan_nik,
        cavab_verilen_nik,
        mesaj,
        bolme,
        mesajin_yazildigi_tarix,
        reng
    )
    VALUES
    (
        :nik,
        :cavab_verilen_nik,
        :mesaj,
        :bolme,
       :mesaj_vaxti,
        :reng
    )
");

$stmt_mesaj->execute([
    ':nik' => $user['login'],

    ':cavab_verilen_nik' =>
        isset($_POST['cavab_verilen_nik'])
        ? trim($_POST['cavab_verilen_nik'])
        : '',

    ':mesaj' => $mesaj,

    ':bolme' => $bolme,
    ':mesaj_vaxti' => date('Y-m-d H:i:s'),

    ':reng' => $reng
]);


    /* =================================================
       MESAJDAN SONRA CHAT-I YENİLƏ
    ================================================= */

    if ($bolme == 'klan') {
        header("Location: chat.php?go=klan");
    } else {
        header("Location: chat.php");
    }

    exit;

    }
}


/* =================================================
   MYSQL-DAN MESAJLARI GƏTİR
========================================================= */

/* =========================================================
   MYSQL-DAN MESAJLARI GƏTİR
========================================================= */

/* =========================================================
   CHAT SƏHİFƏLƏMƏ
   1 SƏHİFƏ = 10 MESAJ
========================================================= */

$sayfa = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($sayfa < 1) {
    $sayfa = 1;
}

$mesaj_sayi = 10;


/* ÜMUMİ MESAJ SAYI */

$stmt_say = $pdo->prepare("
    SELECT COUNT(*)
    FROM chat_messages
    WHERE bolme = :bolme
");

$stmt_say->execute([
    ':bolme' => $bolme
]);

$umumi_mesaj_sayi = (int)$stmt_say->fetchColumn();


/* ÜMUMİ SƏHİFƏ SAYI */

$umumi_sayfa = max(
    1,
    (int)ceil($umumi_mesaj_sayi / $mesaj_sayi)
);


/* ARTİQ OLAN SƏHİFƏYƏ KEÇİLMƏSİN */

if ($sayfa > $umumi_sayfa) {
    $sayfa = $umumi_sayfa;
}


/* OFFSET */

$offset = ($sayfa - 1) * $mesaj_sayi;


/* MESAJLARI GƏTİR */

$stmt_mesajlar = $pdo->prepare("
    SELECT
        mesaj_nomresi,
        mesaji_yazan_nik,
        cavab_verilen_nik,
        mesaj,
        bolme,
        mesajin_yazildigi_tarix,
        reng
    FROM chat_messages
    WHERE bolme = :bolme
    ORDER BY mesaj_nomresi DESC
    LIMIT :limit OFFSET :offset
");

$stmt_mesajlar->bindValue(
    ':bolme',
    $bolme,
    PDO::PARAM_STR
);

$stmt_mesajlar->bindValue(
    ':limit',
    $mesaj_sayi,
    PDO::PARAM_INT
);

$stmt_mesajlar->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);

$stmt_mesajlar->execute();

$mesajlar = $stmt_mesajlar->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL" />

<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar" />

<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" />

<link rel="stylesheet" href="css.css">

<meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<title>chat sohbet</title>


<script type="text/javascript">

function goGeri() {
    window.history.back();
}

</script>

</head>


<body>

<div class="main" style="word-wrap:break-word;">


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


<div class="info">

<br/>


<?php if ($go == "klan") { ?>

<!-- KLAN BOLMESI -->

<a href="chat.php?go=klan&amp;uid=">Yenile</a>
|
Klan
|
<a href="chat.php?go=">Umumi</a>


<?php } else { ?>

<!-- UMUMI BOLME -->

<a href="chat.php?go=&amp;uid=">Yenile</a>
|
Ümumi
|
<a href="chat.php?go=klan">Klan</a>

<?php } ?>


<br/>
<br/>


<?php if ($go == "klan") { ?>

<form method="post" action="chat.php?go=klan&amp;ok=yaz">

<?php } else { ?>

<form method="post" action="chat.php?go=&amp;ok=yaz">

<?php } ?>


<input name="message" value="" maxlength="1200" />
<input
    type="hidden"
    name="cavab_verilen_nik"
    id="cavab_verilen_nik"
    value=""
/>
<br/>

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

<input type="submit" class="button" value="Gönder"/>

<?php } else { ?>

<input type="submit" class="button" value="Gönder"/>

<?php } ?>

</form>


<hr/>


<!-- MESAJLAR BURADA GORUNECEK -->

<div id="chatMessages">

<?php if (count($mesajlar) > 0) { ?>

    <?php foreach ($mesajlar as $chat_mesaji) { ?>

        <div class="battle_log">

           <?php

$chat_nik = trim($chat_mesaji['mesaji_yazan_nik']);

/* Mesajı yazan istifadəçini MYSQL-dan tap */
$stmt_chat_user = $pdo->prepare("
    SELECT id, login, oyuncunun_seviyyesi, movqe,vip
    FROM users
    WHERE login = :login
    LIMIT 1
");

$stmt_chat_user->execute([
    ':login' => $chat_nik
]);

$chat_user = $stmt_chat_user->fetch(PDO::FETCH_ASSOC);
?>
<b>[<u><a href="infoforce.php?uid=<?php echo (int)$chat_user['id']; ?>">i</a></u>]</b>
<?php
/* Standart məlumatlar */
$chat_seviyye = 1;
$chat_movqe = 0;

if ($chat_user) {

    $chat_seviyye = isset($chat_user['oyuncunun_seviyyesi'])
        ? (int)$chat_user['oyuncunun_seviyyesi']
        : 1;

    $chat_movqe = isset($chat_user['movqe'])
        ? (int)$chat_user['movqe']
        : 0;
}


/* Mövqeyə görə rəng */
if ($chat_movqe === 2) {

    // Vampir
    $chat_renk = '#0F7100';

} elseif ($chat_movqe === 1) {

    // İnsan
    $chat_renk = 'red';

} elseif ($chat_movqe === 3) {

    // Neytral
    $chat_renk = 'rgb(0, 0, 255)';

} else {

    $chat_renk = 'white';

}


?>

<a
    href="javascript:void(0);"
    onclick="selectUser('<?php
    echo htmlspecialchars(
        $chat_nik,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>'); return false;"
    style="
color: <?php echo $chat_renk; ?> !important;
<?php if ((int)($chat_user['vip'] ?? 0) === 1) { ?>
text-decoration: underline !important;
text-shadow: 1px 1px 1px #888;
<?php } else { ?>
text-decoration: none;
<?php } ?>
"
>
    <?php
    echo htmlspecialchars(
        $chat_nik,
        ENT_QUOTES,
        'UTF-8'
    );
    ?> [<?php echo $chat_seviyye; ?>]
</a>

            (

            <?php
            echo date(
    'd.m.Y | H:i',
    strtotime($chat_mesaji['mesajin_yazildigi_tarix'])
);

            ?>

            ) »

<?php

$mesaj_metni = htmlspecialchars(
    $chat_mesaji['mesaj'],
    ENT_QUOTES,
    'UTF-8'
);

$cavab_verilen_nik = isset(
    $chat_mesaji['cavab_verilen_nik']
)
    ? trim($chat_mesaji['cavab_verilen_nik'])
    : '';

$menim_loginim = trim($user['login']);


/*
=========================================================
 MESAJ MƏNƏ YAZILIBSA
=========================================================
*/

if (
    $cavab_verilen_nik !== '' &&
    mb_strtolower($cavab_verilen_nik, 'UTF-8') ===
    mb_strtolower($menim_loginim, 'UTF-8')
) {
    $nik = htmlspecialchars(
        $cavab_verilen_nik,
        ENT_QUOTES,
        'UTF-8'
    );

    if (
        strpos($mesaj_metni, $nik) === 0
    ) {

        echo '<b style="color:#000000;">';
        echo $nik;
        echo '</b>';

        echo nl2br(
            substr(
                $mesaj_metni,
                strlen($nik)
            )
        );

    } else {

       echo nl2br(
    smaylikleri_cevir(
        $mesaj_metni,
        $smaylikler
    )
);


    }

} else {

    $mesaj_rengi = '';

    switch ((int)($chat_mesaji['reng'] ?? 0)) {

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

    echo nl2br(
        smaylikleri_cevir(
            $mesaj_metni,
            $smaylikler
        )
    );

    if ($mesaj_rengi !== '') {
        echo '</span>';
    }

}

?>

        </div>

    <?php } ?>

<?php } else { ?>

    <b>Mesaj Yoxdur</b>

<?php } ?>

</div>
<?php if ($umumi_sayfa > 1) { ?>

<div style="text-align:left; margin-top:10px;">

    <?php if ($sayfa < $umumi_sayfa) { ?>

        <b>
            <a href="chat.php?go=<?php echo ($bolme == 'klan' ? 'klan' : ''); ?>&amp;page=<?php echo $sayfa + 1; ?>">
                Növbəti
            </a>
        </b>

        <br>

    <?php } ?>


    <?php for ($i = 1; $i <= $umumi_sayfa; $i++) { ?>
<?php if ($i == $sayfa) { ?>

            <b><?php echo $i; ?></b>

        <?php } else { ?>

            <a href="chat.php?go=<?php echo ($bolme == 'klan' ? 'klan' : ''); ?>&amp;page=<?php echo $i; ?>">
                <?php echo $i; ?>
            </a>

        <?php } ?>

        <?php if ($i < $umumi_sayfa) { ?>
            ,
        <?php } ?>

    <?php } ?>

    <br>

</div>

<?php } ?>

<script type="text/javascript">

/*
 * NIKI SEC
 */

function selectUser(nick) {

    var input = document.getElementsByName("message")[0];

    var cavab = document.getElementById(
        "cavab_verilen_nik"
    );

    if (!input) {
        return;
    }

    input.value = nick + ", ";

    if (cavab) {
        cavab.value = nick;
    }

    input.focus();
}

</script>


<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">

&#x20;

[<b><a href="menu.php?">Menu</a></b>]

[<b><a href="axtar.php?">Axtarış</a></b>]

[<a href="forum/mozu2.php?">Forum</a>]

[<a href="shexsi_sehife.php?">Qurğular</a>]


<br/>
<br/>


<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>



<br/>


<a href="index.php?">Çıxış (<?php echo htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8'); ?>)</a>


<br/>
<br/>


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


<a href="http://klanaz.com/klan/" class="xgame.az">

© Klanaz.com 2026

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