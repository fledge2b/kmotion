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


<script language="JavaScript" type="text/javascript" >


	// I spent a long time trying to avoid global variables but due to the need to cross reference
	// from here to the iframe and back it has not been possible so i have implemented a state object.

	state = {
		iframe_width: 0,		// Width of iframe
		iframe_height: 0,		// Height of iframe

		view_format: 4,			// 1, 2, 3, 4 for 1, 4, 9 or 16 way views

		video_feeds: 0,			// Number of avalible video feeds
	
		view_seqs: [],			// Array of feeds for views ... used to convert from view to feeds 
		feed_text: [],			// Array of feed texts

		enable_buttons: true,		// Enable & Disable view format & camera select button functions

		pref_interleave: true,		// Display interleave mode on or off
		pref_mode: 2,			// Preferences mode ie 1 = 'non interleaved' etc ...
		pref_normal_pause: 1,		// Preference pause between sucessive normal frames for bandwidth control
		pref_motion_pause: 1		// Preference pause between sucessive motion frames for bandwidth control
		}

	state.view_seqs[1] = [0, 1];
	state.view_seqs[2] = [0, 1, 2, 3, 4];
	state.view_seqs[3] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
	state.view_seqs[4] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16];

	<?php					// PHP reads in video_feeds & feed_text ...

	$rc = file("./www.rc");			// Read 1st line from feed.rc ... the number of feeds
	$feeds = count($rc) - 1;

	echo "state.video_feeds = $feeds;\n";

	for ($i=1; $i<$feeds + 1; $i++)
	{
		$text = rtrim($rc[$i]);		// Read 2+ line from feed.rc ... feed text
		echo "\tstate.feed_text[$i] = \"$text\";\n";
	}
	?>


	//*******************************************************************************************************************************************************************************
	// Preload code
	//******************************************************************************************************************************************************************************

	function preload_images()
	{
		// View select button preloads
		preload1 = [];
		preload2 = [];
		preload3 = [];
		for (var i=1; i<5; i++) // Start from 1 to sync with view ids
		{
			preload1[i] = new Image();
			preload1[i].src = "view_select/r"+i+".png";
			preload2[i] = new Image();
			preload2[i].src = "view_select/b"+i+".png";
			preload2[i] = new Image();
			preload2[i].src = "view_select/g"+i+".png";
		}

		// Camera select button preloads
		preload4 = [];
		preload5 = [];

		for (var i=1; i<17; i++)  // Start from 1 to sync with camera ids
		{
			preload4[i] = new Image();
			preload4[i].src = "camera_select/r"+i+".png";
			if (i <= state.video_feeds)
			{
				preload5[i]= new Image();
				preload5[i].src = "camera_select/b"+i+".png";
			}
			else
			{
				preload5[i] = new Image();
				preload5[i].src = "camera_select/g"+i+".png";
			}
		}

		// Function button preloads
		preload6 = [];
		preload7 = [];
		for (i=1; i<8; i++) // Start from 1 to sync with function ids
		{
			preload6[i] = new Image();
			preload6[i].src = "function_select/r"+i+".png";
			preload7[i] = new Image();
			preload7[i].src = "function_select/b"+i+".png";
		}

		// Misc preloads
		preload8 = new Image();
		preload8.src = "misc/caching.jpeg";
		preload9 = new Image();
		preload9.src = "misc/no_video.jpeg";
		preload10 = new Image();
		preload10.src = "misc/scanning.png";
	}


	//*******************************************************************************************************************************************************************************
	// View format button code
	//******************************************************************************************************************************************************************************

	function view_button_clicked(view)
	{
		if (state.enable_buttons)
		{
			update_view_buttons(view);
			state.view_format = view;
			if (state.pref_mode == 3)
			{	// Preferences - Full mode
				document.getElementById('view_iframe').src = "full_frame.php?width="+state.iframe_width+"&height="+state.iframe_height+"&grid="+state.view_format;
			}
			else
			{	// Preferences - Must be Interleave or non Interleave mode. Low Bandwidth mode has view buttons disabled
				document.getElementById('view_iframe').src = "standard_frame.php?width="+state.iframe_width+"&height="+state.iframe_height+"&grid="+state.view_format;
			}
		}
	}


	function update_view_buttons(view)
	{
		for (var i=1; i<5; i++)
		{
			if (i == view) 
			{
				document.getElementById("view_"+i).src="view_select/r"+i+".png";
			}
			else 
			{
				document.getElementById("view_"+i).src="view_select/b"+i+".png";
			}
		}
	}


	function enable_view_buttons()
	{
 		update_view_buttons(state.view_format);
	}


	function disable_view_buttons()
	{
		for (i=1; i<5; i++)
		{
			document.getElementById("view_"+i).src="view_select/g"+i+".png";
		}
	}


	//*******************************************************************************************************************************************************************************
	// Camera select button code
	//******************************************************************************************************************************************************************************

	function camera_button_clicked(feed)
	{
		if (state.enable_buttons)
		{
			document.getElementById("camera_"+feed).src="camera_select/r"+feed+".png";
			window.setTimeout("reset_camera_buttons();",200);
			// Call iframe directly when camera button clicked ... If single view & button clicked, iframe will want to
			// change view ... If multi view & camera button clicked, iframe will store value & wait for view to be clicked to
			// change that views value ie feed. In either case iframe needs to know when a camera button is clicked.
			document.getElementById("view_iframe").contentWindow.parent_button_clicked(feed);
		}

	}


	function reset_camera_buttons()
	{
		for (var i=1; i<17; i++)
		{
			if (i <= state.video_feeds)
			{
				document.getElementById("camera_"+i).src="camera_select/b"+i+".png"
			}
			else 
			{
				document.getElementById("camera_"+i).src="camera_select/g"+i+".png"	
			}
		}
	}


	function enable_camera_buttons()
	{
		reset_camera_buttons();
	}


	function disable_camera_buttons()
	{
		for (var i=1; i<17; i++)
		{
			document.getElementById("camera_"+i).src="camera_select/g"+i+".png";
		}
	}


	//*******************************************************************************************************************************************************************************
	// Enable / Disable view format & camera select buttons
	//******************************************************************************************************************************************************************************

	function  enable_buttons()
	{
		state.enable_buttons = true;
 		enable_view_buttons();
		enable_camera_buttons();
	}

	function  disable_buttons()
	{
		state.enable_buttons = false;
		disable_view_buttons();
		disable_camera_buttons();
	}


	//*******************************************************************************************************************************************************************************
	// Function button code
	//******************************************************************************************************************************************************************************

	function update_function_buttons(func)
	{
		for (var i=1; i<8; i++)
		{
			if (i == func)
			{
				document.getElementById("function_"+i).src="function_select/r"+i+".png";
			}
			else
			{
				document.getElementById("function_"+i).src="function_select/b"+i+".png";
			}
		}
	}


	function live_view()
	{
		update_function_buttons(1);
		state.pref_interleave = (state.pref_mode == 2)?true:false;  // Set interleave from pref section

		if (state.pref_mode == 1 || state.pref_mode == 2)  // Non Interleaved & Interleaved mode ... both modes use same standard_frame.php script
		{						   // state.pref_interleave tells the script what to do
			enable_buttons();
			document.getElementById('view_iframe').src = "standard_frame.php?width="+state.iframe_width+"&height="+state.iframe_height+"&grid="+state.view_format;
		}
		else if (state.pref_mode == 3)  // Full mode ...
		{
			enable_buttons();
			document.getElementById('view_iframe').src = "full_frame.php?width="+state.iframe_width+"&height="+state.iframe_height+"&grid="+state.view_format;
		}
		else if (state.pref_mode == 4)  // Low Bandwidth mode ...
		{
			disable_buttons();
			document.getElementById('view_iframe').src = "lowbw_frame.php?width="+state.iframe_width+"&height="+state.iframe_height;
		}
	}


	function archive_view()
	{		
		update_function_buttons(2);
		disable_buttons();
		document.getElementById('view_iframe').src="archive_frame.php?width="+state.iframe_width+"&height="+state.iframe_height;
	}


	function preferences()
	{
		update_function_buttons(3);
		disable_buttons();
		document.getElementById('view_iframe').src = "pref_frame.php";
	}


	function logout()
	{
		update_function_buttons(4);
		disable_buttons();
		if (window.confirm('   Please confirm you wish to Logout')) 
		{
			window.location='about:blank';
			alert('For security reasons please close your browser now');
		}
		else 
		{
			live_view();
		}
	}


	function server_stats()
	{
		update_function_buttons(5);
		disable_buttons();
		document.getElementById('view_iframe').src = "stats_frame.php?width="+state.iframe_width+"&height="+state.iframe_height;
	}


	function about_kmotion()
	{
		update_function_buttons(6);
		disable_buttons();
		document.getElementById('view_iframe').src='about_frame.php';
	}


	function gpl_licence()
	{
		update_function_buttons(7);
		disable_buttons();
		document.getElementById('view_iframe').src='http://www.gnu.org/copyleft/gpl.html';
	}


	//*******************************************************************************************************************************************************************************
	// Callbacks from iframe code
	//******************************************************************************************************************************************************************************


	// If someone clicks a view in standard_frame.php, display that view full screen, do it with a callback to avoid possible recursion &
	// to set the view format buttons
	function frame_standard_callback(feed)
	{
		state.view_seqs[1][1] = feed;
		view_button_clicked(1);
	}


	// If motion is detected in full_frame.php, display full_inter_frame.php, do it with a callback to avoid possible recursion & 
	// to disable view format & camera select buttons 
	function frame_full_callback()
	{
		disable_buttons();
		document.getElementById('view_iframe').src="full_full_frame.php?width="+state.iframe_width+"&height="+state.iframe_height;
	}


	// If motion has ceased in full_inter_frame.php, display full_frame.php, do it with a callback to avoid possible recursion &
	// to enable view format & camera select buttons 
	function frame_full_callback2()
	{
		enable_buttons();
		document.getElementById('view_iframe').src="full_frame.php?width="+state.iframe_width+"&height="+state.iframe_height+"&grid="+state.view_format;
	}


	// If someone changes the date in archive_frame.php, regenerate the frame to avoid recursion problems
	function frame_archive_callback(date_dir, feed_dir)
	{
		document.getElementById('view_iframe').src="archive_frame.php?width="+state.iframe_width+"&height="+state.iframe_height+"&date_dir="+date_dir+"&feed_dir="+feed_dir;	
	}

	// Auto update of server stats
	function frame_stats_callback()
	{
		document.getElementById('view_iframe').src = "stats_frame.php?width="+state.iframe_width+"&height="+state.iframe_height;
	}




</script>
</head>

<body id="viewer" onload="preload_images();">

	<div id="top_banner">
	<!-- Rotated following lines to work around Firefox bug http://www.thescripts.com/forum/thread587691.html -->
	<img style="vertical-align:middle;float:right" src="misc/apache.png" alt="powered by apache logo">
	<img src="misc/kmotion.png" alt="kmotion logo">	
	</div>

	<div id="main_panel">
	<!-- 	
	Back to the stone age without proper CSS support for iframes  
	It should be easy but is a nightmare because of IE being buggy, It does not accept
	height as a %, it has to be a px value
	-->
		<iframe id="view_iframe" name="view_iframe" src="" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto"></iframe>
	</div>

	<script language="JavaScript" type="text/javascript" >

		// Black magic to get the size of the browser window
		if( typeof( window.innerWidth ) == 'number' ) 
		{
			//Non-IE
			state.iframe_width = window.innerWidth;
			state.iframe_height = window.innerHeight;
		} 
		else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) 
		{
			//IE 6+ in 'standards compliant mode'
			state.iframe_width = document.documentElement.clientWidth;
			state.iframe_height = document.documentElement.clientHeight;
		} 
		else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) 
		{
			//IE 4 compatible
			state.iframe_width = document.body.clientWidth;
			state.iframe_height = document.body.clientHeight;
		}	
		state.iframe_width -= 169;
		state.iframe_height -= 66;
	
		// Because IE is crap !
		document.getElementById('view_iframe').height = state.iframe_height;

	</script>
	<div id="control_panel">
		
		<!-- Setup view format buttons 1,4,9 or 16 way -->
		<span class="headings">View&nbsp;Format</span><br>
		
		<img id="view_1" src="view_select/g1.png" onClick="view_button_clicked(1);" alt="view 1 camera">
		<img id="view_2" src="view_select/g2.png" onClick="view_button_clicked(2);" alt="view 4 cameras">
		<img id="view_3" src="view_select/g3.png" onClick="view_button_clicked(3);" alt="view 9 cameras">
		<img id="view_4" src="view_select/g4.png" onClick="view_button_clicked(4);" alt="view 16 cameras">
	 
		<!-- Setup camera select buttons -->
		<span class="headings">Camera&nbsp;Select</span><br>
		<span>
		<img id="camera_1" src="camera_select/g1.png" onClick="camera_button_clicked(1);" alt="select camera 1">
		<img id="camera_2" src="camera_select/g2.png" onClick="camera_button_clicked(2);" alt="select camera 2">
		<img id="camera_3" src="camera_select/g3.png" onClick="camera_button_clicked(3);" alt="select camera 3">
		<img id="camera_4" src="camera_select/g4.png" onClick="camera_button_clicked(4);" alt="select camera 4">

		<img id="camera_5" src="camera_select/g5.png" onClick="camera_button_clicked(5);" alt="select camera 5">
		<img id="camera_6" src="camera_select/g6.png" onClick="camera_button_clicked(6);" alt="select camera 6">
		<img id="camera_7" src="camera_select/g7.png" onClick="camera_button_clicked(7);" alt="select camera 7">
		<img id="camera_8" src="camera_select/g8.png" onClick="camera_button_clicked(8);" alt="select camera 8">

		<img id="camera_9" src="camera_select/g9.png" onClick="camera_button_clicked(9);" alt="select camera 9">
		<img id="camera_10" src="camera_select/g10.png" onClick="camera_button_clicked(10);" alt="select camera 10">
		<img id="camera_11" src="camera_select/g11.png" onClick="camera_button_clicked(11);" alt="select camera 11">
		<img id="camera_12" src="camera_select/g12.png" onClick="camera_button_clicked(12);" alt="select camera 12">

		<img id="camera_13" src="camera_select/g13.png" onClick="camera_button_clicked(13);" alt="select camera 13">
		<img id="camera_14" src="camera_select/g14.png" onClick="camera_button_clicked(14);" alt="select camera 14">
		<img id="camera_15" src="camera_select/g15.png" onClick="camera_button_clicked(15);" alt="select camera 15">
		<img id="camera_16" src="camera_select/g16.png" onClick="camera_button_clicked(16);" alt="select camera 16">
		</span>

		<!-- Setup key functions buttons -->
		<span class="headings">Key&nbsp;Functions</span>
		<span>
		<img id="function_1" src="function_select/b1.png" onClick="live_view();" alt="live view button">
		<img id="function_2" src="function_select/b2.png" onClick="archive_view();" alt="archive view button">
		<img id="function_3" src="function_select/b3.png" onClick="preferences();" alt="preferences button">
		<img id="function_4" src="function_select/b4.png" onClick="logout();" alt="logout button">
		</span>

		<!-- Setup misc functions buttons -->
		<span class="headings"> Misc&nbsp;Functions</span>
		<span>
		<img id="function_5" src="function_select/b5.png" onClick="server_stats();" alt="server stats button">
		<img id="function_6" src="function_select/b6.png" onClick="about_kmotion();" alt="about kmotion button">
		<img id="function_7" src="function_select/b7.png" onClick="gpl_licence();" alt="GPL Licence button">
		</span>
		
	</div>
</body>


<script language="JavaScript" type="text/javascript">
live_view();
</script>







