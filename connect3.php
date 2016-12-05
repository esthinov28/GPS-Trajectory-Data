<?php
$time_start = microtime(true);
$con=mysqli_connect('localhost','root','');
$db=mysqli_select_db($con,'taxi');
$id = $_GET['id'];
$tanggal = $_GET['tanggal'];

if ($tanggal == "-")
{
  $rs=@mysqli_query($con,"SELECT MAX(date) date FROM taxi WHERE id=$id");
  if ($row = @mysqli_fetch_array($rs, MYSQLI_ASSOC)) {
    $tanggal = $row['date'];
    $tanggal = date('Y-m-d', strtotime($tanggal));
  }
}
$rs=@mysqli_query($con,"SELECT * FROM taxi where id=$id and (date between '".$tanggal." 00:00:00' AND '".$tanggal." 23:59:59') ORDER BY date ASC");

//the id is from ID each Taxi which have 100 ID and wanna show each day routing
  $lat="";
  $marker = array();
  $data = array();

  while ($row = @mysqli_fetch_array($rs, MYSQLI_ASSOC)) {
    //if($i%2!=0){
      //simpan titik awal nya
      $awal_lt=$row['lt'];
      $awal_lg=$row['lg'];
    //}
    $lat .= "{lat: ".$row['lt'].", lng: ".$row['lg']."},";
    $data[] = $row;
  
  }
 /*while ($row2 = mysqli_fetch_array($rsp, MYSQLI_ASSOC)) {
    $profile[] = $row2;
  }*/
  
  //iterasi perhitungan jarak
  $i=0;
  $ii = 0;
  $jumlah_data = count($data);
  $kecepatan = array();
  $json_response = array();
 
  
  if ($jumlah_data > 1) 
  {
    while ($i < $jumlah_data - 1) 
    {
      $dteStart = new DateTime($data[$i]['date']); 
      $dteEnd   = new DateTime($data[$i+1]['date']); 
      $waktu = $dteStart->diff($dteEnd);
      $str_time = $waktu->format("%H:%I:%S");
      
      sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
      
      $time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
      $intKecepatan = vincentyGreatCircleDistance($id, $con, $data[$i]['date'], $data[$i]['lt'], $data[$i]['lg'], $data[$i+1]['lt'], $data[$i+1]['lg'], $time_seconds);
      if ($intKecepatan > 0)
      {
        $json_response[$ii]['kecepatan'] = $intKecepatan;
        
        $json_response[$ii]['lt_awal'] = $data[$i]['lt'];
        $json_response[$ii]['lt_akhir'] = $data[$i+1]['lt'];
        $json_response[$ii]['lg_awal'] = $data[$i]['lg'];
        $json_response[$ii]['lg_akhir'] = $data[$i+1]['lg'];

        $marker[$ii]['lt'] = $data[$i]['lt'];
        $marker[$ii]['lg'] = $data[$i]['lg'];
        
        if ($i == $jumlah_data - 1)
        {
          $marker[$ii+1]['lt'] = $data[$i+1]['lt'];
          $marker[$ii+1]['lg'] = $data[$i+1]['lg'];
        }
        $ii++;
      }
      $i++;
    }
  }
  else
  {
    $json_response[$i]['kecepatan'] = 0;
    $json_response[$i]['lt_awal'] = null;
    $json_response[$i]['lt_akhir'] = null;
    $json_response[$i]['lg_awal'] = null;
    $json_response[$i]['lg_akhir'] = null;
  }
//}/*end for*/
  $rs = mysqli_query($con, "SELECT * FROM profile WHERE id = $id");
    $profil = array();
  while ($row = mysqli_fetch_array($rs, MYSQLI_ASSOC)) {
    $profil[] = $row;
  }

  $rs = mysqli_query($con, "SELECT  MAX(date) dt FROM taxi WHERE id = $id");
  $dates = array();
  while ($row = mysqli_fetch_array($rs, MYSQLI_ASSOC)) {
    $date = $row['dt'];
    $date = date('Y-m-d',strtotime($date)); 
    $dates[]['tanggal'] = $date;
  }

  for ($i = 0; $i < 6;$i++)
  {
    $date = date('Y-m-d', strtotime('-1 day', strtotime($date)));
    $dates[]['tanggal'] = $date;
  }

echo json_encode(array('route' => $json_response, 'marker' => $marker, 'profile' => $profil, 'dates' => $dates));

$lat=substr($lat,0,(strlen($lat)-1));
//rumus perhitungan kecepatan(v=s/t) satuan m/s
function vincentyGreatCircleDistance($id, $con, $date,
  $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $time_seconds, $earthRadius = 6371000)
{
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $lonDelta = $lonTo - $lonFrom;
  $a = pow(cos($latTo) * sin($lonDelta), 2) +
    pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
  $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
  $angle = atan2(sqrt($a), $b);
  if($time_seconds == 0){
    $velocityKM = 0;
  }
  else {
    $velocity = $angle* $earthRadius / $time_seconds;
    $velocityKM = @($velocity / 1000) * 3600;
  }

  /*$update = "UPDATE taxi ". "SET velocity = $velocityKM ". 
               "WHERE id = $id ". "AND date = ". "'".$date."' ". "AND lg = $longitudeTo ". "AND lt = $latitudeTo" ;*/
 // $sql = @mysqli_query($con, $update);

//update taxi set profile_id=2 where id=2
  return $velocityKM;
}

/*$rsp = mysqli_query($con, "SELECT * FROM profile WHERE id = $id");
  $profil = array();
  while ($row = mysqli_fetch_array($rsp, MYSQLI_ASSOC)) {
    $profil[] = $row;
    echo json_encode(array('profile' => $profil));  
  }*/

 $time_end = microtime(true);
    $time = $time_end - $time_start;
    echo "Process Time: {$time}";
?>


<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>Simple Polylines</title>
    <style>
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
      }
      #map {
        height: 100%;
      }
    </style>
  </head>
  <body>

    <div id="map"></div>
    <script>
      function initMap() {
  	var map = new google.maps.Map(document.getElementById('map'), {
          zoom: 11,
          center: {lat: 39.9185466, lng: 116.517268},
          mapTypeId: 'terrain'
        });
        var flightPlanCoordinates = [<?php echo $lat;?>
        ];
        var flightPath = new google.maps.Polyline({
          path: flightPlanCoordinates,
          geodesic: true,
          strokeColor: '#FF0000',
          strokeOpacity: 1.0,
          strokeWeight: 2
        });
        flightPath.setMap(map);
      }
    </script>
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAjKyd42zzfvUsewyw8vI-KFhH2t_B_QpQ&callback=initMap">
    </script>
   <div>
    halaman ini dimuat {elapsed_time} detik </div>
  </body>
</html>
