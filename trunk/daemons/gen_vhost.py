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

import ConfigParser

"""
Generate the kmotion_vhost file from kmotion_vhost_template expanding %directory%
strings to their full paths as defined in daemon.rc
"""
        
def gen_vhost():
    
    parser = ConfigParser.SafeConfigParser()  
    parsed = parser.read('./daemon.rc')
    images_dir = parser.get('dirs', 'images_dir')
    apache2_config_dir = parser.get('dirs', 'apache2_config_dir')
    www_dir = parser.get('dirs', 'www_dir')

    f = open('%s/%s' % (apache2_config_dir, 'kmotion_vhost_template'))
    config = f.readlines()
    f.close

    for i in range(len(config)):
        config[i] = config[i].replace('%images_dir%', images_dir)
        config[i] = config[i].replace('%apache2_config_dir%', apache2_config_dir)
        config[i] = config[i].replace('%www_dir%', www_dir)
        
    f = open('%s/%s' % (apache2_config_dir, 'kmotion_vhost'), 'w')
    for line in config:
        f.write(line)
    f.close
    

if __name__ == '__main__':
    gen_vhost()
    
    
        
        
        
        
        
        
        
        
        
        
        
