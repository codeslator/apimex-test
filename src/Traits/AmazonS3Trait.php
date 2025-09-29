<?php

namespace App\Traits;

use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;
use Aws\S3\ObjectUploader;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\MultipartUploadException;

trait AmazonS3Trait
{
  public function getS3Client(): S3Client
  {
    $credentials = [
      'key' => $_SERVER['AWS_ACCESS_KEY_ID'],
      'secret' => $_SERVER['AWS_SECRET_ACCESS_KEY'],
    ];
    return new S3Client([
      'region' => $_SERVER['AWS_DEFAULT_REGION'],
      'credentials' => $credentials,
      'version' => 'latest'
    ]);
  }

  /**
   * Mulptipart Uploader based on  documentation 
   * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-multipart-upload.html#object-uploader
   * @param string $pathSourceFile  
   * @param string @fileName  its a new file name from aws
   * @return string  content Url from amazon repository object
   */

  public function  objectUploader(string $pathSourceFile, string $fileName): string
  {
    $bucket = $_SERVER['AWS_BUCKET_NAME'];
    // $key = 'my-file.zip';
    $key = $fileName;
    // Use a stream instead of a file path.
    $source = fopen($pathSourceFile, 'rb');
    $s3Client = $this->getS3Client();
    $uploader = new ObjectUploader(
      $s3Client,
      $bucket,
      $key,
      $source
    );
    $str  = "";
    do {
      try {
        $result = $uploader->upload();
        if ($result["@metadata"]["statusCode"] == '200') {
          $url = (string) $result["ObjectURL"];
        }
      } catch (MultipartUploadException $e) {
        rewind($source);
        $uploader = new MultipartUploader($s3Client, $source, [
          'state' => $e->getState(),
        ]);
      }
    } while (!isset($result));
    fclose($source);
    return $str;
  }

  public function getDocumentLink(string $fileName, string $bucket = "", string $lTime  =  '+10 minutes'): string
  {
    $s3Client = $this->getS3Client();
    $command = $s3Client->getCommand('GetObject', [
      'Bucket' => ($bucket === "") ? $_SERVER['AWS_BUCKET_NAME'] : $bucket,
      'Key'    => $fileName,
    ]);
    $url = $s3Client->createPresignedRequest($command, $lTime)->getUri()->__toString();
    return $url;
  }

  public function existsObject(string $fileName, string $bucket = ""): bool
  {
    $s3Client = $this->getS3Client();
    $b = ($bucket === "") ? $_SERVER['AWS_BUCKET_NAME'] : $bucket;
    return $s3Client->doesObjectExist($b, $fileName);
  }

  public function putObject(string $filePath, string $fileName, string $bucket = ""): string
  {
    $s3Client = $this->getS3Client();
    try {
      // Subimos el archivo al bucket
      $result = $s3Client->putObject([
        'Bucket' => ($bucket === "") ? $_SERVER['AWS_BUCKET_NAME'] : $bucket,
        'Key' => $fileName,
        'SourceFile' => $filePath
      ]);
      return (string) $result['ObjectURL'];
    } catch (S3Exception $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getObject(string $fileName): string
  {
    $s3Client = $this->getS3Client();
    try {
      $result = $s3Client->getObject([
        'Bucket' => $_SERVER['AWS_BUCKET_NAME'],
        'Key'    => $fileName,
      ]);
      return (string) $result['Body'];
    } catch (S3Exception $e) {
      throw new \DomainException($e->getMessage());
    }
  }

  public function getObjectB64(string $fileName): string
  {
    $result = $this->getObject($fileName);
    $content = base64_encode((string) $result);
    return $content;
  }
}
