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

# kmotion logger - very light weight

import syslog

"""
Workaround for the buggy syslog module - should not be necessary
"""

class Logger:

    def __init__(self, ident, min_priority):
        """ Create a 'Logger' instance whith a min logging priority of 'min_priority' """
        # 'min_priority' is the min priority level at which events will be sent to syslog, 
        # it  must be one of ...
        # EMERG, ALERT, CRIT, ERR, WARNING, NOTICE, INFO, DEBUG
        self.case = {'EMERG': syslog.LOG_EMERG,
                            'ALERT': syslog.LOG_ALERT,
                            'CRIT': syslog.LOG_CRIT,
                            'ERR': syslog.LOG_ERR,
                            'WARNING': syslog.LOG_WARNING,
                            'NOTICE': syslog.LOG_NOTICE,
                            'INFO': syslog.LOG_INFO,
                            'DEBUG': syslog.LOG_DEBUG}
        self.ident = ident
        self.min_priority = min_priority       
    
    def log(self, msg, priority):
        """ Log an message with priority 'priority' """
        # 'priority' is the actual level of the event, it must be one of ...
        # EMERG, ALERT, CRIT, ERR, WARNING, NOTICE, INFO, DEBUG
        # 'msg' will only be sent to syslog if 'priority' >= 'min_priority'
        
        # TODO: The Python syslog module is very broken - logging priorities are ignored, 
        # this is a workaround ...
        if self.case[priority] <= self.case[self.min_priority]: 
            syslog.openlog(self.ident , syslog.LOG_PID) 
            syslog.syslog(msg)
            syslog.closelog()
        
        # The Python code that should implement the above ...
        #syslog.openlog(self.ident , syslog.LOG_PID, (syslog.LOG_ALERT | syslog.LOG_USER)) 
        #syslog.setlogmask(syslog.LOG_UPTO(self.case[self.min_priority]))
        #syslog.syslog(msg)
        #syslog.closelog()
        
        
        
        
        
        
        
        
        
        
        
        
        
