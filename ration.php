<?php
// $votes = array(2,2);
// $votes = array(3,3,3);
// $votes = array(3,3,7,7,6,3,4,5,6,7,1,3);
$votes = array(4,6,8,8);
$votes = array(2,3,4);
$votes = array(3,3,3);
$votes = array(4,4,3,4);
$votes = array(2,2,4);
$votes = array(4,3,3,1);
// var_dump($votes);
// rsort($votes);
// print_r($votes);
echo "<pre>votes you have entered(DESC)";print_r($votes);
$total_votes = array_sum($votes);
foreach ($votes as $value) {
	$per_votes[] = (int)round((($value ) * (100 /  $total_votes)));
}
// print_r($per_votes);die;
$total_per_votes = array_sum($per_votes);
// repair($per_votes, (100-$total_per_votes));
echo "<pre>Final votes";print_r(repair($per_votes, (100-$total_per_votes)));
function repair($array, $extra){
	$tmp = array_count_values($array);
	$cnt = $tmp[max($array)];
	if($cnt > 1 && $cnt < count($array)){
		$array[array_search(max($array), $array)+$cnt] += $extra;
	}else{
		$array[array_search(max($array), $array)] += $extra;
	}
	return $array;
}