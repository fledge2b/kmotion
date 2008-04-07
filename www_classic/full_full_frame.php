<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>

	<meta http-equiv="content-type" content="text/html; charset=us-ascii">
	<link rel="stylesheet" type="text/css" href="base2.css">
	<title>Kmotion</title>


<script language="javascript">

	// The Full Interleave frame ... display full screen while events active ... 

	// The variables. Due to Javascript not having a sleep() function window.setTimeout() is used. This causes problems
	// when trying to pass parameters resulting in a relatively high number of 'global' variables.  A closure construct is
	// unworkable resulting in  a recursion error.

	parent_cache = {
		view_seqs: parent.state.view_seqs,
		view_format: parent.state.view_format,
		video_feeds: parent.state.video_feeds,
		pref_interleave: parent.state.interleave,
		pref_motion_pause: parent.state.pref_motion_pause,
		pref_normal_pause: parent.state.pref_normal_pause
		};

	stream = {
		cache_jpeg: [],				// Caching jpeg array to avoid flicker
		cache_num: 0,				// Count into above array
		loaded: "false",			// Three way status flag string, false, error or true if image has been loaded

		feed:0,					// Holds the feed pointer ... 1 ... state.video_feeds
		start_time: 0,				// The start time in ms for the current view

		start_time: 0,				// The start time in ms for the current view
		server_returned: false,			// Set when motion first detected

		server_reply1: [],			// An array of jpeg filenames returned by the AJAX call
		server_reply2: [],			// An array of events ie feeds with motion detected returned by the AJAX call

		server_snap2: [],			// A snapshot of the interleave feed array ... ie server_reply2
		interleave_ptr:	1,			// Pointer into the snapshot server_snap2

		cache_try_count: 0			// a caching try counter
		};

	for (var i=0; i < 16; i++)
	{
		stream.cache_jpeg[i] = new Image();
	}


	//*******************************************************************************************************************************************************************************
	// AJAX ...
	//******************************************************************************************************************************************************************************

	function server_poll()
	{
		var xmlHttpReq;
		var response;
		// Mozilla/Safari
		if (window.XMLHttpRequest)
		{
			xmlHttpReq = new XMLHttpRequest();
		}
		// IE
		else if (window.ActiveXObject)
		{
			xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlHttpReq.open("POST", "feed_status.php", true);
		xmlHttpReq.send(null);
		xmlHttpReq.onreadystatechange = function() 
		{	
			if (xmlHttpReq.readyState == 4)
			{						// Array of latest jpeg filenames for feeds: index 1 - 16
        			stream.server_reply1 = xmlHttpReq.responseText.split("#");
									// Array of events: index 0 - length
				stream.server_reply2 = stream.server_reply1[17].split("$");	
			}
		}
	}


	//*******************************************************************************************************************************************************************************
	// Inteleave loop code
	//******************************************************************************************************************************************************************************

	function scan_motion()
	{	
		if ((stream.server_reply2.length) > 1)  // If there are events ...
		{
			// Snapshot the server_reply2 array in case it changes mid display
			stream.server_returned = true;
			stream.snap_reply2 = stream.server_reply2;
			var now = new Date();
			stream.start_time = now.getTime();
			stream_video();
			return;
		}
		if (stream.server_returned)
		{
			window.setTimeout("parent.frame_full_callback2()", 1);
			return
		}
		document.getElementById("image_1").src = "misc/caching.jpeg";
		server_poll()
		window.setTimeout("scan_motion()", 250);
	}


	function stream_video()
	{
		if (stream.interleave_ptr >= stream.snap_reply2.length)
		{
			stream.interleave_ptr = 1;				// End of an interleave cycle
			document.getElementById("text_1").innerHTML = ""; 	// Clear the text field 
			window.setTimeout("scan_motion()", 1); 			// restart scan_motion()
			return;
		}
		else
		{	
			stream.feed = stream.snap_reply2[stream.interleave_ptr];
			var now = new Date();					// Keep refreshing jpegs of current feed
			if (now.getTime() > stream.start_time + 5000)		// for 5 seconds, then move to next feed
			{
				stream.start_time = now.getTime();
				stream.interleave_ptr++;
			}
			server_poll();
			cache();
		}
	}

	function cache()
	{
		stream.cache_num++;  // caching as a browser workaround
		stream.cache_num = (stream.cache_num > 15)?0:stream.cache_num;

		var feed_reply1 = stream.server_reply1[stream.feed];
		var jpeg_file = (feed_reply1 == undefined)?"misc/caching.jpeg":feed_reply1;
		if (feed_reply1 != "" && feed_reply1 != undefined)
		{
			stream.cache_jpeg[stream.cache_num].onload = function ()
			{
				set_view_text();
				stream.cache_try_count = 0;
				document.getElementById("image_1").src = jpeg_file;
				stream.loaded = "true";
			}
	
			stream.cache_jpeg[stream.cache_num].onerror = function ()
			{
				stream.cache_try_count = 0;
				stream.loaded = "error";
			}
			stream.cache_jpeg[stream.cache_num].src = jpeg_file;
			cache_wait();
		}
		else
		{
			window.setTimeout("stream_video()", 1);
		}
	}	


	function cache_wait()
	{
		stream.cache_try_count++;
		if (stream.loaded == "true")
		{
			stream.loaded = "false";
			var pause = Math.max(parent_cache.pref_motion_pause, 100);  // pause needed if browser accessing server on localhost, code in lowbw runns too fast 
			window.setTimeout("stream_video();", pause);  // causing image freezing issues
		}
		else if (stream.loaded == "error")
		{
			stream.cache_try_count = 0;
			stream.loaded = "false";
			window.setTimeout("stream_video();", 1);
		}
		else if (stream.loaded == "false")
		{
			if (stream.cache_try_count < 99)
			{
				window.setTimeout("cache_wait();", 30);
			}
			else
			{
				stream.cache_try_count = 0;
				stream.loaded = "false";
				window.setTimeout("stream_video();", 1);
			}
		}
	}


	function set_view_text()
	{
		document.getElementById("text_1").innerHTML = stream.feed + " : " + parent.state.feed_text[stream.feed];
		document.getElementById("text_1").style.color = "#ff0000";
	}
























	function update()
	{
		if (stream.cache_jpeg[stream.cache_num].complete)
		{
			document.getElementById("image_1").src = stream.cache_jpeg[stream.cache_num].src;
			document.getElementById("text_1").innerHTML = stream.feed + " : " + parent.state.feed_text[stream.feed];
			document.getElementById("text_1").style.color = "#ff0000";	// Set text to red
			stream.try_count = 0;
			window.setTimeout("stream_video()", parent.state.pref_motion_pause);
		}
		else 
		{
			stream.try_count++;		// If cache_jpeg is not completely downloaded, keep trying ...
			if (stream.try_count > 1000) window.setTimeout("stream_video()", 1);
			window.setTimeout("update()", 25);
		}
	}



</script>
</head>
<body id="live_view">
	<?php  

	define("COLS", 1);
	define("COL_PADDING", 3);

	define("ROWS", 1);
	define("ROW_PADDING", 3);

	define("VIEW_WIDTH", $_GET['width']);
	define("VIEW_HEIGHT", $_GET['height']);

	// This is a black art, px never quite add up so may need to tweak +7	
	$scale_width = VIEW_WIDTH;
	$scale_height = VIEW_HEIGHT - ((ROW_PADDING * 2) + 7);

	// Scale the jpeg keeping aspect ratio 384 / 288 = 1.33 
	if (($scale_width / $scale_height) < 1.33)
	{
		$scale = $scale_width / (384 * (COLS - 1));
		$scale_width = $scale * 384;
		$scale_height =  $scale * 288;
	}
	else
	{
		$scale = $scale_height / (288 * ROWS);
		$scale_width = $scale * 384;
		$scale_height =  $scale * 288;		
	}
	
	$col_width = $scale_width + COL_PADDING;
	$total_cols_width = ($col_width * COLS) - COL_PADDING;
	$lhs_margin = ((VIEW_WIDTH - $total_cols_width) / 2);

	// Absolute positioning 
	for ($row = 0; $row < ROWS; $row++)
	{
		for ($col = 0; $col < COLS; $col++)
		{
			$pos = ($col + 1) + ($row * COLS);
			$camera_src = "misc/caching.png";

			printf("<img id=\"image_%d\" style=\"position:absolute;top:%dpx;left:%dpx;\"  src=\"%s\" width=%dpx height=%dpx onClick=\"view_clicked(%s);\" alt=\"Camera image\">", $pos, (($row + 1) * ROW_PADDING) + ($row * $scale_height), $lhs_margin + ($col * COL_PADDING) + ($col * $scale_width) , $camera_src,  $scale_width, $scale_height, $pos);

			printf("<span id=\"text_%d\" style=\"position:absolute;top:%dpx;left:%dpx;\" alt=\"camera text\">", $pos, (($row + 1) * ROW_PADDING) + ($row * $scale_height)+ 3, $lhs_margin + ($col * COL_PADDING) + ($col * $scale_width) + 3);
			printf("</span>");
		}
		printf("</div>");
	}
	?>
 
<script language="JavaScript" type="text/javascript" >
scan_motion();
</script>

</body>