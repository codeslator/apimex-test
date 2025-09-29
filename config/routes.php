<?php

use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Middleware\RateLimitMiddleware;

return function (App $app) {
    $app->add(RateLimitMiddleware::class);

    // API v1
    $app->group(
        '/api',
        function (RouteCollectorProxy $app) {

            // ? AUTHENTICATION
            $app->post('/auth/login', \App\Action\Authentication\AuthenticationLoginAction::class);
            $app->post('/auth/register', \App\Action\Authentication\AuthenticationRegisterAction::class);
            $app->post('/auth/resetPassword', \App\Action\Authentication\AuthenticationResetPasswordAction::class);
            $app->post('/users/checkUser', \App\Action\User\UserCheckExistsAction::class);

            // ? PAYMENTS

            $app->post('/payments/webhook', \App\Action\Payment\PaymentWebhookPaymentAction::class);
            $app->post('/payments/updatePayment', \App\Action\Payment\PaymentUpdatePaymentAction::class);

            // ? Routes that requires authentication
            $app->group(
                '',
                function (RouteCollectorProxy $app) {
                    // ? AUTHENTICATION
                    $app->get('/auth/me', \App\Action\Authentication\AuthenticationMeAction::class)->setName('AUTH_ME');

                    // ? PERMISSIONS
                    $app->get('/permissions/getAll', \App\Action\Permission\PermissionGetAllAction::class)->setName('LIST_PERMISSIONS');
                    $app->get('/permissions/{id}', \App\Action\Permission\PermissionGetByIdAction::class)->setName('VIEW_PERMISSION');
                    $app->post('/permissions/create', \App\Action\Permission\PermissionCreateAction::class)->setName('CREATE_PERMISSION');
                    $app->put('/permissions/update/{id}', \App\Action\Permission\PermissionUpdateByIdAction::class)->setName('UPDATE_PERMISSION');
                    $app->delete('/permissions/delete/{id}', \App\Action\Permission\PermissionDeleteByIdAction::class)->setName('DELETE_PERMISSION');
                    
                    // ? ROLES
                    $app->get('/roles/getAll', \App\Action\Role\RoleGetAllAction::class)->setName('LIST_ROLES');
                    $app->get('/roles/{id}', \App\Action\Role\RoleGetByIdAction::class)->setName('VIEW_ROLE');
                    $app->post('/roles/create', \App\Action\Role\RoleCreateAction::class)->setName('CREATE_ROLE');
                    $app->put('/roles/update/{id}', \App\Action\Role\RoleUpdateByIdAction::class)->setName('UPDATE_ROLE');
                    // ? DOCUMENT TYPES
                    $app->get('/documentTypes/getAll', \App\Action\DocumentType\DocumentTypeGetAllAction::class)->setName('LIST_DOCUMENT_TYPES');
                    $app->get('/documentTypes/{id}', \App\Action\DocumentType\DocumentTypeGetByIdAction::class)->setName('VIEW_DOCUMENT_TYPE');
                    $app->post('/documentTypes/create', \App\Action\DocumentType\DocumentTypeCreateAction::class)->setName('CREATE_DOCUMENT_TYPE');
                    // ? DOCUMENT TYPE FEE
                    $app->get('/documentTypeFees/getAll', \App\Action\DocumentTypeFee\DocumentTypeFeeGetAllAction::class)->setName('LIST_DOCUMENT_TYPE_FEES');
                    $app->get('/documentTypeFees/{id}', \App\Action\DocumentTypeFee\DocumentTypeFeeGetByIdAction::class)->setName('VIEW_DOCUMENT_TYPE_FEE');
                    $app->post('/documentTypeFees/create', \App\Action\DocumentTypeFee\DocumentTypeFeeCreateAction::class)->setName('CREATE_DOCUMENT_TYPE_FEE');

                    // ? DOCUMENTS
                    $app->post('/documents/create', \App\Action\Document\DocumentCreateAction::class)->setName('CREATE_USER_DOCUMENT');
                    $app->get('/documents/getAll/{owner_id}', \App\Action\Document\DocumentGetAllByOwnerIdAction::class)->setName('LIST_USER_DOCUMENTS');
                    $app->get('/documents/getAll', \App\Action\Document\DocumentGetAllAction::class)->setName('LIST_DOCUMENTS');
                    $app->get('/documents/{id}', \App\Action\Document\DocumentGetByIdAction::class)->setName( 'VIEW_DOCUMENT');
                    $app->get('/documents/statistics/{id}', \App\Action\Document\DocumentGetStatisticByOwnerIdAction::class)->setName( 'GET_USER_DOCUMENT_STATISTICS');
                    $app->delete('/documents/delete/{id}', \App\Action\Document\DocumentDeleteByIdAction::class)->setName('DELETE_DOCUMENT');

                    // ? SIGNATURES
                    $app->post('/signatures/signDocument', \App\Action\Signature\SignatureSignDocumentAction::class)->setName('SIGN_USER_DOCUMENT');

                    // ? FILES
                    $app->post('/files/uploadBiometryFiles', \App\Action\File\FileUploadFilesAction::class)->setName('UPLOAD_USER_DOCUMENT_FILE');
                    $app->get('/files/download/{filename}', \App\Action\File\FileDownloadByFileNameAction::class)->setName('DOWNLOAD_FILE');
                    // $app->get('/files/download/{document_id}', \App\Action\File\FileDownloadDocumentAction::class);
                    $app->get('/files/download/{document_id}/{signer_id}', \App\Action\File\FileDownloadBiometryMediaAction::class)->setName('DOWNLOAD_USER_BIOMETRY_FILES');

                    // ? USERS
                    $app->get('/users/getAll', \App\Action\User\UserGetAllAction::class)->setName('LIST_USERS');
                    $app->get('/users/getByTerm', \App\Action\User\UserGetUserByTermAction::class)->setName('LIST_USERS');
                    $app->post('/users/create', \App\Action\User\UserCreateAction::class)->setName('CREATE_USER');
                    $app->get('/users/{id}', \App\Action\User\UserGetByIdAction::class)->setName('VIEW_USER');
                    $app->put('/users/update/{id}', \App\Action\User\UserUpdateByIdAction::class)->setName('UPDATE_USER');
                    $app->delete('/users/delete/{id}', \App\Action\User\UserDeleteByIdAction::class)->setName('DELETE_USER');
                    $app->put('/users/changePassword/{id}', \App\Action\User\UserChangePasswordAction::class)->setName('UPDATE_USER_PASSWORD');

                    // ? MAILS
                    $app->post('/mail/forwardBiometricValidation', \App\Action\Biometry\SendBiometricValidationAction::class)->setName('FORWARD_USER_BIOMETRIC_VALIDATION_EMAIL');

                    // ? SIGNATURE CREDITS
                    $app->post('/signatureCredit/create', \App\Action\SignatureCredit\SignatureCreditCreateAction::class)->setName('ASSIGN_SIGNATURE_CREDITS');
                    $app->get('/signatureCredit/user/{id}', \App\Action\SignatureCredit\SignatureCreditGetByUserIdAction::class)->setName('VIEW_SIGNATURE_CREDIT');
                    
                    // ? SIGNATURE INVENTORY
                    $app->get('/inventory/getAll', \App\Action\SignatureInventory\SignatureInventoryGetAllAction::class)->setName('LIST_SIGNATURE_INVENTORY');
                    $app->get('/inventory/{id}', \App\Action\SignatureInventory\SignatureInventoryGetByIdAction::class)->setName('VIEW_SIGNATURE_INVENTORY');
                    $app->post('/inventory/create', \App\Action\SignatureInventory\SignatureInventoryCreateAction::class)->setName('CREATE_SIGNATURE_INVENTORY');
                    $app->put('/inventory/update/{id}', \App\Action\SignatureInventory\SignatureInventoryUpdateAction::class)->setName('UPDATE_SIGNATURE_INVENTORY');

                    // TODO: COUPONS MODULE TO DEPRECATE
                    $app->get('/coupons/getAll', \App\Action\Coupon\CouponGetAllAction::class)->setName('LIST_COUPONS');
                    $app->get('/coupons/{id}', \App\Action\Coupon\CouponGetByIdAction::class)->setName('VIEW_COUPON');
                    $app->post('/coupons/create', \App\Action\Coupon\CouponCreateAction::class)->setName('CREATE_COUPON');
                    $app->put('/coupons/{id}', \App\Action\Coupon\CouponUpdateByIdAction::class)->setName('UPDATE_COUPON');
                    $app->delete('/coupons/{id}', \App\Action\Coupon\CouponDeleteByIdAction::class)->setName('DELETE_COUPON');

                    // ? SIGNATURE PACKAGES
                    $app->get('/packages/getAll', \App\Action\SignaturePackage\SignaturePackageGetAllAction::class)->setName('LIST_SIGNATURE_PACKAGES');
                    $app->get('/packages/{id}', \App\Action\SignaturePackage\SignaturePackageGetByIdAction::class)->setName('VIEW_SIGNATURE_PACKAGE');
                    $app->post('/packages/create', \App\Action\SignaturePackage\SignaturePackageCreateAction::class)->setName('CREATE_SIGNATURE_PACKAGE');
                    $app->put('/packages/{id}', \App\Action\SignaturePackage\SignaturePackageUpdateByIdAction::class)->setName('UDPATE_SIGNATURE_PACKAGE');
                    $app->delete('/packages/{id}', \App\Action\SignaturePackage\SignaturePackageDeleteByIdAction::class)->setName('DELETE_SIGNATURE_PACKAGE');
                    
                    // ? PACKAGE PURCHASES
                    $app->post('/purchase/package', \App\Action\SignaturePackagePurchase\SignaturePackagePurchaseCreatePurchase::class)->setName('PURCHASE_USER_SIGNATURE_CREDITS');
                    $app->get('/purchase/getAll/{user_id}', \App\Action\SignaturePackagePurchase\SignaturePackagePurchaseGetAllByOwnerIdAction::class)->setName('PAY_USER_SIGNATURE_CREDITS');

                    // ? PAYMENTS
                    $app->get('/payments/getAll', \App\Action\Payment\PaymentGetAllAction::class)->setName('LIST_PAYMENTS');
                    $app->get('/payments/{id}', \App\Action\Payment\PaymentGetByIdAction::class)->setName('VIEW_PAYMENT');

                    $app->get('/statistics/totals', \App\Action\Statistic\StatisticGetAllTotalsAction::class)->setName( 'GET_ALL_STATISTICS');
                }
            )->add(AuthMiddleware::class);
        }
    );

    // API v2
    $app->group('/api/v2', function (RouteCollectorProxy $app) {
            // ? API INTEGRATION CLIENTS
            $app->post('/clients/create', \App\Action\Client\ClientCreateAction::class)->setName('CREATE_CLIENT');
            $app->get('/clients/getAll', \App\Action\Client\ClientGetAllAction::class)->setName('LIST_CLIENTS');
            $app->get('/clients/{id}', \App\Action\Client\ClientGetByIdAction::class)->setName('VIEW_CLIENT');
            $app->put('/clients/deactivate/{id}', \App\Action\Client\ClientDeactivateByIdAction::class)->setName('DEACTIVATE_CLIENT');

            // ? API INTEGRATION CLIENT API KEYS
            $app->post('/clientApiKeys/create', \App\Action\ClientApiKey\ClientApiKeyCreateAction::class)->setName('CREATE_API_KEY');
            $app->get('/clientApiKeys/getAll', \App\Action\ClientApiKey\ClientApiKeyGetAllAction::class)->setName('LIST_API_KEYS');
            $app->get('/clientApiKeys/{id}', \App\Action\ClientApiKey\ClientApiKeyGetByIdAction::class)->setName('VIEW_API_KEY');
            $app->get('/clientApiKeys/clients/{client_id}', \App\Action\ClientApiKey\ClientApiKeyGetAllByClientIdAction::class)->setName('LIST_API_KEYS');
            $app->put('/clientApiKeys/deactivateAll/{client_id}', \App\Action\ClientApiKey\ClientApiKeyDeactivateAllByClientIdAction::class)->setName('DEACTIVATE_API_KEY');
            $app->put('/clientApiKeys/deactivate/{id}', \App\Action\ClientApiKey\ClientApiKeyDeactivateByIdAction::class)->setName('DEACTIVATE_API_KEY');
            $app->put('/clientApiKeys/rotate', \App\Action\ClientApiKey\ClientApiKeyRotateAction::class)->setName('ROTATE_API_KEY');
        }
    )->add(AuthMiddleware::class);
};
