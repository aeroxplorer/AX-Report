<?php
session_start();
ob_start();
include "../functions.php";
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screener Report | AeroXplorer Staff Website</title>
    <link rel="shortcut icon" href="../favicon.png">
    <link rel="stylesheet" href="../styles.css">
        <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Open+Sans&family=Quicksand&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: white;
        }
        .nav-sc {
            width: 100%;
            height: 70px;
        }
        .nav-sc img {
            width: 50px;
            margin: 10px;
            display: inline-block;
            border-radius: 50%;
        }
        .nav-sc span {
            font-size: 20px;
            padding: 35px 5px;
            display: inline-block;
            position: relative;
            top: -27px;
            font-family: 'Open Sans', sans-serif;
        }
        .status {
            width: 80%;
            height: 50%;
            vertical-align: middle;
            text-align: center;
            margin: auto;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            position: absolute;
            font-family: 'Quicksand', sans-serif;
        }
        .statusname {
            font-size: 80px;
            display: block;
        }
        .statusheadline {
             font-size: 40px;
             display: block;
        }
        .in-depth-coverage {
            position: relative;
            top: 100%;
            background-color: white;
            width: 100%;
        }
        .details {
            width: 70%;
            position: relative;
            font-family: 'Quicksand', sans-serif;
            left: 15%;
            padding: 50px 0;
        }
        .progress {
            width: 100%;
            background-color: #eee;
            position: relative;
            height: 45px;
            border-radius: 5px;
            display: block;
        }
        .filler {
            width: 70%;
            text-align: right;
            padding: 7.5px 20px;
            font-size: 25px;
            border-radius: 5px;
            margin: 5px 0;
        }
        details {
            margin: 2%;
            width: 95%;
            border-radius: 5px;
            padding: 15px;
        }
        .summary {
            color: blue;
            font-weight: bold;
            padding: 15px;
            cursor: pointer;
        }
        .feedback-table {
            display: table;
            width: 100%;
        }
        .feedback-table tr:first-of-type {
            background-color: black;
            color: white;
        }
        .feedback-table td {
            padding: 10px;
            text-align: center;
        }
        td img {
            max-width: 250px;
            border-radius: 5px;
        }
        .lenient {
            color: orange;
            font-weight: bold;
        }
        .strict {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="nav-sc">
        <img src="https://cdn.aeroxplorer.com/profile/<?php echo $_SESSION['pfp']; ?>">
        <span><?php echo trim($_SESSION['f_name']) . " " . trim($_SESSION['l_name']); ?> - AeroXplorer Screening Team &bull; <a style='color: black;' href="/dashboard/">&laquo; Home</a></span>
    </div>
    <div class="status">
        <span class="statusname">Loading Information</span>
        <span class="statusheadline">Let's see how you're doing this week!</span>
    </div>
    <div class="in-depth-coverage">
        <div class="details">
            <h1>Detailed Analytics</h1>
            <br><br>
            <div id="put-here">
                Loading Statistics...
            </div>
        </div>
    </div>
    <script>
        function getColor(percentage){
            if(percentage > 0.85){
                return "#83f261";
            } else if(percentage > 0.6) {
                return "#f0eb62";
            } else {
                return "#f0656e";
            }
        }
        document.body.onload = function(){
            var xhr = new XMLHttpRequest();
            xhr.open("POST","getreport.php");
            xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
            xhr.send("user=" + <?php if(($_SESSION['authornum'] == 10 || $_SESSION['authornum'] == 360 || $_SESSION['authornum'] == 398) && (isset($_GET['foruser']))) { echo $_GET['foruser']; } else { echo $_SESSION['authornum']; } ?>);
            xhr.onload = function(){
                document.getElementById("put-here").innerHTML = "";
                var resp = this.responseText;
                try {
                    var info = JSON.parse(resp);
                    document.getElementsByClassName("statusname")[0].innerHTML = info['overall_text'];
                    document.getElementsByClassName("statusheadline")[0].innerHTML = info['overall_headline'];
                    for(var x=0;x<info.scores.length;x++){
                        document.getElementById("put-here").innerHTML += info.scores[x].category + ':<div class="progress"><div class="filler" style="width: ' + Math.round(info.scores[x].score*100) + '%; background-color:' + getColor(info.scores[x].score) + '">' + Math.round(info.scores[x].score*100) + '%</div></div><details><summary>More Information</summary><br>' + window.atob(info.scores[x].feedback) + '</details><br><br>';
                        if(info.scores[x].category.toUpperCase() == "OVERALL"){
                            document.body.style.backgroundColor = getColor(info.scores[x].score);
                        }
                    }
                } catch(err){
                    window.alert(err.message);
                }
            }
        }
    </script>
</body>
</html>