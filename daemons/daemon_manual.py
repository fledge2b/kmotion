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

import time, sys, daemon_whip

"""
A crude daemon control program, usefull for kmotion diagnostics
"""

print '\nkmotion manual daemon control ........'
while (True):
    print '\nThe Options ....\n'
    print '\033[1;33ms: Start Daemons\033[1;37m'
    print '\033[1;33mk: Kill Daemons\033[1;37m'
    print '\033[1;33mr: Reload Daemon configs\033[1;37m'
    print '\033[1;33mq: Quit\033[1;37m'
    print '\nENTER: Refresh'
    
    status = daemon_whip.daemon_status()
    if status['kmotion_hkd1.py']:
        text = '\033[1;32mRunning\033[1;37m'
    else:
        text= '\033[1;31mNot running\033[1;37m'
    print '\nkmotion_hkd1.py status : ' + text
    
    if status['kmotion_hkd2.py']:
        text = '\033[1;32mRunning\033[1;37m'
    else:
        text= '\033[1;31mNot running\033[1;37m'
    print 'kmotion_hkd2.py status : ' + text
    
    if status['motion']:
        text = '\033[1;32mRunning\033[1;37m'
    else:
        text= '\033[1;31mNot running\033[1;37m'
    print 'motion status          : ' + text
    print
    
    opt = raw_input('Option letter then ENTER to select : ')

    if (opt == 's'):
        if(daemon_whip.daemons_running()):
            print '\n\033[1;32mDaemons already running ...\033[1;37m'
        else:
            daemon_whip.start_daemons()
            time.sleep(1)
            if(daemon_whip.daemons_running()):
              print '\n\033[1;32mDaemons have been started ...\033[1;37m'
            else:
              print '\n\033[1;31m*WARNING* Unable to start daemons ...\033[1;37m'
              
    elif (opt == 'k'):
        print '\n\033[1;31mStarting to kill daemons ...\033[1;37m'
        daemon_whip.kill_daemons()
        time.sleep(2)
        print '\033[1;31mDaemons have been killed ...\033[1;37m'
    
    elif (opt == 'r'):
        if(daemon_whip.daemons_running()):
            daemon_whip.config_reload()
            print '\n\033[1;33mDaemons config reloaded ...\033[1;37m'
        else:
            print '\n\033[1;31m*WARNING* Daemons not running ...\033[1;37m'
    
    elif (opt =='q'):
        print '\n\033[1;32mQuitting kmotion manual daemon control ...\033[1;37m'
        status = daemon_whip.daemon_status()
        if status['kmotion_hkd1.py']:
            text = '\033[1;32mRunning\033[1;37m'
        else:
            text= '\033[1;31mNot running\033[1;37m'
        print '\nkmotion_hkd1.py status : ' + text
        
        if status['kmotion_hkd2.py']:
            text = '\033[1;32mRunning\033[1;37m'
        else:
            text= '\033[1;31mNot running\033[1;37m'
        print 'kmotion_hkd2.py status : ' + text
        
        if status['motion']:
            text = '\033[1;32mRunning\033[1;37m'
        else:
            text= '\033[1;31mNot running\033[1;37m'
        print 'motion status          : ' + text
        print
        sys.exit()






