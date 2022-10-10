<?php

namespace Tests\Application\Actions;

use Hoanvv\Test\TestCase;
use Slim\App;
use Prophecy\PhpUnit\ProphecyTrait;

use Hoanvv\App\Actions\ActionPayload;
use Hoanvv\App\Database\IMasterDatabase;
use DateTimeImmutable;

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
        // get DB connection
        $container = $this->app->getContainer();
        $this->db = $container->get(IMasterDatabase::class);
        $date = (new DateTimeImmutable())->format('YmdHis');
        $this->date = $date; // time or running tests
        $this->username = "username_$date";
        $this->password = "pwd_$date";
    }

    public function createApplication(): App
    {
        return (require __DIR__ . '/../../config/bootstrap.php');
    }
    public function createNewUserForTesting()
    {
        $userData = [
            'username' => $this->username,
            'password' => $this->password
        ];
        // Mock a request
        $this->post('/register', $userData);
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
        $this->db->beginTransaction();
        
        $this->createNewUserForTesting();
        // Check if data is inserted successfully
        $query = "SELECT * FROM users where username = '$this->username' and password = '$this->password'";
        $result = $this->db->query($query);
        $rowCount = $result->rowCount();
        // Check testing results
        $this->assertTrue($rowCount == 1, "Create New User Failed: Find $rowCount record(s)");
        $this->db->rollback();

    }

    /**
     * create EXISTING user
     * 
     * Provide an available username
     */
    public function testRegisterExistingUsername()
    {
        $this->db->beginTransaction();
        $this->createNewUserForTesting();

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
        $this->db->rollback();
    }
    /**
     * create user: LACK of information
     * 
     * Provide only username or password for registration
     */
    public function testRegisterUsernameNotEnoughInfo()
    {
        $this->db->beginTransaction();
        $this->createNewUserForTesting();

        // lack of username
        $userData1 = [
            'password' => $this->password . '_lackofusername',
        ];
        // lack of password
        $userData2 = [
            'username' => $this->username . '_lackofpassword',
        ];

        // Mock a request
        $resp1 = $this->post('/register', $userData1);
        $resp2 = $this->post('/register', $userData2);
        $payload1 = $resp1->getBody();
        $payload2 = $resp2->getBody();
        // Check if the data is imported
        $query1 = "SELECT * FROM users where password = '$this->password" . "_lackofusername' ";
        $query2 = "SELECT * FROM users where username = '$this->username" . "_lackofpassword' ";

        $result1 = $this->db->query($query1)->rowCount() == 0;
        $result2 = $this->db->query($query2)->rowCount() == 0;
        // Expected Payload
        $expectedPayload = new ActionPayload(400, "Username or Password cannot be empty");

        // Check testing results
        $this->assertTrue($result1, "Register user lacking username");
        $this->assertTrue($result2, "Register user lacking password");

        $this->assertSame(json_encode($payload1), json_encode($expectedPayload));
        $this->assertSame(json_encode($payload2), json_encode($expectedPayload));
        $this->db->rollback();

    }

    /**
     * TEST CASE(S) FOR GET USER API
     */

    /**
     * get available user (from above)
     */
    public function testGetAvailableUser()
    {
        $this->db->beginTransaction();
        $this->createNewUserForTesting();

        // Mock a request
        $userId = ($this->db->query("SELECT id FROM users where username = '$this->username'")->fetch())['id'];
        $resp = $this->get("/v1/info/$userId");
        $payload = $resp->getBody();
        // Expected result
        $expectedPayload = new ActionPayload(200, [
            'first_name' => null,
            'last_name' => null,
        ]);
        // Checking testing result
        $this->assertSame(json_encode($expectedPayload), json_encode($payload), "user id = $userId");
        $this->db->rollback();
    }
    /**
     * get unavailable user id in INT (no exist)
     * 
     * This test includes checking functionality of authmiddleware
     */
    public function testGetUnavailableUsers()
    {
        $this->db->beginTransaction();
        $this->createNewUserForTesting();
        // Find the current userid test
        $query = "SELECT id from users where username = '$this->username'";
        $result = $this->db->query($query)->fetch();
        $userId = $result['id'];
        $unavaiId = $userId + 2897;
        // Mock a request with an unavailable user_id
        $resp = $this->get("/v1/info/$unavaiId");
        $payload = $resp->getBody();
        // Expected result
        $expectedPayload = [
            'code' => 401,
            'message' => 'Unauthorized request'
        ];
        // Checking testing result
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
        $this->db->rollback();

    }
    /**
     * get user with wrong pattern of user_id (string instead int)
     * 
     * This test includes checking methods of requests
     */
    public function testGetUserIdInString()
    {
        $this->db->beginTransaction();
        $this->createNewUserForTesting();
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
        $this->db->rollback();

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
        $this->db->beginTransaction();
        $this->createNewUserForTesting();

        $userUpdate = [
            'password' => "newpwd_$this->date",
            'first_name' => "firstname_$this->date",
            'last_name' => "lastname_$this->date",
        ];
        // get test user
        $query = "SELECT id from users where username = '$this->username'";
        $result = $this->db->query($query)->fetch();
        $userId = $result['id'];
        $resp = $this->put("/v1/update/$userId", $userUpdate);
        $payload = $resp->getBody();
        // Expected output
        $expectedPayload = new ActionPayload(200, 'Update successfully');
        // Get data after update
        $query = "SELECT * FROM users WHERE password = :password  AND first_name = :first_name AND last_name = :last_name ";
        $exec = $this->db->prepare($query);
        $exec->bindParam(':password', $userUpdate['password']);
        $exec->bindParam(':first_name', $userUpdate['first_name']);
        $exec->bindParam(':last_name', $userUpdate['last_name']);
        $exec->execute();
        $rowCount = $exec->rowCount();
        $affectedId = $exec->fetch()['id'];
        // Check test result
        $this->assertTrue($rowCount == 1, "Can't find User after UPDATE");
        $this->assertTrue($affectedId == $userId, "Update wrong user");
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
        $this->db->rollback();

    }
    /**
     * update some information
     */
    public function testUpdateUserSomeSuccess()
    {
        $this->db->beginTransaction();
        $this->createNewUserForTesting();

        $userUpdate = [
            'first_name' => "new_firstname_$this->date",
            'last_name' => "new_lastname_$this->date"
        ];
        // get test user
        $query = "SELECT id from users where username = '$this->username'";
        $result = $this->db->query($query)->fetch();
        $userId = $result['id'];
        // For this func, no need to check output response
        $this->put("/v1/update/$userId", $userUpdate);
        // Get data after update
        $query = "SELECT * FROM users WHERE first_name = :first_name AND last_name = :last_name";
        $exec = $this->db->prepare($query);
        $exec->bindParam(':first_name', $userUpdate['first_name']);
        $exec->bindParam(':last_name', $userUpdate['last_name']);
        $exec->execute();
        $rowCount = $exec->rowCount();
        $affectedId = $exec->fetch()['id'];
        // Check test result
        $this->assertTrue($rowCount == 1, "Can't find User after UPDATE");
        $this->assertTrue($affectedId == $userId, "Update wrong user");
        $this->db->rollback();

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
        $this->db->beginTransaction();
        $this->createNewUserForTesting();

        $query = "SELECT * from users where username = '$this->username'";
        $beforeData = $this->db->query($query)->fetch();
        $userId = $beforeData['id'];
        $resp = $this->put("/v1/update/$userId", []);
        $payload = $resp->getBody();
        // Expected result
        $expectedPayload = [
            'code' => 400,
            'message' => "Bad request"
        ];
        // Check if user data is changed
        $data = $this->db->query("SELECT * FROM users where id = $userId")->fetch();
        $status = $beforeData['password'] == $data['password']
            && $beforeData['first_name'] == $data['first_name']
            && $beforeData['last_name'] == $data['last_name'];
        $this->assertTrue($status, "Updated Unintentionally");
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
        $this->db->rollback();

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
        $this->db->beginTransaction();
        $this->createNewUserForTesting();

        // get test user
        $query = "SELECT * from users where username = '$this->username'";
        $beforeData = $this->db->query($query)->fetch();
        $userId = $beforeData['id'];
        $fakeUserId = $userId + 2897;
        $resp = $this->delete("/v1/delete/$fakeUserId");
        $payload = $resp->getBody();
        $beforeDl = ($this->db->query("SELECT count(id) as total FROM users")->fetch())['total'];

        // Expected result
        $expectedPayload = [
            'code' => 401,
            'message' => 'Unauthorized request',
        ];
        // Check if user data is changed
        $afterDl = ($this->db->query("SELECT count(id) as total FROM users")->fetch())['total'];

        // Check testing resutl
        $this->assertTrue($beforeDl == $afterDl, "Deleted Unintentionally");
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));
        $this->db->rollback();

    }
    /**
     * PASSED CASE(S)
     */
    /**
     * Delete users successfully
     */
    public function testDeleteUserSuccess()
    {
        $this->db->beginTransaction();
        $this->createNewUserForTesting();

        // get test user
        $query = "SELECT * from users where username = '$this->username'";
        $beforeData = $this->db->query($query)->fetch();
        $userId = $beforeData['id'];
        $resp = $this->delete("/v1/delete/$userId");
        $payload = $resp->getBody();
        $expectedPayload = new ActionPayload(200, 'Delete successfully');
        $this->assertSame(json_encode($expectedPayload), json_encode($payload));

        // Check if user is deleted successfully
        $status = $this->db->query("SELECT * FROM users where id = $userId")->rowCount() == 0;
        $this->assertTrue($status, "Didnot delete user successfully");

        // Rollback any changes in db
        $this->db->rollback();
    }
}
