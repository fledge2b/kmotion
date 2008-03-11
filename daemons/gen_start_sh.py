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

import ConfigParser

"""
Generate the start.sh bash script
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

Starts kmotion daemons, executable from anywhere in the system\n
"""
        
def gen_start_sh():
    """
    Generate the start.sh bash script
    """
    parser = ConfigParser.SafeConfigParser()  
    parsed = parser.read('./daemon.rc')
    daemons_dir = parser.get('dirs', 'daemons_dir')
    
    f = open('%s/start_kmotion.sh' % (daemons_dir), 'w')
    f.write(gpl)
    f.write('cd %s && ./daemon_start.py\n\n' % (daemons_dir))
    f.close()
    
    #FIXME: need to google to make exec bit enabled
    #os.chmod('./start.sh')


if __name__ == '__main__':
    gen_start_sh()
    
    
    
    
    
    
    
    
    
    
    
    
    
