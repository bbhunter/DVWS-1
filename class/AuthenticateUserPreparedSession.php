<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class AuthenticateUserPreparedSession implements MessageComponentInterface
{
	protected $clients;
	public function __construct()
	{
		$this->clients = new \SplObjectStorage;
	}

	public function sql_authenticate_user($arr_data,$from, $session_id, $session_data)
	{
		$reply_data = "";
		try
		{
			require("includes/connect-db.php");
			try
			{
				$auth_user = base64_decode($arr_data['auth_user']);
				$auth_pass = base64_decode($arr_data['auth_pass']);
				$stmt = mysqli_prepare($con, "SELECT username, first_name, last_name, password FROM users WHERE username=? and password=? LIMIT 0,1");
				if($stmt)
				{
					mysqli_stmt_bind_param($stmt, "ss", $auth_user, $auth_pass);
					mysqli_stmt_execute($stmt);
					mysqli_stmt_bind_result($stmt, $username_db, $firstname_db, $lastname_db, $password_db);
					mysqli_stmt_store_result($stmt);
					if (mysqli_stmt_fetch($stmt))
					{
						$reply_data = "<div class='alert alert-success'>Welcome to your account. How are you $firstname_db $lastname_db? <a href='logout.php' class='btn btn-danger btn-md'>Logout</a></div>";
                        $session_data["username"] = $username_db;
                        $session_data["firstname"] = $firstname_db;
                        $session_data["lastname"] = $lastname_db;
                        $contents = json_encode($session_data);
                        $session_file = session_save_path()."/json/sess_".$session_id;
                        file_put_contents($session_file, $contents);
					}
					else
					{
						$reply_data = "<pre>Incorrect username/password</pre>";
					}
					mysqli_stmt_close($stmt);
				}
				else
				{
					echo "Statement failed: " . mysqli_stmt_error($stmt) . "\n";
					$reply_data = "<pre>Some SQL error occurred</pre>";
				}
			}
			catch(Exception $e)
			{
				$reply_data = "Something went wrong. Could not get data.";
			}
		}
		catch(Exception $e)
		{
			$reply_data = "Database connection file not found";
		}
		echo "Sending: authenticate-user-prepared-session : $reply_data\n";
		$from->send($reply_data);
	}

	public function onOpen(ConnectionInterface $conn)
	{
		//store the new connection
		$this->clients->attach($conn);
		echo "someone connected: authenticate-user-prepared-session\n";
		require_once("includes/connect-db.php");
	}

	public function onMessage(ConnectionInterface $from, $msg)
	{
		echo "Received: authenticate-user-prepared-session : $msg \n";
        //get cookies from httpRequest
        $cookiesRaw = $from->httpRequest->getHeader('Cookie');
        //deserialize cookies
        $cookies = array();
        foreach($cookiesRaw as $itm) {
            list($key, $val) = explode('=', $itm, 2);
            $cookies[$key] = $val;
        }
        //get PHPSESSID
        $session_id = $cookies['PHPSESSID'];

        $session_file = session_save_path()."/json/sess_".$session_id;
        if(!file_exists($session_file)) // The session doesn't exist
            return ;
        $contents = file_get_contents($session_file);
        //deserialize session_data
        $session_data = json_decode($contents, true);
		$reply_data = "";
		$arr_data = json_decode($msg,true);
        $this->sql_authenticate_user($arr_data, $from, $session_id, $session_data);
	}

	public function onClose(ConnectionInterface $conn)
	{
		$this->clients->detach($conn);
		echo "Someone has disconnected: authenticate-user-prepared-session\n";
	}

	public function onError(ConnectionInterface $conn, Exception $e)
	{
		echo "An error has occurred: authenticate-user-prepared-session : {$e->getMessage()}\n";
		$conn->close();
	}
}

