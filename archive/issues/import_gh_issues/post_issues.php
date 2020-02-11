<?php

//const MAX_ID = 1478;
const MAX_ID = 1480;
const IMPORT_URL = "https://api.github.com/repos/easySDI/easySDI/import/issues";
const GITHUB_TOKEN = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";

for ($i = 8; $i < MAX_ID; $i++) {
    $postFile = "./new/{$i}.json";
    if (file_exists($postFile)) {
        $payload = file_get_contents($postFile);
    } else {
        $payload = "{
               \"issue\" : {
                  \"closed\" : true,
                  \"created_at\" : \"2020-01-01T12:00:00Z\",
                  \"title\" : \"DELETE ME {$i}\",
                  \"labels\" : [
                     \"to-delete\"
                  ],
                  \"body\" : \"This is a temp issue to delete\"
               }
            }";
    }

    // Prepare new cURL resource
    $ch = curl_init(IMPORT_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Set HTTP Header for POST request
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: token ' . GITHUB_TOKEN,
        'Accept: application/vnd.github.golden-comet-preview+json',
        'User-Agent: PHP Redmine importer'
    ));

    // Submit the POST request
    $result = curl_exec($ch);
    $res = json_decode($result);

    // Check import
    while (!waitForImported($i, $res->url)) {
        echo('.');
    }


}

function waitForImported($id, $url)
{
    sleep(1);
    // Prepare new cURL resource
    $chCheck = curl_init($url);
    curl_setopt($chCheck, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chCheck, CURLINFO_HEADER_OUT, true);

    // Set HTTP Header for GEST request
    curl_setopt($chCheck, CURLOPT_HTTPHEADER, array(
        'Authorization: token ' . GITHUB_TOKEN,
        'Accept: application/vnd.github.golden-comet-preview+json',
        'User-Agent: PHP Redmine importer'
    ));

    // Submit the POST request
    $checkResult = curl_exec($chCheck);
    $checkRes = json_decode($checkResult);

    // Close cURL session handle
    curl_close($chCheck);

    if ($checkRes->status === 'imported') {
        echo($id . ' : ' . $checkRes->status . ' : ' . $checkRes->url . ' : ' . $checkRes->issue_url . "\n");

        if (!endsWith($checkRes->issue_url, '/' . $id)) {
            throw new Exception('incrementing failed !!!');
        }

        return true;
    } elseif ($checkRes->status === 'pending') {
        echo($id . ' : pending..');
        return false;
    } else {
        throw new Exception($checkResult);
    }
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}
