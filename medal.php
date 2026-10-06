<?php
$medal = isset($_GET['medal']) ? (int)$_GET['medal'] : 0;

$medallar = [
    1 => [
        'sekil' => 'medal/39567.gif',
        'sebeb' => '2 ay qayda pozmayan istifadeci'
    ],
    2 => [
        'sekil' => 'medal/12852.gif',
        'sebeb' => '1 ay qayda pozmayan istifadeci'
    ],
    3 => [
        'sekil' => 'medal/22252.gif',
        'sebeb' => '2.000.000-den cox tecrube yigib'
    ],
    4 => [
        'sekil' => 'medal/63427.gif',
        'sebeb' => '1.000.000-den cox tecrube yigib'
    ],
    5 => [
        'sekil' => 'medal/42926.gif',
        'sebeb' => '100.000-den cox tecrube yigib'
    ]
];

if (!isset($medallar[$medal])) {
    $medal = 1;
}

$secili_medal = $medallar[$medal];
?>
<!DOCTYPE html>
<html>
<head>
<meta name="robots" content="ALL" /> 
<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 
<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 

<link rel="stylesheet" href="css.css">

 <meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"><title>medal</title>
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
     <img src=img/coin.png title="Qızıl" alt=''/> 199 367 053 <img src=img/brill.png title="Brilliant" alt=''/> 16553 <img src=img/energy.png title="Enerji" alt=''/> 50  </div></div></div>
<div class="space"></div>
<div style="background: none repeat scroll 0 0 #888686; height: 1px;"></div>

<div class="fl b exp_count"><div style="margin-top: -2px;"><span style="color: #ff3333"><b>25%</b></span></div></div>
<div class="experience">
    <div class="exp_bg">
        <div class="exp_left fl"></div>
        <div class="exp_right fr"></div>
        <div style="width: 25.00%; height: 10px;"><div class="exp_line"></div><div class="exp_point"></div></div>    </div>
</div>
<div style="background: none repeat scroll 0 0 #888686; height: 1px;"></div>

<div class='info'>

<table align="center" border="1" cellpadding="0" cellspacing="0">
<tr>
<td>
<img src="<?php echo htmlspecialchars($secili_medal['sekil']); ?>" alt=" "/><br/>
</td>
<td>
<table border="0" cellpadding="0" cellspacing="0">
<tr>
<td>
<b>Legeb:</b> Vahidou [14]<br/>
<b>Verilme sebebi:</b> <?php echo htmlspecialchars($secili_medal['sebeb']); ?><br/>
<b>Tarix:</b> 18.03.24 | 11:06<br/>
</td>
</tr>
</table>
</td>
</tr>
</table>
</div>

<input type="button" class="button" value="Geri" onclick="goGeri()">

<div class="main_foot">
    <div class="center">
        <div class="grey">
            <div class="small">
                <div class="foot">
    
[<b><a href="dehliz">Menu</a></b>] 
[<b><a href="axtar.php?">Axtarış</a></b>] 
[<a href="forum/mozu2.php?">Forum</a>] 
[<a href="shexsi_sehife.php?">Qurğular</a>]

<br/><br/>
    
<img alt="." height="15" src="http://macera.az/klan/muxtelif/saat.png" title="vaxt" width="15" /> 
14:28
<br/>

<a href="cixis.php?">Çıxış (***YALQUZAQ***)</a>

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


