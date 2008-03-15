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

import daemon_whip

"""
Called by the bin kmotion_restart file this module simply calls config_reload() or if the
daemons are not running calls start_daemons().  The bin kmotion_restart file cannot 
call config_reload() directly because it may be in a different working directory.
"""

if daemon_whip.daemons_running():
    daemon_whip.config_reload()
else:
    daemon_whip.start_daemons()
    
