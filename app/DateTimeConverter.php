<?php

namespace PressToJamCore;


class DateTimeConverter {
	
	

	static function convertTimestamp($timestamp, $format)
	{
	  return date($format, $timestamp);
	}
	
	static function convertTimestampDZ($timestamp, $format)
	{
	
	  return date($format, $timestamp);
	}
	

	static function getDurationStamp($duration)
	{
		$hours = -1;
		$mins = -1;
		$secs = -1;
	
		$stamp = 0;
		
		if (isset($duration['hours']))
		{
			$stamp += $duration['hours'] * 60 * 60;
		}
		
		if (isset($duration['mins']))
		{
			$stamp += $duration['mins'] * 60;
		}
		
		if (isset($duration['secs']))
		{
			$stamp += round($duration['secs']);
		}
		return $stamp;
	}
	
	
	static function convertDurationStamp($stamp, $format)
	{
		$durations = array();
		if (strpos($format, "H") !== false) 
		{
			$hours = floor($stamp / (60 * 60));
			$stamp -= $hours * 60 * 60;
			$durations['hours'] = $hours;
		}
		if (strpos($format, "i") !== false) 
		{
			$mins = floor($stamp / 60);
			$stamp -= $mins * 60;
			$durations['mins'] = ($mins < 10) ? "0" . $mins : $mins;
		}
		
		if (strpos($format, "s") !== false)
		{
			$stamp = round($stamp);
			$durations['secs'] = ($stamp < 10) ? "0" . $stamp : $stamp;
		}
		
		
		return implode(":", $durations);
	}
	
	static function getTimestamp($value, $format)
	{
        //check if value is already timestamp
        if (is_numeric($value)) return $value;
		$datetime = \DateTime::createFromFormat( $format, $value);
		$timestamp = $datetime->getTimestamp();
		return $timestamp;
	}
	
	static function getTimestampDZ($value, $format)
	{
		//, new \DateTimeZone(\DateTimeZone::EUROPE)
		return $value;
		$datetime = \DateTime::createFromFormat( $format, $value);
		$timestamp = $datetime->getTimestamp();
		return $timestamp;
	}
	
}