<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

date_default_timezone_set("American/New_York");
$connection = mysqli_connect('localhost','dandemo_main','','dandemo_posts','3306');
if(isset($_POST['reg'])){
    if($_POST['searchType'] == "search"){
        echo "S-" . date("H-i-s") . ":<br>";
        // SEARCH
        $dest = strtolower($_POST['reg']);
        //$info = file_get_contents("https://www.flightradar24.com/data/aircraft/" . $dest);
        $ch = curl_init();
        //The url you wish to send the POST request to
        $curlurl = "https://www.flightradar24.com/data/aircraft/" . $dest;
                
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
        $info = curl_exec($ch);
        $start = strpos($info,"<title>")+7;
        $end = strpos($info,"</title>");
        $parts = explode(" - ",substr($info,$start,strlen($info)-$end));
        echo "REG:" . $parts[0] . "<br>FRM:" . $parts[1];
        if(strlen($parts[2])<50){
            echo "<br>ARL:" . $parts[2];
        } else {
            echo strlen($parts[2]);
        }
    } elseif($_POST['searchType'] == "exists"){
        echo "S-" . date("H-i-s") . ":<br>";
        // EXISTS IN DB
        $reg = mysqli_real_escape_string($connection,$_POST['reg']);
        $query = "SELECT * FROM photos WHERE registration='$reg' ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($connection,$query);
        $info = mysqli_fetch_assoc($result);
        $id = $info['id'];
        $name = $info['authorname'];
        $ratio = $info['ratio'];
        $location = $info['photo_location'];
        $airline = $info['airline'];
        $aircraft = $info['aircraft'];
        if($id == ""){
            echo "FALSE";
        } else {
            echo "TRUE<br>ID:" . $id . "<br>NAM:" . $name . "<br>RAT:" . $ratio . "<br>ARL:" . $airline . "<br>ARC:" . $aircraft . "<br>LOC:" . $location;
        }
    } elseif($_POST['searchType'] == "match"){
        echo "S-" . date("H-i-s") . ":<br>";
        $reg = mysqli_real_escape_string($connection,$_POST['reg']);
        $num = (int) $_POST['photographer'];
        if($num==0){
            if(rand(0,1) == 0){
                $query = "SELECT * FROM photos WHERE registration='$reg'";
            }
        } else {
            $query = "SELECT * FROM photos WHERE registration='$reg' AND authornum='$num'";
        }
        $result = mysqli_query($connection,$query);
        echo "Results for User#" . $num . ":<br>";
        while($row = mysqli_fetch_assoc($result)){
            $url = $row['photo_url'];
            $id = $row['id'];
            echo "PHOTO#{$id}<br><img src=\"https://cdn.aeroxplorer.com/uploads/{$url}\" style=\"width:100%;\"><br>++++++++++++++++<br>";
        }
    }
}
?>