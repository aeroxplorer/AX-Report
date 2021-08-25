<?php
session_start();
$connection = mysqli_connect('localhost','dandemo_main','','dandemo_queue','3306');
$posts = mysqli_connect('localhost','dandemo_main','','dandemo_posts','3306');
$users = mysqli_connect('localhost','dandemo_users','','dandemo_userinfo','3306');
if(isset($_POST['user']) && (int) $_POST['user'] != 0){
    $user = (int) $_POST['user'];
} else {
    if($_SESSION['authornum'] != 10){
        die();
    } else {
        $user = (int) $_GET['user'];
    }
}
//
$start_time = 0;
$start_date = 0;
$today = date("m/d/Y");
$totime = time();
$accuracy = 0;
$consistency = 0;
//
$screened = [];
$dates = [];
// Get recently-screened photos
$query = "SELECT * FROM screened_pics ORDER BY id ASC";
$result = mysqli_query($connection,$query);
$x=0;
while($row = mysqli_fetch_assoc($result)){
    $x++;
    if($x == 1){
        $start_time = $row['time_s'];
        $start_date = $row['date_s'];
    }
    //
    if($row['screener'] == $user){
        array_push($screened,Array($row['queue_id'],$row['decision'],$row['time_s'],$row['date_s'],$row['id']));
        if(!in_array($row['date_s'],$dates)){
            array_push($dates,$row['date_s']);
        }
    }
    //
}
// Screening Accuracy
$correct = [];
$incorrect = [];
$inconclusive = [];
if(is_countable($screened)){
    for($x=0;$x<count($screened);$x++){
        $qid = $screened[$x][0];
        $decision = $screened[$x][1];
        $sc_id = $screened[$x][4];
        $q = "SELECT * FROM rejected WHERE q_id='$qid' LIMIT 1";
        $r = mysqli_query($connection,$q);
        $info = mysqli_fetch_assoc($r);
        if((int)$info['id'] == 0){
            $q2 = "SELECT * FROM photos WHERE q_id='$qid' LIMIT 1";
            $r2 = mysqli_query($posts,$q2);
            $in = mysqli_fetch_assoc($r2);
            if((int)$in['id'] == 0){
                // inconclusive
                $this_res = "I";
            } else {
                $this_res = "A";
            }
        } else {
            $this_res = "R";
        }
        if($this_res == strtoupper(substr($decision,0,1))){
            array_push($correct,$sc_id);
        } elseif($this_res == "I"){
            array_push($inconclusive,$sc_id);
        } else {
            array_push($incorrect,$sc_id);
        }
        if(count($correct)+count($incorrect) > 0){
            $accuracy = count($correct)/(count($correct)+count($incorrect));
        } else {
            $accuracy = 0;
        }
    }
} else {
    $accuracy = 0;
}
// Screening Consistency
$days_since = round((($totime-$start_time)/86400));
$days_not = $days_since-count($dates);
if($days_since > 0){
    $consistency = count($dates)/$days_since;
} else {
    $consistency = 0;
}
// Quality of Feedback
$scores = [];
$query = "SELECT * FROM rejected WHERE screener='$user' ORDER BY id ASC";
$result = mysqli_query($connection,$query);
while($row = mysqli_fetch_assoc($result)){
    if(strlen($row['feedback']) > 0){
        array_push($scores,count(explode(".",$row['feedback'])));
    }
}
if(count($scores) > 0){
    $avg = array_sum($scores)/count($scores);
} else {
    $avg = 0;
}
$score = round($avg/4,2);
if($score > 1) {
    $feedback_quality = 1;
} else {
    $feedback_quality = $score;
}
// Quota
$query = "SELECT * FROM perms WHERE id='$user' LIMIT 1";
$result = mysqli_query($users,$query);
$score = mysqli_fetch_assoc($result)['score'];
$quotaperc = round($score/((250/7)*$days_since),2);
if($quotaperc > 1){
    $quota = 1;
} else {
    $quota = $quotaperc;
}
// Overall
$overall = round(($accuracy+$feedback_quality+$consistency+$quota)/4,2);
if($overall > 0.85){
    $txt = "Well Done!";
    $head = "You're doing very well as a screener. Keep up the great work!";
} elseif($overall > 0.6){
    $txt = "Room for Improvement.";
    $head = "You're doing fine, but there's definitely room to improve this week.";
} else {
    $txt = "Uh Oh!";
    $head = "Your performance this past week has been subpar. It's time to step it up.";
}
// get the feedback
$string = "<table class='feedback-table'><tr><td>Queue ID</td><td>Photo</td><td>Reason</td><td>Result</td></tr>";
for($x=0;$x<count($incorrect);$x++){
    $query = "SELECT * FROM screened_pics WHERE id='" . $incorrect[$x] . "' LIMIT 1";
    $result = mysqli_query($connection,$query);
    $in = mysqli_fetch_assoc($result);
    if($in['decision'] == "ACCEPT"){
        $q = "SELECT * FROM rejected WHERE q_id='" . $in['queue_id'] . "'";
        $img = mysqli_fetch_assoc(mysqli_query($connection,$q));
        $string .= "<tr><td>" . $in['queue_id'] . "</td><td><img class='show-img' src='https://cdn.aeroxplorer.com/uploads/" . $img['url'] . "'></td><td>" . $img['reason'] . "</td><td class='lenient'>You were too lenient.</td></tr>";
    } else {
        $q = "SELECT * FROM photos WHERE q_id='" . $in['queue_id'] . "'";
        $img = mysqli_fetch_assoc(mysqli_query($posts,$q));
        $string .= "<tr><td>" . $in['queue_id'] . "</td><td><img class='show-img' src='https://cdn.aeroxplorer.com/medium/" . $img['photo_url'] . "'></td><td>N/A</td><td class='strict'>You were too strict.</td></tr>";
    }
}
$string .= "</table>";
//
$feedback_here = "You screened on " . count($dates) . " out of " . $days_since . " days. To improve, be sure you're screening every day.";
//
// Print the information
echo '{
    "overall_text":"' . $txt . '",
    "overall_headline":"' . $head . '",
    "scores":[
        {
            "category":"Screening Accuracy",
            "score":' . round($accuracy,2) . ',
            "feedback":"' . base64_encode($string) . '"
        },
        {
            "category":"Screening Consistency",
            "score":' . round($consistency,2) . ',
            "feedback":"' . base64_encode($feedback_here) . '"
        },
        {
            "category":"Screening Quota",
            "score":' . round($quota,2) . ',
            "feedback":"' . base64_encode("This percentage is based on your ability to meet the weekly quota of 250 points, scaled to whichever day of the week it is. To achieve a perfect score in this category, you must achieve the weekly quota relative to how far into the week you are.") . '"
        },
        {
            "category":"Quality of Feedback",
            "score":' . round($feedback_quality,2) . ',
            "feedback":"' . base64_encode("To improve in this category, you should work on writing your feedback more descriptively.") . '"
        },
        {
            "category":"Overall",
            "score":' . round($overall,2) . ',
            "feedback":"' . base64_encode("This category is the average rating of all of the other categories. To improve this score, you must improve the other scores first.") . '"
        }
    ]
}';
die();
?>