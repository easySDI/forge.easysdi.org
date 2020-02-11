<?php

//const MAX_ID = 1478;
const MAX_ID = 1500;

for ($i = 1; $i < MAX_ID; $i++) {
    if (file_exists("./out/{$i}.json")) {
        $response = file_get_contents("./out/{$i}.json");
        $source = json_decode($response);

        if (!isset($source->issue)) {
            echo("Failed on $i\n");
            continue;
        }

        // new object
        $import = new stdClass();
        $import->issue = new stdClass();
        $import->issue->closed = $source->issue->status->name === "Closed" || $source->issue->status->name === "Rejected";
        $import->issue->title = $source->issue->subject;
        $import->issue->created_at = $source->issue->created_on;
        $import->issue->labels = [];

        //tracker
        $import->issue->labels[] = $source->issue->tracker->name;
        //redmine status
        $import->issue->labels[] = 'Redmine status : ' . $source->issue->status->name;
        if (isset($source->issue->category)) {
            $import->issue->labels[] = 'Category : ' . $source->issue->category->name;
        }

        //Comments
        $import->comments = [];

        foreach ($source->issue->journals as $journal) {
            if (isset($journal->notes) && strlen($journal->notes)) {
                $newComment = new stdClass();
                $newComment->body = "**Original author :** " . $journal->user->name;
                $newComment->body .= "\n\n" . $journal->notes;
                $newComment->body = textileToMD($newComment->body);
                $newComment->created_at = $journal->created_on;
                $import->comments[] = $newComment;
            }
        }

        //Body
        $import->issue->body = '';
        //Add author
        $import->issue->body .= "**Original author :** " . $source->issue->author->name;
        //Original body
        $import->issue->body .= "\n\n" . $source->issue->description;

        //Fixed version
        if (isset($source->issue->fixed_version) && strlen($source->issue->fixed_version->name)) {
            $import->issue->body .= "\n\n**Fixed versions :** " . $source->issue->fixed_version->name;
        }
        //Add custom fields as text
        if (isset($import->issue->custom_fields)) {
            foreach ($import->issue->custom_fields as $custom_field) {
                switch ($custom_field->name) {
                    case "Sponsor":
                        if (count($custom_field->value)) {
                            $import->issue->body .= "\n\n**Sponsor :** " . implode(", ", $custom_field->value);
                        }
                        break;
                    case "Functional impact":
                        if (strlen($custom_field->value)) {
                            $import->issue->body .= "\n\n**Functional impact :** " . $custom_field->value;
                        }
                        break;
                    case "Ergonomic impact":
                        if (strlen($custom_field->value)) {
                            $import->issue->body .= "\n\n**Ergonomic impact :** " . $custom_field->value;
                        }
                        break;
                }
            }
        }

        $import->issue->body = textileToMD($import->issue->body);

        $newJson = json_encode($import, JSON_PRETTY_PRINT);

        file_put_contents("./new/{$i}.json", $newJson);


    } else {
        // Will use "default.json"
    }
}

function textileToMD($str)
{
    $str = str_replace('h1.', '#', $str);
    $str = str_replace('h2.', '##', $str);
    $str = str_replace('h3.', '###', $str);
    $str = str_replace('h4.', '####', $str);
    $str = str_replace('h5.', '#####', $str);
    $str = str_replace("<pre>\n", "```\n", $str);
    $str = str_replace("<pre>", "```\n", $str);
    $str = str_replace('</pre>', "```\n", $str);
    $str = str_replace('</code>', '', $str);
    $str = str_replace('</code>', '', $str);
    return $str;
}
