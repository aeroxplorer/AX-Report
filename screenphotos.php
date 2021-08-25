<?php
session_start();
ob_start();
include "../functions.php";
include "../badge.php";
// SCREEN FOTO
$maxoffset = "";
function checkInfo($airline,$aircraft,$reg){
    $connection = mysqli_connect('localhost','dandemo_main','','dandemo_autofill','3306');
    $posts = mysqli_connect('localhost','dandemo_main','','dandemo_posts','3306');
    $airline = mysqli_real_escape_string($connection,$airline);
    $aircraft = mysqli_real_escape_string($connection,$aircraft);
    $reg = mysqli_real_escape_string($connection,$reg);
    $query = "SELECT * FROM photos WHERE registration='$reg'";
    $resres = mysqli_query($posts,$query);
    $inty = mysqli_fetch_assoc($resres);
    if((int) $inty['id'] == 0){
        $method = "AUTOFILL";
        $query = "SELECT * FROM aircraft WHERE name='$aircraft'";
        $res = mysqli_query($connection,$query);
        if((int) mysqli_fetch_assoc($res)['id'] == 0){
            $final_arc = "red";
        } else {
            $final_arc = "green";
        }
        $query = "SELECT * FROM airlines WHERE name='$airline'";
        $res = mysqli_query($connection,$query);
        if((int) mysqli_fetch_assoc($res)['id'] == 0){
            $final_arl = "red";
        } else {
            $final_arl = "green";
        }
    } else {
        $method = "PHOTO#" . $inty['id'];
        if($airline != $inty['airline']){
            $final_arl = "red";
        } else {
            $final_arl = "green";
        }
        if($aircraft != $inty['aircraft']){
            $final_arc = "red";
        } else {
            $final_arc = "green";
        }
    }
    $string = $method . "." . $final_arl . "." . $final_arc;
    return $string;
}
function isNew($photographer){
    $connection = mysqli_connect('localhost','dandemo_users','','dandemo_userinfo','3306');
    $id = (int) $photographer;
    $query = "SELECT * FROM users WHERE id='$id' LIMIT 1";
    $result = mysqli_query($connection,$query);
    $info = mysqli_fetch_assoc($result);
    $startdate = $info['join_date'];
    if($startdate == date("m/d/Y",strtotime("-1 days")) || $startdate == date("m/d/Y") || $startdate == date("m/d/Y",strtotime("-2 days")) || $startdate == date("m/d/Y",strtotime("-3 days")) || $startdate == date("m/d/Y",strtotime("-4 days")) || $startdate == date("m/d/Y",strtotime("-5 days"))){
        return true;
    } else {
        return false;
    }
}
function duplicateTo($reg,$photographer){
    $connection = mysqli_connect('localhost','dandemo_main','','dandemo_posts','3306');
    $reg = mysqli_real_escape_string($connection,$reg);
    $photographer = (int) $photographer;
    $query = "SELECT * FROM photos WHERE registration='$reg' AND authornum='$photographer'";
    $result = mysqli_query($connection,$query);
    $id = (int) mysqli_fetch_assoc($result)['id'];
    if($id == 0){
        return "NONE";
    } else {
        return $id;
    }
}
$oops = mysqli_connect('localhost','dandemo_main','','dandemo_queue','3306');
if(isset($_POST['skipscreen'])){
    $qqqqqqqq = (int) $_POST['skipscreen'];
    $que = "SELECT * FROM queue_photos WHERE id='$qqqqqqqq' LIMIT 1";
    $res = mysqli_query($oops,$que);
    $inty = mysqli_fetch_assoc($res);
    $scnum = "";
    if($inty['screener'] != $_SESSION['authornum']){
    if($inty['screener2'] != $_SESSION['authornum']){
      $scnum = 3;
    } else {
      $scnum = 2;
      if($inty['screener_verdict'] == "ACCEPT"){
      } else {
      }
    }
  } else {
    $lastscreener = false;
  }
  $def = 0;
  if($scnum == ""){
      $def = "NONE";
  }
    $query = "UPDATE queue_photos SET screener{$scnum}='$def' WHERE id='$qqqqqqqq'";
    $elpupu = mysqli_query($oops,$query);
    $maxoffset = $query;
    $current = $_COOKIE['screener_offset'];
    $new = $current+1;
    if($new > 4){
        $new = 0;
        $alerts++;
        $maxoffset = "You cannot skip more than 4 photos.";
    }
    setcookie("screener_offset",$new,time()+(60*60*8));
    header("location: screenphotos.php");
}
$scnum = "";
function logScreenedPhoto($qid,$dec){
    $connection = mysqli_connect('localhost','dandemo_main','','dandemo_queue','3306');
    $dec = mysqli_real_escape_string($connection,$dec);
    $qid = (int) $qid;
    $user = (int) $_SESSION['authornum'];
    $date = date("m/d/Y");
    $time = time();
    $query = "INSERT INTO screened_pics(screener,date_s,time_s,queue_id,decision) VALUES ('$user','$date','$time','$qid','$dec')";
    $result = mysqli_query($connection,$query);
}
if(!isset($_SESSION['authornum'])){
    header("location: login.php?redirect=screenphotos");
}
function esc($val){
    $connection = mysqli_connect('localhost','dandemo_main','','dandemo_posts','3306');
    $new = mysqli_real_escape_string($connection,$val);
    return $new;
}
if(!isset($_SESSION['start'])){
  $_SESSION['start'] = time();
}
if($_SESSION['photos'] > 0){
  $_SESSION['photos'] = $_SESSION['photos'] + 1;
} else {
  $_SESSION['photos'] = 1;
}
if(isset($_POST['accept']) && $_POST['reject'] != "TRUE"){
    logScreenedPhoto($_POST['queueid'],"ACCEPT");
  $connection = mysqli_connect('localhost','dandemo_main','','dandemo_posts','3306');
  $queue = mysqli_connect('localhost','dandemo_main','','dandemo_queue','3306');
  $qqqid = (int) $_POST['queueid'];
  $init = "SELECT * FROM queue_photos WHERE id='$qqqid' LIMIT 1";
  $rest = mysqli_query($queue,$init);
  $inty = mysqli_fetch_assoc($rest);
  $scnum = 1;
  $prevfeat = (int) $inty['feats'];
  if($inty['screener'] != $_SESSION['authornum']){
    if($inty['screener2'] != $_SESSION['authornum']){
      $scnum = 3;
      $lastscreener = true;
    } else {
      $scnum = 2;
      if($inty['screener_verdict'] == "ACCEPT"){
        $lastscreener = true;
      } else {
        $lastscreener = false;
      }
    }
  } else {
    $lastscreener = false;
  }
  addScore(3,$_SESSION['authornum']);
  if($lastscreener){
    $aircraft = esc($_POST['aircraft']);
    $url = $_POST['imgurl'];
    $airline = esc($_POST['airline']);
    $airport = esc($_POST['airport']);
    $reg = esc($_POST['registration']);
    $ratio = esc($_POST['ratio']);
    $name = esc($_POST['name']);
    $taken = esc($_POST['takendate']);
    $email = esc($_POST['email']);
    $authornum = esc($_POST['authornum']);
    $bts_badges = [];
    $user_photos = howManyPhotos($authornum);
    // PHOTO CT BADGES
    if(time() < 1608613170 && !hasBadge(57,$authornum)){
        addBadge(57,$authornum);
    }
    if($user_photos == 0){
        // FIRST PHOTO
        addBadge(7,$authornum);
        sendMessage($authornum,"Congrats, you got a badge! 'Upload One Photo'");
    } elseif($user_photos >= 9 && $user_photos < 24){
        // 10th photo
        if(!hasBadge(7,$authornum)){
            addBadge(7,$authornum);
        }
        if($user_photos >= 9){
            if(!hasBadge(8,$authornum)){
        addBadge(8,$authornum);
        sendMessage($authornum,"Congrats, you got a badge! 'Upload Ten Photos'");
            }
        }
    } elseif($user_photos >= 24 && $user_photos < 99){
        // 25th photo
        if(!hasBadge(8,$authornum)){
            addBadge(8,$authornum);
        }
        if(!hasBadge(7,$authornum)){
            addBadge(7,$authornum);
        }
        if($user_photos >= 24){
            if(!hasBadge(9,$authornum)){
        addBadge(9,$authornum);
        sendMessage($authornum,"Congrats, you got a badge! 'Upload 25 Photos'");
            }
        }
    } elseif($user_photos >= 99 && $user_photos < 499){
        // 100th photo
        if(!hasBadge(7,$authornum)){
            addBadge(7,$authornum);
        }
        if(!hasBadge(8,$authornum)){
            addBadge(8,$authornum);
        }
        if(!hasBadge(9,$authornum)){
            addBadge(9,$authornum);
        }
        if($user_photos >= 99){
            if(!hasBadge(10,$authornum)){
        addBadge(10,$authornum);
        sendMessage($authornum,"Congrats, you got a badge! 'Upload 100 Photos'");
            }
        }
    } elseif($user_photos >= 499){
        // 500th photo
        if(!hasBadge(7,$authornum)){
            addBadge(7,$authornum);
        }
        if(!hasBadge(8,$authornum)){
            addBadge(8,$authornum);
        }
        if(!hasBadge(9,$authornum)){
            addBadge(9,$authornum);
        }
        if(!hasBadge(10,$authornum)){
            addBadge(10,$authornum);
        }
        if($user_photos >= 499){
            if(!hasBadge(11,$authornum)){
        addBadge(11,$authornum);
        sendMessage($authornum,"Congrats, you got a badge! 'Upload 500 Photos'");
            }
        }
    }
    // AIRPORT BADGES
    airportBadge($airport,$authornum);
    //$photo = esc($_POST['foto']);
    $date = date("m/d/Y");
    $id = esc($_POST['queueid']);
    $acc = $_SESSION['authornum'];
    /// CHECK FOR WATERMARK
    $query = "SELECT * FROM queue_photos WHERE id='$id' LIMIT 1";
    $result = mysqli_query($queue,$query);
    $photo_in = mysqli_fetch_assoc($result);
    // if(trim($date) == "08/19/2020" || trim($date) == "08/20/2020"){
    //     if(!hasBadge(46,$authornum)){
    //     addBadge(46,$authornum);
    //     }
    // }
    if($photo_in['photo_no_wm'] != ""){
        $no_wm = $photo_in['photo_no_wm'];
        $wm = $photo_in['url'];
    } else {
        $wm = $photo_in['url'];
        $no_wm = "";
    }
    /// END CHECK
    if($_POST['FEAT'] == "TRUE" || $photo_in['feats'] > 0){
        $badge = "FEATURED";
        if(!hasBadge(12,$authornum)){
            addBadge(12,$authornum);
        }
    } else {
        $badge = "";
    }
    if(time() < 1625457599){
        if(!hasBadge(98,$authornum)){
            addBadge(98,$authornum);
        }
    }
    $query = "INSERT INTO photos(q_id,taken,authornum,registration,authorname,photo_url,photo_location,type,views,aircraft,airline,upload_date,photo_date,ratio,badge,acc_by,photo_no_wm) VALUES ('$id','$taken','$authornum','$reg','$name','$wm','$airport','alluse','0','$aircraft','$airline','$date','$date','$ratio','$badge','$acc','$no_wm')";
    $result = mysqli_query($connection,$query);
    if(!$result){
        die("Failed to screen." . mysqli_error($connection));
    }
    $q2 = "DELETE FROM queue_photos WHERE id='$id'";
    unlink("/home/dandemo/aeroxplorer.com/images/$url");
    $r2 = mysqli_query($queue,$q2);
    // ADD BADGES
    $badges_given = $_POST['badge'];
    if(is_countable($badges_given)){
        for($i=0;$i<count($badges_given);$i++){
            $curr = mysqli_real_escape_string($connection,$_POST['badge'][$i]);
            if(!hasBadge($curr,$authornum)){
            addBadge($curr,$authornum);
            }
        }
        if(count($badges_given) > 0){
            $badge_aw = true;
        }
    }
    if($r2){
        // Check to see if this is the last photo in the queue for the user
        $checkQueue = "SELECT * FROM queue_photos WHERE authornum='$authornum'";
        $cQres = mysqli_query($queue,$checkQueue);
        $e = 0;
        while(mysqli_fetch_assoc($cQres)){
            $e++;
        }
        if($e == 0){
            // set mail body
            $mail_body = '<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <style>
    * {
      margin: 0;
      box-sizing: border-box;
    }
    body {
      background-color: #efefefff;
    }
    .main-node {
      background-color: #efefefff;
      font-family: \'Open Sans\', sans-serif;
      padding: 10px;
      max-width: 100vw;
    }
    fieldset:first-of-type {
      width: 80%;
      text-align: center;
      border: none;
      border-top: solid 4px green;
    }
    legend:first-of-type {
      font-size: 30px;
      padding: 0 30px;
      color: green;
    }
    fieldset:not(:first-of-type) {
      width: 80%;
      text-align: center;
      border: none;
      border-top: solid 4px red;
    }
    legend:not(:first-of-type) {
      font-size: 30px;
      padding: 0 30px;
      color: red;
    }
    .photo-acc img {
      width: 300px;
      border-radius: 5px;
      margin-right: 50px;
      display: inline-block;
      vertical-align: top;
    }
    .spec {
      position: relative;
    }
    td {
      text-align: left;
    }
    .exclamation {
      position: absolute;
      top: 1px;
      left: 1px;
      background-color: black;
      color: white;
      padding: 10px 14px;
      border-bottom-right-radius: 5px;
    }
    .view-photo {
      text-decoration: none;
      display: block;
      width: 100%;
      text-align: center;
      padding: 10px 15px;
      background-color: #0b5394ff;
      color: white;
      cursor: pointer;
      border-radius: 4px;
      margin: 10px 0;
    }
    td[colspan=2]{
      padding: 10px;
    }
    @media screen and (max-width: 1100px){
      fieldset {
        width: 90% !important;
        text-align: left;
        border-top: none;
        font-size: 1.3em !important;
      }
      .photo-acc {
        width: 100%;
      }
      .photo-acc td {
        display: block;
        width: 100%;
        text-align: center;
        margin: 10px;
      }
      legend {
        font-size: 50px;
        padding: 1px;
        text-decoration: underline;
      }
      * {
        padding: 0 !important;
      }
    }
    .socials {
      display: block;
      width: 100%;
      padding: 15px;
      justify-content: center;
      text-align: center;
      color: black;
      cursor: pointer;
    }
    .socials svg {
      color: black;
      text-decoration: none;
      cursor: pointer;
      margin: 20px;
    }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Open+Sans&family=Quicksand&display=swap" rel="stylesheet">
  </head>
  <body>
<div class="main-node">
      <span class="socials">
        <a href="https://instagram.com/aeroxplorer"><svg xmlns="http://www.w3.org/2000/svg" width="2em" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
  <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/>
</svg></a>
<a href="https://facebook.com/theexplorerblog.fb"><svg xmlns="http://www.w3.org/2000/svg" width="2em" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
  <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
</svg></a>
<a href="https://twitter.com/aeroxplorer"><svg xmlns="http://www.w3.org/2000/svg" width="2em" fill="currentColor" class="bi bi-twitter" viewBox="0 0 16 16">
  <path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z"/>
</svg></a>
<a href="https://www.linkedin.com/company/theexplorerblog/"><svg xmlns="http://www.w3.org/2000/svg" width="2em" fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16">
  <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225h2.401zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248-.822 0-1.359.54-1.359 1.248 0 .694.521 1.248 1.327 1.248h.016zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016a5.54 5.54 0 0 1 .016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225h2.4z"/>
</svg></a>
      </span>
Hello there! Thank you for choosing <b>AeroXplorer</b> as a platform to share your airplane photos. Our team acknowledges and supports your continued dedication toward aviation and is looking forward to more of your excellent contributions in the future.<br><br>
For questions pertaining to our photo guidelines, refer to our <a href="https://aeroxplorer.com/photo-guidelines.php">Photo Screening Guidelines</a>.
<br><br>
If you have any general questions about our screening process and team availability, visit our <a href="https://aeroxplorer.com/screening-faq.php">Photo Screening FAQ</a>.
<br><br>
If you have any further questions, comments, or suggestions that have not been addressed by the above link, please do not hesitate to submit our <a href="https://aeroxplorer.com/contact.php">contact form</a>.
<br><br>
The results of your photo submissions are below! Please note that feedback is automatically generated. If you would like a more detailed explanation of what can be fixed, you can request a human-written evaluation of your photo.
<br><br>
Kind Regards,<br>&emsp;The AeroXplorer.com Team
<br><br>
<center>
<fieldset>
<legend>{{NUM}} Accepted Photos</legend>
  <col width="35%">
  <col width="64%">
  <table class="photo-acc">
        ';
            // check backlog
            $checkBacklog = "SELECT * FROM backlog_email WHERE authornum='$authornum' AND verdict='ACCEPT'";
            $cBres = mysqli_query($queue,$checkBacklog);
            $num = 1;
            while($in = mysqli_fetch_assoc($cBres)){
                $num++;
                $bl_airline = $in['airline'];
                $bl_aircraft = $in['aircraft'];
                $bl_reg = $in['reg'];
                $bl_num = $in['authornum'];
                $bl_id = $in['id'];
                $bl_date = $in['date_submitted'];
                $bl_airport = $in['airport'];
                $bl_url = $in['url'];
                $b_verdict = $in['verdict'];
                $bl_feat = $in['featured'];
                $mail_body .= '<tr>
      <td class="spec">
        ';
        if($bl_feat == 1){
            $mail_body .= '<div class="exclamation">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="yellow" class="bi bi-asterisk" viewBox="0 0 16 16">
            <path d="M8 0a1 1 0 0 1 1 1v5.268l4.562-2.634a1 1 0 1 1 1 1.732L10 8l4.562 2.634a1 1 0 1 1-1 1.732L9 9.732V15a1 1 0 1 1-2 0V9.732l-4.562 2.634a1 1 0 1 1-1-1.732L6 8 1.438 5.366a1 1 0 0 1 1-1.732L7 6.268V1a1 1 0 0 1 1-1z"/>
          </svg>&nbsp;FEATURED
        </div>';
        }
        $mail_body .= '
        <img src="https://cdn.aeroxplorer.com/uploads/' . $bl_url . '" alt="' . $bl_airline . ' ' . $bl_aircraft . ' at ' . $bl_airport . '"></td>
      <td>Airline: ' . $bl_airline . '<br>Aircraft: ' . $bl_aircraft . '<br>Airport: ' . $bl_airport . '<br>Registration: ' . $bl_reg . '<br>Date screened: ' . $bl_date . '<br><a class="view-photo" href="https://aeroxplorer.com/viewprofile.php?id=' . $bl_num . '">Your Profile</a></td>
    </tr>';
                $qqqq = "DELETE FROM backlog_email WHERE id='$bl_id'";
                $rrrr = mysqli_query($queue,$qqqq);
            }
            // Add the info for the current photo
            $mail_body .= '<tr>
      <td class="spec">
        ';
        if($_POST['FEAT'] == "TRUE"){
            $mail_body .= '<div class="exclamation">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="yellow" class="bi bi-asterisk" viewBox="0 0 16 16">
            <path d="M8 0a1 1 0 0 1 1 1v5.268l4.562-2.634a1 1 0 1 1 1 1.732L10 8l4.562 2.634a1 1 0 1 1-1 1.732L9 9.732V15a1 1 0 1 1-2 0V9.732l-4.562 2.634a1 1 0 1 1-1-1.732L6 8 1.438 5.366a1 1 0 0 1 1-1.732L7 6.268V1a1 1 0 0 1 1-1z"/>
          </svg>&nbsp;FEATURED
        </div>';
        }
        $mail_body .= '
        <img style="width: 250px" src="https://cdn.aeroxplorer.com/uploads/' . $url . '" alt="' . $airline . ' ' . $aircraft . ' at ' . $airport . '"></td>
      <td>Airline: ' . $airline . '<br>Aircraft: ' . $aircraft . '<br>Airport: ' . $airport . '<br>Registration: ' . $reg . '<br>Date screened: ' . $date . '<br><a class="view-photo" href="https://aeroxplorer.com/viewprofile.php?id=' . $authornum . '">Your Profile</a></td>
    </tr>';
        $mail_body = str_replace("{{NUM}}",$num,$mail_body);
        // AAAND for rejected photos :( lots of work
        $checkBrej = "SELECT * FROM backlog_email WHERE authornum='$authornum' AND verdict='REJECT'";
        $checkBrejres = mysqli_query($queue,$checkBrej);
        $num = 0;
        $mail_body .= '</table>
</fieldset>
<fieldset>
  <legend style="color: red">{{NUM}} Rejected Photos</legend>
  <col width="35%">
  <col width="64%">
  <table class="photo-acc">';
        while($br = mysqli_fetch_assoc($checkBrejres)){
            $num++;
            $bl_airline = $br['airline'];
            $bl_id = $br['id'];
                $bl_aircraft = $br['aircraft'];
                $bl_reg = $br['reg'];
                $bl_date = $br['date_submitted'];
                $bl_airport = $br['airport'];
                $bl_url = $br['url'];
                $b_verdict = $br['verdict'];
                $bl_reason = $br['rej_reason'];
                $bl_feedback = $br['rej_feedback'];
                $mail_body .= '<tr>
      <td class="spec">
        <img style="width: 250px" src="https://cdn.aeroxplorer.com/uploads/' . $bl_url . '"></td>
      <td>Airline: ' . $bl_airline . '<br>Aircraft: ' . $bl_aircraft . '<br>Airport: ' . $bl_airport . '<br>Registration: ' . $bl_reg . '<br>Date screened: ' . $bl_date . '<br>Reason: ' . $bl_reason . '<br></td>
    </tr>
    <tr>
      <td colspan="2"><i>This feedback is computer-generated.</i><br><br>
        ' . $bl_feedback . '</td>
    </tr>';
                $qqqq = "DELETE FROM backlog_email WHERE id='$bl_id'";
                $rrrr = mysqli_query($queue,$qqqq);
        }
        $mail_body = str_replace("{{NUM}}",$num,$mail_body);
        $mail_body .= '
        </table>
</fieldset>
</div>
  </body>
</html>';

            // Send mail
            $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: screening@aeroxplorer.com";
$mail = mail($email,"AeroXplorer Photo Screening Results",$mail_body,$headers);
if($mail){
            header("location: screenphotos.php");
        }
        } else {
            // add this photo to the backlog, continue with normal work
            $mynum = $_SESSION['authornum'];
            $date = date("m/d/Y");
            $addToBack = "INSERT INTO backlog_email(authornum,verdict,url,airline,aircraft,airport,screener_id,email,date_submitted,reg) VALUES ('$authornum','ACCEPT','$url','$airline','$aircraft','$airport','$mynum','$email','$date','$reg')";
            $addItToBack = mysqli_query($queue,$addToBack);
            if($addItToBack){
                header("location: screenphotos.php");
            }
        }
        sendMessage($authornum,"Your photo ($airline $aircraft) has been accepted.");
        // build numerous image variations
        $ch = curl_init();
        //The url you wish to send the POST request to
        $curlurl = "https://cdn.aeroxplorer.com/resize.php";
        
        //The data you want to send via POST
        $fields = [
            'action' => 'build',
            'image' => $url,
            'ratio' => explode(":",$ratio)[1],
        ];
        //url-ify the data for the POST
        $fields_string = http_build_query($fields);
        //die($fields_string);
        //open connection
        $ch = curl_init();
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $curlurl);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        
        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
        
        //execute post
        $fileres = curl_exec($ch);
    }
} else {
  //not last screener, add to DB
  if($_POST['FEAT'] == "TRUE"){
    $prevfeat++;
  }
  $elqid = esc($_POST['queueid']);
  $aircraft = esc($_POST['aircraft']);
  $airline = esc($_POST['airline']);
  $airport = esc($_POST['airport']);
  $reg = esc($_POST['registration']);
  if($scnum == 1){
    $query = "UPDATE queue_photos SET screener_verdict='ACCEPT',feats='$prevfeat',airline='$airline',aircraft='$aircraft',registration='$reg',location='$airport' WHERE id='$elqid'";
  } else {
    $query = "UPDATE queue_photos SET screener2_verdict='ACCEPT',thirdreview='11111',feats='$prevfeat',airline='$airline',aircraft='$aircraft',registration='$reg',location='$airport' WHERE id='$elqid'";
  }
  $result = mysqli_query($queue,$query);
  if($result){
    header("location: screenphotos.php");
  } else {
      die("FAILED BOOMBOOM: " . mysqli_error($queue));
  }
}
}
if($_POST['reject'] == "TRUE"){
    logScreenedPhoto($_POST['queueid'],"REJECT");
    addScore(3,$_SESSION['authornum']);
    $userinfo = mysqli_connect('localhost','dandemo_users','','dandemo_userinfo','3306');
    $connection = mysqli_connect('localhost','dandemo_main','','dandemo_queue','3306');
    $queue = mysqli_connect('localhost','dandemo_main','','dandemo_queue','3306');
    // get screener number
    $qqqid = (int) $_POST['queueid'];
    $init = "SELECT * FROM queue_photos WHERE id='$qqqid' LIMIT 1";
    $rest = mysqli_query($connection,$init);
    $inty = mysqli_fetch_assoc($rest);
    $scnum = 1;
    $prevfeat = $inty['feats'];
    if($inty['screener'] != $_SESSION['authornum']){
      if($inty['screener2'] != $_SESSION['authornum']){
        $scnum = 3;
        $lastscreener = true;
      } else {
        $scnum = 2;
        if($inty['screener_verdict'] == "REJECT"){
          $lastscreener = true;
        } else {
          $lastscreener = false;
        }
      }
    } else {
      $lastscreener = false;
    }
    //
    if($lastscreener){
    $url = $_POST['imgurl'];
    $given_feedback = esc($_POST['givenfeedback']);
    $aircraft = esc($_POST['aircraft']);
    $airline = esc($_POST['airline']);
    $airport = esc($_POST['airport']);
    $reg = esc($_POST['registration']);
    $ratio = esc($_POST['ratio']);
    $name = esc($_POST['name']);
    $email = esc($_POST['email']);
    $authornum = esc($_POST['authornum']);
    $photo = esc($_POST['foto']);
    $date = date("m/d/Y");
    $theid = esc($_POST['queueid']);
    $acc = $_SESSION['authornum'];
    $quick = "SELECT * FROM users WHERE id='$authornum' LIMIT 1";
    $quickres = mysqli_query($userinfo,$quick);
    $rejected = mysqli_fetch_assoc($quickres)['rejected'] + 1;
    $gg = "UPDATE users SET rejected='$rejected' WHERE id='$authornum'";
    $ggg = mysqli_query($userinfo,$gg);
    if(!$ggg){
        echo "UH OH! SMTH WENT WRONGGGGG";
    }
    $feedback = esc($_POST['feedback']);
    $reason = esc($_POST['reason']);
    $fdvb = esc($_POST['givenfeedback']);
    $query = "INSERT INTO rejected(q_id,url,rej_date,authornum,email,registration,location,ratio,username,airline,aircraft,screener,reason,feedback) VALUES ('$theid','$photo','$date','$authornum','$email','$reg','$airport','$ratio','$name','$airline','$aircraft','$acc','$reason','$fdvb')";
    $result = mysqli_query($connection,$query);
    if($result){
        // if($feedback != ""){
        // $qu = "INSERT INTO feedback(rej_date,authornum,email,reg,airline,aircraft,location,reason,feedback,url) VALUES ('$date','$authornum','$email','$reg','$airline','$aircraft','$airport','$reason','NONE','$photo')";
        // $re = mysqli_query($connection,$qu);
        // }
        // DELETE FROM QUEUE
        if($feedback != "" && strlen($given_feedback)<5){
            $fdvb = "The screener failed to give feedback. You will receive an email with this information soon.";
            $given_feedback = "The screener failed to give feedback. You will receive an email with this information soon.";
        }
        $final = "DELETE FROM queue_photos WHERE id='$theid'";
        $finalres = mysqli_query($connection,$final);
        unlink("/home/dandemo/aeroxplorer.com/images/$url");
        if($finalres){
            // Uh OH, THE DIFFICULT EMAIL PART
            $checkQueue = "SELECT * FROM queue_photos WHERE authornum='$authornum'";
        $cQres = mysqli_query($connection,$checkQueue);
        $e = 0;
        while(mysqli_fetch_assoc($cQres)){
            $e++;
        }
        if($e == 0){
            // set mail body
            $mail_body = '<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <style>
    * {
      margin: 0;
      box-sizing: border-box;
    }
    body {
      background-color: #efefefff;
    }
    .main-node {
      background-color: #efefefff;
      font-family: \'Open Sans\', sans-serif;
      padding: 10px;
      max-width: 100vw;
    }
    fieldset:first-of-type {
      width: 80%;
      text-align: center;
      border: none;
      border-top: solid 4px green;
    }
    legend:first-of-type {
      font-size: 30px;
      padding: 0 30px;
      color: green;
    }
    fieldset:not(:first-of-type) {
      width: 80%;
      text-align: center;
      border: none;
      border-top: solid 4px red;
    }
    legend:not(:first-of-type) {
      font-size: 30px;
      padding: 0 30px;
      color: red;
    }
    .photo-acc img {
      width: 300px;
      border-radius: 5px;
      margin-right: 50px;
      display: inline-block;
      vertical-align: top;
    }
    .spec {
      position: relative;
    }
    td {
      text-align: left;
    }
    .exclamation {
      position: absolute;
      top: 1px;
      left: 1px;
      background-color: black;
      color: white;
      padding: 10px 14px;
      border-bottom-right-radius: 5px;
    }
    .view-photo {
      text-decoration: none;
      display: block;
      width: 100%;
      text-align: center;
      padding: 10px 15px;
      background-color: #0b5394ff;
      color: white;
      cursor: pointer;
      border-radius: 4px;
      margin: 10px 0;
    }
    td[colspan=2]{
      padding: 10px;
    }
    @media screen and (max-width: 1100px){
      fieldset {
        width: 90% !important;
        text-align: left;
        border-top: none;
        font-size: 1.3em !important;
      }
      .photo-acc {
        width: 100%;
      }
      .photo-acc td {
        display: block;
        width: 100%;
        text-align: center;
        margin: 10px;
      }
      legend {
        font-size: 50px;
        padding: 1px;
        text-decoration: underline;
      }
      * {
        padding: 0 !important;
      }
    }
    .socials {
      display: block;
      width: 100%;
      padding: 15px;
      justify-content: center;
      text-align: center;
      color: black;
      cursor: pointer;
    }
    .socials svg {
      color: black;
      text-decoration: none;
      cursor: pointer;
      margin: 20px;
    }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Open+Sans&family=Quicksand&display=swap" rel="stylesheet">
  </head>
  <body>
    <div class="main-node">
      <span class="socials">
        <a href="https://instagram.com/aeroxplorer"><svg xmlns="http://www.w3.org/2000/svg" width="2em" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
  <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/>
</svg></a>
<a href="https://facebook.com/theexplorerblog.fb"><svg xmlns="http://www.w3.org/2000/svg" width="2em" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
  <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
</svg></a>
<a href="https://twitter.com/aeroxplorer"><svg xmlns="http://www.w3.org/2000/svg" width="2em" fill="currentColor" class="bi bi-twitter" viewBox="0 0 16 16">
  <path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z"/>
</svg></a>
<a href="https://www.linkedin.com/company/theexplorerblog/"><svg xmlns="http://www.w3.org/2000/svg" width="2em" fill="currentColor" class="bi bi-linkedin" viewBox="0 0 16 16">
  <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225h2.401zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248-.822 0-1.359.54-1.359 1.248 0 .694.521 1.248 1.327 1.248h.016zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016a5.54 5.54 0 0 1 .016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225h2.4z"/>
</svg></a>
      </span>
Hello there! Thank you for choosing <b>AeroXplorer</b> as a platform to share your airplane photos. Our team acknowledges and supports your continued dedication toward aviation and is looking forward to more of your excellent contributions in the future.<br><br>
For questions pertaining to our photo guidelines, refer to our <a href="https://aeroxplorer.com/photo-guidelines.php">Photo Screening Guidelines</a>.
<br><br>
If you have any general questions about our screening process and team availability, visit our <a href="https://aeroxplorer.com/screening-faq.php">Photo Screening FAQ</a>.
<br><br>
If you have any further questions, comments, or suggestions that have not been addressed by the above link, please do not hesitate to submit our <a href="https://aeroxplorer.com/contact.php">contact form</a>.
<br><br>
The results of your photo submissions are below! Please note that feedback is automatically generated. If you would like a more detailed explanation of what can be fixed, you can request a human-written evaluation of your photo.
<br><br>
Kind Regards,<br>&emsp;The AeroXplorer.com Team
<br><br>
<center>
<fieldset>
    <legend>{{NUM}} Accepted Photos</legend>
  <col width="35%">
  <col width="64%">
  <table class="photo-acc">
        ';
        // check backlog
            $checkBacklog = "SELECT * FROM backlog_email WHERE authornum='$authornum' AND verdict='ACCEPT'";
            $cBres = mysqli_query($connection,$checkBacklog);
            $num = 0;
            while($in = mysqli_fetch_assoc($cBres)){
                $num++;
                $bl_airline = $in['airline'];
                $bl_aircraft = $in['aircraft'];
                $bl_reg = $in['reg'];
                $bl_id = $in['id'];
                $bl_date = $in['date_submitted'];
                $bl_airport = $in['airport'];
                $bl_url = $in['url'];
                $b_verdict = $in['verdict'];
                $bl_feat = $in['featured'];
                 $mail_body .= '<tr>
      <td class="spec">
        ';
        if($bl_feat == 1){
            $mail_body .= '<div class="exclamation">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="yellow" class="bi bi-asterisk" viewBox="0 0 16 16">
            <path d="M8 0a1 1 0 0 1 1 1v5.268l4.562-2.634a1 1 0 1 1 1 1.732L10 8l4.562 2.634a1 1 0 1 1-1 1.732L9 9.732V15a1 1 0 1 1-2 0V9.732l-4.562 2.634a1 1 0 1 1-1-1.732L6 8 1.438 5.366a1 1 0 0 1 1-1.732L7 6.268V1a1 1 0 0 1 1-1z"/>
          </svg>&nbsp;FEATURED
        </div>';
        }
        $mail_body .= '
        <img style="width: 250px" src="https://cdn.aeroxplorer.com/uploads/' . $bl_url . '" alt="' . $bl_airline . ' ' . $bl_aircraft . ' at ' . $bl_airport . '"></td>
      <td>Airline: ' . $bl_airline . '<br>Aircraft: ' . $bl_aircraft . '<br>Airport: ' . $bl_airport . '<br>Registration: ' . $bl_reg . '<br>Date screened: ' . $bl_date . '<br><a class="view-photo" href="https://aeroxplorer.com/viewprofile.php?id=' . $bl_num . '">Your Profile</a></td>
    </tr>';

                $qqqq = "DELETE FROM backlog_email WHERE id='$bl_id'";
                $rrrr = mysqli_query($connection,$qqqq);
            }
        $mail_body = str_replace("{{NUM}}",$num,$mail_body);
        // AAAND for rejected photos :( lots of work
        $checkBrej = "SELECT * FROM backlog_email WHERE authornum='$authornum' AND verdict='REJECT'";
        $checkBrejres = mysqli_query($connection,$checkBrej);
        $num = 1;
        $mail_body .= '</table>
</fieldset>
<fieldset>
  <legend style="color: red">{{NUM}} Rejected Photos</legend>
  <col width="35%">
  <col width="64%">
  <table class="photo-acc">';
        while($br = mysqli_fetch_assoc($checkBrejres)){
            $num++;
            $bl_airline = $br['airline'];
                $bl_aircraft = $br['aircraft'];
                $bl_reg = $br['reg'];
                $bl_id = $br['id'];
                $bl_date = $br['date_submitted'];
                $bl_airport = $br['airport'];
                $bl_url = $br['url'];
                $b_verdict = $br['verdict'];
                $bl_reason = $br['rej_reason'];
                $bl_feedback = $br['rej_feedback'];
                $mail_body .= '<tr>
      <td class="spec">
        <img style="width: 250px" src="https://cdn.aeroxplorer.com/uploads/' . $bl_url . '"></td>
      <td>Airline: ' . $bl_airline . '<br>Aircraft: ' . $bl_aircraft . '<br>Airport: ' . $bl_airport . '<br>Registration: ' . $bl_reg . '<br>Date screened: ' . $bl_date . '<br>Reason: ' . $bl_reason . '<br></td>
    </tr>
    <tr>
      <td colspan="2"><i>This feedback is computer-generated.</i><br><br>
        ' . $bl_feedback . '</td>
    </tr>';
                $qqqq = "DELETE FROM backlog_email WHERE id='$bl_id'";
                $rrrr = mysqli_query($connection,$qqqq);
        }
        // Add the info for the current photo
        $mail_body .= '<tr>
      <td class="spec">
        <img style="width: 250px" src="https://cdn.aeroxplorer.com/uploads/' . $url . '"></td>
      <td>Airline: ' . $airline . '<br>Aircraft: ' . $aircraft . '<br>Airport: ' . $airport . '<br>Registration: ' . $reg . '<br>Date screened: ' . $date . '<br>Reason: ' . $reason . '<br></td>
    </tr>
    <tr>
      <td colspan="2"><i>This feedback is computer-generated. <a href="https://aeroxplorer.com/dashboard/portfolio.php">Request Human Feedback (Dashboard)</a>.</i><br><br>
        ' . $given_feedback . '</td>
    </tr>';
        $mail_body = str_replace("{{NUM}}",$num,$mail_body);
        $mail_body .= '
        </table>
</fieldset>
</div>
  </body>
</html>';

            // Send mail
            $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: screening@aeroxplorer.com";
$mail = mail($email,"AeroXplorer Photo Screening Results",$mail_body,$headers);
if($mail){
            header("location: screenphotos.php");
            // Delete from backlog
        }
            // Delete from backlog if success
        } else {
            // add this photo to the backlog, continue with normal work
            $mynum = $_SESSION['authornum'];
            $date = date("m/d/Y");
            $addToBack = "INSERT INTO backlog_email(authornum,verdict,url,airline,aircraft,airport,screener_id,email,date_submitted,reg,rej_reason) VALUES ('$authornum','REJECT','$url','$airline','$aircraft','$airport','$mynum','$email','$date','$reg','$reason')";
            $addItToBack = mysqli_query($connection,$addToBack);
            if($addItToBack){
                header("location: screenphotos.php");
            }
        }
        sendMessage($authornum,"Your photo ($airline $aircraft) has been rejected.");
        }
    } else {
        die("SOMETHING WENT WRONG. PLEASE CONTACT ADMIN. ERROR #3");
    }
    // ASK FOR FEEDBACK
} else {
  $reason = esc($_POST['reason']);
  $aircraft = esc($_POST['aircraft']);
  $airline = esc($_POST['airline']);
  $airport = esc($_POST['airport']);
  $reg = esc($_POST['registration']);
  if($scnum == 1){
    $query = "UPDATE queue_photos SET screener_verdict='REJECT',sc1reason='$reason',airline='$airline',aircraft='$aircraft',registration='$reg',location='$airport' WHERE id='$qqqid'";
  } else {
    $query = "UPDATE queue_photos SET screener2_verdict='REJECT',thirdreview='11111',sc2reason='$reason',airline='$airline',aircraft='$aircraft',registration='$reg',location='$airport' WHERE id='$qqqid'";
  }
  $result = mysqli_query($connection,$query);
  if($result){
    header("location: screenphotos.php");
  }
}
}

// ADD SMTH TO MAKE SURE THERE ARE NO DUPLICATE UPLOADS???


// IF PHOTO IS NOT BEING SCREENED
$mynum = $_SESSION['authornum'];
$alerts = 0;
$connection = mysqli_connect('localhost','dandemo_main','','dandemo_queue','3306');
if(!isset($_SESSION['username'])){
  header("location: index.php");
}
if(!hasAvailable("screening")){
    header("location: index.php");
}
// ONLOAD, NOTIFY THAT SCREENING IS TAKEN AT THE TIME & DATE
//$pullid = (int) file_get_contents(__DIR__ . "nextqueue.php?id=$mynum");
// file get contents isn't working
//
$ch = curl_init();
if(!isset($_COOKIE['screener_offset'])){
    setcookie("screener_offset",0,time()+(60*60*8));
    $this_offset = 0;
} else {
    $this_offset = (int) $_COOKIE['screener_offset'];
}
//The url you wish to send the POST request to
$curlurl = "https://aeroxplorer.com/dashboard/nextqueue.php?id=" . $mynum . "&offset=" . $this_offset;
//The data you want to send via POST
$fields = [
    'info' => 'burner'
];
//url-ify the data for the POST
$fields_string = http_build_query($fields);
//die($fields_string);
//open connection
$ch = curl_init();
        
//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $curlurl);
curl_setopt($ch,CURLOPT_POST, true);
curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        
//So that curl_exec returns the contents of the cURL; rather than echoing it
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

//execute post
$pullid = curl_exec($ch);
// end fallback
//
$init = "SELECT * FROM queue_photos WHERE id='$pullid'";
$result = mysqli_query($connection,$init);
$info = mysqli_fetch_assoc($result);
if($info['photo_no_wm'] == ""){
    $url = $info['url'];
} else {
    $url = $info['photo_no_wm'];
}
$location = $info['location'];
$reg = $info['registration'];
$airline = $info['airline'];
$aircraft = $info['aircraft'];
$username = $info['username'];
$takendate = $info['takendate'];
$information_accuracy = explode(".",checkInfo($airline,$aircraft,$reg));
// method . airline . aircraft
$authornum = $info['authornum'];
$name = $info['name'];
$email = $info['email'];
$feedback = $info['feedback'];
if(str_ireplace("duplicate","",$info['sc1reason']) != $info['sc1reason'] || str_ireplace("duplicate","",$info['sc2reason']) != $info['sc2reason']){
  $alerts++;
  $duplicatepic = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-layers" viewBox="0 0 16 16">
  <path d="M8.235 1.559a.5.5 0 0 0-.47 0l-7.5 4a.5.5 0 0 0 0 .882L3.188 8 .264 9.559a.5.5 0 0 0 0 .882l7.5 4a.5.5 0 0 0 .47 0l7.5-4a.5.5 0 0 0 0-.882L12.813 8l2.922-1.559a.5.5 0 0 0 0-.882l-7.5-4zm3.515 7.008L14.438 10 8 13.433 1.562 10 4.25 8.567l3.515 1.874a.5.5 0 0 0 .47 0l3.515-1.874zM8 9.433L1.562 6 8 2.567 14.438 6 8 9.433z"/>
</svg> This photo is a duplicate.';
} else {
  $duplicatepic = "";
}
$q_id = $info['id'];
$specliv = $info['special_liv'];
if(empty($q_id) && empty($url)){
    $photos = false;
} else {
    $photos = true;
}
$first = $info['screener'];
$second = $info['screener2'];
$third = $info['screener3'];
$myscnum = "";
if($first == $_SESSION['authornum'] || $first == 0){
    $myscnum = "";
} elseif($second == $_SESSION['authornum'] || $second == 0){
    $myscnum = "2";
} else {
    $myscnum = "3";
}

$claim = "UPDATE queue_photos SET screener{$myscnum}='$mynum' WHERE id='$q_id'";
$claimres = mysqli_query($connection,$claim);
if(!$claimres){
    $claimed = false;
    $alerts++;
} else {
    $claimed = true;
}
// MAKE SMTH FOR IF THERE ARE NO AVAILABLE PHOTOS TO SCREEN
?>
<html lang="en">
<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screen Photos | AeroXplorer</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="shortcut icon" href="../favicon.png">
    <style>
    .screen-photo {
        width: 100%;
        font-family: 'Open Sans',sans-serif;
    }
    .aircraft-info {
        background-color: white;
        color: black;
        height: 50px;
        text-align: center;
    }
    input[type=submit], input[type=button]{
        width: 100%;
    }
    .aircraft-info input[type=text] {
        padding: 10px;
        border: none;
        border-bottom: solid 2px black;
    }
    .actions {
        background-color: white;
        color: black;
        height: 100%;
        text-align: center;
    }
    .actions div {
        padding: 15px 30px;
    }
    .panel-title {
        width: 100%;
        margin: 0;
        color: white;
        background-color: black;
        text-align: center;
        font-family: 'Candal';
    }
    .accept, .reject {
        display: block;
    }
    .tool-avail {
        width: 95%;
        color: white;
        margin-top: 15px;
        border-radius: 5px;
        padding-top: 10px;
        padding-bottom: 10px;
        cursor: pointer;
        background-color: black;
        color: white;
        display: block;
        text-decoration: none;
    }
    .finish {
        width: 95%;
        color: white;
        border-radius: 5px;
        padding-top: 10px;
        padding-bottom: 10px;
        cursor: pointer;
        margin-top: 15px;
        background-color: red;
        color: white;
        display: block;
    }
    .tool-avail:hover, .finish:hover {
        opacity: 0.8;
    }
    .main-att {
        width: 100%;
    }
    #badge-open {
        position: fixed;
        padding: 15px 20px;
        background-color: black;
        color: white;
        bottom: 0;
        left: 0;
        z-index: 50;
        font-size: 1.5em;
        cursor: pointer;
        border-top-right-radius: 5px;
        font-family: 'Open Sans',sans-serif;
    }
    #terminalheader {
        text-align: right;
        width: 100%;
        background-color: red;
        color: white;
        cursor: move;
    }
    #terminalheader a:link, #terminalheader a:hover{
        color: white;
        cursor: pointer;
        border-radius: 50%;
        width: 10px;
        height: 10px;
        margin-right: 15px;
        text-decoration: none;
    }
    #terminalheader a:hover {
        background-color: white;
        color: red;
    }
    #terminal {
        width: 250px;
        height: 250px;
        padding: 0;
        position: absolute;
        left: 10%;
        top: 20%;
        z-index: 25;
        display: none;
    }
    #term-bott {
        padding: 15px;
        background-color: black;
        color: green;
        text-align: left;
        width: 100%;
        overflow-x:hidden;
        overflow-y: scroll;
        height: 100%;
        font-family: 'Courier New', monospace;
        font-size: <?php if($_SESSION['authornum'] == 191){echo "22";} else { echo "15";} ?>px;
    }
    #queue-list {
        z-index: 25;
        top: 0;
        width: 100%;
        padding: 15px;
        min-height: 300px;
        position: absolute;
        background-color: white;
        color: black;
        border-bottom: solid 5px black;
        display: none;
    }
    #queue-list img {
        height: 150px;
    }
    .badge-list {
        width: 50%;
        background-color: white;
        color: black;
        position: fixed;
        bottom: 0;
        left: -101%;
        height: 80%;
        border-right: solid 3px black;
        z-index: 90;
        transition: 0.5s;
        padding: 50px;
    }
    .duplicate-pic {
        width: 92%;
        background-color: orange;
        color: white;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        margin: 4%;
    }
    #command-ar {
      width: 85%;
      border: none;
      background: none;
      color: green;
      outline: none;
      font-family: 'Courier New', monospace;
      font-size: 1em;
    }
    .accept, .reject {
        padding: 10px 15px;
        color: white;
        font-weight: bold;
        border-radius: 5px;
        width: 100%;
        cursor: pointer;
        border: solid 2px black;
    }
    .accept {
        background-color: green;
    }
    .reject {
        background-color: red;
    }
    @keyframes duplicate {
      0% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.1);
      }
      100% {
        transform: scale(1);
      }
    }
    .alal {
      color: red;
      text-decoration: none;
      transition: 0.2s;
      animation: 1s duplicate;
      animation-iteration-count: infinite;
      animation-fill-mode: both;
      animation-timing-function: linear;
      position: relative;
    }
    .next-up {
        position: relative;
        display: inline-block;
    }
    .next-up img {
        height: 150px;
    }
    .photographer-name-inset {
        position: absolute;
        bottom: 0;
        left: 0;
        padding: 5px;
        color: white;
        font-weight: bold;
        border-top-right-radius: 10px;
        background: rgba(0,0,0,0.4);
    }
    .new-user {
        padding: 5px;
        font-size: 13px;
        background-color: black;
        color: white;
        font-family: 'Quicksand', sans-serif;
        border-radius: 5px;
        margin-left: 6px;
        cursor: default;
        text-decoration: none;
    }
    </style>
    <link href="https://fonts.googleapis.com/css?family=Candal|Open+Sans&display=swap" rel="stylesheet">
    <script>
    function rejection() {
    var reason = window.prompt("Enter rejection reason \n Must be more than 2 characters \n Please be somewhat descriptive.");
    while(reason.length < 2){
        reason = window.prompt("YOU DIDN'T MEET THE REQUIREMENTS \n ++++++++++++++++++++++++++ \n Enter rejection reason \n Must be more than 2 characters \n Please be somewhat descriptive.");
    }
    document.getElementById("reject").value = "TRUE";
    document.getElementById("form-reason").value = reason;
    document.getElementById("main-form").submit();
}
function acceptphoto() {
    document.getElementById("accept").value = "TRUE";
    document.getElementById("main-form").submit();
}
    </script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/exif-js/2.3.0/exif.min.js"></script>
</head>
<body onload="calculateRatio()">
<div id="terminal">
<div id="terminalheader">
<a href="javascript:closeTerminal()">&times;</a>
</div>
<div id="term-bott">
&copy; AeroXplorer.<br>
<span id="assets">Loading Assets...</span><br>
Hello, <?php echo $_SESSION['f_name'] . "(#" . $_SESSION['authornum'] . ")"; ?><?php if(strlen($duplicatepic) > 0){echo "<br>" . $duplicatepic;} ?><?php if(strlen($maxoffset)>0){ echo "<br>" . $maxoffset;} ?><br>QID#<?php echo $q_id; ?><br>Correction via <?php echo $information_accuracy[0]; ?><br>
<?php
if($photos == false){
    echo "No photos to screen.<br>";
}
if($claimed == false){
    echo "Failed to Claim.<br>Session unable to start.<br>Please refresh.<br>";
}
?>
> <span id="typer-area"></span><input id="command-ar" type="text" spellcheck="false" autofocus>
</div>
</div>
<form action="" method="post" id="main-form">
<div class="badge-list">
<br>
<h2 style="font-family: 'Candal'">Available Badges&emsp;<a href="javascript:closeBadges()">(&times; CLOSE)</a></h2>
<br><br>
<?php
$badges = mysqli_connect('localhost','dandemo_groups','','dandemo_badges','3306');
$q1 = "SELECT * FROM badge_list WHERE ((tags LIKE '%$airline%' OR tags LIKE '%$aircraft%' OR tags LIKE '%$reg%' OR tags LIKE '%$location%') OR (description LIKE '%$airline%' OR description LIKE '%$aircraft%' OR description LIKE '%$reg%' OR description LIKE '%$location%') OR (name LIKE '%$airline%' OR name LIKE '%$aircraft%' OR name LIKE '%$reg%' OR name LIKE '%$location%')) AND NOT tags LIKE '%&&&&%'";
$r1 = mysqli_query($badges,$q1);
$x = 0;
while($row = mysqli_fetch_assoc($r1)){
    $image = $row['photo'];
    $title = $row['name'];
    $desc = $row['description'];
    $att = $row['att'];
    $id = $row['id'];
    $date = $row['lt_end'];
    echo "<script>console.log('" . substr($date,0,2) . "," . substr($date,3,2) . "," . substr($date,6,4) . "');</script>";
    if($att == "LIMITED TIME"){
        $today_m = date("m");
        $today_y = date("Y");
        $today_d = date("d");
        if(substr($date,0,2) >= $today_m && substr($date,3,2) >= $today_d && substr($date,6,4) >= $today_y){
            $showbadge = true;
        } else {
            $showbadge = false;
        }
    } elseif($att == "UNATTAINABLE"){
        $showbadge = false;
    } else {
        $showbadge = true;
    }
    //
    if($showbadge == true){
        echo "<label><input type='checkbox' name='badge[]' value='$id'><img src='https://cdn.aeroxplorer.com/badges/$image' title='{$title}: {$desc}' style='width: 75px'></label>";
        $x++;
    }
}
if($specliv == "TRUE"){
        $x = $x+3;
        echo "<label><input type='checkbox' name='badge[]' value='87'><img src='https://cdn.aeroxplorer.com/badges/oneworld.png' title='OneWorld: Upload a photo of a plane painted in the OneWorld livery' style='width: 75px'></label>
        <label><input type='checkbox' name='badge[]' value='88'><img src='https://cdn.aeroxplorer.com/badges/skyteam.png' title='SkyTean: Upload a photo of a plane painted in the SkyTeam livery' style='width: 75px'></label>
        <label><input type='checkbox' name='badge[]' value='89'><img src='https://cdn.aeroxplorer.com/badges/star_alliance.png' title='Star Alliance: Upload a photo of a plane painted in the Star Alliance livery' style='width: 75px'></label>
        ";
    }
?>
<!-- <label><input type='checkbox' name='badge[]' value='46'><img src='../badges/nad.png' title='National Aviation Day' style='width: 75px'></label> -->
</div>
<span id="badge-open">
BADGES (<?php echo $x; ?>)
</span>
<?php
if($x > 0){
    echo "<script>window.alert('There are $x applicable badges.');</script>";
}
?>
<table class="screen-photo">
<col width="80%">
<col width="19%">
<tr>
<td class="aircraft-info">
<input type="hidden" name="ratio" id="ratiohere-form" value="">
<span id="ratiohere">#:#</span> |
&nbsp; <label onclick="playPeppa()"><input type="checkbox" name="FEAT" value="TRUE"> FEAT</label>
<input type="text" title="AIRLINE" placeholder="AIRLINE" name="airline" id="airline" autocomplete="off" size="15" spellcheck="false" style="border-bottom: solid 4px <?php echo $information_accuracy[1]; ?>" value="<?php echo $airline; ?>" required>
<input type="text" title="AIRCRAFT" placeholder="AIRCRAFT" name="aircraft" id="aircraft" autocomplete="off" size="14" spellcheck="false" style="border-bottom: solid 4px <?php echo $information_accuracy[2]; ?>" value="<?php echo $aircraft; ?>" required>
<input type="text" title="REGISTRATION" placeholder="REGISTRATION" name="registration" id="reg" autocomplete="off" size="7" spellcheck="false" style="border-bottom: solid 4px black" value="<?php echo $reg; ?>" required>
<input type="text" title="LOCATION/AIRPORT" placeholder="LOCATION/AIRPORT" name="airport" id="airport" autocomplete="off" size="5" spellcheck="false" style="border-bottom: solid 4px black" value="<?php echo $location; ?>" required>
Photographer: <a href="../viewprofile.php?id=<?php echo $authornum; ?>" target="_blank"><?php echo $name; if(isNew($authornum)){ echo '<span class="new-user">NEW USER</span>';} ?></a>&emsp;|&emsp;
<input type="hidden" name="authornum" id="userNum" value="<?php echo $authornum; ?>">
<input type="hidden" name="name" value="<?php echo $name; ?>">
<input type="hidden" name="email" value="<?php echo $email; ?>">
<input type="hidden" name="imgurl" value="<?php echo $url; ?>">
<input type="hidden" name="foto" value="<?php echo $url; ?>">
<input type="hidden" name="queueid" id="queueid" value="<?php echo $q_id; ?>">
<input type="hidden" name="feedback" value="<?php echo $feedback; ?>">
<input type="hidden" name="takendate" value="<?php echo $takendate; ?>">
<input type="hidden" name="reason" id="form-reason" value="">
<input type="hidden" name="reject" id="reject" value="">
<input type="hidden" name="accept" id="accept" value="">
Session: <?php if($claimed == true){ echo "🟢"; } else { echo "🔴"; } ?>
</td>
<td rowspan="2" class="actions">
    <?php
    $dups = duplicateTo($reg,$authornum);
    if($dups != "NONE"){
        echo '<div class="duplicate-pic">This photo may be a duplicate to <a href="https://aeroxplorer.com/viewphoto.php?id=' . $dups . '" target="_blank">#' . $dups . '</a> and potentially others.</div>';
    }
    ?>
    <?php
    if(((int) $myscnum == 2 || (int) $myscnum == 3) && $feedback == true){
        echo 'Feedback:<br><textarea name="givenfeedback" id="zfdb" placeholder="Type your feedback here..." style="width: 100%; height: 200px"></textarea><br>Populate:<select onchange="go()" id="ap-reason">
        <option selected disabled>Select One</option>
        <option value="0">Backlit/Toplit</option>
        <option value="1">Bad Framing</option>
        <option value="2">Contrast</option>
        <option value="3">Duplicate</option>
        <option value="4">Dust</option>
        <option value="5">Exposure</option>
        <option value="6">External Watermark</option>
        <option value="7">Heat Haze</option>
        <option value="8">Horizon</option>
        <option value="9">Motion blur</option>
        <option value="10">Noise</option>
        <option value="11">Soft</option>
        <option value="12">Vignetting</option>
        <option value="13">Overexposed</option>
        <option value="14">Obstruction</option>
        <option value="15">Aberration</option>
        </select>';
    }
    ?>
<div class="panel-title">
RESOURCES
</div>
<br>
<a class="tool-avail" href="https://docs.google.com/document/d/1f392AsBz4nmUzBXiIgNKBSEMZviI6JOgVR7QW_oFDNI/edit?usp=sharing" target="_blank">Screening Information</a>
<a class="tool-avail" href="https://docs.google.com/document/d/1NsZgPQI33DunQM8kMPRp3sIt2TkVO23zYzbUm3QzAtY/edit?usp=sharing" target="_blank">Aircraft Reference</a>
<br>
<div class="panel-title">
ACTIONS
</div>
<br>
<span id="maybe"></span>
<input type="submit" class="accept" onclick="acceptphoto()" value="ACCEPT">&nbsp;
<input type="button" class="reject" onclick="rejection()" value="REJECT">
</form>
<br>
<div class="panel-title">
TOOLS
</div>
<br>
<div class="tools-list">
<a class="tool-avail" href="javascript:horizon()">HORIZON</a>
<a class="tool-avail" href="javascript:center()">CENTER</a>
<a class="tool-avail" href="javascript:viewQueue()">VIEW QUEUE</a>
<a class="tool-avail" href="javascript:terminal()">TERMINAL &bull; <span class="alal"><?php echo $alerts; ?></span></a>
<a class="tool-avail" href="javascript:skipQueue()">SKIP <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-skip-forward-fill" viewBox="0 0 16 16">
  <path d="M15.5 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V8.753l-6.267 3.636c-.54.313-1.233-.066-1.233-.697v-2.94l-6.267 3.636C.693 12.703 0 12.324 0 11.693V4.308c0-.63.693-1.01 1.233-.696L7.5 7.248v-2.94c0-.63.693-1.01 1.233-.696L15 7.248V4a.5.5 0 0 1 .5-.5z"/>
</svg></a>
<a class="finish" href="javascript:finish()">FINISH SESSION</a>
</div>
</td>
</tr>
<tr>
<td style="position: relative">
<!-- PHOTO AND BADGE OPENER -->
<div id="queue-list">
<h2 style="font-family: 'Candal'">Next in Queue <a href="javascript:closeQueue()">(&times; CLOSE)</a></h2>
<br>
<?php
$connection = mysqli_connect('localhost','dandemo_main','','dandemo_queue','3306');
$elpp = $_SESSION['authornum'];
$query = "SELECT * FROM `queue_photos` WHERE authornum <> '$elpp' AND ((NOT screener='$elpp' OR screener IS NULL) AND (NOT screener2='$elpp' OR screener2 IS NULL)) OR (screener2 <> '10' AND screener <> '10' AND (screener3 <> '$elpp' OR (screener3 IS NULL AND thirdreview='11111'))) ORDER BY id ASC LIMIT 12";
$result = mysqli_query($connection,$query);
while($row = mysqli_fetch_assoc($result)){
    $photo_url = $row['url'];
    $idid = $row['id'];
    $photographer = $row['name'];
    echo '<div class="next-up">
    <img src="https://cdn.aeroxplorer.com/uploads/' . $photo_url . '">
    <span class="photographer-name-inset">' . $photographer . '</span>
    </div>';
}
?>
</div>
<?php
if($url == ""){
    if($_COOKIE['screener_offset'] > 0){
        echo "No more photos in your queue, pulling from other queue. Refresh to continue.";
    } else {
        echo 'No more photos to screen.';
    }
    setcookie("screener_offset",0,time()+(60*60*8));
} else {
    echo '<img src="https://cdn.aeroxplorer.com/uploads/' . $url . '" onerror="window.alert(\'Photo failed to load.\')" class="main-att">';
}
?>
</td>
<td></td>
</tr>
</table>
<audio id="peppa-pig-audio">
  <source src="https://aeroxplorer.com/testing/peppapig.mp3?time=<?php echo time(); ?>" type="audio/mp3">
</audio>
<!-- FORMS FOR JS BUTTONS -->
<?php
if(isset($_POST['session-end'])){
    $mynum = $_SESSION['authornum'];
    $qid = (int) $_POST['session-end'];
    if($myscnum == ""){
      $q = "UPDATE queue_photos SET screener='NONE' WHERE id='$qid'";
    } elseif($myscnum == "2"){
      $q = "UPDATE queue_photos SET screener2='0' WHERE id='$qid'";
    } else {
      $q = "UPDATE queue_photos SET screener3='0' WHERE id='$qid'";
    }
    $r = mysqli_query($connection,$q);
    $_SESSION['photos'] = 0;
    if($r){
        header("location: index.php");
    } else {
        echo '<script>window.alert("FAILED TO END SESSION. PLEASE NOTIFY ADMIN");</script>';
    }
}
?>
<form action="" method="POST" id="finish-session">
<input type="hidden" name="session-end" id="session-end" value="TRUE">
</form>
<form action="" method="POST" id="skipqueue">
    <input type="hidden" name="skipscreen" value="<?php echo $q_id; ?>">
</form>
<script>
    var rejreasons = ["This photo is backlit. Backlit is when the light source is coming from behind the object (in this case, the plane). To improve, try spotting from a different location where the sun will be behind you or wait for a time of day where the sun is behind you. There are also some editing tricks to help. First bring your exposure up a tad, next use shadow and highlights to find a sweet spot! Backlit is acceptable as long as it looks good. Thank you for submitting to AeroXplorer.","Cool shot! Unfortunately, there seems to be tint in your photo. We suggest reducing the __(color)__ tint while editing so the photo's coloration is more balanced. Thank you for submitting to AeroXplorer.","It seems that the contrast is too __(high/low)__ in your photo. You can fix this by __(give method based on high or low)__. Thank you for submtting to AeroXplorer.","Nice shot! Your photo was rejected due to this being a duplicate upload. There isn't any feedback to duplicate uploads except only upload the photo once. Thank you for submitting to AeroXplorer.","You have a dust spot __(dust spot location)__. Try using your healing tool while editing, and make sure you have your sensor on your camera cleaned. You can watch a video about how to do this here: https://www.youtube.com/watch?v=FW_Hm5wuxfw. Thank you for submitting to AeroXplorer.","This photo seems to be very dark. To improve, in your preferred editing software, increase the exposure. Thank you for submitting to AeroXplorer.","We treat watermarks as foreign objects. If you decide to use a foreign watermark(not from AeroXplorer), please ensure it is not obstructing the aircraft.","There seems to be a lot of heat haze in this photo. If you look closely at the edges of the object in focus, you can see wavy lines(heat haze). To improve, try to spot for a different location or during a cooler or a cloudy day. Photographing the aircraft from a closer distance will also help. Thank you for submitting to AeroXplorer.","Nice shot! Your photo was rejected by the screener because the horizon is unlevel. You can fix this by rotating the image so that the horizon is parallel to the horizontal edges of the screen. You can watch our tutorial here: https://www.youtube.com/watch?v=jKtFxYwj4aU. Thank you for submitting to AeroXplorer.","The plane in this photo seems to be out of focus. This is visible __(explain where visible)__. To improve, make sure that your camera is focused on the plane before taking a picture. You can also try shooting with a higher shutter speed. Thank you for submitting to AeroXplorer.","This photo seems to be grainy. This is because __(explain the source of the grain)__. To fix, __(what can they do to fix?)__. Thanks for submitting to AeroXplorer.","The plane seems to be a little soft in this photo. To improve, try to increase the contrast and clarity of the image in the photo editing software of your choice. Increasing the sharpness of the photo may also fix the issue. Thank you for submitting to AeroXplorer.","A lightening or darkening of the surroundings in a photo, otherwise known as vignetting, is not acceptable. Usually vignetting is caused by incompatible camera settings or by post-processing implementations. You can fix this by removing it in your post-processing software(if it was manually added) or modifying your camera settings to reduce shadowing by lens hoods. Thank you for submitting to AeroXplorer.","It seems that this picture is overexposed. To improve, try to, in whatever editing software you use, reduce the exposure(brightness) of this image. Thank you for submitting to AeroXplorer.","Although we allow partial obstructions to the landing gears and engines, this is not allowed. Thank you for submitting to AeroXplorer.","It seems that there is chromatic aberration on the top edge of the plane. In your editing software, you can fix this by reducing the amount of purple fringing. Here is a video to help :) https://www.youtube.com/watch?v=84-1ndaWnT4. Thanks for submitting to AeroXplorer."];
    function go(){
        document.getElementById("zfdb").value += rejreasons[document.getElementById("ap-reason").value];
    }
var xhr = new XMLHttpRequest();
xhr.onload = function() {
  var info = this.responseText;
  console.log(info);
  document.getElementById("typer-area").innerHTML += info + "<br>> ";
}
if(screen.width < 700){
  document.body.innerHTML = "Your device width is too small to screen photos. Please use a computer or large tablet. Width: " + screen.width;
}
function skipQueue(){
    document.getElementById("skipqueue").submit();
}
function playPeppa(){
    document.getElementById("peppa-pig-audio").play();
}
function processCommand() {
  var command = document.getElementById("command-ar").value;
  document.getElementById("command-ar").value = "";
  var box = document.getElementById("typer-area");
  // COMMANDS
  if(command == "search"){
    box.innerHTML += "Searching...<br>";
    // DISPLAYS AIRCRAFT INFO FROM THE WEB
    xhr.open("POST","screening-ajax.php");
    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    var info = "reg=" + document.getElementById("reg").value + "&searchType=search";
    xhr.send(info);
  }
  if(command == "match"){
    box.innerHTML += "Matching...<br>";
    // DISPLAYS PHOTOS OF THE SAME AIRCRAFT BY THE USER
    xhr.open("POST","screening-ajax.php");
    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    var info = "reg=" + document.getElementById("reg").value + "&photographer=" + document.getElementById("userNum").value + "&searchType=match";
    xhr.send(info);
  }
  if(command == "exists"){
    box.innerHTML += "Searching...<br>";
    // DISPLAYS ALL INFORMATION IF EXISTS
    xhr.open("POST","screening-ajax.php");
    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    var info = "reg=" + document.getElementById("reg").value + "&searchType=exists";
    xhr.send(info);
  }
  if(command.split(" ")[0] == "verify"){
    box.innerHTML += "Searching...<br>";
    xhr.open("POST","../ajax.php");
    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    var info = "name=" + command.replace(command.split(" ")[0] + " ","");
    console.log(info);
    xhr.send(info);
  }
  if(command.split(" ")[0] == "open"){
      var photonum = command.split(" ")[1].trim();
      window.open("https://aeroxplorer.com/viewphoto.php?id=" + photonum);
  }
  if(command == "stats"){
    box.innerHTML += "YOUR STATS:<br>MUST HAVE 1+<br>";
    box.innerHTML += "TIME:" + "<?php echo floor((time() - $_SESSION['start'])/60) . 'm ' . floor(time() - $_SESSION['start']) - floor((time() - $_SESSION['start'])/60) . 's' ?>" + "<br># SCR: <?php echo $_SESSION['photos']; ?><br>";
  }
  if(command == "exif"){
    box.innerHTML += "Extracting EXIF...<br>";
    EXIF.getData(document.querySelector(".main-att"), function() {
      var myData = this;
      box.innerHTML += "<details><summary>EXIF GROUP</summary><br>Camera: " + myData.exifdata.Model + "<br>Software: " + myData.exifdata.Software + "<br>Flash: " + myData.exifdata.Flash + "<br>F: " + parseFloat(myData.exifdata.FNumber.numerator / myData.exifdata.FNumber.numerator) + "<br>White Balance: " + myData.exifdata.WhiteBalance + "</details><br>> ";
    });
  }
  if(command == "identify"){
    function ConvertDMSToDD(degrees, minutes, seconds, direction) {

    var dd = degrees + (minutes/60) + (seconds/3600);

    if (direction == "S" || direction == "W") {
        dd = dd * -1;
    }

    return dd;
}
    EXIF.getData(document.querySelector(".main-att"), function() {
      var myData = this;
      if(myData.exifdata.hasOwnProperty("GPSLatitude")){
      var latDegree = myData.exifdata.GPSLatitude[0].numerator;
      var latMinute = myData.exifdata.GPSLatitude[1].numerator;
      var latSecond = myData.exifdata.GPSLatitude[2].numerator;
      var latDirection = myData.exifdata.GPSLatitudeRef;
      var latFinal = ConvertDMSToDD(latDegree, latMinute, latSecond, latDirection);
      // Calculate longitude decimal
      var lonDegree = myData.exifdata.GPSLongitude[0].numerator;
      var lonMinute = myData.exifdata.GPSLongitude[1].numerator;
      var lonSecond = myData.exifdata.GPSLongitude[2].numerator;
      var lonDirection = myData.exifdata.GPSLongitudeRef;

      var lonFinal = ConvertDMSToDD(lonDegree, lonMinute, lonSecond, lonDirection);
    }
    var artist, copyright;
    if(myData.exifdata.hasOwnProperty("Artist")){
      artist = myData.exifdata.Artist;
    } else {
      artist = "UNKNOWN";
    }
    if(myData.exifdata.hasOwnProperty("Copyright")){
      copyright = myData.exifdata.Copyright;
    } else {
      copyright = "UNKNOWN";
    }
      box.innerHTML += "<details><summary>IDENTIFICATION</summary>" + "<br>Name: " + artist + "<br>Copyright: " + copyright + "<br>Coords: " + latFinal + ", " + lonFinal + "</details><br>> ";
    });
  }
  if(command == "clear"){
    box.innerHTML = "clear<br>";
  }
  box.innerHTML += "> ";
}
document.getElementById("command-ar").onkeyup = function(e) {
  e.preventDefault();
  if(e.keyCode == 13){
    document.getElementById("typer-area").innerHTML += document.getElementById("command-ar").value + "<br>";
    processCommand();
  }
}
function calculateRatio() {
    // SIDE NOTE: OPEN TERMINAL IF NEEDED
    if(localStorage.getItem("terminalState") == 1){
        document.getElementById("terminal").style.display = "block";
    }
    document.getElementById("assets").innerHTML = "Assets Loaded.";
    var targetImg = document.getElementsByClassName("main-att")[0];
    var width = targetImg.width;
    var height = targetImg.height;
    // CALCULATION TIME!!
    var ratioHeight = Math.round((16/width)*height);
    var aspectRatio = "16:" + ratioHeight;
    // SET IT
    document.getElementById("ratiohere").innerHTML = aspectRatio;
    document.getElementById("ratiohere-form").value = aspectRatio;
}
function closeBadges() {
    document.getElementsByClassName("badge-list")[0].style.left = "-101%";
}
document.getElementById("badge-open").onclick = function() {
    // OPEN BADGE PANE
    document.getElementsByClassName("badge-list")[0].style.left = "0";
}
function closeTerminal() {
    // CLOSE THE TERMINAL
    document.getElementById("terminal").style.display = "none";
    localStorage.setItem("terminalState",0);
}
function horizon() {
    // OPEN PANE
    var img = document.getElementsByClassName("main-att")[0].src;
    var filePath = img.substring(img.indexOf("../images/")+36);
    window.open("photo-grid.php?img=" + filePath + "&show=horizon","","width=800,height=800");
}
function center() {
    // OPEN PANE
    var img = document.getElementsByClassName("main-att")[0].src;
    var filePath = img.substring(img.indexOf("../images/")+36);
    window.open("photo-grid.php?img=" + filePath + "&show=center","","width=800,height=800");
}
function viewQueue() {
    // OPEN PANE
    document.getElementById("queue-list").style.display = "block";
}
function closeQueue() {
    document.getElementById("queue-list").style.display = "none";
}
function terminal() {
    // OPEN PANE
    document.getElementById("terminal").style.display = "block";
    document.getElementById("command-ar").focus();
    localStorage.setItem("terminalState",1);
}
function finish() {
    // END SESSION
    // CHANGE ALL SCREENER TABLE VALUES TO NONE SO OTHERS CAN SCREEN
    document.getElementById("session-end").value = document.getElementById("queueid").value;
    document.getElementById("finish-session").submit();
}
// CONTEXT MENU

document.getElementsByClassName("main-att")[0].addEventListener( "contextmenu", function(e) {
    e.preventDefault();
    if(e.ctrlKey == true){
        window.open(document.getElementsByClassName("main-att")[0].src);
    }
});

/*

THIS NEXT PART IS TO MAKE THE TERMINAL DRAGGABLE

*/
// Make the DIV element draggable:
dragElement(document.getElementById("terminal"));

function dragElement(elmnt) {
  var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
  if (document.getElementById(elmnt.id + "header")) {
    // if present, the header is where you move the DIV from:
    document.getElementById(elmnt.id + "header").onmousedown = dragMouseDown;
  } else {
    // otherwise, move the DIV from anywhere inside the DIV:
    elmnt.onmousedown = dragMouseDown;
  }

  function dragMouseDown(e) {
    e = e || window.event;
    e.preventDefault();
    // get the mouse cursor position at startup:
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmouseup = closeDragElement;
    // call a function whenever the cursor moves:
    document.onmousemove = elementDrag;
  }

  function elementDrag(e) {
    e = e || window.event;
    e.preventDefault();
    // calculate the new cursor position:
    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;
    // set the element's new position:
    elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
    elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
  }

  function closeDragElement() {
    // stop moving when mouse button is released:
    document.onmouseup = null;
    document.onmousemove = null;
  }
}
</script>
</body>
</html>
