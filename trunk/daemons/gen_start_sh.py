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

# generate the start.sh bash script

import os, ConfigParser

"""
Generate the kmotion_start.sh bash script
"""

gpl = """
#!/bin/bash

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
"""

blog = """print "\\033[1;32mkmotion has been started\\033[1;37m"
"""

        
def gen_start_sh():
    """
    Generate the start.sh bash script
    """
    parser = ConfigParser.SafeConfigParser()  
    parsed = parser.read('./daemon.rc')
    daemons_dir = parser.get('dirs', 'daemons_dir')
    
    f = open('%s/kmotion_start.sh' % (daemons_dir), 'w')
    f.write(gpl)
    f.write('cd %s && ./daemon_dep.py\n\n' % (daemons_dir))
    f.write(blog)
    f.close()
    
    os.chmod('./kmotion_start.sh', 0755)
    

if __name__ == '__main__':
    gen_start_sh()
    
    
    
    
    
    
    
    
    
    
    
    
    
