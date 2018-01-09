<?php
$totalPostImageLikes =0;
$img = array(
	array('image_id' => 6835,'likes' => 3, 'likes_proportion' => 25),
	array('image_id' => 6837,'likes' => 0, 'likes_proportion' => 0),
	array('image_id' => 6834,'likes' => 7, 'likes_proportion' => 57),
	array('image_id' => 6836,'likes' => 2, 'likes_proportion' => 17)
);
foreach($img as $im)
{
    $totalPostImageLikes +=  $im['likes'];
}

$totalPostImageVoteCount = $totalPostImageLikes;
if (count($img) > 0) {
	foreach($img as $key=>$images_likes)
	{
	    $likes_count = (int) $images_likes['likes'];
	    if ($likes_count > 0) {
	    	$per_votes[$key]['image_id'] = $images_likes['image_id'];
	        $per_votes[$key]['likes_proportion'] = (int)round(( $likes_count / $totalPostImageVoteCount ) * 100);
	        $total_per_votes += $per_votes[$key]['likes_proportion'];
	    }
	}
}
function assc_array_count_values( $array, $key ) {
	foreach( $array as $row ) {
		$new_array[] = $row[$key];
	}
	return array_count_values( $new_array );
}

function repair($array, $extra){
	    	// var_dump($array);

	$tmp = assc_array_count_values($array, 'likes_proportion');
	$max = 0;
    foreach( $array as $k => $v )
    {
        $max = max( array( $max, $v['likes_proportion'] ) );
    }
	$cnt = $tmp[$max];
	// print_r($tmp);die;
	if($cnt > 1 && $cnt < count($array)){
		foreach ($array as $key => $value) {
			if($max == $value['likes_proportion']){
				$array[$key+$cnt]['likes_proportion'] += $extra;
				break;
			}
		}
	}else{
		foreach ($array as $key => $value) {
			if($max == $value['likes_proportion']){
				$array[$key]['likes_proportion'] += $extra;
				break;
			}
		}
	}
	// print_r($array);die;
	return $array;
}

function find_index_with_position($array, $position) {
    foreach($array as $index => $single) {
        if($single['image_id'] == $position) return $index;
    }
    return FALSE;
}


// echo "<pre>";print_r($img);
$inter_array = repair($per_votes, (100-$total_per_votes));
// echo "<pre>";print_r($inter_array);

foreach ($img as $key => $value) {
	if($value['likes'] > 0){
    	$img[$key]['likes_proportion'] = $inter_array[find_index_with_position($inter_array, $img[$key]['image_id'])]['likes_proportion'];
	}
}
echo "<pre>";print_r($img);
die;
/*echo "<pre>";print_r($img);
// echo "<pre>Final votes";print_r(repair($per_votes, (100-$total_per_votes)));
foreach($inter_array as $index=>$val){
	if($inter_array[$index]["image_id"] == $img[$index]["image_id"]){
		$img[$index]['likes_proportion'] =  $inter_array[$index]["likes_proportion"];
	}
}*/
echo "<pre>";print_r($img);
// echo "<pre>";print_r(array_sum($inter_array,$img));
// print_r(array_merge_recursive($img, $per_votes));