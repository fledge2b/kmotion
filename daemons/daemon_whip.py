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

# kmotion control daemons

import os, sys, time, ConfigParser
import kmotion_logger, parse_motion

"""
Controls kmotion daemons & reports on their ststus
"""

logger = kmotion_logger.Logger('daemon_whip', 'WARNING')
 
def start_daemons():
    """ Start kmotion_hkd1, kmotion_hkd2 & motion daemons """ 
    parse_motion = parse_motion.Parse_Motion()  
    parse_motion.parse()  # locate & parse motion.conf & its thread files. generate feed.rc and modify daemon.rc as appropreate
    
    parser = ConfigParser.SafeConfigParser()
    parser.read('./kmotion.rc')
    daemons_dir =  parser.get('dirs', 'daemons_dir')
        
    # Only need to start kmotion_hkd1, it starts the rest
    if os.system('ps ax | grep \'kmotion_hkd1.py$\' > /dev/null'): os.system(daemons_dir + '/kmotion_hkd1.py &> /dev/null')
    else: logger.log('start_daemons() - daemons already running - none started', 'DEBUG')
    
def kill_daemons():
        """ Stop kmotion_hkd1, kmotion_hkd2 & motion daemons """
        os.system('pkill -f \'python.+kmotion_hkd1.py\'')
        os.system('pkill -f \'python.+kmotion_hkd2.py\'')
        os.system('killall -q motion')  # Try an initial killall to avoid sleep if possible
        logger.log('kill_daemons() - killing daemons ...', 'DEBUG')
        while  not os.system('ps ax | grep \'[m]otion$\' > /dev/null'):
            time.sleep(1)
            os.system('killall -q motion')
            logger.log('kill_daemons() - motion not killed - retrying ...', 'DEBUG')
        logger.log('kill_daemons() - daemons killed ...', 'DEBUG')
    
def daemons_running():
    """ Return true if daemons are running """
    if os.system('ps ax | grep \'kmotion_hkd1.py$\' > /dev/null'):  return False
    else: return True
    
def config_reload():
    """ Force daemons to reload configs """
    # Only need to SIGHUP kmotion_hkd1, it SIGHUPs kmotion_hkd2
    parse_motion = parse_motion.Parse_Motion()  
    parse_motion.parse() 
    os.system('pkill -SIGHUP -f \'python.+kmotion_hkd1.py\'') 
    os.system('pkill -SIGHUP -f \'python.+kmotion_hkd2.py\'')
    os.system('killall -s SIGHUP motion 2> /dev/null')


if __name__ == '__main__':
    start_daemons()

