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

import os, ConfigParser
import gen_vhost, gen_kmotion, gen_kmotion_restart

"""
Modify daemon.rc paths to reflect cwd. Generate vhost file from vhost template. Generate 
bin files kmotion and kmotion_restart
"""

parser = ConfigParser.SafeConfigParser()
parsed = parser.read('./daemon.rc')
cwd = os.getcwd()[:-7]  # strips off 'daemons' to get root directory
parser.set('dirs', 'images_dir', cwd + 'images')
parser.set('dirs', 'misc_config_dir', cwd + 'misc_config')
parser.set('dirs', 'daemons_dir', cwd + 'daemons')
parser.set('dirs', 'apache2_config_dir', cwd + 'apache2_config')
parser.set('dirs', 'www_dir', cwd + 'www')

gen_vhost.gen_vhost()
gen_kmotion.gen_kmotion()
gen_kmotion_restart.gen_kmotion_restart()

    
    
