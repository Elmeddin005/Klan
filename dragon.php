<?php
session_start();
/* =========================================================
   DRAGON CASTLE
========================================================= */

$dragon_max_hp = 3000;


/* =========================================================
   ƏSAS SESSION
========================================================= */

if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = '***YALQUZAQ***';
}


/* =========================================================
   MESAJLAR
========================================================= */

if (
    !isset($_SESSION['dragon_messages']) ||
    !is_array($_SESSION['dragon_messages'])
) {
    $_SESSION['dragon_messages'] = array();
}


/* =========================================================
   DÖYÜŞÇÜ
========================================================= */

if (!isset($_SESSION['dragon_fighter'])) {
    $_SESSION['dragon_fighter'] = false;
}


/* =========================================================
   ÜMUMİ ZƏRBƏ
========================================================= */

if (
    !isset($_SESSION['dragon_damage']) ||
    !is_numeric($_SESSION['dragon_damage'])
) {
    $_SESSION['dragon_damage'] = 0;
}


/* =========================================================
   EJDAHA CANI
========================================================= */

if (
    !isset($_SESSION['dragon_hp']) ||
    !is_numeric($_SESSION['dragon_hp'])
) {
    $_SESSION['dragon_hp'] = $dragon_max_hp;
}


/* =========================================================
   OYUNÇU CANI
========================================================= */

if (
    !isset($_SESSION['dragon_player_hp']) ||
    !is_numeric($_SESSION['dragon_player_hp'])
) {
    $_SESSION['dragon_player_hp'] = 2000;
}


/* =========================================================
   MÖVQE
========================================================= */

if (
    !isset($_SESSION['dragon_required_position']) ||
    !is_numeric($_SESSION['dragon_required_position'])
) {
    $_SESSION['dragon_required_position'] = rand(0, 3);
}


/* =========================================================
   QRUPDA OLMA
========================================================= */

if (!isset($_SESSION['dragon_in_group'])) {
    $_SESSION['dragon_in_group'] = false;
}


/* =========================================================
   AKTİV QRUP ID
========================================================= */

if (
    !isset($_SESSION['dragon_group_id']) ||
    !is_numeric($_SESSION['dragon_group_id'])
) {
    $_SESSION['dragon_group_id'] = 0;
}


/* =========================================================
   QRUPLAR
========================================================= */

if (
    !isset($_SESSION['dragon_groups']) ||
    !is_array($_SESSION['dragon_groups'])
) {
    $_SESSION['dragon_groups'] = array();
}


/* =========================================================
   İSTİFADƏÇİNİN YARATDIĞI QRUP
========================================================= */

if (
    !isset($_SESSION['dragon_created_group']) ||
    !is_numeric($_SESSION['dragon_created_group'])
) {
    $_SESSION['dragon_created_group'] = 0;
}


/* =========================================================
   MƏĞLUB QRUPLAR
========================================================= */

if (
    !isset($_SESSION['dragon_defeated_groups']) ||
    !is_array($_SESSION['dragon_defeated_groups'])
) {
    $_SESSION['dragon_defeated_groups'] = array();
}
/* =========================================================
   QƏLƏBƏ QAZANILAN QRUP
========================================================= */

if (
    !isset($_SESSION['dragon_victory_group']) ||
    !is_numeric($_SESSION['dragon_victory_group'])
) {
    $_SESSION['dragon_victory_group'] = 0;
}


/* =========================================================
   KÖHNƏ QRUPLARI NORMALİZASİYA ET
========================================================= */

foreach ($_SESSION['dragon_groups'] as $gid => &$group) {

    if (!is_array($group)) {
        unset($_SESSION['dragon_groups'][$gid]);
        continue;
    }


    /* PLAYERS */

    if (
        !isset($group['players']) ||
        !is_array($group['players'])
    ) {

        $group['players'] = array();

        if (
            isset($group['creator']) &&
            $group['creator'] != ''
        ) {
            $group['players'][] = $group['creator'];
        }
        elseif (
            isset($group['username']) &&
            $group['username'] != ''
        ) {
            $group['players'][] = $group['username'];
        }
    }


    /* DEFEATED */

    if (
        !isset($group['defeated']) ||
        !is_array($group['defeated'])
    ) {
        $group['defeated'] = array();
    }


    /* DAMAGE */

    if (
        !isset($group['damage']) ||
        !is_numeric($group['damage'])
    ) {
        $group['damage'] = 0;
    }


    /* DRAGON HP */

    if (
        !isset($group['dragon_hp']) ||
        !is_numeric($group['dragon_hp'])
    ) {
        $group['dragon_hp'] = $dragon_max_hp;
    }


    /* MAX */

    if (
        !isset($group['max']) ||
        !is_numeric($group['max'])
    ) {
        $group['max'] = 10;
    }
}

unset($group);





/* =========================================================
   QRUPDAN ÇIXMA TƏSDİQİ
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'cix'
) {

    if (
        $_SERVER['REQUEST_METHOD'] == 'POST' &&
        isset($_POST['action']) &&
        $_POST['action'] == 'send'
    ) {

        $gid = intval($_SESSION['dragon_group_id']);

        $username = $_SESSION['username'];


        if (
            $gid != 0 &&
            isset($_SESSION['dragon_groups'][$gid])
        ) {

            if (
                !isset($_SESSION['dragon_groups'][$gid]['players']) ||
                !is_array($_SESSION['dragon_groups'][$gid]['players'])
            ) {
                $_SESSION['dragon_groups'][$gid]['players'] = array();
            }


            $new_players = array();

            foreach (
                $_SESSION['dragon_groups'][$gid]['players']
                as $player
            ) {

                if ($player != $username) {
                    $new_players[] = $player;
                }
            }

            $_SESSION['dragon_groups'][$gid]['players'] =
                $new_players;


            if (
                !isset($_SESSION['dragon_groups'][$gid]['defeated']) ||
                !is_array($_SESSION['dragon_groups'][$gid]['defeated'])
            ) {
                $_SESSION['dragon_groups'][$gid]['defeated'] = array();
            }


            if (
                !in_array(
                    $username,
                    $_SESSION['dragon_groups'][$gid]['defeated'],
                    true
                )
            ) {
                $_SESSION['dragon_groups'][$gid]['defeated'][] =
                    $username;
            }


            if (
                !in_array(
                    $gid,
                    $_SESSION['dragon_defeated_groups'],
                    true
                )
            ) {
                $_SESSION['dragon_defeated_groups'][] = $gid;
            }
        }


        $_SESSION['dragon_in_group'] = false;
        $_SESSION['dragon_group_id'] = 0;
        $_SESSION['dragon_fighter'] = false;

        $_SESSION['dragon_created_group'] = 0;

        $_SESSION['dragon_player_hp'] = 2000;

        $_SESSION['dragon_damage'] = 0;


        header(
            "Location: dragon.php?go=terk_etdin&lis=3"
        );

        exit;
    }
}


/* =========================================================
   MESAJ GÖNDƏR
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'yaz'
) {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $message =
            isset($_POST['message'])
            ? trim($_POST['message'])
            : '';


        if ($message != '') {

            if (
                mb_strlen($message, 'UTF-8') > 300
            ) {
                $message =
                    mb_substr(
                        $message,
                        0,
                        300,
                        'UTF-8'
                    );
            }


            $_SESSION['dragon_messages'][] =
                array(
                    'username' =>
                        $_SESSION['username'],

                    'message' =>
                        $message,

                    'time' =>
                        time()
                );
        }
    }


    header(
        "Location: dragon.php?lis=3"
    );

    exit;
}


/* =========================================================
   OX AT
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'vur'
) {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $hucum =
            isset($_POST['hucum'])
            ? intval($_POST['hucum'])
            : -1;


        /* =================================================
           HAZIRKI DÜZGÜN MÖVQE
        ================================================= */

        $dogru_movqe =
            intval(
                $_SESSION['dragon_required_position']
            );


        /* =================================================
           DÖYÜŞ DAVAM EDİR?
        ================================================= */

        if (
            $_SESSION['dragon_player_hp'] > 0 &&
            $_SESSION['dragon_hp'] > 0
        ) {

            /* =================================================
               ZƏRBƏ MİQDARI
            ================================================= */

            $zerbe = rand(500, 1000);


            /* =================================================
               CƏMİ VURULAN ZƏRBƏ
               DÜZ VƏ SƏHV HƏR İKİSİ SAYILIR
            ================================================= */

            $_SESSION['dragon_damage'] += $zerbe;


            /* =================================================
               QRUP ID
            ================================================= */

            $gid =
                intval(
                    $_SESSION['dragon_group_id']
                );


            /* =================================================
               DÜZGÜN MÖVQE
            ================================================= */

            if ($hucum == $dogru_movqe) {

                /*
                 * Düz vuruldu.
                 * Oyunçunun canı azalmır.
                 */

                $ejderha_zerbesi = $zerbe;


                /*
                 * Ejdahanın qalan canından çox
                 * zərbə vurulmasın.
                 */

                if (
                    $ejderha_zerbesi >
                    $_SESSION['dragon_hp']
                ) {

                    $ejderha_zerbesi =
                        $_SESSION['dragon_hp'];
                }


                /*
                 * Ejdahanın canını azalt
                 */

                $_SESSION['dragon_hp'] -=
                    $ejderha_zerbesi;


                if (
                    $_SESSION['dragon_hp'] < 0
                ) {

                    $_SESSION['dragon_hp'] = 0;
                }

            }

            /* =================================================
               SƏHV MÖVQE
            ================================================= */

            else {

                /*
                 * Səhv vurdu.
                 * Oyunçunun canından yalnız 200 gedir.
                 */

                $_SESSION['dragon_player_hp'] -= 200;


                if (
                    $_SESSION['dragon_player_hp'] < 0
                ) {

                    $_SESSION['dragon_player_hp'] = 0;
                }
            }


            /* =================================================
               QRUP MƏLUMATLARINI YENİLƏ
            ================================================= */

            if (
                $gid != 0 &&
                isset($_SESSION['dragon_groups'][$gid])
            ) {

                $_SESSION['dragon_groups'][$gid]['damage'] =
                    $_SESSION['dragon_damage'];

                $_SESSION['dragon_groups'][$gid]['dragon_hp'] =
                    $_SESSION['dragon_hp'];
            }


          /* =================================================
   EJDAHA ÖLDÜ
================================================= */

/* =================================================
   QƏLƏBƏ
================================================= */

if (
    $_SESSION['dragon_hp'] <= 0
) {

    $_SESSION['dragon_hp'] = 0;


    if (
        $gid != 0 &&
        isset($_SESSION['dragon_groups'][$gid])
    ) {

        $_SESSION['dragon_groups'][$gid]['dragon_hp'] = 0;

        $_SESSION['dragon_groups'][$gid]['damage'] =
            $_SESSION['dragon_damage'];
    }


/* =================================================
   QALİB QRUPU YADDA SAXLA
   HƏR QRUPUN NƏTİCƏSİ AYRICA SAXLANILIR
================================================= */

if (
    !isset($_SESSION['dragon_group_results']) ||
    !is_array($_SESSION['dragon_group_results'])
) {

    $_SESSION['dragon_group_results'] =
        array();
}


/* BU QRUPDA QALİB GƏLDİ */

$_SESSION['dragon_group_results'][$gid] =
    'victory';


/* SON QALİB QRUPU DA SAXLA */

$_SESSION['dragon_victory'] =
    true;

$_SESSION['dragon_victory_group'] =
    $gid;


    /* KÖHNƏ QRUPDAN AYRIL */

    $_SESSION['dragon_in_group'] = false;

    $_SESSION['dragon_group_id'] = 0;

    $_SESSION['dragon_fighter'] = false;


    /* YENİ QRUP YARATMAĞA İCAZƏ VER */

    $_SESSION['dragon_created_group'] = 0;


/*
 * QUTULARI HAZIRLA
 */

$_SESSION['dragon_opened_boxes'] = array();

$_SESSION['dragon_boxes'] = array();


/* =========================================================
   GÖY ƏŞYALAR - 8 ƏDƏD
========================================================= */

$goy_esyalar = array(

    array(
        'name' => 'Xrom Balta',
        'img' => 'img/esyalar/14baltagoy.jpg'
    ),

    array(
        'name' => 'Xrom Qılınc',
        'img' => 'img/esyalar/14qilincgoy.jpg'
    ),

    array(
        'name' => 'Xrom Amulet',
        'img' => 'img/esyalar/14amuletgoy.jpg'
    ),

    array(
        'name' => 'Xrom Zireh',
        'img' => 'img/esyalar/14zirehgoy.jpg'
    ),

    array(
        'name' => 'Xrom Ayaqqabı',
        'img' => 'img/esyalar/14ayaqqabigoy.jpg'
    ),

    array(
        'name' => 'Xrom Əlcək',
        'img' => 'img/esyalar/14elcekgoy.jpg'
    ),

    array(
        'name' => 'Xrom Üzük',
        'img' => 'img/esyalar/14uzukgoy.jpg'
    ),

    array(
        'name' => 'Xrom Kəmər',
        'img' => 'img/esyalar/14kemergoy.jpg'
    )

);


/* =========================================================
   YAŞIL ƏŞYALAR - 8 ƏDƏD
========================================================= */

$yasil_esyalar = array(

    array(
        'name' => 'Yaşıl Balta',
        'img' => 'img/esyalar/14baltayasil.jpg'
    ),

    array(
        'name' => 'Yaşıl Qılınc',
        'img' => 'img/esyalar/14qilincyasil.jpg'
    ),

    array(
        'name' => 'Yaşıl Amulet',
        'img' => 'img/esyalar/14amuletyasil.jpg'
    ),

    array(
        'name' => 'Yaşıl Zireh',
        'img' => 'img/esyalar/14zirehyasil.jpg'
    ),

    array(
        'name' => 'Yaşıl Ayaqqabı',
        'img' => 'img/esyalar/14ayaqqabiyasil.jpg'
    ),

    array(
        'name' => 'Yaşıl Əlcək',
        'img' => 'img/esyalar/14elcekyasil.jpg'
    ),

    array(
        'name' => 'Yaşıl Üzük',
        'img' => 'img/esyalar/14uzukyasil.jpg'
    ),

    array(
        'name' => 'Yaşıl Kəmər',
        'img' => 'img/esyalar/14kemeryasil.jpg'
    )

);


/* =========================================================
   2-Cİ QUTU ÜÇÜN MƏCUNLAR
========================================================= */

$mecunlar_2 = array(

    array(
        'name' => 'Can Mecunu 5%',
        'img' => 'img/mecunlar/can5.jpg'
    ),

    array(
        'name' => 'Can Mecunu 10%',
        'img' => 'img/mecunlar/can10.jpg'
    ),

    array(
        'name' => 'Can Mecunu 20%',
        'img' => 'img/mecunlar/can20.jpg'
    )

);

/* =========================================================
   QUTULARI YALNIZ 1 DƏFƏ YARAT
========================================================= */

if (
    !isset($_SESSION['dragon_boxes']) ||
    !is_array($_SESSION['dragon_boxes']) ||
    count($_SESSION['dragon_boxes']) != 5 ||
    !isset($_SESSION['dragon_boxes'][5]['item']['name'])
) {


    /* =====================================================
       1-Cİ QUTU

       40% GÖY ƏŞYA
       60% YAŞIL ƏŞYA
    ===================================================== */

$random1 = rand(1, 100);

if ($random1 <= 40) {

    $qutu1_item = $goy_esyalar[
        array_rand($goy_esyalar)
    ];

} else {

    $qutu1_item = $yasil_esyalar[
        array_rand($yasil_esyalar)
    ];

}

    /* =====================================================
       2-Cİ QUTU

       5%-lik Məcun
       10%-lik Məcun
       20%-lik Məcun

       3-dən 1-i təsadüfi
    ===================================================== */

    $qutu2_item = $mecunlar_2[
        array_rand($mecunlar_2)
    ];


    /* =====================================================
       3-CÜ QUTU

       100% YAŞIL ƏŞYA
    ===================================================== */

    $qutu3_item = $yasil_esyalar[
        array_rand($yasil_esyalar)
    ];


    /* =====================================================
       4-CÜ QUTU

       100% YAŞIL ƏŞYA
    ===================================================== */

    $qutu4_item = $yasil_esyalar[
        array_rand($yasil_esyalar)
    ];


    /* =====================================================
       5-Cİ QUTU

       40% QARA ALMAZ
       20% YAŞIL ALMAZ
       20% 30%-LİK MƏCUN
       20% 40%-LİK MƏCUN
    ===================================================== */

  $random5 = rand(1, 100);

if ($random5 <= 40) {

    $qutu5_item = array(
        'name' => 'Qara Almaz',
        'img' => 'img/almazlar/almazqara.jpg'
    );

}
elseif ($random5 <= 60) {

    $qutu5_item = array(
        'name' => 'Yaşıl Almaz',
        'img' => 'img/almazlar/almazyasil.jpg'
    );

}
elseif ($random5 <= 80) {

    $mecun30 = array(

        array(
            'name' => 'Can Mecunu 30%',
            'img' => 'img/mecunlar/can30.jpg'
        ),

        array(
            'name' => 'Müdafiə Mecunu 30%',
            'img' => 'img/mecunlar/mudafie30.jpg'
        ),

        array(
            'name' => 'Zərbə Mecunu 30%',
            'img' => 'img/mecunlar/zerbe30.jpg'
        )

    );

    $qutu5_item = $mecun30[
        array_rand($mecun30)
    ];

}
else {

    $mecun40 = array(

        array(
            'name' => 'Can Mecunu 40%',
            'img' => 'img/mecunlar/can40.jpg'
        ),

        array(
            'name' => 'Müdafiə Mecunu 40%',
            'img' => 'img/mecunlar/mudafie40.jpg'
        ),

        array(
            'name' => 'Zərbə Mecunu 40%',
            'img' => 'img/mecunlar/zerbe40.jpg'
        )

    );

    $qutu5_item = $mecun40[
        array_rand($mecun40)
    ];

}


    /* =====================================================
       5 QUTUNU SESSION-A YAZ
    ===================================================== */

    $_SESSION['dragon_boxes'] = array(

        1 => array(
            'opened' => false,
            'item' => $qutu1_item
        ),

        2 => array(
            'opened' => false,
            'item' => $qutu2_item
        ),

        3 => array(
            'opened' => false,
            'item' => $qutu3_item
        ),

        4 => array(
            'opened' => false,
            'item' => $qutu4_item
        ),

        5 => array(
            'opened' => false,
            'item' => $qutu5_item
        )

    );

}


header(
    "Location: dragon.php?go=qalib&lis=3"
);

exit;
}
/* =================================================
   OYUNÇU HƏLƏ SAĞLAMDIRSA
   YENİ TƏSADÜFİ MÖVQE
================================================= */

if (
    $_SESSION['dragon_player_hp'] > 0 &&
    $_SESSION['dragon_hp'] > 0
) {

    $_SESSION['dragon_required_position'] =
        rand(0, 3);

    if (
        $gid != 0 &&
        isset($_SESSION['dragon_groups'][$gid])
    ) {

        $_SESSION['dragon_groups'][$gid]['required_position'] =
            $_SESSION['dragon_required_position'];

    }
}


            /* =================================================
               OYUNÇU ÖLDÜ
            ================================================= */

            if (
                $_SESSION['dragon_player_hp'] <= 0
            ) {

                if (
                    $gid != 0 &&
                    isset($_SESSION['dragon_groups'][$gid])
                ) {

                    if (
                        !isset(
                            $_SESSION['dragon_groups'][$gid]['defeated']
                        ) ||
                        !is_array(
                            $_SESSION['dragon_groups'][$gid]['defeated']
                        )
                    ) {

                        $_SESSION['dragon_groups'][$gid]['defeated'] =
                            array();
                    }


                    if (
                        !in_array(
                            $_SESSION['username'],
                            $_SESSION['dragon_groups'][$gid]['defeated'],
                            true
                        )
                    ) {

                        $_SESSION['dragon_groups'][$gid]['defeated'][] =
                            $_SESSION['username'];
                    }


                    if (
                        !in_array(
                            $gid,
                            $_SESSION['dragon_defeated_groups'],
                            true
                        )
                    ) {

                        $_SESSION['dragon_defeated_groups'][] =
                            $gid;
                    }
                }


                $_SESSION['dragon_in_group'] = false;

                $_SESSION['dragon_group_id'] = 0;

                $_SESSION['dragon_fighter'] = false;

                $_SESSION['dragon_created_group'] = 0;
            }
        }
    }


    /* =================================================
       OYUNÇU ÖLDÜ → MƏĞLUBİYYƏT
    ================================================= */

    if (
        $_SESSION['dragon_player_hp'] <= 0
    ) {

        header(
            "Location: dragon.php?go=meglub&lis=3"
        );

        exit;
    }


    /* =================================================
       GERİ QAYIT
    ================================================= */

    header(
        "Location: dragon.php?lis=3"
    );

    exit;
}


/* =========================================================
   DÖYÜŞÇİ ÇAĞIR
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'cagir_ok'
) {

    $_SESSION['dragon_fighter'] = true;


    header(
        "Location: dragon.php?lis=3&cagirildi=1"
    );

    exit;
}



/* =========================================================
   QRUPA DAXİL OL
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'daxil_ol' &&
    isset($_GET['id'])
) {

    $group_id = intval($_GET['id']);


    if (
        isset($_SESSION['dragon_groups'][$group_id])
    ) {

        $group =
            $_SESSION['dragon_groups'][$group_id];


        /* =================================================
           QRUP MÖVCUD DEYİLSƏ
        ================================================= */

        if (!is_array($group)) {

            header(
                "Location: dragon.php?go=qruplar&lis=3"
            );

            exit;
        }


        /* =================================================
           SİZ BU QRUPDA MƏĞLUB OLMUSUNUZ?
        ================================================= */

        $is_defeated = false;


        if (
            isset($group['defeated']) &&
            is_array($group['defeated'])
        ) {

            if (
                in_array(
                    $_SESSION['username'],
                    $group['defeated'],
                    true
                )
            ) {

                $is_defeated = true;
            }
        }

/* =================================================
   BU OYUNÇU BU QRUPDA ƏVVƏLCƏ QALİB GƏLİBSƏ
   QALİB SƏHİFƏSİNİ GÖSTƏR
================================================= */

if (
    isset($_SESSION['dragon_group_results']) &&
    is_array($_SESSION['dragon_group_results']) &&
    isset($_SESSION['dragon_group_results'][$group_id]) &&
    $_SESSION['dragon_group_results'][$group_id] === 'victory'
) {

    $_SESSION['dragon_victory'] = true;

    $_SESSION['dragon_victory_group'] =
        $group_id;

    $_SESSION['dragon_in_group'] = false;

    $_SESSION['dragon_group_id'] = 0;

    $_SESSION['dragon_fighter'] = false;

    header(
        "Location: dragon.php?go=qalib&lis=3"
    );

    exit;
}

        if ($is_defeated) {

            header(
                "Location: dragon.php?go=meglub&lis=3"
            );

            exit;
        }


        /* =================================================
           HAZIRDA AKTİV DÖYÜŞDƏ QRUPDASANMI?
           
           YALNIZ BU HALDA BAŞQA QRUPA GİRİŞİ BAĞLA.
           
           dragon_created_group BURADA YOXLANILMIR.
           ÇÜNKİ QRUP BİTİBSƏ YENİ QRUPA VƏ BAŞQA QRUPA
           GİRİŞ İCAZƏLİ OLMALIDIR.
        ================================================= */

        if (
            isset($_SESSION['dragon_in_group']) &&
            $_SESSION['dragon_in_group'] === true
        ) {

            $active_group_id =
                intval(
                    $_SESSION['dragon_group_id']
                );


            /* =================================================
               AKTİV QRUPUN HƏQİQƏTƏN MÖVCUD OLDUĞUNU YOXLA
            ================================================= */

            $active_group_valid = false;


            if (
                $active_group_id != 0 &&
                isset(
                    $_SESSION['dragon_groups'][$active_group_id]
                )
            ) {

                $active_group =
                    $_SESSION['dragon_groups'][$active_group_id];


                if (
                    is_array($active_group) &&
                    isset($active_group['dragon_hp']) &&
                    intval($active_group['dragon_hp']) > 0
                ) {

                    $active_group_valid = true;
                }
            }


            /* =================================================
               AKTİV QRUP HƏLƏ DAVAM EDİRSƏ
            ================================================= */

            if ($active_group_valid) {

                /* Eyni qrupa girirsə problem yoxdur */

                if (
                    $active_group_id != $group_id
                ) {

                    header(
                        "Location: dragon.php?go=artiq_qrup&lis=3"
                    );

                    exit;
                }
            }


            /* =================================================
               QRUP BİTİBSƏ
               
               SESSION-U TƏMİZLƏ.
               BELƏLİKLƏ BAŞQA QRUPA GİRƏ BİLƏRSƏN.
            ================================================= */

            else {

                $_SESSION['dragon_in_group'] = false;

                $_SESSION['dragon_group_id'] = 0;

                $_SESSION['dragon_fighter'] = false;
            }
        }


        /* =================================================
           QRUPDA OLANLAR
        ================================================= */

        if (
            !isset(
                $_SESSION['dragon_groups'][$group_id]['players']
            ) ||
            !is_array(
                $_SESSION['dragon_groups'][$group_id]['players']
            )
        ) {

            $_SESSION['dragon_groups'][$group_id]['players'] =
                array();
        }


        /* =================================================
           10 NƏFƏR LİMİTİ
        ================================================= */

        if (
            !in_array(
                $_SESSION['username'],
                $_SESSION['dragon_groups'][$group_id]['players'],
                true
            )
        ) {

            if (
                count(
                    $_SESSION['dragon_groups'][$group_id]['players']
                ) >= 10
            ) {

                header(
                    "Location: dragon.php?go=qruplar&lis=3"
                );

                exit;
            }


            $_SESSION['dragon_groups'][$group_id]['players'][] =
                $_SESSION['username'];
        }




/* =================================================
   YENİ AKTİV QRUP
================================================= */

$_SESSION['dragon_in_group'] = true;

$_SESSION['dragon_group_id'] = $group_id;


        /* =================================================
           QRUP ZƏRBƏSİ
        ================================================= */

        if (
            isset(
                $_SESSION['dragon_groups'][$group_id]['damage']
            )
        ) {

            $_SESSION['dragon_damage'] =
                intval(
                    $_SESSION['dragon_groups'][$group_id]['damage']
                );
        }
        else {

            $_SESSION['dragon_damage'] = 0;
        }


        /* =================================================
           EJDAHA CANI
        ================================================= */

        if (
            isset(
                $_SESSION['dragon_groups'][$group_id]['dragon_hp']
            )
        ) {

            $_SESSION['dragon_hp'] =
                intval(
                    $_SESSION['dragon_groups'][$group_id]['dragon_hp']
                );
        }
        else {

            $_SESSION['dragon_hp'] =
                $dragon_max_hp;
        }

        /* =================================================
           OYUNÇU CANI
        ================================================= */

        $_SESSION['dragon_player_hp'] = 2000;


        /* =================================================
           YENİ MÖVQE
        ================================================= */

        $_SESSION['dragon_required_position'] =
            rand(0, 3);


        /* =================================================
           QRUPA DAXİL OLDU
        ================================================= */

        header(
            "Location: dragon.php?lis=3"
        );

        exit;
    }
}


/* =========================================================
   YENİ QRUP YARAT
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'ok'
) {
    $old_created_group =
    intval($_SESSION['dragon_created_group']);


/* =====================================================
   KÖHNƏ QRUP HƏLƏ AKTİVDİRSƏ YENİ QRUP OLMAZ
===================================================== */

if (
    $old_created_group != 0 &&
    isset($_SESSION['dragon_groups'][$old_created_group])
) {

    $old_group =
        $_SESSION['dragon_groups'][$old_created_group];


    if (
        isset($old_group['dragon_hp']) &&
        intval($old_group['dragon_hp']) > 0
    ) {

        header(
            "Location: dragon.php?go=artiq_qrup&lis=3"
        );

        exit;
    }


    /*
     * dragon_hp = 0
     * deməli köhnə qrup bitib.
     * Yeni qrup yaratmağa icazə ver.
     */

    $_SESSION['dragon_created_group'] = 0;
}

    $old_created_group =
        intval($_SESSION['dragon_created_group']);


    /*
     * Əgər əvvəlki qrup artıq qalib gəlibsə,
     * yeni qrup yaratmağa icazə ver.
     */

    if (
        $old_created_group != 0 &&
        isset($_SESSION['dragon_groups'][$old_created_group]) &&
        isset($_SESSION['dragon_groups'][$old_created_group]['dragon_hp']) &&
        intval(
            $_SESSION['dragon_groups'][$old_created_group]['dragon_hp']
        ) > 0
    ) {

        header(
            "Location: dragon.php?go=artiq_qrup&lis=3"
        );

        exit;
    }


    /*
     * Köhnə qrup qalib gəlibsə,
     * artıq qrup yaratmış hesab olunmur.
     */

    if (
        $old_created_group != 0 &&
        isset($_SESSION['dragon_groups'][$old_created_group]) &&
        isset($_SESSION['dragon_groups'][$old_created_group]['dragon_hp']) &&
        intval(
            $_SESSION['dragon_groups'][$old_created_group]['dragon_hp']
        ) <= 0
    ) {

        $_SESSION['dragon_created_group'] = 0;
    }
    $new_id =
    intval(
        time() . rand(100, 999)
    );




    $new_id =
        intval(
            time() . rand(100, 999)
        );


    $difficulty =
        isset($_POST['bb'])
        ? intval($_POST['bb'])
        : 0;


    $_SESSION['dragon_groups'][$new_id] =
        array(

            'id' =>
                $new_id,

            'name' =>
                'Dragon Castle',

            'players' =>
                array(
                    $_SESSION['username']
                ),

            'max' =>
                10,

            'difficulty' =>
                $difficulty,

            'created' =>
                time(),

            'expires' =>
                strtotime(
                    date('Y-m-d') .
                    ' 22:00:00'
                ),

            'creator' =>
                $_SESSION['username'],

            'damage' =>
                0,

            'dragon_hp' =>
                $dragon_max_hp,

            'defeated' =>
                array()
        );


    $_SESSION['dragon_created_group'] =
        $new_id;

    $_SESSION['dragon_in_group'] =
        true;

    $_SESSION['dragon_group_id'] =
        $new_id;

    $_SESSION['dragon_damage'] =
        0;

    $_SESSION['dragon_hp'] =
        $dragon_max_hp;

    $_SESSION['dragon_player_hp'] =
        2000;

    $_SESSION['dragon_required_position'] =
        rand(0, 3);

    $_SESSION['dragon_messages'] =
        array();

    $_SESSION['dragon_fighter'] =
        false;


    header(
        "Location: dragon.php?lis=3"
    );

    exit;
}


/* =========================================================
   HTML
========================================================= */
?>

<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN"
"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">

<html
xmlns="http://www.w3.org/1999/xhtml"
xml:lang="az"
lang="az">

<head>

<meta
name="robots"
content="ALL" />

<meta
name="keywords"
content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar" />

<meta
name="description"
content="Azerbaycanda ilk Mobil Online oyun. Первый мобильный онлайн-игры" />

<link rel="stylesheet" href="css.css">

<meta
content="text/html; charset=utf-8"
http-equiv="content-type" />

<meta
name="viewport"
content="width=device-width; initial-scale=1.0; maximum-scale=3.0" />

<title>Dragon Castle</title>

<script>

function goGeri() {
    window.history.back();
}

</script>

</head>

<body>

<div
class="main"
style="word-wrap:break-word;">


<!-- =====================================================
     HEADER
===================================================== -->

<div id="header">

<?php

if (
    !isset($_GET['go']) &&
    isset($_SESSION['dragon_in_group']) &&
    $_SESSION['dragon_in_group'] == true
) {

?>

<a href="dragon.php?go=cix&amp;lis=5">

<img
src="img/logo.png"
alt="logo">

</a>

<?php

}
else {

?>

<a href="menu.php?">

<img
src="img/logo.png"
alt="logo">

</a>

<?php

}

?>
<div class="icons"></div>

<div class="main_foot">

<div class="grey">

<img
src="img/coin.png"
title="Qızıl"
alt="" />

199 032 345

<img
src="img/brill.png"
title="Brilliant"
alt="" />

16703

<img
src="img/energy.png"
title="Enerji"
alt="" />

50

</div>

</div>

</div>


<div class="space"></div>

<div
style="background:none repeat scroll 0 0 #888686;height:1px;">
</div>


<!-- =====================================================
     EXPERIENCE
===================================================== -->

<div class="fl b exp_count">

<div style="margin-top:-2px;">

<span style="color:#ff3333">

<b>24%</b>

</span>

</div>

</div>


<div class="experience">

<div class="exp_bg">

<div class="exp_left fl"></div>

<div class="exp_right fr"></div>

<div
style="width:24.00%;height:10px;">

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>

</div>

</div>


<div
style="background:none repeat scroll 0 0 #888686;height:1px;">
</div>


<div class="info">

<?php
if (
    !isset($_GET['go']) ||
    $_GET['go'] != 'hediyye'
) {
?>

<!-- =====================================================
     BAŞLIQ
===================================================== -->

<div class="center">

<div class="block_line">

<b>Dragon Castle</b>

</div>

</div>

<br/>

<?php
}
?>


<?php

/* =========================================================
   QRUPLAR
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'qruplar'
) {

    foreach (
        $_SESSION['dragon_groups']
        as $gid => $group
    ) {

        if (
            isset($group['expires']) &&
            time() >= $group['expires']
        ) {

            unset(
                $_SESSION['dragon_groups'][$gid]
            );
        }
    }

?>

<div class="menu">

<?php

if (
    !empty($_SESSION['dragon_groups'])
) {

    foreach (
        $_SESSION['dragon_groups']
        as $gid => $group
    ) {

        $player_count = 0;

        if (
            isset($group['players']) &&
            is_array($group['players'])
        ) {

            $player_count =
                count($group['players']);
        }

?>

<li>

<a href="dragon.php?go=q&amp;id=<?php echo intval($gid); ?>&amp;lis=3">

<img
src="muxtelif/qrupda.png"
alt="" />

Dragon castle
(<?php echo $player_count; ?>/10)

</a>

</li>

<?php

    }

}

?>

<br/>

<div class="line"></div>

<li>

<a href="dragon.php?go=neww">

[Yeni qrup yarad]

</a>

</li>

<div class="line"></div>

</div>


<?php
/* =========================================================
   YENİ QRUP
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'neww'
) {

    $created_gid =
        intval($_SESSION['dragon_created_group']);


    /* =====================================================
       KÖHNƏ QRUP VARSA ONUN VƏZİYYƏTİNİ YOXLAYIRIQ
    ===================================================== */

    $old_group_active = false;


    if (
        $created_gid != 0 &&
        isset($_SESSION['dragon_groups'][$created_gid])
    ) {

        $old_group =
            $_SESSION['dragon_groups'][$created_gid];


        /*
         * Əjdaha hələ sağdırsa
         * qrup hələ bitməyib.
         */

        if (
            isset($old_group['dragon_hp']) &&
            intval($old_group['dragon_hp']) > 0
        ) {

            $old_group_active = true;
        }
    }


    /* =====================================================
       QRUP HƏLƏ DAVAM EDİRSƏ
    ===================================================== */

    if ($old_group_active) {

?>

<div class="center">

<b style="color:grey;">
Siz Artıq Qrup Yaratmısınız !
</b>

</div>

<br/>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

Dragon Castle

</a>

</li>

<li>

<a href="menu.php?">

Ana sehife

</a>

</li>

</div>

<?php

    }


    /* =====================================================
       QRUP BİTİBSƏ — YENİ QRUP YARATMAQ OLAR
    ===================================================== */

    else {

        /*
         * Köhnə qrup artıq bitib.
         * Ona görə istifadəçinin yaratdığı qrup
         * artıq aktiv qrup hesab olunmur.
         */

        $_SESSION['dragon_created_group'] = 0;

?>

<form
method="post"
action="dragon.php?go=ok">

<b>
Oyunun Gerginliyi:
</b>

<br/>

<select name="bb">

<option value="0">
Zeif
</option>

<option value="1">
Normal
</option>

<option value="2">
Çetin
</option>

</select>

<br/>
<br/>

<input
type="hidden"
name="action"
value="save" />

<input
type="submit"
class="button"
value="Ok" />

</form>

<div class="menu">

<br/>

<li>

<a href="dragon.php?go=qruplar">

<img
src="muxtelif/on.png"
alt="" />

Dragon Castle

</a>

</li>

<li>

<a href="menu.php?">

<img
src="muxtelif/home.png"
alt="" />

Ana sehife

</a>

</li>

</div>

<?php

    }




/* =========================================================
   MƏLUMAT / HƏDİYYƏLƏR
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'melumat'
) {

?>

Qalada canı 1 milyona geder ola bilecek çox güclü bir ejdaha var.

Üstünüzdeki eşyalar ve parametrler bu qalada keçersizdir,
yalnız müeyyen edilmiş oxlar vasitesiyle ve size gösterilen mövqeylerden
ox atmaqla ejdahanı mehv etmek mümkündür.

Sehv mövqey seçdiyiniz halda ejdaha size zerbeler vuracaq.

Qrup maksimum 10 nefer istifadeçi tutur.

Ne qeder çox istifadeçi olarsa ejdahanı bir o qeder tez öldürüb
növbeti qrupu başlatmaq şansınız olacaq.

Qala gün erzinde 1 saat aktiv olur.

Aktiv vaxt bitdikde eger sizin qrup ejdahanın canını 70% azalda bilibse
ve hemçinin siz toplam 10.000-den çox zerbe vurmusuzsa sizin üçün döyüş sona geder davam edir.

Lakin başqa heç kim qrupa daxil ola bilmez ve ya qrup yarada bilmez.

<br/>

<hr/>

<b>Hediyyeler:</b>

<br/>

<div class="menu">

<?php

$hits = array(
    1 => 3000,
    2 => 5000,
    3 => 10000,
    4 => 50000,
    5 => 70000
);

for (
    $i = 1;
    $i <= 5;
    $i++
) {

?>

<div class="battle_log">

<li>

<a
href="dragon.php?go=hediyyeler&amp;idi=<?php echo $i; ?>">

<img
width="48"
height="42"
src="zombi/qutu<?php echo $i; ?>.png"
alt="" />

= (<?php echo $hits[$i]; ?> Zerbe)

</a>

</li>

</div>

<?php

}

?>

</div>

<div class="menu">

<br/>

<li>

<a href="dragon.php?lis=3">

<img
src="img/go_next.png"
alt="" />

Dragon Castle

</a>

</li>

</div>


<?php
/* =========================================================
   HƏDİYYƏLƏRİN İÇİ
========================================================= */
}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'hediyyeler'
) {

    $idi =
        isset($_GET['idi'])
        ? intval($_GET['idi'])
        : 0;


    /* =====================================================
       YALNIZ 1-5 ARASI QUTULAR
    ===================================================== */

    if ($idi < 1 || $idi > 5) {

        header(
            "Location: dragon.php?go=melumat&lis=3"
        );

        exit;
    }


    /* =====================================================
       HƏDİYYƏLƏR
    ===================================================== */

    $hediyyeler = array(

        /* =================================================
           3000 ZƏRBƏ
        ================================================= */

        1 => array(

            array(
               'img' => 'img/esyalar/bruncqilinc.jpg',
                'name' => 'Burunc Qilinc',
                'id' => 41,
                'min' => '47',
                'max' => '53',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/bruncdebilqe.jpg',
                'name' => 'Burunc Debilqe',
                'id' => 42,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/AmetistAmuletgoy.jpg',
                'name' => 'Ametist amulet',
                'id' => 402,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistzirehgoy.jpg',
                'name' => 'Ametist zireh',
                'id' => 403,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistbaltagoy.jpg',
                'name' => 'Ametist balta',
                'id' => 404,
                'min' => '2464',
                'max' => '2630',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistkemergoy.jpg',
                'name' => 'Ametist kemer',
                'id' => 405,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistayaqqabigoy.jpg',
                'name' => 'Ametist ayaqqabi',
                'id' => 406,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistgurzgoy.jpg',
                'name' => 'Ametist gurz',
                'id' => 407,
                'min' => '2464',
                'max' => '2630',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistelcekgoy.jpg',
                'name' => 'Ametist elcek',
                'id' => 408,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistdebilqegoy.jpg',
                'name' => 'Ametist debilge',
                'id' => 409,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistuzukgoy.jpg',
                'name' => 'Ametist uzuk',
                'id' => 410,
                'min' => '798',
                'max' => '1212',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistqilincgoy.jpg',
                'name' => 'Ametist qilinc',
                'id' => 411,
                'min' => '2464',
                'max' => '2630',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/AmetistAmuletyasil.jpg',
                'name' => 'Ametist amulet',
                'id' => 412,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistzirehyasil.jpg',
                'name' => 'Ametist zireh',
                'id' => 413,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistbaltayasil.jpg',
                'name' => 'Ametist balta',
                'id' => 414,
                'min' => '2710',
                'max' => '2893',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistkemeryasil.jpg',
                'name' => 'Ametist kemer',
                'id' => 415,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistayaqqabiyasil.jpg',
                'name' => 'Ametist ayaqqabi',
                'id' => 416,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistgurzyasil.jpg',
                'name' => 'Ametist gurz',
                'id' => 417,
                'min' => '2710',
                'max' => '2893',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistelcekyasil.jpg',
                'name' => 'Ametist elcek',
                'id' => 418,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistdebilqeyasil.jpg',
                'name' => 'Ametist debilge',
                'id' => 419,
                'min' => '',
                'max' => '',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistuzukyasil.jpg',
                'name' => 'Ametist uzuk',
                'id' => 420,
                'min' => '877',
                'max' => '1333',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            ),

            array(
                'img' => 'img/esyalar/Ametistqilincyasil.jpg',
                'name' => 'Ametist qilinc',
                'id' => 421,
                'min' => '2710',
                'max' => '2893',
                'param' => '70 - 350',
                'faiz' => '6 - 12'
            )

        ),

    

2 => array(

    array(
        'img' => 'img/mecunlar/sehirli20.jpg',
        'name' => 'Sehirli Mecun 20%',
        'id' => 147,
        'about' => 'Doyush vaxti istifade olunur ,azalan cani maxsimum canin 20% i geder artirir'
    ),

    array(
        'img' => 'img/mecunlar/can5.jpg',
        'name' => 'Can Mecunu 5%',
        'id' => 160,
        'about' => 'Istifade olunduqda 3 saat erzinde Caninizi 5% artirir'
    ),

    array(
        'img' => 'img/mecunlar/can20.jpg',
        'name' => 'Can Mecunu 20%',
        'id' => 162,
        'about' => 'Istifade olunduqda 3 saat erzinde Caninizi 20% artirir'
    ),

    array(
        'img' => 'img/mecunlar/zerbe5.jpg',
        'name' => 'Zerbe Mecunu 5%',
        'id' => 165,
        'about' => 'Istifade olunduqda 3 saat erzinde Zerbenizi 5% artirir'
    ),

    array(
        'img' => 'img/mecunlar/zerbe20.jpg',
        'name' => 'Zerbe Mecunu 20%',
        'id' => 167,
        'about' => 'Istifade olunduqda 3 saat erzinde Zerbenizi 20% artirir'
    ),

    array(
        'img' => 'img/mecunlar/mudafie10.jpg',
        'name' => 'Mudafie Mecunu 10%',
        'id' => 171,
        'about' => 'Istifade olunduqda 3 saat erzinde Mudafienizi 10% artirir'
    )

),

/* =================================================
   10000 ZƏRBƏ
================================================= */

3 => array(

    array(
        'img' => 'img/esyalar/bruncqilinc.jpg',
        'name' => 'Burunc Qilinc',
        'min' => '47',
        'max' => '53',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/bruncdebilqe.jpg',
        'name' => 'Burunc Debilqe',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/AmetistAmuletgoy.jpg',
        'name' => 'Ametist amulet',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistzirehgoy.jpg',
        'name' => 'Ametist zireh',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistbaltagoy.jpg',
        'name' => 'Ametist balta',
        'min' => '2464',
        'max' => '2630',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistkemergoy.jpg',
        'name' => 'Ametist kemer',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistayaqqabigoy.jpg',
        'name' => 'Ametist ayaqqabi',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistgurzgoy.jpg',
        'name' => 'Ametist gurz',
        'min' => '2464',
        'max' => '2630',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistelcekgoy.jpg',
        'name' => 'Ametist elcek',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistdebilqegoy.jpg',
        'name' => 'Ametist debilge',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistuzukgoy.jpg',
        'name' => 'Ametist uzuk',
        'min' => '798',
        'max' => '1212',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistqilincgoy.jpg',
        'name' => 'Ametist qilinc',
        'min' => '2464',
        'max' => '2630',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/AmetistAmuletyasil.jpg',
        'name' => 'Ametist amulet',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistzirehyasil.jpg',
        'name' => 'Ametist zireh',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistbaltayasil.jpg',
        'name' => 'Ametist balta',
        'min' => '2710',
        'max' => '2893',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistkemeryasil.jpg',
        'name' => 'Ametist kemer',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistayaqqabiyasil.jpg',
        'name' => 'Ametist ayaqqabi',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistgurzyasil.jpg',
        'name' => 'Ametist gurz',
        'min' => '2710',
        'max' => '2893',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistelcekyasil.jpg',
        'name' => 'Ametist elcek',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistdebilqeyasil.jpg',
        'name' => 'Ametist debilge',
        'min' => '',
        'max' => '',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistuzukyasil.jpg',
        'name' => 'Ametist uzuk',
        'min' => '877',
        'max' => '1333',
        'param' => '263 - 350',
        'faiz' => '6 - 18'
    ),

    array(
        'img' => 'img/esyalar/Ametistqilincyasil.jpg',
        'name' => 'Ametist qilinc',
        'id' => 421,
        'min' => '2710',
        'max' => '2893',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    )

),   // <-- 4 => array burada bağlanır
/* =================================================
   50 000 ZƏRBƏ
================================================= */

4 => array(

    array(
        'img' => 'img/esyalar/bruncqilinc.jpg',
        'name' => 'Burunc Qilinc',
        'id' => 41,
        'min' => '47',
        'max' => '53',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/bruncdebilqe.jpg',
        'name' => 'Burunc Debilqe',
        'id' => 42,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/AmetistAmuletgoy.jpg',
        'name' => 'Ametist amulet',
        'id' => 402,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistzirehgoy.jpg',
        'name' => 'Ametist zireh',
        'id' => 403,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistbaltagoy.jpg',
        'name' => 'Ametist balta',
        'id' => 404,
        'min' => '2464',
        'max' => '2630',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistkemergoy.jpg',
        'name' => 'Ametist kemer',
        'id' => 405,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistayaqqabigoy.jpg',
        'name' => 'Ametist ayaqqabi',
        'id' => 406,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistgurzgoy.jpg',
        'name' => 'Ametist gurz',
        'id' => 407,
        'min' => '2464',
        'max' => '2630',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistelcekgoy.jpg',
        'name' => 'Ametist elcek',
        'id' => 408,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistdebilqegoy.jpg',
        'name' => 'Ametist debilge',
        'id' => 409,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistuzukgoy.jpg',
        'name' => 'Ametist uzuk',
        'id' => 410,
        'min' => '798',
        'max' => '1212',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistqilincgoy.jpg',
        'name' => 'Ametist qilinc',
        'id' => 411,
        'min' => '2464',
        'max' => '2630',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/AmetistAmuletyasil.jpg',
        'name' => 'Ametist amulet',
        'id' => 412,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistzirehyasil.jpg',
        'name' => 'Ametist zireh',
        'id' => 413,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistbaltayasil.jpg',
        'name' => 'Ametist balta',
        'id' => 414,
        'min' => '2710',
        'max' => '2893',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistkemeryasil.jpg',
        'name' => 'Ametist kemer',
        'id' => 415,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/AmetistAmuletyasil.jpg',
        'name' => 'Ametist ayaqqabi',
        'id' => 416,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistgurzyasil.jpg',
        'name' => 'Ametist gurz',
        'id' => 417,
        'min' => '2710',
        'max' => '2893',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistelcekyasil.jpg',
        'name' => 'Ametist elcek',
        'id' => 418,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistdebilqeyasil.jpg',
        'name' => 'Ametist debilge',
        'id' => 419,
        'min' => '',
        'max' => '',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistuzukyasil.jpg',
        'name' => 'Ametist uzuk',
        'id' => 420,
        'min' => '877',
        'max' => '1333',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    ),

    array(
        'img' => 'img/esyalar/Ametistqilincyasil.jpg',
        'name' => 'Ametist qilinc',
        'id' => 421,
        'min' => '2710',
        'max' => '2893',
        'param' => '315 - 350',
        'faiz' => '11 - 24'
    )
    ),
    /* =================================================
   70 000 ZƏRBƏ
================================================= */

5 => array(

    array(
        'img' => 'img/almazlar/almazqara.jpg',
        'name' => 'Qara almaz',
        'id' => 280,
        'min' => '',
        'max' => '',
        'param' => '',
        'faiz' => '',
        'about' => 'Eşyanın əsas parametrlərini 10% gücləndirmək üçün istifadə olunur'
    ),

    array(
        'img' => 'img/almazlar/almazyasil.jpg',
        'name' => 'Yaşıl almaz',
        'id' => 331,
        'min' => '',
        'max' => '',
        'param' => '',
        'faiz' => '',
        'about' => 'Eşyanın əsas parametrlərini 10% gücləndirmək üçün istifadə olunur. Nəticələr 100% uğurlu alınır'
    ),

    array(
        'img' => 'img/mecunlar/can30.jpg',
        'name' => 'Can Mecunu 30%',
        'id' => 163,
        'min' => '',
        'max' => '',
        'param' => '',
        'faiz' => '',
        'about' => 'İstifadə olunduqda 3 saat ərzində Canınızı 30% artırır'
    ),

    array(
        'img' => 'img/mecunlar/can40.jpg',
        'name' => 'Can Mecunu 40%',
        'id' => 164,
        'min' => '',
        'max' => '',
        'param' => '',
        'faiz' => '',
        'about' => 'İstifadə olunduqda 3 saat ərzində Canınızı 40% artırır'
    ),

    array(
        'img' => 'img/mecunlar/zerbe30.jpg',
        'name' => 'Zərbə Mecunu 30%',
        'id' => 168,
        'min' => '',
        'max' => '',
        'param' => '',
        'faiz' => '',
        'about' => 'İstifadə olunduqda 3 saat ərzində Zərbənizi 30% artırır'
    ),

    array(
        'img' => 'img/mecunlar/zerbe40.jpg',
        'name' => 'Zərbə Mecunu 40%',
        'id' => 169,
        'min' => '',
        'max' => '',
        'param' => '',
        'faiz' => '',
        'about' => 'İstifadə olunduqda 3 saat ərzində Zərbənizi 40% artırır'
    ),

    array(
        'img' => 'img/mecunlar/mudafie30.jpg',
        'name' => 'Müdafiə Mecunu 30%',
        'id' => 173,
        'min' => '',
        'max' => '',
        'param' => '',
        'faiz' => '',
        'about' => 'İstifadə olunduqda 3 saat ərzində Müdafiənizi 30% artırır'
    ),

    array(
        'img' => 'img/mecunlar/mudafie40.jpg',
        'name' => 'Müdafiə Mecunu 40%',
        'id' => 174,
        'min' => '',
        'max' => '',
        'param' => '',
        'faiz' => '',
        'about' => 'İstifadə olunduqda 3 saat ərzində Müdafiənizi 40% artırır'
    )

),   // 5 => array bağlanır

);    // $hediyyeler = array bağlanır

?>

<div class="center">



</div>

</div>

<br/>

Qeyd:Qutudan gösterilen esyalardan biri çıxa biler

<br/>

<?php



/* =====================================================
   HƏDİYYƏLƏR / ƏŞYALAR
===================================================== */

if (
    isset($hediyyeler[$idi]) &&
    !empty($hediyyeler[$idi])
) {

    foreach (
        $hediyyeler[$idi]
        as $item
    ) {

?>

<div class="battle_log">

<div class="content">

<table
border="0"
cellpadding="2"
cellspacing="0">

<tr>

<td>

<img src="<?php echo htmlspecialchars($item['img'], ENT_QUOTES, 'UTF-8'); ?>" alt="Shekil"/>

</td>

<td>

<?php

echo htmlspecialchars(
    $item['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

<?php

/* =================================================
   MƏCUNLAR ÜÇÜN HAQQINDA
================================================= */

if (
    isset($item['about']) &&
    $item['about'] != ''
) {

?>

<br/>

Haqqında:
<?php

echo htmlspecialchars(
    $item['about'],
    ENT_QUOTES,
    'UTF-8'
);

?>

<?php

}

/* =================================================
   ADİ ƏŞYALAR ÜÇÜN PARAMETRLƏR
================================================= */

else {

    if (
        isset($item['min']) &&
        isset($item['max']) &&
        $item['min'] != '' &&
        $item['max'] != ''
    ) {

?>

<br/>

Mini.zerbe:
<?php echo $item['min']; ?>

<br/>

Maks.zerbe:
<?php echo $item['max']; ?>

<?php

    }

    if (
        isset($item['param']) &&
        $item['param'] != ''
    ) {

?>

<br/>

Elave parametrler:
<?php echo $item['param']; ?>

<?php

    }

    if (
        isset($item['faiz']) &&
        $item['faiz'] != ''
    ) {

?>

<br/>

Elave faizler:
<?php echo $item['faiz']; ?>

<?php

    }

}

?>

<br/>

</td>

</tr>

</table>

</div>

</div>

<?php

    }

}

?>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()"
/>
<?php
/* =========================================================
   DÖYÜŞÇÜ ÇAĞIR
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'cagir'
) {

?>

<?php

if (
    $_SESSION['dragon_fighter'] == true
) {

?>

<a
href="dragon.php?go=cagir_ok&amp;uid=1000506&amp;lis=3">

(Go)

</a>

|

<a
href="infoforce.php?uid=1000506">

<font color="green">

YuRi_BoYKa [14]

</font>

</a>

<hr/>



<a href="dragon.php?lis=3">

Geri

</a>

<?php

}
else {

?>

<a href="dragon.php?lis=3">

Geri

</a>

<?php

}

/* =========================================================
   QƏLƏBƏ
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'qalib'
) {
    

/* =========================================================
   NƏTİCƏ XALI VƏ QUTU SAYI
========================================================= */

$victory_group_id = isset($_SESSION['dragon_victory_group'])
    ? intval($_SESSION['dragon_victory_group'])
    : 0;

$final_score = 0;

if (
    $victory_group_id != 0 &&
    isset($_SESSION['dragon_groups'][$victory_group_id]) &&
    is_array($_SESSION['dragon_groups'][$victory_group_id])
) {

    if (
        isset(
            $_SESSION['dragon_groups'][$victory_group_id]['damage']
        )
    ) {

        $final_score = intval(
            $_SESSION['dragon_groups'][$victory_group_id]['damage']
        );
    }
}

/* Əgər qrup damage-i yoxdursa session damage-dən götür */
if (
    $final_score <= 0 &&
    isset($_SESSION['dragon_damage'])
) {

    $final_score = intval(
        $_SESSION['dragon_damage']
    );
}


/* =========================================================
   XALA GÖRƏ QUTU SAYI
========================================================= */

$box_count = 0;

if ($final_score >= 70000) {

    $box_count = 5;

}
elseif ($final_score >= 50000) {

    $box_count = 4;

}
elseif ($final_score >= 10000) {

    $box_count = 3;

}
elseif ($final_score >= 5000) {

    $box_count = 2;

}
elseif ($final_score >= 3000) {

    $box_count = 1;
}


/* =========================================================
   70 000 OLARSA QALİB
========================================================= */

$is_real_victory =
    ($final_score >= 70000);

?>



<div class="center">

</div>

</div>


<div class="center">

<b>
<?php

if ($final_score >= 70000) {
    echo 'Siz Qalib Gəldiniz!';
} else {
    echo 'Siz Məğlub oldunuz!';
}

?>
(Xal:<?php echo $final_score; ?>)
</b>

<br/>
<br/>

<b>Qazandınız:</b>


Beşinci Qutu

<br/>

<div class="battle_log">

<div class="content">
<table 
border="0" 
cellpadding="0" 
cellspacing="0" 
style="margin-left:30%;" 

>
<tr>

<td>

<img
src="zombi/qutu5.png"
alt="qutu5"
/>

</td>

<td>

<img
src="muxtelif/gifts.gif"
alt=""
>

<?php

for (
    $i = 1;
    $i <= $box_count;
    $i++
) {

?>

<img
src="muxtelif/gifts.gif"
alt=""
>

<?php

for (
    $i = 1;
    $i <= $box_count;
    $i++
) {

?>

<img
src="muxtelif/gifts.gif"
alt=""
>

<?php

if (
    isset($_SESSION['dragon_opened_boxes'][$i]) &&
    $_SESSION['dragon_opened_boxes'][$i] === true
) {

?>

<img
src="muxtelif/okey.png"
alt="Açılıb"
/>

<?php

}

?>

<a
href="dragon.php?go=hediyye&amp;qutu=<?php echo $i; ?>&amp;qrup_num=<?php echo intval($_SESSION['dragon_victory_group']); ?>"
>
Hediyyə <?php echo $i; ?>
</a>

<br/>

<?php

}

?>

</td>

</tr>

</table>

</div>

</div>

</div>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

<img
src="img/go_next.png"
alt=""
>

Dragon Castle

</a>

</li>

<li>

<a href="menu.php?">

<img
src="img/go_next.png"
alt=""
>

Ana sehife

</a>

</li>

</div>

</div>

<?php

/* =========================================================
   HƏDİYYƏ AÇ
========================================================= */


elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'hediyye'
) {

    $qutu = isset($_GET['qutu'])
        ? intval($_GET['qutu'])
        : 0;


    /* =====================================================
       YALNIZ 1-5 ARASI QUTULAR
    ===================================================== */

    if ($qutu < 1 || $qutu > 5) {

        header(
            "Location: dragon.php?go=qalib"
        );

        exit;
    }


    /* =====================================================
       QUTULAR YOXDURSA GERİ QAYIT
    ===================================================== */

    if (
        !isset($_SESSION['dragon_boxes']) ||
        !is_array($_SESSION['dragon_boxes'])
    ) {

        header(
            "Location: dragon.php?go=qalib"
        );

        exit;
    }


    /* =====================================================
       SEÇİLƏN QUTU YOXDURSA GERİ QAYIT
    ===================================================== */

    if (
        !isset($_SESSION['dragon_boxes'][$qutu]) ||
        !is_array($_SESSION['dragon_boxes'][$qutu])
    ) {

        header(
            "Location: dragon.php?go=qalib"
        );

        exit;
    }


    /* =====================================================
       QUTUNUN ƏŞYASINI GÖTÜR
       BURADA YENİ ƏŞYA YARADILMIR
    ===================================================== */

    $box =
        $_SESSION['dragon_boxes'][$qutu];

    $item =
        isset($box['item']) &&
        is_array($box['item'])
        ? $box['item']
        : array(
            'name' => 'Əşya yoxdur',
            'img' => ''
        );


    $item_name =
        isset($item['name'])
        ? $item['name']
        : 'Əşya yoxdur';


    $item_img =
        isset($item['img'])
        ? $item['img']
        : '';


    /* =====================================================
       AÇILMIŞ QUTUNU YADDA SAXLA
    ===================================================== */

    if (
        !isset($_SESSION['dragon_opened_boxes']) ||
        !is_array($_SESSION['dragon_opened_boxes'])
    ) {

        $_SESSION['dragon_opened_boxes'] =
            array();
    }


    $_SESSION['dragon_opened_boxes'][$qutu] =
        true;

?>


<div class="center">

<b>
Siz Qalib oldunuz!
(Xal: <?php echo isset($final_score) ? $final_score : 70000; ?>)
</b>

</div>

<br/>

Göstərilən əşya daha əvvəl çantanıza göndərilib

<br/>

<div class="battle_log">

<div class="content">

<table
border="0"
cellpadding="0"
cellspacing="0"
>

<tr>

<td>

<?php

if ($item_img != '') {

?>

<img
src="<?php echo htmlspecialchars(
    $item_img,
    ENT_QUOTES,
    'UTF-8'
); ?>"
alt="<?php echo htmlspecialchars(
    $item_name,
    ENT_QUOTES,
    'UTF-8'
); ?>"
/>

<?php

}

?>

</td>

<td>

<?php

echo htmlspecialchars(
    $item_name,
    ENT_QUOTES,
    'UTF-8'
);

?>

<br/>

<a
href="chantam.php?go=eshya"
>

Əşyalar

</a>

</td>

</tr>

</table>

</div>

</div>

<br/>

<input
type="button"
class="button"
value="Geri"
onclick="window.location.href='dragon.php?go=qalib';"
/>


<?php

/* =========================================================
   BURADA HƏDİYYƏ BLOKU BİTİR
========================================================= */


/* =========================================================
   MƏĞLUBİYYƏT
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'meglub'
) {

?>


<div class="center">

<b style="color:grey;">
Siz Bu Qrupdan Məğlub Ayrılmısınız !
</b>

</div>

<br/>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

Dragon Castle

</a>

</li>

<li>

<a href="menu.php?">

Ana sehife

</a>

</li>

</div>


<?php

/* =========================================================
   SİZ QRUPU TƏRK ETDİNİZ
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'terk_etdin'
) {

?>

<div class="center">

<b style="color:grey;">
Siz qrupu tərk etdiniz !
</b>

</div>

<br/>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

Dragon Castle

</a>

</li>

<li>

<a href="menu.php?">

Ana sehife

</a>

</li>

</div>


<?php

/* =========================================================
   ARTİQ QRUP YARADIB
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'artiq_qrup'
) {

?>

<div class="center">

<b style="color:grey">
Siz Artıq Qrup Yaratmısınız !
</b>

</div>

<br/>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

Dragon Castle

</a>

</li>

<li>

<a href="menu.php?">

Ana sehife

</a>

</li>

</div>


<?php

/* =========================================================
   QRUPDAN ÇIXMA TƏSDİQİ
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'cix'
) {

?>

<p>

Siz qrupu terk etdikde qrupdan meglub olaraq ayrılırsız

<br/>

Siz qrupu terk etmek isteyirsiz?

</p>

<br/>

<form
action="dragon.php?go=cix&amp;lis=5"
method="post">

<input
type="hidden"
name="action"
value="send" />

<input
type="submit"
class="button"
value="He" />

/

<a href="dragon.php?lis=5">

Yox

</a>

<br/>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()" />

</form>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

Dragon Castle

</a>

</li>

<li>

<a href="menu.php?">

Ana sehife

</a>

</li>

</div>


<?php

/* =========================================================
   QRUP MƏLUMATI
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'q'
) {

    $gid =
        isset($_GET['id'])
        ? intval($_GET['id'])
        : 0;


    if (
        $gid == 0 ||
        !isset($_SESSION['dragon_groups'][$gid])
    ) {

?>

<div class="center">

<b>
Qrup tapılmadı.
</b>

</div>

<br/>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

Dragon Castle

</a>

</li>

</div>

<?php

    }
    else {

        $group =
            $_SESSION['dragon_groups'][$gid];
$defeated_here = false;

        $difficulty_text = 'Zeif';


        if (
            isset($group['difficulty']) &&
            intval($group['difficulty']) == 1
        ) {

            $difficulty_text = 'Normal';
        }


        if (
            isset($group['difficulty']) &&
            intval($group['difficulty']) == 2
        ) {

            $difficulty_text = 'Çetin';
        }


        $defeated_here = false;


        if (
            isset($group['defeated']) &&
            is_array($group['defeated'])
        ) {

            if (
                in_array(
                    $_SESSION['username'],
                    $group['defeated'],
                    true
                )
            ) {

                $defeated_here = true;
            }
        }


        if ($defeated_here) {

?>

<div class="center">

<b>
Siz bu qrupdan meglub ayrılmısız !
</b>

</div>

<br/>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

Dragon Castle

</a>

</li>

</div>

<?php

        }
        else {

?>

<b>
Otaqın Adı:
</b>

<?php

echo htmlspecialchars(
    isset($group['name'])
    ? $group['name']
    : 'Dragon Castle',
    ENT_QUOTES,
    'UTF-8'
);

?>

<br/>

<b>
Döyüşçü Tutumu:
</b>

10

<br/>

<b>
Oyunun Gerginliyi:
</b>

<?php

echo $difficulty_text;

?>

<br/>

<b>
Döyüşde Olanlar
</b>

<br/>

<div class="menu">

<?php

if (
    isset($group['players']) &&
    is_array($group['players'])
) {

    foreach (
        $group['players']
        as $player
    ) {

?>

<li>

<a href="#">

<img
src="muxtelif/qrupda.png"
alt="" />

<?php

echo htmlspecialchars(
    $player,
    ENT_QUOTES,
    'UTF-8'
);

?>

</a>

</li>

<?php

    }

}

?>

<hr/>

<li>

<a href="dragon.php?go=daxil_ol&amp;id=<?php echo $gid; ?>&amp;lis=3">

[Qrupa Daxil ol]

</a>

</li>

<hr/>

<br/>

<li>

<a href="dragon.php?go=qruplar&amp;lis=3">

<img
src="muxtelif/on.gif"
alt="" />

Dragon Castle

</a>

</li>

<li>

<a href="menu.php?">

<img
src="muxtelif/home.png"
alt="" />

Ana sehife

</a>

</li>

</div>

<?php

        }

    }
    /* =========================================================
   QRUPA DAXİL OL
========================================================= */

}
elseif (
    isset($_GET['go']) &&
    $_GET['go'] == 'daxil_ol' &&
    isset($_GET['id'])
) {

    $group_id =
        intval($_GET['id']);

    if (
        $group_id == 0 ||
        !isset($_SESSION['dragon_groups'][$group_id])
    ) {

        header(
            "Location: dragon.php?go=qruplar&lis=3"
        );

        exit;
    }

    $group =
        $_SESSION['dragon_groups'][$group_id];

    if (
        !is_array($group)
    ) {

        header(
            "Location: dragon.php?go=qruplar&lis=3"
        );

        exit;
    }


    /* =================================================
       MƏĞLUB OLDUĞUNUZ QRUPDURSA
    ================================================= */

    if (
        isset($group['defeated']) &&
        is_array($group['defeated']) &&
        in_array(
            $_SESSION['username'],
            $group['defeated'],
            true
        )
    ) {

        header(
            "Location: dragon.php?go=meglub&lis=3"
        );

        exit;
    }


    /* =================================================
       QRUP HƏLƏ AKTİVDİRSƏ
    ================================================= */

    if (
        isset($_SESSION['dragon_in_group']) &&
        $_SESSION['dragon_in_group'] === true
    ) {

        $active_group_id =
            intval(
                $_SESSION['dragon_group_id']
            );

        if (
            $active_group_id != 0 &&
            $active_group_id != $group_id &&
            isset($_SESSION['dragon_groups'][$active_group_id]) &&
            isset($_SESSION['dragon_groups'][$active_group_id]['dragon_hp']) &&
            intval($_SESSION['dragon_groups'][$active_group_id]['dragon_hp']) > 0
        ) {

            header(
                "Location: dragon.php?go=artiq_qrup&lis=3"
            );

            exit;
        }
    }


    /* =================================================
       10 NƏFƏR LİMİTİ
    ================================================= */

    if (
        !isset($group['players']) ||
        !is_array($group['players'])
    ) {

        $_SESSION['dragon_groups'][$group_id]['players'] =
            array();
    }

    if (
        !in_array(
            $_SESSION['username'],
            $_SESSION['dragon_groups'][$group_id]['players'],
            true
        )
    ) {

        if (
            count(
                $_SESSION['dragon_groups'][$group_id]['players']
            ) >= 10
        ) {

            header(
                "Location: dragon.php?go=qruplar&lis=3"
            );

            exit;
        }

        $_SESSION['dragon_groups'][$group_id]['players'][] =
            $_SESSION['username'];
    }


    /* =================================================
       AKTİV QRUPU QUR
    ================================================= */

    $_SESSION['dragon_in_group'] = true;

    $_SESSION['dragon_group_id'] = $group_id;


    /* =================================================
       QRUP ZƏRBƏSİ
    ================================================= */

    if (
        isset(
            $_SESSION['dragon_groups'][$group_id]['damage']
        )
    ) {

        $_SESSION['dragon_damage'] =
            intval(
                $_SESSION['dragon_groups'][$group_id]['damage']
            );
    }
    else {

        $_SESSION['dragon_damage'] = 0;
    }


    /* =================================================
       EJDAHA CANI
    ================================================= */

    if (
        isset(
            $_SESSION['dragon_groups'][$group_id]['dragon_hp']
        )
    ) {

        $_SESSION['dragon_hp'] =
            intval(
                $_SESSION['dragon_groups'][$group_id]['dragon_hp']
            );
    }
    else {

        $_SESSION['dragon_hp'] =
            $dragon_max_hp;
    }


    /* =================================================
       OYUNÇU CANI
    ================================================= */

    $_SESSION['dragon_player_hp'] = 2000;


    /* =================================================
       MÖVQE
    ================================================= */

    $_SESSION['dragon_required_position'] =
        rand(0, 3);


    /* =================================================
       EJDAHA SƏHİFƏSİNƏ KEÇ
    ================================================= */

    header(
        "Location: dragon.php?lis=3"
    );

    exit;
}


/* =========================================================
   ƏSAS EJDAHA DÖYÜŞÜ
========================================================= */


else {

    if (
        !$_SESSION['dragon_in_group']
    ) {

?>

<div class="center">

<b>
Siz hazırda heç bir qrupda deyilsiniz.
</b>

</div>

<br/>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

Dragon Castle

</a>

</li>

</div>

<?php

    }
    else {

        $current_group_id =
            intval($_SESSION['dragon_group_id']);


        /* =================================================
           HAZIRDA DAXIL OLDUĞUN QRUPUN CANINI GÖTÜR
        ================================================= */

        if (
            $current_group_id != 0 &&
            isset($_SESSION['dragon_groups'][$current_group_id]) &&
            isset($_SESSION['dragon_groups'][$current_group_id]['dragon_hp'])
        ) {

            $dragon_hp =
                intval(
                    $_SESSION['dragon_groups'][$current_group_id]['dragon_hp']
                );

            $_SESSION['dragon_hp'] =
                $dragon_hp;

        }
        else {

            $dragon_hp =
                $dragon_max_hp;

        }


        /* =================================================
           HAZIRDA DAXIL OLDUĞUN QRUPUN ZƏRBƏSİ
        ================================================= */

        if (
            $current_group_id != 0 &&
            isset($_SESSION['dragon_groups'][$current_group_id]) &&
            isset($_SESSION['dragon_groups'][$current_group_id]['damage'])
        ) {

            $dragon_damage =
                intval(
                    $_SESSION['dragon_groups'][$current_group_id]['damage']
                );

            $_SESSION['dragon_damage'] =
                $dragon_damage;

        }
        else {

            $dragon_damage = 0;

        }


        $player_hp =
            intval(
                $_SESSION['dragon_player_hp']
            );


        if (
            $player_hp <= 0
        ) {
            ?>
<div class="center">

<b>
Siz bu qrupdan meglub ayrılmısız !
</b>

</div>

<br/>

<div class="menu">

<li>

<a href="dragon.php?go=qruplar">

Dragon Castle

</a>

</li>

<li>

<a href="menu.php?">

Ana sehife

</a>

</li>

</div>

<?php

        }
        else {

            $dragon_percent =
                ($dragon_hp /
                $dragon_max_hp) * 100;


            if (
                $dragon_percent < 0
            ) {
                $dragon_percent = 0;
            }


            if (
                $dragon_percent > 100
            ) {
                $dragon_percent = 100;
            }


            $dragon_percent_text =
                number_format(
                    $dragon_percent,
                    2,
                    '.',
                    ''
                );


            $required_position =
                intval(
                    $_SESSION['dragon_required_position']
                );


            if (
                $required_position == 0
            ) {

                $required_text =
                    'Yaxınlaşıb ox-u atın';

            }
            elseif (
                $required_position == 1
            ) {

                $required_text =
                    'Geri çəkilib ox-u atın';

            }
            elseif (
                $required_position == 2
            ) {

                $required_text =
                    'Sağa çəkilib ox-u atın';

            }
            else {

                $required_text =
                    'Sola çəkilib ox-u atın';
            }

?>

<?php

if (
    isset($_GET['cagirildi'])
) {

?>

<div class="success">

<img
src="muxtelif/okey.png"
alt="" />

Çağırış göndərildi

<br/>

</div>

<?php

}

?>


<!-- =====================================================
     ZƏRBƏ VƏ CANLAR
===================================================== -->

<p>

<img
src="muxtelif/udar.png"
alt="zerbe" />

<font style="color:#ff66cc">

Cemi vurduqum Zerbe:

<?php echo $dragon_damage; ?>

</font>

<br/>

<img
src="muxtelif/can.png"
alt="can" />

<font style="color:#FF0000">

Menim canım:

<?php echo $player_hp; ?>

</font>

<br/>

<img
src="muxtelif/can.png"
alt="can" />

<font style="color:#FF0000">

Ejdahanın Canı:

<?php echo $dragon_hp; ?>

</font>

</p>

<br/>

<div class="point-line"></div>

<p>

<small>

<?php echo $required_text; ?>

</small>

</p>

<div class="point-line"></div>

<br/>


<?php



if (
    $dragon_hp <= 0
) {

    $current_gid =
        intval($_SESSION['dragon_group_id']);

    /*
     * YALNIZ HƏQİQƏTƏN QALİB OLDUĞUN QRUPDA
     * QALİB SƏHİFƏSİ AÇILSIN.
     */

    if (
        $current_gid != 0 &&
        isset($_SESSION['dragon_victory_group']) &&
        intval($_SESSION['dragon_victory_group']) == $current_gid &&
        isset($_SESSION['dragon_victory']) &&
        $_SESSION['dragon_victory'] === true
    ) {

        header(
            "Location: dragon.php?go=qalib&lis=3"
        );

        exit;
    }

}
else {

?>
<form
method="post"
action="dragon.php?go=vur&amp;lis=3">

<b>
Mövqey:
</b>

<br/>

<select name="hucum">

<option value="0">
Yaxinlaş
</option>

<option value="1">
Geri çekil
</option>

<option value="2">
saga çekil
</option>

<option value="3">
sola çekil
</option>

</select>

<br/>
<br/>

<input
type="hidden"
name="action"
value="save" />

<input
type="submit"
class="button_small"
value="Ox At" />

</form>

<?php

}

?>

<br/>

<div class="space"></div>


<!-- =====================================================
     EJDAHA CAN FAİZİ
===================================================== -->

<div
style="background:none repeat scroll 0 0 #888686;height:1px;">
</div>

<div class="fl b exp_count">

<div style="margin-top:-2px;">

<font style="color:#ff3333">

<b>

<?php echo $dragon_percent_text; ?>%

</b>

</font>

</div>

</div>

<div class="experience">

<div class="exp_bg">

<div class="exp_left fl"></div>

<div class="exp_right fr"></div>

<div
style="width:<?php echo $dragon_percent; ?>%;height:10px;">

<div class="exp_line2"></div>

<div class="exp_point2"></div>

</div>

</div>

</div>

<div
style="background:none repeat scroll 0 0 #888686;height:1px;">
</div>


<!-- =====================================================
     EJDAHA
===================================================== -->

<img
width="150"
height="84"
src="img/dragon.png"
alt="ejdaha"/>

<br/>

<br/>


<!-- =====================================================
     MENYU
===================================================== -->

<div class="menu">

<li>

<a href="dragon.php?lis=3">

Sehifeni Yenile

</a>

</li>

<li>

<a href="dragon.php?go=melumat&amp;lis=3">

Hediyyeler

</a>

</li>

<li>

<a href="dragon.php?go=cagir&amp;lis=3">

Döyüşçü çağır

</a>

</li>

</div>

<hr/>


<!-- =====================================================
     QRUPDA OLANLAR
===================================================== -->

<div class="center">

<div class="block_line">

<b>
Qrupda Olanlar
</b>

</div>

</div>

<br/>

<div class="menu">

<?php

$active_gid =
    intval(
        $_SESSION['dragon_group_id']
    );


if (
    $active_gid != 0 &&
    isset($_SESSION['dragon_groups'][$active_gid])
) {

    if (
        !isset(
            $_SESSION['dragon_groups'][$active_gid]['players']
        ) ||
        !is_array(
            $_SESSION['dragon_groups'][$active_gid]['players']
        )
    ) {

        $_SESSION['dragon_groups'][$active_gid]['players'] =
            array(
                $_SESSION['username']
            );
    }


    foreach (
        $_SESSION['dragon_groups'][$active_gid]['players']
        as $player
    ) {

        if (
            $player ==
            $_SESSION['username']
        ) {

            $player_damage =
                $dragon_damage;

        }
        else {

            $player_damage = 0;
        }

?>

<li>

<a href="#">

<img
src="klan_img/"
alt="" />

<?php

echo htmlspecialchars(
    $player,
    ENT_QUOTES,
    'UTF-8'
);

?>

[14]

(<?php echo $player_damage; ?>)

</a>

</li>

<?php

    }

}
else {

?>

<li>

<a
href="infoforce.php?uid=1000749">

<img
src="klan_img/"
alt="" />

<?php

echo htmlspecialchars(
    $_SESSION['username'],
    ENT_QUOTES,
    'UTF-8'
);

?>

[14]

(<?php echo $dragon_damage; ?>)

</a>

</li>

<?php

}


if (
    $_SESSION['dragon_fighter'] == true
) {

?>

<li>

<a
href="infoforce.php?uid=1000506">

<img
src="klan_img/"
alt="" />

YuRi_BoYKa [14] (0)

</a>

</li>

<?php

}

?>

</div>

<br/>
<br/>


<!-- =====================================================
     MESAJ FORMU
===================================================== -->

<form
method="post"
action="dragon.php?go=yaz&amp;lis=3">

<input
name="message"
value=""
maxlength="300" />

<br/>

<input
type="submit"
class="button"
value="Gönder" />

</form>

<hr/>


<!-- =====================================================
     MESAJLAR
===================================================== -->

<?php

if (
    !empty($_SESSION['dragon_messages'])
) {

    $messages =
        array_reverse(
            $_SESSION['dragon_messages']
        );


    foreach (
        $messages
        as $msg
    ) {

        $msg_user =
            isset($msg['username'])
            ? htmlspecialchars(
                $msg['username'],
                ENT_QUOTES,
                'UTF-8'
            )
            : '***YALQUZAQ***';


        $msg_text =
            isset($msg['message'])
            ? htmlspecialchars(
                $msg['message'],
                ENT_QUOTES,
                'UTF-8'
            )
            : '';

?>

<a
href="infoforce.php?uid=1000749">

<u>

<font color="green">

<?php echo $msg_user; ?>

</font>

</u>

</a>

&#187;

<small>

<?php echo $msg_text; ?>

</small>

<br/>

<?php

    }

}

?>


<!-- =====================================================
     QRUPDAN ÇIX
===================================================== -->

<div class="menu">

<li>

<a
href="dragon.php?go=cix&amp;lis=5">

Qrupu terk et

</a>

</li>

</div>

<br/>

<?php

        }
    }
}

?>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">

<?php

/* =====================================================
   YALNIZ EJDAHA DÖYÜŞ SƏHİFƏSİ
===================================================== */

$dragon_battle_page = (
    basename($_SERVER['PHP_SELF']) == 'dragon.php' &&
    !isset($_GET['go']) &&
    isset($_SESSION['dragon_in_group']) &&
    $_SESSION['dragon_in_group'] == true
);

?>


[

<b>

<?php if ($dragon_battle_page) { ?>

<a href="dragon.php?go=cix&amp;lis=5">

Menu

</a>

<?php } else { ?>

<a href="menu.php?">

Menu

</a>

<?php } ?>

</b>

]


[

<b>

<?php if ($dragon_battle_page) { ?>

<a href="dragon.php?go=cix&amp;lis=5">

Axtarış

</a>

<?php } else { ?>

<a href="axtar.php?">

Axtarış

</a>

<?php } ?>

</b>

]


[

<?php if ($dragon_battle_page) { ?>

<a href="dragon.php?go=cix&amp;lis=5">

Forum

</a>

<?php } else { ?>

<a href="forum/mozu2.php?">

Forum

</a>

<?php } ?>

]


[

<?php if ($dragon_battle_page) { ?>

<a href="dragon.php?go=cix&amp;lis=5">

Qurğular

</a>

<?php } else { ?>

<a href="shexsi_sehife.php?">

Qurğular

</a>

<?php } ?>

]


<br/>
<br/>

<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>

<br/>

<a href="cixis.php?">

Çıxış
(<?php

echo htmlspecialchars(
    $_SESSION['username'],
    ENT_QUOTES,
    'UTF-8'
);

?>)

</a>

</a>

<br/>
<br/>

<a href="menu.php?dil=tr">

Türkce:

<img
alt="türkce"
src="http://macera.az/klan/muxtelif/tr.gif"
title="Türkce" />

</a>

<br/>

Sciript name:
Qanlı efsane(modern version)

<br/>

<a
href="http://klanaz.com/klan/"
class="xgame.az">

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