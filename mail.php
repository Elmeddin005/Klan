<?php

session_start();

require_once "config.php";
require_once "user_data.php";

/* =========================================================
   PHPMailer
========================================================= */

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/* =========================================================
   GMAIL SMTP MƏLUMATLARI
========================================================= */

$gmail_smtp = 'emkae73@gmail.com';
$gmail_app_password = 'xlbnsxqufnjxoqyv';


/* =========================================================
   GİRİŞ YOXLAMASI
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];


/* =========================================================
   İSTİFADƏÇİ MƏLUMATLARI
========================================================= */

$user_id = (int)$user['id'];
$user_login = $user['login'];
$user_ad = $user['ad'];


/* =========================================================
   İSTİFADƏÇİNİN EMAIL-İNİ SQL-DƏN GÖTÜRÜRÜK
========================================================= */

$stmt_email = $pdo->prepare("
    SELECT email
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_email->execute([
    ':id' => $my_id
]);

$user_email = $stmt_email->fetchColumn();

if ($user_email === false) {
    $user_email = '';
}


/* =========================================================
   DƏYİŞƏNLƏR
========================================================= */

$xeta = '';
$ugur = '';

$kohne_email = '';
$yeni_email = '';

$email_deyisdirildi = false;


/* =========================================================
   EMAIL TƏSDİQ LİNKİ
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'confirm' &&
    !empty($_GET['token'])
) {

    $token = trim($_GET['token']);

    $stmt_confirm = $pdo->prepare("
        SELECT
            user_id,
            kohne_email,
            yeni_email
        FROM email_deyisme
        WHERE token = :token
        LIMIT 1
    ");

    $stmt_confirm->execute([
        ':token' => $token
    ]);

    $deyisme = $stmt_confirm->fetch(PDO::FETCH_ASSOC);


    /* Link tapılmadı */
    if (!$deyisme) {

        $xeta = 'Təsdiq linki etibarsızdır və ya artıq istifadə olunub.';

    } else {

        /* =================================================
           EMAILİ HƏQİQƏTƏN DƏYİŞİRİK
        ================================================= */

        $stmt_update_email = $pdo->prepare("
            UPDATE users
            SET email = :yeni_email
            WHERE id = :user_id
        ");

        $stmt_update_email->execute([
            ':yeni_email' => $deyisme['yeni_email'],
            ':user_id'    => (int)$deyisme['user_id']
        ]);


        /* =================================================
           TOKENİ SİLİRİK
        ================================================= */

        $stmt_delete_confirm = $pdo->prepare("
            DELETE FROM email_deyisme
            WHERE token = :token
        ");

        $stmt_delete_confirm->execute([
            ':token' => $token
        ]);


        $email_deyisdirildi = true;
    }
}


/* =========================================================
   EMAIL DƏYİŞDİRMƏ ƏMƏLİYYATI
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'ok' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {

    $kohne_email = trim($_POST['mail2'] ?? '');
    $yeni_email  = trim($_POST['mail'] ?? '');


    /* =====================================================
       KÖHNƏ EMAIL YOXLAMASI
    ===================================================== */

    if ($kohne_email !== $user_email) {

        $xeta = 'Köhnə Email düzgün daxil edilməyib!';

    }


    /* =====================================================
       YENİ EMAIL YOXLAMASI
    ===================================================== */

    elseif (!filter_var($yeni_email, FILTER_VALIDATE_EMAIL)) {

        $xeta = 'Yeni E-mail düzgün daxil edilməyib!';

    }


    /* =====================================================
       EYNİ EMAIL YOXLAMASI
    ===================================================== */

    elseif ($yeni_email === $user_email) {

        $xeta = 'Yeni E-mail köhnə E-mail ilə eyni ola bilməz!';

    }


    /* =====================================================
       EMAIL DƏYİŞMƏ SORĞUSU
    ===================================================== */

    else {

        /* Təhlükəsiz token */
        $token = bin2hex(random_bytes(32));


        /* =================================================
           ƏVVƏLKİ GÖZLƏYƏN SORĞUNU SİLİRİK
        ================================================= */

        $stmt_delete = $pdo->prepare("
            DELETE FROM email_deyisme
            WHERE user_id = :user_id
        ");

        $stmt_delete->execute([
            ':user_id' => $my_id
        ]);


        /* =================================================
           YENİ SORĞUNU SQL-Ə YAZIRIQ
        ================================================= */

        $stmt_email_deyisme = $pdo->prepare("
            INSERT INTO email_deyisme
            (
                user_id,
                kohne_email,
                yeni_email,
                token
            )
            VALUES
            (
                :user_id,
                :kohne_email,
                :yeni_email,
                :token
            )
        ");

        $stmt_email_deyisme->execute([
            ':user_id'     => $my_id,
            ':kohne_email' => $user_email,
            ':yeni_email'  => $yeni_email,
            ':token'       => $token
        ]);


        /* =================================================
           TƏSDİQ LİNKİ
        ================================================= */

       $tesdiq_linki =
    'http://localhost/mail.php?go=confirm&token=' .
    urlencode($token);


        /* =================================================
           GMAIL GÖNDƏRİRİK
        ================================================= */

        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();

            $mail->Host = 'smtp.gmail.com';

            $mail->SMTPAuth = true;

            $mail->Username = $gmail_smtp;

            $mail->Password = $gmail_app_password;

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port = 587;
            $mail->SMTPOptions = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];


            $mail->CharSet = 'UTF-8';


            /* Göndərən */
            $mail->setFrom(
                $gmail_smtp,
                'Klanaz'
            );


            /* Köhnə Email-ə göndərilir */
            $mail->addAddress($user_email);


            $mail->isHTML(true);

            $mail->Subject =
                'Email deyisdirilmesi';


            $mail->Body = '
                <html>
                <body>

                <b>Email dəyişdirilməsi</b>

                <br><br>

                Sizin Email ünvanınızı dəyişdirmək üçün
                aşağıdakı linkə daxil olun:

                <br><br>

                <a href="' .
                htmlspecialchars(
                    $tesdiq_linki,
                    ENT_QUOTES,
                    'UTF-8'
                ) .
                '">
                    Emaili təsdiqlə
                </a>

                <br><br>

                Əgər bu əməliyyatı siz etməmisinizsə,
                bu məktubu nəzərə almayın.

                </body>
                </html>
            ';


            $mail->send();

            $ugur = 'Emelyat yerine yetirildi';


        } catch (Exception $e) {

            /*
             * Məktub göndərilmədikdə
             * SQL-dəki gözləyən sorğunu silirik.
             */

            $stmt_delete_error = $pdo->prepare("
                DELETE FROM email_deyisme
                WHERE user_id = :user_id
            ");

            $stmt_delete_error->execute([
                ':user_id' => $my_id
            ]);

            $xeta = 'E-mail göndərilmədi: ' . $e->getMessage();


        }
    }
}


/* =========================================================
   HEADER ÜÇÜN MƏLUMATLAR
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

$new_message_count =
    (int)$stmt_new_message->fetchColumn();


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

$oyuncu_seviyyesi =
    (int)$stmt_seviyye->fetchColumn();

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
    ':id' => $user_id
]);


/* =========================================================
   ONLINE OYUNCULARIN SAYI
========================================================= */

$stmt_online_count = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE online_oyuncu_vaxti >= :vaxt
");

$stmt_online_count->execute([
    ':vaxt' => time() - 180
]);

$online_sayi =
    $stmt_online_count->fetchColumn();


/* =========================================================
   EMAILİN GİZLİ GÖRÜNÜŞÜ
========================================================= */

$email_goster = '';

if (!empty($user_email)) {

    $email_hisseleri =
        explode('@', $user_email, 2);

    $email_ad =
        $email_hisseleri[0] ?? '';

    $ilk_3 =
        substr($email_ad, 0, 3);

    $email_goster =
        $ilk_3 . '**@**.**';
}

?>


<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL" />

<meta
    name="keywords"
    content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar"
/>

<meta
    name="description"
    content="Azerbaycanda ilk Mobil Online oyunu."
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


<!-- =====================================================
     HEADER
===================================================== -->

<div id="header">

<a href="menu.php">

<img
    src="img/logo.png"
    alt=""
/>

</a>

<div class="icons"></div>

<div class="main_foot">

<div class="grey">

<img
    src="img/coin.png"
    title="Qızıl"
    alt=""
/>

<?php
echo (int)$user['qızıl'];
?>


<img
    src="img/brill.png"
    title="Brilliant"
    alt=""
/>

<?php
echo (int)$user['brılyant'];
?>


<img
    src="img/energy.png"
    title="Enerji"
    alt=""
/>

<?php
echo (int)$user['enerjı'];
?>


<?php if ($new_message_count > 0): ?>

<a href="arxiv.php?go=goster">

<img
    src="img/mektub.gif"
    title="Məktub"
    alt="Məktub"
/>

</a>

(<?php
echo $new_message_count;
?>)

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

<?php
echo $progress;
?>%

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


<?php if (!empty($xeta)): ?>


<!-- =====================================================
     XƏTA SƏHİFƏSİ
     FOOTER YOXDUR
===================================================== -->

<div class="info">

<?php
echo htmlspecialchars(
    $xeta,
    ENT_QUOTES,
    'UTF-8'
);
?>

<br />

<a href="menu.php">
Ana Səhifə
</a>

<br />
<br />

</div>


</div>

</body>

</html>

<?php
exit;
?>


<?php endif; ?>


<?php if ($email_deyisdirildi): ?>


<!-- =====================================================
     EMAIL DƏYİŞDİRİLDİ
     FOOTER YOXDUR
===================================================== -->

<div class="info">

<b>
E-mail Uğurla Dəyişdirildi.
</b>


<br />

<a href="menu.php">
Ana Sehife
</a>


<br />

</div>


</div>

</body>

</html>

<?php
exit;
?>


<?php endif; ?>


<?php if (!empty($ugur)): ?>


<!-- =====================================================
     ƏMƏLİYYAT UĞURLU OLDU
     FOOTER YOXDUR
===================================================== -->

<div class="info">

<b>
Əməliyyat yerine yetirildi
</b>

<br>

Diqqet: Sizin köhne E-mail-e
(<u><?php
echo htmlspecialchars(
    $user_email,
    ENT_QUOTES,
    'UTF-8'
);
?></u>)
link gönderildi.

Qeyd olunan linke daxil olmaqla yeni E-mail
(<u><?php
echo htmlspecialchars(
    $yeni_email,
    ENT_QUOTES,
    'UTF-8'
);
?></u>)
tesdiqlemelisiz, eks halda sizin E-mail
<u><?php
echo htmlspecialchars(
    $user_email,
    ENT_QUOTES,
    'UTF-8'
);
?></u>
olaraq qalacaq.

<br>
<br>

<a href="menu.php">
Ana Sehife
</a>

<br>
<br>

</div>


</div>

</body>

</html>

<?php
exit;
?>


<?php endif; ?>


<!-- =====================================================
     NORMAL EMAIL FORMU
===================================================== -->

<div class="info">

Sizin Email-in ilk 3 herfi

<br />

<b>

<?php
echo htmlspecialchars(
    $email_goster,
    ENT_QUOTES,
    'UTF-8'
);
?>

</b>

<hr />


<form
    method="post"
    action="mail.php?go=ok"
>

Köhne E-mail:

<br />

<input
    name="mail2"
    maxlength="255"
    value=""
    title="mail2"
    emptyok="true"
/>

<br />

Yeni E-mail:

<br />

<input
    name="mail"
    maxlength="255"
    value=""
    title="mail"
    emptyok="true"
/>

<br />

<input
    type="hidden"
    name="action"
    value="save"
/>

<input
    type="submit"
    class="button"
    value="Deyiş"
/>

<br />

</form>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

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

<br />
<br />


<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>

<br />


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

<br />
<br />


<a href="menu.php?dil=tr">

Türkce:

<img
    alt="türkce"
    src="http://macera.az/klan/muxtelif/tr.gif"
    title="Türkce"
/>

</a>

<br />

Sciript name: Qanlı efsane(modern version)

<br />


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
