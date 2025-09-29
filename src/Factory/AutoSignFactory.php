<?php

namespace App\Factory;

use GuzzleHttp\Client;
use ZipArchive;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Traits\AmazonS3Trait;

class AutoSignFactory
{
    private $ambiente;
    private $username;
    private $password;
    private $usuarioServicio;
    private $passwordServicio;
    private $doc2SignLite;
    private $doc2SignLiteSteps;
    private $au2signApiUrl;
    private $au2signAuthUrl;
    private $documentPath;

    use AmazonS3Trait;

    public function __construct()
    {
        $this->ambiente = (string) $_SERVER['PSC_WORLD_ENVIRONMENT'];
        $this->username = (string) $_SERVER['PSC_WORLD_USER'];
        $this->password = (string) $_SERVER['PSC_WORLD_PASSWORD'];
        $this->usuarioServicio = (string) $_SERVER['PSC_WORLD_SERVICE_USER'];
        $this->passwordServicio = (string) $_SERVER['PSC_WORLD_SERVICE_PASSWORD'];
        $this->au2signApiUrl = (string) $_SERVER['PSC_WORLD_AU2SIGN_API_URL'];
        $this->au2signAuthUrl = (string) $_SERVER['PSC_WORLD_AU2SIGN_AUTH_URL'];
        $this->documentPath = __DIR__ . '/../Documents';
    }

    private $positionSignatures = [
        "0" => ['PosX' => 1, 'PosY' => 680],
        "1" => ['PosX' => 130, 'PosY' => 680],
        "2" => ['PosX' => 260, 'PosY' => 680],
        "3" => ['PosX' => 390, 'PosY' => 680],
        "4" => ['PosX' => 1, 'PosY' => 610],
        "5" => ['PosX' => 130, 'PosY' => 610],
        "6" => ['PosX' => 260, 'PosY' => 610],
        "7" => ['PosX' => 390, 'PosY' => 610],
        "8" => ['PosX' => 1, 'PosY' => 540],
        "9" => ['PosX' => 130, 'PosY' => 540],
    ];

    public function getToken(): string
    {
        $client = new Client();
        $response = $client->request('POST', "$this->au2signAuthUrl/OAuth/token", [
            'auth' => [$this->username, $this->password],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'ambiente' => $this->ambiente
            ]
        ]);
        $body = json_decode($response->getBody()->getContents(), true);
        return $body['access_token'];
    }

    public function signDocument(string $token, string $identifier, array $signerList, string $base64File): mixed
    {
        try {
            $client = new Client();
            $response = $client->request('POST', "$this->au2signApiUrl/LoadMultipleOES", [
                'headers' => [
                    'Authorization' => "Bearer $token",
                    'ambiente' => $this->ambiente
                ],
                'json' => [
                    'Identificador' => $identifier,
                    'Firmantes' => $signerList,
                    'FirmaEmpresa' => "",
                    'DocumentoBase64' => $base64File,
                    'MostrarFirmas' => 1,
                    'AgregarFirmaEmpresa' => false,
                    'FirmaDeEmpresaUnica' => false,
                    'FirmaAutografa' => false
                ]
            ]);
            $body = json_decode($response->getBody()->getContents(), true);
            return $body;
        } catch (\PDOException $e) {
            throw new \DomainException('Error signing document: ' . sprintf($e->getMessage()));
        }
    }

    public function getSignedDocument(string $token, string $identifier): string
    {
        try {
            $client = new Client();
            $response = $client->request('GET', "$this->au2signApiUrl/GETDocFirmado", [
                'headers' => [
                    'Authorization' => "Bearer $token",
                ],
                'query' => [
                    'Identificador' => $identifier,
                    'ambiente' => $this->ambiente,
                ]
            ]);
            $body = json_decode($response->getBody()->getContents(), true);
            return $body['GETDocFirmadoResult'];
        } catch (\PDOException $e) {
            throw new \DomainException('Error getting signed document: ' . sprintf($e->getMessage()));
        }
    }

    public function getDocumentNOM(string $token, string $identifier): string
    {
        try {
            $client = new Client();
            $response = $client->request('GET', "$this->au2signApiUrl/GETNOM", [
                'headers' => [
                    'Authorization' => "Bearer $token",
                ],
                'query' => [
                    'Identificador' => $identifier,
                    'ambiente' => $this->ambiente,
                    'UsuarioServicio' => $this->usuarioServicio,
                    'PasswordServicio' => $this->passwordServicio,
                ]
            ]);
            $body = json_decode($response->getBody()->getContents(), true);
            return $body['GETNOMResult'];
        } catch (\PDOException $e) {
            throw new \DomainException('Error getting signed document: ' . sprintf($e->getMessage()));
        }
    }

    public function generateSignedDocument(string $identifier, array $signerList, string $base64File): array
    {
        try {
            $token = $this->getToken();
    
            $response = $this->signDocument($token, $identifier, $signerList, $base64File);
    
            if ($response['LoadMultipleOESResult'] == '1') {
                # Generate and decode signed document pdf
                $signedDocument = $this->getSignedDocument($token, $identifier);
                $filePath = $this->getSignedDocumentPDF($identifier, $signedDocument);
                $url = $this->putObject($filePath, "$identifier.pdf");
                # Generate and decode signed document NOM asn1
                $documentNOM = $this->getDocumentNOM($token, $identifier);
                $nomPath = $this->getSignedDocumentNOM($identifier, $documentNOM);
                # Generate zip with signed document and NOM, then compress in zip file
                $zipPath = $this->getSignedDocumentZip($identifier, $filePath, $nomPath);
                $zipUrl = $this->putObject($zipPath, "$identifier.zip");
                $fileName = "$identifier.pdf";
                $zipName = "$identifier.zip";

                header("Content-type: application/octet-stream");
                header("Content-Type: application/force-download");
                header("Content-Disposition: attachment; filename=\"$zipPath\"\n");

                $response = [
                    'status' => 'success',
                    'documentName' => $fileName,
                    'documentUrl' => $url,
                    'zipName' => $zipName,
                    'zipUrl' => $zipUrl,
                ];

                return $response;
            }
            return ['fail'];
        } catch (\PDOException $e) {
            throw new \DomainException('Error generating signed document signed document: ' . sprintf($e->getMessage()));
        }
    }

    public function getSignedDocumentZip(string $identifier, string $signedDocumentPath, string $nomPath): string
    {
        $zipName = "$identifier.zip";
        $zipPath = "$this->documentPath/$identifier/$zipName";
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFile($signedDocumentPath, "$identifier.pdf");
        $zip->addFile($nomPath, $identifier . "_NOM.asn1");
        $zip->close();
        return $zipPath;
    }

    public function getSignedDocumentPDF(string $identifier, string $signedDocument): string
    {
        $decodedSignedDocument = base64_decode($signedDocument);
        $fileName = $identifier . "_sign.pdf";
        $filePath = "$this->documentPath/$identifier/$fileName";
        $pdf = fopen($filePath, 'w');
        fwrite($pdf, $decodedSignedDocument);
        fclose($pdf);
        return $filePath;
    }
    
    public function getSignedDocumentNOM(string $identifier, string $documentNOM): string
    {
        $decodedNOM = base64_decode($documentNOM);
        $nomName = $identifier . "_NOM.asn1";
        $nomPath = "$this->documentPath/$identifier/$nomName";
        $asn1 = fopen($nomPath, 'w');
        fwrite($asn1, $decodedNOM);
        fclose($asn1);
        return $nomPath;
    }
}
