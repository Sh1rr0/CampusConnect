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
$router->post('/admin/events/{id}/cancel', 'AdminController::cancel');
$router->post('/admin/events/{id}/reopen', 'AdminController::reopen');

// ---- auth + students ----
$router->post('/api/login', 'ApiController::login');
$router->post('/api/signup', 'ApiController::signup');
$router->post('/api/logout', 'ApiController::logout');
$router->get('/api/me', 'ApiController::me');
$router->get('/api/programs', 'ApiController::programs');
$router->get('/api/events', 'ApiController::events');
$router->get('/api/events/{id}', 'ApiController::event');
$router->post('/api/events/{id}/register', 'ApiController::register');
$router->get('/api/my-registrations', 'ApiController::my_registrations');

// ---- admin: organizations and manager accounts ----
$router->get('/api/admin/organizations', 'ApiAdminController::organizations');
$router->post('/api/admin/organizations', 'ApiAdminController::create_organization');
$router->post('/api/admin/organizations/{id}/update', 'ApiAdminController::update_organization');
$router->post('/api/admin/organizations/{id}/delete', 'ApiAdminController::delete_organization');
$router->get('/api/admin/managers', 'ApiAdminController::managers');
$router->post('/api/admin/managers', 'ApiAdminController::create_manager');
$router->post('/api/admin/managers/{id}/update', 'ApiAdminController::update_manager');
$router->post('/api/admin/managers/{id}/delete', 'ApiAdminController::delete_manager');

// ---- admin + manager: events ----
$router->get('/api/admin/events', 'ApiAdminController::events');
$router->post('/api/admin/events', 'ApiAdminController::create_event');
$router->post('/api/admin/events/{id}/update', 'ApiAdminController::update_event');
$router->post('/api/admin/events/{id}/delete', 'ApiAdminController::delete_event');
$router->post('/api/admin/events/{id}/cancel', 'ApiAdminController::cancel');
$router->post('/api/admin/events/{id}/reopen', 'ApiAdminController::reopen');
$router->post('/api/admin/events/{id}/status', 'ApiAdminController::set_status');
$router->post('/api/admin/events/{id}/fine', 'ApiAdminController::set_fine');
$router->post('/api/admin/events/{id}/request-change', 'ApiAdminController::request_change');
$router->post('/api/admin/events/{id}/charge-fines', 'ApiAdminController::charge_fines');
$router->get('/api/admin/events/{id}/attendees', 'ApiAdminController::attendees');

// ---- change requests, attendance, fines ----
$router->get('/api/admin/change-requests', 'ApiAdminController::change_requests');
$router->post('/api/admin/change-requests/{id}/approve', 'ApiAdminController::approve_change');
$router->post('/api/admin/change-requests/{id}/reject', 'ApiAdminController::reject_change');
$router->post('/api/admin/checkin', 'ApiAdminController::checkin');
$router->get('/api/admin/fines', 'ApiAdminController::fines');
$router->post('/api/admin/fines/{id}/paid', 'ApiAdminController::mark_paid');