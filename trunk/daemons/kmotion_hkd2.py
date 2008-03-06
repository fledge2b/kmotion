#!/usr/bin/env python

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

# kmotion housekeeping daemon 2

import os, sys, time, signal, shutil, ConfigParser, kmotion_logger, daemon_whip

logger = kmotion_logger.Logger('kmotion_hkd2', 'WARNING')

"""
Copys or moves files from /var/lib/kmotion/kmotion_dbase/<date>/<feed>/tmp to ../video
as defined in kmotion.rc. Updates /var/lib/kmotion/kmotion_dbase/<date>/<feed>/journal_snap
with snapshot information. Finally responds to a SIGHUP by re-reading kmotion.rc.
"""

class Hkd2_Feed:
    
    def __init__(self, feed, kmotion_dbase, snapshot_interval):
        self.feed = feed
        self.kmotion_dbase = kmotion_dbase
        self.snapshot_current = '000000'
        self.snapshot_interval = snapshot_interval
        self.prev_date = '000000'
        
    def run(self):
        """ process the snapshots """
        date = time.strftime('%Y%m%d')
        tmp_dir = '%s/%s/%02i/tmp/' % (self.kmotion_dbase, date, self.feed + 1) 
        video_dir = '%s/%s/%02i/video/' % (self.kmotion_dbase, date, self.feed + 1)
        
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
            
    def update_journal(self, date, feed, seconds, pause):
        """ update the snapshot journal """
        # add to journal of snapshots in the form #<snapshot start seconds>$<snapshot pause in seconds>
        journal = open('%s/%s/%02i/journal_snap' % (self.kmotion_dbase, date, (feed + 1)), 'a')
        journal.write('#%s$%s' % (seconds, pause))
        journal.close()
            
    def inc_time(self, time, inc_secs):
        """ increment a time string of format HHMMSS with inc_secs seconds, returns a time string of the format HHMMSS """
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
        self.sighup_ok = True
        
    def read_config(self):
        """ read the config file, return a list of snapshot_intervals where the lists length represents the number of feeds """
        parser = ConfigParser.SafeConfigParser()
        parsed = parser.read('./daemon.rc')
        
        snapshot_list = []
        try:    
            kmotion_dbase = parser.get('misc', 'kmotion_dbase')
            for i in xrange(16):
                    snapshot_interval = int(parser.get('feed%s' % (str(i + 1)), 'snapshot_interval'))
                    snapshot_list.append(snapshot_interval)
        except:
            pass
        return kmotion_dbase, snapshot_list
    
    def start_daemon(self):
        """" Start the house keeping 2 daemon """
        logger.log('Daemon starting ...', 'CRIT')
        while (True):
            kmotion_dbase, snapshot_list = self.read_config()
            instance = []
            for i in xrange(len(snapshot_list)):
                instance.append(Hkd2_Feed(i, kmotion_dbase, snapshot_list[i]))
            
            self.sighup_ok = True
            while (self.sighup_ok):
                for i in range(len(instance)):
                    instance[i].run()
                time.sleep(2)
        
    def signal_hup(self, signum, frame):
            """ set change self.sighup_ok on SIGHUP """
            logger.log('Signal SIGHUP detected, re-reading config file', 'DEBUG')
            self.sighup_ok = False
            
        
if __name__ == '__main__':
    Hkd2 = Kmotion_Hkd2()
    Hkd2.start_daemon()
    
            
            
 