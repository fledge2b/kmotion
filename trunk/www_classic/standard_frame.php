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

	// The standard multi view frame ... with & without interleave

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
		preload_jpeg: [],			// Preloaded jpeg array
		preload_filename: [],			// Preloaded jpeg filenames
		preload_try_count: 0,			// Preload try counter
		
		preload_count: 0,			// Count into above jpeg array
		loaded: "false",			// Three way status flag string, false, error or true if image has been loaded

		view: 0,				// Holds the current view number ... 1 ... parent_cache.view_format squared
		feed:0,					// Holds the feed number ... 1 ... parent_cache.video_feeds

		view_change: 0,				// Contains number of last manual view change, used to avoid caching issues
		last_camera_button: 0,			// Contains last camera button pressed, ... set by parent_cache.view_format() 2 - 4

		server_reply1: [],			// The jpeg filename returned from the AJAX call
		server_reply2: [],			// An array of events ie feeds with motion detected from the AJAX call

		server_snap2: [],			// A snapshot of the interleave feed array ... ie server_reply2
		interleave_lock: false,			// Display an interleave feed or a sequence feed
		interleave_ptr:	0,			// Pointer into interleave feed array ... ie server_reply2
							// Prep value to ensure server_snap2 starts correctly
		interleave_view: start_view()		// Pointer into view sequence ...  1 ... parent_cache.view_format squared
		};

	for (var i=0; i < 16; i++)
	{
		stream.preload_jpeg[i] = new Image();
	}


	// start_view() calculates the initial view setting to ensure that view 1 is updated first in all cases
	function start_view()
	{
		var ptr = 1;
		for (var i=1; i<=Math.pow(parent_cache.view_format, 2); i++)
		{
			if (parent_cache.view_seqs[parent_cache.view_format][i] <= parent_cache.video_feeds)
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
		xmlHttpReq.onreadystatechange = function() 
		{
			if (xmlHttpReq.readyState == 4)
			{						// Array of latest jpeg filenames for feeds: index 1 - 16
        			stream.server_reply1 = xmlHttpReq.responseText.split("#");
									// Array of events: index 0 - length
				stream.server_reply2 = stream.server_reply1[17].split("$");
			}
		}
		xmlHttpReq.open("POST", "feed_status.php", true);
		xmlHttpReq.send(null);
	}


	//*******************************************************************************************************************************************************************************
	// Interleave loop code ... Its complex by necessity ....
	//******************************************************************************************************************************************************************************


	function stream_video()
	{
		if (stream.interleave_lock && parent_cache.pref_interleave)	// *** Interleave cycle code ***
		{
			if ((stream.server_reply2.length) > 1)  // If > 1 an event has occured ie
			{					// motion has been detected
				if (stream.interleave_ptr >= stream.server_snap2.length)
				{	// Snapshot server_reply2 to ensure a clean interleave cycle
					stream.server_snap2 = stream.server_reply2;
					stream.interleave_ptr = 1;  // End of an interleave cycle
					stream.interleave_lock = false;
					window.setTimeout("stream_video()", 1);
					return;
				}
				else
				{	
					var feed = stream.server_snap2[stream.interleave_ptr];
					stream.interleave_ptr++;
					var not_viewable = true;  // Check that event is viewable
					for (var i=1; i <= (Math.pow(parent_cache.view_format, 2)); i++)
					{	
						if (parent_cache.view_seqs[parent_cache.view_format][i] == feed) 
						{	// If viewable set stream.view	
							stream.view = i;
							not_viewable = false;
							break;
						}
					}
					if (not_viewable)	// If not viewable ...
					{			// restart stream_video()
						window.setTimeout("stream_video()", 1);
						return;
					}
				}
			}
			else 
			{
			stream.interleave_lock = false;  // Flip lock
			window.setTimeout("stream_video()", 1);  // restart stream_video()
			return;
			}
		}
		else  // *** Normal cycle code ***
		{	
			stream.interleave_view++;  // Active normal cycle
			stream.interleave_view = (stream.interleave_view > (Math.pow(parent_cache.view_format, 2)))?1:stream.interleave_view;

			for (var i=0; i<stream.server_reply2.length; i++) // Scan for interleave - normal cycle clash
				if (stream.server_reply2[i] == parent_cache.view_seqs[parent_cache.view_format][stream.interleave_view] && parent_cache.pref_interleave) 
				{
					stream.interleave_lock = true;	// If clash restart stream_video()
					window.setTimeout("stream_video()", 1);
					return;
				}

			stream.view = stream.interleave_view;

			// Dont flag an interleave cycle on 'no video' views else interleave becomes unbalanced
			if (parent_cache.view_seqs[parent_cache.view_format][stream.view] <= parent_cache.video_feeds) 
			{
				stream.interleave_lock = true;
			}
		}

		if (parent_cache.view_seqs[parent_cache.view_format][stream.view]<= parent_cache.video_feeds)  // Don't bother with 'no video' views 
		{
			server_poll();
			window.setTimeout("cache()", 1);
		}
		else
		{
			window.setTimeout("stream_video()", 5);  // Avoids CPU pegging if all images are 'no video'
		}
	}


	function cache()
	{
		stream.preload_count++;  // caching as a browser workaround
		stream.preload_count = (stream.preload_count > 15)?0:stream.preload_count;

		stream.feed = parent_cache.view_seqs[parent_cache.view_format][stream.view];
		var jpeg = stream.server_reply1[stream.feed];
		stream.preload_filename[stream.preload_count] = (jpeg == undefined)?"misc/caching.jpeg":jpeg;

		if (jpeg == "" || jpeg == undefined)
		{
			window.setTimeout("stream_video()", 100);
		}
		else
		{
			stream.preload_jpeg[stream.preload_count].onload = function ()
			{
				set_view_status();
				stream.preload_try_count = 0;		
				for (var i=1; i <= (Math.pow(parent_cache.view_format, 2)); i++) // Scan for views with duplicate feeds
				{
					if (parent_cache.view_seqs[parent_cache.view_format][i] == stream.feed)  // Set .src of all views found
					{
						document.getElementById("image_" + i).src = stream.preload_filename[stream.preload_count];
					}
				}
				stream.loaded = "true";	
			}
	
			stream.preload_jpeg[stream.preload_count].onerror = function ()
			{
				stream.preload_try_count = 0;
				stream.loaded = "error";
			}

			if (stream.view_change == stream.view)  // If stream view is the last view changed, cache it for a cycle
			{
				stream.preload_jpeg[stream.preload_count].src = "misc/caching.jpeg";
				stream.view_change = 0;
				cache_wait();
				return;
			}
			stream.preload_jpeg[stream.preload_count].src = stream.preload_filename[stream.preload_count];
			cache_wait();
		}
	}	


	function cache_wait()
	{
		stream.preload_try_count++;
		if (stream.loaded == "true")
		{
			if (parent_cache.pref_interleave && ((stream.server_reply2.length) > 1))
			{
				stream.loaded = "false";
				var pause = Math.max(parent_cache.pref_motion_pause, 100);
				window.setTimeout("stream_video();", pause);
			}
			else
			{
				stream.loaded = "false";
				var pause = Math.max(parent_cache.pref_normal_pause, 100);
				window.setTimeout("stream_video();", pause);
			}
		}

		else if (stream.loaded == "error")
		{
			stream.preload_try_count = 0;
			stream.loaded = "false";
			window.setTimeout("stream_video();", 1);
		}

		else if (stream.loaded == "false")
		{
			if (stream.preload_try_count < 99)
			{
				window.setTimeout("cache_wait();", 30);
			}
			else
			{
				stream.preload_try_count = 0;
				stream.loaded = "false";
				window.setTimeout("stream_video();", 1);
			}
		}
	}


	//*******************************************************************************************************************************************************************************
	// Setup view code
	//******************************************************************************************************************************************************************************


	function set_view_background()
	{
		for (var i=1; i <= (Math.pow(parent_cache.view_format, 2)); i++)
		{
			if (parent_cache.view_seqs[parent_cache.view_format][i] <= parent_cache.video_feeds)
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
		for (var i=1; i <= (parent_cache.view_format * parent_cache.view_format); i++)
		{
			if (parent_cache.view_seqs[parent_cache.view_format][i] <= parent_cache.video_feeds)
			{
				document.getElementById("text_"+i).innerHTML = parent_cache.view_seqs[parent_cache.view_format][i] + " : " + parent.state.feed_text[parent_cache.view_seqs[parent_cache.view_format][i]];
			}
			else
			{
				document.getElementById("text_"+i).innerHTML = parent_cache.view_seqs[parent_cache.view_format][i] + " : No Video";
			}
		}
	}


	function set_view_status()
	{
		for (var i=1; i <= (parent_cache.view_format * parent_cache.view_format); i++)
		{
			var view = parent_cache.view_seqs[parent_cache.view_format][i];
			{
				var flag = false;
				for (j=0; j < (stream.server_reply2.length); j++)
				{
					if (view == stream.server_reply2[j]) flag = true;
				}
				if (flag) document.getElementById("text_"+i).style.color = "#ff0000";
				else document.getElementById("text_"+i).style.color = "#0000ff";	
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
			
			parent_cache.view_seqs[parent_cache.view_format][view] = stream.last_camera_button;
			view_change = view;

			if (parent_cache.view_seqs[parent_cache.view_format][view] <= parent_cache.video_feeds)
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
			window.setTimeout("parent.frame_standard_callback("+parent_cache.view_seqs[parent_cache.view_format][view]+")", 1);
		}
	}

	
	function parent_button_clicked(button)
	{
		if (parent_cache.view_format == 1)
		{	
			parent_cache.view_seqs[1][1] = button;
			stream.view_change = stream.view;
	
			if (parent_cache.view_seqs[parent_cache.view_format][stream.view] <= parent_cache.video_feeds)
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

	// This is a black art indeed.	
	$scale_width = VIEW_WIDTH;
	$scale_height = VIEW_HEIGHT - (ROW_PADDING * (ROWS + 1));

	// Calculate the jpeg size keeping aspect ratio 384 / 288 = 1.33 
	if (($scale_width / $scale_height) < 1.33)
	{
		$scale = $scale_width / COLS;
		$scale_width = $scale;
		$scale_height =  $scale * 0.75;
	}
	else
	{
		$scale = $scale_height / ROWS;
		$scale_width = $scale * 1.33;
		$scale_height =  $scale;		
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
			$camera_src = "misc/caching.jpeg";

			printf("<img id=\"image_%d\" style=\"position:absolute;top:%dpx;left:%dpx;\"  src=\"%s\" width=%dpx height=%dpx onClick=\"view_clicked(%s);\" alt=\"Camera image\">", $pos, (($row + 1) * ROW_PADDING) + ($row * $scale_height), $lhs_margin + ($col * COL_PADDING) + ($col * $scale_width) , $camera_src,  $scale_width, $scale_height, $pos);

			printf("<span id=\"text_%d\" style=\"position:absolute;top:%dpx;left:%dpx;\">", $pos, (($row + 1) * ROW_PADDING) + ($row * $scale_height)+ 3, $lhs_margin + ($col * COL_PADDING) + ($col * $scale_width) + 3);
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
