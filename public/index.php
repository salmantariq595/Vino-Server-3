<?php
// index.php

require_once '../src/config/database.php';
require_once '../src/helpers/returnResponse.php';
require_once '../src/helpers/errorCodes.php';
require_once '../src/helpers/auth.php';
require_once '../src/helpers/requestParse.php';
require_once '../src/helpers/userAuthCheck.php';

require '../src/controllers/AppApiController.php';
require '../src/controllers/AuthController.php';

require '../vendor/autoload.php';
// require __DIR__ . '/vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use GuzzleHttp\Client;
use Rakit\Validation\Validator;


// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();


$app = AppFactory::create();

$appController = new AppApiController();
$authController = new AuthController();

?>

<?php

$app->get('/api/v2/{bankId}/hello', function (Request $request, Response $response, array $args) {
    $bankid = $args['bankid'];
    $queryParams = $request->getQueryParams();
    $data = array_merge(['bankid' => $bankid], $queryParams);
    $validator = new Validator();
    $validation = $validator->make($data, [
        'bankid' => 'required',
        'testcode' => 'required|numeric'
    ]);

    // Perform validation
    $validation->validate();

    // Check if validation fails
    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        return sendCustomResponse("Validation Error", $errors, 400, 400);
    }

    $responseData = [
        'bankId' => $bankid,
        'testcode' => $queryParams['testcode'] ?? null
    ];


    return sendCustomResponse('Hello world', $responseData, 1002, 200);
});

$app->get('/api/v2/{bankId}/validate-connection', function (Request $request, Response $response, array $args) {
    $bankId = $args['bankId'];
    $queryParams = $request->getQueryParams();

    // Combine path and query parameters for validation
    $data = array_merge(['bankId' => $bankId], $queryParams);

    $validator = new Validator();
    $validation = $validator->make($data, [
        'bankId' => 'required',
    ]);

    // Perform validation
    $validation->validate();

    // Check if validation fails
    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        return sendCustomResponse($response, "Validation Error", $errors, 400, 400);
    }

    try {
        // Attempt to establish a connection
        clearstatcache();
        $pdo = Database::getConnection($bankId);

        // If connection is successful
        return sendCustomResponse(
            "Connection established successfully for bank ID: $bankId",
            "Success",
            1002,
            200,
            200
        );
    } catch (Exception $e) {
        // If there's an error
        return sendCustomResponse(
            $response,
            "Error",
            $e->getMessage(),
            500,
            500
        );
    }
});

// ******************************************** Auth endpoimts START ************************************************


// register new customer
$app->post('/api/v2/{bankId}/auth/register-user-customer-new', function (Request $request, Response $response, array $args) use ($appController) {
    $bankId = (int)$args['bankId'];
    $rawBody = $request->getBody()->__toString();
    $data = json_decode($rawBody, true);


    $result = $appController->registerNewCustomer($bankId, $data);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});



// register existing customer
$app->post('/api/v2/{bankId}/auth/register-user-customer-exist', function (Request $request, Response $response, array $args) use ($appController) {
    $bankId = (int)$args['bankId'];
    $rawBody = $request->getBody()->__toString();
    $data = json_decode($rawBody, true);


    $result = $appController->registerExistCustomer($bankId, $data);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});

// generate user app token

$app->post('/api/v2/{bankId}/auth/generate-token', function (Request $request, Response $response, array $args) use ($appController) {
    $bankId = (int)$args['bankId'];
    $rawBody = $request->getBody()->__toString();
    $data = json_decode($rawBody, true);

    $authController = new AuthController();
    $result = $authController->mobileLoginNewLogic($bankId, $data);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});

// ******************************************** Auth endpoimts END ************************************************

// ********************************************************************************************
// **************************************************************************************
// ********************************************************************************
// ******************************************** User endpoimts START ************************************************

// get current user's information
$app->get('/api/v2/{bankId}/app/user/current', function (Request $request, Response $response, array $args) use ($appController) {

    session_start();

    $jwtToken = getBearerToken();
    $user = authenticateUser($jwtToken);

    if (!$user) {
        sendCustomResponse('Token Expired. Please Login again', null, 401, 401);
    }

    $result = $appController->currentUser((int)$args['bankId'], $user);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});


// get current user's data
$app->get('/api/v2/{bankId}/app/user/current/accounts/balance', function (Request $request, Response $response, array $args) use ($appController) {

    session_start();

    $jwtToken = getBearerToken();
    $user = authenticateUser($jwtToken);

    if (!$user) {
        sendCustomResponse('Unauthorized', null, 401, 401);
    }

    $result = $appController->currentUserAccountBalance((int)$args['bankId'], $user);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});


$app->post('/api/v2/{bankId}/app/user/pin-create', function (Request $request, Response $response, array $args) use ($appController) {

    $user = userAuthVerify();
    $requestData = requestParse($request);

    $result = $appController->userPinCreate((int)$args['bankId'], $requestData, $user);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});


$app->put('/api/v2/{bankId}/app/user/pin-change', function (Request $request, Response $response, array $args) use ($appController) {

    $user = userAuthVerify();
    $requestData = requestParse($request);

    $result = $appController->userPinUpdate((int)$args['bankId'], $requestData, $user);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});

$app->put('/api/v2/{bankId}/app/user/password-change', function (Request $request, Response $response, array $args) use ($appController) {

    // $user = userAuthVerify();
    $requestData = requestParse($request);

    $result = $appController->userPasswordUpdate((int)$args['bankId'], $requestData);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});



$app->post('/api/v2/{bankId}/app/user/pin-verify', function (Request $request, Response $response, array $args) use ($appController) {
    $user = userAuthVerify();
    $requestData = requestParse($request);

    $result = $appController->userPinVerify((int)$args['bankId'], $requestData, $user);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});


// ********************************************************************************************
// **************************************************************************************
// ********************************************************************************
// ******************************************** Common endpoimts START ************************************************



$app->get('/api/v2/{bankId}/app/common/account-type', function (Request $request, Response $response, array $args) use ($appController) {

    $result = $appController->getAccountType((int)$args['bankId']);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});


$app->get('/api/v2/{bankId}/app/common/config', function (Request $request, Response $response, array $args) use ($appController) {
    // $requestData = requestParse($request);
    $queryParams = $request->getQueryParams();
    $result = $appController->getConfig((int)$args['bankId'], $queryParams);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});

$app->post('/api/v2/{bankId}/app/common/password-reset', function (Request $request, Response $response, array $args) use ($appController) {
    $requestData = requestParse($request);
    $result = $appController->resetPassword((int)$args['bankId'], $requestData);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});

$app->post('/api/v2/{bankId}/app/common/file-upload', function (Request $request, Response $response, array $args) use ($appController) {
    $requestData = requestParse($request);
    $result = $appController->uploadImage((int)$args['bankId'], $requestData);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});


// ********************************************************************************************
// **************************************************************************************
// ********************************************************************************
// ******************************************** TRANSACTION endpoimts START ************************************************

// $groupPrefixTrans = 'api/v2/{bankid}/app/transaction';

$app->get('/api/v2/{bankId}/app/transaction', function (Request $request, Response $response, array $args) use ($appController) {
    $user = userAuthVerify();
    $requestData = requestParse($request);
    $result = $appController->getTransaction((int)$args['bankId'], $requestData);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});

$app->get('/api/v2/{bankId}/app/transaction/bank-list', function (Request $request, Response $response, array $args) use ($appController) {
    $user = userAuthVerify();

    $result = $appController->getBankList((int)$args['bankId']);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});

$app->get('/api/v2/{bankId}/app/transaction/telco-networks', function (Request $request, Response $response, array $args) use ($appController) {
    $user = userAuthVerify();

    $result = $appController->getTelecoNetworks((int)$args['bankId']);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});

$app->get('/api/v2/{bankId}/app/transaction/find-account-info', function (Request $request, Response $response, array $args) use ($appController) {
    $user = userAuthVerify();
    $requestData = requestParse($request);

    $result = $appController->getAccountInfo((int)$args['bankId'], $requestData);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});





// Test function 
$app->get('/api/v2/{bankId}/app/get-service-id', function (Request $request, Response $response, array $args) use ($appController) {
    $user = userAuthVerify();
    $requestData = requestParse($request);

    $localDb = new LocalDbController(Database::getConnection('mysql'));
    $result = $localDb->getServicebyId((int)$args['bankId'], $requestData);

    if ($result['code'] == 200) {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    } else {
        return sendCustomResponse($result['message'], $result['data'], $result['dcode'], $result['code']);
    }
});




$app->run();
?>