# my first ever python script used in real life.
# fetches data from SoDra, parses and sends to a php script to save it to a database.

import urllib2, zipfile, cStringIO, csv, os, datetime, re, subprocess, shlex, ConfigParser

#let's read the settings file
settings_file = os.path.dirname(os.path.realpath(__file__)) + '/../settings/settings.ini'
config = ConfigParser.ConfigParser()
config.readfp(open(settings_file))
php_executable = config.get('python', 'php_executable')
php_executable = php_executable.strip('"')
php_script = os.path.join(os.path.dirname(__file__), config.get('python', 'php_file'))
php_command = php_executable + ' ' + php_script
sodra_url = config.get('python', 'sodra_url')

#let's fetch a new data set from SoDra
webfile = urllib2.urlopen(sodra_url)

#let's read it in
zip_file = cStringIO.StringIO(webfile.read())
zip_obj = zipfile.ZipFile(zip_file, 'r')
csvReader = csv.reader(cStringIO.StringIO(zip_obj.read(zip_obj.namelist()[0])), delimiter = ';')

#let's run a php script
p = subprocess.Popen(php_command, stdin=subprocess.PIPE, stdout=subprocess.PIPE, shell=True)

#let's parse the SoDra file and pass the data to PHP to be inserted into mysql
i = 0
for row in csvReader:
	i = i + 1
	if (i == 1):		
		found = re.search('\d{4}-\d{2}-\d{2}', row[0]).group()
		if (found != None): 
			today = found
		else:
			today = datetime.date.today().strftime("%Y-%m-%d")
	if (i > 3):
		row.append(today)
		p.stdin.write(', '.join(row) + '\n')
