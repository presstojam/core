<?php
namespace PressToJamCore\Services;

use Aws\CloudFront\CloudFrontClient; 
use Aws\Exception\AwsException;

class CloudFrontManager {

    private $client;
    private $distribution_id;

    function __construct(\PressToJamCore\Configs\AWS $config) {
        $arr = $config->toArr();
        $this->client = new CloudFrontClient($arr['settings']);
        $this->distribution_id = $config->resource;
    }


    public function createInvalidation($paths) {
        $caller_ref = time();
        try {
            $result = $this->client->createInvalidation([
                'DistributionId' => $this->distribution_id,
                'InvalidationBatch' => [
                    'CallerReference' => $caller_ref,
                    'Paths' => [
                        'Items' => $paths,
                        'Quantity' => count($paths),
                    ],
                ]
            ]);
    
            $message = '';
    
            if (isset($result['Location']))
            {
                $message = 'The invalidation location is: ' . 
                    $result['Location'];
            }
    
            $message .= ' and the effective URI is ' . 
                $result['@metadata']['effectiveUri'] . '.';
    
            return $message;
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
        return $caller_ref;
    }

}