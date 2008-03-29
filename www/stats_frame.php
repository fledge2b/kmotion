<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">

<!--
Copyright 2008 David Selby dave6502@googlemail.com

GNU General Public Licence (GPL)

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.
This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
details.
You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc., 59 Temple
Place, Suite 330, Boston, MA  02111-1307  USA
-->

<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=us-ascii">
	<link rel="stylesheet" type="text/css" href="base2.css">
	<title>Kmotion</title>

<script language="javascript" type="text/javascript">

var init = 15;
var count = 0;

function timer()
	{
		count = init;
		countdown();
	}

function countdown()
	{
		update_clock(count);
		count--;
		if (count > 0)
		{
			window.setTimeout("countdown();", 1000);
		}
		else
		{
		parent.frame_stats_callback()
		window.setTimeout("parent.frame_stats_callback()", 1);
		}
	}

function update_clock(secs)
	{
		document.getElementById("clock").innerHTML = secs;
	}

</script>

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
		echo "<div style=\"background-color:#e1e1e1;\">";
		printf("<div class=\"bar_graph text\" style=\"width:%dpx;background-color:#%s;\">", $bar_px, $bar_color);
		printf("&nbsp;%s", $bar_text1);
		printf("<span style=\"position:absolute;left:%dpx;\">&nbsp;%s</span>", $bar_offset, $bar_text2);
		echo "</div>";
		echo "</div>";
	}

	// Get data from 'top' for rest of statistics
	exec('top -b -n 1', $topinfo);

	// Heading
	$uname = exec('uname -a');
	echo "<div id=\"heading\">";
	$uname_array = explode(' ', $uname);
	printf("Server Stats: %s %s<br>",  $uname_array[0],$uname_array[1]);
	unset ($uname_array[0]);  // Remove items from array
	unset ($uname_array[1]);
	printf("<span id=\"sub_heading\">%s<br>", implode(" ", $uname_array));	
	printf("Server Uptime ");
	$top_array = preg_split("/[\s,]+/", $topinfo[0]);
	$i = 4;
	while ($top_array[$i + 1] != 'user' and $top_array[$i + 1] != 'users')
		{
			printf($top_array[$i].' ');
			$i++;
		}
	echo "</span></div>";

	// Load averages
	echo "<div class=\"section_heading text\">";
	echo "Load Averages";
	echo "</div>";
	$top_array = preg_split("/[\s,]+/", $topinfo[0]);
	$offset = sizeof($top_array) - 3;
	display_bar('1&nbsp;Min', $top_array[$offset], 90, $top_array[$offset], 1, 1.5);
	display_bar('5&nbsp;Min', $top_array[1 + $offset], 90, $top_array[1 + $offset], 1, 1.5);
	display_bar('15&nbsp;Min', $top_array[2+ $offset], 90, $top_array[2 + $offset], 1, 1.5);

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
	echo("Server Stats will refresh in ");
	echo("<span id='clock'>xx</span>");
	echo(" seconds");
	echo '</div>';

?>


<script language="JavaScript" type="text/javascript" >
timer();
</script>
