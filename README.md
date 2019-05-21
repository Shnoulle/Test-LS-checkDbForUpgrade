# checkForDbUpdate

This plugin just test updating to 400 + 407 DB update for LimeSurvey using SQL instruction only. 

# Usage

1. Put the plugin in plugins directory 
2. Go to plugin manager
3. Configure this plugin (no need to activate)
4. Set debug to 2
5. Check **Do update**
6. Click on _Save_
7. Report issue …
8. Check `*_test` table

Warning : this plugin delete some `*_test`,`*_l10ns`,`*_old` table if exist in your DB.
The table `*_test` and `*_l10ns` are not deleted after testing for review purpose.
