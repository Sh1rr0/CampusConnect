<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
| -------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------
| Here is where you can register web routes for your application.
|
|
*/
/** @var object $router **/

$router->get('/', 'Welcome::index');

$router->get('/', 'EventsController::index');
$router->get('/events', 'EventsController::index');
$router->get('/events/create', 'EventsController::create');
$router->post('/events/create', 'EventsController::store');
$router->get('/events/{id}', 'EventsController::show');
$router->post('/events/{id}/register', 'EventsController::register');
$router->get('/my-events', 'EventsController::my');

$router->get('/login', 'AuthController::login');
$router->post('/login', 'AuthController::authenticate');
$router->get('/signup', 'AuthController::signup');
$router->post('/signup', 'AuthController::store_signup');
$router->get('/logout', 'AuthController::logout');

$router->get('/admin', 'AdminController::dashboard');
$router->post('/admin/events/{id}/status', 'AdminController::set_status');
$router->get('/admin/events/{id}/attendees', 'AdminController::attendees');
$router->get('/admin/events/{id}/scan', 'AdminController::scanner');
$router->post('/admin/checkin', 'AdminController::checkin');

$router->get('/admin/events/{id}/edit', 'AdminController::edit');
$router->post('/admin/events/{id}/edit', 'AdminController::update');
$router->post('/admin/events/{id}/delete', 'AdminController::delete');
$router->post('/admin/events/{id}/delete-permanently', 'AdminController::delete_permanently');