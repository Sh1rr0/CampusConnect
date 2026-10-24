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
        $email = trim($this->io->post('email'));
        $pass  = $this->io->post('password');
        $user  = $this->UserModel->find_by_email($email);
        $user  = $user ? (array) $user : null;

        // Accepts real hashes (new signups) and the plain-text demo passwords
        if ($user && (password_verify($pass, $user['password_hash']) || $pass === $user['password_hash'])) {
            $this->session->set_userdata(array(
                'user_id'   => $user['id'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'role'      => $user['role']
            ));
            redirect(in_array($user['role'], array('admin', 'organizer')) ? 'admin' : 'events');
        }
        $this->call->view('events/login', array('error' => 'Invalid email or password.'));
    }

    public function signup() {
        $this->call->view('events/signup', array('error' => null));
    }

    public function store_signup() {
        $name  = trim($this->io->post('full_name'));
        $email = trim($this->io->post('email'));
        $pass  = $this->io->post('password');

        $error = null;
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter your name and a valid email.';
        elseif (strlen($pass) < 6) $error = 'Password must be at least 6 characters.';
        elseif ($this->UserModel->email_exists($email)) $error = 'That email is already registered.';

        if ($error) { $this->call->view('events/signup', array('error' => $error)); return; }

        $this->UserModel->create(array(
            'full_name' => $name, 'email' => $email,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'student'
        ));
        redirect('login');
    }

    public function logout() {
        $this->session->unset_userdata(array('user_id', 'full_name', 'email', 'role'));
        redirect('login');
    }
}