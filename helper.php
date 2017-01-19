<?php

function getSinaudevJSON(){
	$curl_ = curl_init();
	$config = array(
			CURLOPT_URL => 'http://sinaudev.org/json',
			CURLOPT_POST => false,
			CURLOPT_RETURNTRANSFER => true);
	curl_setopt_array($curl_, $config);
	$result = curl_exec($curl_);
	curl_close($curl_);

	return json_decode($result,true);
}

function arraySearch($search,$array_, $column=''){
	if (!empty($column)) $array_ = array_column($array_, $column);
	return array_filter($array_, 
						function ($haystack) use (&$search) { 
							return stripos($haystack, $search);
						});
}

function getSinaudevPost($title,$data){
	$find = arraySearch($title,$data,'title');
	$array_value = [];
	foreach($find as $key => $value){
  		$array_value[] = $data[$key];
	}
	return $array_value;
}

function rspace($str){
 return preg_replace('!\s+!', ' ',trim($str));	
}

function tokenTruncate($string, $your_desired_width) {
  $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
  $parts_count = count($parts);

  $length = 0;
  $last_part = 0;
  for (; $last_part < $parts_count; ++$last_part) {
    $length += strlen($parts[$last_part]);
    if ($length > $your_desired_width) { break; }
  }

  return implode(array_slice($parts, 0, $last_part));
}