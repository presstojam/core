<?php

namespace PressToJamCore\Services;

use \Aws\S3\S3Client;
use \Aws\Exception\AwsException;

class AmazonS3Host
{
    private $client;
    private $bucket;
    private $path = "";
    private $prefix = "";
    private $public = false;
    static private $credentials;

    public function __construct(\PressToJamCore\Configs\AWS $config)
    {
        $arr = $config->toArr();
        $this->client = new S3Client($arr['settings']);
        $this->bucket = $config->resource;
        $this->path = $config->prefix;
        $this->public = $config->public;
    }


    public function invalidateCache($paths) {
        $client = \PressToJamCore\Configs\Factory::createCloudFrontManager();

        foreach($paths as $num=>$path) {
            $paths[$num] = "/" . ltrim($path, "/");
        }
        $client->createInvalidation($paths);
    }



    public function push($file_name, $content)
    {
        $content_types=array(
            "css"=>"text/css",
            "gz"=>"application/gzip",
            "gif"=>"image/gif",
            "htm"=>"text/html",
            "html"=>"text/html",
            "ico"=>"image/vnd.microsoft.icon",
            "jpeg"=>"image/jpeg",
            "js"=>"application/javascript",
            "png"=>"image/png",
            "txt"=>"text/plain",
            "json"=>"application/json",
            "xml"=>"application/xml",
            "pdf"=>"application/pdf",
            "odt"=>"application/vnd.oasis.opendocument.text");

        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $content_type =  (isset($content_types[$ext])) ? $content_types[$ext] : "application/octet-stream";

        $file_name = trim($this->path . $file_name);
        try {
       

            $result = $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key'    => $file_name,
            'Body'   => $content,
            'ContentType' => $content_type
        ]);
            //echo $result['ObjectURL'] . PHP_EOL;
        } catch (S3Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function pushBatch($arr) {
        foreach($arr as $file_name=>$content) {
            $this->push($file_name, $content);
        }

        if ($this->public) {
            $this->invalidateCache(array_keys($arr));
        }
    }

    public function get($file_name)
    {
        $file_name = trim($this->path . $file_name);
        try {
            $result = $this->client->getObject([
            'Bucket' => $this->bucket,
            'Key'    => $file_name,
            ]);
           return (string) $result['Body'];
        } catch (S3Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function list($dir = "") {
        echo "Listing files for " . $this->path . $dir;
        $objects = $this->client->listObjectsV2([
            'Bucket' => $this->bucket,
            'Prefix' => $this->path . $dir
        ]);

        $arr=array();
        foreach($objects['Contents']  as $object) {
            $file_name = trim($object['Key']);
            $arr[] = trim(substr($file_name, strlen($this->path)));
        }
        return $arr;
    }

    public function fileExists($key) {
        return $this->client->doesObjectExist($this->bucket, $this->path . $key);
    }


    public function remove($file_name) {
        $file_name = trim($this->path . $file_name);
        $result = $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key'    => $file_name
        ]);
    }


    public function copy($file_name, $old_file) {
        $data = $this->get($old_file);
        $this->push($file_name, $data);
    }
}
