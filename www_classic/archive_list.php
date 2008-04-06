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

</head>
<body id="feed_status">
<!--
Returns a coded data blob holding information on the selected video dir data structure. The data blob is coded
to drastically reduce the data size and increase responsiveness.

<hash>s<filename of first snapshot with this config><dollar><seconds till next snapshot> ...
<hash>es<dir name of first dir in seq><dollar><number of jpegs in dir> ...
<hash>ef<dir name of last dir in seq><dollar><number of jpegs in dir> ...
<hash>ec<number of jpegs in 'mid' seq> ...
-->
<?php
	$rc = file("./www.rc");
	$path = rtrim($rc[0]);

	$archive_dir = $path."/".$_GET['archive_dir'];
	#$archive_dir = "/var/lib/kmotion/images/20080308/01/";

	$data = array();

	// Read journal_snap. It consists of data with the format ...
	// #s<filename of first snapshot with this config>$<seconds till next snapshot>
	if (file_exists($archive_dir."/journal_snap"))
	{
		$journal_snap = fopen($archive_dir."/journal_snap", 'r');
		$snap = fread($journal_snap, filesize($archive_dir."/journal_snap"));
		$snap_array = explode("#", $snap);
	
		for ($i=1; $i < sizeof($snap_array); $i++)
		{
			$data[] = "s".$snap_array[$i];
		}
		fclose($journal_snap);
	}

	// Scan $archive_dir and generate a data blob with the format ...
	// #es<dir name of first dir in seq>$<number of jpegs in dir>#ef<dir name of last dir in seq>$<number of jpegs in dir>
	// #ec<number of jpegs in 'mid' seq>. Cannot use motion.conf on_event_start/end due to time delays ...
	$entry_array = array();
	$dir = dir($archive_dir."/video");
	while (false !== ($entry = $dir->read())) 
		{
			if ($entry == "." or $entry == ".." or (substr($entry, -4) == ".jpg"))
			{
				continue;	// Filter the junk ...
			}
			else
			{
				$entry_array[] = $entry;
			}
		}
	$dir->close();
	sort($entry_array, SORT_NUMERIC);

	if (sizeof($entry_array) > 0)
	{
		$data[] = "es".$entry_array[0]."$".count(glob($archive_dir."/video/".$entry_array[0]."/*.jpg"));
	
		for ($i=1; $i < sizeof($entry_array); $i++)
		{
			if ($entry_array[$i] != $entry_array[$i - 1] + 1)
			{
				$data[] = "ef".$entry_array[$i - 1]."$".count(glob($archive_dir."/video/".$entry_array[$i - 1]."/*.jpg"));
				// check three prev *.jpg counts & pick the max count
				$data[] = "ec".max(count(glob($archive_dir."/video/".$entry_array[$i - 2]."/*.jpg")), count(glob($archive_dir."/video/".$entry_array[$i - 3]."/*.jpg")), count(glob($archive_dir."/video/".$entry_array[$i - 4]."/*.jpg")));
				$data[] = "es".$entry_array[$i]."$".count(glob($archive_dir."/video/".$entry_array[$i]."/*.jpg"));
			}
		}
	
		$data[] = "ef".$entry_array[(sizeof($entry_array) - 1)]."$".count(glob($archive_dir."/video/".$entry_array[(sizeof($entry_array) - 1)]."/*.jpg"));

		$data[] = "ec".count(glob($archive_dir."/video/".$entry_array[(sizeof($entry_array) - 2)]."/*.jpg"));
	}

	for ($i=0; $i < sizeof($data); $i++)
	{
		echo "#".$data[$i];
	}


?>