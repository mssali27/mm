<?php
ini_set('date.timezone', 'America/Los_Angeles');
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require_once '../include/card.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
// User id from db - Global Variable
$user_id = NULL;
/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticatecustomer(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
    // Verifying Authorization Header
    if (isset($headers['Apiauthorization'])) {
        $db = new DbHandler();
        // get the api key
        $api_key = $headers['Apiauthorization'];
		
        // validating api key
        if (!$db->isValidCustomerApiKey($api_key)) {
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $customer_id;
            // get user primary key id
            $customer_id = $db->getCustomerId($api_key);
			
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}
function authenticateRestaurant(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
    // Verifying Authorization Header
    if (isset($headers['Apiauthorization'])) {
        $db = new DbHandler();
        // get the api key
        $api_key = $headers['Apiauthorization'];
        // validating restaurantApi key
        if (!$db->isValidRestaurantApiKey($api_key)) {
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $restaurant_id;
            // get user primary key id
            $restaurant_id = $db->getRestaurantId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * customer Registration
 * url - /customer_register
 * method - POST
 */
$app->post('/customer_register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('user_name', 'email', 'password','contact_no','address','agreement','card_no','expm', 'expy','cvv','postal_code','city','state','country'));
            $response = array();
            // reading post params
            $name = $app->request->post('user_name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');
            $contact = $app->request->post('contact_no');
            $address = $app->request->post('address');
            $active=1;
            $deviceType=$app->request->post('device_type');
            $deviceId=$app->request->post('device_id');
            $postalCode=$app->request->post('postal_code');
            $city=$app->request->post('city');
            $state=$app->request->post('state');
            $country=$app->request->post('country');
            if($_FILES){
                $image=image_to_base64($_FILES['image']['tmp_name']);    
            }else{
                $image=$app->request->post('image');    
            }
            $created=date("Y-m-d");
            $agreement=$app->request->post('agreement');
            $cardNo=$app->request->post('card_no');
            $expm=$app->request->post('expm');
            $expy=$app->request->post('expy');
            $cvv=$app->request->post('cvv');
            // validating email address
            validateEmail($email);
            validateCard($cardNo, $expm, $expy, $cvv);
            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password, $contact, $address, $active, $deviceType, $deviceId, $image, $created, $agreement, $postalCode, $city, $state, $country, $cardNo, $expm, $expy, $cvv);
            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * Restaurant Registration
 * url - /restaurant_register
 * method - POST
 */
$app->post('/restaurant_register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('user_name', 'email', 'password','contact_no','address','agreement', 'longt', 'lat','licence_no','tax_id', 'description', 'postal_code', 'city', 'state', 'country','card_no','expm', 'expy','cvv'));
            $response = array();
            // reading post params
            $name = $app->request->post('user_name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');
            $contact = $app->request->post('contact_no');
            $address = $app->request->post('address');
            $agreement=$app->request->post('agreement');
            $long=$app->request->post('longt');
            $lat=$app->request->post('lat');
            $licenceNo=$app->request->post('licence_no');
            $taxId=$app->request->post('tax_id');
            $description=$app->request->post('description');
            $active=1;
            $deviceType=$app->request->post('device_type');
            $deviceId=$app->request->post('device_id');
            $postalCode=$app->request->post('postal_code');
            $city=$app->request->post('city');
            $state=$app->request->post('state');
            $country=$app->request->post('country');
            if($_FILES){
                $image=image_to_base64($_FILES['image']['tmp_name']);    
            }else{
                $image=$app->request->post('image');    
            }
            $created=date("Y-m-d");
            $cardNo=$app->request->post('card_no');
            $expm=$app->request->post('expm');
            $expy=$app->request->post('expy');
            $cvv=$app->request->post('cvv');
            // validating email address
            validateEmail($email);
            validateCard($cardNo, $expm, $expy, $cvv);
            $db = new DbHandler();
            $res = $db->createRestaurant($name, $email, $password, $contact, $address, $active, $deviceType, $deviceId, $image, $created, $agreement, $long, $lat, $licenceNo, $taxId, $description, $postalCode, $city, $state, $country, $cardNo, $expm, $expy, $cvv);
            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });
/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            //die("login");
            verifyRequiredParams(array('email', 'password'));
 
            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();
 
            $db = new DbHandler();
            // check for correct email and password
			$check=$db->checkLogin($email, $password);
            if ($check) {
                // get the user by email
				if($check==1){
					$table="customers";
				}
				if($check==2){
					$table="restaurants";
				}
                $user = $db->getUserByEmail($email, $table);
 
                if ($user != NULL) {
                    $response["error"] = false;
                    $response['id'] = $user["id"];
                    $response['name'] = $user["name"];
                    $response['email'] = $user['email'];
                    $response['contact'] = $user["contact"];
					$response["address"]=$user["address"];
                    $response["image"]=$user["image"];
					$response["created_at"]=$user["created_at"];
					$response["account_type"]=$user["type"];
					$response["apiKey"]=$user["apiKey"];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }
 
            echoRespnse(200, $response);
        });
		
/**
 * Updating existing customer
 * method PUT
 * params user_name, contact_no, address,postal_code,city,state,country image(base-64)
 * url - /customer/:id
 *header param:-Apiauthorization
 */
$app->put('/customer/:id', 'authenticatecustomer', function($id) use($app) {
			verifyRequiredParams(array('user_name', 'contact_no', 'address','postal_code','city','state','country'));
			global $customer_id;            
			$name = $app->request->put('user_name');
			$contactNo = $app->request->put('contact_no');
			$address = $app->request->put('address');
			$image = $app->request->put('image');
			$postalCode = $app->request->put('postal_code');
			$city = $app->request->put('city');
			$state = $app->request->put('state');
			$country = $app->request->put('country');
			$db = new DbHandler();
			$response = array();
			$result = $db->updateCustomer($customer_id, $id, $name, $contactNo, $address, $image, $postalCode, $city, $state, $country);
			if ($result) {
				$response["error"] = false;
				$response["message"] = "User updated successfully";
			} else {
				$response["error"] = true;
				$response["message"] = "User failed to update. Please try again!";
			}
			echoRespnse(200, $response);
});

/**
 * Deleting Customer
 * method DELETE
 * url /customer/:id
 *header param:-Apiauthorization
 */
$app->delete('/customer/:id', 'authenticatecustomer', function($id) use($app) {
            global $customer_id;            
            $db = new DbHandler();
            $response = array();
            $result = $db->deleteCustomer($customer_id, $id);
            if ($result) {
                $response["error"] = false;
                $response["message"] = "Customer deleted succesfully";
            } else {
                $response["error"] = true;
                $response["message"] = "Customer failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });
/**
 * Updating existing Restaurant
 * method PUT
 * params user_name, contact_no, address, description, image
 * url - /restaurant/:id
 *header param:-Apiauthorization
 */
$app->put('/restaurant/:id', 'authenticateRestaurant', function($id) use($app) {
			verifyRequiredParams(array('user_name', 'contact_no', 'address', 'description','postal_code','city','state','country'));
			global $restaurant_id;            
			$name = $app->request->put('user_name');
			$contactNo = $app->request->put('contact_no');
			$address = $app->request->put('address');
			$description = $app->request->put('description');
			$image = $app->request->put('image');
			$postalCode = $app->request->put('postal_code');
			$city = $app->request->put('city');
			$state = $app->request->put('state');
			$country = $app->request->put('country');
			$db = new DbHandler();
			$response = array();
			$result = $db->updateRestaurant($restaurant_id, $id, $name, $contactNo, $address, $description, $image, $postalCode, $city, $state, $country);
			if ($result) {
				$response["error"] = false;
				$response["message"] = "User updated successfully";
			} else {
				$response["error"] = true;
				$response["message"] = "User failed to update. Please try again!";
			}
			echoRespnse(200, $response);
		});
/**
 * Deleting Restaurant
 * method DELETE
 * url /restaurant/:id
 *header param:-Apiauthorization
 */
$app->delete('/restaurant/:id', 'authenticateRestaurant', function($id) use($app) {
            global $restaurant_id;            
            $db = new DbHandler();
            $response = array();
            $result = $db->deleteRestaurant($restaurant_id, $id);
            if ($result) {
                $response["error"] = false;
                $response["message"] = "Restaurant deleted succesfully";
            } else {
                $response["error"] = true;
                $response["message"] = "Restaurant failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });
/**
 * Get Restaurant By Id
 * method GET
 * url /restaurant/:id
 */
$app->get('/restaurant/:id', function($id) {
            $response = array();
            $db = new DbHandler();
            $result = $db->getRestaurantById($id);
            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
	            $response["user_name"] = $result["user_name"];
	            $response["description"] = $result["description"];
	            $response["email"] = $result["email"];
	            $response["contact_no"] = $result["contact_no"];
	            $response["address"] = $result["address"];
	            $response["image"] = $result["image"];
	            $response["longt"] = $result["longt"];
	            $response["lat"] = $result["lat"];
	            $response["created"] = $result["created"];
	            $response["licence_no"] = $result["licence_no"];
	            $response["tax_id"] = $result["tax_id"];
	            $response["apiKey"] = $result["apiKey"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });
/**
 * Listing all restaurants
 * method GET
 * url /restaurants          
 */
$app->get('/restaurants', function() {
          //  die("restaurants");
            $response = array();
            $db = new DbHandler();
            $result = $db->getAllRestaurants();
            $response["error"] = false;
            $response["restaurants"] = array();
            while($restaurant = $result->fetch(PDO::FETCH_ASSOC)) {
                $tmp = array();
                $tmp["id"] = $restaurant["id"];
                $tmp["user_name"] = $restaurant["user_name"];
                $tmp["email"] = $restaurant["email"];
                $tmp["description"] = $restaurant["description"];
                $tmp["address"] = $restaurant["address"];
                $tmp["postalCode"]=$restaurant["postal_code"];
                $tmp["city"]=$restaurant["city"];
                $tmp["state"]=$restaurant["state"];
                $tmp["country"]=$restaurant["country"];
                $tmp["long"]=$restaurant["longt"];
                $tmp["lat"]=$restaurant["lat"];
                $tmp["created"]=$restaurant["created"];
                $tmp["licenceNo"]=$restaurant["licence_no"];
                $tmp["taxId"]=$restaurant["tax_id"];
                $tmp["image"]=$restaurant["image"];
                $tmp["apiKey"]=$restaurant["apikey"];
                array_push($response["restaurants"], $tmp);
            }
            echoRespnse(200, $response);
 });

/**
 * Listing near by restaurants
 * method GET
 * url /nearByRestaurants     
 * param :- current latitude and longitude
 * Return the resturant data with radius in mile.      
*/
$app->get('/nearByRestaurants/:lat/:lang', function($lat, $lang) {
	        // $latitude1=37.402653;
	        // $longitude1=-122.079353;
            $latitude1 = $lat;
            $longitude1 = $lang;
            $response = array();
            $db = new DbHandler();
            $result = $db->getAllRestaurants();
            $response["error"] = false;
            $response["restaurants"] = array();
            while ($restaurant = $result->fetch(PDO::FETCH_ASSOC)) {
            	$distance=getDistance($latitude1, $longitude1, $restaurant['lat'], $restaurant['longt']);
            	$restaurant['distance']=$distance;
            	$tmp = array();
                $tmp["id"] = $restaurant["id"];
                $tmp["user_name"] = $restaurant["user_name"];
                $tmp["email"] = $restaurant["email"];
                $tmp["description"] = $restaurant["description"];
                $tmp["address"] = $restaurant["address"];
                $tmp["postalCode"]=$restaurant["postal_code"];
                $tmp["city"]=$restaurant["city"];
                $tmp["state"]=$restaurant["state"];
                $tmp["country"]=$restaurant["country"];
                $tmp["long"]=$restaurant["longt"];
                $tmp["lat"]=$restaurant["lat"];
                $tmp["created"]=$restaurant["created"];
                $tmp["licenceNo"]=$restaurant["licence_no"];
                $tmp["taxId"]=$restaurant["tax_id"];
                $tmp["image"]=$restaurant["image"];
                $tmp["distance"]=$restaurant["distance"];
                $tmp["apiKey"]=$restaurant["apikey"];
            	array_push($response["restaurants"], $tmp);
            }
            $distanceArr=array();
            foreach($response["restaurants"] as $key=>$val){
            	 $distanceArr[$key]=$val['distance'];
            }
            array_multisort($distanceArr, SORT_ASC, $response["restaurants"]);
            echoRespnse(200, $response);
 });

/**
*Function to get distance between two coordinate.
*Return distance in 
*/
 function getDistance($latitude1, $longitude1, $latitude2, $longitude2) {  
        //radius in mile
        $earth_radius = 3959;
        //radiud in kilometer
        //$earth_radius = 6371; 
        $dLat = deg2rad($latitude2 - $latitude1);  
        $dLon = deg2rad($longitude2 - $longitude1);  
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);  
        $c = 2 * asin(sqrt($a));  
        $d = $earth_radius * $c;  
        return $d;  
 } 
/**
 * Verifying required params posted or not
*/
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    //echo"<pre>";print_r(image_to_base64($_FILES['image']['tmp_name']));die;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating credit card
 */
function validateCard($cardnum, $expm, $expy, $cvv) {
    $app = \Slim\Slim::getInstance();
    $cc = new CCVal($cardnum, $expm, $expy, $cvv);
    $cstatus=$cc->IsValid();
    //echo"<pre>";print_r($cstatus);die;
    if($cstatus[0]!="valid"){
        $response["error"] = true;
        $response["message"] = $cstatus;
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}
/**
*Function to convert image to base 64.
*/
function image_to_base64($path_to_image)
    {
    $type = pathinfo($path_to_image, PATHINFO_EXTENSION);
    $image = file_get_contents($path_to_image);
    $base64 =  base64_encode($image);
    return $base64;
    }
$app->run();
?>