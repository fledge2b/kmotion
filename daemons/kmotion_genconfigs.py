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

# kmotion generate configs

import sys, os, ConfigParser, daemon_whip, kmotion_logger

""" 
generates motion.conf, motion.?.conf & feed.rc config files from kmotion.rc 

** WARNING ** if kmotion_genconfigs cannot access certain sections of kmotion.rc 
it kills all daemons in protest :)
"""

class Kmotion_Genconfigs:
    
    def __init__(self):
        self.config_header_blog = """
#######################################################################################
# This motion.?.conf file has been automatically generated by kmotion. To change this 
# file modify kmotion.rc and run daemon_manual.py select 'reload daemon config'. If you
# manually change this file you risk corrupting kmotion. DO NOT CHANGE THIS FILE !!
#######################################################################################
"""
        self.config_user_blog = """
#######################################################################################
# User options ...
#######################################################################################
\n"""
        self.config_compulsory_blog = """
#######################################################################################
# Compulsory options ...
#######################################################################################
\n"""
        self.config_threads_blog = """
#######################################################################################
# Threads ...
#######################################################################################
\n"""
        
        self.banned_options = ['snapshot_interval', 'jpeg_filename', 'snapshot_filename', 'on_event_start', 'on_event_end', 'on_picture_save', 'live', 'name']
        self.logger = kmotion_logger.Logger('kmotion_genconfigs', 'WARNING')
        self.parser = ConfigParser.SafeConfigParser()
        self.motion_conf_dir = ''
        self.feed_conf_dir = ''
            
            
    def gen_configs(self):
        """ generate all kmotions config files from kmotion.rc """
        try:
            self.parser.read(os.path.expanduser('~/.kde/share/apps/kmotion/kmotion.rc'))
            self.motion_conf_dir = self.parser.get('misc', 'motion_conf_dir')
            self.feed_conf_dir = self.parser.get('misc', 'feed_conf_dir')
        except:
            self.logger.log('Corrupt config error : %s - Killing motion & all daemon processes' % sys.exc_info()[1], 'CRIT')
            daemon_whip.kill_daemons()
            sys.exit()
            
        feeds = 0
        try:
            for i in range(16):
                if self.parser.get('feed%s' % (str(i + 1)), 'live') == "yes" : 
                    feeds = feeds + 1
                else: 
                    raise
        except:
            pass
            
        self.logger.log('Generating motion.conf ...', 'DEBUG')
        self.motion_conf(feeds)
        self.logger.log('Generating motion.?.conf ...', 'DEBUG')
        for feed in range(1, feeds + 1):
            self.motion_n_conf(feed)
        for feed in range(feeds + 1, 17):
            self.motion_n_blank(feed)
        self.logger.log('Generating feed.rc ...', 'DEBUG')
        self.feed_rc(feeds)
        
    
    def motion_conf(self, feeds):
        """ generate the main motion motion.conf file """
        motion_conf = open('%s/motion.conf' % (self.motion_conf_dir), 'w') 
        motion_conf.write(self.config_header_blog)
        motion_conf.write(self.config_user_blog)
        
        items = self.parser.items('feed_common')
        items.sort()
        for item in items:
            if item[0] not in self.banned_options:
                motion_conf.write('%s %s\n' % (item[0], item[1]))
                
        motion_conf.write(self.config_compulsory_blog)
        motion_conf.write('snapshot_interval 1\n')
        motion_conf.write('target_dir /var/lib/motion\n')
    
        motion_conf.write(self.config_threads_blog)
        for i in range(1, feeds + 1):
            motion_conf.write('thread %s/motion.%d.conf\n' % (self.motion_conf_dir, i))
        motion_conf.close()
                
    
    def motion_n_conf(self, feed):
        """ generate the multiple thread motion.?.conf files """
        motion_conf = open('%s/motion.%d.conf' % (self.motion_conf_dir, feed), 'w')
        motion_conf.write(self.config_header_blog)
        motion_conf.write(self.config_user_blog)
        
        items = self.parser.items('feed%d' % (feed))
        items.sort()
        for item in items:
            if item[0] not in self.banned_options:
                motion_conf.write('%s %s\n' % (item[0], item[1]))
                
        motion_conf.write(self.config_compulsory_blog)
        motion_conf.write('jpeg_filename %%Y%%m%%d/%0.2d/video/%%H%%M%%S/%%q\n' % (feed))
        motion_conf.write('snapshot_filename %%Y%%m%%d/%0.2d/tmp/%%H%%M%%S\n' % (feed))
        motion_conf.write('on_event_start /usr/bin/touch /var/lib/motion/events/%d\n' % (feed))
        motion_conf.write('on_event_end /bin/rm /var/lib/motion/events/%d\n' % (feed))
        motion_conf.write('on_picture_save echo > /var/lib/motion/%%Y%%m%%d/%0.2d/last_jpeg' % (feed))
        motion_conf.close()
    
    
    def motion_n_blank(self, feed):
        """ generate the multiple thread motion.?.conf blank files """
        motion_conf = open('%s/motion.%d.conf' % (self.motion_conf_dir, feed), 'w')
        motion_conf.write(self.config_header_blog)
        motion_conf.write(self.config_user_blog)
        motion_conf.write(self.config_compulsory_blog)
        motion_conf.close()
    
    
    def feed_rc(self, feeds):
        """ generate the feed.rc file needed for apaches kmotion PHP scripts """
        feed_rc = open('%s/feed.rc' % (self.feed_conf_dir), 'w')
        feed_rc.write('%s\n' % (feeds))
        
        for feed in range(1, feeds + 1):
            try:
                name = self.parser.get('feed%d' % (feed), 'name')
            except:
                name = 'Camera %d' % (i)  # if not defined, give a default name
            feed_rc.write('%s\n' % (name))
        feed_rc.close()
        
    
    
if __name__ == '__main__':
    Genconfigs = Kmotion_Genconfigs()
    Genconfigs.gen_configs()
    