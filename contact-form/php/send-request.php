<?php

if($_POST) {

   $date = trim(stripslashes($_POST['date']));
   $nps = trim(stripslashes($_POST['nps']));
   $message = trim(stripslashes($_POST['message']));

   // Set Message
   $header = array (
       'Content-type: application/json',
       'Authorization: Basic YWRtaW46QzBtMW5kdzRyM1BsQHRmMHJt'
   );
   
   $postdata = array (
       'Nablyudenie' => $message,
       'NPS' => $nps,
       'Data' => $date
   );
   //echo json_encode($postdata);
   $curl = curl_init('https://ktkqr.36.comindware.net/api/public/solution/Punktyoprosa');
   //$this->logger->write (__LINE__ . ' header - ' . json_encode ($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
   curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
   curl_setopt($curl, CURLOPT_POST, true);
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
   curl_exec($curl);
   $info = curl_getinfo($curl);
   curl_close($curl);
   //echo $info['http_code'] . '<br>';
	
   if ($info['http_code'] < 400) { echo "OK"; }
   else { echo "Что-то пошло не так, попробуйте еще раз."; }

}

?>