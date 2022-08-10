<?php
namespace PressToJamCore\Wrappers;

use Aws\CloudFront\CloudFrontClient; 
use Aws\Exception\AwsException;

class CloudFrontManager {

    private $client;
    private $distribution_id;

    function __construct(\PressToJamCore\Configs $configs) {
        $configs->isRequired("aws", "settings");
        $configs->isRequired("aws", "cfdistributionid");
        $this->client = new CloudFrontClient($configs->getConfig("aws", "settings"));
        $this->distribution_id =$configs->getConfig("aws", "cfdistributionid");
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