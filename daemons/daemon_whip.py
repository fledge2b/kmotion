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

import os, sys, time, ConfigParser
import logger, gen_int_rcs, gen_vhost, gen_kmotion, gen_kmotion_reload

"""
Controls kmotion daemons allowing daemon starting, stopping, checking of status
and configuration reloading
"""

parser = ConfigParser.SafeConfigParser()
parsed = parser.read('./kmotion.rc')
log_level = parser.get('debug', 'log_level')
logger = logger.Logger('daemon_whip', log_level)

 
def start_daemons():
    """ 
    Start kmotion_hkd1, kmotion_hkd2 & motion daemons 
    """ 
    parser = ConfigParser.SafeConfigParser()
    parser.read('./kmotion.rc')
    daemons_dir =  parser.get('dirs', 'daemons_dir')
        
    rcs = gen_int_rcs.Gen_Int_Rcs()  
    rcs.gen_int_rcs() 
    gen_vhost.gen_vhost()
    
    # Only need to start kmotion_hkd1, it starts the rest
    if os.system('ps ax | grep \'kmotion_hkd1.py$\' > /dev/null'): 
        os.system(daemons_dir + '/kmotion_hkd1.py &')
    else: 
        logger.log('start_daemons() - daemons already running - none started', 'DEBUG')
    
    
def kill_daemons():
        """ 
        Stop kmotion_hkd1, kmotion_hkd2 & motion daemons 
        """
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
    """ 
    Returns false if any daemons are not running
    """
    return not os.system('ps ax | grep \'kmotion_hkd1.py$\' > /dev/null') | os.system('ps ax | grep \'kmotion_hkd2.py$\' > /dev/null') | os.system('/bin/ps ax | /bin/grep [m]otion\ -c') 
    #return not os.system('ps ax | grep \'kmotion_hkd1.py$\' > /dev/null')
    
    
def daemon_status():
    """ 
    Returns a list of daemon names as keys and True / False for daemon running
    """
    status = {}
    status['kmotion_hkd1.py'] = not os.system('ps ax | grep \'kmotion_hkd1.py$\' > /dev/null')
    status['kmotion_hkd2.py'] = not os.system('ps ax | grep \'kmotion_hkd2.py$\' > /dev/null')
    status['motion'] = not os.system('/bin/ps ax | /bin/grep [m]otion\ -c')
    return status

    
def config_reload():
    """ 
    Force daemons to reload configs
    """
    rcs = gen_int_rcs.Gen_Int_Rcs()  
    rcs.gen_int_rcs() 
    gen_vhost.gen_vhost()
    os.system('pkill -SIGHUP -f \'python.+kmotion_hkd1.py\'') 
    os.system('pkill -SIGHUP -f \'python.+kmotion_hkd2.py\'')
    os.system('killall -s SIGHUP motion 2> /dev/null')


if __name__ == '__main__':
    start_daemons()

