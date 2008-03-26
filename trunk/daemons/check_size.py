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

import os

"""
?????????????????????????????????????????????????????????
size ............... av_dir_size, av_file_size, total_size
"""

parser = ConfigParser.SafeConfigParser()
parsed = parser.read('./daemon.rc')
log_level = parser.get('debug', 'log_level')
logger = logger.Logger('check_size', log_level)

class Check_Size:
    
    def __init__(self):
        self.date_dir = ''
    
    def check_size(self, ):
        self.date = time.strftime('%Y%m%d') 
        # check & create date dir just in case kmotion_hkd1 crosses 00:00 before motion
        if self.prev_date != self.date:  
            self.date_dir = '%s/%s' % (self.images_dir, date)
            if not(os.path.isdir(self.date_dir)): os.makedirs(date_dir)
            self.prev_date = date
    
    
    
    
    def read_av_dir_size(self, thread):
        """
        Reads todays average directory size, if 'av_dir_size' file returns 0
        """
        if os.path.isfile('%s/%s' % (self.date_dir, thread)):
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    def read_config(self):
        """ 
        Read config from daemon.rc 
        """
        parser = ConfigParser.SafeConfigParser()  
        parsed = parser.read('./daemon.rc')
        
        self.images_dir = parser.get('dirs', 'images_dir')
        self.daemons_dir = parser.get('dirs', 'daemons_dir')
        self.misc_config_dir = parser.get('dirs', 'misc_config_dir')
        self.size_gb = int(parser.get('storage', 'size_gb')) * 2**30  # 2**30 = 1GB
        
    
    def signal_hup(self, signum, frame):
        """
        On SIGHUP re-read the config file 
        """
        logger.log('Signal SIGHUP detected, re-reading config file', 'DEBUG')
        self.read_config()
    
    
    
    
