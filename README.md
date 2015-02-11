# newsblur-export
(Unofficial) NewsBlur saved stories exporter

Exports your saved stories from https://www.newsblur.com/

## Usage
If you use the source from git, you'll need to download the dependencies using Composer.

Copy newsblur.ini.example to newsblur.ini, edit that file and add your own username and password.

Then run:

php export.php

If all goes well, your saved stories will be exported to a single file named starred_stories.json
