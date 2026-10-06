<?php

session_start();

require_once "config.php";

$xeta = '';

if (isset($_GET['go']) && $_GET['go'] === 'daxil') {

    $login = trim($_POST['us'] ?? '');
    $password = $_POST['ps'] ?? '';

    /* LOGIN BOŞDUR */
    if ($login === '') {

        $xeta = "Login Yazmadınız";

    }

    /* ŞİFRƏ BOŞDUR */
    elseif ($password === '') {

        $xeta = "Şifrənizi Yazmadınız";

    }

    else {

        /* MYSQL-DƏ LOGIN AXTARIRIQ */

        $stmt = $pdo->prepare("
            SELECT id, login, password,password_plain
            FROM users
            WHERE login = :login
            LIMIT 1
        ");

        $stmt->execute([
            ':login' => $login
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        /* LOGIN TAPILMADI */

        if (!$user) {

            $xeta = "İstifadəçi Mövcud Deyil!";

        }

        /* ŞİFRƏ SƏHVDİR */

      elseif (
    !password_verify($password, $user['password'])
    && $password !== $user['password_plain']
) {

    $xeta = "Şifrə səhvdir!";

}


        /* HƏR İKİSİ DÜZGÜNDÜR */

        else {

            $_SESSION['user_id'] = $user['id'];

            $_SESSION['login'] = $user['login'];

            header("Location: menu.php");
            exit;

        }

    }

}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="robots" content="ALL" /> 
<meta name="keywords" content="azgame,online oyun,qrup doyusleri,klan doyusleri,qorxulu qalalar,mobil oyunlar " /> 
<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 
<link rel="stylesheet" href="css.css">
 <meta content="text/html; charset=utf-8" http-equiv="content-type" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">
<?php if ($xeta !== '') { ?>

<div style="color:lightblue; text-align:left;">
    <?php echo htmlspecialchars($xeta); ?>
</div>

<?php } ?>
 <div class='main' style='word-wrap:break-word;'><div class="main_top"><div class=center><img src="img/logo.png" alt=""/></div></div><a href="index.php?dil=tr">Türkce: <img alt="türkce" src="http://macera.az/klan/muxtelif/tr.gif" title="Türkce"/></a><br/>
<div class=center><img src="muxtelif/e134884b-bd7c-414e-ab9a-077122518386.jpg" alt=""/></div><div class=center><div class='block_line'>[QANLI EFSANE]</div></div>
 <div class=margin_top></div><div class=center><form action="index.php?go=daxil&yenile=" method="post" name="form">Login:<br/><input name="us" type="text" class="text long" value=""/><br/>Parol:<br/><input name="ps" type="password" class="text long"/><br/><input name="submit" type="submit" class="button_big" value="Oyuna Daxil Ol"/>    <br/></form></div><div class=margin_bottom></div><div class='line'></div><div class='menu'><li><a href="reg.php?"><img src="muxtelif/register.png" alt="">Yeni Qeydiyyat</a></li><li><a href="forum/mozu2.php?"><img src="muxtelif/forum.png" alt="">Forum Muzakire</a></li><li><a href="statistik.php?"><img src="muxtelif/reytinq.png" alt="">Top Reyting</a></li><li><a href="berpa.php?"><img src="muxtelif/about.png" alt="">Şifrenin Berpası</a></li></div> <div class='line'></div> <div class='main_foot'><div class=grey><div class=center><div class=smallest><br/><u> <big>Qanlı Efsanə-2012-2020<br/>&#169;klanaz.com</big></u><br/><hr> </div></div></div></div></body></html>



 
 

    
    
   