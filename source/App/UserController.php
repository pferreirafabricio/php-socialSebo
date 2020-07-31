<?php 

namespace Source\App;

use Source\App\Controller;
use Source\Models\User;
use Source\Database\UserDB;

class UserController extends Controller
{
    private $userDB;

    public function __construct()
    {
        $this->userDB = new UserDB();
    }


    public function create(): void
    {
        $filters = [
            'name' => FILTER_SANITIZE_STRING,
            'email' => FILTER_SANITIZE_EMAIL,
            'password' => FILTER_SANITIZE_STRING,
            'confirmPassword' => FILTER_SANITIZE_STRING,
        ];

        $data = postAll($filters);
        $data = array_map('strip_tags', $data);
        $data =  array_map('trim', $data);
        $userData = (object) $data;

        $user = new User(
                get('id', FILTER_SANITIZE_NUMBER_INT),
                $userData->name,
                $userData->email,
                $userData->password,
                1,
                null);

        $errors = $this->validate($user);

        if ($errors !== []) {
            echo $this->error('Form data invalid!', $errors, 400);
        }

        $user->setPassword(passwordHash($user->getPassword()));

        if ($this->userDB->verifyIfEmailExists($user->getEmail())) {
            echo $this->error('This email already exists!', [
                "The given email is already in use by another user"
            ] , 500);
            die();
        }

        if (!$this->userDB->insert($user)) {
            echo $this->error('User register failed!', [
                "Something was wrong on user registration, please try again in 5 minutes"
            ] , 500);
            die();
        }
        
        // Create view for success messages
        echo $this->success("User create successfully!", 200, 'login');
    }
    
    /**
     * Validate all User data
     *
     * @param  User $data User data to be validated
     * @return array
     */
    public function validate(User $user): array
    {
        $emailRegex = '/^([a-zA-Z0-9\.\+\-\_]{5,60})\@([a-zA-Z0-9\.\+\-\_]{2,10})\.([a-zA-Z0-9]{2,10}).+$/';
        $passwordRegex = '/([a-zA-Z0-9]){2,60}/';
        $nameRegex = '/([a-zA-Z]){2,60}/';
        $errors = [];

        if (!preg_match($nameRegex, $user->getName()))
            $errors[] = 'Name is invalid';

        if (!preg_match($emailRegex, $user->getEmail()))
            $errors[] = 'Email is invalid';

        if (!preg_match($passwordRegex, $user->getPassword()))
            $errors[] = 'Password is invalid';

        // if ($user->password !== $user->confirmPassword)
        //     $errors[] = 'Password must be equals is invalid';
            
        return $errors;
    }
    
    /**
     * Create a User object with data sent
     *
     * @return User
     */
    private function getInput(int $id = null): User
    {
        return new User(
            filter_var($id, FILTER_VALIDATE_INT),
            post('name'),
            post('email'),
            post('password'),
            post('confirmPassword'),
            1,
            null
        );
    }
}