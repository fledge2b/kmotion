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

import os, pwd, ConfigParser

def install():
    """
    A very simple automated install script ... this code is not bombproof !
    """
    
    print """\033[1;32m
Welcome to the kmotion v1.10b installer. Apart from internal configurations 
the only changes that will be made to your system are:

(1) A link from /etc/apache2/sites-enabled/ to this directory
(2) The addition of 'kmotion' and 'kmotion_restart' to /usr/bin/
(3) The addition of 'sudo -u <name> motion &' to /etc/rc.local

All of which are reversible manually or by executing uninstall.py. 

Press ENTER to start install.\033[1;37m"""

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
    
    # ironically then change to the daemons dir !
    os.chdir('./daemons')
        
    # configure kmotion
    print_checking('Generating kmotion configurations')
    print_ok()
    print_checking('Changing kmotion paths to cwd')
    print_ok()
    os.system('sudo -u %s ./install_utils.py' % login)
    
    images_dir = parser.get('dirs', 'images_dir')
    misc_config_dir = parser.get('dirs', 'misc_config_dir')
    daemons_dir = parser.get('dirs', 'daemons_dir')
    apache2_config_dir = parser.get('dirs', 'apache2_config_dir')
    www_dir = parser.get('dirs', 'www_dir')
    
    # apt-get critical packages
    print_checking('Requesting critical packages')
    print_ok()
    print
    os.system('apt-get install libapache2-mod-php5 apache2 motion screen')
    
    # link vhost file
    print
    print_checking('Linking apache2 \'sites-enabled\' to kmotion \'kmotion_vhost\'')
    os.system('ln -fs %s/kmotion_vhost  /etc/apache2/sites-enabled' % apache2_config_dir)
    print_ok()
        
    # restart apache2
    print_checking('Restarting apache2')
    print_ok()
    print
    os.system('/etc/init.d/apache2 restart')
    
    # move bin file kmotion to /usr/bin
    print_checking('Adding \'kmotion\' to \'/usr/bin\'')
    os.rename('%s/kmotion' % daemons_dir, '/usr/bin/kmotion')
    print_ok()
    
    # move bin file kmotion_restart to /usr/local/bin
    print_checking('Adding \'kmotion_restart\' to \'/usr/bin\'')
    os.rename('%s/kmotion_restart' % daemons_dir,  '/usr/bin/kmotion_restart')
    print_ok()
            
    # add kmotion to /etc/rc.local
    print_checking('Adding \'kmotion\' to \'/etc/rc.local\' for auto startup')
    f = open('/etc/rc.local', 'r')
    lines = f.readlines()
    f.close
    
    f = open('/etc/rc.local', 'w')
    ok = False
    for line in lines:
        if line == 'sudo -u %s motion &\n' % login: continue
        if line[:4] == 'exit':
            f.write('sudo -u %s motion &\n' %  login)
            ok = True
        f.write(line)
    f.close
    if ok: 
        print_ok()
    else: print_fail('Failed to add kmotion to \'/etc/rc.local\' please ensure \'/etc/rc.local\' has the line \'exit 0\' as the last line')
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
    print '\033[1;31m[FAIL]\n\n%s\n\033[1;37m' % text,
    
    
install()