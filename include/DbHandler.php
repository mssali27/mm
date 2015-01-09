<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
    /*
  Conver base64 string into image and save into directory.
  return url of image.
*/

    public function upload_image($content,$imgname){
        //die($content);
        if($content){
                $img_name = $imgname."-".time();
                $data = $content;
                $data = str_replace(' ', '+', $data);
                $data = base64_decode($data);
               // die(dirname(dirname(__FILE__)).'/images/customers');
                file_put_contents(dirname(dirname(__FILE__)).'/images/customers/'.$img_name.".jpeg", $data);
                chmod(dirname(dirname(__FILE__)).'/images/customers/'.$img_name.".jpeg", 0777);
                return $_SERVER['HTTP_HOST'].'/mmzi/images/customers/'.$img_name.".jpeg";
        }else{
            return false;
        }
    }

    /* ------------- `customers` table method ------------------ */
    /**
     * Creating new customer
     */
    public function createUser($name, $email, $password, $contact, $address, $active, $deviceType, $deviceId, $image, $created, $agreement, $postalCode, $city, $state, $country, $cardNo, $expm, $expy, $cvv) {
        require_once 'PassHash.php';
        $response = array();
        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::md5pass($password);
            // Generating API key
            $api_key = $this->generateApiKey();
            // insert query
            $image = $this->upload_image($image,'test');
           
            $stmt = $this->conn->prepare("INSERT INTO customers(user_name, email, password, contact_no, address, active, device_type, device_id, image, created, agreement, postal_code, city, state, country, apikey) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
          
            $stmt->bind_param("ssssssssssssssss", $name, $email, $password_hash, $contact, $address, $active, $deviceType, $deviceId, $image, $created, $agreement, $postalCode, $city, $state, $country, $api_key);

            $result = $stmt->execute();
            $insertedId=$this->conn->insert_id;
            $userType=0;
            $stmt = $this->conn->prepare("INSERT INTO cards(user_id, user_type, card_no, cvv, expm, expy) values(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $insertedId, $userType, $cardNo, $expm, $expy, $cvv);
            $result = $stmt->execute();
            $stmt->close();
            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            }else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        }else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /* ------------- `restaurants` table method ------------------ */
    /**
     * Creating new restaurants
     */
     public function createRestaurant($name, $email, $password, $contact, $address, $active, $deviceType, $deviceId, $image, $created, $agreement, $long, $lat, $licenceNo, $taxId, $description, $postalCode, $city, $state, $country, $cardNo, $expm, $expy, $cvv) {

        require_once 'PassHash.php';
        $response = array();
        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::md5pass($password);
            // Generating API key
            $api_key = $this->generateApiKey();
            // insert query
            $image = $this->upload_image($image,'test');
            // die($image);
            $stmt = $this->conn->prepare("INSERT INTO restaurants(user_name, email, password, contact_no, address, active, device_type, device_id, image, created, agreement, longt, lat, licence_no, tax_id, description, postal_code, city, state, country, apikey) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
             
            $stmt->bind_param("sssssssssssssssssssss", $name, $email, $password_hash, $contact, $address, $active, $deviceType, $deviceId, $image, $created, $agreement, $long, $lat, $licenceNo, $taxId, $description, $postalCode, $city, $state, $country, $api_key);

            $result = $stmt->execute();
            $insertedId=$this->conn->insert_id;
            $userType=1;
            $stmt = $this->conn->prepare("INSERT INTO cards(user_id, user_type, card_no, cvv, expm, expy) values(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $insertedId, $userType, $cardNo, $expm, $expy, $cvv);
            $result = $stmt->execute();
            $stmt->close();
            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            }else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        }else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
		$tableidentifier=TRUE;
        $stmt = $this->conn->prepare("SELECT password FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        $stmt->store_result();
		if($stmt->num_rows > 0) $tableidentifier=1;
		if(!$stmt->num_rows > 0){
			$stmt = $this->conn->prepare("SELECT password FROM restaurants WHERE email = ?");
			$stmt->bind_param("s", $email);
			$stmt->execute();
			$stmt->bind_result($password_hash);
			$stmt->store_result();

			if($stmt->num_rows > 0) $tableidentifier=2;

		}
        if ($stmt->num_rows > 0) {
            $stmt->fetch();
            $stmt->close();
            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return $tableidentifier;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();
            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        if(!$num_rows > 0){
            $stmt = $this->conn->prepare("SELECT id from restaurants WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
        }
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email, $table) {
	    //die($email);
        $stmt = $this->conn->prepare("SELECT id, user_name, email, active, contact_no, address, image, created, apikey FROM $table WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($id, $name, $email, $active, $contact_no, $address, $image, $created, $apikey);
            $stmt->fetch();
            $user = array();
            $user["id"]=$id;
			$user["type"]=rtrim($table, 's');
            $user["name"] = $name;
            $user["email"] = $email;
			$user["status"] = $active;
			$user["contact"]=$contact_no;
			$user["address"]=$address;
            $user["image"] = $image;
            $user["created_at"] = $created;
            $user["apiKey"] = $apikey;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching Customer id by api key
     * @param String $api_key customer api key
     */
    public function getCustomerId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM customers WHERE apikey = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($customer_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $customer_id;
        } else {
            return NULL;
        }
    }
     /**
     * Fetching Restaurant id by api key
     * @param String $api_key restaurant api key
     */
    public function getRestaurantId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM restaurants WHERE apikey = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($restaurant_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $restaurant_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating customer api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key customer api key
     * @return boolean
     */
    public function isValidCustomerApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from customers WHERE apikey = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    /**
     * Validating restaurant api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key restaurant api key
     * @return boolean
     */
    public function isValidRestaurantApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from restaurants WHERE apikey = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Fetching single Restaurant
     * @param String $restaurant_id id of the Restaurant
     */
    public function getRestaurantById($restaurant_id) {
        $stmt = $this->conn->prepare("SELECT id, user_name, description, email, contact_no, address, image, longt, lat, created,licence_no, tax_id, apikey from restaurants r WHERE id = ?");
        $stmt->bind_param("i", $restaurant_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $userName, $description, $email, $contactNo, $address, $image, $longt, $lat, $created, $licenceNo, $taxId, $apikey);
            $stmt->fetch();
            if($id){
                $res["id"] = $id;
                $res["user_name"] = $userName;
                $res["description"] = $description;
                $res["email"] = $email;
                $res["contact_no"] = $contactNo;
                $res["address"] = $address;
                $res["image"] = $image;
                $res["longt"] = $longt;
                $res["lat"] = $lat;
                $res["created"] = $created;
                $res["licence_no"] = $licenceNo;
                $res["tax_id"] = $taxId;
                $res["apiKey"] = $apikey;
                $stmt->close();
                return $res;
            }else{
              return NULL;    
            }
            
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all restaurants
     */
    public function getAllRestaurants() {
        $dsn = 'mysql:dbname='.DB_NAME.';host='.DB_HOST;
        $user = DB_USERNAME;
        $password = DB_PASSWORD;
        $dbh=new PDO($dsn, $user, $password);
        $status=1;
        $params = array(':active' => $status);

        $sth = $dbh->prepare('SELECT * FROM restaurants WHERE active = :active ');
        $sth->execute($params);
       // $result = $sth->fetch(PDO::FETCH_ASSOC);
        return $sth; 
        //print_r($result);die;

        /*$status=1;
        $stmt = $this->conn->prepare("SELECT * FROM restaurants r WHERE r.active = ?");

        $stmt->bind_param("i", $status);
        $stmt->execute(); 
        echo "<pre>";print_r($stmt);die;
        $results=$stmt->get_result();
         while($row = $results->fetch_assoc()) {
               die("ID--".$row['id']);
            }
        $restaurants = $stmt->get_result();
        echo"<pre>";print_r($restaurants->fetch_assoc());die;
        $stmt->close();
        
        return $restaurants;*/
    }

    /**
     * Updating customer
     */
    public function updateCustomer($user_id, $id, $name, $contactNo, $address, $image, $postalCode, $city, $state, $country) {
        if($user_id!=$id){
            return 0;
        }
        $image=$this->upload_image($image,'utest');
        $stmt = $this->conn->prepare("UPDATE customers c SET c.user_name=?,c.contact_no=?,c.address=?,c.image=?,c.postal_code=?,c.city=?,c.state=?,c.country=? WHERE c.id=?");
        //die($stmt);
        $stmt->bind_param("ssssssssi", $name, $contactNo, $address, $image, $postalCode, $city, $state, $country, $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Updating Restaurant
     */
    public function updateRestaurant($user_id, $id, $name, $contactNo, $address, $description, $image) {
        if($user_id!=$id){
            return 0;
        }
        $image=$this->upload_image($image,'utest');
        $stmt = $this->conn->prepare("UPDATE restaurants r SET r.user_name=?,r.contact_no=?,r.address=?,r.description=?,r.image=?,c.postal_code=?,c.city=?,c.state=?,c.country=? WHERE r.id=?");
        $stmt->bind_param("sssssssssi", $name, $contactNo, $address, $description, $image, $postalCode, $city, $state, $country, $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a Customer
     * @param String $id id of the customer to delete
     */
    public function deleteCustomer($customer_id, $id) {
        if($customer_id!=$id){
            return 0;
        }
        $stmt = $this->conn->prepare("DELETE c FROM customers c WHERE c.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a restaurant
     * @param String $id id of the restaurant to delete
     */
    public function deleteRestaurant($restaurant_id, $id) {
        if($restaurant_id!=$id){
            return 0;
        }
        $stmt = $this->conn->prepare("DELETE r FROM restaurants r WHERE r.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }



}

?>
