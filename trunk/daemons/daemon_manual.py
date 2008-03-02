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

# kmotion manual daemon control

import time, sys, daemon_whip

print '\nkmotion manual daemon control ........'
while (True):
    print '\nThe Options ....\n'
    print '1: Start Daemons'
    print '2: Kill Daemons'
    print '3: Reload Daemon configs'
    print '4: Quit'
    
    if(daemon_whip.daemons_running()):
      status = '\033[1;32mRunning\033[1;37m'
    else:
      status = '\033[1;31mNot running\033[1;37m'
    print '\nDaemon status : ' + status
    
    opt = raw_input('Option number then ENTER :')
    
    if (opt == '1'):
        if(daemon_whip.daemons_running()):
            print '\n\033[1;32mDaemons already running ...\033[1;37m'
        else:
            daemon_whip.start_daemons()
            time.sleep(1)
            if(daemon_whip.daemons_running()):
              print '\n\033[1;32mDaemons have been started ...\033[1;37m'
            else:
              print '\n\033[1;31m*WARNING* Unable to start daemons ...\033[1;37m'
              
    elif (opt == '2'):
        if(daemon_whip.daemons_running()):
            print '\n\033[1;31mStarting to kill daemons ...\033[1;37m'
            daemon_whip.kill_daemons()
            print '\033[1;31mDaemons have been killed ...\033[1;37m'
        else:
            print '\n\033[1;31m*INFORMATION* Daemons not running ...\033[1;37m'
    
    elif (opt == '3'):
        if(daemon_whip.daemons_running()):
            daemon_whip.config_reload()
            print '\n\033[1;33mDaemons config reloaded ...\033[1;37m'
        else:
            print '\n\033[1;31m*WARNING* Daemons not running ...\033[1;37m'
    
    elif (opt =='4'):
        if(daemon_whip.daemons_running()):
            print '\n\033[1;31mStarting to kill daemons ...\033[1;37m'
            daemon_whip.kill_daemons()
            print '\033[1;31mDaemons have been killed ...\033[1;37m'
        sys.exit()






