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

# start daemons !

import os, sys
import daemon_whip, kmotion_logger

"""
Start the daemons on bootup, cannot just call daemon_whip directly because of circular dependencies
"""
        
class daemons_start:
    
    def __init__(self):
        self.motion_config_dir, log_level = self.read_config()
        self.logger = kmotion_logger.Logger('daemons_start', log_level)
        
    
    def find_motion_conf(self):
        """
        look for motion.conf in the optional arguments, cwd, /etc/motion/, ~/.motion/, /usr/local/etc/
        If found set motion_config if not log & sys.exit().
        """
        cwd = os.getcwd() + '/motion.conf'
        home = os.path.expanduser( '~/.motion/motion.conf')
        etc = '/etc/motion/motion.conf'
        usr =  '/usr/local/etc/motion.conf'
        
        if  len(sys.argv) == 2  and sys.argv[1].split('/')[-1:][0] == 'motion.conf' and os.path.isfile(sys.argv[1]): 
            motion_config = sys.argv[1]
        elif os.path.isfile(cwd): 
            motion_config = cwd
        elif os.path.isfile(home): 
            motion_config = home
        elif os.path.isfile(etc): 
            motion_config = etc
        elif os.path.isfile(usr): 
            motion_config = usr
        else:
            self.logger.log('Could not find motion_configuration file in <arguments>, ./motion.conf, /etc/motion/motion.conf, ~/.motion/motion.conf or /usr/local/etc/motion.conf', 'CRIT')
            sys.exit()
        self.logger.log('Found config file %s' % (motion_config), 'NOTICE')
        return motion_config
        
        
    def parse_motion_config(self):
        f = open(self.motion_config, 'r')
        
        
        
        
        
        
        
        
        
    
    def read_config(self):
        """ 
        read config from ./daemon.rc 
        """
        parser = self.motion_configParser.Safeself.motion_configParser()  
        parsed = parser.read('./daemon.rc')
        try:   
            motion_config_dir = parser.get('misc', 'motion_self.motion_config_dir')
            log_level = parser.get('misc', 'log_level')
        except:
            self.logger.log('Corrupt self.motion_config error : %s - Killing motion & all daemon processes' % sys.exc_info()[1], 'CRIT')
            daemon_whip.kill_daemons()
            sys.exit()
        return motion_config_dir, log_level
            
            
      
     
    
    
    #how do I expand home ?
    
    
    # ok how do I pass parameters ?
    find_motion_conf()
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    daemon_whip.start_daemons()

