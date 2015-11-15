<?php
require 'vendor/autoload.php';

$app = new \Slim\Slim();


$app->post('/init', function () use ($app){

    $id_sensor = $app->request->post("id_sensor");
    $id_android = $app->request->post("id_android");

    echo init($id_android,$id_sensor);

});

$app->get('/status', function () use ($app){

    $id_sensor = $app->request->get("id_sensor");
    $status = $app->request->get("status");

    switch ($status) {
        case 1:
            $status = "normal";
            break;
        case 2:
            $status = "waspada";
            break;
        case 3:
            $status = "bahaya";
            break;
    }

    echo addStatus($id_sensor,$status);

});

$app->get('/status/:id_sensor', function ($id_sensor) use ($app){

    echo getStatus($id_sensor);

});

$app->get('/', function () use ($app){

    require_once("home.php");

});



// helper PDO.

function getConnection() {
    $dbhost="localhost";
    $dbuser="laproid_gas";
    $dbpass="0m04Jt4pcW";
    $dbname="laproid_gas";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}

function init($id_android, $id_sensor){

	//ambil data
	$data = null;
	$sql = "SELECT * FROM device WHERE id_android=:id_android";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id_android", $id_android);
        $stmt->execute();
        $data = $stmt->fetchObject();
        $db = null;
        //echo json_encode($data);
    } catch(PDOException $e) {
        return json_encode((object) [
            "error" =>true,
            "message"=>$e->getMessage()
            ]);
    }

    //cek jika sudah ada di update, jika belum di insert
    $type = "";
    if($data!=null){
    	$sql = "UPDATE device SET id_sensor=:id_sensor WHERE id_android=:id_android";
        $type = "update";
    }else{
        $sql = "INSERT INTO device (id_sensor, id_android, created_at) VALUES (:id_sensor, :id_android, :created_at)";
        $type = "add";
    }
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id_sensor", $id_sensor);
        $stmt->bindParam("id_android", $id_android);
        if($type == "add"){
            $date = date("Y-m-d H:i:s");
            $stmt->bindParam("created_at", $date);
        }
        $stmt->execute();
        //$id = $db->lastInsertId();
        $db = null;
        return json_encode((object) [
        	"error" =>false,
        	"id_android"=>$id_android,
        	"id_sensor"=>$id_sensor,
        	"message"=>"Sensor berhasil di sinkronasi"
        	]);
    } catch(PDOException $e) {
          return json_encode((object) [
        	"error" =>true,
        	"message"=>$e->getMessage()
        	]);
    }

}

function addStatus($id_sensor, $status){

    $sql = "INSERT INTO status (id_sensor, statusgas, created_at) VALUES (:id_sensor, :statusgas, :created_at)";

    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id_sensor", $id_sensor);
        $stmt->bindParam("statusgas", $status);
        $date = date("Y-m-d H:i:s");
        $stmt->bindParam("created_at", $date);

        $stmt->execute();
        //$id = $db->lastInsertId();
        $db = null;


    } catch(PDOException $e) {
          return json_encode((object) [
            "error" =>true,
            "message"=>$e->getMessage()
            ]);
    }

    $ids = getIds($id_sensor);

    if(count($ids)==0){
        return json_encode((object) [
            "error" =>true,
            "message"=>"Sensor tidak ditemukan"
        ]);
    }

    echo send_notification($ids,"Status sensor sekarang ".$status);
    /*
    return json_encode((object) [
            "error" =>false,
            "id_sensor"=>$id_sensor,
            "statusgas"=>$status,
            "message"=>"Status berhasil di update"
            ]);
    */
}

function getStatus($id_sensor){

    //ambil data
    $data = null;
    $sql = "SELECT * FROM status WHERE id_sensor=:id_sensor ORDER BY id desc LIMIT 0,1";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id_sensor", $id_sensor);
        $stmt->execute();
        $data = $stmt->fetchObject();
        $db = null;
        $status = ($data != null) ? $data->statusgas : "normal";
        return json_encode((object) array(
                "error" =>false,
                "id_sensor"=>$data->id_sensor,
                "statusgas"=>$status,
            ));

    } catch(PDOException $e) {
         return json_encode((object) [
            "error" =>true,
            "message"=>$e->getMessage()
            ]);
    }

}

function send_notification(Array $registatoin_ids,$message) {
    /*
    * Google API Key
    */
    define("GOOGLE_API_KEY", "AIzaSyDn4B8d_y5jwSUOyc1gWo5xVHXpGbLEEFQ");
    // Set POST request variable
    $url = 'https://android.googleapis.com/gcm/send';

    $fields = array(
        'registration_ids' => $registatoin_ids,
        'data' => array("pesan"=>$message),
    );

    $headers = array(
        'Authorization: key=' . GOOGLE_API_KEY,
        'Content-Type: application/json'
    );
    // Open connection
    $ch = curl_init();

    // Set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // disable SSL certificate support
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    // execute post
    $result = curl_exec($ch);
    if ($result === FALSE) {
        die('Curl failed: ' . curl_error($ch));
    }

    // Close connection
    curl_close($ch);
    echo $result;
}

function getIds($id_sensor){
    /*return array id*/

    //ambil data
    $data = null;
    $sql = "SELECT * FROM device WHERE id_sensor=:id_sensor";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id_sensor", $id_sensor);
        $stmt->execute();
        $data =  $stmt->fetchAll();
        $db = null;

    } catch(PDOException $e) {
         echo json_encode((object) [
            "error" =>true,
            "message"=>$e->getMessage()
            ]);

         die();
    }
    $result = array();

    if($data != null){

        //if(count($data)>1){
            foreach ($data as $key => $value) {
                $result[] = $value['id_android'];
            }
        //}else{
            $result[] = $value['id_android'];
        //}

 //$result[] = "asassas";
          //print_r($data);

    }

    return $result;
}

$app->run();
