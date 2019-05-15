<?php
/**
 * Checking some SQL instruction
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2019 Denis Chenu <www.sondages.pro>
 * @license Do What The Fuck You Want To Public License (WTFPL)
 * @version 0.0.1
 *
 */
class checkDbForUpgrade extends PluginBase
{

    static protected $name = 'checkDbForUpgrade';
    static protected $description = 'testing some event : totally unstable, can broke all usage of LimeSurvey, can broke your car and your house too.';

    protected $storage = 'DbStorage';

    protected $settings=array(
        'duUpdate'=>array(
            'type'=>'boolean',
            'label'=>'Do update',
        ),
    );

    /**
    * Add function when you want
    */
    public function init()
    {
    }

    public function saveSettings($settings)
    {
        if(!empty($settings['duUpdate'])) {
            $oDB = Yii::app()->getDb();
            if(Yii::app()->db->schema->getTable('{{questions_test}}')){
                $oDB->createCommand()->dropTable('{{questions_test}}');
            }
            if(Yii::app()->db->schema->getTable('{{question_l10ns}}')){
                $oDB->createCommand()->dropTable('{{question_l10ns}}');
            }
            if(Yii::app()->db->schema->getTable('{{answers_test}}')){
                $oDB->createCommand()->dropTable('{{answers_test}}');
            }
            if(Yii::app()->db->schema->getTable('{{answer_l10ns}}')){
                $oDB->createCommand()->dropTable('{{answer_l10ns}}');
            }
            /* Must not happen if don't broke, but can broke … */
            if(Yii::app()->db->schema->getTable('{{answers_old}}')){
                $oDB->createCommand()->dropTable('{{answers_old}}');
            }

            /* The test question (to don't update real table) : samle than 3.0 installer */
            $oDB->createCommand()->createTable('{{questions_test}}', array(
                'qid' =>  "autoincrement",
                'parent_qid' =>  "integer NOT NULL default '0'",
                'sid' =>  "integer NOT NULL default '0'",
                'gid' =>  "integer NOT NULL default '0'",
                'type' =>  "string(1) NOT NULL default 'T'",
                'title' =>  "string(20) NOT NULL default ''",
                'question' =>  "text NOT NULL",
                'preg' =>  "text",
                'help' =>  "text",
                'other' =>  "string(1) NOT NULL default 'N'",
                'mandatory' =>  "string(1) NULL",
                'question_order' =>  "integer NOT NULL",
                'language' =>  "string(20) default 'en' NOT NULL",
                'scale_id' =>  "integer NOT NULL default '0'",
                'same_default' =>  "integer NOT NULL default '0'",
                'relevance' =>  "text",
                'modulename' =>  "string(255) NULL",
                'composite_pk' => array('qid', 'language')
            ));
            $oDB->createCommand()->createIndex('{{idx1_questions}}', '{{questions_test}}', 'sid', false);
            $oDB->createCommand()->createIndex('{{idx2_questions}}', '{{questions_test}}', 'gid', false);
            $oDB->createCommand()->createIndex('{{idx3_questions}}', '{{questions_test}}', 'type', false);
            $oDB->createCommand()->createIndex('{{idx4_questions}}', '{{questions_test}}', 'title', false);
            $oDB->createCommand()->createIndex('{{idx5_questions}}', '{{questions_test}}', 'parent_qid', false);
            $oDB->createCommand("INSERT INTO {{questions_test}} select * FROM {{questions}}")->execute();

            $oDB->createCommand()->createTable('{{answers_test}}', array(
                'qid' => 'integer NOT NULL',
                'code' => 'string(5) NOT NULL',
                'answer' => 'text NOT NULL',
                'sortorder' => 'integer NOT NULL',
                'assessment_value' => 'integer NOT NULL DEFAULT 0',
                'language' => "string(20) NOT NULL DEFAULT 'en'",
                'scale_id' => 'integer NOT NULL DEFAULT 0',
            ));

            $oDB->createCommand()->addPrimaryKey('{{answers_pk}}', '{{answers_test}}', ['qid', 'code', 'language', 'scale_id'], false);
            $oDB->createCommand()->createIndex('{{answers_idx2}}', '{{answers_test}}', 'sortorder', false);
            $oDB->createCommand("INSERT INTO {{answers_test}} select * FROM {{answers}}")->execute();

            /**
             * ## Do the action on questions_test ##
             **/
            /* l10ns question table : same than current */
            $oDB->createCommand()->createTable('{{question_l10ns}}', array(
                'id' =>  "pk",
                'qid' =>  "integer NOT NULL",
                'question' =>  "text NOT NULL",
                'help' =>  "text",
                'language' =>  "string(20) NOT NULL"
            ));
            $oDB->createCommand()->createIndex('{{idx1_question_l10ns}}', '{{question_l10ns}}', ['qid', 'language'], true);
            $oDB->createCommand("INSERT INTO {{question_l10ns}} (qid, question, help, language) select qid, question, help, language from {{questions}}")->execute();
            $oDB->createCommand()->renameTable('{{questions_test}}', '{{questions_old}}');
            $oDB->createCommand()->createTable('{{questions_test}}', array(
                'qid' =>  "pk",
                'parent_qid' =>  "integer NOT NULL default '0'",
                'sid' =>  "integer NOT NULL default '0'",
                'gid' =>  "integer NOT NULL default '0'",
                'type' =>  "string(30) NOT NULL default 'T'",
                'title' =>  "string(20) NOT NULL default ''",
                'preg' =>  "text",
                'other' =>  "string(1) NOT NULL default 'N'",
                'mandatory' =>  "string(1) NULL",
                //'encrypted' =>  "string(1) NULL default 'N'", DB version 406
                'question_order' =>  "integer NOT NULL",
                'scale_id' =>  "integer NOT NULL default '0'",
                'same_default' =>  "integer NOT NULL default '0'",
                'relevance' =>  "text",
                'modulename' =>  "string(255) NULL"
            ));
            /* Since qid is unique : can be used, but mysql < 5.7.5 throw error , fix NULL to emty string when needed */
            $oDB->createCommand("INSERT INTO {{questions_test}} (qid, parent_qid, sid, gid, type, title, preg, other, mandatory, question_order, scale_id, same_default, relevance, modulename) SELECT qid, parent_qid, sid, gid, type, title, COALESCE(preg,''), other, COALESCE(mandatory,''), question_order, scale_id, same_default, COALESCE(relevance,''), COALESCE(modulename,'') from {{questions_old}} GROUP BY qid")->execute();
            $oDB->createCommand()->createIndex('{{idx1_questions}}', '{{questions_test}}', 'sid', false);
            $oDB->createCommand()->createIndex('{{idx2_questions}}', '{{questions_test}}', 'gid', false);
            $oDB->createCommand()->createIndex('{{idx3_questions}}', '{{questions_test}}', 'type', false);
            $oDB->createCommand()->createIndex('{{idx4_questions}}', '{{questions_test}}', 'title', false);
            $oDB->createCommand()->createIndex('{{idx5_questions}}', '{{questions_test}}', 'parent_qid', false);
            $oDB->createCommand()->dropTable('{{questions_old}}');

            /* Groups , labels can use same system (i think) */

            /**
             * ## Do the action on answers_test ##
             **/
            $oDB->createCommand()->createTable('{{answer_l10ns}}', array(
                'id' =>  "pk",
                'aid' =>  "integer NOT NULL",
                'answer' =>  "text NOT NULL",
                'language' =>  "string(20) NOT NULL"
            ));
            $oDB->createCommand()->createIndex('{{idx1_answer_l10ns}}', '{{answer_l10ns}}', ['aid', 'language'], true);
            
            $oDB->createCommand()->renameTable('{{answers_test}}', '{{answers_old}}');
            $oDB->createCommand()->createIndex('answer_idx_10', '{{answers_old}}', ['qid', 'code', 'scale_id']);
            $oDB->createCommand()->createTable('{{answers_test}}',[
                'aid' =>  "pk",
                'qid' => 'integer NOT NULL',
                'code' => 'string(5) NOT NULL',
                'sortorder' => 'integer NOT NULL',
                'assessment_value' => 'integer NOT NULL DEFAULT 0',
                'scale_id' => 'integer NOT NULL DEFAULT 0'
            ]);
            /* No pk in insert (not checked in mssql and pgsql … ) according to https://www.w3schools.com/SQl/sql_autoincrement.asp : IDENTITY must do the trick */
            $oDB->createCommand("INSERT INTO {{answers_test}} (qid, code, sortorder, assessment_value, scale_id) SELECT qid, code, sortorder, assessment_value, scale_id FROM {{answers_old}} GROUP BY qid, code, scale_id")->execute();
            /* no pk in insert, get aid by INNER join */
            $oDB->createCommand("INSERT INTO {{answer_l10ns}} (aid, answer, language) SELECT {{answers_test}}.aid, {{answers_old}}.answer, {{answers_old}}.language
                    FROM {{answers_old}}
                    INNER JOIN {{answers_test}}
                    ON {{answers_old}}.qid = {{answers_test}}.qid AND {{answers_old}}.code = {{answers_test}}.code AND {{answers_old}}.scale_id = {{answers_test}}.scale_id");
            $oDB->createCommand()->dropTable('{{answers_old}}');
            $oDB->createCommand()->createIndex('{{answers_idx}}', '{{answers_test}}', ['qid', 'code', 'scale_id'], true);
            $oDB->createCommand()->createIndex('{{answers_idx2}}', '{{answers_test}}', 'sortorder', false);
        }
    }

    /* Copy paste of updatedb_helper function */
    private static  function modifyPrimaryKey($sTablename, $aColumns)
    {
        switch (Yii::app()->db->driverName) {
            case 'mysql':
                Yii::app()->db->createCommand("ALTER TABLE {{".$sTablename."}} DROP PRIMARY KEY, ADD PRIMARY KEY (".implode(',', $aColumns).")")->execute();
                break;
            case 'pgsql':
            case 'sqlsrv':
            case 'dblib':
            case 'mssql':
                $pkquery = "SELECT CONSTRAINT_NAME "
                ."FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS "
                ."WHERE (TABLE_NAME = '{{{$sTablename}}}') AND (CONSTRAINT_TYPE = 'PRIMARY KEY')";

                $primarykey = Yii::app()->db->createCommand($pkquery)->queryRow(false);
                if ($primarykey !== false) {
                    Yii::app()->db->createCommand("ALTER TABLE {{".$sTablename."}} DROP CONSTRAINT ".$primarykey[0])->execute();
                    Yii::app()->db->createCommand("ALTER TABLE {{".$sTablename."}} ADD PRIMARY KEY (".implode(',', $aColumns).")")->execute();
                }
                break;
            default: die('Unknown database type');
        }
    }

    /* Copy paste of updatedb_helper function */
    private static  function dropColumn($sTableName, $sColumnName)
    {
        if (Yii::app()->db->getDriverName()=='mssql' || Yii::app()->db->getDriverName()=='sqlsrv' || Yii::app()->db->getDriverName()=='dblib')
        {
            self::dropDefaultValueMSSQL($sColumnName,$sTableName);
        }
        try {
            Yii::app()->db->createCommand()->dropColumn($sTableName,$sColumnName);
        } catch (Exception $e) {
           // If it cannot be dropped we assume it is already gone
        };
    }

    private static function dropDefaultValueMSSQL($fieldname, $tablename)
    {
        // find out the name of the default constraint
        // Did I already mention that this is the most suckiest thing I have ever seen in MSSQL database?
        $dfquery = "SELECT c_obj.name AS constraint_name
        FROM sys.sysobjects AS c_obj INNER JOIN
        sys.sysobjects AS t_obj ON c_obj.parent_obj = t_obj.id INNER JOIN
        sys.sysconstraints AS con ON c_obj.id = con.constid INNER JOIN
        sys.syscolumns AS col ON t_obj.id = col.id AND con.colid = col.colid
        WHERE (c_obj.xtype = 'D') AND (col.name = '$fieldname') AND (t_obj.name='{$tablename}')";
        $defaultname = Yii::app()->getDb()->createCommand($dfquery)->queryRow();
        if ($defaultname != false) {
            Yii::app()->db->createCommand("ALTER TABLE {$tablename} DROP CONSTRAINT {$defaultname['constraint_name']}")->execute();
        }
    }
}

