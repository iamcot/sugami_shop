<?php

namespace App\Service;

use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;

class AwsService
{
    private $client;
    private $bucket;

    /**
     * @param string $bucket
     * @param array $s3args
     */
    public function __construct(string $bucket, array $s3args,  protected LoggerInterface $log)
    {
        $this->setBucket($bucket);
        $this->setClient(new S3Client($s3args));
    }

    private function setBucket($bucket)
    {
        $this->bucket = $bucket;
        return $this;
    }

    private function setClient(S3Client $client)
    {
        $this->client = $client;
        return $this;
    }

    protected function getClient()
    {
        return $this->client;
    }

    protected function getBucket()
    {
        return $this->bucket;
    }

    public function doUpload($file, $newFilename = null, $meta = [], $privacy = 'public-read'): string
    {
        if (!$newFilename) {
            $newFilename = $file->getClientOriginalName();
        }
        if (!isset($meta['contentType'])) {
            $mimeTypeHandler = finfo_open(FILEINFO_MIME_TYPE);
            $meta['contentType'] = finfo_file($mimeTypeHandler, $file);
            finfo_close($mimeTypeHandler);
        }

        return $this->getClient()->upload($this->getBucket(), $newFilename, file_get_contents($file), $privacy, [
            'Metadata' => $meta
        ])->toArray()['ObjectURL'];
    }

    public function getFiles($offSetKey = "")
    {
        try {
            $result = $this->getClient()->listObjectsV2([
                'Bucket' => $this->getBucket(),
                'Maxkeys' => 20,
                'StartAfter' => $offSetKey,
            ]);
            foreach ($result['Contents'] as $content) {
                $files[] = [
                    'Key' => $content['Key'],
                    'LastModified' => date('Y-m-d H:i:s', strtotime($content['LastModified']))
                ];
            }
        } catch (\Exception $ex) {
            $this->log->error($ex);
            $files = [];
        }
        return $files;
    }

    public function getFile(string $packCode) : string
    {
        $key = $packCode . ".webm";
        try {
            $result = $this->getClient()->getObject([
                'Bucket' => $this->getBucket(),
                'Key' => $key,
            ]);
            $file = $result['@metadata']['effectiveUri'];
        } catch (\Exception $ex) {
            $this->log->error($ex);
            $file = "";
        }
        return $file;
    }
}
