#!/usr/bin/env python

# Copyright 2008 David Selby dave6502@googlemail.com

# GNU General Public Licence (GPL)
# 
# This program is free software; you can redistribute it and/or modify it under
# the terms of the GNU General Public License as published by the Free Software
# Foundation; either version 2 of the License, or (at your option) any later
# version.
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
# details.
# You should have received a copy of the GNU General Public License along with
# this program; if not, write to the Free Software Foundation, Inc., 59 Temple
# Place, Suite 330, Boston, MA  02111-1307  USA

import os, sys, time, signal, shutil, ConfigParser, logger, daemon_whip

"""
A fairly complex daemon that copys, moves or deletes files from images_dir/.../tmp to images_dir/.../video
as defined in kmotion.rc generating a 'sanitized' snapshot sequence. Updates journal_snap with snapshot 
information, responds to a SIGHUP by re-reading its configuration. Responds to SIGTERM by updateing 
journal_snap with #HHMMSS$0 signifying no more snapshots.
"""

parser = ConfigParser.SafeConfigParser()
parsed = parser.read('./kmotion.rc')
log_level = parser.get('debug', 'log_level')
logger = logger.Logger('kmotion_hkd2', log_level)

class Hkd2_Feed:
    
    def __init__(self, feed, images_dir, snapshot_interval):
        self.feed = feed
        self.images_dir = images_dir
        self.snapshot_current = '000000'
        self.snapshot_interval = snapshot_interval
        self.prev_date = '000000'
        
        
    def run(self):
        """ 
        Copys, moves or deletes files from images_dir/.../tmp to images_dir/.../video as defined in kmotion.rc 
        generating a 'sanitized' snapshot sequence
        """
        date = time.strftime('%Y%m%d')
        tmp_dir = '%s/%s/%02i/tmp/' % (self.images_dir, date, self.feed + 1) 
        video_dir = '%s/%s/%02i/video/' % (self.images_dir, date, self.feed + 1)
        
        # force a journal write on a new day
        if self.prev_date != date:  
            self.snapshot_current = time.strftime('%H%M%S')
            # make dir including intermediate-level directories
            if not(os.path.isdir(tmp_dir)): os.makedirs(tmp_dir)
            if not(os.path.isdir(video_dir)): os.makedirs(video_dir)
            self.update_journal(date, self.feed, self.snapshot_current, self.snapshot_interval)
            self.prev_date = date
    
        jpeg_list = os.listdir(tmp_dir)
        jpeg_list.sort()
        
        while (len(jpeg_list) >= 4):  # need this buffer to ensure motion writes video dirs before we attempt to move jpegs to video dir
        
            jpeg_name = jpeg_list[0][:-4]  # [:-4] to strip '.jpg'
            if  not(self.snapshot_interval):  # if self.snapshot_interval = 0, no snapshots are needed
                os.remove(tmp_dir + jpeg_name + '.jpg')
                jpeg_list = jpeg_list[1:] 
                continue
                
            if os.path.isdir(video_dir + self.snapshot_current):  # if there is a video dir, move to next snapshot_current
                logger.log('Remove snapshot: video clash %s.jpg' % (tmp_dir + jpeg_name), 'DEBUG')
                self.snapshot_current = self.inc_time(self.snapshot_current, self.snapshot_interval)
                continue
                
            if jpeg_name > self.snapshot_current:  # if jpeg is in the future, copy but don't delete
                logger.log('Copy %s.jpg %s.jpg' % (tmp_dir + jpeg_name, video_dir + self.snapshot_current), 'DEBUG')
                shutil.copy(tmp_dir + jpeg_name + '.jpg', video_dir + self.snapshot_current + '.jpg')
                self.snapshot_current = self.inc_time(self.snapshot_current, self.snapshot_interval)
            
            elif jpeg_name == self.snapshot_current:  # if jpeg is now, move ie delete
                logger.log('Move %s.jpg %s.jpg' % (tmp_dir + jpeg_name, video_dir + self.snapshot_current), 'DEBUG')
                os.rename(tmp_dir + jpeg_name + '.jpg', video_dir + self.snapshot_current + '.jpg')
                self.snapshot_current = self.inc_time(self.snapshot_current, self.snapshot_interval)
                jpeg_list = jpeg_list[1:] 
            
            else:  # if jpeg is in the past, delete
                logger.log('Remove snapshot: past timeslot %s.jpg' % (tmp_dir + jpeg_name), 'DEBUG')
                os.remove(tmp_dir + jpeg_name + '.jpg')
                jpeg_list = jpeg_list[1:] 
            
            
    def update_journal_break(self):
        """
        updates journal_snap with #HHMMSS$0 signifying no more snapshots
        """
        self.update_journal(time.strftime('%Y%m%d'), self.feed,  time.strftime('%H%M%S'), 0)
            
            
    def update_journal(self, date, feed, seconds, pause):
        """ 
        Given the date, feed number, seconds and pause in seconds updates journal_snap
        """
        # add to journal of snapshots in the form #<snapshot start seconds>$<snapshot pause in seconds>
        journal = open('%s/%s/%02i/journal_snap' % (self.images_dir, date, (feed + 1)), 'a')
        journal.write('#%s$%s' % (seconds, pause))
        journal.close()
            
            
    def inc_time(self, time, inc_secs):
        """ 
        Given a time string in the format HHMMSS and the seconds to increment, calculates a new time string
        
        Returns a new time string in the format HHMMSS
        """
        hh = int(time[:2])
        mm = int(time[2:4])
        ss = int(time[4:])
        tmp_secs = hh * 60 * 60 + mm * 60 + ss + inc_secs
        hh = tmp_secs // (60 * 60)
        mm = (tmp_secs - hh * 60 * 60) // 60
        ss =  tmp_secs - hh * 60 * 60 - mm * 60
        return '%02i%02i%02i' % (hh, mm, ss)
        
        
class Kmotion_Hkd2:
    
    def __init__(self):
        signal.signal(signal.SIGHUP, self.signal_hup)
        signal.signal(signal.SIGTERM, self.signal_term)  
        self.no_sighup = True
        self.Hk2_feed_instances = []
        
        
    def update_Hk2_feed_instances(self):
        """
        Read the config from daemon.rc and generates a list of Hk2_feed_instances
        """
        parser_kmotion = ConfigParser.SafeConfigParser()
        parsed_kmotion = parser_kmotion.read('./kmotion.rc')
        parser_daemon = ConfigParser.SafeConfigParser()
        parsed_daemon = parser_daemon.read('./daemon.rc')
        
        self.Hk2_feed_instances = []
        images_dir = parser_kmotion.get('dirs', 'images_dir')
        feed_count = int(parser_daemon.get('feed_count', 'count'))
        for i in range(feed_count):
            self.Hk2_feed_instances.append(Hkd2_Feed(i, images_dir, int(parser_daemon.get('feed_intervals', 'snapshot_interval%d' % (i + 1)))))
            
    
    def start_daemon(self):
        """" 
        Start the house keeping 2 daemon. This daemon wakes up every 2 seconds
        """
        logger.log('Daemon starting ...', 'CRIT')
        while True:
            self.no_sighup = True
            self.update_Hk2_feed_instances()
            while self.no_sighup:
                for instance in self.Hk2_feed_instances:
                    instance.run()
                time.sleep(2)
        
        
    def signal_hup(self, signum, frame):
        """ 
        On SIGHUP set self.sighup_ok to False to force a re-read of the config file 
        """
        logger.log('Signal SIGHUP detected, re-reading config file', 'DEBUG')
        self.no_sighup = False
 
 
    def signal_term(self, signum, frame):
        """ 
        On SIGTERM update journal_snap with #HHMMSS$0 signifying no more snapshots
        """
        logger.log('Signal SIGTERM detected, updateing journal with break #HHMMSS$0', 'DEBUG')
        for instance in self.Hk2_feed_instances:
            instance.update_journal_break()
        sys.exit()
 
 
if __name__ == '__main__':
    Hkd2 = Kmotion_Hkd2()
    Hkd2.start_daemon()
    
            
            
 
