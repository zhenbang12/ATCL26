<?php
// Front controller for the Camp Management System

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Core\Router;

$router = new Router();

// -------- Route Definitions --------

// Home / dashboard
$router->get('/', 'HomeController@index');
$router->get('/dashboard', 'HomeController@dashboard');
$router->get('/public', 'HomeController@public');
$router->get('/insights', 'InsightsController@index');

// Site settings (advisor / committee)
$router->get('/settings/landing', 'SettingsController@landingPage');
$router->post('/settings/landing/save', 'SettingsController@landingPageSave');
$router->post('/settings/registration/save', 'SettingsController@registrationSettingsSave');
$router->get('/settings/backup', 'BackupController@showPage');
$router->post('/settings/backup/run', 'BackupController@runBackup');
$router->post('/settings/backup/restore', 'BackupController@runRestore');

// User management (superuser only)
$router->get('/users', 'UserController@index');
$router->post('/users/create', 'UserController@create');
$router->post('/users/reset-password', 'UserController@resetPassword');
$router->post('/users/delete', 'UserController@delete');

// Auth
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// 1. Participant & Admission Management
$router->get('/participants', 'ParticipantController@index');
$router->get('/participants/list', 'ParticipantController@list');
$router->get('/participants/data', 'ParticipantController@tableData');
$router->get('/participants/create', 'ParticipantController@create');
$router->get('/participants/create-walkin', 'ParticipantController@createWalkIn');
$router->post('/participants/store', 'ParticipantController@store');
$router->get('/participants/lookup', 'ParticipantController@lookupForm');
$router->post('/participants/lookup', 'ParticipantController@lookup');
$router->get('/participants/checkin', 'ParticipantController@checkinForm');
$router->post('/participants/checkin', 'ParticipantController@processCheckin');
$router->get('/participants/groups', 'ParticipantController@groups');
$router->post('/participants/auto-group', 'ParticipantController@autoGroup');
$router->post('/participants/group-by-faculty', 'ParticipantController@groupByFaculty');
$router->post('/participants/group-by-language', 'ParticipantController@groupByLanguage');
$router->post('/participants/groups/save-layout', 'ParticipantController@saveGroupLayout');
$router->post('/participants/groups/add-group', 'ParticipantController@addGroupShell');
$router->post('/participants/groups/add-slot', 'ParticipantController@addGroupSlot');
$router->post('/participants/groups/adjust-slot', 'ParticipantController@adjustGroupSlot');
$router->post('/participants/groups/move', 'ParticipantController@moveParticipantGroup');
$router->post('/participants/groups/bulk-move', 'ParticipantController@bulkMoveParticipantGroup');
$router->post('/participants/groups/assign-facilitator', 'ParticipantController@assignFacilitatorToGroup');
$router->post('/participants/groups/assign-facilitators-bulk', 'ParticipantController@assignFacilitatorsBulk');
$router->get('/participants/assign-buddy', 'ParticipantController@assignBuddy');
$router->get('/participants/groups/state', 'ParticipantController@groupsState');
$router->post('/participants/clear-groups', 'ParticipantController@clearGroups');
$router->post('/participants/clear-group-shells', 'ParticipantController@clearGroupShells');
$router->get('/participants/export', 'ParticipantController@export');
$router->get('/participants/export-groups', 'ParticipantController@exportGroups');
$router->get('/participants/duplicates', 'ParticipantController@duplicates');
$router->post('/participants/duplicates/resolve', 'ParticipantController@resolveDuplicate');
$router->post('/participants/duplicates/unresolve', 'ParticipantController@unresolveDuplicate');
$router->get('/participants/anomalies', 'ParticipantController@anomalies');
$router->post('/participants/remove-anomaly', 'ParticipantController@removeAnomaly');
$router->get('/participants/audit-logs', 'ParticipantController@auditLogs');
$router->get('/participants/status', 'ParticipantController@checkStatus');
$router->get('/participants/edit', 'ParticipantController@edit');
$router->post('/participants/update', 'ParticipantController@update');
$router->post('/participants/delete', 'ParticipantController@delete');
$router->get('/participants/verify-edit', 'ParticipantController@verifyEditForm');
$router->post('/participants/verify-edit', 'ParticipantController@processVerifyEdit');
$router->get('/participants/edit-public', 'ParticipantController@editPublicForm');
$router->post('/participants/update-public', 'ParticipantController@updatePublic');

// 2. Financial Control & Procurement
$router->get('/finance', 'FinanceController@index');
$router->get('/finance/claims', 'FinanceController@claims');
$router->post('/finance/claims/store', 'FinanceController@storeClaim');
$router->post('/finance/claims/approve', 'FinanceController@approveClaim');
$router->post('/finance/claims/reject', 'FinanceController@rejectClaim');
$router->get('/finance/claims/edit', 'FinanceController@editClaim');
$router->post('/finance/claims/update', 'FinanceController@updateClaim');
$router->get('/finance/buying-requests', 'FinanceController@buyingRequests');
$router->post('/finance/buying-requests/store', 'FinanceController@storeBuyingRequest');
$router->post('/finance/buying-requests/approve', 'FinanceController@approveBuyingRequest');
$router->post('/finance/buying-requests/reject', 'FinanceController@rejectBuyingRequest');
$router->get('/finance/buying-requests/edit', 'FinanceController@editBuyingRequest');
$router->post('/finance/buying-requests/update', 'FinanceController@updateBuyingRequest');
$router->get('/finance/budget', 'FinanceController@budgetDashboard');

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

// 3. Event Operations & Crew
$router->get('/operations', 'OperationsController@index');
$router->get('/operations/crew', 'OperationsController@crew');
$router->get('/operations/crew/create', 'OperationsController@createCrew');
$router->post('/operations/crew/store', 'OperationsController@storeCrew');
$router->get('/operations/crew/edit', 'OperationsController@editCrew');
$router->post('/operations/crew/update', 'OperationsController@updateCrew');
$router->post('/operations/crew/update-facilitator', 'OperationsController@updateFacilitator');
$router->post('/operations/crew/delete', 'OperationsController@deleteCrew');
$router->get('/operations/games', 'OperationsController@games');

// 4. Logistics & Governance
$router->get('/logistics', 'LogisticsController@index');
$router->get('/logistics/venues', 'LogisticsController@venues');
$router->get('/logistics/inventory', 'LogisticsController@inventory');
$router->get('/governance', 'GovernanceController@index');
$router->get('/governance/tasks', 'GovernanceController@tasks');
$router->get('/governance/proposals', 'GovernanceController@proposals');

// Lost & Found
$router->get('/lost-and-found', 'LostAndFoundController@index');
$router->get('/lost-and-found/create', 'LostAndFoundController@create');
$router->post('/lost-and-found/store', 'LostAndFoundController@store');
$router->get('/lost-and-found/edit', 'LostAndFoundController@edit');
$router->post('/lost-and-found/update', 'LostAndFoundController@update');
$router->post('/lost-and-found/delete', 'LostAndFoundController@delete');
$router->post('/lost-and-found/mark-returned', 'LostAndFoundController@markReturned');
$router->post('/lost-and-found/bulk-delete', 'LostAndFoundController@bulkDelete');
$router->post('/lost-and-found/bulk-unclaim', 'LostAndFoundController@bulkMarkUnclaimed');
$router->get('/lost-and-found/public', 'LostAndFoundController@publicView');
$router->get('/lost-and-found/claim', 'LostAndFoundController@claimForm');
$router->post('/lost-and-found/submit-claim', 'LostAndFoundController@submitClaim');

// Session management
$router->get('/sessions', 'SessionController@index');
$router->get('/sessions/create', 'SessionController@create');
$router->post('/sessions/store', 'SessionController@store');
$router->post('/sessions/activate', 'SessionController@activate');
$router->post('/sessions/set-default', 'SessionController@setDefault');
$router->post('/sessions/delete', 'SessionController@delete');

// Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
