<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=us-ascii">
	<link rel="stylesheet" type="text/css" href="base2.css">
	<title>Kmotion</title>

</head>
<body id="server_stats">

<!--
Generates server statistics page getting its raw data from 'uname' & 'top'. This is very messy code due to the large
number of string splicing & searching necessary to filter the data. I would welcome someone re-writting it in a more
elegant fashion :)
-->

<?php

	define("VIEW_WIDTH", $_GET['width'] - 10);
	define("VIEW_HEIGHT", $_GET['height']);

	// Generic function to display a bar graph ...
	function display_bar($bar_text1, $bar_text2, $bar_offset, $value, $warning_value, $max_value)
	{
		$value = min($value, $max_value); 
		$bar_px = $value * (VIEW_WIDTH / $max_value);

		if ($value >= $warning_value) $bar_color='ff0000';
		else $bar_color='00ff00';
		echo "<div style=\"background-color:#e1e1e1;\">\n";
		printf("\t<div class=\"bar_graph text\" style=\"width:%dpx;background-color:#%s;\">\n", $bar_px, $bar_color);
		printf("\t\t&nbsp;%s\n", $bar_text1);
		printf("\t\t<span style=\"position:absolute;left:%dpx;\">&nbsp;%s</span>\n", $bar_offset, $bar_text2);
		echo "\t</div>\n";
		echo "</div>\n\n";
	}

	// Heading
	$uname = exec('uname -a');
	echo "\n<div id=\"heading\">\n";
	$uname_array = explode(' ', $uname);
	printf("\tServer Stats: %s %s<br>\n",  $uname_array[0],$uname_array[1]);
	unset ($uname_array[0]);  // Remove items from array
	unset ($uname_array[1]);
	printf("\t<span id=\"sub_heading\">%s</span>\n", implode(" ", $uname_array));
	echo "</div>\n\n";

	// Get data from 'top' for rest of statistics
	exec('top -b -n 1', $topinfo);

	// Load averages
	echo "<div class=\"section_heading text\">\n";
	echo "\tLoad Averages\n";
	echo "</div>\n\n";

	$top_array = preg_split("/[\s,]+/", $topinfo[0]);

	// Workaround, 'top' changes its output format on the top line from '59 min,' to '1:00,'
	// displacing the outputed values in the array
	if ($top_array[9 + $offset] == 'average:') $offset = 1;
	else $offset = 0;

	display_bar('1&nbsp;Min', $top_array[9 + $offset], 90, $top_array[9 + $offset], 1, 1.5);
	display_bar('5&nbsp;Min', $top_array[10 + $offset], 90, $top_array[10 + $offset], 1, 1.5);
	display_bar('15&nbsp;Min', $top_array[11+ $offset], 90, $top_array[11 + $offset], 1, 1.5);

	// CPU	
	echo '<div class="section_heading text">';
	printf('CPU ');
	echo '</div>';

	$top_array = preg_split("/[\s,]+/", $topinfo[2]);
	$item = $top_array[1];
	display_bar('User', substr($item, 0, -2), 90, substr($item, 0, -3), 75, 100);
	$item = $top_array[2];
	display_bar('System', substr($item, 0, -2), 90, substr($item, 0, -3), 75, 100);

	// Memory	
	$top_array = preg_split("/[\s,]+/", $topinfo[3]);
	$item = $top_array[1];	

	echo '<div class="section_heading text">';	
	printf('Memory %s', $item);
	echo '</div>';

	$total_mem = substr($item, 0, -1);

	$item = $top_array[5];
	$free = substr($item, 0, -1);

	$item = $top_array[7];
	$buffers = substr($item, 0, -1);

	// Darn it right in the middle of 'Memory' I need data from 'Swap' for cached stats
	$top_array2 = preg_split("/[\s,]+/", $topinfo[4]);
	$item2 = $top_array2[7];
	$cached = substr($item2, 0, -1);

	$app = $total_mem - ($buffers + $cached + $free);
	display_bar('System', round(($app / $total_mem) * 100, 1).'%', 90, $app, $total_mem + 1, $total_mem);
	display_bar('Buffers', round(($buffers / $total_mem) * 100, 1).'%', 90, $buffers, $total_mem + 1, $total_mem);
	display_bar('Cached', round(($cached / $total_mem) * 100, 1).'%', 90, $cached, $total_mem + 1, $total_mem);

	// Swap	
	$top_array = preg_split("/[\s,]+/", $topinfo[4]);
	$item = $top_array[1];

	echo '<div class="section_heading text">';	
	printf('Swap %s', $item);
	echo '</div>';

	$total_mem = substr($item, 0, -1);
	$item = $top_array[3];
	$swap = substr($item, 0, -1);
	display_bar('Used', round(($swap / $total_mem) * 100, 1).'%', 90, $swap, 1, $total_mem);

	echo '<div class="section_heading text">';	
	echo('Click the \'Server Stats\' button to refresh');
	echo '</div>';

?>

</body>
