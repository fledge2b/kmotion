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

import os, pwd, ConfigParser

def uninstall():
    """
    A very simple automated uninstall script ... this code is not bombproof !
    """
    print """\033[1;31m
Welcome to the kmotion v1.10b uninstaller. Apart from the kmotion directory
all traces of kmotion will be uninstalled. This includes

(1) A link from /etc/apache2/sites-enabled/ to this directory
(2) The addition of 'kmotion' and 'kmotion_restart' in /usr/bin/
(3) The addition of 'sudo -u <name> motion &' in /etc/rc.local
(4) The addition of '127.0.0.1 kmotion' to /etc/hosts \033[1;32m

Press ENTER to start uninstall.\033[1;37m"""

    raw_input()

    # check we are running as root
    print_checking('Checking install is running as root')
    uid = os.getuid()
    if uid != 0:
        print_fail( 'Install needs to be runs as root, try \'sudo install\'')
        return
    print_ok()
    
    login = os.environ['SUDO_USER']
    
    # check we can read ./daemons/daemon.rc - if we can't assume we are not in the root directory
    print_checking('Checking install is running in correct directory')
    parser = ConfigParser.SafeConfigParser()
    parsed = parser.read('./daemons/daemon.rc')
    if parsed != ['./daemons/daemon.rc']:
        print_fail( 'Please \'cd\' to the kmotion root directory')
        return
    print_ok()
    
    # delete link from /etc/apache2/sites-enabled/
    print_checking('Deleteing apache2 \'sites-enabled\' link')
    if os.path.islink('/etc/apache2/sites-enabled/kmotion_vhost'):
        os.remove('/etc/apache2/sites-enabled/kmotion_vhost')
        print_ok()  
    else:
        print_fail('apache2 \'sites-enabled\' link already deleted')

    # restart apache2
    print_checking('Restarting apache2')
    print_ok()
    print
    os.system('/etc/init.d/apache2 restart')

    # delete /usr/bin/kmotion
    print_checking('Deleteing /usr/bin/kmotion')
    if os.path.isfile('/usr/bin/kmotion'):
        os.remove('/usr/bin/kmotion')
        print_ok() 
    else:
        print_fail('/usr/bin/kmotion already deleted')
    
    # delete /usr/bin/kmotion_restart
    print_checking('Deleteing /usr/bin/kmotion_restart')
    if os.path.isfile('/usr/bin/kmotion_restart'):
        os.remove('/usr/bin/kmotion_restart')
        print_ok()  
    else:
        print_fail('/usr/bin/kmotion_restart already deleted')
    
    # remove kmotion from /etc/rc.local
    print_checking('Removing \'kmotion\' from \'/etc/rc.local\' for auto startup')
    f = open('/etc/rc.local', 'r')
    lines = f.readlines()
    f.close
    
    f = open('/etc/rc.local', 'w')
    detected = False
    for line in lines:
        if line == 'sudo -u %s kmotion &\n' % login:
            detected = True
            continue
        f.write(line)
    f.close
    if detected:
        print_ok()
    else:
        print_fail('\'kmotion\' already removed from \'/etc/rc.local\'')
  
    # remove 127.0.0.1 kmotion from /etc/hosts
    print_checking('Removeing \'127.0.0.1    kmotion\' from \'/etc/hosts\'')
    f = open('/etc/hosts', 'r')
    lines = f.readlines()
    f.close

    f = open('/etc/hosts', 'w')
    for line in lines:
        if line == '127.0.0.1\tkmotion\n': continue
        f.write(line)
    f.close
    print_ok()
    print
    

def print_checking(text):
    """
    Given a text string colorise to green and calculate the number of '.'s
    """
    length = len(text)
    print '\033[1;32m%s' % text,
    print '.' *  (65 - length) , 
    print '\033[1;37m',
    
def print_ok():
    """
    Colorise a green [ok]
    """
    print '\033[1;32m[OK]\033[1;37m'
    
def print_fail(text):
    """
    Given a text string colorise to red and prepend a [fail] 
    """
    print '\033[1;31m[FAIL] : %s\033[1;37m' % text
    

uninstall()
