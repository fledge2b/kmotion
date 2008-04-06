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

import os, sys, time, signal, shutil,  ConfigParser, daemon_whip
import logger

"""
Checks the size of the images_dir deleteing the oldest directorys first when 90% of max_size_gb is reached.   
Checks that 'motion' & 'kmotion_hkd2.py' are running, restarting them if neccessary. Responds to a 
SIGHUP by re-reading its configuration.
"""

parser = ConfigParser.SafeConfigParser()
parsed = parser.read('./kmotion.rc')
log_level = parser.get('debug', 'log_level')
logger = logger.Logger('kmotion_hkd1', log_level)

class Kmotion_Hkd1:
    
    def __init__(self):
        self.images_dir = ''
        self.daemons_dir = ''
        self.misc_config_dir = ''
        self.file_system = ''
        self.cull_trigpc = 0
        self.prev_date = '000000'
        signal.signal(signal.SIGHUP, self.signal_hup)
        self.read_config()
        
        
    def start_daemon(self):    
        """
        Start the house keeping 1 daemon. This daemon wakes up every 15 minutes
        """
        logger.log('Daemon starting ...', 'DEBUG')
        # ensure .../events directory exists & is empty
        shutil.rmtree('%s/events' % (self.images_dir), True)
        os.makedirs('%s/events' % (self.images_dir))
        while(True):   
            self.chk_motion() 
            self.chk_kmotion_hkd2()
            time.sleep(15 * 60)  # sleep here to allow system to settle after boot
            
            if  self.total_images_size() > self.max_size_gb * 0.9:   # if > 90% of max_size_gb, delete oldest images
                dir = os.listdir(self.images_dir)
                dir.sort()
                if dir[0] == '.svn': dir.pop(0)  # skip '.svn' control directory if present
                logger.log('Image storeage limit reached - deleteing %s/%s' %  (self.images_dir, dir[0]), 'CRIT')
                shutil.rmtree('%s/%s' % (self.images_dir, dir[0]))  # delete oldest dir first
        

    def total_images_size(self):
        """
        Returns the total size of the images directory
        """
        # the following rather elaborate system is designed to lighten the server load. if there are 10's of thousands of
        # files a 'du -h' command will peg the server for several seconds or minutes every 15 mins
        # this code waits for > 50 files or directories then averages their size as a once off. a 'ls | grep | wc' structure 
        # then quickly counts the number of files and directories, multiply by average size and total size is calculated.
        self.update_todays_size()
        total_images_size = 0
        dates = [x for x in os.listdir(self.images_dir) if x != 'events']
        for date in dates:
            date_dir = '%s/%s' % (self.images_dir, date)
            if os.path.isfile('%s/dir_size' % date_dir):
                f = open('%s/dir_size' % date_dir)
                total_images_size = total_images_size + int(f.readline())
                f.close()
        return total_images_size
        
            
    def update_todays_size(self):
        """
        Calculate todays 'images_dir' file size and modify 'dir_size' file
        """
        date = time.strftime('%Y%m%d') 
        date_dir = '%s/%s' % (self.images_dir, date)
        # check & create date dir just in case kmotion_hkd1 crosses 00:00 before motion
        if self.prev_date != date:  
            if not(os.path.isdir(date_dir)): os.makedirs(date_dir)
            self.prev_date = date
        
        dir_size = 0
        feeds = [x for x in os.listdir(date_dir) if os.path.isdir('%s/%s' % (date_dir, x))]  # directories only
        for feed in feeds:
            feed_dir = '%s/%s' % (date_dir, feed)
            dir_size = dir_size + self.av_file_size(feed_dir) * self.file_count(feed_dir)
            dir_size = dir_size + self.av_dir_size(feed_dir) * self.dir_count(feed_dir)
        
        f = open('%s/dir_size' % date_dir, 'w')
        f.write(str(dir_size))
        f.close()
        
        
    def av_file_size(self, feed_dir):
        """
        If 'av_file_size' exists return its value else attempt to calculate its value, create 'av_file_size' and return its value, 
        else if there is insufficient data to calculate 'av_file_size' return 0 
        """
        if os.path.isfile('%s/av_file_size' % feed_dir):  # if there is a update_av_file_size file, average has already been calculated
            f = open('%s/av_file_size' % feed_dir, 'r')
            av_file_size = int(f.readline())
            f.close
        else:
            av_file_size = 0
            files = [x for x in os.listdir('%s/video/' %  feed_dir) if os.path.isfile('%s/video/%s' % (feed_dir, x))]
            if len(files) > 50:
                size = 0
                for file in files:
                    size = size + os.path.getsize('%s/video/%s' % (feed_dir, file))
                av_file_size = size // len(files)
                f = open('%s/av_file_size' % feed_dir, 'w')
                f.write(str(av_file_size))
                f.close
        return av_file_size
            
            
    def file_count(self, feed_dir):
        """
        If an 'av_file_size' file exists calculate and return an updated directory count, else return 0
        """
        # FIXME: if there are 10's of thousands of files a more efficient way to count would be to read
        # in 'journal_snap' file, decode it and calculate the number of files from there. thats a fair bit
        # of code for an unlikely event.
        file_count = 0
        if os.path.isfile('%s/av_file_size' % feed_dir):
            # using BASH directly to count the number of files is the fastest way when they may be
            # in the 10's of thousands. messy but quick
            os.system('nice -n 19 ls -l %s/video | grep .*\.jpg | wc -l > /tmp/kmotion_bash' % feed_dir)
            f = open('/tmp/kmotion_bash', 'r')
            file_count = int(f.readline())
            f.close()
        return file_count
        

    def av_dir_size(self, feed_dir):
        """
        If 'av_dir_size' exists return its value else attempt to calculate its value, create 'av_dir_size' and return its value, 
        else if there is insufficient data to calculate 'av_dir_size' return 0 
        """
        if os.path.isfile('%s/av_dir_size' % feed_dir):  # if there is a update_av_dir_size file, average has already been calculated
            f = open('%s/av_dir_size' % feed_dir, 'r')
            av_dir_size = int(f.readline())
            f.close
        else:
            av_dir_size = 0
            dirs = [x for x in os.listdir('%s/video/' %  feed_dir) if os.path.isdir('%s/video/%s' % (feed_dir, x))]
            if len(dirs) > 50:
                size = 0
                count = 0
                for dir in dirs:
                    count = count + 1
                    for file in os.listdir('%s/video/%s' %  (feed_dir, dir)):
                        size = size + os.path.getsize('%s/video/%s/%s' % (feed_dir, dir, file))
                av_dir_size = size // count
                f = open('%s/av_dir_size' % feed_dir, 'w')
                f.write(str(av_dir_size))
                f.close
        return av_dir_size
         
         
    def dir_count(self, feed_dir):
        """
        If an 'av_dir_size' file exists calculate and return an updated directory count, else return 0
        """
        dir_count = 0
        if os.path.isfile('%s/av_dir_size' % feed_dir):
            # using BASH directly to count the number of directories is the fastest way when they may be
            # in the 10's of thousands. messy but quick
            os.system('nice -n 19 ls -l %s/video | grep -v .*\.jpg | wc -l > /tmp/kmotion_bash' % feed_dir)
            f = open('/tmp/kmotion_bash', 'r')
            dir_count = int(f.readline())
            f.close()
        return dir_count
            
        
    def chk_motion(self):
        """
        Check motion is still running ... if not restart it ... 
        """
        if os.system('/bin/ps ax | /bin/grep [m]otion\ -c'):
           logger.log('motion not running - starting motion', 'CRIT')
           os.system('motion -c %s/motion.conf 2> /dev/null &' % (self.misc_config_dir))
            
                
    def chk_kmotion_hkd2(self):
        """
        Check kmotion_hkd2.py is still running ... if not restart it ... 
        """
        if os.system('/bin/ps ax | /bin/grep [k]motion_hkd2.py$'):
           logger.log('kmotion_hkd2.py not running - starting kmotion_hkd2.py', 'CRIT')
           os.system('%s/kmotion_hkd2.py &' % (self.daemons_dir))


    def read_config(self):
        """ 
        Read config from daemon.rc 
        """
        parser = ConfigParser.SafeConfigParser()  
        parsed = parser.read('./kmotion.rc')
        
        self.images_dir = parser.get('dirs', 'images_dir')
        self.daemons_dir = parser.get('dirs', 'daemons_dir')
        self.misc_config_dir = parser.get('dirs', 'misc_config_dir')
        self.max_size_gb = int(parser.get('storage', 'max_size_gb')) * 2**30  # 2**30 = 1GB
        
        
    def signal_hup(self, signum, frame):
        """
        On SIGHUP re-read the config file 
        """
        logger.log('Signal SIGHUP detected, re-reading config file', 'DEBUG')
        self.read_config()
        
            
if __name__ == '__main__':
    Hkd1 = Kmotion_Hkd1()
    Hkd1.start_daemon()
    
