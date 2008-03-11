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

# parse motion

import os, sys, ConfigParser
import kmotion_logger

"""
Parse motion.conf & threads, generate filterd motion.conf & threads + daemon_rc & www_rc
"""
        
class Parse_Motion:
    
    def __init__(self):  
        self.blacklist = ['jpeg_filename', 'snapshot_filename', 'on_event_start', 'on_event_end', 'on_picture_save', 'target_dir']
        
        
    def parse(self):
        """
        locate & parse motion.conf & its thread files. generate feed.rc and modify daemon.rc as appropreate
        """
        self.read_daemon_rc()
        self.logger = kmotion_logger.Logger('daemon_start', self.log_level)
        motion_config = self.find_motion_conf()
        threads, snapshot_interval = self.parse_motion_conf(motion_config)
        names_list, snapshot_list = self.parse_motionx_conf(threads, snapshot_interval)
        self.write_www_rc(names_list)
        self.write_daemon_rc(snapshot_list)
        
    
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
                
                
    def parse_motion_conf(self, motion_config):
        """
        parse motion.conf, check for blacklist options, global snapshot_intervals and threads. returns
        a list of thread files & any snapshot_intervals
        """
        snapshot_interval = 0
        threads = []  # list of motion.conf thread file names
        f = open(motion_config, 'r')
        lines = f.readlines()
        f.close()
        
        f = open('%s/motion.conf' % self.misc_config_dir, 'w')
        for line in lines:
            line_split = line.split()
            if not len(line_split):  # if [], blank line, skip it
                continue
            elif line_split[0][:1] == '#':  # if #, comment, skip it
                continue
            elif line_split[0] in self.blacklist:
                self.logger.log('Overriding motion.conf line: %s' % (line), 'CRIT')
                continue
            elif line_split[0] == 'snapshot_interval' and len(line_split) == 2:
                snapshot_interval = int(line_split[1])
                continue
            elif line_split[0] == 'thread'and len(line_split) == 2:
                threads.append(line_split[1])
                f.write('thread %s/motion%s.conf\n' % (self.misc_config_dir,  len(threads)))
                continue
            f.write(line)
        f.write('\nsnapshot_interval 1\n')
        f.write('target_dir %s' % (self.images_dir))
        f.close()
        
        if not len(threads):  # kmotion needs motion.conf to use threads
            self.logger.log('motion.conf not configured with threads - exiting', 'CRIT')
            sys.exit()
        return threads, snapshot_interval
        
        
    def parse_motionx_conf(self, threads, snapshot_interval):
        """
        parse all threads pointed to in motion.conf, check for blacklist options, snapshot_intervals, and the #ktext
        operator. returns a #ktext names & snapshot_intervals
        """
        thread_count = 0
        names_list = []       # list of feed names from motionx.conf's
        snapshot_list = []  # list of snapshot values from motiox.conf's. If none specified use snapshot value defined in motion.conf
        
        for thread in threads:
            snapshot = snapshot_interval
            ktext = ''
            thread_count = thread_count + 1
            f = open(thread, 'r')
            lines = f.readlines()
            f.close()
                    
            f = open('%s/motion%d.conf' % (self.misc_config_dir, thread_count), 'w')
            for line in lines:
                line_split = line.split()
                if not len(line_split):  # if [], blank line, skip it
                    continue
                elif line_split[0] == '#ktext' and len(line_split) >= 2:  # keyword for kmotion text
                    ktext = line[7:-1]
                    continue
                elif line_split[0][:1] == '#':  # if #, comment, skip it
                    continue
                elif line_split[0] in self.blacklist:
                    self.logger.log('Overriding motion%d.conf line: %s' % (thread_count, line), 'CRIT')
                    continue
                elif line_split[0] == 'snapshot_interval' and len(line_split) == 2:
                    snapshot = int(line_split[1]) 
                    continue
                f.write(line)
            f.write('jpeg_filename %%Y%%m%%d/%0.2d/video/%%H%%M%%S/%%q\n' % (thread_count))
            f.write('snapshot_filename %%Y%%m%%d/%0.2d/tmp/%%H%%M%%S\n' % (thread_count))
            f.write('on_event_start /usr/bin/touch %s/events/%d\n' % (self.images_dir, thread_count))
            f.write('on_event_end /bin/rm %s/events/%d\n' % (self.images_dir, thread_count))
            f.write('on_picture_save echo > %s/%%Y%%m%%d/%0.2d/last_jpeg' % (self.images_dir, thread_count))
            f.close()
            
            snapshot_list.append(snapshot)
            if ktext == '':
                names_list.append('Default Text')
                self.logger.log('No #ktext specified in motion%d.conf - \'Default Text\' used' % (thread_count), 'CRIT')
            else:
                names_list.append(ktext)
        return names_list, snapshot_list
            
            
    def write_www_rc(self, names_list):
        """
        update php.rc
        """
        f = open('%s/www.rc' % (self.www_dir), 'w')
        f.write('%s\n' % (self.images_dir))
        for name in names_list:
            f.write('%s\n' % (name))
        f.close


    def write_daemon_rc(self, snapshot_list):
        """
        write modified config to ./daemon.rc
        """
        parser = ConfigParser.SafeConfigParser()
        parser.read('./daemon.rc')
        snaps = len(snapshot_list)
        parser.set('feed_count', 'count',  str(snaps))
        for snap in range(snaps):
            parser.set('feed_intervals', 'snapshot_interval%d' % (snap + 1), str(snapshot_list[snap]))
        parser.write(open('./daemon.rc', 'w'))  # when parser writes the file WHAT A HORRIBLE MESS - YUK !
        
        
    def read_daemon_rc(self):
        """ 
        read config from ./daemon.rc 
        """
        parser = ConfigParser.SafeConfigParser()
        parser.read('./daemon.rc')
        self.images_dir = parser.get('dirs', 'images_dir')
        self.misc_config_dir = parser.get('dirs', 'misc_config_dir')
        self.www_dir = parser.get('dirs', 'www_dir')
        self.log_level = parser.get('debug', 'log_level')
        
            
if __name__ == '__main__':
    parse = Parse_Motion()
    parse.parse()
        
