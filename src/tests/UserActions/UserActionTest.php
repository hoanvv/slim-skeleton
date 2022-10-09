<?php

namespace Tests\Application\Actions;

use Hoanvv\Test\TestCase;
use Slim\App;
use Prophecy\PhpUnit\ProphecyTrait;
use Hoanvv\App\Actions\User\ListingAction;
use Hoanvv\App\Domain\Repositories\User\IUserRepository;
use Hoanvv\App\Domain\Models\User;
use Hoanvv\App\Actions\ActionPayload;
use Hoanvv\App\Domain\Repositories\User\UserRepository;
use Hoanvv\App\Database\MasterDatabase;
use DateTimeImmutable;
use PDO;

class UserActionTest extends TestCase
{
    use ProphecyTrait;
    protected string $date = '0000';
    protected string $username = '';
    protected string $password = '';

    protected $db;
    /**
     * set up data for each test
     */
    public function setUpData()
    {
        $date = (new DateTimeImmutable())->format('YmdHis');
        $this->date = $date; // time or running tests
        $this->username = "username_$date";
        $this->password = "pwd_$date";
        $this->db = (new MasterDatabase())->db();
        //begin transaction:
        // $this->db->beginTransaction();
        // $this->db->commit(true);
    }

    public function createApplication(): App
    {
        return (require __DIR__ . '/../../config/bootstrap.php');
    }

    /**
     *  TEST CASE(S) FOR REGISTER USER API
     */

    /**
     * create NEW user
     * 
     * Provide valid username and password
     */
    public function testRegisterNewUsersSuccessfully()
    {
        $userData = [
            'username' => $this->username,
            'password' => $this->password
        ];
        // Mock a request
        $this->post('/register', $userData);

        // Check if data is inserted successfully
        $query = "SELECT * FROM users where username = '$this->username' and password = '$this->password'";
        $result = $this->db->query($query);
        $row_count = $result->rowCount();
        // Check testing results
        $this->assertTrue($row_count == 1, "Create New User Failed: Find $row_count record(s)");
    }

    /**
     * create EXISTING user
     * 
     * Provide an available username
     */
    public function testRegisterExistingUsername()
    {
        $userData = [
            'username' => $this->username,
            'password' => $this->password
        ];

        // Mock a request
        $resp = $this->post('/register', $userData);
        $payload = $resp->getBody();
        // Check if there are more than one result
        $query = "SELECT * FROM users where username = '$this->username' and password = '$this->password'";
        $result_row = $this->db->query($query)->rowCount();
        // Expected Payload
        $expectedPayload = new ActionPayload(400, "Username already exists");
        // Check testing results
        $this->assertTrue($result_row == 1, "Register user with same username: Find $result_row record(s)");
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
    }
    /**
     * create user: LACK of information
     * 
     * Provide only username or password for registration
     */
    public function testRegisterUsernameNotEnoughInfo()
    {
        // lack of username
        $userData_1 = [
            'password' => $this->password . '_lackofusername',
        ];
        // lack of password
        $userData_2 = [
            'username' => $this->username . '_lackofpassword',
        ];

        // Mock a request
        $resp_1 = $this->post('/register', $userData_1);
        $resp_2 = $this->post('/register', $userData_2);
        $payload_1 = $resp_1->getBody();
        $payload_2 = $resp_2->getBody();
        // Check if the data is imported
        $query_1 = "SELECT * FROM users where password = '$this->password" . "_lackofusername' ";
        $query_2 = "SELECT * FROM users where username = '$this->username" . "_lackofpassword' ";

        $result_1 = $this->db->query($query_1)->rowCount() == 0;
        $result_2 = $this->db->query($query_2)->rowCount() == 0;
        // Expected Payload
        $expectedPayload = new ActionPayload(400, "Username or Password cannot be empty");

        // Check testing results
        $this->assertTrue($result_1, "Register user lacking username");
        $this->assertTrue($result_2, "Register user lacking password");

        $this->assertSame(json_encode($payload_1), json_encode($expectedPayload));
        $this->assertSame(json_encode($payload_2), json_encode($expectedPayload));
    }

    /**
     * TEST CASE(S) FOR GET USER API
     */

    /**
     * get available user (from above)
     */
    public function testGetAvailableUser()
    {
        // Mock a request
        $user_id = ($this->db->query("SELECT id FROM users where username = '$this->username'")->fetch())['id'];
        $resp = $this->get("/v1/info/$user_id");
        $payload = $resp->getBody();
        // Expected result
        $expectedPayload = new ActionPayload(200, [
            'first_name' => null,
            'last_name' => null,
        ]);
        // Checking testing result
        $this->assertSame(json_encode($expectedPayload), json_encode($payload), "user id = $user_id");
    }
    /**
     * get unavailable user id in INT (no exist)
     * 
     * This test includes checking functionality of authmiddleware
     */
    public function testGetUnavailableUsers()
    {
        // Find the current userid test
        $query = "SELECT id from users where username = '$this->username'";
        $result = $this->db->query($query)->fetch();
        $user_id = $result['id'];
        $unavai_id = $user_id + 2897;
        // Mock a request with an unavailable user_id
        $resp = $this->get("/v1/info/$unavai_id");
        $payload = $resp->getBody();
        // Expected result
        $expectedPayload = [
            'code' => 401,
            'message' => 'Unauthorized request'
        ];
        // Checking testing result
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
    }
    /**
     * get user with wrong pattern of user_id (string instead int)
     * 
     * This test includes checking methods of requests
     */
    public function testGetUserIdInString()
    {

        // Mock a request with an unavailable user_id (in string)
        $resp = $this->get("/v1/info/dump_string");
        $payload = $resp->getBody();
        // Expected result
        $expectedPayload = [
            "code" =>  404,
            "message" => "Not found."
        ];
        // Checking testing result
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
    }

    /**
     * TEST CASE(S) FOR UPDATE USER API
     */
    /**
     * PASSED CASE(S)
     */
    /**
     * update all information of a user (not username)
     * 
     */
    public function testUpdateUserAllSuccess()
    {
        $user_update = [
            'password' => "newpwd_$this->date",
            'first_name' => "firstname_$this->date",
            'last_name' => "lastname_$this->date",
        ];
        // get test user
        $query = "SELECT id from users where username = '$this->username'";
        $result = $this->db->query($query)->fetch();
        $user_id = $result['id'];
        $resp = $this->put("/v1/update/$user_id", $user_update);
        $payload = $resp->getBody();
        // Expected output
        $expectedPayload = new ActionPayload(200, 'Update successfully');
        // Get data after update
        $query = "SELECT * FROM users WHERE password = :password  AND first_name = :first_name AND last_name = :last_name ";
        $exec = $this->db->prepare($query);
        $exec->bindParam(':password', $user_update['password']);
        $exec->bindParam(':first_name', $user_update['first_name']);
        $exec->bindParam(':last_name', $user_update['last_name']);
        $exec->execute();
        $row_count = $exec->rowCount();
        $affected_id = $exec->fetch()['id'];
        // Check test result
        $this->assertTrue($row_count == 1, "Can't find User after UPDATE");
        $this->assertTrue($affected_id == $user_id, "Update wrong user");
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
    }
    /**
     * update some information
     */
    public function testUpdateUserSomeSuccess()
    {
        $user_update = [
            'first_name' => "new_firstname_$this->date",
            'last_name' => "new_lastname_$this->date"
        ];
        // get test user
        $query = "SELECT id from users where username = '$this->username'";
        $result = $this->db->query($query)->fetch();
        $user_id = $result['id'];
        // For this func, no need to check output response
        $this->put("/v1/update/$user_id", $user_update);
        // Get data after update
        $query = "SELECT * FROM users WHERE first_name = :first_name AND last_name = :last_name";
        $exec = $this->db->prepare($query);
        $exec->bindParam(':first_name', $user_update['first_name']);
        $exec->bindParam(':last_name', $user_update['last_name']);
        $exec->execute();
        $row_count = $exec->rowCount();
        $affected_id = $exec->fetch()['id'];
        // Check test result
        $this->assertTrue($row_count == 1, "Can't find User after UPDATE");
        $this->assertTrue($affected_id == $user_id, "Update wrong user");
    }
    /**
     * FAILED CASE(S)
     */
    /**
     * Update but no information provided
     */
    public function testUpdateUserFail()
    {
        // get test user
        $query = "SELECT * from users where username = '$this->username'";
        $before_data = $this->db->query($query)->fetch();
        $user_id = $before_data['id'];
        $resp = $this->put("/v1/update/$user_id", []);
        $payload = $resp->getBody();
        // Expected result
        $expectedPayload = [
            'code' => 400,
            'message' => "Bad request"
        ];
        // Check if user data is changed
        $data = $this->db->query("SELECT * FROM users where id = $user_id")->fetch();
        $status = $before_data['password'] == $data['password']
            && $before_data['first_name'] == $data['first_name']
            && $before_data['last_name'] == $data['last_name'];
        $this->assertTrue($status, "Updated Unintentionally");
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
    }

    /**
     * TEST CASE(S) FOR DELETE USER API
     */
    /**
     * FAILED CASE(S)
     */
    /**
     * Delete unavailable userid
     */
    public function testDeleteUserFailed()
    {
        // get test user
        $query = "SELECT * from users where username = '$this->username'";
        $before_data = $this->db->query($query)->fetch();
        $user_id = $before_data['id'];
        $fake_userid = $user_id + 2897;
        $resp = $this->delete("/v1/delete/$fake_userid");
        $payload = $resp->getBody();
        $before_dl = ($this->db->query("SELECT count(id) as total FROM users")->fetch())['total'];

        // Expected result
        $expectedPayload = [
            'code' => 401,
            'message' => 'Unauthorized request',
        ];
        // Check if user data is changed
        $after_dl = ($this->db->query("SELECT count(id) as total FROM users")->fetch())['total'];

        // Check testing resutl
        $this->assertTrue($before_dl == $after_dl, "Deleted Unintentionally");
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
    }
    /**
     * PASSED CASE(S)
     */
    /**
     * Delete users successfully
     */
    public function testDeleteUserSuccess()
    {
        // get test user
        $query = "SELECT * from users where username = '$this->username'";
        $before_data = $this->db->query($query)->fetch();
        $user_id = $before_data['id'];

        $resp = $this->delete("/v1/delete/$user_id");
        $payload = $resp->getBody();
        $expectedPayload = new ActionPayload(200, 'Delete successfully');
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));

        // Check if user is deleted successfully
        $status = $this->db->query("SELECT * FROM users where id = $user_id")->rowCount() == 0;
        $this->assertTrue($status, "Didnot delete user successfully");

        // Rollback any changes in db
        // $this->db->rollback();
    }
}
