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

# kmotion house keeping daemon 1

import os, sys, time, signal, shutil,  ConfigParser, daemon_whip
import logger

"""
Checks the % of free disk space & if too low deletes oldest video dirs first. Also
checks that 'motion' & 'kmotion_hkd2.py' are running, restarting them if they are not. 
Finally responds to a SIGHUP by re-reading kmotion.rc.
"""

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
        self.logger = logger.Logger('kmotion_hdk1', self.log_level)
        
        
    def start_daemon(self):    
        """
        Start the house keeping 1 daemon 
        """
        self.logger.log('Daemon starting ...', 'DEBUG')
        # ensure .../events dir exists & is empty
        shutil.rmtree('%s/events' % (self.images_dir), True)
        os.makedirs('%s/events' % (self.images_dir))
        while(True):   
            self.update_total_size()                # for todays images
            sum = self.sum_total_sizes()        # for all images
            if sum > self.size_gb * 0.9:   # if > 90% of size_gb, delete oldest images
                dir = os.listdir(self.images_dir)
                dir.sort()
                self.logger.log('Image storeage limit reached - deleteing %s/%s' %  (self.images_dir, dir[0]), 'CRIT')
                shutil.rmtree('%s/%s' % (self.images_dir, dir[0]))  # delete oldest dir first
            self.chk_motion()
            self.chk_kmotion_hkd2()
            time.sleep(15 * 60)
        
        
    def update_total_size(self):
        """
        Update total_size file for todays images
        """
        date = time.strftime('%Y%m%d') 
        # check & create date dir just in case kmotion_hkd1 crosses 00:00 before kmotion_hkd2
        if self.prev_date != date:  
            date_dir = '%s/%s' % (self.images_dir, date)
            if not(os.path.isdir(date_dir)): os.makedirs(date_dir)
            self.prev_date = date
        
        os.system('nice -n 19 du -c --block-size=1 %s/%s | grep total > /tmp/kmotion_size' % (self.images_dir, date)) 
        f = open('/tmp/kmotion_size', 'r')
        du_op= f.readline()
        f.close()
        
        f = open('%s/%s/total_size' % (self.images_dir, date), 'w')
        f.write(du_op.split()[0])
        f.close()


    def sum_total_sizes(self):
        """
        Return the sum of all total_size files
        """
        sum = 0
        dirs = os.listdir(self.images_dir)
        dirs.sort()
        for date in dirs[:-2]:  # [:-2] to filter off events & last_snap.jpg
            if os.path.isfile('%s/%s/total_size' % (self.images_dir, date)):
                f = open('%s/%s/total_size' % (self.images_dir, date), 'r')
                sum = sum + int(f.readline())
                f.close()
        return sum


    def chk_motion(self):
        """
        Check motion is still running ... if not restart it ... 
        """
        if os.system('/bin/ps ax | /bin/grep [m]otion\ -c'):
           self.logger.log('motion not running - starting motion', 'CRIT')
           os.system('motion -c %s/motion.conf 2> /dev/null &' % (self.misc_config_dir))
            
                
    def chk_kmotion_hkd2(self):
        """
        Check kmotion_hkd2.py is still running ... if not restart it ... 
        """
        if os.system('/bin/ps ax | /bin/grep [k]motion_hkd2.py$'):
           self.logger.log('kmotion_hkd2.py not running - starting kmotion_hkd2.py', 'CRIT')
           os.system('%s/kmotion_hkd2.py 2> /dev/null &' % (self.daemons_dir))


    def read_config(self):
        """ 
        Read config file from ./kmotion.rc 
        """
        parser = ConfigParser.SafeConfigParser()  
        parsed = parser.read('./daemon.rc')
        
        self.images_dir = parser.get('dirs', 'images_dir')
        self.daemons_dir = parser.get('dirs', 'daemons_dir')
        self.misc_config_dir = parser.get('dirs', 'misc_config_dir')
        self.size_gb = int(parser.get('storage', 'size_gb')) * 1000000000
        self.log_level = parser.get('debug', 'log_level')
        
        
    def signal_hup(self, signum, frame):
        """
        Re-read the config file on SIGHUP 
        """
        self.logger.log('Signal SIGHUP detected, re-reading config file', 'DEBUG')
        self.read_config()
        
            
if __name__ == '__main__':
    Hkd1 = Kmotion_Hkd1()
    Hkd1.start_daemon()
    
