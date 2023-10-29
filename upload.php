<?php     
// Include database configuration file 
require_once 'dbConfig.php'; 
 
$statusMsg = $valErr = ''; 
$status = 'danger'; 
 
// If the form is submitted 
if(isset($_POST['submit'])){ 
    // Allowed mime types of the file to upload 
    $allowedTypeArr = array("video/mp4", "video/avi", "video/mpeg", "video/mpg", "video/mov", "video/wmv", "video/rm", "video/quicktime"); 
     
    // Store post data in session 
    $_SESSION['postData'] = $_POST; 
     
    // Get input's value 
    $title = $_POST['title']; 
    $description = $_POST['description']; 
    $tags = $_POST['tags']; 
    $privacy = !empty($_POST['privacy'])?$_POST['privacy']:'public'; 
     
    // Validate form input fields 
    if(empty($_FILES["file"]["name"])){ 
        $valErr .= 'Please select a video file to upload.<br/>'; 
    }elseif(!in_array($_FILES['file']['type'], $allowedTypeArr)){ 
        $valErr .= 'Sorry, only MP4, AVI, MPEG, MPG, MOV, and WMV files are allowed to upload.<br/>'; 
    } 
         
    if(empty($title)){ 
        $valErr .= 'Please enter the title.<br/>'; 
    } 
     
    // Check whether user inputs are empty 
    if(empty($valErr)){ 
        $targetDir = "videos/"; 
        $fileName = time().'_'.basename($_FILES["file"]["name"]); 
        $targetFilePath = $targetDir.$fileName; 
         
        // Upload file to local server 
        if(move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)){ 
             
            // Insert data into the database 
            $sqlQ = "INSERT INTO videos(title,description,tags,privacy,file_name,created)VALUES (?,?,?,?,?,NOW())";
            $stmt = $db->prepare($sqlQ); 
            $stmt->bind_param("sssss", $db_title, $db_description, $db_tags, $db_privacy, $db_file_name); 
            $db_title = $title; 
            $db_description = $description; 
            $db_tags = $tags; 
            $db_privacy = $privacy; 
            $db_file_name = $fileName; 
            $insert = $stmt->execute(); 
             
            if($insert){ 
                $file_id = $stmt->insert_id; 
                 
                // Remove post data from session 
                unset($_SESSION['postData']); 
                 
                // Store DB reference ID of file in SESSION 
                $_SESSION['last_uploaded_file_id'] = $file_id; 
                 
                // Get Google OAuth URL 
                $state = mt_rand(); 
                $client->setState($state); 
                $_SESSION['state'] = $state; 
                $googleOauthURL = $client->createAuthUrl(); 
                 
                // Redirect user for Google authentication 
                header("Location: $googleOauthURL"); 
                exit(); 
            }else{ 
                $statusMsg = 'Something went wrong, please try again after some time.'; 
            } 
        }else{ 
            $statusMsg = 'File upload failed, please try again after some time.'; 
        } 
    }else{ 
        $statusMsg = '<p>Please fill all the mandatory fields:</p>'.trim($valErr, '<br/>'); 
    } 
}else{ 
    $statusMsg = 'Form submission failed!'; 
} 
 
$_SESSION['status_response'] = array('status' => $status, 'status_msg' => $statusMsg); 
 
header("Location: index.php"); 
exit(); 
?>