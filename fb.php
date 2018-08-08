<?php
ini_set('max_execution_time', 300); //300 seconds = 5 minutes
session_start();
require_once __DIR__ . '/src/Facebook/autoload.php';

$fb = new Facebook\Facebook([
  'app_id' => '515203538658837',
  'app_secret' => '3a6891838549f3920cd1f0f759133411',
  'default_graph_version' => 'v2.4',
  ]);

$servername = "localhost";
$username = "easybookfinder";
$password = "Tomten52!";
$dbname = "easybookfinder";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$helper = $fb->getRedirectLoginHelper();

$permissions = ['email', 'publish_actions']; // optional

$sql = "SELECT * FROM sessions LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
	 $accessTokenDB = $row['session'];
	}
}
	
try {
	if (isset($_SESSION['facebook_access_token'])) {
		$accessToken = $_SESSION['facebook_access_token']; 
	} else { 
  		$accessToken = $helper->getAccessToken(); 
	}
} catch(Facebook\Exceptions\FacebookResponseException $e) {
 	// When Graph returns an error
 	echo 'Graph returned an error: ' . $e->getMessage();

  	exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
 	// When validation fails or other local issues
	echo 'Facebook SDK returned an error: ' . $e->getMessage();
  	exit;
 }  
 if(isset($accessTokenDB)){
 $accessToken = $accessTokenDB;	
 }

if (isset($accessToken)) {
	if (isset($_SESSION['facebook_access_token'])) {
		$fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
	} else {
		// getting short-lived access token
		$_SESSION['facebook_access_token'] = (string) $accessToken;

	  	// OAuth 2.0 client handler
		$oAuth2Client = $fb->getOAuth2Client();

		// Exchanges a short-lived access token for a long-lived one
		$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);

		$_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;

		// setting default access token to be used in script
		$fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
	}

	// redirect the user back to the same page if it has "code" GET variable
	if (isset($_GET['code'])) {
		header('Location: ./');
	}

	// getting basic info about user
	try {

$sql = "SELECT * FROM program_1 WHERE is_posted=0 LIMIT 1";
$result = $conn->query($sql);

if ($conn->query('SELECT * FROM sessions')->num_rows == 0) {
			$long = $_SESSION['facebook_access_token'];//print_r($long);
			$sql1 = "INSERT INTO sessions(session) VALUES('$long')";
			$result1 = $conn->query($sql1);
}

if ($result->num_rows > 0) {

	    while($row = $result->fetch_assoc()) {

		$catproductnameurl = "http://easybookfinder.com/book/".urlencode($row["isbn"]."_".str_replace(array(' ','.',':'),"",$row["title"]));
		// message must come from the user-end
		$data = ['link' => $catproductnameurl];
		$request = $fb->post('/me/feed', $data);
		$response = $request->getGraphUser();

	// post on behalf of page
	$pages = $fb->get('/me/accounts');
	$pages = $pages->getGraphEdge()->asArray();
	foreach ($pages as $key) {
		if ($key['name'] == 'Just Books') {
			$post = $fb->post('/' . $key['id'] . '/feed', array('link' => $catproductnameurl), $key['access_token']);
			$post = $post->getGraphNode()->asArray();
			//print_r($post);

					
		}
	}
		$isbn = $row['isbn'];

		$sql = "UPDATE program_1 SET is_posted=1 WHERE isbn= {$isbn}";
		$result = $conn->query($sql);



	}

}

	} catch(Facebook\Exceptions\FacebookResponseException $e) {

		// When Graph returns an error
		echo 'Graph returned an error: ' . $e->getMessage();
		session_destroy();
		// redirecting user back to app login page
		header("Location: ./");
		exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		echo "Hii"; die;
		// When validation fails or other local issues
		echo 'Facebook SDK returned an error: ' . $e->getMessage();
		exit;
	}
	
	// printing $profile array on the screen which holds the basic info about user
	print_r($profile);

  	// Now you can redirect to another page and use the access token from $_SESSION['facebook_access_token']
} else {
	// replace your website URL same as added in the developers.facebook.com/apps e.g. if you used http instead of https and you used non-www version or www version of your website then you must add the same here
	$loginUrl = $helper->getLoginUrl('http://www.easybookfinder.com/fb/', $permissions);
	echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
}