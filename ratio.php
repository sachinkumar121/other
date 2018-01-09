<?php
$votes = array(4,3,3,1);
$votes = array(4,4,3,1);
$votes = array(3,3,3);
$votes = array(2,2,4);
$votes = array(2,2);
$votes = array(4,4,3,4);
// sort($votes);
echo $max_in_votes = max($votes)."<br>";
// $votes = array(3,2,2);
/*$votes = array(2,2,2);
$votes = array(2,2,2);
$votes = array(2,2,2);*/
print_r($votes)."<br>";
$array_count_values = array_count_values($votes);
print_r($array_count_values)."<br>";
echo $max_in_repeat_votes = max($array_count_values)."<br>";
$total_votes = array_sum($votes)."<br>";
foreach ($votes as $value) {
	$per_votes[] = (int)round((($value ) * (100/  $total_votes)));
}
print_r($per_votes)."<br>";
echo $total_per_votes = array_sum($per_votes)."<br>";
echo $total_votes;
// repair($per_votes, (100-$total_per_votes));
echo "<pre>";print_r(repair($per_votes, (100-$total_per_votes)));
function repair($array, $extra){
	echo max($array);
	// var_dump($array);
	// echo "hey";
	print_r($array)."<br>";
	$tmp = array_count_values($array);
	print_r($tmp)."<br>";
	echo $cnt = $tmp[max($array)];
	if($cnt > 1 && $cnt < count($array)){
		$array[array_search(max($array), $array)+$cnt] += $extra;
	}else{
		$array[array_search(max($array), $array)] += $extra;
	}
	// echo count(array_filter($array,create_function('$a','return $a==max($array)')));
	// die;
	return $array;
	/*foreach ($array as $value) {
		$per_votes[] = $value;
	}*/
}
die;