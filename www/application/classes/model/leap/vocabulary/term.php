<?php
/**
 * Created by JetBrains PhpStorm.
 * User: larjohns
 * Date: 13/9/2012
 * Time: 3:11 μμ
 * To change this template use File | Settings | File Templates.
 */
class Model_Leap_Vocabulary_Term extends  DB_ORM_Model
{

    const Property = "property";

    const Relation = "rel";

    const Reverse = "rev";


    public function __construct() {
        parent::__construct();

        $this->fields = array(
            'id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
                'unsigned' => TRUE,
            )),
            'name' => new DB_ORM_Field_String($this, array(
                'max_length' => 500,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            'vocab_id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            'type' => new DB_ORM_Field_String($this, array(
                'max_length' => 500,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            'term_label' => new DB_ORM_Field_String($this, array(
                'max_length' => 500,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
        );


        $this->relations = array(


            'vocabulary' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('vocab_id'),
                'parent_key' => array('id'),
                'parent_model' => 'vocabulary',
            )),

            'mappings' => new DB_ORM_Relation_HasMany($this, array(
                    'child_key' => array('term_id'),
                    'child_model' => 'vocabulary_mapping',
                    'parent_key' => array('id'),
             )),


        );
    }

    public static function data_source() {
        return 'default';
    }

    public static function table() {
        return 'rdf_terms';
    }

    public static function primary_key() {
        return array('id');
    }

    public function getFullRepresentation(){
        return $this->vocabulary->namespace . $this->name;
    }

    public static function getAll(){
        $builder = DB_SQL::select('default')->from(self::table());

        $result = $builder->query();

        if ($result->is_loaded()) {
            $terms = array();

            foreach ($result as $record) {
                $terms[] = DB_ORM::model('vocabulary_term', array((int)$record['id']));
            }

            return $terms;
        }
        return array();
    }


    public function newTerm($uri, $label=""){
        $namespace = self::guessNamespace($uri);
        $name = self::guessName($uri);
        if($name == "")return;
        $vocab  = Model_Leap_Vocabulary::getVocabularyByNamespace($namespace);
        if($vocab===NULL){

            $vocab = DB_ORM_Model::factory("vocabulary");
            $vocab->newVocabulary($namespace);
        }
        $builder = DB_SQL::select('default')
            ->from($this->table())
            ->where('vocab_id', '=', $vocab->id)
            ->where('name', '=', $name);
        $result = $builder->query();
        $this->name = $name;
        $this->term_label = $label;

        $this->vocab_id  = $vocab->id;

        if ($result->is_loaded()) {

            $this->id =   $result[0]["id"];

            $this->load();

            $this->term_label = $label;
            //$property = DB_ORM::model('vocabulary_term', array($id));

            //var_dump($this);
            //return $result[0];
        }



        $this->save();

        return $this;
    }

    /**
     * Extracts the namespace prefix out of a URI.
     *
     * @param	String	$uri
     * @return	string
     * @access	public
     */
    public static  function guessNamespace($uri) {
        $l = self::getNamespaceEnd($uri);
        return $l > 1 ? substr($uri ,0, $l) : "";
    }

    /**
     * Delivers the name out of the URI (without the namespace prefix).
     *
     * @param	String	$uri
     * @return	string
     * @access	public
     */
   public static  function guessName($uri) {
        return substr($uri,self::getNamespaceEnd($uri));
    }


    /**
     * Position of the namespace end
     * Method looks for # : and /
     * @access	private
     */
    static  function getNamespaceEnd($uri) {
        $l = strlen($uri)-1;
        do {
            $c = substr($uri, $l, 1);
            if($c == '#' || $c == ':' || $c == '/')
                break;
            $l--;
        } while ($l >= 0);
        $l++;
        return $l;
    }

}
