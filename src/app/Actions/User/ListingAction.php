<?php

declare(strict_types=1);

namespace Hoanvv\App\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Hoanvv\App\Actions\Action;
use Hoanvv\App\Domain\Repositories\User\IUserRepository;
use Hoanvv\App\Factory\LoggerFactory;
use Hoanvv\App\Database\IMasterDatabase;
use Slim\Exception\HttpBadRequestException;

class ListingAction extends Action
{
    /**
     * @var IUserRepository
     */
    protected $userRepository;

    protected LoggerFactory $logger;

    protected IMasterDatabase $masterDB;
    public function __construct(IUserRepository $userRepository, LoggerFactory $logger, IMasterDatabase $masterDB)
    {
        $this->userRepository = $userRepository;
        $this->masterDB = $masterDB;
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
        $this->statusCode = 400;
        if ($username && $password) {
            // Check if username exists
            $query = "SELECT * from users where username = '$username' LIMIT 1";
            $usernameExists = $this->masterDB->query($query);
            if ($usernameExists->rowCount()) {
                $respMsg = 'Username already exists';
            } else {
                //insert user data info
                $query = "INSERT INTO users (username, password) VALUES (:username, :password)";
                $preDB = $this->masterDB->prepare($query);
                $preDB->bindParam(':username', $username);
                $preDB->bindParam(':password', $password);
                $this->statusCode = $preDB->execute() ? 200 : $this->statusCode;
                $respMsg = $this->statusCode ? "Successfully registered" : "Failed to register user";
            }
        } else {
            $respMsg = 'Username or Password cannot be empty';
        }
        return $this->respondWithData($respMsg, $this->statusCode, $response);
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
            $respMsg = [
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name']
            ];
        } else {
            $respMsg = "Invalid data";
        }
        return $this->respondWithData($respMsg, $this->statusCode, $response);
    }

    /**
     * update user profile
     * @return Response
     */
    public function updateUser($request, $response, $arg): Response
    {
        $params = $request->getParsedBody();
        $password = $params['password'] ?? '';
        $firstName = $params['first_name'] ?? '';
        $lastName = $params['last_name'] ?? '';
        $userId = $arg['user_id'];
        if ($password || $firstName || $lastName) {
            $updateStr = '';
            // update pwd
            $updateStr .= $password ? " password = '$password'" : '';
            // update first name
            $updateStr .= $firstName ? ($updateStr ? " ,first_name = '$firstName'" : "first_name = '$firstName'") : '';
            // update last name
            $updateStr .= $lastName ? ($updateStr ? " ,last_name = '$lastName'" : "last_name = '$lastName'") : '';

            $query = "UPDATE users SET $updateStr WHERE id = '$userId'";
            $status = $this->masterDB->query($query);
            $respMsg =  $status ? "Update successfully" : "Update failed";
            $this->statusCode = $status ? 200 : 400;
        } else {
            throw new HttpBadRequestException($request, "Bad request");
        };
        return $this->respondWithData($respMsg, $this->statusCode, $response);
    }

    /**
     * delete user
     * @return Response
     */
    public function deleteUser($request, $response): Response
    {
        $userId = ($request->getAttribute('userData'))['id'];
        $query = "DELETE FROM users WHERE id = '$userId'";
        $status = $this->masterDB->query($query);
        $respMsg = $status ? "Delete successfully" : "Delete failed";
        $this->statusCode = $status ? 200 : 400;
        return $this->respondWithData($respMsg, $this->statusCode, $response);
    }
}
