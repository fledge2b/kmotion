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

# kmotion house keeping daemon 1

import os, sys, time, signal, shutil,  ConfigParser, daemon_whip
import kmotion_logger, kmotion_genconfigs

"""
Checks the % of free disk space & if too low deletes oldest video dirs first. Also
checks that 'motion' & 'kmotion_hkd2.py' are running, restarting them if they are not. 
Finally responds to a SIGHUP by re-reading kmotion.rc.
"""

class Kmotion_Hkd1:
    
    def __init__(self):
        self.kmotion_dbase = ''
        self.motion_config = ''
        self.file_system = ''
        self.cull_trigpc = 0
        self.logger = kmotion_logger.Logger('kmotion_hdk1', 'WARNING')
        signal.signal(signal.SIGHUP, self.signal_hup)
        self.read_config()
        
    def start_daemon(self):    
        """" Start the house keeping 1 daemon """
        self.logger.log('Daemon starting ...', 'DEBUG')
        
        # ensure .../events dir exists & is empty
        shutil.rmtree('%s/events' % (self.kmotion_dbase), True)
        os.makedirs('%s/events' % (self.kmotion_dbase))
        
        while(True):   
            # Check the free disk space ...
            logon = os.environ['LOGNAME']  
            os.system('df > /tmp/kmotion-%s' % logon) 
            f = open('/tmp/kmotion-%s' % logon, 'r')
            df_op= f.readlines()
            f.close()
            
            found = False
            for line in df_op:
                split = line.split()
                if split[0] == self.file_system:  # Found the file_system, check the space
                    found = True 
                    if int(split[4][:-1]) > self.cull_trigpc:
                        dir = os.listdir('/var/lib/motion')
                        dir.sort()
                        if len(dir) == 0:  # Check for no video, if so exit
                            self.logger.log('Disk space lower limit reached but no video to cull in %s - Killing motion & all daemon processes' % ('/var/lib/motion'), 'CRIT')
                            daemon_whip.kill_daemons()
                            sys.exit()
                        self.logger.log('Disk space lower limit reached - deleteing %s/%s' %  ('/var/lib/motion', dir[0]), 'DEBUG')
                        shutil.rmtree('%s/%s' % (self.kmotion_dbase, dir[0]))  # Delete oldest dir first
            if not(found): 
                self.logger.log('Could not identify filesystem %s - Killing motion & all daemon processes' % (self.file_system), 'CRIT')
                daemon_whip.kill_daemons()
                sys.exit()
            
            self.chk_motion()
            self.chk_kmotion_hkd2()
            time.sleep(5 * 60)
        
    def chk_motion(self):
            ''' Check motion is still running ... if not restart it ... '''
            if os.system('/bin/ps ax | /bin/grep [m]otion\ -c'):
               self.logger.log('motion not running - starting motion', 'CRIT')
               os.system('motion -c ~/%s &' % (self.motion_config))
                
    def chk_kmotion_hkd2(self):
            ''' Check kmotion_hkd2.py is still running ... if not restart it ... '''
            if os.system('/bin/ps ax | /bin/grep [k]motion_hkd2.py$'):
               self.logger.log('kmotion_hkd2.py not running - starting kmotion_hkd2.py', 'CRIT')
               os.system(self.kmotion_dbase + '/kmotion_hkd2.py &')

    def read_config(self):
        """ Read config file from '~/.kde/share/apps/kmotion/kmotion.rc' """
        parser = ConfigParser.SafeConfigParser()  
        parsed = parser.read('/var/lib/kmotion/kmotion_config/kmotion.rc')
        
        if parsed[0][-10:] != 'kmotion.rc':
            emsg = 'Can\'t open config file %s - Killing motion & all daemon processes' % parsed[0][-10:]
            self.logger.log(emsg,  'CRIT')
            daemon_whip.kill_daemons()
            sys.exit()
            
        try:   
            self.kmotion_dbase = parser.get('misc', 'kmotion_dbase')
            self.motion_config = parser.get('misc', 'motion_config')
            self.file_system = parser.get('misc', 'file_system')
            self.cull_trigpc = int(parser.get('misc', 'cull_trigpc'))
        except:
            self.logger.log('Corrupt config error : %s - Killing motion & all daemon processes' % sys.exc_info()[1], 'CRIT')
            daemon_whip.kill_daemons()
            sys.exit()
        
    def signal_hup(self, signum, frame):
            """ Re-read the config file on SIGHUP """
            self.logger.log('Signal SIGHUP detected, re-reading config file', 'DEBUG')
            self.read_config()
            
if __name__ == '__main__':
    Hkd1 = Kmotion_Hkd1()
    Hkd1.start_daemon()
    
