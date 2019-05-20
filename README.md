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
8. Check _test table

Warning : this plugin delete questions_test,question_l10ns,answers_test,answer_l10ns,label_test,label_l10ns,questions_old,answers_old table if exist in your DB.
The table _test can be checked to be used in LimeSurvey 4.X
