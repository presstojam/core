<?php

namespace PressToJamCore\Wrappers;


use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;

class SQSHandler  {

    protected $client;

    function __construct(\PressToJamCore\Configs $configs) {
        $configs->isRequired("aws", "settings");
        $configs->isRequired("aws", "sqsarn");
        $this->client = new SqsClient($configs->getConfig("aws", "settings"));
        $this->queue = $configs->getConfig("aws", "sqsarn");
    }


    function sendMessage($atts) {
    
        $params = [
            'DelaySeconds' => 10,
            'MessageBody' => json_encode($atts),
            'QueueUrl' => $this->queue
        ];

        
        try {
            $result = $this->client->sendMessage($params);
        } catch (AwsException $e) {
            // output error message if fails
            echo $e->getMessage();
            error_log($e->getMessage());
        }
    }

    function sendFifoMessage($atts, $group_id, $dedup_id) {
    
        $params = [
            'MessageBody' => json_encode($atts),
            'QueueUrl' => $this->queue
        ];

        $params["MessageGroupId"] = $group_id;
        $params["MessageDeduplicationId"] = $dedup_id;
        
        try {
            $result = $this->client->sendMessage($params);
        } catch (AwsException $e) {
            // output error message if fails
            echo $e->getMessage();
            error_log($e->getMessage());
        }
    }


    function read($callback, $logger) {
        while (1) {
            try {
                $result = $this->client->receiveMessage(array(
                'AttributeNames' => ['SentTimestamp'],
                'MaxNumberOfMessages' => 1,
                'MessageAttributeNames' => ['All'],
                'QueueUrl' => $this->queue, // REQUIRED
                'WaitTimeSeconds' => 20,
                ));
        
                if (!empty($result->get('Messages'))) {
                    $msg = $result->get('Messages')[0];
                    if ($callback) {
                        $callback(json_decode($msg["Body"], true), $logger);
                    }
                    $result = $this->client->deleteMessage([
                    'QueueUrl' => $this->queue, // REQUIRED
                    'ReceiptHandle' => $msg['ReceiptHandle'] // REQUIRED
                    ]);
                } 
            } catch (AwsException $e) {
                //going to log that the process has executed and an error has happened,
                //next time cron checks, it can test for this.
                $logger->critical("Queue failed: " . $e->getMessage());
            }
        }
    }
} 