<?php

namespace PressToJamCore;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail {

	private $email="";
	private $bcc_email=array();
	private $cc_email=array();
	private $attachments=array();
	private $subject="";
	private $body="";
	private $alt_body="";
	private $is_html=false;
	private $results_ob;
	
	public function __construct($meta_state = false)
	{
		$this->meta_state = $meta_state;
	}
	
	function regFile($view_file)
	{
		$root = Configs::s()->get("doc_root");
		$content_root = Configs::s()->get("view_root");
		$file = str_replace(".php", "", $view_file) . ".php";
		$this->file = $root . $content_root . "/" . ltrim($file, "/");
	}
	
	function regResults($name, $results)
	{
		if (!$this->results_ob) 
		{
			$this->results_ob = new ResultsOb();
		}
		$this->results_ob->reg($name, $results);
	}
	
	function __get($name)
	{
		return $this->results_ob->{$name};
	}
	
		
	function run()
	{
		$mail = new PHPMailer(true);
		$config = Config::s();
		$AC = $this; //set a default variable for ease of use
		include $this->file;
		//in the file, the body will be set
		//$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
		$mail->isSMTP();                                            // Send using SMTP
		$mail->Host       = $config->get("SMTP_HOST");              // Set the SMTP server to send through
		$mail->SMTPAuth   = $config->get("SMTP_AUTH");              // Enable SMTP authentication
		$mail->Username   = $config->get("SMTP_USERNAME");          // SMTP username
		$mail->Password   = $config->get("SMTP_PASSWORD");          // SMTP password
		$mail->SMTPSecure = $config->get("SMTP_AUTHTYPE");		        // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
		$mail->Port       = $config->get("SMTP_PORT");              // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
		$mail->IsHTML($this->is_html);
		//Recipients
		$mail->setFrom($config->get("SITE_EMAIL"), $config->get("SITE_EMAIL"));
		$mail->addAddress($this->email);     // Add a recipient
		$mail->addReplyTo($config->get("SITE_EMAIL"));
		foreach($this->cc_email as $email)
		{
			$mail->addCC($email);
		}
		
		foreach($this->bcc_email as $email)
		{
			$mail->addBCC($email);
		}

		// Attachments
		foreach($this->attachments as $name=>$file)
		{
			$mail->addAttachment($file, $name);         // Add attachments
		}

		// Content
		if ($this->is_html) $mail->isHTML(true);                                  // Set email format to HTML
		$mail->Subject = $this->subject;
		$mail->Body    = $this->body;
		$mail->AltBody = $this->alt_body;

		$mail->send();
	}

}