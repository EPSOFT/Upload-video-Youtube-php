<?php  
// Include database configuration file 
require_once 'dbConfig.php'; 
 
$statusMsg = ''; 
$status = 'danger'; 
$redirectURL = 'index.php'; 
 
// Check if an auth token exists for the required scopes 
$tokenSessionKey = 'token-' . $client->prepareScopes(); 
if (isset($_GET['code'])) { 
    if (strval($_SESSION['state']) !== strval($_GET['state'])) { 
        die('The session state did not match.'); 
    } 
     
    $client->authenticate($_GET['code']); 
    $_SESSION[$tokenSessionKey] = $client->getAccessToken(); 
    header('Location: ' . REDIRECT_URL); 
} 
 
if (isset($_SESSION[$tokenSessionKey])) { 
    $client->setAccessToken($_SESSION[$tokenSessionKey]); 
} 
 
// Check to ensure that the access token was successfully acquired. 
if ($client->getAccessToken()) { 
    // Get file reference ID from SESSION 
    $file_id = $_SESSION['last_uploaded_file_id']; 
     
    if(!empty($file_id)){ 
        // Fetch video file details from the database 
        $sqlQ = "SELECT * FROM videos WHERE id = ?"; 
        $stmt = $db->prepare($sqlQ);  
        $stmt->bind_param("i", $db_file_id); 
        $db_file_id = $file_id; 
        $stmt->execute(); 
        $result = $stmt->get_result(); 
        $videoData = $result->fetch_assoc(); 
         
        if(!empty($videoData)){ 
            $file_name = $videoData['file_name']; 
            $videoPath = 'videos/'.$file_name; 
             
            if(!empty($videoData['youtube_video_id'])){ 
                // Get video info from local database 
                $video_title = $videoData['title']; 
                $video_desc = $videoData['description']; 
                $video_tags = $videoData['tags']; 
                $youtube_video_id = $videoData['youtube_video_id']; 
            }else{ 
                try { 
                    // Create a snippet with title, description, tags and category ID 
                    // Create an asset resource and set its snippet metadata and type. 
                    // This example sets the video's title, description, keyword tags, and 
                    // video category. 
                    $snippet = new Google_Service_YouTube_VideoSnippet(); 
                    $snippet->setTitle($videoData['title']); 
                    $snippet->setDescription($videoData['description']); 
                    $snippet->setTags(explode(",", $videoData['tags'])); 
                 
                    // Numeric video category. See 
                    // https://developers.google.com/youtube/v3/docs/videoCategories/list 
                    $snippet->setCategoryId("22"); 
                 
                    // Set the video's status to "public". Valid statuses are "public", 
                    // "private" and "unlisted". 
                    $status = new Google_Service_YouTube_VideoStatus(); 
                    $status->privacyStatus = $videoData['privacy']; 
                 
                    // Associate the snippet and status objects with a new video resource. 
                    $video = new Google_Service_YouTube_Video(); 
                    $video->setSnippet($snippet); 
                    $video->setStatus($status); 
                 
                    // Specify the size of each chunk of data, in bytes. Set a higher value for 
                    // reliable connection as fewer chunks lead to faster uploads. Set a lower 
                    // value for better recovery on less reliable connections. 
                    $chunkSizeBytes = 1 * 1024 * 1024; 
                 
                    // Setting the defer flag to true tells the client to return a request which can be called 
                    // with ->execute(); instead of making the API call immediately. 
                    $client->setDefer(true); 
                 
                    // Create a request for the API's videos.insert method to create and upload the video. 
                    $insertRequest = $youtube->videos->insert("status,snippet", $video); 
                 
                    // Create a MediaFileUpload object for resumable uploads. 
                    $media = new Google_Http_MediaFileUpload( 
                        $client, 
                        $insertRequest, 
                        'video/*', 
                        null, 
                        true, 
                        $chunkSizeBytes 
                    ); 
                    $media->setFileSize(filesize($videoPath)); 
                 
                 
                    // Read the media file and upload it chunk by chunk. 
                    $status = false; 
                    $handle = fopen($videoPath, "rb"); 
                    while (!$status && !feof($handle)) { 
                        $chunk = fread($handle, $chunkSizeBytes); 
                        $status = $media->nextChunk($chunk); 
                    } 
                    fclose($handle); 
                 
                    // If you want to make other calls after the file upload, set setDefer back to false 
                    $client->setDefer(false); 
                     
                    if(!empty($status['id'])){ 
                        // Uploaded youtube video info 
                        $video_title = $status['snippet']['title']; 
                        $video_desc = $status['snippet']['description']; 
                        $video_tags = implode(",",$status['snippet']['tags']); 
                        $youtube_video_id = $status['id']; 
                         
                        // Update youtube video reference id in the database 
                        $sqlQ = "UPDATE videos SET youtube_video_id=? WHERE id=?"; 
                        $stmt = $db->prepare($sqlQ); 
                        $stmt->bind_param("si", $db_youtube_video_id, $db_file_id); 
                        $db_youtube_video_id = $youtube_video_id; 
                        $db_file_id = $file_id; 
                        $update = $stmt->execute(); 
                         
                        if($update){ 
                            // Delete video file from local server 
                            @unlink($videoPath); 
                        } 
                         
                        unset($_SESSION['last_uploaded_file_id']); 
                         
                        $status = 'success'; 
                        $statusMsg = 'Video has been uploaded to YouTube successfully!'; 
                    } 
                } catch (Google_Service_Exception $e) { 
                    $statusMsg = 'A service error occurred: <code>'.$e->getMessage().'</code>'; 
                } catch (Google_Exception $e) { 
                    $statusMsg = 'An client error occurred: <code>'.$e->getMessage().'</code>'; 
                    $statusMsg .= '<br/>Please reset session <a href="logout.php">Logout</a>'; 
                } 
            } 
             
            if(!empty($youtube_video_id)){ 
                $redirectURL = 'status.php?fid='.base64_encode($file_id); 
            } 
        }else{ 
            $statusMsg = 'File data not found!'; 
        } 
    }else{ 
        $statusMsg = 'File reference not found!'; 
    } 
     
    $_SESSION[$tokenSessionKey] = $client->getAccessToken(); 
}else{ 
    $statusMsg = 'Failed to fetch access token!'; 
} 
 
$_SESSION['status_response'] = array('status' => $status, 'status_msg' => $statusMsg); 
 
header("Location: $redirectURL"); 
exit();