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

# kmotion install daemon

import os, ConfigParser

def install():

    # check for root authority
    print_checking('Checking install is running as root')
    uid = os.getuid()
    if uid != 0:
        print_fail( 'install.py needs to be run as \'sudo install.py\'')
        return
    print_ok()
    
    # check we can read ./daemon.rc
    print_checking('Checking install is running in daemons directory')
    parser = ConfigParser.SafeConfigParser()
    parsed = parser.read('./daemon.rc')
    if parsed != ['./daemon.rc']:
        print_fail( 'please \'cd\' to the kmotion daemons directory')
        return
    print_ok()
        
    images_dir = parser.get('dirs', 'images_dir')
    misc_config_dir = parser.get('dirs', 'misc_config_dir')
    daemons_dir = parser.get('dirs', 'daemons_dir')
    apache2_config_dir = parser.get('dirs', 'apache2_config_dir')
    www_dir = parser.get('dirs', 'www_dir')
    # apt-get the packages
    print_checking('Requesting critical packages')
    print_ok()
    print
    os.system('apt-get install libapache2-mod-php5 apache2 motion screen')
    
    # reconfigure kmotion
    print
    print_checking('Configuring kmotion')
    #FIXME: need to get users name :)
    os.system('sudo -u dave ./reconfig.py')
    print_ok()
    
    # link vhost file
    print_checking('Linking apache2 \'sites-enabled\' to kmotion \'kmotion_vhost\'')
    os.system('rm /etc/apache2/sites-enabled/kmotion_vhost &> /dev/null')
    os.system('ln -s %s/kmotion_vhost  /etc/apache2/sites-enabled' % apache2_config_dir)
    print_ok()
        
    # restart apache
    print_checking('Restarting apache2')
    print_ok()
    print
    os.system('/etc/init.d/apache2 restart')
    
    # add startup to /etc/rc.local
    print_checking('Adding \'kmotion\' to \'/etc/rc.local\'')
    f = open('/etc/rc.local', 'r')
    lines = f.readlines()
    f.close
    
    f = open('/etc/rc.local', 'w')
    for line in lines:
        if line == 'motion &': continue
        if line == 'exit 0':
    #FIXME: need to get users name :)
            f.write('sudo -u %s -s motion &')
        f.write(line)
    f.close
            
    print_checking('Linking \'kmotion\' to \'/usr/local/bin\'')
    print_ok()
    #FIXME: need to get users name :)
    os.system('sudo -u dave ln -s %s/kmotion /usr/local/bin')
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
def print_checking(text):
    length = len(text)
    print '\033[1;32m%s' % (text),
    print '.' *  (65 - length) , 
    print '\033[1;37m',
    
def print_ok():
    print '\033[1;32m[OK]\033[1;37m'
    
def print_fail(text):
    print '\033[1;31m[FAIL]\n\n%s\n\033[1;37m' % (text)
    
    
        
    
    
    
    




install()
