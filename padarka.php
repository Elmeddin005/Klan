<?php
$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$gifts = [
    [
        'sekil' => 'hediyye/10.png',
        'mesaj' => ',,,,,,',
        'gonderen' => '***YALQUZAQ***',
        'uid' => 1000749
    ],
    [
        'sekil' => 'hediyye/6.png',
        'mesaj' => '))))',
        'gonderen' => '***YALQUZAQ***',
        'uid' => 1000749
    ],
    [
        'sekil' => 'hediyye/20.png',
        'mesaj' => 'Zefer Bizindir)))',
        'gonderen' => '***YALQUZAQ***',
        'uid' => 1000749
    ]
];

$gift_count = count($gifts);
$page = isset($_GET['s']) ? (int)$_GET['s'] : 0;

if ($page < 0) {
    $page = 0;
}

$per_page = 7;

$start = $page * $per_page;

$page_gifts = array_slice($gifts, $start, $per_page);

$total_pages = ceil($gift_count / $per_page);
?>
<!DOCTYPE html>
<html>
<head>
<meta name="robots" content="ALL" /> 
<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 
<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 

<link rel="stylesheet" href="css.css">

 <meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"><title>hediyyeler</title>
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
     <img src=img/coin.png title="Qızıl" alt=''/> 199 367 053 <img src=img/brill.png title="Brilliant" alt=''/> 16503 <img src=img/energy.png title="Enerji" alt=''/> 50  </div></div></div>
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

<div class="info">

<b>Vahidou [14]</b>, - hediyyeleri.<br/>

<u>Hediyye sayı: (<b><?php echo $gift_count; ?></b>)</u>

<br/>

<?php foreach ($page_gifts as $gift): ?>

<hr/>

<img src="<?php echo htmlspecialchars($gift['sekil']); ?>" alt=".."/><br/>

<?php echo htmlspecialchars($gift['mesaj']); ?><br/>

<b>
    <a href="infoforce.php?uid=<?php echo (int)$gift['uid']; ?>">
        <?php echo htmlspecialchars($gift['gonderen']); ?>
    </a>
</b>

<?php endforeach; ?>

<hr/>

<?php if ($page > 0): ?>
    <a href="padarka.php?uid=<?php echo $uid; ?>&amp;s=<?php echo $page - 1; ?>">
        &#171; Evvelki
    </a>
<?php endif; ?>

<?php if ($page > 0 && $page + 1 < $total_pages): ?>
    &nbsp; | &nbsp;
<?php endif; ?>

<?php if ($page + 1 < $total_pages): ?>
    <a href="padarka.php?uid=<?php echo $uid; ?>&amp;s=<?php echo $page + 1; ?>">
        Növbeti &#187;&#187;
    </a>
<?php endif; ?>

<br/>

[<a href="hediyye.php?uid=<?php echo $uid; ?>&amp;go=hediyye">Hediyye ver</a>]

<br/>

</div>


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
    
<img alt="." height="15" src="http://macera.az/klan/muxtelif/saat.png" title="vaxt" width="15" /> 
16:14
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


