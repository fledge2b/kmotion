<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=us-ascii">
	<link rel="stylesheet" type="text/css" href="base2.css">
	<title>Kmotion</title>

<script language="JavaScript" type="text/javascript">

	// The Preferences frame ...

	bw_convert = [1, 250, 500, 750, 1000, 2000, 3000, 4000, 5000];
	

	function mode_button_clicked(mode)
	{
		parent.state.pref_mode = mode;
	}


	function normal_pause_changed()
	{
		parent.state.pref_normal_pause = bw_convert[document.forms[0].normal_pause.selectedIndex];
	}


	function motion_pause_changed()
	{
		parent.state.pref_motion_pause = bw_convert[document.forms[0].motion_pause.selectedIndex];
	}


	function pref_setup()
	{
		for (var i=0; i<4; i++)
		{
			document.forms[0].radio1[i].checked = ((i + 1) == parent.state.pref_mode);
		}

		for (var i=0; i<9; i++)
		{
			if (parent.state.pref_normal_pause == bw_convert[i])
			{
				document.forms[0].normal_pause.options[i].selected = true;
			}
		}

		for (var i=0; i<9; i++)
		{
			if (parent.state.pref_motion_pause == bw_convert[i])
			{
				document.forms[0].motion_pause.options[i].selected = true;
			}
		}
	}


</script>
</head>

<body id="pref">

<form name="pref_form">
<div class="heading">
Preferences:
</div>

<div class="section_heading text">
View Mode
</div>
<br>

<div style="float:left">
	<input id="mode_1" style="margin-left:36px" type="radio" name="radio1" value="1" onClick="mode_button_clicked(1);">
</div>
<div class="text" style="margin-left:85px">
	<span class="select_heading">Non Interleaved:</span> Images are updated sequentially irrespective of any motion that may be detected.
</div>

<div style="float:left">
	<input id="mode_2" style="margin-left:36px" type="radio" name="radio1" value="2" onClick="mode_button_clicked(2);">
</div>
<div class="text" style="margin-left:85px">
	<span class="select_heading">Interleaved:</span> Images are updated sequentially - images where motion has been detected are given preferential treatment.
</div>

<div style="float:left">
	<input id="mode_3" style="margin-left:36px" type="radio" name="radio1" value="3" onClick="mode_button_clicked(3);">
</div>
<div class="text" style="margin-left:85px">
	<span class="select_heading">Full:</span> Images are updated sequentially - images where motion has been detected are displayed full screen.
</div>

<div style="float:left">
	<input id="mode_4" style="margin-left:36px" type="radio" name="radio1" value="4" onClick="mode_button_clicked(4);">
</div>
<div class="text" style="margin-left:85px">
	<span class="select_heading">Low Bandwidth:</span> No images are displayed - Any images where motion has been detected are displayed full screen.
</div>

<div class="section_heading text">
	Bandwidth Control
</div>
<br>

<div class="text" style="float:left">
	<select id="normal_pause" name="normal_pause" onChange="normal_pause_changed();" style="margin-left:8px">
		<option value="1" selected>None</option>
		<option value="250">0.25s</option>
		<option value="500">0.50s</option>
		<option value="250">0.75s</option>
		<option value="250">1s</option>
		<option value="250">2s</option>
		<option value="250">3s</option>
		<option value="250">4s</option>
		<option value="250">5s</option>
	</select>
</div>

<div class="text" style="margin-left:85px">
	<span class="select_heading">Pause in seconds between frames when no motion has been detected.</span> 'None' will use unlimited bandwidth (Only recommended for LANs)
</div>

<br>
<div class="text" style="float:left">
	<select id="motion_pause" name="motion_pause" onChange="motion_pause_changed();" style="margin-left:8px">
		<option value="1" selected>None</option>
		<option value="250">0.25s</option>
		<option value="500">0.50s</option>
		<option value="750">0.75s</option>
		<option value="1000">1s</option>
		<option value="2000">2s</option>
	</select>
</div>

<div class="text" style="margin-left:85px">
	<span class="select_heading">Pause in seconds between frames when motion has been detected.</span> 'None' will use unlimited bandwidth (Only recommended for LANs)
</div>


<script language="JavaScript" type="text/javascript">
pref_setup();
</script>

</body>

