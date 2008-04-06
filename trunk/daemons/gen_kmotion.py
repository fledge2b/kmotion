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

import os, ConfigParser

"""
Generate the kmotion module which when executed always cd's to the correct directory 
and calls kmotion.py
"""

def gen_kmotion():
    
    parser = ConfigParser.SafeConfigParser()  
    parsed = parser.read('./kmotion.rc')
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
os.system('./kmotion.py')
""" % daemons_dir
        
    f = open('%s/kmotion' % (daemons_dir), 'w')
    f.write(blog)
    f.close()
    os.chmod('./kmotion', 0755)
    

if __name__ == '__main__':
    gen_kmotion()
    
    
