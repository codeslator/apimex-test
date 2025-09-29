<h1 align="center">
  <img src="https://firmavirtual.mx/wp-content/uploads/2022/10/logo_FIRMA_VIRTUAL_1210.png" width="200">
</h1>

# FirmaVirtual México API - Apimex

API Rest para los servicios de **FirmaVirtual** en México. Está diseñada para facilitar la gestión y automatización de procesos relacionados con la firma electrónica en México. Visita el sitio web **[aquí](https://firmavirtual.mx/)**.

## Características principales:
- **Autenticación segura**: Basada en tokens para garantizar la integridad y confidencialidad de las operaciones.
- **Manejo de documentos**: Subida, consulta, descarga y administración de documentos para firma.
- **Procesos de firma**: Inicio, monitoreo y finalización de transacciones de firma electrónica.
- **Cumplimiento normativo**: Compatible con los estándares legales y regulatorios aplicables en México.

## Requerimientos

#### En local:
- **PHP 8.3+**
- **MySQL 8.0+** or **MariaDB**
- **Composer**

#### Con Docker:
- **Docker**

## Instalación y Ejecución

1. Clonar el repositorio.
```bash
git clone https://github.com/firma-virtual/apimex.git
```
2. Crear archivo `.env` en la raíz del proyecto. Se deben definir los valores de las siguientes variables de entorno:
```sh
APP_ENV=

# Configuraciones de la Base de Datos.
DB_HOST=
DB_NAME=
DB_USER=
DB_PASSWORD=
DB_PORT=
SECRET_KEY=

# Token de Gigstack
TOKEN_GIGSTACK=

# Credenciales para PSC World
PSC_WORLD_ENVIRONMENT='0'
PSC_WORLD_USER=
PSC_WORLD_PASSWORD=
PSC_WORLD_SERVICE_USER=
PSC_WORLD_SERVICE_PASSWORD=
PSC_WORLD_AU2SIGN_API_URL=
PSC_WORLD_AU2SIGN_AUTH_URL=

# Servicios de Front de FirmaVirtual MX
MAINURL=
DOMAINURL=
DEMOURL=
SUPPORT_EMAIL='no-reply@firmavirtual.mx'

# Servicios de Correo Electronico (Solo se debe tener uno activo [MAIL_SERVICE], por defecto se usa Brevo)

# MAIL_SERVICE='SENDGRID'
# SENDGRID_STATUS='ENABLE'
# SENDGRID_API_KEY=

# MAIL_SERVICE='MAILGUN'
# MAILGUN_DOMAIN_AUTH='firmavirtual.com'
# MAILGUN_API_KEY=
# MAILGUN_DOMAIN_API=
# MAILGUN_DOMAIN_SANDBOX=

MAIL_SERVICE='BREVO'
BREVO_STATUS='ENABLE'
BREVO_API_KEY=

# AWS Bucket S3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET_NAME=
AWS_VERSION=

# Consolidador de archivos PDF (Hace merge del documento con los certificados)
DOC_CONSOLIDADOR_UUID='591d49dd-5987-4038-9f05-f67d1f32c482'
URL_CONSOLIDADOR='https://apis.firmavirtual.legal/doc-consolidator-api/merge?fileInfos=[]'
```

3. Contruir y ejecutar contenedor de la API con el siguiente comando:
```bash
docker compose up --build
```

4. Si en contenedor se construyó y se ejecutó sin problemas, podras acceder a la API con la siguiente URL:
```
http://localhost:8080/
```

5. Para construir la imágen del contenedor, se usa el siguiente comando:
```bash
docker build -t apimex .
```

6. Se ejecuta la imágen con el siguiente comando:
```bash
docker run -d -p 8080:80 apimex
```

## Proceso de Firma

Se describe el flujo comopleto del proceso para el registro de usuario, creación del documento, firma electrónica y entrega de documento firmado.

1. Crear usuario.
2. Iniciar sesión.
3. Crear documento para firma.
4. Recibir correo de pago del documento y proceder al pago (si lo requiere).
5. Recibir correo de validación biométrica.
6. Realizar proceso de validación biomética.
7. Firma del documento.
8. Entrega del documento.
9. Descarga de documento firmado.
