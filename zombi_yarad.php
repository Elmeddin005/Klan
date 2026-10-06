<?php

session_start();
require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$my_id = (int)$_SESSION['user_id'];
$unread_count = 0;
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
$my_id = (int)$_SESSION['user_id'];


if (
    isset($_GET['temizle_zombie']) &&
    $_GET['temizle_zombie'] === '1'
) {

    $_SESSION['zombie_groups'] = array();
    $_SESSION['zombie_messages'] = array();
    $_SESSION['zombie_battle'] = array();
    $_SESSION['zombie_battle_messages'] = array();

    unset($_SESSION['zombie_opened_boxes']);
    unset($_SESSION['zombie_box_rewards']);

    echo 'Zombie qrupları təmizləndi: ' .
        count($_SESSION['zombie_groups']);

    exit;
}
/*
==========================================================
 ZOMBIE CASTLE
 zombi_yarad.php
==========================================================
*/


/* ==========================================================
   ƏSAS DƏYİŞƏNLƏR
   ========================================================== */

$go = isset($_GET['go']) ? $_GET['go'] : '';

$group_number = isset($_GET['qrup_num'])
    ? (int)$_GET['qrup_num']
    : 0;
$return_page = isset($_GET['return'])
    ? (string)$_GET['return']
    : '';

/* ==========================================================
   MESAJLAR
   ========================================================== */

$meglub_message = false;
$meglub_score = 0;

$group_exit_message = false;
$join_failed_message = false;
$group_cancelled_message = false;
$start_message = '';

$logo_exit_warning = false;


/* ==========================================================
   MƏĞLUBİYYƏT
   ========================================================== */

if ($go === 'meglub') {

    $meglub_message = true;

    $meglub_score = isset($_GET['xal'])
        ? (int)$_GET['xal']
        : 0;
}


/* ==========================================================
   QRUP SİSTEMİ
   ========================================================== */

if (
    !isset($_SESSION['zombie_groups']) ||
    !is_array($_SESSION['zombie_groups'])
) {
    $_SESSION['zombie_groups'] = array();
}

if (
    !isset($_SESSION['zombie_messages']) ||
    !is_array($_SESSION['zombie_messages'])
) {
    $_SESSION['zombie_messages'] = array();
}

if (
    !isset($_SESSION['zombie_battle']) ||
    !is_array($_SESSION['zombie_battle'])
) {
    $_SESSION['zombie_battle'] = array();
}

if (
    !isset($_SESSION['zombie_battle_messages']) ||
    !is_array($_SESSION['zombie_battle_messages'])
) {
    $_SESSION['zombie_battle_messages'] = array();
}


/* ==========================================================
   OYUNCU
   ========================================================== */

$player_id = isset($_SESSION['player_id'])
    ? (int)$_SESSION['player_id']
    : 1000749;

$player_name = '***YALQUZAQ***';

/* ==========================================================
   OYUNÇUNUN AKTİV QRUPUNU TAP
   ========================================================== */

$has_active_group = false;
$my_group_number = 0;

/*
 * SESSION-da zombie_groups mütləq mövcud olsun
 */
if (
    !isset($_SESSION['zombie_groups']) ||
    !is_array($_SESSION['zombie_groups'])
) {
    $_SESSION['zombie_groups'] = array();
}

/*
 * Bütün qrupları yoxla
 */
foreach (
    $_SESSION['zombie_groups']
    as $check_gid => $check_group
) {

    $check_gid = (int)$check_gid;

    /*
     * Qrup düzgün massiv deyilsə keç
     */
    if (!is_array($check_group)) {
        continue;
    }

    /*
     * Ləğv olunmuş qrup aktiv deyil
     */
    if (
        isset($check_group['cancelled']) &&
        $check_group['cancelled'] === true
    ) {
        continue;
    }

    /*
     * Döyüşə başlamış qrup artıq aktiv siyahıda deyil
     */
    if (
        isset($check_group['started']) &&
        $check_group['started'] === true
    ) {
        continue;
    }

    /*
     * Üzvlər yoxdursa keç
     */
    if (
        !isset($check_group['members']) ||
        !is_array($check_group['members'])
    ) {
        continue;
    }

    /*
     * Oyunçunu axtar
     */
    foreach (
        $check_group['members']
        as $check_member
    ) {

        if (
            isset($check_member['id']) &&
            (int)$check_member['id'] ===
            (int)$player_id
        ) {

            $has_active_group = true;
            $my_group_number = $check_gid;

            break 2;
        }
    }
}

/* ==========================================================
   VAXT
   ========================================================== */

$group_timeout = 180;

/* Ləğv olunmuş qrup neçə saniyəyə silinsin */
$cancel_delete_time = 5;

/* Boş qrup neçə saniyəyə silinsin */
$empty_group_delete_timeout = 15;

/* Döyüş başlayan qrup avtomatik silinmir */
$battle_delete_timeout = 0;


/* ==========================================================
   QRUP SİL
   ========================================================== */

function zombie_delete_group($gid)
{
    $gid = (int)$gid;

    if (isset($_SESSION['zombie_groups'][$gid])) {
        unset($_SESSION['zombie_groups'][$gid]);
    }

    if (isset($_SESSION['zombie_messages'][$gid])) {
        unset($_SESSION['zombie_messages'][$gid]);
    }

    if (isset($_SESSION['zombie_battle'][$gid])) {
        unset($_SESSION['zombie_battle'][$gid]);
    }

    if (isset($_SESSION['zombie_battle_messages'][$gid])) {
        unset($_SESSION['zombie_battle_messages'][$gid]);
    }
}


/* ==========================================================
   KÖHNƏ QRUPLARI TƏMİZLƏ
   ========================================================== */

foreach ($_SESSION['zombie_groups'] as $gid => $group) {

    $gid = (int)$gid;

    if (!isset($_SESSION['zombie_groups'][$gid])) {
        continue;
    }


    /* ======================================================
       DÖYÜŞ BAŞLAYIBSA
       ====================================================== */

    if (
        isset($group['started']) &&
        $group['started'] === true
    ) {

        if (
            $battle_delete_timeout > 0 &&
            isset($group['battle_delete_time']) &&
            (int)$group['battle_delete_time'] > 0 &&
            time() >= (int)$group['battle_delete_time']
        ) {

            zombie_delete_group($gid);
        }

        continue;
    }


    /* ======================================================
       LƏĞV OLUNMUŞ QRUP
       ====================================================== */

    if (
        isset($group['cancelled']) &&
        $group['cancelled'] === true
    ) {

        if (
            isset($group['cancelled_time']) &&
            time() - (int)$group['cancelled_time']
            >= $cancel_delete_time
        ) {

            zombie_delete_group($gid);
        }

        continue;
    }


    /* ======================================================
       QRUP BOŞDURSA
       ====================================================== */

    $member_count =
        isset($group['members']) &&
        is_array($group['members'])
            ? count($group['members'])
            : 0;


    if ($member_count === 0) {

        if (
            !isset($group['empty_group_time']) ||
            (int)$group['empty_group_time'] <= 0
        ) {

            $_SESSION['zombie_groups']
            [$gid]['empty_group_time'] = time();

        } elseif (
            time() -
            (int)$group['empty_group_time']
            >= $empty_group_delete_timeout
        ) {

            zombie_delete_group($gid);
        }

        continue;
    }


    /* ======================================================
       1 NƏFƏRLİ QRUPUN TIMERİ
       ====================================================== */

    if ($member_count <= 1) {

        if (
            !isset($group['last_single_member_time']) ||
            (int)$group['last_single_member_time'] <= 0
        ) {

            $_SESSION['zombie_groups']
            [$gid]['last_single_member_time'] =
                isset($group['created'])
                    ? (int)$group['created']
                    : time();
        }

    } else {

        $_SESSION['zombie_groups']
        [$gid]['last_single_member_time'] = 0;
    }
}


/* ==========================================================
   AKTİV QRUP
   ========================================================== */

$current_group = null;

if (
    $group_number > 0 &&
    isset($_SESSION['zombie_groups'][$group_number])
) {

    $current_group =
        $_SESSION['zombie_groups'][$group_number];
}

/* ==========================================================
   DÖYÜŞÇÜ ÇAĞIR
   ========================================================== */

if (
    $go === 'cagir' &&
    $current_group !== null
) {

    $cagir_players = array();

    /*
     * Digər qruplardakı oyunçuları tapırıq.
     * Özümüzü və hazırkı qrupdakı oyunçuları göstərmirik.
     */

    foreach ($_SESSION['zombie_groups'] as $gid => $group) {

        if ((int)$gid === (int)$group_number) {
            continue;
        }

        if (
            !isset($group['members']) ||
            !is_array($group['members'])
        ) {
            continue;
        }

        foreach ($group['members'] as $member) {

            if (
                !isset($member['id']) ||
                (int)$member['id'] === $player_id
            ) {
                continue;
            }

            /*
             * Hazırkı qrupda artıq olan adamı göstərmə
             */
            $already_in_group = false;

            foreach ($current_group['members'] as $cmember) {

                if (
                    isset($cmember['id']) &&
                    (int)$cmember['id'] === (int)$member['id']
                ) {
                    $already_in_group = true;
                    break;
                }
            }

            if ($already_in_group) {
                continue;
            }

            /*
             * Eyni adam ikinci dəfə çıxmasın
             */
            $exists = false;

            foreach ($cagir_players as $cp) {

                if (
                    (int)$cp['id'] === (int)$member['id']
                ) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                continue;
            }

            $cagir_players[] = array(
                'id' => (int)$member['id'],
                'name' => isset($member['name'])
                    ? $member['name']
                    : 'Oyunçu'
            );
        }
    }
}


/* ==========================================================
   QRUP YARAT
   ========================================================== */

if (
    $go === 'ok' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {

    /* ======================================================
       OYUNÇUNUN ARTIQ QRUPDA OLUB-OLMADIĞINI YOXLAYIRIQ
       ====================================================== */

    $already_group = false;

    foreach (
        $_SESSION['zombie_groups']
        as $check_gid => $check_group
    ) {

        /* Ləğv olunmuş qrup aktiv sayılmır */

        if (
            isset($check_group['cancelled']) &&
            $check_group['cancelled'] === true
        ) {
            continue;
        }


        /* Döyüş başlamış qrup aktiv sayılmır */

        if (
            isset($check_group['started']) &&
            $check_group['started'] === true
        ) {
            continue;
        }


        if (
            !isset($check_group['members']) ||
            !is_array($check_group['members'])
        ) {
            continue;
        }


        foreach (
            $check_group['members']
            as $check_member
        ) {

            if (
                isset($check_member['id']) &&
                (int)$check_member['id'] ===
                (int)$player_id
            ) {

                $already_group = true;

                break 2;
            }
        }
    }


    /* ======================================================
       ARTIQ QRUP VARSA
       ====================================================== */

    if ($already_group) {

        header(
            'Location: zombi_yarad.php?go=neww&already=1'
        );

        exit;
    }


    /* ======================================================
       QRUPUN ADI
       ====================================================== */

    $group_name = isset($_POST['alish_min'])
        ? trim($_POST['alish_min'])
        : '';


    if ($group_name === '') {

        $group_name = 'Zombie Qrup';
    }


    /* ======================================================
       QRUP TUTUMU
       ====================================================== */

    $capacity = isset($_POST['aa'])
        ? (int)$_POST['aa']
        : 2;


    if (
        $capacity !== 2 &&
        $capacity !== 3 &&
        $capacity !== 4 &&
        $capacity !== 6
    ) {

        $capacity = 2;
    }


    /* ======================================================
       YENİ QRUP NÖMRƏSİ
       ====================================================== */

    $group_number = 1;


    if (
        !empty($_SESSION['zombie_groups'])
    ) {

        $group_numbers =
            array_keys(
                $_SESSION['zombie_groups']
            );


        $group_numbers =
            array_map(
                'intval',
                $group_numbers
            );


        $group_number =
            max($group_numbers) + 1;
    }


    /* ======================================================
       QRUPU YARAT
       ====================================================== */

    $_SESSION['zombie_groups'][$group_number] =
        array(

            'id' => $group_number,

            'name' => $group_name,

            'capacity' => $capacity,

            'created' => time(),

            'empty_group_time' => 0,

            'started' => false,

            'cancelled' => false,

            'last_single_member_time' => time(),

            'cancelled_time' => 0,

            'battle_delete_time' => 0,

            'left_players' => array(),

            'members' => array(

                array(

                    'id' => $player_id,

                    'name' => $player_name,

                    'owner' => true

                )

            )

        );


    /* ======================================================
       QRUP MESAJLARI
       ====================================================== */

    $_SESSION['zombie_messages'][$group_number] =
        array();


    /* ======================================================
       KÖHNƏ MESAJLARI TƏMİZLƏ
       ====================================================== */

    unset(
        $_SESSION['zombie_exit_group']
    );

    unset(
        $_SESSION['zombie_join_failed_group']
    );

    unset(
        $_SESSION['zombie_group_cancelled_message']
    );

    unset(
        $_SESSION['zombie_call_success']
    );


    /* ======================================================
       QRUP YARADILDI
       QRUP SƏHİFƏSİNƏ KEÇ
       ====================================================== */

    header(
        'Location: zombi_yarad.php?go=q&qrup_num=' .
        $group_number
    );

    exit;
}

/* ==========================================================
   QRUPDAN ÇIX
   ========================================================== */

if (
    $go === 'cix' &&
    isset($_GET['ok']) &&
    $_GET['ok'] === 'cix' &&
    $current_group !== null
) {

    $members =
        isset(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        ) &&
        is_array(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        )
            ? $_SESSION['zombie_groups']
              [$group_number]['members']
            : array();


    $new_members = array();


    foreach ($members as $member) {

        if (
            (int)$member['id'] !== $player_id
        ) {

            $new_members[] = $member;
        }
    }


    $_SESSION['zombie_groups']
    [$group_number]['members'] =
        $new_members;


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
            [$group_number]['left_players']
        )
    ) {

        $_SESSION['zombie_groups']
        [$group_number]['left_players'][] =
            $player_id;
    }


    /* ======================================================
       BOŞ QRUP TIMERİ
       ====================================================== */

    if (
        count(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        ) === 0
    ) {

        $_SESSION['zombie_groups']
        [$group_number]['empty_group_time'] =
            time();

    } else {

        $_SESSION['zombie_groups']
        [$group_number]['empty_group_time'] =
            0;
    }


    /* ======================================================
       OWNER KONTROLU
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


    /* ======================================================
       OWNER ÇIXIBSA YENİ OWNER
       ====================================================== */

    if (
        !$owner_exists &&
        count(
            $_SESSION['zombie_groups']
            [$group_number]['members']
        ) > 0
    ) {

        foreach (
            $_SESSION['zombie_groups']
            [$group_number]['members']
            as $index => $member
        ) {

            $_SESSION['zombie_groups']
            [$group_number]['members']
            [$index]['owner'] = true;

            break;
        }
    }


    $_SESSION['zombie_exit_group'] =
        $group_number;


    header(
        'Location: zombi_yarad.php?go=exit_done&qrup_num=' .
        $group_number
    );

    exit;
}


/* ==========================================================
   ÇIXIŞ SƏHİFƏSİ
   ========================================================== */

if (
    $go === 'exit_done' &&
    $group_number > 0 &&
    isset($_SESSION['zombie_exit_group']) &&
    (int)$_SESSION['zombie_exit_group'] ===
        $group_number
) {

    $group_exit_message = true;

    unset($_SESSION['zombie_exit_group']);
}


/* ==========================================================
   QRUPA DAXİL OL
   ========================================================== */
/* ==========================================================
   DÖYÜŞÇÜ ÇAĞIRIŞI
   ========================================================== */

if (
    $go === 'cagir_ok' &&
    $current_group !== null
) {

    $cagir_uid = isset($_GET['uid'])
        ? (int)$_GET['uid']
        : 0;

    if ($cagir_uid > 0) {

        /*
         * Çağırış mesajını qrup üçün saxlayırıq.
         */
        if (
            !isset($_SESSION['zombie_call_requests'])
        ) {
            $_SESSION['zombie_call_requests'] = array();
        }

        if (
            !isset(
                $_SESSION['zombie_call_requests']
                [$group_number]
            )
        ) {
            $_SESSION['zombie_call_requests']
                [$group_number] = array();
        }

        $_SESSION['zombie_call_requests']
            [$group_number][$cagir_uid] = true;
            $_SESSION['zombie_call_success'] = $group_number;
    }

    /*
     * Çağırışdan sonra qrup səhifəsinə qayıt.
     */
    header(
        'Location: zombi_yarad.php?go=q&qrup_num'
    );

    exit;
}

if (
    $go === 'komek_ok' &&
    $current_group !== null
) {

    $player_left_group = false;


    /* ======================================================
       BU QRUPDAN ƏVVƏL ÇIXIBMI?
       ====================================================== */

    if (
        isset($current_group['left_players']) &&
        is_array($current_group['left_players']) &&
        in_array(
            $player_id,
            $current_group['left_players']
        )
    ) {

        $player_left_group = true;
    }


    if ($player_left_group) {

        $_SESSION['zombie_join_failed_group'] =
            $group_number;

        header(
            'Location: zombi_yarad.php?go=grup&qrup_num=' .
            $group_number
        );

        exit;
    }


    /* ======================================================
       DÖYÜŞ BAŞLAYIBSA
       ====================================================== */

    if (
        isset($current_group['started']) &&
        $current_group['started'] === true
    ) {

        header(
            'Location: zombi_yarad.php?go=qruplar'
        );

        exit;
    }


    /* ======================================================
       QRUP LƏĞV OLUNUBSA
       ====================================================== */

    if (
        isset($current_group['cancelled']) &&
        $current_group['cancelled'] === true
    ) {

        $_SESSION['zombie_group_cancelled_message'] =
            $group_number;

        header(
            'Location: zombi_yarad.php?go=grup&qrup_num=' .
            $group_number
        );

        exit;
    }


    $member_exists = false;


    $members =
        isset($current_group['members']) &&
        is_array($current_group['members'])
            ? $current_group['members']
            : array();


    foreach ($members as $member) {

        if (
            (int)$member['id'] === $player_id
        ) {

            $member_exists = true;
            break;
        }
    }


    $member_count = count($members);


    $is_full =
        $member_count >=
        (int)$current_group['capacity'];


    /* ======================================================
       QRUP DOLUDUR
       ====================================================== */

    if ($is_full && !$member_exists) {

        header(
            'Location: zombi_yarad.php?go=grup&qrup_num=' .
            $group_number
        );

        exit;
    }


    /* ======================================================
       OYUNÇUNU QRUPA ƏLAVƏ ET
       ====================================================== */

    if (
        !$member_exists &&
        $member_count <
        (int)$current_group['capacity']
    ) {

        $_SESSION['zombie_groups']
        [$group_number]['members'][] =
            array(
                'id' => $player_id,
                'name' => $player_name,
                'owner' => false
            );


        /* 1 nəfərlik timeri söndür */

        if (
            count(
                $_SESSION['zombie_groups']
                [$group_number]['members']
            ) > 1
        ) {

            $_SESSION['zombie_groups']
            [$group_number]
            ['last_single_member_time'] = 0;
        }


        /* Boş qrup timerini sıfırla */

        $_SESSION['zombie_groups']
        [$group_number]
        ['empty_group_time'] = 0;
    }


    header(
        'Location: zombi_yarad.php?go=q&qrup_num=' .
        $group_number
    );

    exit;
}


/* ==========================================================
   QRUP VAXT KONTROLU
   ========================================================== */

if ($current_group !== null) {

    /* ======================================================
       DÖYÜŞ BAŞLAYIBSA
       ====================================================== */

    if (
        isset($current_group['started']) &&
        $current_group['started'] === true
    ) {

        if (
            $battle_delete_timeout > 0 &&
            (
                !isset($current_group['battle_delete_time']) ||
                (int)$current_group['battle_delete_time'] <= 0
            )
        ) {

            $_SESSION['zombie_groups']
            [$group_number]
            ['battle_delete_time'] =
                time() + $battle_delete_timeout;
        }


    } else {

        /* ==================================================
           DÖYÜŞ BAŞLAMAYIBSA
           ================================================== */

        if (
            !isset($current_group['cancelled']) ||
            $current_group['cancelled'] !== true
        ) {

            $member_count =
                isset($current_group['members']) &&
                is_array($current_group['members'])
                    ? count($current_group['members'])
                    : 0;


            /* 1 və ya 0 nəfərlik qrup */

            if ($member_count <= 1) {

                if (
                    !isset(
                        $current_group
                        ['last_single_member_time']
                    ) ||
                    (int)
                    $current_group
                    ['last_single_member_time'] <= 0
                ) {

                    $_SESSION['zombie_groups']
                    [$group_number]
                    ['last_single_member_time'] =
                        isset($current_group['created'])
                            ? (int)$current_group['created']
                            : time();
                }


                $single_time =
                    isset(
                        $_SESSION['zombie_groups']
                        [$group_number]
                        ['last_single_member_time']
                    )
                        ? (int)
                        $_SESSION['zombie_groups']
                        [$group_number]
                        ['last_single_member_time']
                        : time();


                $passed_time =
                    time() - $single_time;


                /* 180 saniyə tamam oldu */

                if (
                    $passed_time >= $group_timeout
                ) {

                    $_SESSION['zombie_groups']
                    [$group_number]['cancelled'] =
                        true;

                    $_SESSION['zombie_groups']
                    [$group_number]['cancelled_time'] =
                        time();

                    $_SESSION['zombie_group_cancelled_message'] =
                        $group_number;


                    $current_group =
                        $_SESSION['zombie_groups']
                        [$group_number];

                    $group_cancelled_message =
                        true;
                }
            }
        }
    }
}


/* ==========================================================
   LƏĞV OLUNMUŞ QRUP
   ========================================================== */

if (
    $current_group !== null &&
    isset($current_group['cancelled']) &&
    $current_group['cancelled'] === true
) {

    $group_cancelled_message = true;
}


/* ==========================================================
   JOIN ERROR MESAJI
   ========================================================== */

if (
    isset($_SESSION['zombie_join_failed_group']) &&
    (int)$_SESSION['zombie_join_failed_group'] ===
        $group_number
) {

    $join_failed_message = true;

    unset(
        $_SESSION['zombie_join_failed_group']
    );
}


/* ==========================================================
   CANCEL MESAJI
   ========================================================== */

if (
    isset($_SESSION['zombie_group_cancelled_message']) &&
    (int)$_SESSION['zombie_group_cancelled_message'] ===
        $group_number
) {

    $group_cancelled_message = true;

    unset(
        $_SESSION['zombie_group_cancelled_message']
    );
}


/* ==========================================================
   LOGO ÇIXIŞ XƏBƏRDARLIĞI
   ========================================================== */

if (
    $current_group !== null &&
    isset($current_group['started']) &&
    $current_group['started'] === false &&
    !$group_exit_message &&
    !$join_failed_message &&
    !$group_cancelled_message
) {

    $logo_exit_warning = true;
}


/* ==========================================================
   QRUP ÇATI
   ========================================================== */

if (
    $go === 'yaz' &&
    $current_group !== null &&
    isset($_POST['message'])
) {

    $message = trim($_POST['message']);


    if ($message !== '') {

        if (
            !isset(
                $_SESSION['zombie_messages']
                [$group_number]
            ) ||
            !is_array(
                $_SESSION['zombie_messages']
                [$group_number]
            )
        ) {

            $_SESSION['zombie_messages']
            [$group_number] =
                array();
        }


        $_SESSION['zombie_messages']
        [$group_number][] =
            array(
                'id' => $player_id,
                'name' => $player_name,
                'message' => $message,
                'time' => time()
            );
    }


    header(
        'Location: zombi_yarad.php?go=q&qrup_num=' .
        $group_number
    );

    exit;
}


/* ==========================================================
   OYUNA BAŞLA
   ========================================================== */

if (
    $go === 'bashla' &&
    $group_number > 0 &&
    isset($_SESSION['zombie_groups'][$group_number])
) {

    $current_group =
        $_SESSION['zombie_groups'][$group_number];


    /* ======================================================
       LƏĞV OLUNUBSA
       ====================================================== */

    if (
        isset($current_group['cancelled']) &&
        $current_group['cancelled'] === true
    ) {

        header(
            'Location: zombi_yarad.php?go=grup&qrup_num=' .
            $group_number
        );

        exit;
    }


    /* ======================================================
       ARTIQ BAŞLAYIBSA
       ====================================================== */

    if (
        isset($current_group['started']) &&
        $current_group['started'] === true
    ) {

        header(
            'Location: kordinat3.php?go=deyis&qrup_num=' .
            $group_number
        );

        exit;
    }


    $member_count =
        isset($current_group['members']) &&
        is_array($current_group['members'])
            ? count($current_group['members'])
            : 0;


    /* ======================================================
       DÖYÜŞƏ BAŞLA
       ====================================================== */

    if ($member_count >= 1) {

        $_SESSION['zombie_groups']
        [$group_number]
        ['started'] = true;


        $_SESSION['zombie_groups']
        [$group_number]
        ['battle_delete_time'] = 0;


        /* ==================================================
           DÖYÜŞ SİSTEMİ
           ================================================== */

        $_SESSION['zombie_battle']
        [$group_number] =
            array(

                'stage' => 1,

                'zombie_count' => 5,

                'score' => 0,

                'last_attack' => time(),

                'finished' => false
            );


        $_SESSION['zombie_battle_messages']
        [$group_number] =
            array();


        header(
            'Location: kordinat3.php?go=deyis&qrup_num=' .
            $group_number
        );

        exit;

    } else {

        $start_message =
            'Döyüşə başlamaq üçün kömək gəlməlidir (Döyüşçü çağırın)';
    }
}


/* ==========================================================
   SON TƏMİZLƏMƏ
   ========================================================== */

foreach ($_SESSION['zombie_groups'] as $gid => $group) {

    $gid = (int)$gid;

    if (!isset($_SESSION['zombie_groups'][$gid])) {
        continue;
    }


    /* ======================================================
       LƏĞV OLUNMUŞ QRUP
       ====================================================== */

    if (
        isset($group['cancelled']) &&
        $group['cancelled'] === true
    ) {

        if (
            isset($group['cancelled_time']) &&
            time() -
            (int)$group['cancelled_time']
            >= $cancel_delete_time
        ) {

            zombie_delete_group($gid);
        }

        continue;
    }


    /* ======================================================
       DÖYÜŞ BAŞLAYIBSA SİLMƏ
       ====================================================== */

    if (
        isset($group['started']) &&
        $group['started'] === true
    ) {

        continue;
    }


    $member_count =
        isset($group['members']) &&
        is_array($group['members'])
            ? count($group['members'])
            : 0;


    /* ======================================================
       TAM BOŞ QRUP
       ====================================================== */

    if ($member_count === 0) {

        if (
            !isset($group['empty_group_time']) ||
            (int)$group['empty_group_time'] <= 0
        ) {

            $_SESSION['zombie_groups']
            [$gid]['empty_group_time'] =
                time();

        } elseif (
            time() -
            (int)$group['empty_group_time']
            >= $empty_group_delete_timeout
        ) {

            zombie_delete_group($gid);

            continue;
        }

    } else {

        $_SESSION['zombie_groups']
        [$gid]['empty_group_time'] = 0;
    }
}


/* ==========================================================
   CURRENT GROUP YENİLƏ
   ========================================================== */

$current_group = null;

if (
    $group_number > 0 &&
    isset($_SESSION['zombie_groups'][$group_number])
) {

    $current_group =
        $_SESSION['zombie_groups'][$group_number];
}

?>
<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL">

<meta
name="keywords"
content="klan.az, azgame, azgame.biz, online oyun, qrup döyüşləri, klan döyüşləri, qorxulu qalalar"
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
content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"
>

<title>zombiler</title>

<style>

.zombie-group-link {
    color: #850E0E !important;
    font-weight: bold !important;
    text-decoration: none !important;
}

.zombie-group-link:hover {
    color: #850E0E !important;
    text-decoration: none !important;
}

.zombie-active-groups {
    margin-top: 0;
}

.zombie-group-item {
    margin: 0;
    padding: 0;
}

.zombie-message {
    margin-bottom: 6px;
}

.zombie-exit-warning {
    text-align: center;
    line-height: 1.6;
}

.zombie-exit-warning a {
    color: #850E0E;
    font-weight: bold;
    text-decoration: none;
}

.zombie-exit-warning a:hover {
    color: #850E0E;
    text-decoration: none;
}

.zombie-exit-success {
    text-align: left;
    font-weight: bold;
    color: #000000;
    line-height: 1.6;
}

.zombie-defeat {
    text-align: left;
    font-weight: bold;
    color: #000000;
    line-height: 1.6;
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

<div id="header">

<?php if ($go === 'meglub') { ?>

<a
href="zombi_yarad.php?go=meglub_logo_confirm&amp;qrup_num=<?php
echo (int)$group_number;
?>&amp;xal=<?php
echo (int)$meglub_score;
?>"
>
<img
src="img/logo.png"
alt=""
>

</a>

<?php } elseif ($join_failed_message) { ?>

<a href="menu.php?">

<img
src="img/logo.png"
alt=""
>

</a>

<?php } elseif (
    $go === 'neww' &&
    $has_active_group
) { ?>

<a
href="zombi_yarad.php?go=logo_cix&amp;qrup_num=<?php
echo (int)$my_group_number;
?>"
>

<img
src="img/logo.png"
alt=""
>

</a>

<?php } elseif ($logo_exit_warning) { ?>

<a
href="zombi_yarad.php?go=logo_cix&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

<img
src="img/logo.png"
alt=""
>

</a>

<?php } else { ?>

<a href="menu.php?">

<img
src="img/logo.png"
alt=""
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
<?php if ($unread_count > 0) { ?>
    <a href="arxiv.php?go=goster">
        <img src="img/mektub.gif" title="Yeni mesaj" alt="Mesaj"/>
    </a>
    (<?php echo $unread_count; ?>)
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

<?php if (
    $go === 'meglub_logo_confirm' &&
    $group_number > 0
) { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

<div class="zombie-exit-warning">

Siz digər səhifələrə keçmək üçün qrupu tərk etməlisiniz!

<br>

Qrupu tərk etmək istəyirsiz?

<br><br>

<a
href="zombi_yarad.php?go=cix&amp;ok=cix&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>
Hə
</a>

&nbsp; | &nbsp;

<a
href="kordinat3.php?go=deyis&amp;qrup_num=<?php
echo (int)$group_number;
?>&amp;battle_return=1"
>
Yox
</a>

</div>

</div>

<?php exit; ?>

<?php } ?>
<!-- ======================================================
     DÖYÜŞ NƏTİCƏSİ
     ====================================================== -->

<?php if ($meglub_message) { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>
<br>


<?php
/*
==========================================================
 NƏTİCƏ MƏTNİ
==========================================================
*/

if ($meglub_score >= 70000) {

    $result_text = 'Siz Qalib Geldiniz!';

} else {

    $result_text = 'Siz Meglub oldunuz !';

}
?>


<div class="center">

<b>

<?php echo $result_text; ?>

(Xal:<?php echo (int)$meglub_score; ?>)

</b>

</div>

<br>


<?php
/*
==========================================================
 QUTU SİSTEMİ
==========================================================

0 - 2999
Qutu yoxdur

3000 - 4999
1-ci Qutu

5000 - 9999
2-ci Qutu

10000 - 49999
3-cü Qutu

50000 - 69999
4-cü Qutu

70000
5-ci Qutu
==========================================================
*/


$box_number = 0;
$box_name = '';


if ($meglub_score >= 70000) {

    $box_number = 5;
    $box_name = 'Beşinci Qutu';

} elseif ($meglub_score >= 50000) {

    $box_number = 4;
    $box_name = 'Dördüncü Qutu';

} elseif ($meglub_score >= 10000) {

    $box_number = 3;
    $box_name = 'Üçüncü Qutu';

} elseif ($meglub_score >= 5000) {

    $box_number = 2;
    $box_name = 'İkinci Qutu';

} elseif ($meglub_score >= 3000) {

    $box_number = 1;
    $box_name = 'Birinci Qutu';

}
?>


<?php if ($box_number > 0) { ?>


<b>Qazandınız:</b>

<?php echo $box_name; ?>

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
src="zombi/qutu<?php echo $box_number; ?>.png"
alt="qutu<?php echo $box_number; ?>"
>

</td>


<td>


<?php
/*
==========================================================
 HƏDİYYƏLƏR
==========================================================
*/

for (
    $gift = 1;
    $gift <= $box_number;
    $gift++
) {
?>

<?php
$box_is_opened = false;

if (
    isset($_SESSION['zombie_opened_boxes']) &&
    isset(
        $_SESSION['zombie_opened_boxes']
        [$group_number]
        [$gift]
    ) &&
    $_SESSION['zombie_opened_boxes']
        [$group_number]
        [$gift] === true
) {
    $box_is_opened = true;
}
?>

<img
src="muxtelif/gifts.gif"
alt=""
>

<?php if ($box_is_opened) { ?>

<img
src="muxtelif/okey.png"
alt="Açılıb"
>

<?php } ?>

<a
href="kordinat3.php?ac=ok&amp;qutu=<?php
echo (int)$gift;
?>&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

Hediyye <?php echo (int)$gift; ?>

</a>

<br>

<?php
}
?>


</td>

</tr>

</table>

</div>

</div>


<?php } else { ?>


Siz hec bir qutu elde etmediz


<?php } ?>


<br>


<div class="menu">

<br>

<li>

<a href="zombi_yarad.php?go=qruplar">

<img
src="img/go_next.png"
alt=""
>

Zombie Castle

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




<!-- ======================================================
     QRUPDAN ÇIXDI
     ====================================================== -->

<?php } elseif ($group_exit_message) { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

Siz Qrupu Tərk Etdiniz !

<br>

<div class="menu">

<br>

<li>

<a href="zombi_yarad.php?go=qruplar">

<img
src="muxtelif/on.png"
alt=""
>

Zombie Castle

</a>

</li>

<li>

<a href="menu.php?">

<img
src="muxtelif/home.png"
alt=""
>

Ana sehife

</a>

</li>

</div>

</div>


<!-- ======================================================
     QRUPDAN MƏĞLUB AYRILDI
     ====================================================== -->

<?php } elseif ($join_failed_message) { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

Siz Bu Qrupdan Məğlub Ayrılmısınız !

<br>

<div class="menu">

<br>

<li>

<a href="zombi_yarad.php?go=qruplar">

<img
src="muxtelif/on.png"
alt=""
>

Zombie Castle

</a>

</li>

<li>

<a href="menu.php?">

<img
src="muxtelif/home.png"
alt=""
>

Ana sehife

</a>

</li>

</div>

</div>


<!-- ======================================================
     QRUP LƏĞV
     ====================================================== -->

<?php } elseif ($group_cancelled_message) { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

<b>
Köməyə gələn oyunçu olmadığına görə döyüş ləğv edildi
</b>

<br>

<div class="menu">

<br>

<li>

<a href="zombi_yarad.php?go=qruplar">

<img
src="muxtelif/on.png"
alt=""
>

Zombie Castle

</a>

</li>

<li>

<a href="menu.php?">

<img
src="muxtelif/home.png"
alt=""
>

Ana sehife

</a>

</li>

</div>

</div>


<!-- ======================================================
     LOGO ÇIXIŞ
     ====================================================== -->

<?php } elseif (
    $go === 'logo_cix' &&
    $current_group !== null
) { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

<div class="zombie-exit-warning">

Siz digər səhifələrə keçmək üçün qrupu tərk etməlisiniz!

<br>

Qrupu tərk etmək istəyirsiz?

<br><br>

<a
href="zombi_yarad.php?go=cix&amp;ok=cix&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>
Hə
</a>

&nbsp; | &nbsp;

<a href="zombi_yarad.php?go=q&amp;qrup_num=<?php echo (int)$group_number; ?>" > Yox </a>
</div>

</div>


<!-- ======================================================
     OYUNA BAŞLAMA XƏBƏRDARLIĞI
     ====================================================== -->

<?php } elseif ($start_message !== '') { ?>

<div class="info">

<div
style="
font-weight:bold;
color:#000000;
"
>

<?php

echo htmlspecialchars(
    $start_message,
    ENT_QUOTES,
    'UTF-8'
);

?>

</div>

<br>

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>
<?php
if (
    isset($_SESSION['zombie_call_success']) &&
    (int)$_SESSION['zombie_call_success'] ===
    (int)$group_number
) {
?>

<div class="success">
    <img src="muxtelif/okey.png" alt="" />
    <span style="color:#259C00;">
        Çağırış gönderildi<br/>
    </span>
</div>

<?php
    unset($_SESSION['zombie_call_success']);
}
?>
<div class="menu">

<li>

<a
href="zombi_yarad.php?go=q&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

Sehifeni Yenile

</a>

</li>

<li>

<a
href="zombi_yarad.php?go=cagir&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

Döyüşçü çağır

</a>

</li>

<li>

<a
href="zombi_yarad.php?go=bashla&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

Oyuna Başla

</a>

</li>

</div>

<br>

<div class="center">

<div class="block_line">

<b>Qrup Melumatlari</b>

</div>

</div>

<br>

<b>Qrupun adı:</b>

<?php

echo htmlspecialchars(
    $current_group['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

<br>

<b>Döyüşçü tutumu:</b>

<?php echo (int)$current_group['capacity']; ?>

<br>

<hr>

<div class="center">

<div class="block_line">

<b>Qrupda Olanlar</b>

</div>

</div>

<br>

<div class="menu">

<?php foreach (
    $current_group['members']
    as $member
) { ?>

<li>

<a
href="infoforce.php?uid=<?php
echo (int)$member['id'];
?>"
>

<img
src="klan_img/1.gif"
alt=""
>

<?php

echo htmlspecialchars(
    $member['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

[14]

</a>

</li>

<?php } ?>

</div>

<br><br>

<form
method="post"
action="zombi_yarad.php?go=yaz&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

<input
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

<?php

if (
    isset($_SESSION['zombie_messages'][$group_number]) &&
    count(
        $_SESSION['zombie_messages'][$group_number]
    ) > 0
) {

    foreach (
        $_SESSION['zombie_messages'][$group_number]
        as $chat_message
    ) {

?>

<a
href="infoforce.php?uid=<?php
echo (int)$chat_message['id'];
?>"
>

<u>

<font color="green">

<?php

echo htmlspecialchars(
    $chat_message['name'],
    ENT_QUOTES,
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
    $chat_message['message'],
    ENT_QUOTES,
    'UTF-8'
);

?>

<br>

</small>

<?php

    }
}

?>

</div>


<!-- ======================================================
     QRUP YARAT
     ====================================================== -->

<?php } elseif ($go === 'neww') { ?>

<?php

foreach ($_SESSION['zombie_groups'] as $check_gid => $check_group) {

    if (
        isset($check_group['cancelled']) &&
        $check_group['cancelled'] === true
    ) {
        continue;
    }

    /*
     * DÖYÜŞ BAŞLAMIŞ QRUPU AKTİV QRUP SAYMA
     *
     * Aktiv qruplarda görünmürsə,
     * burada da "Siz artıq qrup yaratmısınız"
     * çıxmamalıdır.
     */
    if (
        isset($check_group['started']) &&
        $check_group['started'] === true
    ) {
        continue;
    }

    if (
        !isset($check_group['members']) ||
        !is_array($check_group['members'])
    ) {
        continue;
    }

    foreach ($check_group['members'] as $check_member) {

        if (
            isset($check_member['id']) &&
            (int)$check_member['id'] === (int)$player_id
        ) {

            $has_active_group = true;
            $my_group_number = (int)$check_gid;

            break 2;
        }
    }
}

?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

<?php if ($has_active_group) { ?>

<div class="center">

<b style="color:grey;">Siz Hal Hazırda Aktiv Döyüşdəsiniz!</b>

</div>




<?php } else { ?>

<form
method="post"
action="zombi_yarad.php?go=ok"
>

<b>Otaqin Adı</b>

<br>

<input
type="text"
size="12"
name="alish_min"
maxlength="15"
value=""
>

<br>

<b>Oyunçu Tutumu</b>

<br>

<select name="aa">

<option value="2">2</option>
<option value="3">3</option>
<option value="4">4</option>
<option value="6">6</option>

</select>

<br><br>

<input
type="hidden"
name="action"
value="save"
>

<input
type="submit"
class="button"
value="Ok"
>

<br>

</form>

<?php } ?>

<div class="menu">

<br>

<li>

<a href="zombi_yarad.php?go=qruplar">

<img
src="muxtelif/on.png"
alt=""
>

Zombie Castle

</a>

</li>

<li>

<a href="menu.php?">

<img
src="muxtelif/home.png"
alt=""
>

Ana sehife

</a>

</li>

</div>

</div>


<!-- ======================================================
     QRUP MƏLUMATLARI
     ====================================================== -->
<?php } elseif (
    $go === 'cagir' &&
    $current_group !== null
) { ?>

<div class="info">

    <div class="center">
        <div class="block_line">
            <b>Zombie Castle</b>
        </div>
    </div>

    <br>

    <?php if (count($cagir_players) > 0) { ?>

        <?php foreach ($cagir_players as $cagir_player) { ?>

            <a href="zombi_yarad.php?go=cagir_ok&amp;uid=<?php
                echo (int)$cagir_player['id'];
            ?>&amp;qrup_num=<?php
                echo (int)$group_number;
            ?>&amp;yenile=<?php
                echo mt_rand(100000000, 999999999);
            ?>">
                (Go)
            </a>

            |

            <a href="infoforce.php?uid=<?php
                echo (int)$cagir_player['id'];
            ?>">
                <font color="green">
                    <?php
                    echo htmlspecialchars(
                        $cagir_player['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                    [14]
                </font>
            </a>

            <hr>

        <?php } ?>

    <?php } ?>

    <br>

    <a href="zombi_yarad.php?go=q&amp;qrup_num=<?php echo (int)$group_number; ?>">
    Geri
    <br>
</a>

</div>


<?php } elseif (
    $go === 'grup' &&
    $current_group !== null
) { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

<b>Otaqın Adı:</b>

<?php

echo htmlspecialchars(
    $current_group['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

<br>

<b>Döyüşçü Tutumu:</b>

<?php echo (int)$current_group['capacity']; ?>

<br>

<b>Döyüşdə Olanlar</b>

<br>

<div class="menu">

<?php foreach (
    $current_group['members']
    as $member
) { ?>

<li>

<a
href="infoforce.php?id=<?php
echo (int)$player_id;
?>&amp;uid=<?php
echo (int)$member['id'];
?>"
>

<img
src="klan_img/144.gif"
alt=""
>

<?php

echo htmlspecialchars(
    $member['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

[14]

</a>

</li>

<?php } ?>

<hr>

<?php

if (
    count($current_group['members']) <
    (int)$current_group['capacity'] &&
    !(
        isset($current_group['cancelled']) &&
        $current_group['cancelled'] === true
    )
) {

?>

<li>

<a
href="zombi_yarad.php?go=komek_ok&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

[Qrupa Daxil ol]

</a>

</li>

<?php } ?>

<hr>

<br>

<li>

<a href="zombi_yarad.php?go=qruplar">

<img
src="muxtelif/on.png"
alt=""
>

Zombie Castle

</a>

</li>

<li>

<a href="menu.php?">

<img
src="muxtelif/home.png"
alt=""
>

Ana sehife

</a>

</li>

</div>

</div>


<!-- ======================================================
     QRUP SƏHİFƏSİ
     ====================================================== -->

<?php } elseif (
    $current_group !== null &&
    $group_number > 0
) { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

<div class="menu">

<li>

<a
href="zombi_yarad.php?go=q&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

Sehifeni Yenile

</a>

</li>

<li>

<a
href="zombi_yarad.php?go=cagir&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

Döyüşçü çağır

</a>

</li>

<li>

<a
href="zombi_yarad.php?go=bashla&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

Oyuna Başla

</a>

</li>

</div>

<div class="center">

<div class="block_line">

<b>Qrup Melumatlari</b>

</div>

</div>

<br>

<b>Qrupun adı:</b>

<?php

echo htmlspecialchars(
    $current_group['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

<br>

<b>Döyüşçü tutumu:</b>

<?php echo (int)$current_group['capacity']; ?>

<br>

<hr>

<div class="center">

<div class="block_line">

<b>Qrupda Olanlar</b>

</div>

</div>

<br>

<div class="menu">

<?php foreach (
    $current_group['members']
    as $member
) { ?>

<li>

<a
href="infoforce.php?uid=<?php
echo (int)$member['id'];
?>"
>

<img
src="klan_img/1.gif"
alt=""
>

<?php

echo htmlspecialchars(
    $member['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

[14]

</a>

</li>

<?php } ?>

</div>

<br><br>

<form
method="post"
action="zombi_yarad.php?go=yaz&amp;qrup_num=<?php
echo (int)$group_number;
?>"
>

<input
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

<?php

if (
    isset($_SESSION['zombie_messages'][$group_number]) &&
    count(
        $_SESSION['zombie_messages'][$group_number]
    ) > 0
) {

    foreach (
        $_SESSION['zombie_messages'][$group_number]
        as $chat_message
    ) {

?>

<a
href="infoforce.php?uid=<?php
echo (int)$chat_message['id'];
?>"
>

<u>

<font color="green">

<?php

echo htmlspecialchars(
    $chat_message['name'],
    ENT_QUOTES,
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
    $chat_message['message'],
    ENT_QUOTES,
    'UTF-8'
);

?>

<br>

</small>

<?php

    }
}

?>

</div>


<!-- ======================================================
     QRUPLAR
     ====================================================== -->

<?php } elseif ($go === 'qruplar') { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

<div class="center">

<b>Haqqında:</b>

<br>

Siz bu qalada zombilere qarşı döyüşeceksiz.
Qalada ki zombiler xautik olduquna göre,
qalaya girmək üçün mütlek qrup yaradıb
ve ya hansısa qrupa daxil olmalısız.

<a
href="zombi_yarad.php?go=qruplar&amp;melumat=ardi"
>

Etraflı

</a>

<br>

</div>

<hr>

<b>Aktiv qrupların siyahısı</b>

<br><br>

<div class="zombie-active-groups">

<?php

$active_group_found = false;

foreach (
    $_SESSION['zombie_groups']
    as $group
) {

    if (
        isset($group['cancelled']) &&
        $group['cancelled'] === true
    ) {
        continue;
    }


    if (
        isset($group['started']) &&
        $group['started'] === true
    ) {
        continue;
    }


    $member_count =
        isset($group['members']) &&
        is_array($group['members'])
            ? count($group['members'])
            : 0;


    /*
     * BOŞ QRUP
     */

if ($member_count === 0) {

    if (
        !isset($group['empty_group_time']) ||
        (int)$group['empty_group_time'] <= 0
    ) {

        $_SESSION['zombie_groups']
        [(int)$group['id']]
        ['empty_group_time'] =
            time();

    } elseif (
        time() -
        (int)$group['empty_group_time']
        >= $empty_group_delete_timeout
    ) {

        zombie_delete_group(
            (int)$group['id']
        );

        continue;
    }
}


$is_full =
    $member_count >=
    (int)$group['capacity'];


$active_group_found = true;

?>

<div class="zombie-group-item">

<?php if (!$is_full) { ?>

<a
class="zombie-group-link"
href="zombi_yarad.php?go=grup&amp;qrup_num=<?php
echo (int)$group['id'];
?>"
>

<?php } else { ?>

<span
class="zombie-group-link"
style="cursor:default;"
>

<?php } ?>

<img
src="muxtelif/qrupda.png"
alt=""
>

<?php

echo htmlspecialchars(
    $group['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

(<?php echo $member_count; ?>/<?php
echo (int)$group['capacity'];
?>)

<?php if (!$is_full) { ?>

</a>

<?php } else { ?>

</span>

<?php } ?>

</div>

<?php } ?>

</div>

<?php if ($active_group_found) { ?>

<br>

<?php } ?>

<a href="zombi_yarad.php?go=neww">

[Qrup Yarad]

</a>

<br>

<a href="zombi_yarad.php?go=qruplar_kohne">

Gün erzinde yaradılan qruplar

</a>

<br>

<a href="menu.php?">

<img
src="muxtelif/home.png"
alt=""
>

Ana sehife

</a>

</div>


<!-- ======================================================
     ƏSAS SƏHİFƏ
     ====================================================== -->

<?php } else { ?>

<div class="info">

<div class="center">

<div class="block_line">

<b>Zombie Castle</b>

</div>

</div>

<br>

<div class="center">

<b>Haqqında:</b>

<br>

Siz bu qalada zombilere qarşı döyüşeceksiz.
Qalada ki zombiler xautik olduquna göre,
qalaya girmək üçün mütlek qrup yaradıb
ve ya hansısa qrupa daxil olmalısız.

<a
href="zombi_yarad.php?go=qruplar&amp;melumat=ardi"
>

Etraflı

</a>

<br>

</div>

<hr>

<b>Aktiv qrupların siyahısı</b>

<br><br>

<div class="zombie-active-groups">

<?php

$active_group_found = false;

foreach (
    $_SESSION['zombie_groups']
    as $group
) {

    if (
        isset($group['cancelled']) &&
        $group['cancelled'] === true
    ) {
        continue;
    }


    if (
        isset($group['started']) &&
        $group['started'] === true
    ) {
        continue;
    }


    $member_count =
        isset($group['members']) &&
        is_array($group['members'])
            ? count($group['members'])
            : 0;


    /*
     * BOŞ QRUP 15 SANİYƏ
     */

    if ($member_count === 0) {

        if (
            !isset($group['empty_group_time']) ||
            (int)$group['empty_group_time'] <= 0
        ) {

            $_SESSION['zombie_groups']
            [(int)$group['id']]
            ['empty_group_time'] =
                time();

            continue;
        }


        if (
            time() -
            (int)$group['empty_group_time']
            >= $empty_group_delete_timeout
        ) {

            zombie_delete_group(
                (int)$group['id']
            );

            continue;
        }
    }


    $active_group_found = true;

?>

<div class="zombie-group-item">

<a
class="zombie-group-link"
href="zombi_yarad.php?go=grup&amp;qrup_num=<?php
echo (int)$group['id'];
?>"
>

<img
src="muxtelif/qrupda.png"
alt=""
>

<?php

echo htmlspecialchars(
    $group['name'],
    ENT_QUOTES,
    'UTF-8'
);

?>

(<?php echo $member_count; ?>/<?php
echo (int)$group['capacity'];
?>)

</a>

</div>

<?php } ?>

</div>

<?php if ($active_group_found) { ?>

<br>

<?php } ?>

<a href="zombi_yarad.php?go=neww">

[Qrup Yarad]

</a>

<br>

<a href="zombi_yarad.php?go=qruplar_kohne">

Gün erzinde yaradılan qruplar

</a>

<br>

<a href="menu.php?">

<img
src="muxtelif/home.png"
alt=""
>

Ana sehife

</a>

</div>

<?php } ?>


<!-- ======================================================
     FOOTER
     ====================================================== -->

<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">

[<b><a href="zombi_yarad.php?go=logo_cix&qrup_num=<?php echo (int)$group_number; ?>">Menu</a></b>]

[<b><a href="zombi_yarad.php?go=logo_cix&qrup_num=<?php echo (int)$group_number; ?>">Axtarış</a></b>]

[<a href="zombi_yarad.php?go=logo_cix&qrup_num=<?php echo (int)$group_number; ?>">Forum</a>]

[<a href="zombi_yarad.php?go=logo_cix&qrup_num=<?php echo (int)$group_number; ?>">Qurğular</a>]
<br><br>

<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>

<br>

<a href="index.php?">
    Çıxış (<?php echo htmlspecialchars($user_login, ENT_QUOTES, 'UTF-8'); ?>)
</a>

<br><br>

<a href="menu.php?dil=tr">

Türkce:

<img
alt="türkce"
src="http://macera.az/klan/muxtelif/tr.gif"
title="Türkce"
/>

</a>

<br>

Sciript name: Qanlı efsanə(modern version)

<br>

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