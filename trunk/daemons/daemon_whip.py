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

import os, sys, time, ConfigParser, kmotion_genconfigs
import kmotion_logger

"""
Controls kmotion daemons & reports on their ststus
"""

logger = kmotion_logger.Logger('daemon_whip', 'WARNING')
genconfigs = kmotion_genconfigs.Kmotion_Genconfigs()
 
def start_daemons():
    """ Start kmotion_hkd1, kmotion_hkd2 & motion daemons """
    genconfigs.gen_configs()  # generate motion.conf, motion.?.conf & feed.rc from kmotion.rc
    parser = ConfigParser.SafeConfigParser()
    
    try:
        parser.read(os.path.expanduser('~/.kde/share/apps/kmotion/kmotion.rc'))
        lib_dir =  parser.get('misc', 'kmotion_lib_dir')
    except:
        logger.log('Corrupt config error : %s - Killing motion & all daemon processes' % sys.exc_info()[1], 'CRIT')
        kill_daemons()
        sys.exit()
        
    # Only need to start kmotion_hkd1, it starts the rest
    if os.system('ps ax | grep \'kmotion_hkd1.py$\' > /dev/null'): os.system(lib_dir + '/kmotion_hkd1.py &> /dev/null')
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
    genconfigs.gen_configs()
    os.system('pkill -SIGHUP -f \'python.+kmotion_hkd1.py\'') 
    os.system('pkill -SIGHUP -f \'python.+kmotion_hkd2.py\'')
    os.system('killall -s SIGHUP motion 2> /dev/null')


if __name__ == '__main__':
    start_daemons()

