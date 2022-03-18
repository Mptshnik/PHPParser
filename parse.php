<?php
$logFile = 'access_log';
$data = parseFile($logFile);
$output = convertToJson($data);

file_put_contents('output.json', $output);


/**
 *@param string $fileName
 *@return array
 */
function parseFile(string $fileName): array {
	$lines = file($fileName, FILE_IGNORE_NEW_LINES);
	$data = array();

	$ips = parseArray($lines, '@(\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3})@', 0);
	//https://stackoverflow.com/questions/206059/php-validation-regex-for-url
	$urls = parseArray($lines, "@(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))@", 0);
	$traffic = parseArray($lines, '@\s(\d{3})\s(\d{1,100})\s@', 2);
	$statusCodes = parseArray($lines, '@\s(\d{3})\s(\d{1,100})\s@', 1);
	$crawlers = parseArray($lines, '@"([a-zA-Z0-9]+)/\d{1,10}.+@', 1);

	$data['views'] = count($ips);
	$data['urls'] = count(getUniqueElements($urls));
	$data['traffic'] = array_sum($traffic);
	$data['linesCount'] = getFileLength($fileName);
	$data['statusCodes'] = getCountedItems($statusCodes);
	$data['crawlers'] = getCountedItems($crawlers);

	return $data;
}


/**
 *@param array $array
 *@return array
 */
function getUniqueElements(array $array): array {
	$uniqueElements = array();
	
	foreach ($array as $key1 => $value1) {
		$count = 0;
		foreach ($array as $key2 => $value2) {
			if($value1 === $value2){
				$count += 1;
			}
		}
		if($count === 1){
			$uniqueElements[] = $value1;
		}
	}

	return $uniqueElements;
}

/**
 *@param array $array
 *@return array
 */
function getCountedItems(array $array): array {
	$items = array();

	foreach (array_unique($array) as $key1 => $value1) {
		$count = 0;
		foreach ($array as $key2 => $value2) {	
			if($value1 === $value2){
				$count+=1;
			}
		}
		$items[$value1] = $count;
	}

	return $items;
}

/**
 *@param string fileName
 *@return int
 */
function getFileLength(string $fileName): int {
	$linesCount = 0;
	$file = fopen($fileName, 'r');

	while(!feof($file)){
  		$line = fgets($file);
  		$linesCount++;
  	}

	fclose($file);
	return $linesCount;
}

/**
 *@param array $array
 *@param string $pattern
 *@param int $matchIndex
 *@return array
 */
function parseArray(array $array, string $pattern, int $matchIndex): array {
	$data = array();

	foreach ($array as $line) {
		if(preg_match($pattern, $line, $matches)){
			$data[] = $matches[$matchIndex];
		}
	}

	return $data;
}

/**
 *@param array $data
 *@return string
 */
function convertToJson(array $data): string {
	$result = '';

	foreach ($data as $key => $value) {
		if(is_array($value)){
			$jsonValue = convertToJson($value);
			if(is_string($key)){
				$result = "$result\"$key\":$jsonValue,";
			}
			else{
				$result = "$result$jsonValue,";
			}			
		}
		else{
			$result = "$result\"$key\":\"$value\",";
		}
	}
	
	$result = str_replace(', }', '}', "{ $result }");
	return $result;
}

?>