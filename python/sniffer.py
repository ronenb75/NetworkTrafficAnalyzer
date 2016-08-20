#!/usr/bin/env python

import re
import sys
import subprocess
import mysql.connector
import netaddr
import time

localIP = '192.168.0.230'

# Checking if another process is already running. If yes, just exit.
process = subprocess.Popen("/bin/ps aux | /bin/grep -v grep | /bin/grep -c sniffer.py", stdout=subprocess.PIPE, shell=True)
if int(process.communicate()[0]) >= 2:
        sys.exit()

conn = mysql.connector.connect(user='root', password='', host='localhost', database='network')

cursor = conn.cursor()
try:
    cursor.execute("CREATE TABLE IF NOT EXISTS traffic (srcIP int(10) unsigned NOT NULL, dstIP int(10) unsigned NOT NULL, traffic bigint(20) unsigned NOT NULL, firstSeen timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', lastUpdate timestamp NOT NULL DEFAULT now() ON UPDATE now(), PRIMARY KEY (srcIP,dstIP))")
    cursor.execute("CREATE TABLE IF NOT EXISTS ips (ip int(10) unsigned NOT NULL, name varchar(200) NOT NULL, PRIMARY KEY (ip))")
except:
    print 'error creating table'
    sys.exit()
cursor.close()


traffic={}
presistTime = time.time()

p = subprocess.Popen(['tcpdump', '-B 8192', '-s 100', '-nnNqtli', 'eth0', 'tcp and not port 22'], stdout=subprocess.PIPE, stderr=None)

reg = re.compile(r"IP (?P<IP1>(?:\d{1,3}\.){3}\d{1,3})\.(?P<Port1>\d+) > (?P<IP2>(?:\d{1,3}\.){3}\d{1,3})\.(?P<Port2>\d+): (?:tc|ud)p (?P<size>\d+)")

for line in iter(p.stdout.readline, b''):
    m = reg.match(line.rstrip())

    if m is not None:
        ip1 = int(netaddr.IPAddress(m.group(1)))
        ip2 = int(netaddr.IPAddress(m.group(3)))

        try:
            traffic[ip1,ip2] += int(m.group(5))
        except:
            if ip1 != int(netaddr.IPAddress(localIP)) and ip2 != int(netaddr.IPAddress(localIP)):
                traffic[ip1,ip2] = int(m.group(5))

        if time.time() - presistTime > 60:
            #print "presist now...."
            try:
                cursor = conn.cursor()
            except:
                print 'cannot open cursor to MySQL database...'

            for ip1, ip2 in traffic:
                #print ("insert into traffic (srcIP, dstIP, traffic) values ('{0}', '{1}', '{2}') on duplicate key update traffic = traffic + '{2}'".format(ip1, ip2, traffic[ip1,ip2]))
                try:
                    cursor.execute("insert into traffic (srcIP, dstIP, traffic, firstSeen) values ('{0}', '{1}', '{2}', now()) on duplicate key update traffic = traffic + '{2}'".format(ip1, ip2, traffic[ip1,ip2]))
                except:
                    print 'error inserting MySQL row'

            try:
                cursor.execute("delete from traffic where traffic = 0")
            except:
                print 'error inserting MySQL row'

            cursor.close()
            conn.commit()
            traffic={}
            presistTime = time.time()
            #print "presist Done...."
            #print ('----------------------------------------------')

conn.close()