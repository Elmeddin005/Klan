<?php

require_once "config.php";


/* =========================================================
   QEYDİYYAT XƏTASI SƏHİFƏSİ
========================================================= */

function qeydiyyatXetasi($mesaj)
{
?>

<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL" />

<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar" />

<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu." />

<link rel="stylesheet" href="css.css">

<meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<title>Qeydiyyat | Qanli efsane</title>


<script>

function goGeri()
{
    window.history.back()
}

</script>

</head>


<body>

<div class='main' style='word-wrap:break-word;'>

<link rel='stylesheet' type='text/css' href='game/style/new_style.css'>


<div class="main_top">

<div class=center>

<img src="img/logo.png" alt=""/>

</div>

</div>


<div class=center>

<img src="muxtelif/logo.png" alt=""/>

</div>


<div class=center>

<div class='block_line'>Yeni Qeydiyyat</div>

</div>


<div class=margin_top></div>


<?php echo htmlspecialchars($mesaj); ?><br/>


<a href="javascript:goGeri()" style="position:relative; display:inline-block;">

<img src="img/style2/button_smallest.gif" alt="" style="width:100px; height:28px;">

<span style="position:absolute; left:0; top:0; width:100%; height:auto; text-align:center; line-height:26px; color:white;">
Geri
</span>

</a>


</div>

</body>

</html>

<?php

exit;

}


/* =========================================================
   SORĞU YOXLAMASI
========================================================= */

if (
    !isset($_GET['go']) ||
    $_GET['go'] !== 'ok' ||
    !isset($_POST['action']) ||
    $_POST['action'] !== 'save'
) {

    die("Yanlış sorğu.");

}


/* =========================================================
   FORM MƏLUMATLARI
========================================================= */

$login = trim($_POST['user'] ?? '');

$password = $_POST['pass'] ?? '';

$ad = trim($_POST['name'] ?? '');

$cins = $_POST['cins'] ?? '';

$haqqinda = trim($_POST['infa'] ?? '');

$email = trim($_POST['infa2'] ?? '');

$movqe = $_POST['aa'] ?? '';

$kod = trim($_POST['Y_kod'] ?? '');


/* =========================================================
   BOŞ XANA YOXLAMALARI
========================================================= */

if ($login === '') {

    qeydiyyatXetasi("Leqeb yazmadız");

}


if ($password === '') {

    qeydiyyatXetasi("Şifrenizi yazmadız");

}


if ($ad === '') {

    qeydiyyatXetasi("Adınızı yazmadız");

}


if ($haqqinda === '') {

    qeydiyyatXetasi("Haqqınızda yazmadız");

}


if ($email === '') {

    qeydiyyatXetasi("Email yazmadız");

}


if ($kod === '') {

    qeydiyyatXetasi("Kodu yazmadız");

}


/* =========================================================
   ŞİFRƏ MİNİMUM 5
========================================================= */

if (mb_strlen($password, 'UTF-8') < 5) {

    qeydiyyatXetasi("Şifrəniz minimum5 rəqəm olmalıdır.");

}


/* =========================================================
   LƏQƏB MİNİMUM 5
========================================================= */

if (mb_strlen($login, 'UTF-8') < 5) {

    qeydiyyatXetasi("Ləqəbiniz Minimum 5 rəqəm olmalıdır.");

}


/* =========================================================
   LOGIN ARTİQ VAR?
========================================================= */

$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE login = :login
    LIMIT 1
");

$stmt->execute([

    ':login' => $login

]);


if ($stmt->fetch()) {

    qeydiyyatXetasi("Bu Login Artiq Movcuddur");

}


/* =========================================================
   EMAIL ARTİQ VAR?
========================================================= */

$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE email = :email
    LIMIT 1
");

$stmt->execute([

    ':email' => $email

]);


if ($stmt->fetch()) {

    qeydiyyatXetasi("Bu E-mail Artiq Movcuddur");

}



/* =========================================================
   ŞİFRƏNİ HASH EDİRİK
========================================================= */

$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


/* =========================================================
   MYSQL-Ə YAZIRIQ
========================================================= */

$stmt = $pdo->prepare("
    INSERT INTO users
    (
        login,
        password,
        password_plain,
        ad,
        cins,
        email,
        haqqinda,
        movqe,
        kod,
        created_at,
        oyuncunun_seviyyesi,
        oyuncunun_tecrubesi,
        qızıl,
        brılyant,
        enerjı
    )

    VALUES
    (
        :login,
        :password,
        :password_plain,
        :ad,
        :cins,
        :email,
        :haqqinda,
        :movqe,
        :kod,
        NOW(),
        1,
        10,
        30,
        3,
        50
    )
");


try {

    $stmt->execute([
        ':login' => $login,
        ':password' => $password_hash,
        ':password_plain' => $password,
        ':ad' => $ad,
        ':cins' => $cins,
        ':email' => $email,
        ':haqqinda' => $haqqinda,
        ':movqe' => $movqe,
        ':kod' => $kod
    ]);

} catch (PDOException $e) {

    die(
        '<pre style="
            color:red;
            background:white;
            padding:15px;
            white-space:pre-wrap;
        ">' .
        htmlspecialchars($e->getMessage()) .
        '</pre>'
    );

}


$id = $pdo->lastInsertId();


?>


<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL" />

<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar" />

<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu." />

<link rel="stylesheet" href="css.css" type="text/css"/>

<meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<title>Qeydiyyat | Qanli efsane</title>

</head>


<body>

<div class='main' style='word-wrap:break-word;'>

<link rel='stylesheet' type='text/css' href='game/style/new_style.css'>


<div class="main_top">

<div class=center>

<img src="img/logo.png" alt=""/>

</div>

</div>


<div class=center>

<img src="muxtelif/logo.png" alt=""/>

</div>


<div class=center>

<div class='block_line'>Yeni Qeydiyyat</div>

</div>


<div class=margin_top></div>


- <u>Qeydiyyat Tamamlandı.</u><br/>

<hr>


* Sizin id:

<b>
<?php echo htmlspecialchars($id); ?>
</b>

<br/>


* Sizin Loqin:

<b>
<?php echo htmlspecialchars($login); ?>
</b>

<br/>


* Sizin Parol:

<b>
<?php echo htmlspecialchars($password); ?>
</b>

<br/>


<hr>


<div class='menu'>

<li>

<a href="menu.php?">Oyuna Daxil Ol</a>

</li>

</div>


</div>

</body>

</html>