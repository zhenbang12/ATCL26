<?php
// Front controller for the Camp Management System

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Router;

$router = new Router();

// -------- Route Definitions --------

// Home / dashboard
$router->get('/', 'HomeController@index');

// Auth
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// 1. Participant & Admission Management
$router->get('/participants', 'ParticipantController@index');
$router->get('/participants/create', 'ParticipantController@create');
$router->post('/participants/store', 'ParticipantController@store');
$router->get('/participants/lookup', 'ParticipantController@lookupForm');
$router->post('/participants/lookup', 'ParticipantController@lookup');
$router->get('/participants/checkin', 'ParticipantController@checkinForm');
$router->post('/participants/checkin', 'ParticipantController@processCheckin');
$router->get('/participants/groups', 'ParticipantController@groups');
$router->post('/participants/auto-group', 'ParticipantController@autoGroup');
$router->post('/participants/group-by-faculty', 'ParticipantController@groupByFaculty');
$router->post('/participants/group-by-language', 'ParticipantController@groupByLanguage');
$router->post('/participants/clear-groups', 'ParticipantController@clearGroups');
$router->get('/participants/export', 'ParticipantController@export');

// 2. Financial Control & Procurement
$router->get('/finance', 'FinanceController@index');
$router->get('/finance/claims', 'FinanceController@claims');
$router->post('/finance/claims/store', 'FinanceController@storeClaim');
$router->get('/finance/budget', 'FinanceController@budgetDashboard');

// 3. Event Operations & Crew Management
$router->get('/operations', 'OperationsController@index');
$router->get('/operations/crew', 'OperationsController@crew');
$router->get('/operations/games', 'OperationsController@games');

// 4. Project Governance & Administration
$router->get('/governance', 'GovernanceController@index');
$router->get('/governance/tasks', 'GovernanceController@tasks');
$router->get('/governance/proposals', 'GovernanceController@proposals');

// Form Management
$router->get('/forms', 'FormController@index');
$router->get('/forms/create', 'FormController@create');
$router->post('/forms/store', 'FormController@store');
$router->get('/forms/edit', 'FormController@edit');
$router->post('/forms/update', 'FormController@update');
$router->get('/forms/view', 'FormController@view');
$router->get('/forms/submissions', 'FormController@submissions');
$router->get('/forms/summary', 'FormController@summary');
$router->post('/forms/delete', 'FormController@delete');
$router->get('/forms/public', 'FormController@publicForm');
$router->post('/forms/submit', 'FormController@submit');

// 5. Logistics & Resource Management
$router->get('/logistics', 'LogisticsController@index');
$router->get('/logistics/venues', 'LogisticsController@venues');
$router->get('/logistics/inventory', 'LogisticsController@inventory');

// Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

