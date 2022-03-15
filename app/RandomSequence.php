<?php

namespace PressToJamCore;


class RandomSequence  {

	
	static function get($size, $salt = "", $num_only = false)
	{
		if ($num_only) $permitted_chars = "0123456789";
		else $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$code = $salt . substr(str_shuffle($permitted_chars), 0, $size);
		return $code;
	}


}