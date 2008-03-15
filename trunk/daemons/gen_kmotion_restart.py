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

# generate the kmotion_restart module

import os, ConfigParser

"""
Generate the kmotion start module
"""

def gen_kmotion_restart():
    """
    Generate the kmotion_restart module
    """
    
    parser = ConfigParser.SafeConfigParser()  
    parsed = parser.read('./daemon.rc')
    daemons_dir = parser.get('dirs', 'daemons_dir')
    
    blog = """#!/usr/bin/env python

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

# Starts kmotion daemons, executable from anywhere in the system\n

import os

os.chdir('%s')
os.system('./kmotion_restart.py')

print '\033[1;32mkmotion restarted ...\033[1;37m'
print
""" % daemons_dir
        
    f = open('%s/kmotion_restart' % (daemons_dir), 'w')
    f.write(blog)
    f.close()
    os.chmod('./kmotion_restart', 0755)
    

if __name__ == '__main__':
    gen_kmotion_restart()
    
    
