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

$my_id = (int)$user['id'];

$user_id = $my_id;
$user_login = $user['login'];
$user_ad = $user['ad'];


/* =========================================================
   GET
========================================================= */

$go = $_GET['go'] ?? '';

$mesaj_oxu = false;
$secilen_mesaj = null;


/* =========================================================
   OXUNMAMIŞ MƏKTUBLAR
========================================================= */

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
   SƏVİYYƏ
========================================================= */

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

if ($oyuncu_seviyyesi < 1) {
    $oyuncu_seviyyesi = 1;
}


/* =========================================================
   ONLINE VAXTI
========================================================= */

$stmt_online = $pdo->prepare("
    UPDATE users
    SET online_oyuncu_vaxti = :vaxt
    WHERE id = :id
");

$stmt_online->execute([
    ':vaxt' => time(),
    ':id' => $my_id
]);


/* =========================================================
   ONLINE OYUNCULAR
========================================================= */

$stmt_online_count = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE online_oyuncu_vaxti >= :vaxt
");

$stmt_online_count->execute([
    ':vaxt' => time() - 180
]);

$online_sayi = (int)$stmt_online_count->fetchColumn();


/* =========================================================
   GEDƏN MƏKTUBLARIN SAYI
========================================================= */

$stmt_geden_sayi = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE mesaji_gonderen_nik = :my_id
      AND silindi_gonderen = 0
");

$stmt_geden_sayi->execute([
    ':my_id' => $my_id
]);

$geden_sayi = (int)$stmt_geden_sayi->fetchColumn();


/* =========================================================
   ARXİV MƏKTUBLARININ SAYI
========================================================= */

$stmt_arxiv_sayi = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE
        (
            mesaji_gonderen_nik = :my_id_1
            AND silindi_gonderen = 0
        )
        OR
        (
            mesaji_alan_nik = :my_id_2
            AND silindi_alan = 0
        )
");

$stmt_arxiv_sayi->execute([
    ':my_id_1' => $my_id,
    ':my_id_2' => $my_id
]);

$arxiv_cemi = (int)$stmt_arxiv_sayi->fetchColumn();


/* =========================================================
   ÜMUMİ DƏYİŞƏNLƏR
========================================================= */

$sehife = 1;
$limit = 10;
$baslangic = 0;

$geden_cemi = 0;
$gedenler = [];

$arxiv_sehife = 1;
$arxiv_limit = 10;
$arxiv_baslangic = 0;
$arxivler = [];

$goster_baslangic = 0;
$goster_son = 0;

$arxiv_uid = 0;
$arxiv_mesajlari = [];
$arxiv_user_login = '';

$mektublar_silindi = false;


/* =========================================================
   BÜTÜN MƏKTUBLARI SİL
========================================================= */

if (
    $go === 'delete' &&
    isset($_GET['hami']) &&
    $_GET['hami'] === 'get'
) {

    $stmt_delete = $pdo->prepare("
        UPDATE mesajlar
        SET
            silindi_gonderen = CASE
                WHEN mesaji_gonderen_nik = :my_id_1
                THEN 1
                ELSE silindi_gonderen
            END,

            silindi_alan = CASE
                WHEN mesaji_alan_nik = :my_id_2
                THEN 1
                ELSE silindi_alan
            END

        WHERE
            mesaji_gonderen_nik = :my_id_3
            OR
            mesaji_alan_nik = :my_id_4
    ");

    $stmt_delete->execute([
        ':my_id_1' => $my_id,
        ':my_id_2' => $my_id,
        ':my_id_3' => $my_id,
        ':my_id_4' => $my_id
    ]);

    $mektublar_silindi = true;
}


/* =========================================================
   GEDƏN MESAJI OXU
========================================================= */

if (
    $go === 'geden' &&
    isset($_GET['oxu'])
) {

    $mesaj_id = (int)$_GET['oxu'];

    $stmt_mesaj = $pdo->prepare("
        SELECT
            id,
            mesaji_gonderen_nik,
            mesaji_alan_nik,
            mesaj,
            tarix,
            oxundu
        FROM mesajlar
        WHERE id = :id
          AND mesaji_gonderen_nik = :my_id
        LIMIT 1
    ");

    $stmt_mesaj->execute([
        ':id' => $mesaj_id,
        ':my_id' => $my_id
    ]);

    $secilen_mesaj = $stmt_mesaj->fetch(PDO::FETCH_ASSOC);

    if (!$secilen_mesaj) {
        exit('Mesaj Yoxdur.');
    }

    $mesaj_oxu = true;
}


/* =========================================================
   GEDƏN MƏKTUBLAR
========================================================= */

if ($go === 'geden' && !$mesaj_oxu) {

    $sehife = isset($_GET['s'])
        ? (int)$_GET['s']
        : 1;

    if ($sehife < 1) {
        $sehife = 1;
    }

    $baslangic = ($sehife - 1) * $limit;


    /* CƏMİ GEDƏN */

    $stmt_geden_cemi = $pdo->prepare("
        SELECT COUNT(*)
        FROM mesajlar
        WHERE mesaji_gonderen_nik = :my_id
          AND silindi_gonderen = 0
    ");

    $stmt_geden_cemi->execute([
        ':my_id' => $my_id
    ]);

    $geden_cemi = (int)$stmt_geden_cemi->fetchColumn();


    /* GEDƏN MƏKTUBLAR */

    if ($geden_cemi > 0) {

        $stmt_geden = $pdo->prepare("
            SELECT
                m.id,
                m.mesaji_gonderen_nik,
                m.mesaji_alan_nik,
                m.mesaj,
                m.tarix,
                m.oxundu,

                u.login AS alan_login

            FROM mesajlar m

            LEFT JOIN users u
                ON u.id = m.mesaji_alan_nik

            WHERE m.mesaji_gonderen_nik = :my_id
              AND m.silindi_gonderen = 0

            ORDER BY m.id DESC

            LIMIT :baslangic, :limit
        ");

        $stmt_geden->bindValue(
            ':my_id',
            $my_id,
            PDO::PARAM_INT
        );

        $stmt_geden->bindValue(
            ':baslangic',
            $baslangic,
            PDO::PARAM_INT
        );

        $stmt_geden->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $stmt_geden->execute();

        $gedenler = $stmt_geden->fetchAll(PDO::FETCH_ASSOC);


        /* GÖSTƏRİLƏN ARALIQ */

        $goster_baslangic = $baslangic + 1;

        $goster_son = min(
            $baslangic + $limit,
            $geden_cemi
        );
    }
}


/* =========================================================
   ARXİV - BÜTÜN MESAJLAR
========================================================= */

if (
    $go === 'arxiv' &&
    !isset($_GET['uid'])
) {

    $arxiv_sehife = isset($_GET['s'])
        ? (int)$_GET['s']
        : 1;

    if ($arxiv_sehife < 1) {
        $arxiv_sehife = 1;
    }

    $arxiv_baslangic =
        ($arxiv_sehife - 1) * $arxiv_limit;


    /* =====================================================
       ARXİVDƏ BÜTÜN MESAJLARIN SAYI
    ===================================================== */

    $stmt_arxiv_cemi = $pdo->prepare("
        SELECT COUNT(*)
        FROM mesajlar
        WHERE
            (
                mesaji_gonderen_nik = :my_id_1
                AND silindi_gonderen = 0
            )
            OR
            (
                mesaji_alan_nik = :my_id_2
                AND silindi_alan = 0
            )
    ");

    $stmt_arxiv_cemi->execute([
        ':my_id_1' => $my_id,
        ':my_id_2' => $my_id
    ]);

    $arxiv_cemi = (int)$stmt_arxiv_cemi->fetchColumn();


    /* =====================================================
       ARXİV MESAJLARI
    ===================================================== */

    if ($arxiv_cemi > 0) {

        $stmt_arxiv = $pdo->prepare("
            SELECT
                m.id,
                m.mesaji_gonderen_nik,
                m.mesaji_alan_nik,
                m.mesaj,
                m.tarix,
                m.oxundu,

                CASE
                    WHEN m.mesaji_gonderen_nik = :my_id_1
                    THEN u2.login
                    ELSE u1.login
                END AS diger_login

            FROM mesajlar m

            LEFT JOIN users u1
                ON u1.id = m.mesaji_gonderen_nik

            LEFT JOIN users u2
                ON u2.id = m.mesaji_alan_nik

            WHERE
                (
                    m.mesaji_gonderen_nik = :my_id_2
                    AND m.silindi_gonderen = 0
                )
                OR
                (
                    m.mesaji_alan_nik = :my_id_3
                    AND m.silindi_alan = 0
                )

            ORDER BY m.id DESC

            LIMIT :baslangic, :limit
        ");


        $stmt_arxiv->bindValue(
            ':my_id_1',
            $my_id,
            PDO::PARAM_INT
        );

        $stmt_arxiv->bindValue(
            ':my_id_2',
            $my_id,
            PDO::PARAM_INT
        );

        $stmt_arxiv->bindValue(
            ':my_id_3',
            $my_id,
            PDO::PARAM_INT
        );

        $stmt_arxiv->bindValue(
            ':baslangic',
            $arxiv_baslangic,
            PDO::PARAM_INT
        );

        $stmt_arxiv->bindValue(
            ':limit',
            $arxiv_limit,
            PDO::PARAM_INT
        );

        $stmt_arxiv->execute();

        $arxivler = $stmt_arxiv->fetchAll(PDO::FETCH_ASSOC);
    }
}


/* =========================================================
   ARXİVDƏ KONKRET İSTİFADƏÇİ İLƏ YAZIŞMA
========================================================= */

if (
    $go === 'arxiv' &&
    isset($_GET['uid'])
) {

    $arxiv_uid = (int)$_GET['uid'];

    if ($arxiv_uid > 0) {

        /* İSTİFADƏÇİ */

        $stmt_arxiv_user = $pdo->prepare("
            SELECT
                id,
                login
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $stmt_arxiv_user->execute([
            ':id' => $arxiv_uid
        ]);

        $arxiv_user =
            $stmt_arxiv_user->fetch(PDO::FETCH_ASSOC);


        if (!$arxiv_user) {
            exit('İstifadəçi tapılmadı.');
        }

        $arxiv_user_login =
            $arxiv_user['login'];


        /* =================================================
           HƏMİN ADAMLA BÜTÜN YAZIŞMA
        ================================================= */

        $stmt_arxiv_mesaj = $pdo->prepare("
            SELECT
                id,
                mesaji_gonderen_nik,
                mesaji_alan_nik,
                mesaj,
                tarix,
                oxundu

            FROM mesajlar

            WHERE
                (
                    mesaji_gonderen_nik = :my_id_1
                    AND
                    mesaji_alan_nik = :uid_1
                )

                OR

                (
                    mesaji_gonderen_nik = :uid_2
                    AND
                    mesaji_alan_nik = :my_id_2
                )

            ORDER BY id ASC
        ");

        $stmt_arxiv_mesaj->execute([
            ':my_id_1' => $my_id,
            ':uid_1' => $arxiv_uid,
            ':uid_2' => $arxiv_uid,
            ':my_id_2' => $my_id
        ]);

        $arxiv_mesajlari =
            $stmt_arxiv_mesaj->fetchAll(
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
    content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar"
>

<meta
    name="description"
    content="Azerbaycanda ilk Mobil Online oyunu."
>

<link
    rel="stylesheet"
    href="css.css"
>

<meta
    content="text/html; charset=utf-8"
    http-equiv="content-type"
>

<meta
    name="viewport"
    content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"
>

<title>Mektublar</title>

</head>


<body>

<script>
function goGeri() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = 'mektublar.php';
    }
}
</script>


<div
    class="main"
    style="word-wrap:break-word;"
>


<!-- =====================================================
     HEADER
===================================================== -->

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


<?php if ($new_message_count > 0): ?>

<a href="mektublar.php?go=arxiv">

<img
    src="img/mektub.gif"
    title="Məktub"
    alt="Məktub"
>

</a>

(<?php echo $new_message_count; ?>)

<?php endif; ?>
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


<!-- =====================================================
     PROGRESS
===================================================== -->

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


<?php if (
    $go === 'delete' &&
    $mektublar_silindi
): ?>


<!-- =====================================================
     SİLİNDİ
===================================================== -->

<div class="info">

Bütün Mektublarınız silindi!<br/>

<b>Qeyd:</b> Qarşı tərəfdə məktubların kopyası saxlanılır

<br/>
<br/>

<a href="mektublar.php?">
Mektublar
</a>

</div>


<!-- =====================================================
     ARXİVDƏ KONKRET ADAMLA YAZIŞMA
===================================================== -->

<?php elseif (
    $go === 'arxiv' &&
    $arxiv_uid > 0
): ?>


<div class="info">

<?php echo htmlspecialchars(
    $arxiv_user_login,
    ENT_QUOTES,
    'UTF-8'
); ?>

<br/>
<br/>


<?php if (empty($arxiv_mesajlari)): ?>

<b>Sizə mesaj gəlməyib</b>

<br/>
<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<?php else: ?>


<?php foreach ($arxiv_mesajlari as $mesaj): ?>


<?php

/* TARİX */

$tarix_raw = $mesaj['tarix'];

if (is_numeric($tarix_raw)) {

    $mesaj_timestamp = (int)$tarix_raw;

} else {

    $mesaj_timestamp = strtotime($tarix_raw);
}

if ($mesaj_timestamp !== false) {

    $mesaj_tarix = date(
        'd.m.Y |H:i',
        $mesaj_timestamp
    );

} else {

    $mesaj_tarix = '';
}


/* MESAJ */

$mesaj_metni = nl2br(
    htmlspecialchars(
        $mesaj['mesaj'],
        ENT_QUOTES,
        'UTF-8'
    )
);

?>


<?php if (
    (int)$mesaj['mesaji_gonderen_nik']
    === $my_id
): ?>

<b>Sən</b>

<?php else: ?>

<b>
<?php echo htmlspecialchars(
    $arxiv_user_login,
    ENT_QUOTES,
    'UTF-8'
); ?>
</b>

<?php endif; ?>

[<?php echo $mesaj_tarix; ?>]

<br/>

<?php echo $mesaj_metni; ?>

<br/>
<br/>


<?php endforeach; ?>


<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<?php endif; ?>

</div>


<!-- =====================================================
     ARXİV
===================================================== -->

<?php elseif ($go === 'arxiv'): ?>


<div class="info">


<?php if (empty($arxivler)): ?>


<!-- MESAJ YOXDURSA BAŞLIQ GÖRÜNMÜR -->

<b>Sizə mesaj gəlməyib</b>

<br/>
<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>


<?php else: ?>


<!-- MESAJ VARSA BAŞLIQ GÖRÜNÜR -->

Sizin Arxiv Mektublarınız.

<br/>
<br/>


<?php foreach ($arxivler as $arxiv): ?>


<?php

$arxiv_login = htmlspecialchars(
    $arxiv['diger_login'] ?? 'İstifadəçi',
    ENT_QUOTES,
    'UTF-8'
);


/* TARİX */

$tarix_raw = $arxiv['tarix'];

if (is_numeric($tarix_raw)) {

    $arxiv_timestamp = (int)$tarix_raw;

} else {

    $arxiv_timestamp = strtotime($tarix_raw);
}

if ($arxiv_timestamp !== false) {

    $arxiv_tarix = date(
        'd.m.y |H:i',
        $arxiv_timestamp
    );

} else {

    $arxiv_tarix = '';
}


/* =====================================================
   QARŞI TƏRƏFİN ID-Sİ
===================================================== */

$diger_user_id = (
    (int)$arxiv['mesaji_gonderen_nik'] === $my_id
)
    ? (int)$arxiv['mesaji_alan_nik']
    : (int)$arxiv['mesaji_gonderen_nik'];

?>


<a
    href="arxiv.php?uid=<?php
        echo $diger_user_id;
    ?>"
>
<?php echo $arxiv_login; ?>
</a>


[<?php echo $arxiv_tarix; ?>]

<br/>


<?php endforeach; ?>


<!-- =====================================================
     ARXİV PAGINATION
     
     YALNIZ MESAJ VARSA GÖRÜNÜR
===================================================== -->

<br/>


<?php if ($arxiv_cemi > 0): ?>


<?php if ($arxiv_sehife > 1): ?>

<a
    href="mektublar.php?go=arxiv&s=<?php
        echo $arxiv_sehife - 1;
    ?>"
>

&lt;&lt;

<?php

$geri_baslangic =
    (($arxiv_sehife - 2) * $arxiv_limit) + 1;

$geri_son = min(
    (($arxiv_sehife - 1) * $arxiv_limit),
    $arxiv_cemi
);

echo $geri_baslangic;
?>

-
<?php echo $geri_son; ?>

&lt;&lt;

</a>

<?php endif; ?>


<?php if (
    $arxiv_cemi > ($arxiv_sehife * $arxiv_limit)
): ?>


<?php if ($arxiv_sehife > 1): ?>

&nbsp;

<?php endif; ?>


<a
    href="mektublar.php?go=arxiv&s=<?php
        echo $arxiv_sehife + 1;
    ?>"
>

&gt;&gt;

<?php

$irəli_baslangic =
    ($arxiv_sehife * $arxiv_limit) + 1;

$irəli_son = min(
    ($arxiv_sehife * $arxiv_limit)
    + $arxiv_limit,
    $arxiv_cemi
);

echo $irəli_baslangic;
?>

-
<?php echo $irəli_son; ?>

&gt;&gt;

</a>

<?php endif; ?>


<br/>

<?php endif; ?>


<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>


<?php endif; ?>

</div>


<!-- =====================================================
     GEDƏN MESAJ DETALI
===================================================== -->

<?php elseif (
    $go === 'geden' &&
    $mesaj_oxu
): ?>


<div class="info">

<u>

<b>Göndərdiyin Mesaj:</b>

<?php

$tarix = $secilen_mesaj['tarix'];

if (is_numeric($tarix)) {

    $timestamp = (int)$tarix;

} else {

    $timestamp = strtotime($tarix);
}

if ($timestamp !== false) {

    echo '(' . date(
        'H:i d.m.Y',
        $timestamp
    ) . ')';

}

?>

</u>

<br/>


<u>

<?php echo nl2br(
    htmlspecialchars(
        $secilen_mesaj['mesaj'],
        ENT_QUOTES,
        'UTF-8'
    )
); ?>

</u>

<br/>
<br/>


<a href="mektublar.php?go=geden">
Gedenler(<?php echo $geden_sayi; ?>)
</a>

</div>


<!-- =====================================================
     GEDƏN MƏKTUBLAR
===================================================== -->

<?php elseif ($go === 'geden'): ?>


<div class="info">


<?php if (empty($gedenler)): ?>


<!-- MESAJ YOXDURSA GÖSTƏRİLMƏYƏCƏK:
     Gosterir 0-0 / Cemi 0
-->

<b>Siz Məktub Göndərməmisiniz</b>

<br/>
<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>


<?php else: ?>


<!-- MESAJ VARSA GÖSTƏRİLİR -->

Gosterir
<?php echo $goster_baslangic; ?>
-
<?php echo $goster_son; ?>

/

Cemi
<?php echo $geden_cemi; ?>

<br/>


<?php foreach ($gedenler as $mektub): ?>


<?php

$alan_login = htmlspecialchars(
    $mektub['alan_login'] ?? 'İstifadəçi',
    ENT_QUOTES,
    'UTF-8'
);

$mesaj_id = (int)$mektub['id'];


/* TARİX */

$tarix_raw = $mektub['tarix'];

if (is_numeric($tarix_raw)) {

    $timestamp = (int)$tarix_raw;

} else {

    $timestamp = strtotime($tarix_raw);
}

if ($timestamp !== false) {

    $tarix_goster = date(
        'd.m.y |H:i',
        $timestamp
    );

} else {

    $tarix_goster = '';
}

?>


<?php if (
    (int)$mektub['oxundu'] === 0
): ?>

<b>

Oxunmayıb

<a
    href="mektublar.php?go=geden&oxu=<?php
        echo $mesaj_id;
    ?>"
>

<?php echo $alan_login; ?>

</a>

[<?php echo $tarix_goster; ?>]

</b>


<?php else: ?>

Oxunub

<a
    href="mektublar.php?go=geden&oxu=<?php
        echo $mesaj_id;
    ?>"
>

<?php echo $alan_login; ?>

</a>

[<?php echo $tarix_goster; ?>]


<?php endif; ?>


<br/>


<?php endforeach; ?>


<!-- =====================================================
     GEDƏN PAGINATION
===================================================== -->

<br/>


<?php if ($geden_cemi > 0): ?>


<?php if ($sehife > 1): ?>

<a
    href="mektublar.php?go=geden&s=<?php
        echo $sehife - 1;
    ?>"
>

&lt;&lt;

<?php

$geri_baslangic =
    (($sehife - 2) * $limit) + 1;

$geri_son = min(
    (($sehife - 1) * $limit),
    $geden_cemi
);

echo $geri_baslangic;
?>

-

<?php echo $geri_son; ?>

&lt;&lt;

</a>

<?php endif; ?>


<?php if (
    $geden_cemi > ($sehife * $limit)
): ?>


<?php if ($sehife > 1): ?>

&nbsp;

<?php endif; ?>


<a
    href="mektublar.php?go=geden&s=<?php
        echo $sehife + 1;
    ?>"
>

&gt;&gt;

<?php

$irəli_baslangic =
    ($sehife * $limit) + 1;

$irəli_son = min(
    ($sehife * $limit) + $limit,
    $geden_cemi
);

echo $irəli_baslangic;
?>

-

<?php echo $irəli_son; ?>

&gt;&gt;

</a>

<?php endif; ?>


<br/>

<?php endif; ?>


<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>


<?php endif; ?>

</div>


<!-- =====================================================
     ƏSAS MƏKTUBLAR
===================================================== -->

<?php else: ?>


<div class="info">

<br/>

<div class="center">

<div class="block_line">
Mektublar
</div>

</div>

<br/>

<div class="menu">

<li>
<a href="mektublar.php?go=geden">
Gedenler(<?php echo $geden_sayi; ?>)
</a>
</li>


<li>
<a href="mektublar.php?go=arxiv">
Arxiv Mektublar(<?php echo $arxiv_cemi; ?>)
</a>
</li>


<li>
<a href="mektublar.php?go=xeber">
Bildiriş Mektublar
</a>
</li>


<br/>


<li>
<a href="mektublar.php?go=delete&amp;hami=get">
Mektubları Sil
</a>
</li>


<br/>
<br/>

</div>

</div>


<?php endif; ?>


<!-- =====================================================
     FOOTER
===================================================== -->

<?php if (
    $go !== 'arxiv'
): ?>


<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">


[<b>
<a href="menu.php?">
Menu
</a>
</b>]


[<b>
<a href="axtar.php?">
Axtarış
</a>
</b>]


[<a href="forum/mozu2.php?">
Forum
</a>]


[<a href="shexsi_sehife.php?">
Qurğular
</a>]


<br/>
<br/>


<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>


<br/>


<a href="index.php?">

Çıxış
(<?php

echo htmlspecialchars(
    $user_login,
    ENT_QUOTES,
    'UTF-8'
);

?>)

</a>


<br/>
<br/>


<a href="menu.php?dil=tr">

Türkce:

<img
    alt="türkce"
    src="http://macera.az/klan/muxtelif/tr.gif"
    title="Türkce"
>

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


<?php endif; ?>


</div>

</body>

</html>
