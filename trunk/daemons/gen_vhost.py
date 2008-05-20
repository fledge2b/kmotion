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

import ConfigParser

"""
Generate the kmotion_vhost file from kmotion_vhost_template expanding %directory%
strings to their full paths as defined in daemon.rc
"""
        
def gen_vhost():
    
    parser = ConfigParser.SafeConfigParser()  
    parsed = parser.read('./kmotion.rc')
    images_dir = parser.get('dirs', 'images_dir')
    apache2_config_dir = parser.get('dirs', 'apache2_config_dir')
    port = parser.get('misc', 'port')
    
    www =  parser.get('www', 'www')

    if www == '2.0': 
        dir = 'www_2.0_dir' 
    else: 
        dir = 'www_classic_dir'

    www_dir = parser.get('dirs', dir)

    LDAP_enabled = parser.get('LDAP', 'enabled') in ['true', 'True',  'yes',  'Yes']
    LDAP_url = parser.get('LDAP', 'AuthLDAPUrl')
    
    if LDAP_enabled:
        LDAP_block = '''
        # ** INFORMATION ** LDAP mode enabled ... 
        AuthName "LDAP"
        AuthBasicProvider ldap
        AuthzLDAPAuthoritative off
        AuthLDAPUrl %s\n''' % LDAP_url
    else:
        LDAP_block = '''
        # ** INFORMATION ** Users digest file enabled ...
        AuthName "kmotion"
        AuthUserFile %s/users_digest\n''' % apache2_config_dir
    
    template = open('%s/%s' % (apache2_config_dir, 'kmotion_vhost_template')).readlines()
    for i in range(len(template)):
        template[i] = template[i].replace('%images_dir%', images_dir)
        template[i] = template[i].replace('%apache2_template_dir%', apache2_config_dir)
        template[i] = template[i].replace('%www_dir%', www_dir)
        template[i] = template[i].replace('%port%', port)
        template[i] = template[i].replace('%LDAP_block%',  LDAP_block)
        
    f = open('%s/%s' % (apache2_config_dir, 'kmotion_vhost'), 'w')
    for line in template:
        f.write(line)
    f.close

if __name__ == '__main__':
    gen_vhost()
    
    
        
        
        
        
        
        
        
        
        
        
        
