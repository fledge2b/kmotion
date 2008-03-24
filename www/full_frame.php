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


<script language="javascript">

	// The Full frame ... display split screens but goto full screen while events active ... 

	// The variables. Due to Javascript not having a sleep() function window.setTimeout() is used. This causes problems
	// when trying to pass parameters resulting in a relatively high number of 'global' variables. A closure construct is
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
		loaded: false,				// Status flag, true if image has been loaded

		view: 0,				// Holds the current view number ... 1 ... parent.state.view_format squared
		feed:0,					// Holds the feed number ... 1 ... state.video_feeds

		view_change: 0,				// Contains number of last manual view change, used to avoid caching issues
		last_camera_button: 0,			// Contains last camera button pressed, ... set by parent.state.view_format()

		server_reply1: [],			// An array of jpeg filenames returned by the AJAX call
		server_reply2: [],			// An array of events ie feeds with motion detected returned by the AJAX call

		interleave_view: start_view(),		// Pointer into view sequence ...  1 ... parent.state.view_format squared

		cache_try_count: 0			// a caching try counter
		};

	stream.cache_jpeg[0] = new Image();
	stream.cache_jpeg[1] = new Image();
	stream.cache_jpeg[2] = new Image();
	stream.cache_jpeg[3] = new Image();


	// start_view() calculates the initial view setting to ensure that view 1 is updated first in all cases
	function start_view()
	{
		var ptr = 1;
		for (var i=1; i<=Math.pow(parent.state.view_format, 2); i++)
		{
			if (parent.state.view_seqs[parent.state.view_format][i] <= parent.state.video_feeds)
			{
				ptr = i;
			}
		}
		return ptr-1;
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
	// Cut down interleave loop code, uses parent.frame_full_callback() frame to display interleave views
	//******************************************************************************************************************************************************************************

	function stream_video()
	{
		if ((stream.server_reply2.length) > 1)  // If > 1 an event has occured ie
		{					// motion has been detected
			window.setTimeout("parent.frame_full_callback()", 1);
			return;
		}
		{	
			stream.interleave_view++;
			stream.interleave_view = (stream.interleave_view > (Math.pow(parent.state.view_format, 2)))?1:stream.interleave_view;
			stream.view = stream.interleave_view;
		}

		server_poll();  // Don't bother with 'no video' views 
		if (parent.state.view_seqs[parent.state.view_format][stream.view] <= parent.state.video_feeds)
		{
			cache();
		}
		else 
		{
			window.setTimeout("stream_video()", 5);  // Avoids CPU pegging if all images are 'no video'
		}
	}


	function cache()
	{
		stream.cache_num++;  // caching as a browser workaround
		stream.cache_num = (stream.cache_num > 3)?0:stream.cache_num;

		stream.cache_jpeg[stream.cache_num].onload = function ()
		{
			set_view_status();
			stream.cache_try_count = 0;
			stream.loaded = true;
		}

		stream.cache_jpeg[stream.cache_num].onerror = function ()
		{
			stream.cache_try_count = 0;
			stream.loaded = true;
		}

		stream.feed = parent_cache.view_seqs[parent_cache.view_format][stream.view];	
		// Avoid flashes of white on views
		var feed_reply1 = stream.server_reply1[stream.feed];
		var jpeg_file = (feed_reply1 == undefined)?"misc/caching.jpeg":feed_reply1;
		if (feed_reply1 != "" && feed_reply1 != undefined)
		{
			if (stream.view_change == stream.view)  // If stream view is the last view changed, cache it for a cycle
			{
				stream.cache_jpeg[stream.cache_num].src = "misc/caching.jpeg";
				stream.view_change = 0;
			}
			for (var i=1; i <= (Math.pow(parent_cache.view_format, 2)); i++) // Scan for views with duplicate feeds
			{
				if (parent_cache.view_seqs[parent_cache.view_format][i] == stream.feed)  // Set .src of all views found
				{
					document.getElementById("image_" + i).src = jpeg_file;
				}
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
		if (stream.loaded)
		{
			if (parent_cache.pref_interleave && ((stream.server_reply2.length) > 1))
			{
				stream.loaded = false;
				var pause = Math.max(parent_cache.pref_motion_pause, 100);
				window.setTimeout("stream_video();", pause);
			}
			else
			{
				stream.loaded = false;
				var pause = Math.max(parent_cache.pref_normal_pause, 100);
				window.setTimeout("stream_video();", pause);
			}
		}
		else
		{
			if (stream.cache_try_count < 99)
			{
				window.setTimeout("cache_wait();", 30);
			}
			else
			{
				stream.cache_try_count = 0;
				stream.loaded = false;
				window.setTimeout("stream_video();", 1);
			}
		}
	}


	//*******************************************************************************************************************************************************************************
	// Setup view code
	//******************************************************************************************************************************************************************************

	function set_view_background()
	{
		for (var i=1; i <= (Math.pow(parent.state.view_format, 2)); i++)
		{
			if (parent.state.view_seqs[parent.state.view_format][i] <= parent.state.video_feeds)
			{
				document.getElementById("image_"+i).src = "misc/caching.jpeg";
			}
			else 
			{
				document.getElementById("image_"+i).src = "misc/no_video.jpeg";
			}
		}
	}


	function set_view_text()
	{
		for (var i=1; i <= (parent.state.view_format * parent.state.view_format); i++)
		{
			if (parent.state.view_seqs[parent.state.view_format][i] <= parent.state.video_feeds)
			{
				document.getElementById("text_"+i).innerHTML = parent.state.view_seqs[parent.state.view_format][i] + " : " + parent.state.feed_text[parent.state.view_seqs[parent.state.view_format][i]];
			}
			else
			{
				document.getElementById("text_"+i).innerHTML = parent.state.view_seqs[parent.state.view_format][i] + " : No Video";
			}
		}
	}


	function set_view_status()
	{
		for (var i=1; i <= (parent.state.view_format * parent.state.view_format); i++)
		{
			var view = parent.state.view_seqs[parent.state.view_format][i];
			{
				var flag = false;
				for (var j=0; j < (stream.server_reply2.length); j++)
				{
					if (view == stream.server_reply2[j]) flag = true;
				}
				if (flag)
				{
					document.getElementById("text_"+i).style.color = "#ff0000";
				}
				else
				{
					document.getElementById("text_"+i).style.color = "#0000ff";	
				}
			}
		
		}
	}


	//*******************************************************************************************************************************************************************************
	// View clicked code
	//******************************************************************************************************************************************************************************

	function view_clicked(view)
	{
		if (stream.last_camera_button != 0)
		{
			
			parent.state.view_seqs[parent.state.view_format][view] = stream.last_camera_button;
			view_change = view;

			if (parent.state.view_seqs[parent.state.view_format][view] <= parent.state.video_feeds)
			{
				document.getElementById("image_"+view).src = "misc/caching.jpeg";
			}
			else 
			{
				document.getElementById("image_"+view).src = "misc/no_video.jpeg";
			}
			set_view_text();
			set_view_status();	
			stream.last_camera_button = 0;
		}
		else 
		{
			stream.last_camera_button = 0;	// Call parent so buttons on control panel are updated
			window.setTimeout("parent.frame_standard_callback("+parent.state.view_seqs[parent.state.view_format][view]+")", 1);
		}
	}

	
	function parent_button_clicked(button)
	{
		if (parent.state.view_format == 1)
		{	
			parent.state.view_seqs[1][1] = button;
			stream.view_change = stream.view;
	
			if (parent.state.view_seqs[parent.state.view_format][stream.view] <= parent.state.video_feeds)
			{
				document.getElementById("image_"+stream.view).src = "misc/caching.jpeg";
			}
			else 
			{
				document.getElementById("image_"+stream.view).src = "misc/no_video.jpeg";
			}
			set_view_text();
			set_view_status();	
		}
		else
		{
			stream.last_camera_button = button;
		}
	}

</script>
</head>
<body id="live_view">
	<?php  

	define("COLS", $_GET['grid']);
	define("COL_PADDING", 3);

	define("ROWS", $_GET['grid']);
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
			$camera_src = "misc/cashing.png";

			printf("<img id=\"image_%d\" style=\"position:absolute;top:%dpx;left:%dpx;\"  src=\"%s\" width=%dpx height=%dpx onClick=\"view_clicked(%s);\" alt=\"Camera image\">", $pos, (($row + 1) * ROW_PADDING) + ($row * $scale_height), $lhs_margin + ($col * COL_PADDING) + ($col * $scale_width) , $camera_src,  $scale_width, $scale_height, $pos);

			printf("<span id=\"text_%d\" style=\"position:absolute;top:%dpx;left:%dpx;\" alt=\"camera text\">", $pos, (($row + 1) * ROW_PADDING) + ($row * $scale_height)+ 3, $lhs_margin + ($col * COL_PADDING) + ($col * $scale_width) + 3);
			printf("</span>");
		}
		printf("</div>");
	}
	?>
 
<script language="JavaScript" type="text/javascript" >
set_view_background();
set_view_text();
set_view_status();
stream_video();
</script>

</body>