<?php

echo "let's go";

$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);

$totalIssuesInProject = 1007;

for ($i = 0; $i < $totalIssuesInProject; $i = $i + 100) {
	$url = "https://forge.easysdi.org/projects/easysdi/issues.json?status_id=*&limit=100&sort=id:asc&limit=100&offset=" . $i;
	var_dump($url);
    $response = file_get_contents($url, false, stream_context_create($arrContextOptions));
    $issues_list = json_decode($response);
    foreach ($issues_list->issues as $issue) {
        var_dump($issue->id);

		$urlIssue = "https://forge.easysdi.org//issues/{$issue->id}.json?include=journals";
		$response = file_get_contents($urlIssue, false, stream_context_create($arrContextOptions));
        file_put_contents("./out/{$issue->id}.json", $response);

    }
}

?>
