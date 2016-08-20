# NetworkTrafficAnalyzer
Simple network traffic analyzer

This is a very simple network analyzer for the home user.
I was looking for something that can show me the individual usage of each machine inside my home network and couldn’t find anything which persist the stats.
I'm not interested in real-time view as there are many tools that can do that. I want the long-run view of what's going on.

The python sctipt runs from cron, firing up tcpdump to capture the traffic going through the router and collect Source IP, Destination IP and size of the packet. No payload is being recorded.
Every 60 seconds, the results are persist into a MySQL database.

The PHP part access the database and present an easy view into the information I wanted to look at. The code can be changed to look at whatever you'd like.

Notes: my graphic design abilities are only slightly worse then my PHP and Python coding skills, which means the page looks really ugly. But it practical.
Adding to that, I'm a huge believer in K.I.S.S, meaning my code is minimal, to do exactly what I need it to do and nothing else.

Security note: As you can see, there is NO SECURITY what-so-ever in this code! Linux root access, empty MySQL root password, no input sensitising, bare ajax functions etc. Since it's all sitting inside my home environment with no access from the outside, I let myself go naked. Please DO NOT USE THIS CODE AS IT IS in any kind of production environment without putting security measures first!

Any improvements will be gladly accepted.
Blog post regaring this tool can be found here: http://blog.ronenb.com/2016/08/20/network-traffic-analyzer-with-raspberrypi/
