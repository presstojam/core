<?php

namespace PressToJamCore;



class View  {

	private $status_code = 200;
    private $status_message = "Success";
    private $response=null;
    private $is_multiple = false;
   
    function __construct() {
	}

  
	public function addResponse($response)
	{
        $this->response = $response;
	}

    public function addResponses(array $responses) {
        $this->response = $responses;
        $this->is_multiple = true;
    }

    public function getReponse() {
        return $this->response;
    }

    public function setStatus($code, $msg)
    {
        $this->status_code = $code;
        $this->status_message = $msg;
        $this->response = $msg;
    }
	
	
	function flush()
	{
        if (!headers_sent($filename, $linenum)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $this->status_code . ' ' . trim(str_replace(array("\r", "\n", "\t"), "", $this->status_message)), true, $this->status_code);
            header('Content-Type: application/json');
            
            if ($this->response === null ) echo json_encode($this->status_message);
            else echo json_encode($this->response);
            exit;
        } else {
			echo "Error headers sent on " . $filename . " and " . $linenum;
		}
	}
	

} 