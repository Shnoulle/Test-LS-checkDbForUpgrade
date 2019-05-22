<?php
/**
 * Checking some SQL instruction
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2019 Denis Chenu <www.sondages.pro>
 * @license Do What The Fuck You Want To Public License (WTFPL)
 * @version 0.1.0
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
            'help' => 'Checking : you test code for DB update, no checking : you clean database from _test, _i10n and _old table used bby this plugin',
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
        self::_deletePreviousTable();

        if(!empty($settings['duUpdate'])) {
            self::_createTestTable();

            $oDB = Yii::app()->getDb();
            $options = "";
            if(in_array(Yii::app()->db->driverName,['mysql','mysqli'])) {
                $options = 'ROW_FORMAT=DYNAMIC'; // Same than create-database
            }
            /**
             * Do the action on questions_test
             **/
            /* l10ns question table : same than current */
            $oDB->createCommand()->createTable('{{question_l10ns}}', array(
                'id' =>  "pk",
                'qid' =>  "integer NOT NULL",
                'question' =>  "text NOT NULL",
                'help' =>  "text",
                'language' =>  "string(20) NOT NULL"
            ), $options);
            $oDB->createCommand()->createIndex('{{idx1_question_l10ns}}', '{{question_l10ns}}', ['qid', 'language'], true);
            $oDB->createCommand("INSERT INTO {{question_l10ns}}
                (qid, question, help, language)
                select qid, question, help, language
                from {{questions}}
            ")->execute();
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
            ), $options);
            /* Be sure to have empty string (because set when create question) */
            switchMSSQLIdentityInsert('questions_test', true); // Untested
            $oDB->createCommand("INSERT INTO {{questions_test}}
                (qid, parent_qid, sid, gid, type, title, preg, other, mandatory, question_order, scale_id, same_default, relevance, modulename)
                SELECT qid, parent_qid, {{questions_old}}.sid, gid, type, title, COALESCE(preg,''), other, COALESCE(mandatory,''), question_order, scale_id, same_default, COALESCE(relevance,''), COALESCE(modulename,'')
                FROM {{questions_old}}
                    INNER JOIN {{surveys}} ON {{questions_old}}.sid = {{surveys}}.sid AND {{questions_old}}.language = {{surveys}}.language
            ")->execute();
            switchMSSQLIdentityInsert('questions_test', false); // Untested
            $oDB->createCommand()->dropTable('{{questions_old}}'); // Drop the table before create index for pgsql
            $oDB->createCommand()->createIndex('{{idx1_questions_test}}', '{{questions_test}}', 'sid', false);
            $oDB->createCommand()->createIndex('{{idx2_questions_test}}', '{{questions_test}}', 'gid', false);
            $oDB->createCommand()->createIndex('{{idx3_questions_test}}', '{{questions_test}}', 'type', false);
            $oDB->createCommand()->createIndex('{{idx4_questions_test}}', '{{questions_test}}', 'title', false);
            $oDB->createCommand()->createIndex('{{idx5_questions_test}}', '{{questions_test}}', 'parent_qid', false);
            /**
             * Do the action on groups_test
             **/
            $oDB->createCommand()->createTable('{{group_l10ns}}', array(
                'id' =>  "pk",
                'gid' =>  "integer NOT NULL",
                'group_name' =>  "text NOT NULL",
                'description' =>  "text",
                'language' =>  "string(20) NOT NULL"
            ), $options);
            $oDB->createCommand()->createIndex('{{idx1_group_l10ns}}', '{{group_l10ns}}', ['gid', 'language'], true);
            $oDB->createCommand("INSERT INTO {{group_l10ns}} (gid, group_name, description, language) select gid, group_name, description, language from {{groups}}")->execute();
            $oDB->createCommand()->renameTable('{{groups_test}}', '{{groups_old}}');
            $oDB->createCommand()->createTable('{{groups_test}}', array(
                'gid' =>  "pk",
                'sid' =>  "integer NOT NULL default '0'",
                'group_order' =>  "integer NOT NULL default '0'",
                'randomization_group' =>  "string(20) NOT NULL default ''",
                'grelevance' =>  "text NULL"
            ), $options);
            switchMSSQLIdentityInsert('groups_test', true); // Untested
            $oDB->createCommand("INSERT INTO {{groups_test}}
                (gid, sid, group_order, randomization_group, grelevance)
                SELECT gid, {{groups_old}}.sid, group_order, randomization_group, COALESCE(grelevance,'')
                FROM {{groups_old}}
                    INNER JOIN {{surveys}} ON {{groups_old}}.sid = {{surveys}}.sid AND {{groups_old}}.language = {{surveys}}.language
                ")->execute();
            switchMSSQLIdentityInsert('groups_test', false); // Untested
            $oDB->createCommand()->dropTable('{{groups_old}}'); // Drop the table before create index for pgsql
            $oDB->createCommand()->createIndex('{{idx1_groups_test}}', '{{groups_test}}', 'sid', false);
            /**
             * ## Do the action on answers_test ##
             **/

            $oDB->createCommand()->createTable('{{answer_l10ns}}', array(
                'id' =>  "pk",
                'aid' =>  "integer NOT NULL",
                'answer' =>  "text NOT NULL",
                'language' =>  "string(20) NOT NULL"
            ), $options);
            $oDB->createCommand()->createIndex('{{idx1_answer_l10ns}}', '{{answer_l10ns}}', ['aid', 'language'], true);
            /* Renaming old without pk answers */
            $oDB->createCommand()->renameTable('{{answers_test}}', '{{answers_old}}');
            /* Create new answers with pk and copy answers_old Grouping by unique part */
            $oDB->createCommand()->createTable('{{answers_test}}',[
                'aid' =>  "pk",
                'qid' => 'integer NOT NULL',
                'code' => 'string(5) NOT NULL',
                'sortorder' => 'integer NOT NULL',
                'assessment_value' => 'integer NOT NULL DEFAULT 0',
                'scale_id' => 'integer NOT NULL DEFAULT 0'
            ], $options);
            $oDB->createCommand()->createIndex('answer_idx_10', '{{answers_old}}', ['qid', 'code', 'scale_id']);
            /* No pk in insert (not checked in mssql and pgsql … ) according to https://www.w3schools.com/SQl/sql_autoincrement.asp : IDENTITY must do the trick */
            $oDB->createCommand("INSERT INTO {{answers_test}}
                (qid, code, sortorder, assessment_value, scale_id)
                SELECT {{answers_old}}.qid, {{answers_old}}.code, {{answers_old}}.sortorder, {{answers_old}}.assessment_value, {{answers_old}}.scale_id
                FROM {{answers_old}}
                    INNER JOIN {{questions_test}} ON {{answers_old}}.qid = {{questions_test}}.qid
                    INNER JOIN {{surveys}} ON {{questions_test}}.sid = {{surveys}}.sid AND {{surveys}}.language = {{answers_old}}.language
                ")->execute();
            /* no pk in insert, get aid by INNER join */
            $oDB->createCommand("INSERT INTO {{answer_l10ns}} (aid, answer, language) SELECT {{answers_test}}.aid, {{answers_old}}.answer, {{answers_old}}.language
                    FROM {{answers_old}}
                    INNER JOIN {{answers_test}}
                    ON {{answers_old}}.qid = {{answers_test}}.qid AND {{answers_old}}.code = {{answers_test}}.code AND {{answers_old}}.scale_id = {{answers_test}}.scale_id");
            $oDB->createCommand()->dropTable('{{answers_old}}');
            $oDB->createCommand()->createIndex('{{answers_idx}}', '{{answers_test}}', ['qid', 'code', 'scale_id'], true);
            $oDB->createCommand()->createIndex('{{answers_idx2}}', '{{answers_test}}', 'sortorder', false);

            /**
             * Labels
             **/
            $oDB->createCommand()->createTable('{{label_l10ns}}', array(
                'id' =>  "pk",
                'label_id' =>  "integer NOT NULL",
                'title' =>  "text",
                'language' =>  "string(20) NOT NULL DEFAULT 'en'"
            ), $options);
            $oDB->createCommand()->createIndex('{{idx1_label_l10ns}}', '{{label_l10ns}}', ['label_id', 'language'], true);
            $oDB->createCommand("INSERT INTO {{label_l10ns}}
                (label_id, title, language)
                SELECT {{labels_test}}.id, title, language
                FROM {{labels_test}}
                ")->execute();
            $oDB->createCommand()->renameTable('{{labels_test}}', '{{labels_old}}');
            $oDB->createCommand()->createTable('{{labels_test}}',[
                'id' =>  "pk",
                'lid' => 'integer NOT NULL',
                'code' => 'string(5) NOT NULL',
                'sortorder' => 'integer NOT NULL',
                'assessment_value' => 'integer NOT NULL DEFAULT 0'
            ], $options);
            switchMSSQLIdentityInsert('labels_test', true); // Untested
            $oDB->createCommand("INSERT INTO {{labels_test}}
                (id, lid, code, sortorder, assessment_value)
                SELECT id, lid, code, sortorder, assessment_value
                FROM {{labels_old}} GROUP BY id
                ")->execute();
            switchMSSQLIdentityInsert('labels_test', false); // Untested
            $oDB->createCommand()->dropTable('{{labels_old}}');
            
            /**
             * Default values
             **/
            $oDB->createCommand()->createTable('{{defaultvalue_l10ns}}', array(
                'id' =>  "pk",
                'dvid' =>  "integer NOT NULL default '0'",
                'language' =>  "string(20) NOT NULL",
                'defaultvalue' =>  "text",
            ), $options);
            $oDB->createCommand()->createIndex('{{idx1_defaultvalue_l10ns}}', '{{defaultvalue_l10ns}}', ['dvid', 'language'], true);
            $oDB->createCommand()->renameTable('{{defaultvalues_test}}', '{{defaultvalues_old}}');
            $oDB->createCommand()->createIndex('defaultvalues_old_idx_10', '{{defaultvalues_old}}', ['qid', 'scale_id', 'sqid', 'specialtype', 'language']);
            $oDB->createCommand()->createTable('{{defaultvalues_test}}',[
                'dvid' =>  "pk",
                'qid' =>  "integer NOT NULL default '0'",
                'scale_id' =>  "integer NOT NULL default '0'",
                'sqid' =>  "integer NOT NULL default '0'",
                'specialtype' =>  "string(20) NOT NULL default ''",
            ], $options);
            $oDB->createCommand("INSERT INTO {{defaultvalues_test}}
                (qid, scale_id, sqid, specialtype)
                SELECT {{defaultvalues_old}}.qid, {{defaultvalues_old}}.scale_id, {{defaultvalues_old}}.sqid, {{defaultvalues_old}}.specialtype
                FROM {{defaultvalues_old}}
                    INNER JOIN {{questions_test}} ON {{defaultvalues_old}}.qid = {{questions_test}}.qid
                    INNER JOIN {{surveys}} ON {{questions_test}}.sid = {{surveys}}.sid AND {{surveys}}.language = {{defaultvalues_old}}.language
                ")->execute();
            $oDB->createCommand()->createIndex('{{idx1_defaultvalue_test}}', '{{defaultvalues_test}}', ['qid', 'scale_id', 'sqid', 'specialtype'], false);
            $oDB->createCommand()->dropTable('{{defaultvalues_old}}');

        }
    }

    /* Copy paste of updatedb_helper function
     * Unused
     **/
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

    /* Copy paste of updatedb_helper function
     * Unused
     **/
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

    /* Copy paste of updatedb_helper function
     * Unused
     **/
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

    /**
     * delete previous table
     */
    private static function _deletePreviousTable()
    {
        $oDB = Yii::app()->getDb();
        if(Yii::app()->db->schema->getTable('{{questions_test}}')){
            $oDB->createCommand()->dropTable('{{questions_test}}');
        }
        if(Yii::app()->db->schema->getTable('{{question_l10ns}}')){
            $oDB->createCommand()->dropTable('{{question_l10ns}}');
        }
        if(Yii::app()->db->schema->getTable('{{groups_test}}')){
            $oDB->createCommand()->dropTable('{{groups_test}}');
        }
        if(Yii::app()->db->schema->getTable('{{group_l10ns}}')){
            $oDB->createCommand()->dropTable('{{group_l10ns}}');
        }
        if(Yii::app()->db->schema->getTable('{{answers_test}}')){
            $oDB->createCommand()->dropTable('{{answers_test}}');
        }
        if(Yii::app()->db->schema->getTable('{{answer_l10ns}}')){
            $oDB->createCommand()->dropTable('{{answer_l10ns}}');
        }
        if(Yii::app()->db->schema->getTable('{{labels_test}}')){
            $oDB->createCommand()->dropTable('{{labels_test}}');
        }
        if(Yii::app()->db->schema->getTable('{{label_l10ns}}')){
            $oDB->createCommand()->dropTable('{{label_l10ns}}');
        }
        if(Yii::app()->db->schema->getTable('{{defaultvalues_test}}')){
            $oDB->createCommand()->dropTable('{{defaultvalues_test}}');
        }
        if(Yii::app()->db->schema->getTable('{{defaultvalue_l10ns}}')){
            $oDB->createCommand()->dropTable('{{defaultvalue_l10ns}}');
        }

        /* Must not happen if don't broke, but can broke when testing code */
        if(Yii::app()->db->schema->getTable('{{questions_old}}')){
            $oDB->createCommand()->dropTable('{{questions_old}}');
        }
        if(Yii::app()->db->schema->getTable('{{answers_old}}')){
            $oDB->createCommand()->dropTable('{{answers_old}}');
        }
        if(Yii::app()->db->schema->getTable('{{groups_old}}')){
            $oDB->createCommand()->dropTable('{{groups_old}}');
        }
        if(Yii::app()->db->schema->getTable('{{labels_old}}')){
            $oDB->createCommand()->dropTable('{{labels_old}}');
        }
        if(Yii::app()->db->schema->getTable('{{defaultvalues_old}}')){
            $oDB->createCommand()->dropTable('{{defaultvalues_old}}');
        }
    }

    /**
     * Create test table : same table than 3.X installer with data from current table
     */
    private static function _createTestTable()
    {
        $oDB = Yii::app()->getDb();
        /* question */
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
        $oDB->createCommand()->createIndex('{{idx1_questions_test}}', '{{questions_test}}', 'sid', false);
        $oDB->createCommand()->createIndex('{{idx2_questions_test}}', '{{questions_test}}', 'gid', false);
        $oDB->createCommand()->createIndex('{{idx3_questions_test}}', '{{questions_test}}', 'type', false);
        $oDB->createCommand()->createIndex('{{idx4_questions_test}}', '{{questions_test}}', 'title', false);
        $oDB->createCommand()->createIndex('{{idx5_questions_test}}', '{{questions_test}}', 'parent_qid', false);
        switchMSSQLIdentityInsert('questions_test', true);
        $oDB->createCommand("INSERT INTO {{questions_test}}
            (qid, parent_qid, sid, gid, type, title, question, preg, help, other, mandatory, question_order, language, scale_id, same_default, relevance, modulename)
            select
            qid, parent_qid, sid, gid, type, title, question, preg, help, other, mandatory, question_order, language, scale_id, same_default, relevance, modulename
            FROM {{questions}}")->execute();
        switchMSSQLIdentityInsert('questions_test', false);

        /* groups */
        $oDB->createCommand()->createTable('{{groups_test}}', array(
            'gid' =>  "autoincrement",
            'sid' =>  "integer NOT NULL default '0'",
            'group_name' =>  "string(100) NOT NULL default ''",
            'group_order' =>  "integer NOT NULL default '0'",
            'description' =>  "text",
            'language' =>  "string(20) default 'en' NOT NULL",
            'randomization_group' =>  "string(20) NOT NULL default ''",
            'grelevance' =>  "text NULL",
            'composite_pk' => array('gid', 'language')
        ));
        $oDB->createCommand()->createIndex('{{idx1_groups_test}}', '{{groups_test}}', 'sid', false);
        $oDB->createCommand()->createIndex('{{idx2_groups_test}}', '{{groups_test}}', 'group_name', false);
        $oDB->createCommand()->createIndex('{{idx3_groups_test}}', '{{groups_test}}', 'language', false);
        switchMSSQLIdentityInsert('groups_test', true);
        $oDB->createCommand("INSERT INTO {{groups_test}}
            (gid, sid, group_name,group_order, description, language, randomization_group, grelevance)
            select
            gid, sid, group_name,group_order, description, language, randomization_group, grelevance
            FROM {{groups}}")->execute();
        switchMSSQLIdentityInsert('groups_test', false);

        /* answers */
        $oDB->createCommand()->createTable('{{answers_test}}', array(
            'qid' => 'integer NOT NULL',
            'code' => 'string(5) NOT NULL',
            'answer' => 'text NOT NULL',
            'sortorder' => 'integer NOT NULL',
            'assessment_value' => 'integer NOT NULL DEFAULT 0',
            'language' => "string(20) NOT NULL DEFAULT 'en'",
            'scale_id' => 'integer NOT NULL DEFAULT 0',
        ));
        $oDB->createCommand()->addPrimaryKey('{{answers_pk_test}}', '{{answers_test}}', ['qid', 'code', 'language', 'scale_id'], false);
        $oDB->createCommand()->createIndex('{{answers_idx2_test}}', '{{answers_test}}', 'sortorder', false);
        switchMSSQLIdentityInsert('answers_test', true);
        $oDB->createCommand("INSERT INTO {{answers_test}}
            (qid,code,answer,sortorder,assessment_value,language,scale_id)
            select
            qid,code,answer,sortorder,assessment_value,language,scale_id
        FROM {{answers}}")->execute();
        switchMSSQLIdentityInsert('answers_test', false);

        /* labels */
        $oDB->createCommand()->createTable('{{labels_test}}', array(
            'id' =>  "pk",
            'lid' =>  "integer NOT NULL DEFAULT 0",
            'code' =>  "string(5) NOT NULL default ''",
            'title' =>  "text",
            'sortorder' =>  "integer NOT NULL",
            'language' =>  "string(20) NOT NULL DEFAULT 'en'",
            'assessment_value' =>  "integer NOT NULL default '0'",
        ));

        $oDB->createCommand()->createIndex('{{idx1_labels_test}}', '{{labels_test}}', 'code', false);
        $oDB->createCommand()->createIndex('{{idx2_labels_test}}', '{{labels_test}}', 'sortorder', false);
        $oDB->createCommand()->createIndex('{{idx3_labels_test}}', '{{labels_test}}', 'language', false);
        $oDB->createCommand()->createIndex('{{idx4_labels_test}}', '{{labels_test}}', ['lid','sortorder','language'], false);
        switchMSSQLIdentityInsert('labels_test', true);
        $oDB->createCommand("INSERT INTO {{labels_test}}
            (id, lid, code, title, sortorder, language, assessment_value)
            select
            id, lid, code, title, sortorder, language, assessment_value
        FROM {{labels}}")->execute();
        switchMSSQLIdentityInsert('labels_test', false);

        /* defaultvalues */
        $oDB->createCommand()->createTable('{{defaultvalues_test}}', array(
            'qid' =>  "integer NOT NULL default '0'",
            'scale_id' =>  "integer NOT NULL default '0'",
            'sqid' =>  "integer NOT NULL default '0'",
            'language' =>  "string(20) NOT NULL",
            'specialtype' =>  "string(20) NOT NULL default ''",
            'defaultvalue' =>  "text",
        ));
        $oDB->createCommand()->addPrimaryKey('{{defaultvalues_test_pk}}', '{{defaultvalues_test}}', ['qid', 'specialtype', 'language', 'scale_id', 'sqid'], false);
        switchMSSQLIdentityInsert('defaultvalues_test', true);
        $oDB->createCommand("INSERT INTO {{defaultvalues_test}}
            (qid, scale_id, sqid, language, specialtype, defaultvalue)
            select
            qid, scale_id, sqid, language, specialtype, defaultvalue
        FROM {{defaultvalues}}")->execute();
        switchMSSQLIdentityInsert('defaultvalues_test', false);

    }
}

