<?php

namespace PressToJamCore\Services;


use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;

class SQSHandler  {

    protected $client;

    function __construct(\PressToJamCore\Configs\AWS $config) {
        $arr = $config->toArr();
        $this->client = new SqsClient($arr["settings"]);
        $this->queue = $config->resource;
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


    function read($callback, $logger = false) {
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
                if ($logger) {
                    $logger->addLog("Queue failed: " . $e->getMessage());
                } else {
                    file_put_contents("/tmp/failed.txt", $e->getMessage());
                    $fp = fopen("/tmp/log.txt", 'a');
                    fwrite($fp, date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n");
                    fclose($fp);
                }
            }
        }
    }
} 