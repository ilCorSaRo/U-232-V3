<?php
/**
 *   https://github.com/Bigjoos/
 *   Licence Info: GPL
 *   Copyright (C) 2010 U-232 v.3
 *   A bittorrent tracker source based on TBDev.net/tbsource/bytemonsoon.
 *   Project Leaders: Mindless, putyn.
 *
 */
function cleanup_log($data)
{
    $text = sqlesc($data['clean_title']);
    $added = TIME_NOW;
    $ip = sqlesc($_SERVER['REMOTE_ADDR']);
    $desc = sqlesc($data['clean_desc']);
    sql_query("INSERT INTO cleanup_log (clog_event, clog_time, clog_ip, clog_desc) VALUES ($text, $added, $ip, {$desc})") or sqlerr(__FILE__, __LINE__);
}
function docleanup($data)
{
    global $INSTALLER09, $queries, $mc1;
    set_time_limit(1200);
    ignore_user_abort(1);
    // ===Clear funds on first day of the month
  if (date("d") == 1){
   sql_query("TRUNCATE funds");}
    //if (mysqli_affected_rows() > 0)
    $mc1->delete_value('totalfunds_');
    // ===End
    //== Donation Progress Mod Updated For Tbdev 2009/2010 by Bigjoos/pdq
    $res = sql_query("SELECT id, modcomment, vipclass_before FROM users WHERE donor='yes' AND donoruntil < ".TIME_NOW." AND donoruntil <> '0'") or sqlerr(__FILE__, __LINE__);
    $msgs_buffer = $users_buffer = array();
    if (mysqli_num_rows($res) > 0) {
        $subject = "Donor status removed by system.";
        $msg = "Your Donor status has timed out and has been auto-removed by the system, and your Vip status has been removed. We would like to thank you once again for your support to {$INSTALLER09['site_name']}. If you wish to re-new your donation, Visit the site paypal link. Cheers!\n";
        while ($arr = mysqli_fetch_assoc($res)) {
            $modcomment = $arr['modcomment'];
            $modcomment = get_date(TIME_NOW, 'DATE', 1)." - Donation status Automatically Removed By System.\n".$modcomment;
            $modcom = sqlesc($modcomment);
            $msgs_buffer[] = '(0,'.$arr['id'].','.TIME_NOW.', '.sqlesc($msg).','.sqlesc($subject).')';
            $users_buffer[] = '('.$arr['id'].','.$arr['vipclass_before'].',\'no\',\'0\', '.$modcom.')';
            $update['class'] = ($arr['vipclass_before']);
            $mc1->begin_transaction('user'.$arr['id']);
            $mc1->update_row(false, array(
                'class' => $update['class'],
                'donor' => 'no',
                'donoruntil' => 0
            ));
            $mc1->commit_transaction($INSTALLER09['expires']['user_cache']);
            $mc1->begin_transaction('user_stats_'.$arr['id']);
            $mc1->update_row(false, array(
                'modcomment' => $modcomment
            ));
            $mc1->commit_transaction($INSTALLER09['expires']['user_stats']);
            $mc1->begin_transaction('MyUser_'.$arr['id']);
            $mc1->update_row(false, array(
                'class' => $update['class'],
                'donor' => 'no',
                'donoruntil' => 0
            ));
            $mc1->commit_transaction($INSTALLER09['expires']['curuser']);
            $mc1->delete_value('inbox_new_'.$arr['id']);
            $mc1->delete_value('inbox_new_sb_'.$arr['id']);
        }
        $count = count($users_buffer);
        if ($count > 0) {
            sql_query("INSERT INTO messages (sender,receiver,added,msg,subject) VALUES ".implode(', ', $msgs_buffer)) or sqlerr(__FILE__, __LINE__);
            sql_query("INSERT INTO users (id, class, donor, donoruntil, modcomment) VALUES ".implode(', ', $users_buffer)." ON DUPLICATE key UPDATE class=values(class),
            donor=values(donor),donoruntil=values(donoruntil),modcomment=concat(values(modcomment),modcomment)") or sqlerr(__FILE__, __LINE__);
            write_log("Cleanup: Donation status expired - ".$count." Member(s)");
        }
        unset($users_buffer, $msgs_buffer, $update, $count);
    }
    //===End===//
    if ($queries > 0) write_log("Delete Old Funds Clean -------------------- Delete Old Funds cleanup Complete using $queries queries --------------------");
    if (false !== mysqli_affected_rows($GLOBALS["___mysqli_ston"])) {
        $data['clean_desc'] = mysqli_affected_rows($GLOBALS["___mysqli_ston"])." items deleted/updated";
    }
    if ($data['clean_log']) {
        cleanup_log($data);
    }
}
?>
