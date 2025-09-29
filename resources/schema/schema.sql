

CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(100) UNIQUE,
  `rfc` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `mother_last_name` varchar(100),
  `full_name` varchar(100),
  `email` varchar(100) UNIQUE NOT NULL,
  `phone` varchar(20),
  `username` varchar(100) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int,
  `signature_credit_id` int,
  `client_id` int NULL,
  `pass_reset` boolean,
  `is_active` boolean DEFAULT true,
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `roles` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255),
  `is_active` boolean NOT NULL
);

CREATE TABLE `permissions` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `code` varchar(50) UNIQUE NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255)
);

CREATE TABLE `roles_permission` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `role_id` int,
  `permission_id` int
);

CREATE TABLE `signature_credits` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `consumed_quantity` int DEFAULT 0,
  `remaining_quantity` int,
  `from_date` timestamp NULL,
  `to_date` timestamp NULL,
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `signature_packages` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(100) UNIQUE,
  `name` varchar(100) NOT NULL,
  `price_per_signature` decimal(10,2),
  `iva` decimal(10,2),
  `min_quantity` int,
  `max_quantity` int,
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `signature_packages_purchases` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `credit_id` int,
  `package_id` int,
  `quantity` int NOT NULL,
  `total_price` decimal(10,2),
  `total_iva` decimal(10,2),
  `amount` decimal(10,2),
  `is_paid` boolean DEFAULT false,
  `completed_at` timestamp NULL,
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `signature_inventory` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `quantity` int,
  `source` ENUM ('PSC_WORLD', 'OTHER'),
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `coupons` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `code` varchar(50) UNIQUE NOT NULL,
  `expiration_date` timestamp NULL,
  `discount_amount` decimal(10,2),
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `purchase_coupons` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `coupon_id` int,
  `purchase_id` int
);

CREATE TABLE `signers` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(100) UNIQUE,
  `document_id` int,
  `signature_code` varchar(100) NOT NULL,
  `rfc` varchar(100) NOT NULL,
  `curp` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `mother_last_name` varchar(100),
  `birth_date` date DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `role` ENUM ('SIGNER', 'PAYER', 'SIGNER_PAYER'),
  `signer_type` ENUM ('NATURAL', 'LEGAL'),
  `portion` int,
  `payment` decimal(10,2),
  `iva_pay` decimal(10,2),
  `total_pay` decimal(10,2),
  `is_paid` boolean DEFAULT false,
  `is_prepaid` boolean DEFAULT false,
  `is_signed` boolean DEFAULT false,
  `require_video` boolean NULL,
  `signature_page` int,
  `posX` int,
  `posY` int,
  `signature_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `signer_payment` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `signer_id` int,
  `purchase_id` int,
  `payer_id` int,
  `payment_link` varchar(100),
  `invoice_id` varchar(100),
  `amount` decimal(10,2),
  `status` varchar(20),
  `method_type` varchar(20),
  `info_link_creator` text,
  `response_payment_link` text,
  `completed_at` timestamp NULL,
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `documents` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(100) UNIQUE,
  `document_code` varchar(50) UNIQUE,
  `document_type_fee_id` int,
  `status` ENUM ('CREATED', 'REVIEW', 'APPROVED', 'REJECTED', 'SIGNED_PENDING', 'SIGNED', 'FINISHED', 'DELETED', 'OTHER'),
  `payment_status` ENUM ('PAIDOUT', 'PENDING'),
  `owner_id` int,
  `owner_type` ENUM ('NATURAL', 'LEGAL'),
  `signer_count` int,
  `is_deleted` boolean DEFAULT false,
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT NOW(),
  `deleted_at` timestamp NULL
);

CREATE TABLE `files` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `code` varchar(100),
  `name` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `remote_url` varchar(255) NULL,
  `document_id` int,
  `signer_id` int,
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `document_type_fee` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `modality` ENUM ('SIGNATURE'),
  `sign_count` int,
  `amount` decimal(10,2),
  `amount_iva` decimal(10,2),
  `iva` decimal(10,2),
  `total` decimal(10,2),
  `is_active` boolean,
  `document_type_id` int
);

CREATE TABLE `document_type` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(120),
  `document_type` ENUM ('REAL_STATE', 'LABOR', 'PERSONAL', 'POWER', 'COMPANY', 'SOCIETY', 'VEHICLE', 'GENERIC')
);

CREATE TABLE `biometric_history` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(100) UNIQUE,
  `document_id` int,
  `signer_id` int,
  `verification_code` varchar(100),
  `has_photo_identity_uploaded` boolean DEFAULT false,
  `has_biometric_identity_uploaded` boolean DEFAULT false,
  `has_video_identity_uploaded` boolean DEFAULT false,
  `session_id` varchar(50),
  `scan_id` longtext,
  `validation_url` TEXT NULL,
  `is_url_active` boolean NULL,
  `is_done` boolean DEFAULT false,
  `current_step` ENUM ('Email', 'Photo', 'Biometry', 'Video', 'Finish'),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT NOW()
);

CREATE TABLE `clients` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `uuid` varchar(100) UNIQUE,
  `name` varchar(100) NOT NULL,
  `rfc` varchar(100) NOT NULL UNIQUE,
  `description` varchar(255),
  `contact_first_name` varchar(100),
  `contact_last_name` varchar(100),
  `contact_mother_last_name` varchar(100) NULL,
  `contact_full_name` varchar(255),
  `contact_email` varchar(100) UNIQUE NOT NULL,
  `contact_phone` varchar(100) NULL,
  `webhook_url` text,
  `rate_limit` int,
  `is_active` boolean DEFAULT true,
  `created_at` timestamp DEFAULT NOW(),
  `updated_at` timestamp NULL
);

CREATE TABLE `client_api_keys` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `client_id` int,
  `name` varchar(100) NOT NULL,
  `description` varchar(255),
  `api_key` varchar(255) NOT NULL UNIQUE,
  `environment` ENUM ('PRODUCTION', 'STAGING', 'DEVELOPMENT'),
  `is_visible` boolean DEFAULT true,
  `status` ENUM ('ACTIVE', 'EXPIRED', 'REVOKED') DEFAULT 'ACTIVE',
  `expires_at` timestamp NULL,
  `last_used_at` timestamp NULL,
  `rotated_at` timestamp NULL,
  `created_at` timestamp DEFAULT NOW()
);


CREATE INDEX `users_index_0` ON `users` (`uuid`);

CREATE UNIQUE INDEX `users_index_1` ON `users` (`id`, `uuid`);

CREATE INDEX `signature_packages_index_2` ON `signature_packages` (`uuid`);

CREATE UNIQUE INDEX `signature_packages_index_3` ON `signature_packages` (`id`, `uuid`);

CREATE INDEX `signers_index_4` ON `signers` (`uuid`);

CREATE UNIQUE INDEX `signers_index_5` ON `signers` (`id`, `uuid`);

CREATE INDEX `documents_index_6` ON `documents` (`uuid`);

CREATE UNIQUE INDEX `documents_index_7` ON `documents` (`id`, `uuid`);

CREATE INDEX `biometric_history_index_8` ON `biometric_history` (`uuid`);

CREATE UNIQUE INDEX `biometric_history_index_9` ON `biometric_history` (`id`, `uuid`);

CREATE INDEX `client_api_keys_index_10` ON `client_api_keys` (`api_key`);

CREATE UNIQUE INDEX `client_api_keys_index_11` ON `client_api_keys` (`api_key`, `environment`);

ALTER TABLE `users` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

ALTER TABLE `users` ADD FOREIGN KEY (`signature_credit_id`) REFERENCES `signature_credits` (`id`);

ALTER TABLE `users` ADD FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

ALTER TABLE `roles_permission` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

ALTER TABLE `roles_permission` ADD FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`);

ALTER TABLE `signature_packages_purchases` ADD FOREIGN KEY (`credit_id`) REFERENCES `signature_credits` (`id`);

ALTER TABLE `signature_packages_purchases` ADD FOREIGN KEY (`package_id`) REFERENCES `signature_packages` (`id`);

ALTER TABLE `purchase_coupons` ADD FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`);

ALTER TABLE `purchase_coupons` ADD FOREIGN KEY (`purchase_id`) REFERENCES `signature_packages_purchases` (`id`);

ALTER TABLE `signers` ADD FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`);

ALTER TABLE `signer_payment` ADD FOREIGN KEY (`signer_id`) REFERENCES `signers` (`id`);

ALTER TABLE `signer_payment` ADD FOREIGN KEY (`purchase_id`) REFERENCES `signature_packages_purchases` (`id`);

ALTER TABLE `signer_payment` ADD FOREIGN KEY (`payer_id`) REFERENCES `users` (`id`);

ALTER TABLE `documents` ADD FOREIGN KEY (`document_type_fee_id`) REFERENCES `document_type_fee` (`id`);

ALTER TABLE `documents` ADD FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

ALTER TABLE `files` ADD FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`);

ALTER TABLE `files` ADD FOREIGN KEY (`signer_id`) REFERENCES `signers` (`id`);

ALTER TABLE `document_type_fee` ADD FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`);

ALTER TABLE `biometric_history` ADD FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`);

ALTER TABLE `biometric_history` ADD FOREIGN KEY (`signer_id`) REFERENCES `signers` (`id`);

ALTER TABLE `client_api_keys` ADD FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);


-- ? INSERT CONFIGURATION

INSERT INTO `permissions` (`code`, `name`, `description`) VALUES 
('VIEW_HOME', 'Ver Inicio', 'Permite ver la pantalla de inicio de la plataforma, independientemente su rol.'),
('MANAGE_ROLES', 'Gestionar Roles', 'Permite gestionar los roles en la plataforma.'),
('LIST_ROLES', 'Listar Roles', 'Permite ver el listado de roles existente en la plataforma.'),
('CREATE_ROLE', 'Crear Rol', 'Permite crear un nuevo rol de usuario en la plataforma.'),
('VIEW_ROLE', 'Ver Detalles del Rol', 'Permite ver los detalles de un rol de usuario en la plataforma.'),
('UPDATE_ROLE', 'Actualizar Rol', 'Permite actualizar un  rol de usuario en la plataforma.'),
('DELETE_ROLE', 'Eliminar Rol', 'Permite eliminar los datos de un rol de usuario en la plataforma.'),
('MANAGE_PERMISSIONS', 'Gestionar Permisos', 'Permite gestionar los permisos en la plataforma.'),
('LIST_PERMISSIONS', 'Listar Permisos', 'Permite ver el listado de permisos existente en la plataforma.'),
('CREATE_PERMISSION', 'Crear Permiso', 'Permite crear un nuevo permiso en la plataforma.'),
('VIEW_PERMISSION', 'Ver Detalles del Permiso', 'Permite ver los detalles de un permiso en la plataforma.'),
('UPDATE_PERMISSION', 'Actualizar Permiso', 'Permite actualizar un permiso en la plataforma.'),
('DELETE_PERMISSION', 'Eliminar Permiso', 'Permite eliminar un permiso en la plataforma.'),
('MANAGE_SIGNATURE_INVENTORY', 'Gestionar Inventario de Firmas', 'Permite gestionar el inventario de firmas en la plataforma.'),
('LIST_SIGNATURE_INVENTORY', 'Listar Inventario de Firmas', 'Permite ver el listado de inventario de firmas existente en la plataforma.'),
('CREATE_SIGNATURE_INVENTORY', 'Crear Inventario de Firmas', 'Permite crear un nuevo inventario de firmas en la plataforma.'),
('VIEW_SIGNATURE_INVENTORY', 'Ver Detalles del Inventario de Firmas', 'Permite ver los detalles de un inventario de firmas en la plataforma.'),
('UPDATE_SIGNATURE_INVENTORY', 'Actualizar Inventario de Firmas', 'Permite actualizar un inventario de firmas en la plataforma.'),
('DELETE_SIGNATURE_INVENTORY', 'Eliminar Inventario de Firmas', 'Permite eliminar un inventario de firmas en la plataforma.'),
('INCREMENT_SIGNATURE_INVENTORY', 'Incrementar Inventario de Firmas', 'Permite incrementar el stock del inventario de firmas en la plataforma.'),
('DECREMENT_SIGNATURE_INVENTORY', 'Decrementar Inventario de Firmas', 'Permite decrementar el stock del inventario de firmas en la plataforma.'),
('MANAGE_SIGNATURE_PACKAGES', 'Gestionar Paquetes de Firmas', 'Permite gestionar el  paquetes de firmas en la plataforma.'),
('LIST_SIGNATURE_PACKAGES', 'Listar Paquetes de Firmas', 'Permite ver el listado de paquetes de firmas existentes en la plataforma.'),
('CREATE_SIGNATURE_PACKAGE', 'Crear Paquete de Firma', 'Permite crear un nuevo paquete de firmas en la plataforma.'),
('VIEW_SIGNATURE_PACKAGE', 'Ver Detalles del Paquete de Firma', 'Permite ver los detalles de un paquete de firmas en la plataforma.'),
('MANAGE_COUPONS', 'Gestionar Cupones', 'Permite gestionar los cupones en la plataforma.'),
('LIST_COUPONS', 'Listar Cupones', 'Permite ver el listado de cupones existentes en la plataforma.'),
('CREATE_COUPON', 'Crear Cupón', 'Permite crear un nuevo cupón en la plataforma.'),
('VIEW_COUPON', 'Ver Detalles del Cupón', 'Permite ver los detalles de un cupón en la plataforma.'),
('MANAGE_DOCUMENT_TYPES', 'Gestionar Tipos de Documentos', 'Permite gestionar los tipos de documentos en la plataforma.'),
('LIST_DOCUMENT_TYPES', 'Listar Tipos de Documentos', 'Permite ver el listado de tipos de documentos existentes en la plataforma.'),
('CREATE_DOCUMENT_TYPE', 'Crear Tipo de Documento', 'Permite crear un nuevo tipo de documento en la plataforma.'),
('VIEW_DOCUMENT_TYPE', 'Ver Detalles del Tipo de Documento', 'Permite ver los detalles de un tipo de documentoen la plataforma.'),
('MANAGE_DOCUMENT_TYPE_FEES', 'Gestionar Tarifas de Tipos de Documentos', 'Permite gestionar las tarifas de tipos de documentos en la plataforma.'),
('LIST_DOCUMENT_TYPE_FEES', 'Listar Tarifas de Tipos de Documentos', 'Permite ver el listado de tarifas de tipos de documentos existentes en la plataforma.'),
('CREATE_DOCUMENT_TYPE_FEE', 'Crear Tarifa de Tipo de Documento', 'Permite crear una nueva tarifa de tipo de documento en la plataforma.'),
('VIEW_DOCUMENT_TYPE_FEE', 'Ver Detalles del Tarifa de Tipo de Documento', 'Permite ver los detalles de una tarifa de tipo de documentoen la plataforma.'),
('MANAGE_USERS', 'Gestionar Usuaros', 'Permite gestionar los usuarios en la plataforma.'),
('LIST_USERS', 'Listar Usuarios', 'Permite ver el listado de usuarios existentes en la plataforma.'),
('CREATE_USER', 'Crear Usuario', 'Permite crear un nuevo usuario en la plataforma.'),
('VIEW_USER', 'Ver Detalles del Usuario', 'Permite ver los detalles de un usuario en la plataforma.'),
('UPDATE_USER', 'Actualizar Usuario', 'Permite actualizar un usuario en la plataforma.'),
('DELETE_USER', 'Eliminar Usuario', 'Permite eliminar los datos de un usuario en la plataforma.'),
('RESET_USER_PASSWORD', 'Resetear Contraseña del Usuario', 'Permite resetear la contraseña de un usuario en la plataforma.'),
('UPDATE_USER_PASSWORD', 'Actualizar Contraseña del Usuario', 'Permite actualizar la contraseña de un usuario en la plataforma.'),
('VIEW_USER_PROFILE', 'Ver Perfil del Usuario', 'Permite ver el perfil de usuario en la plataforma.'),
('UPDATE_USER_PROFILE', 'Actualizar Perfil del Usuario', 'Permite actualizar los datos del perfil de un usuario en la plataforma.'),
('MANAGE_DOCUMENTS', 'Gestionar Documentos', 'Permite gestionar los documentos en la plataforma.'),
('LIST_DOCUMENTS', 'Listar Documentos', 'Permite ver el listado de documentos existentes en la plataforma.'),
('VIEW_DOCUMENT', 'Ver Detalles del Documento', 'Permite ver los detalles de un documento en la plataforma.'),
('DOWNLOAD_DOCUMENT_FILE', 'Descargar Documento', 'Permite descargar el archivo de un documento de la plataforma.'),
('MANAGE_USER_DOCUMENTS', 'Gestionar Documentos del Usuario', 'Permite gestionar los documentos del usuario en la plataforma.'),
('LIST_USER_DOCUMENTS', 'Listar Documentos del Usuario', 'Permite gestionar los documentos por el usuario en la plataforma.'),
('CREATE_USER_DOCUMENT', 'Crear Documento del Usuario', 'Permite crear un nuevo documento por el usuario en la plataforma.'),
('VIEW_USER_DOCUMENT', 'Ver Detalles del Documento del Usuario', 'Permite ver los detalles de un documento existente por el usuario en la plataforma.'),
('UPDATE_USER_DOCUMENT', 'Actualizar Documento del Usuario', 'Permite actualizar un documento existente por el usuario en la plataforma.'),
('DELETE_USER_DOCUMENT', 'Eliminar Documento del Usuario', 'Permite eliminar un documento existente por el usuario en la plataforma.'),
('SIGN_USER_DOCUMENT', 'Firmar Documento del Usuario', 'Permite firmar un documento por el usuario en la plataforma.'),
('PAY_USER_DOCUMENT_SIGNATURES', 'Pagar Firmas del Documento del Usuario', 'Permite pagar las firmas de un documento por el usuario en la plataforma.'),
('MANAGE_SIGNATURE_CREDITS', 'Gestionar Créditos de Firmas', 'Permite gestionar los créditos de firmas en la plataforma.'),
('LIST_SIGNATURE_CREDITS', 'Listar Créditos de Firmas', 'Permite listar los créditos de firmas en la plataforma.'),
('LIST_SIGNATURE_CREDITS_PURCHASES', 'Listar Compras de Créditos de Firmas', 'Permite listar las compras de los créditos de firmas en la plataforma.'),
('MANAGE_USER_SIGNATURE_CREDITS', 'Comprar Créditos de Firmas del Usuario', 'Permite comprar créditos de firmas por el usuario en la plataforma.'),
('PURCHASE_USER_SIGNATURE_CREDITS', 'Comprar Créditos de Firmas del Usuario', 'Permite comprar créditos de firmas por el usuario en la plataforma.'),
('PAY_USER_SIGNATURE_CREDITS', 'Pagar Creditos de Firmas del Usuario', 'Permite pagar créditos de firmas por el usuario en la plataforma.'),
('MANAGE_FILES', 'Gestionar Archivos', 'Permite gestionar los archivos en la plataforma.'),
('LIST_FILES', 'Listar Archivos', 'Permite listar los archivos en la plataforma.'),
('CREATE_FILE', 'Crear Archivo', 'Permite crear un nuevo archivo en la plataforma.'),
('VIEW_FILE', 'Ver Detalles del Archivo', 'Permite ver los detalles de un archivo en la plataforma.'),
('DOWNLOAD_FILE', 'Descargar Archivo', 'Permite descargar un archivo en la plataforma.'),
('DOWNLOAD_USER_DOCUMENT_FILE', 'Descargar Archivo del Documento del Usuario', 'Permite descargar un archivo de un documento por el usuario en la plataforma.'),
('DOWNLOAD_USER_BIOMETRY_FILES', 'Descargar Archivo de Validación Biométrica del Usuario', 'Permite descargar los archivos de validación biométrica del usuario en la plataforma.'),
('UPLOAD_USER_DOCUMENT_FILE', 'Cargar Archivo del Documento del Usuario', 'Permite cargar un archivo de un documento por el usuario en la plataforma.'),
('MANAGE_PAYMENTS', 'Gestionar Pagos', 'Permite gestionar los pagos en la plataforma.'),
('LIST_PAYMENTS', 'Listar Pagos', 'Permite listar los pagos en la plataforma.'),
('MANAGE_BIOMETRIC_VALIDATIONS', 'Gestionar Validaciones Biométricas', 'Permite gestionar las validaciones biométricas en la plataforma.'),
('LIST_BIOMETRIC_VALIDATIONS', 'Listar Validaciones Biométricas', 'Permite listar las validaciones biométricas en la plataforma.'),
('FORWARD_USER_BIOMETRIC_VALIDATION_EMAIL', 'Reenviar Mail de Validación Biométrica', 'Permite reenviar el mail de validación biométrica por el usuario en la plataforma.'),
('MANAGE_SIGNATURES', 'Gestionar Firmas', 'Permite gestionar las firmas en la plataforma.'),
('LIST_SIGNATURES', 'Listar Firmas', 'Permite listar las firmas en la plataforma.'),
('VIEW_API_DOCS', 'Ver Documentación de la API', 'Permite ver el enlace que redirige a la documentación de la API.'),
('MANAGE_CLIENTS', 'Gestionar Clientes', 'Permite gestionar los clientes dentro de la plataforma.'),
('LIST_CLIENTS', 'Listar Clientes', 'Permite ver el listado de clientes existente en la plataforma.'),
('CREATE_CLIENT', 'Crear Cliente', 'Permite crear un nuevo cliente en la plataforma.'),
('VIEW_CLIENT', 'Ver Detalles del Cliente', 'Permite ver los detalles de un clientes en la plataforma.'),
('UPDATE_CLIENT', 'Actualizar Cliente', 'Permite actualizar un clientes en la plataforma.'),
('DELETE_CLIENT', 'Eliminar Cliente', 'Permite eliminar los datos de un clientes en la plataforma.'),
('MANAGE_API_KEYS', 'Gestionar ApiKeys', 'Permite gestionar las apikeys dentro de la plataforma.'),
('LIST_API_KEYS', 'Listar ApiKeys', 'Permite ver el listado de apikeys existente en la plataforma.'),
('CREATE_API_KEY', 'Crear ApiKey', 'Permite crear un nuevo apikey para un cliente en la plataforma.'),
('VIEW_API_KEY', 'Ver Detalles del ApiKey', 'Permite ver los detalles de un apikey en la plataforma.'),
('UPDATE_API_KEY', 'Actualizar ApiKey', 'Permite actualizar un apikey en la plataforma.'),
('DELETE_API_KEY', 'Eliminar ApiKey', 'Permite eliminar los datos de un apikey en la plataforma.'),
('DEACTIVATE_API_KEY', 'Desactivar ApiKey', 'Permite desactivar uno o mas de un apikey en la plataforma.'),
('ROTATE_API_KEY', 'Rotar ApiKey', 'Permite rotar un apikey (generar uno nuevo desactivando el anterior) en la plataforma.'),
('DELETE_DOCUMENT', 'Eliminar Documento', 'Permite eliminar un documento existente en la plataforma.'),
('VIEW_USER_DOCUMENT_STATISTICS', 'Ver Estadísticas de los Documentos', 'Permite ver las estadísticas de los documentos del usuario.'),
('GET_USER_DOCUMENT_STATISTICS', 'Obtener Estadisticas de Documentos del Usuario', 'Permite obtener las estadisticas de los documentos del usuario.'),
('ASSIGN_SIGNATURE_CREDITS', 'Asignar Créditos de Firma a Usuario', 'Permite asignar créditos de firmas a un usuario especifico en la plataforma.'),
('VIEW_PAYMENT', 'Ver Detalles de Pago', 'Permite ver los detalles de un pago en la plataforma.'),
('MANAGE_CLIENT_CONTACTS', 'Gestionar Contactos de Cliente', 'Permite gestionar los contactos asociados a clientes dentro de la plataforma.'),
('LIST_CLIENT_CONTACTS', 'Listar Contactos de Cliente', 'Permite ver el listado de contactos asociados a clientes existente en la plataforma.'),
('CREATE_CLIENT_CONTACT', 'Crear Contacto de Cliente', 'Permite crear un nuevo contacto asociado a un cliente en la plataforma.'),
('VIEW_CLIENT_CONTACT', 'Ver Detalles del Contacto de Cliente', 'Permite ver los detalles de un contacto asociado a un cliente en la plataforma.'),
('UPDATE_CLIENT_CONTACT', 'Actualizar Contacto de Cliente', 'Permite actualizar los datos de un contacto asociado a un cliente en la plataforma.'),
('DELETE_CLIENT_CONTACT', 'Eliminar Contacto de Cliente', 'Permite eliminar los datos de un contacto asociado a un cliente en la plataforma.'),
('UPDATE_COUPON', 'Actualizar Cupón', 'Permite actualizar los datos de un cupón en la plataforma.'),
('DELETE_COUPON', 'Eliminar Cupón', 'Permite eliminar los datos de un cupón en la plataforma.'),
('UDPATE_SIGNATURE_PACKAGE', 'Actualizar Paquete de Firmas', 'Permite actualizar los datos de un paquete de firmas en la plataforma.'),
('DELETE_SIGNATURE_PACKAGE', 'Eliminar Paquete de Firmas', 'Permite eliminar los datos de un paquete de firmas en la plataforma.'),
('GET_ALL_STATISTICS', 'Obtener Todas las Estadisticas', 'Permite obtener las estadisticas relevantes de la plataforma.'),
('VIEW_SIGNATURE_CREDIT', 'Ver Detalles de los Créditos de Firma del Usuario', 'Permite ver los detalles de créditos de firma de un usuario en la plataforma.');

INSERT INTO `roles` (`name`, `description`, `is_active`, `code`) VALUES
('Super Administrador', 'Este usuario tiene todos los permisos de adminisitración de la plataforma.', true, 'ADMIN'),
('Usuario Regular', 'Este usuario tiene los permisos de uso de la plataforma para el cliente, ya sea regular o empresa.', true, 'USER'),
('Usuario para Integraciones', 'Este usuario tiene los mismos permisos de uso que el Usuario Regular con algunas funcionalidades relacionadas a las APIs.', true, 'API_INTEGRATION');

INSERT INTO `roles_permission` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 31),
(1, 32),
(1, 33),
(1, 34),
(1, 35),
(1, 36),
(1, 37),
(1, 38),
(1, 39),
(1, 40),
(1, 41),
(1, 42),
(1, 43),
(1, 44),
(1, 45),
(1, 46),
(1, 47),
(1, 48),
(1, 49),
(1, 50),
(1, 51),
(1, 60),
(1, 61),
(1, 62),
(1, 66),
(1, 67),
(1, 68),
(1, 69),
(1, 70),
(1, 74),
(1, 75),
(1, 76),
(1, 77),
(1, 79),
(1, 80),
(2, 1),
(2, 44),
(2, 45),
(2, 46),
(2, 47),
(2, 52),
(2, 53),
(2, 54),
(2, 55),
(2, 56),
(2, 57),
(2, 58),
(2, 59),
(2, 63),
(2, 64),
(2, 65),
(2, 71),
(2, 72),
(2, 73),
(2, 78);


INSERT INTO `signature_inventory`(`quantity`, `source`) VALUES (10000,'PSC_WORLD');

INSERT INTO `signature_packages` (`uuid`, `name`, `price_per_signature`, `iva`, `min_quantity`, `max_quantity`) VALUES
('ae8b207e-0bbd-493c-a2d4-2710e8c3e419', 'P1', 40.00, 0.16, 10, 100),
('cdd2e2c9-0f4b-4c05-897f-f7b0d40a2ed4', 'P2', 37.00, 0.16, 101, 250),
('a7904a90-589e-4ce3-8060-5a343dbfb6ec', 'P3', 34.00, 0.16, 251, 500),
('5d6b16d8-710f-4190-aa4a-60f051b04fdf', 'P4', 31.00, 0.16, 501, 1000),
('b51a1431-a584-4f0b-9746-00f583a06af5', 'P5', 28.00, 0.16, 1001, 2500),
('df2f16cb-a720-43bd-98a5-152cd592d8af', 'P6', 27.00, 0.16, 2501, 5000);

INSERT INTO `document_type` (`name`, `document_type`) VALUES ('Genérico', 'GENERIC');

INSERT INTO `document_type_fee` (`modality`, `sign_count`, `amount`, `amount_iva`, `iva`, `total`, `is_active`, `document_type_id`) VALUES ('SIGNATURE', 10, 40.00, 6.40, 0.16, 46.40, 1, 1);

INSERT INTO `users` (`uuid`, `rfc`, `first_name`, `last_name`, `mother_last_name`, `email`, `phone`, `username`, `password`, `role_id`, `signature_credit_id`, `pass_reset`) VALUES
('4671f24b-ae56-4455-88eb-4f6fea536cdf', 'FHFB000000001', 'Firma', 'Virtual', 'MX', 'admin@firmavirtual.mx', '+525516199788', 'firmavirtual.mx', '60a2a82291da5f37ab9d828843dae95db071372d4bdb234c9594aacea8e7a2efeff22f8a198ae639bc07bbd0f6cc9181d4da20ff56795077e8661183fc7bf76f', 1, NULL, 0);