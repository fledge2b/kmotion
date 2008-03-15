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

import gen_rcs, gen_vhost, gen_kmotion, gen_kmotion_restart

"""
Generate all rc's by parsing motion.conf files, generate modified motion.conf files for kmotion,
generate www.rc and modify daemon.rc 

Generate vhost file from vhost template and bin files kmotion and kmotion_restart
"""

rcs = gen_rcs.Gen_Rcs()  
rcs.gen_rcs() 

gen_vhost.gen_vhost()
gen_kmotion.gen_kmotion()
gen_kmotion_restart.gen_kmotion_restart()

    
    
