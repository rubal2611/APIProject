<?php
error_reporting(0);
function getErrors($header)
{
    // Extract the status line from the header
    preg_match('{HTTP\/\S*\s(\d{3})}', $header, $match);	
    $errors = array();
    switch($match[1]){
        // 400 : bad request (Word or language either not found, or incorrect)
        case 400:
            $errors['status'] = 400;
            $errors['message'] = "Bad Request";
        break;
        // 403 : Authentication failed (APP ID or KEY incorrect)
        case 403:
            $errors['status'] = 403;
            $errors['message'] = "Authentication failed";
        break;
        // 404: Not found (Incorrect API URL)
        case 404:
            $errors['status'] = 404;
            $errors['message'] = "Not found";
        break;
        // 414 : Request too long (Word or language exceeds 128 characters)
        case 414:
            $errors['status'] = 414;
            $errors['message'] = "Request too long";
        break;
        // 500 : Internal server error (This is API side error)
        case 500:
            $errors['status'] = 500;
            $errors['message'] = "Internal server error";
        break;
        // 504 : Bad gateway (API is down)
        case 502:
            $errors['status'] = 502;
            $errors['message'] = "Bad Gateway";
        break;
        // 505 : Service Unavailble (API is down)
        case 503:
            $errors['status'] = 503;
            $errors['message'] = "Service Unavailable";
        break;
        // 504 : Gateway timeout (The API did not reply in time)
        case 504:
            $errors['status'] = 504;
            $errors['message'] = "Gateway timeout";
        break;
    }   
    return $errors;      
}    
function displayErrors($errors)
{
    if(is_array($errors) && sizeof($errors)){
        header('X-PHP-Response-Code: 400', true, 400);
        header('Content-Type: application/json; charset=UTF-8');
        header("Cache-Control: no-cache, must-revalidate");
        
        echo json_encode(
                array('http_code'=>$errors['status'],'message'=>$errors['message'])
            );
            exit;
    }
    return;
}   
$word= trim($_GET['word']);
/**if word parameter is empty then show error   */
if(empty($word)){
    displayErrors(array('status'=>400,'message'=>'Missing a required parameter "word".'));
}

$word = strtolower($word);

/* CAll to first Dictionary with CURL */
$ch = curl_init();
// set url
curl_setopt($ch, CURLOPT_URL, "https://api.dictionaryapi.dev/api/v2/entries/en/$word");

//return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// $output contains the output string
$dictionary1 = curl_exec($ch);

// close curl resource to free up system resources
curl_close($ch);  

/* CAll to second dictionary  */

$_appID = "a6c5a99b";
$_appKey="a4db0841d3afff4af6fed33fab56959a";
$url        = "https://od-api.oxforddictionaries.com/api/v2/entries/en-gb/".$word;

// Create HTTP header array
// This is the HTTP header thats sent to the API URL when the request takes place
// The APP ID and KEY are used here to authenticate the requested - an Oxford Dictionaries user account is required
// (https://developer.oxforddictionaries.com/)
$options = array(
        'http' => array(
                'method' => "GET",
                'header' => "app_id:".$_appID."\r\n" .
                            "app_key:".$_appKey."\r\n" .
                            "Content-Type: application/json"
                        
    )
);

// Create the request stream
$context    = stream_context_create($options);
// Perform the request
$result = @file_get_contents($url, false, $context);

// Check the returned status of the request and handle any errors
displayErrors(getErrors($http_response_header[0]));

/* Decode the result into an array */
$output1 = json_decode($dictionary1);
$output2 = json_decode($result);

$audioUrl   = $output2->results[0]->lexicalEntries[0]->entries[0]->pronunciations[0]->audioFile;
$example     = $output2->results[0]->lexicalEntries[0]->entries[0]->senses[0]->examples[0]->text;
$definition = $output2->results[0]->lexicalEntries[0]->entries[0]->senses[0]->definitions[0];

header('X-PHP-Response-Code: 200', true, 200);
header('Content-Type: application/json; charset=UTF-8');
header("Cache-Control: no-cache, must-revalidate");
echo json_encode(
                    array(
                        'word'=>$output2->word,
                        'definition'=>$definition,
                        'audioURL'=>$audioUrl,
                        'phonetic'=>$output1[0]->phonetic,
                        'partofspeech'=>$output1[0]->meanings[0]->partOfSpeech,
                        'example'=>$example
                    )
                );



?>
