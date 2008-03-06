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
        self.blog = """
#######################################################################################
# kmotion compulsory options ...
#######################################################################################
\n"""
        self.motion_config_dir, log_level = self.read_config()
        self.logger = kmotion_logger.Logger('daemons_start', log_level)
        
        motion_config = find_motion_conf()
        threads, snapshot_interval = self.parse_motion_config(motion_config)
        
    
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
            self.logger.log('Could not find config file in <arg>, ./motion.conf, /etc/motion/motion.conf, ~/.motion/motion.conf or /usr/local/etc/motion.conf - exiting', 'CRIT')
            sys.exit()
        self.logger.log('Found config file %s' % (motion_config), 'NOTICE')
        return motion_config
                
                
    def parse_motion_config(self, motion_config):
        """
        parse motion.conf, check for blacklist options, global snapshot_intervals and threads. returns
        a list of thread files & any snapshot_intervals
        """
        blacklist = ['jpeg_filename', 'snapshot_filename', 'on_event_start', 'on_event_end', 'on_picture_save']
        snapshot_interval = 0
        threads = []  # list of motion.conf thread file names
        f = open(motion_config, 'r')
        lines = f.readlines()
        f.close()
        
        f = open('%s/motion.conf' % self.motion_config_dir, 'w')
        for line in lines:
            line_split = line.split()
            if line_split[0] in blacklist:
                self.logger.log('Overriding motion.conf line: %s' % (line), 'CRIT')
            elif line_split[0] == 'snapshot_interval' and len(line_split) == 2:
                snapshot_interval = int(line_split[1])
            elif line_split[0] == 'thread'and len(line_split) == 2:
                threads.append(line_split[1])
                f.write('thread motion%s.conf' % (len(threads))) 
            else:
                f.write(line)
        f.write(self.blog)
        f.write('snapshot_interval 1')
        f.close()
        if len(threads):  # kmotion needs motion.conf to use threads
            self.logger.log('motion.conf not configured with threads - exiting', 'CRIT')
            sys.exit()
            
        return threads, snapshot_interval
        
        
    def parse_motionx_config(self, threads, snapshot_interval):
        blacklist = ['jpeg_filename', 'snapshot_filename', 'on_event_start', 'on_event_end', 'on_picture_save']
        feed = 0
        for thread in threads:
            feed = feed + 1
            f = open(thread, 'r')
            lines = f.readlines()
            f.close()
                
            for line in lines:
                line_split = line.split()
                if line_split[0] in blacklist:
                    self.logger.log('Overriding %s line: %s' % (thread, line), 'CRIT')
                elif line_split[0] == 'snapshot_interval' and len(line_split) == 2:
                    snapshot_interval = int(line_split[1])
                else:
                    f.write(line)
                    
        f.write('jpeg_filename %%Y%%m%%d/%0.2d/video/%%H%%M%%S/%%q\n' % (feed))
        f.write('snapshot_filename %%Y%%m%%d/%0.2d/tmp/%%H%%M%%S\n' % (feed))
        f.write('on_event_start /usr/bin/touch %s/events/%d\n' % (self.kmotion_dbase, feed))
        f.write('on_event_end /bin/rm %s/events/%d\n' % (self.kmotion_dbase, feed))
        f.write('on_picture_save echo > %s/%%Y%%m%%d/%0.2d/last_jpeg' % (self.kmotion_dbase, feed))
        f.write(self.blog)
        f.close()
        
        
        
        
        
        
    
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

