<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CAINFO, "C:\\Users\\Etudiant\\Documents\\3BCI-ProjetFinal\\ApiSymfony\\cacert.pem");
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    echo 'Success: ' . $response;
}
curl_close($ch);
