<?php

declare(strict_types=1);

namespace Hoanvv\App\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Hoanvv\App\Actions\Action;
use Hoanvv\App\Domain\Repositories\User\IUserRepository;
use Hoanvv\App\Factory\LoggerFactory;
use Hoanvv\App\Database\MasterDatabase;
use Slim\Exception\HttpBadRequestException;

class ListingAction extends Action
{
    /**
     * @var IUserRepository
     */
    protected $userRepository;

    protected LoggerFactory $logger;

    public function __construct(IUserRepository $userRepository, LoggerFactory $logger)
    {
        $this->userRepository = $userRepository;
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->respondWithData($users);
    }
    /**
     * register new users
     * @return Response
     */
    public function registerUser($request, $response): Response
    {
        $params = $request->getParsedBody();
        $username = $params['username'] ?? '';
        $password = $params['password'] ?? '';
        $statusCode = 400;
        if ($username && $password) {
            $db = new MasterDatabase();
            $db = $db->db();
            // Check if username exists
            $query = "SELECT * from users where username = '$username' LIMIT 1";
            $username_exists = $db->query($query);
            if ($username_exists->rowCount()) {
                $resp_msg = 'Username already exists';
            } else {
                //insert user data info
                $query = "INSERT INTO users (username, password) VALUES (:username, :password)";
                $pre_db = $db->prepare($query);
                $pre_db->bindParam(':username', $username);
                $pre_db->bindParam(':password', $password);
                $this->statusCode = $pre_db->execute() ? 200 : $statusCode;
                $resp_msg = $this->statusCode ? "Successfully registered" : "Failed to register user";
            }
        } else {
            $resp_msg = 'Username or Password cannot be empty';
        }
        return $this->respondWithData($resp_msg, $this->statusCode, $response);
    }

    /**
     * Read user information
     * 
     * @return Response
     */
    public function findUser($request, $response): Response
    {
        $userData = $request->getAttribute('userData');

        if ($userData) {
            $this->statusCode = 200;
            $resp_msg = [
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name']
            ];
        } else {
            $resp_msg = "Invalid data";
        }
        return $this->respondWithData($resp_msg, $this->statusCode, $response);
    }

    /**
     * update user profile
     * @return Response
     */
    public function updateUser($request, $response, $arg): Response
    {
        $params = $request->getParsedBody();
        $password = $params['password'] ?? '';
        $first_name = $params['first_name'] ?? '';
        $last_name = $params['last_name'] ?? '';
        $user_id = $arg['user_id'];
        if ($password || $first_name || $last_name) {
            $update_str = '';
            // update pwd
            $update_str .= $password ? " password = '$password'" : '';
            // update first name
            $update_str .= $first_name ? ($update_str ? " ,first_name = '$first_name'" : "first_name = '$first_name'") : '';
            // update last name
            $update_str .= $last_name ? ($update_str ? " ,last_name = '$last_name'" : "last_name = '$last_name'") : '';

            $query = "UPDATE users SET $update_str WHERE id = '$user_id'";
            $db = (new MasterDatabase())->db();
            $status = $db->query($query);
            $resp_msg =  $status ? "Update successfully" : "Update failed";
            $this->statusCode = $status ? 200 : 400;
        } else {
            throw new HttpBadRequestException($request, "Bad request");
        };
        return $this->respondWithData($resp_msg, $this->statusCode, $response);
    }

    /**
     * delete user
     * @return Response
     */
    public function deleteUser($request, $response): Response
    {
        $user_id = ($request->getAttribute('userData'))['id'];
        $db = new MasterDatabase();
        $db = $db->db();
        $query = "DELETE FROM users WHERE id = '$user_id'";
        $status = $db->query($query);
        $resp_msg = $status ? "Delete successfully" : "Delete failed";
        $this->statusCode = $status ? 200 : 400;
        return $this->respondWithData($resp_msg, $this->statusCode, $response);
    }
}
