<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('UserModel');
    }

    public function login() {
        $this->call->view('events/login', array('error' => null));
    }

    public function authenticate() {
        $email = $this->io->post('email');
        $pass  = $this->io->post('password');
        $user  = $this->UserModel->find_by_email($email);
      
       if ($user && $pass === $user['password_hash'])  {
            $this->session->set_userdata(array(
                'user_id'   => $user['id'],
                'full_name' => $user['full_name'],
                'role'      => $user['role']
            ));
            redirect('events');
        }
        $this->call->view('events/login', array('error' => 'Invalid email or password.'));
    }

    public function logout() {
        $this->session->unset_userdata(array('user_id', 'full_name', 'role'));
        redirect('login');
    }
}