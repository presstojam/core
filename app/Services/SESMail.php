<?php

namespace PressToJamCore\Services;

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

class SESMail extends PHPMailer {

    public function connect($region = "eu-west-1") {
        $client = SesClient::factory(array(
            'version'=> 'latest',     
            'region' => $region
        ));
    }


    public function send() {
       // Attempt to assemble the above components into a MIME message.
        if (!$this->preSend()) {
            echo $this->ErrorInfo;
            return;
        } else {
            // Create a new variable that contains the MIME message.
            $message = $this->getSentMIMEMessage();
        }

        // Try to send the message.
        try {
            $result = $client->sendRawEmail([
            'RawMessage' => [
                'Data' => $message
            ]]);
            // If the message was sent, show the message ID.
            $messageId = $result->get('MessageId');
            echo("Email sent! Message ID: $messageId"."\n");
        } catch (SesException $error) {
            // If the message was not sent, show a message explaining what went wrong.
            echo("The email was not sent. Error message: "
            .$error->getAwsErrorMessage()."\n");
        }
    }

}