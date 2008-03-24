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

	// The archive frame ...

	// The variables. Due to Javascript not having a sleep() function window.setTimeout() is used. This causes problems
	// when trying to pass parameters resulting in a relatively high number of 'global' variables.  A closure construct is
	// unworkable with window.setTimeout() causing a recursion error.

	
	archive = {
		selected_date: "",			// Selected date string
		selected_feed: 0,			// Selected feed number
		frame_skipping: false			// if frames are being skipped due to selected replay rate
		};

	image = {
		is_event: true,				// true = event, false = snapshot
		loaded: false,				// is image loaded
		jpeg_secs: 0,				// seconds - either file (if snap) or dir (if event)
		jpeg_frame: 0,				// frame pointer if event
		max_frames: 0				// when in event mode max frames per second
		};

	dbase = {
		event_start: [],			// event start: num of seconds and number of frames
		event_start_frames: [],			// part of event dbase

		event_finish: [],			// event finish: num of seconds and number of frames
		event_finish_frames: [],		// part of event dbase

		event_frames: [],			// event number of frames mid sequence

		snap: [],				// snapshot: num of seconds at start 
		snap_delay: [],				// snapshot: number of seconds delay

		populated: false			// dbase populated flag
		};

	tline = {		
		events: [],				// Array of block numbers holding tline events ... cached for speed
		snap: [],				// Array of block numbers holding tline snapshots ... cached for speed
		marker: 0				// The current tline block
		};
	
	nexus = {
		start_ms: 0,				// the start time of the image load in ms
		image_try_count:0,			// frame retry count
		populate_try_count:0,			// populate dbase try count
		//skip_frames: false,			// true if skipping frames while FF or REW ing
		play_mode: true,			// true = playing, false = pause
		play_forward: true,			// true = play forward, false = play rewind
		play_accelerator: 0,			// how much play acceleration
		accelerator_index: [1, 2, 5, 10]	// non linear play acceleration
		}

	stream = {
		cache_jpeg: [],				// Caching jpeg array to avoid flicker
		cache_num: 0				// Count into above array
		};

	stream.cache_jpeg[0] = new Image();
	stream.cache_jpeg[1] = new Image();
	stream.cache_jpeg[2] = new Image();
	stream.cache_jpeg[3] = new Image();

	//*******************************************************************************************************************************************************************************
	// The nexus thanks to javascript ... the broken language ...
	//******************************************************************************************************************************************************************************


	function archive_nexus()
	{
		document.getElementById("image_1").src = "misc/indexing.jpeg";
		nexus.populate_try_count = 0;
		archive.selected_date = selected_date();
		archive.selected_feed = selected_feed();
		dbase_populate(archive.selected_date + "/" + zero_fill(archive.selected_feed));
		dbase_wait();
	}
	

	// Wait for dbase to be populated ... 
	function dbase_wait()
	{
		nexus.populate_try_count++;
		if (dbase.populated)
		{
			init();
		}
		else
		{
			if (nexus.populate_try_count < 100)
			{
				window.setTimeout("dbase_wait()", 100);
			}
			else  // a fallback if the on event in dbase_populate() hangs ... 
			{
				window.setTimeout("archive_nexus()", 1);
			}
		}
	}


	function init()
	{
		if (first_image())
		{
			document.getElementById("image_1").src = "misc/caching.jpeg";
			tline_populate();
			tline_marker(parseInt(image.jpeg_secs / 300));
			update_status_bar();
			update_buttons();

			var time_now = new Date();
			nexus.start_ms = time_now.getTime();
			image.loaded = false;
			display_frame(fq_filename());
			window.setTimeout("forward_image_wait();", 1);
		}
		else
		{
			// If no images ... exit ...
			document.getElementById("image_1").src = "misc/no_video.jpeg";
		}
	}


	//*******************************************************************************************************************************************************************************
	// Play forward
	//******************************************************************************************************************************************************************************


	function play_forward()
	{
		if (nearest_next_image())
		{	
			nexus.image_try_count = 0;
			var time_now = new Date();
			nexus.start_ms = time_now.getTime();
			image.loaded = false;
			display_frame(fq_filename());
			forward_image_wait();
		}
		else
		{
			nexus.play_mode = false;  // If no more images ... exit ...
			update_buttons();
		}
	}


	function forward_image_wait()
	{
		if (!nexus.play_mode || !nexus.play_forward)  // if in pause mode, quit immediately
		{
			return;
		}

		nexus.image_try_count++;
		if (image.loaded)
		{
			nexus.image_try_count = 0;
			tline_marker(parseInt(image.jpeg_secs / 300));

			image.loaded = false;
			var time_now = new Date();
			var now_ms = time_now.getTime();
			var delay_ms = now_ms - nexus.start_ms;  // image load time

			if (image.is_event)  // event play acceleration
			{
				var frame_ms = 1000 / image.max_frames;
				var event_limit_ms = frame_ms / nexus.accelerator_index[nexus.play_accelerator];
				if (event_limit_ms > delay_ms || nexus.accelerator_index[nexus.play_accelerator] == 1)  // never skip if playing x1
				{
					//nexus.skip_frames = false;
					window.setTimeout("play_forward();", event_limit_ms - delay_ms);
				}
				else  // if images can not be downloaded fast enough - skip images ...
				{
					for (var i = 0; i < Math.min((delay_ms / event_limit_ms), image.max_frames); i++)
					{
						nearest_next_image();
						if (!image.is_event)  // in case of overrun from event to snapshot
						{
							window.setTimeout("play_forward();", 1);
							return;
						}
					} 
					//nexus.skip_frames = true;
					window.setTimeout("play_forward();", 1);
				}	
			}
			else  // snap play acceleration
			{
				var snap_limit_ms = 1000 / nexus.accelerator_index[nexus.play_accelerator];
				if (snap_limit_ms > delay_ms || nexus.accelerator_index[nexus.play_accelerator] == 1)  // never skip if playing x1
				{
					//nexus.skip_frames = false;
					window.setTimeout("play_forward();", snap_limit_ms - delay_ms);
				}
				else  // if images can not be downloaded fast enough - skip images ...
				{			
					for (var i = dbase.snap.length - 1; i >= 0; i--)  // find the snapshot delay seconds
					{
						if (dbase.snap[i] <= image.jpeg_secs)
						{
							var delay_secs = dbase.snap_delay[i];
						}
					}
					var max_skip = 300 / delay_secs
					for (var i = 0; i < Math.min((delay_ms / snap_limit_ms), max_skip); i++)
					{
						nearest_next_image();
						if (image.is_event)  // in case of overrun from snapshot to event
						{
							
							window.setTimeout("play_forward();", 1);
							return;
						}
					} 
					//nexus.skip_frames = true;
					window.setTimeout("play_forward();", 1);
				}	
			}
		}
		else  // image not loaded yet ... slow connection ...
		{
			if (nexus.image_try_count < 33)
			{
				window.setTimeout("forward_image_wait();", 30);
			}
			else	// a fallback if the on event in display_frame() hangs ... 
			{
				display_frame(fq_filename());
				nexus.image_try_count = 0;
				window.setTimeout("forward_image_wait();", 1);
			}
		}
	}


	//*******************************************************************************************************************************************************************************
	// Play reverse
	//******************************************************************************************************************************************************************************


	function play_reverse()
	{
		if (nearest_prev_image())
		{	
			nexus.image_try_count = 0;
			var time_now = new Date();
			nexus.start_ms = time_now.getTime();
			image.loaded = false;
			display_frame(fq_filename());
			reverse_image_wait();
		}
		else
		{
			nexus.play_mode = false;  // If no more images ... exit ...
			update_buttons();
		}
	}

	function reverse_image_wait()
	{
		if (!nexus.play_mode || nexus.play_forward)  // if in pause mode, quit immediately
		{
			return;
		}

		nexus.image_try_count++;
		if (image.loaded)
		{
			nexus.image_try_count = 0;
			tline_marker(parseInt(image.jpeg_secs / 300));

			image.loaded = false;
			var time_now = new Date();
			var now_ms = time_now.getTime();
			var delay_ms = now_ms - nexus.start_ms;  // image load time

			if (image.is_event)  // event play acceleration
			{
				var frame_ms = 1000 / image.max_frames;
				var event_limit_ms = frame_ms / nexus.accelerator_index[nexus.play_accelerator];
				if (event_limit_ms > delay_ms || nexus.accelerator_index[nexus.play_accelerator] == 1)  // never skip if playing x1
				{
					//nexus.skip_frames = false;
					window.setTimeout("play_reverse();", event_limit_ms - delay_ms);
				}
				else  // if images can not be downloaded fast enough - skip images ...
				{
					for (var i = 0; i < Math.min((delay_ms / event_limit_ms), image.max_frames); i++)
					{
						nearest_prev_image();
						if (!image.is_event)  // in case of overrun from event to snapshot
						{
							window.setTimeout("play_reverse();", 1);
							return;
						}
					} 
					//nexus.skip_frames = true;
					window.setTimeout("play_reverse();", 1);
				}	
			}
			else  // snap play acceleration
			{
				var snap_limit_ms = 1000 / nexus.accelerator_index[nexus.play_accelerator];
				if (snap_limit_ms > delay_ms || nexus.accelerator_index[nexus.play_accelerator] == 1)  // never skip if playing x1
				{
					//nexus.skip_frames = false;
					window.setTimeout("play_reverse();", snap_limit_ms - delay_ms);
				}
				else  // if images can not be downloaded fast enough - skip images ...
				{
					for (var i = dbase.snap.length - 1; i >= 0; i--)  // find the snapshot delay seconds
					{
						if (dbase.snap[i] <= image.jpeg_secs)
						{
							var delay_secs = dbase.snap_delay[i];
						}
					}
					var max_skip = 300 / delay_secs
					for (var i = 0; i < Math.min((delay_ms / snap_limit_ms), max_skip); i++)
					{
						nearest_prev_image();
						if (image.is_event)  // in case of overrun from snapshot to event
						{
							window.setTimeout("play_reverse();", 1);
							return;
						}
					} 
					//nexus.skip_frames = true;
					window.setTimeout("play_reverse();", 1);
				}	
			}
		}
		else  // image not loaded yet ... slow connection ...
		{
			if (nexus.image_try_count < 33)
			{
				window.setTimeout("reverse_image_wait();", 30);
			}
			else	// a fallback if the on event in display_frame() hangs ... 
			{
				display_frame(fq_filename());
				nexus.image_try_count = 0;
				window.setTimeout("reverse_image_wait();", 1);
			}
		}
	}


	//*******************************************************************************************************************************************************************************
	// Load & display a frame
	//******************************************************************************************************************************************************************************


	function display_frame(jpeg_file)
	{
		stream.cache_num++;  // caching as a browser workaround
		stream.cache_num = (stream.cache_num > 3)?0:stream.cache_num;

		stream.cache_jpeg[stream.cache_num].onload = function ()
		{
			document.getElementById("image_1").src = stream.cache_jpeg[stream.cache_num].src;
			update_status_bar();
			image.loaded = true;  // signal flag as onload operates in an asynchronous manor
		}

		stream.cache_jpeg[stream.cache_num].onerror = function ()
		{
			image.loaded = true;  // signal flag as onerror operates in an asynchronous manor 
		}
		stream.cache_jpeg[stream.cache_num].src = jpeg_file;
	}


	//*******************************************************************************************************************************************************************************
	// Populate dbase
	//******************************************************************************************************************************************************************************


	function dbase_populate(archive_dir)
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
		xmlHttpReq.open("POST", "archive_list.php?archive_dir=" + archive_dir, true);
		xmlHttpReq.send(null);

		xmlHttpReq.onreadystatechange = function() 
		{
			if (xmlHttpReq.readyState == 4)
			{
				dbase_fill(xmlHttpReq.responseText);
			}
		}
	}

	function dbase_fill(data_blob)
	{
		var reply_split = data_blob.split("#");
		for (var i=1; i < reply_split.length; i++)
		{
			if (reply_split[i].charAt(0) == "s")
			{
				var reply = reply_split[i].substr(1);  // Remove the starting "s" character
				var reply2 = reply.split("$");
				dbase.snap.push(time_secs(reply2[0]));
				dbase.snap_delay.push(parseInt(reply2[1]));
			}	
			else  // If its not an 's', must be a 'e'
			{
				var reply = reply_split[i].substr(1);  // Remove the starting "e" character
				var reply2 = reply.split("$");
				if (reply.charAt(0) == "s")  // 'es'
				{
					dbase.event_start.push(time_secs(reply2[0].substr(1)));
					dbase.event_start_frames.push(parseInt(reply2[1]));
				}
				else if (reply.charAt(0) == "f")  // 'ef'
				{
					dbase.event_finish.push(time_secs(reply2[0].substr(1)));
					dbase.event_finish_frames.push(parseInt(reply2[1]));
				}
				else  // 'ec'
				{
					dbase.event_frames.push(parseInt(reply2[0].substr(1)));
				}
			}
		}
		dbase.populated = true;
	}


	//*******************************************************************************************************************************************************************************
	// Changes in dropdown boxes
	//******************************************************************************************************************************************************************************


	function date_changed()
	{
		parent.frame_archive_callback(selected_date(), selected_feed());
	}


	function feed_changed()
	{
		parent.frame_archive_callback(selected_date(), selected_feed());
	}


	//*******************************************************************************************************************************************************************************
	// Read the selected feed data from the drop down list
	//******************************************************************************************************************************************************************************


	function selected_date()
	{
		var ptr = document.forms[0].date.selectedIndex;
		var date_long = document.forms[0].date.options[ptr].text;
		var yyyy = date_long.substr(0, 4);
		var mm = date_long.substr(5, 2);
		var dd = date_long.substr(8, 2);
		return yyyy + mm + dd;
	}


	function selected_feed()
	{
		var feed = (document.forms[0].feed.selectedIndex) + 1;
		return feed;
	}


	//*******************************************************************************************************************************************************************************
	// Status bar
	//******************************************************************************************************************************************************************************


	function update_status_bar()
	{
		var time = secs_time(image.jpeg_secs);
		var hh = time.substr(0, 2);
		var mm = time.substr(2, 2);
		var ss = time.substr(4, 2);
		var ftime = hh + ":" + mm + ":" + ss;

/*
		if (nexus.skip_frames)
		{
			document.getElementById("status_text").innerHTML = ftime + " *** WARNING Skipping Frames ***"; 
			document.getElementById("status_background").style.background = "#ffff00";
		}
		else 
*/
		if (image.is_event)
		{
			document.getElementById("status_text").innerHTML = ftime + " Displaying Event"; 
			document.getElementById("status_background").style.background = "#ff0000";
		}
		else
		{
			document.getElementById("status_text").innerHTML = ftime + " Displaying Snapshot"; 
			document.getElementById("status_background").style.background = "#0000ff";
		}
	}
	

	//*******************************************************************************************************************************************************************************
	// Button code
	//******************************************************************************************************************************************************************************


	function play_pause_clicked()
	{
		if (nexus.play_mode)
		{
			nexus.play_mode = false;
			update_buttons();
			//nexus.skip_frames = false;
			update_status_bar();
		}
		else
		{
			nexus.play_mode = true;
			nexus.play_forward = true;
			nexus.play_accelerator = 0;  // reset the accelerater to 0 ie x1, to avoid repeated hitting of end of timeline & switching to pause mode
			update_buttons();
			if (nexus.play_forward)
			{
				play_forward();
			}
			else
			{
				play_reverse();
			}
		}
	}


	function plus_clicked()  // sorts '+frame', '>' & acceleration - calling the appropriate code
	{
		if (nexus.play_mode)
		{
			if (nexus.play_forward)
			{
				nexus.play_accelerator++;
				nexus.play_accelerator = Math.min(3, nexus.play_accelerator);
			}
			else
			{
				if (nexus.play_accelerator > 0)
				{
					nexus.play_accelerator--;
				}
				else
				{
					nexus.play_forward = true;
					play_forward_clicked();
				}

			}
			update_buttons();
		}
		else
		{
			plus_one_clicked();
		}
	}


	function minus_clicked()  // sorts '-frame', '<' & acceleration - calling the appropriate code
	{		
		if (nexus.play_mode)
		{
			if (!nexus.play_forward)
			{
				nexus.play_accelerator++;
				nexus.play_accelerator = Math.min(3, nexus.play_accelerator);
			}
			else
			{
				if (nexus.play_accelerator > 0)
				{
					nexus.play_accelerator--;
				}
				else
				{
					nexus.play_forward = false;
					play_reverse_clicked();
				}

			}
			update_buttons();
		}
		else
		{
			minus_one_clicked();
		}
	}


	function play_forward_clicked()
	{
		play_forward();
	}


	function play_reverse_clicked()
	{
		play_reverse();
	}
	

	function plus_one_clicked()
	{
		if (nearest_next_image())
		{
			tline_marker(parseInt(image.jpeg_secs / 300));
			display_frame(fq_filename());
		}
		else
		{
			return;
		}
	}


	function minus_one_clicked()
	{
		if (nearest_prev_image())
		{
			tline_marker(parseInt(image.jpeg_secs / 300));
			display_frame(fq_filename());
		}
		else
		{
			return;
		}
	}


	function next_event_block()
	{
		for (var dbase_ptr = 0; dbase_ptr < dbase.event_start.length; dbase_ptr++)
		{
			if (dbase.event_start[dbase_ptr] > image.jpeg_secs)
			{
				image.is_event = true;
				image.jpeg_secs = dbase.event_start[dbase_ptr];
				image.jpeg_frame = dbase.event_frames[dbase_ptr] - dbase.event_start_frames[dbase_ptr];
				image.max_frames = dbase.event_frames[dbase_ptr];
				tline_marker(parseInt(image.jpeg_secs / 300));
				display_frame(fq_filename());
				break;
			}
		}
	}


	function prev_event_block()
	{
		for (var dbase_ptr = dbase.event_start.length - 1; dbase_ptr >= 0; dbase_ptr--)
		{
			if (dbase.event_finish[dbase_ptr] < image.jpeg_secs)
			{
				image.is_event = true;
				image.jpeg_secs = dbase.event_start[dbase_ptr];
				image.jpeg_frame = dbase.event_frames[dbase_ptr] - dbase.event_start_frames[dbase_ptr];
				image.max_frames = dbase.event_frames[dbase_ptr];
				tline_marker(parseInt(image.jpeg_secs / 300));
				display_frame(fq_filename());
				break;
			}
		}
	}


	function update_buttons()
	{
		if (nexus.play_mode)
		{
			document.getElementById("play_pause_button").value = "Click to Pause";

			if (nexus.play_forward)
			{
				var arrow = "";
				for (var i = 0; i <= nexus.play_accelerator; i++)
				{
					arrow = arrow + ">";
				}
				document.getElementById("plus_one_button").style.color = "#ff0000";
				document.getElementById("plus_one_button").value = arrow;
				document.getElementById("minus_one_button").style.color = "#000000";
				document.getElementById("minus_one_button").value = "<";
			}
			else
			{
				var arrow = "";
				for (var i = 0; i <= nexus.play_accelerator; i++)
				{
					arrow = arrow + "<";
				}
				document.getElementById("plus_one_button").style.color = "#000000";
				document.getElementById("plus_one_button").value = ">";
				document.getElementById("minus_one_button").style.color = "#ff0000";
				document.getElementById("minus_one_button").value = arrow;
			}
		}
		else
		{
			document.getElementById("play_pause_button").value = "Click to Play";
			document.getElementById("plus_one_button").style.color = "#000000";
			document.getElementById("plus_one_button").value = "+ frame";
			document.getElementById("minus_one_button").style.color = "#000000";
			document.getElementById("minus_one_button").value = "- frame";
		}
	}


	//*******************************************************************************************************************************************************************************
	// First image
	//******************************************************************************************************************************************************************************


	function first_image()  // used to pick the first event as a starting point, if no events, picks the first snapshot 
	{
		var event_secs = dbase.event_start[0];
		var snap_secs = dbase.snap[0];

		if (event_secs != undefined)
		{
			image.is_event = true;
			image.jpeg_secs = event_secs;
			image.jpeg_frame = dbase.event_frames[0] - dbase.event_start_frames[0];
			image.max_frames = dbase.event_frames[0];
			return true;
		}
		else if (snap_secs != undefined)
		{
			image.is_event = false;
			image.jpeg_secs = snap_secs;
			return true;
		}
		else return false;
	}


	//*******************************************************************************************************************************************************************************
	// Next image
	//******************************************************************************************************************************************************************************


	function nearest_next_image()
	{
		var event = next_event_frame();  // check to see if currently viewing an event
		var event_valid = event[0], event_secs = event[1], event_frame = event[2], event_ptr = event[3];
		if (event_valid) // viewing an event
		{
			image.is_event = true;
			image.jpeg_secs = event_secs;
			image.jpeg_frame = event_frame;
			image.max_frames = dbase.event_frames[event_ptr];
			return true;
		}
		else  // not viewing an event ... check for nearest next event & snap 
		{
			var event = next_event();
			var event_valid = event[0], event_secs = event[1], event_frame = event[2], event_ptr = event[3];
			var event = next_snap();
			var snap_valid = event[0], snap_secs = event[1];

			if (!event_valid && !snap_valid)  // no valid previous image
			{
				return false;
			}

			if (!event_valid)  // no valid next event
			{
				image.is_event = false;
				image.jpeg_secs = snap_secs;
				return true;
			}

			if (!snap_valid)  // no valid next snap
			{
				image.is_event = true;
				image.jpeg_secs = event_secs;
				image.jpeg_frame = event_frame;
				image.max_frames = dbase.event_frames[event_ptr];
				return true;
			}

			if (event_secs <= snap_secs)  // decide which to use
			{
				image.is_event = true;
				image.jpeg_secs = event_secs;
				image.jpeg_frame = event_frame;
				image.max_frames = dbase.event_frames[event_ptr];
				return true;
			}
			else
			{
				image.is_event = false;
				image.jpeg_secs = snap_secs;
				return true;
			}
		}
	}	


	function next_event_frame()
	{
		// quick check to see if image object is within event database
		var dbase_ptr = -1;
		for (var i = 0; i < dbase.event_start.length; i++)
		{
			if (image.jpeg_secs > dbase.event_start[i] && image.jpeg_secs < dbase.event_finish[i])
			{
				dbase_ptr = i;
			}
			else if (image.jpeg_secs == dbase.event_start[i] && image.jpeg_frame >= (dbase.event_frames[i] - dbase.event_start_frames[i]))
			{
				dbase_ptr = i;
			}
			else if (image.jpeg_secs == dbase.event_finish[i] && image.jpeg_frame < (dbase.event_finish_frames[i] - 1))
			{
				dbase_ptr = i;
			}
		}
		if (dbase_ptr != -1)
		{
			var jpeg_secs = image.jpeg_secs;
			var jpeg_frame = image.jpeg_frame;
			jpeg_frame++;
			if (jpeg_frame >= dbase.event_frames[dbase_ptr])
			{
				jpeg_frame = 0;
				jpeg_secs++;
			}
			return [true, jpeg_secs, jpeg_frame, dbase_ptr];
		}
		else
		{
			return [false, 0, 0, 0];
		}
	}


	function next_event()
	{
		for (var i = 0; i < dbase.event_start.length; i++)
		{
			if (dbase.event_start[i] > image.jpeg_secs)
			{
				return [true, dbase.event_start[i], dbase.event_frames[i] - dbase.event_start_frames[i], i];
			}
		}
		return [false, 0, 0, 0];
	}


	function next_snap()
	{
		for (var i = dbase.snap.length - 1; i >= 0; i--)
		{
			if (dbase.snap[i] <= image.jpeg_secs && dbase.snap_delay[i] != 0)
			{
				var strip = image.jpeg_secs - dbase.snap[i];
				var ratio = strip / dbase.snap_delay[i];
				ratio = parseInt(ratio) + 1;
				ratio = Math.max(0, ratio);
				var calc_secs = dbase.snap[i] + ratio * dbase.snap_delay[i];
				if (is_valid_secs(calc_secs))
				{
					return [true, calc_secs];
				}
			}
		}
		return [false, 0];
	}


	//*******************************************************************************************************************************************************************************
	// Previous image
	//******************************************************************************************************************************************************************************


	function nearest_prev_image()
	{	
		var event = prev_event_frame();  // check to see if currently viewing an event
		var event_valid = event[0], event_secs = event[1], event_frame = event[2], event_ptr = event[3];
		if (event_valid)  // viewing an event
		{
			image.is_event = true;
			image.jpeg_secs = event_secs;
			image.jpeg_frame = event_frame;
			image.max_frames = dbase.event_frames[event_ptr];
			return true;
		}
		else  // not viewing an event ... check for nearest next event & snap 
		{
			var event = prev_event();
			var event_valid = event[0], event_secs = event[1], event_frame = event[2], event_ptr = event[3];
			var event = prev_snap();
			var snap_valid = event[0], snap_secs = event[1];
			if (!event_valid && !snap_valid)  // no valid previous image
			{
				return false;
			}

			if (!event_valid)  // no valid previous event
			{
				image.is_event = false;
				image.jpeg_secs = snap_secs;
				return true;
			}

			if (!snap_valid)  // no valid previous snap
			{
				image.is_event = true;
				image.jpeg_secs = event_secs;
				image.jpeg_frame = event_frame;
				image.max_frames = dbase.event_frames[event_ptr];
				return true;
			}

			if (event_secs >= snap_secs)  // decide which to use
			{
				image.is_event = true;
				image.jpeg_secs = event_secs;
				image.jpeg_frame = event_frame;
				image.max_frames = dbase.event_frames[event_ptr];
				return true;
			}
			else
			{
				image.is_event = false;
				image.jpeg_secs = snap_secs;
				return true;
			}
		}
	}	


	function prev_event_frame()
	{
		// quick check to see if image object is within event database
		var dbase_ptr = -1;
		for (var i = 0; i < dbase.event_start.length; i++)
		{
			if (image.jpeg_secs > dbase.event_start[i] && image.jpeg_secs < dbase.event_finish[i])
			{
				dbase_ptr = i;
			}
			else if (image.jpeg_secs == dbase.event_start[i] && image.jpeg_frame > (dbase.event_frames[i] - dbase.event_start_frames[i] + 1))
			{
				dbase_ptr = i;
			}
			else if (image.jpeg_secs == dbase.event_finish[i] && image.jpeg_frame <= (dbase.event_finish_frames[i] - 1))
			{
				dbase_ptr = i;
			}
		}

		if (dbase_ptr != -1)
		{
			var jpeg_secs = image.jpeg_secs;
			var jpeg_frame = image.jpeg_frame;
			jpeg_frame--;
			if (jpeg_frame < 0)
			{
				jpeg_frame = dbase.event_frames[dbase_ptr] - 1;
				jpeg_secs--;
			}
			return [true, jpeg_secs, jpeg_frame, dbase_ptr];
		}
		else
		{
			return [false, 0, 0, 0];
		}
	}


	function prev_event()
	{
		for (var i = dbase.event_start.length - 1; i >= 0; i--)
		{
			if (dbase.event_finish[i] < image.jpeg_secs)
			{
				return [true, dbase.event_finish[i], dbase.event_finish_frames[i] - 1, i];
			}
		}
		return [false, 0, 0, 0];
	}


	function prev_snap()
	{
		for (var i = dbase.snap.length - 1; i >= 0; i--)
		{
			if (dbase.snap[i] < image.jpeg_secs && dbase.snap_delay[i] != 0)
			{
				var strip = image.jpeg_secs - dbase.snap[i];
				var ratio = strip / dbase.snap_delay[i];
				if (parseInt(ratio) == ratio)
				{
					ratio = parseInt(ratio) - 1;
				}
				else
				{
					ratio = parseInt(ratio);
				}
				ratio = Math.max(0, ratio);
				var calc_secs = dbase.snap[i] + ratio * dbase.snap_delay[i];
				if (is_valid_secs(calc_secs))
				{
					return [true, calc_secs];
				}
			}
		}
		return [false, 0];
	}


	//*******************************************************************************************************************************************************************************
	// Timeline
	//******************************************************************************************************************************************************************************


	function tline_populate()
	{
		var snap_end = 86400 + 1;
		for (var i = dbase.snap.length - 1; i >= 0; i--)  // snapshots
		{
			if (dbase.snap_delay[i] != 0)  // watch out for infinate loop
			{
				for (var snap = dbase.snap[i]; snap < snap_end; snap += dbase.snap_delay[i])
				{
					if (is_valid_secs(snap))
					{
						var tblock = parseInt(snap / 300);	// 300 = (24 * 60 * 60) / 288
						tline.snap.push(tblock);
						document.getElementById("tline_" + tblock).src = "misc/tline_snap.png";
					}
				}
				snap_end = dbase.snap[i];
			}
		}
	
		for (var tblock = 0; tblock < 288; tblock++)  // events
		{
			var secs_start = tblock * 5 * 60;
			var secs_finish = secs_start + 5 * 60 - 1;
			for (var i = 0; i < dbase.event_start.length; i++)
			{
				if (dbase.event_start[i] >= secs_start &&  dbase.event_finish[i] <= secs_finish)
				{
					tline.events.push(tblock);
					document.getElementById("tline_" + tblock).src = "misc/tline_event.png";
					break;
				}
			}
		}
		// This code is only here till I can find somewhere better to put it ! It could have been coded in PHP
		// but I need to keep the demands on the server as light as possible ....
		for (var tblock=0; tblock < 288; tblock++)		// time popups
		{
			var timeslot = secs_timeslot(tblock * 5 * 60);
			document.getElementById("tline_" + tblock).title = timeslot;

		}
	}


	function secs_timeslot(secs)
	{
		var hours = int_div(secs, (60 * 60));
		secs = secs - (hours * (60 * 60));
		var mins = int_div(secs, 60);
		return zero_fill(hours) + ":" + zero_fill(mins);
	}


	function tline_marker(tblock)
	{
		if (tblock != tline.marker)
		{
			if (tline.events.inArray(tline.marker))
			{
				document.getElementById("tline_" + tline.marker).src = "misc/tline_event.png";
			}
			else if (tline.snap.inArray(tline.marker))
			{
				document.getElementById("tline_" + tline.marker).src = "misc/tline_snap.png";
			}
			else
			{
				document.getElementById("tline_" + tline.marker).src = "misc/tline_blank.png";
			}
			document.getElementById("tline_" + tblock).src = "misc/tline_marker.png";
			tline.marker = tblock;
		}
	}


	function tline_clicked(tblock)
	{
		var is_event = image.is_event;
		var jpeg_secs = image.jpeg_secs;
		var jpeg_frame = image.jpeg_frame;

		image.is_event = true;
		image.jpeg_secs = tblock * 5 * 60;
		image.jpeg_frame = 0;

		if (nearest_next_image())
		{
			tline_marker(parseInt(image.jpeg_secs / 300));
			display_frame(fq_filename());
		}
		else  // if there is no next image, do nothing ...
		{
			image.is_event = is_event;
			image.jpeg_secs = jpeg_secs;
			image.jpeg_frame = jpeg_frame;
		}
	}


	//*******************************************************************************************************************************************************************************
	// Misc functions
	//******************************************************************************************************************************************************************************


	function time_secs(time)
	{
		var hours = time.substr(0, 2);
		var mins = time.substr(2, 2);
		var secs = time.substr(4, 2);
		return ((hours * 60 * 60) + (mins * 60) + parseInt(secs));
	}


	function secs_time(secs)
	{
		var hours = int_div(secs, (60 * 60));
		secs = secs - (hours * (60 * 60));
		var mins = int_div(secs, 60);
		secs = secs - (mins * 60);
		return zero_fill(hours) + zero_fill(mins) + zero_fill(secs);
	}


	function zero_fill(str)
	{
		str = str.toString();
		if (str.length == 1)
		{
			str = "0" + str;
		}
		return str;
	}


	function int_div(num, denom)
	{
 		var remainder = num % denom;
    		var quotient = (num - remainder) / denom;
		return quotient;
	}


	function fq_filename()  // return a fully qualified path & filename from image object
	{
		var pathnfile = "";
		if (image.is_event)	// event
		{
			pathnfile = "/images/" + archive.selected_date + "/" + zero_fill(archive.selected_feed) + "/video/" + secs_time(image.jpeg_secs) + "/" + zero_fill(image.jpeg_frame) + ".jpg";
		}			
		else			// snap
		{
			pathnfile = "/images/" + archive.selected_date + "/" + zero_fill(archive.selected_feed) + "/video/" + secs_time(image.jpeg_secs) + ".jpg";
		}
		return pathnfile;
	}


	// code to check for invalid snapshot entries. An invalid entry would be anything after 86399 ... ie 24 * 60 * 60 or
	// entries that are after 'now' - which can happen as an unfortunate side effect of calculating entries
	function is_valid_secs(secs)
	{
		if (secs > 86399)  // check for end of 24 hour period
		{
			return false;
		}
		var now = new Date();
		var yyyy = now.getFullYear();
		var mm = zero_fill(now.getMonth() + 1);
		var dd = zero_fill(now.getDate());
		var real_date = yyyy + mm + dd;

		if (real_date != archive.selected_date)  // if its not 'today' it won't be after 'now'
		{
			return true;
		}
		else
		{
			var tline_time = secs_time(secs);

			var hh = zero_fill(now.getHours());
			var mm = zero_fill(now.getMinutes());
			var real_time = hh + mm;

			if (tline_time < real_time)  // check to see if its after 'now'
			{
				return true;
			} 
		}
		return false;
	}


	// Returns true if the passed value is found in the
	// array.  Returns false if it is not.
	Array.prototype.inArray = function (value)
	{
    		var i;
    		for (i=0; i < this.length; i++)
		{
        		// Matches identical (===), not just similar (==).
        		if (this[i] === value)
			{
            			return true;
        		}
    		}
    		return false;
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

	$date_dir = $_GET['date_dir'];
	$feed_dir = $_GET['feed_dir'];

	// This is a black art, px never quite add up so may need to tweak +7	
	$scale_width = VIEW_WIDTH;
	$scale_height = VIEW_HEIGHT - ((ROW_PADDING * 2) + 7) - 75;

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


	// Date selection dropdown
	$date_dirs = Array();
	$rc = file("./www.rc");
	
	$path = rtrim($rc[0]);
	$dir = dir($path);
	while (false !== ($entry = $dir->read())) 
	{
		if (substr($entry, 0, 2) == "20")
		{
			$date_dirs[] = $entry;
		}
	}
	$dir->close();
	sort($date_dirs, SORT_NUMERIC);

	printf("<div style=\"position:absolute;top:%dpx;left:%dpx\">", ROW_PADDING, $lhs_margin);
	printf("<form action=\"#\">");
	printf("<select id=\"date\" name=\"date\" onChange=\"date_changed();\" style=\"width:105px\">");
	for ($i=sizeof($date_dirs) - 1; $i >= 0; $i--)
	{	
		$yy = substr($date_dirs[$i], 0, 4);
		$mm = substr($date_dirs[$i], 4, 2);
		$dd = substr($date_dirs[$i], 6, 2);
		printf("<option");
		if ($date_dirs[$i] == $date_dir)
		{
			printf(" selected");  // the selected date
		}
		printf(">%s-%s-%s</option>", $yy, $mm, $dd);
	}		
	printf("</select>");

	// Feed selection dropdown
	// if $date_dir == "", page was not called with a ?date_dir= therefore auto select
	if ($date_dir == "")
	{
		$date_dir = $date_dirs[sizeof($date_dirs) - 1];
	}

	$feed_dirs = Array();
	$dir = dir($path."/".$date_dir);
	while (false !== ($entry = $dir->read())) 
	{
		if ($entry != "." and $entry != ".." and $entry != 'size')
		{
			$feed_dirs[] = $entry;
		}
	}
	$dir->close();
	sort($feed_dirs, SORT_NUMERIC);

	$rc = file("./www.rc");
	printf("<select id=\"feed\" name=\"feed\" onChange=\"feed_changed()\" style=\"width:160px\">");
	for ($i=0; $i < sizeof($feed_dirs); $i++)
	{	
		printf("<option");
		if ($feed_dir == $feed_dirs[$i])
		{
			printf(" selected");  // the selcted feed
		}
		printf(">%s : %s</option>", $feed_dirs[$i], $rc[(int)$feed_dirs[$i]]);
	}		
	printf("</select>");
	printf("</form>");
	printf("</div>");

	// Status bar
	printf("<div id=\"status_background\" style=\"position:absolute;top:%dpx;left:%dpx;height:%dpx;width:%dpx;background-color:#%s;\">", ROW_PADDING, $lhs_margin + 275, 20, $scale_width - 275, "c1c1c1");
	printf("</div>");

	printf("<div id=\"status_text\" style=\"position:absolute;top:%dpx;left:%dpx;color:#%s\">", ROW_PADDING, $lhs_margin + 280, "000000");
	printf("</div>");

	// The jpeg ...
	for ($row = 0; $row < ROWS; $row++)
	{
		for ($col = 0; $col < COLS; $col++)
		{
			$pos = ($col + 1) + ($row * COLS);
			$camera_src = "misc/indexing.jpeg";

			printf("<img id=\"image_%d\" style=\"position:absolute;top:%dpx;left:%dpx;\" src=\"%s\" width=%dpx height=%dpx title=\"Right click the mouse and select 'Save image as'.\" onClick=\"view_clicked(%s);\" alt=\"\">", $pos, (($row + 1) * ROW_PADDING) + ($row * $scale_height) + 22, $lhs_margin + ($col * COL_PADDING) + ($col * $scale_width) , $camera_src,  $scale_width, $scale_height, $pos);
		}
	}

	// The control buttons
	$button_width = (int)($scale_width / 5);
	$total_width = $button_width * 5;
	$margin_offset = ($scale_width - $total_width) / 2;
	printf("<div style=\"position:absolute;top:%dpx;left:%dpx\">", (2 * ROW_PADDING) + $scale_height + 22, $lhs_margin + $margin_offset);
	printf("<form action=\"#\">");
	printf("<input type=\"button\" name=\"minus_event\" value=\"- event\"  onClick=\"prev_event_block();\" style=\"width: %dpx\">", $button_width);
	printf("<input id=\"minus_one_button\" type=\"button\" name=\"minus_1\" value=\"<\" onClick=\"minus_clicked();\" style=\"width: %dpx\">", $button_width);
	printf("<input id=\"play_pause_button\" type=\"button\" name=\"play_pause\" value=\"Click to Pause\" onClick=\"play_pause_clicked();\" style=\"width: %dpx\">", $button_width);
	printf("<input id=\"plus_one_button\" type=\"button\" name=\"plus_1\" value=\">\" onClick=\"plus_clicked();\" style=\"width: %dpx\">", $button_width);
	printf("<input type=\"button\" name=\"plus_event\" value=\"+ event\" onClick=\"next_event_block();\" style=\"width: %dpx\">", $button_width);
	printf("</form>");
	printf("</div>");

	// The time line
	printf("<div style=\"position:absolute;top:%dpx;left:%dpx\">", (3 * ROW_PADDING) + $scale_height + 44, $lhs_margin);
	$tline_scale = $scale_width / 288;
	for ($tline_offset = 0; $tline_offset < 288; $tline_offset++)
	{	
		// Code to get around fractional px positioning & width
		$px_left = round($tline_offset * $tline_scale);
		$px_width = round(($tline_offset + 1) * $tline_scale - $px_left);
		printf("<img id=\"tline_%d\" style=\"position:absolute;top:%dpx;left:%dpx;\" src=\"misc/tline_blank.png\" title=\"\" width=%dpx height=%dpx onClick=\"tline_clicked(%d)\" alt=\"timeline\">", $tline_offset, 0, $px_left, $px_width, 30, $tline_offset);
	}
	printf("</div>");
	?>


<script language="JavaScript" type="text/javascript" >
archive_nexus();
</script>





