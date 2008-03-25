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

import os

def sys_v_exists(service):
    """
    Given the name of a service in '/etc/init.d/' returns a bool true
    if the file exists 
    """
    




def sys_v_remove(service):
    """
    Given the name of a service in '/etc/init.d/' changes all links in all '/etc/rc?.d' directories
    to 'K??service' effectively disableing the service.
    """
    if not os.path.isfile('/etc/init.d/%s' % service): 
        return False

    os.system('update-rc.d -f %s remove' % service)
    os.system('update-rc.d %s stop 0 1 2 3 4 5 6 .' % service)
    return True
    
def sys_v_add(service):
    """
    Given the name of a service in '/etc/init.d/' changes all links in all '/etc/rc?.d' directories
    to 'defaults' effectively enableing the service.
    """
    if not os.path.isfile('/etc/init.d/%s' % service): 
        return False

    os.system('update-rc.d -f %s remove' % service)
    os.system('update-rc.d %s multiuser' % service)
    return True
    
    
    
    
    
    
    
    
    
    
    
    
