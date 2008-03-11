<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=us-ascii">
	<link rel="stylesheet" type="text/css" href="base2.css">
	<title>Kmotion</title>

</head>
<body id="feed_status">
<!--
Returns the latest jpeg filenames for all active video feeds plus any feeds identified in the
events directory (ie feeds with motion detection active)

The returned data is in a form that facilitates rapid Javascript string splitting
-->
<?php

	$rc = file("./php.rc");
	$path = rtrim($rc[0]);

	$events = array();  // The events array ...
	$dir = dir($path."/events/");  // Scan the events dir and add events to $events
	while (false !== ($entry = $dir->read())) 
	{
		if ($entry !== "." and $entry !== "..") $events[] = $entry;
	}
	$dir->close();

	sort($events, SORT_NUMERIC);

	$rc = file("./php.rc");  // Read 1st line from feed.rc ... the number of feeds
	$feeds = count($rc) - 1;

	for ($i=1; $i < 17; $i++)
	{
		$jpeg_name = "";
		if ($i <= $feeds)  // Skip jpeg names for feeds that do not exist
		{
			// Retrys reading $jpeg_holder if blank on the first try, possible cause being BASH
			// script is re-writing $jpeg_holder. Avoids occasional white frames on video.
			for ($j=0; $j<3; $j++)
			{
				$jpeg_holder = sprintf($path."/%s/%02d/last_jpeg", date("Ymd"), $i);
				//$jpeg_holder = sprintf("/var/lib/motion/%s/%02d/last_jpeg", date("Ymd"), 1);  // usefull for simulating multiple feeds
				if (file_exists($jpeg_holder))  // Abort of file does not exist
				{
					$jpeg_name = trim(file_get_contents($jpeg_holder));
					# using '/images' as a key breakup the path & re-construct to match apache alias
					$jpeg_name = explode('/images', $jpeg_name);
					$jpeg_name = $jpeg_name[1];
					$jpeg_name = '/images'.$jpeg_name;

					if($jpeg_name !== "") break;
					usleep(10);
				}
				else break;
			}
			echo "#".$jpeg_name;
		}
		else
		{
			echo "#";
		}
	}
	echo "#";

	foreach ($events as $event)
	{
		echo "$".$event;
	}
?>
