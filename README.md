# moodle-tool-linkchecking
Moodle Admin Tool  
  
Maintaining courses means watching for broken links.   This tool quickly helps developers identify links that are broken.   Also, since we are now ensuring that our resources are compatible with secure Moodle sites, we need to watch for links that were incorrectly entered.
  
Depending on your server settings you may be required to create a local instance of your website and database.
  
Follow the instructions on the plugin very carefully. Backing up the database table 'mdl_book_chapters' is recommended. Once the 'Run' button is clicked, the execution time may be hours if the link limit is set high, make sure your server has sufficient memory allocated otherwise it may crash.
