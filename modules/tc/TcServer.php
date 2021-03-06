<?php

require_once "TcApi.php";

class TcServer
{
    public $id,$type,$enabled,$server_key,$username,$password;
    public $api_url,$webapp,$max_rooms,$max_users,$enable_recordings,$weight,$screenshare,$all_courses;

    public function __construct($data)
    {
        if ( is_object($data)) {
            $me = new ReflectionClass($this);
            foreach ($me->getProperties() as $property)
            {
                $propname = $property->getName();
                if ( property_exists($data,$propname) )
                    $this->$propname = $data->$propname;
            }
        }
        elseif ( is_array($data) ) {
            die('['.__METHOD__.'] Initialization by array currently unimplemented.');
        }
    }

    public static function LoadById($id)
    {
        $r = Database::get()->querySingle("SELECT * FROM tc_servers WHERE id = ?d", $id);
        return $r ? new TcServer($r) : false;
    }

    public static function LoadOneByTypes($types, $enabledOnly = false)
    {
        if ($enabledOnly)
            $enabledOnly = " AND enabled=1 ";

        if (! is_array($types)) {
            $types = array(
                $types
            );
        }
        array_walk($types,function(&$value) { $value = '"'.$value.'"'; });
        $types = implode(',',$types);
        //TODO: FIX Database to support IN() - probably by supporting arrays as values to bind
        $r = Database::get()->querySingle("SELECT * FROM tc_servers WHERE `type` IN ($types)" . $enabledOnly . " ORDER BY weight ASC");
        return $r ? new TcServer($r) : false;
    }

    public static function LoadOneByCourse($course_id)
    {
        $r = Database::get()->querySingle("SELECT id FROM course_external_server WHERE course_id=?d", $course_id);
        if ($r)
            return self::LoadById($r);
        return false;
    }

    public static function LoadAllByTypes($types, $enabledOnly = false)
    {
        
        if ($enabledOnly)
            $enabledOnly = " AND enabled=1 ";

        if (! is_array($types)) {
            $types = array(
                $types
            );
        }
        array_walk($types,function(&$value) { $value = '"'.$value.'"'; });
        $types = implode(',',$types);
        //TODO: FIX Database to support IN() - probably by supporting arrays as values to bind
        
        $r = Database::get()->queryArray("SELECT * FROM tc_servers WHERE `type` IN ($types)" . $enabledOnly . "ORDER BY weight ASC");
        $s = [];
        if ($r) {
            foreach ($r as $rr) {
                $s[] = new TcServer($rr);
            }
        }
        return $s;
    }

    public static function LoadAll($enabledOnly = false)
    {
        if ($enabledOnly)
            $enabledOnly = " WHERE enabled=1 ";

        $r = Database::get()->queryArray("SELECT * FROM tc_servers" . $enabledOnly . " ORDER BY weight ASC");
        $s = [];
        if ($r) {
            foreach ($r as $rr) {
                $s[] = new TcServer($rr);
            }
        }
        return $s;
    }

    public function recording()
    {
        return $this->enable_recordings;
    }

    public function enabled()
    {
        return $this->enabled;
    }

    public function get_connected_users()
    {
        $api = $this->getApi();
        try {
            $x = $api->getServerUsers($this);
            return $x;
        }
        catch(Exception $e) {
            return "Error: ".$e->getMessage();
        }
    }
    
    public function available($users_to_join=null) {
        if ( !$this->enabled )
            return false;
        if ( $users_to_join !== null ) {
            $api = $this->getApi();
            $x = $api->getServerUsers($this);
            if ( $x )
                return $this->max_users>$users_to_join;
            else
                return true;
        }
    }
        
    public function getApi() {
        $className = TcApi::AVAILABLE_APIS[$this->type];
        require_once $this->type.'-api.php';
        return new $className(['server'=>$this]);
    }
}


/*
 * DB migration:
ALTER TABLE `tc_servers`
	CHANGE COLUMN `type` `type` VARCHAR(255) NOT NULL COLLATE 'utf8_general_ci' AFTER `id`,
	CHANGE COLUMN `enabled` `enabled` TINYINT(1) NOT NULL DEFAULT '0' COLLATE 'utf8_general_ci' AFTER `port`,
	CHANGE COLUMN `server_key` `server_key` VARCHAR(255) NOT NULL COLLATE 'utf8_general_ci' AFTER `enabled`,
	CHANGE COLUMN `api_url` `api_url` VARCHAR(255) NOT NULL COLLATE 'utf8_general_ci' AFTER `password`,
	CHANGE COLUMN `enable_recordings` `enable_recordings` TINYINT(1) NULL DEFAULT '0' COLLATE 'utf8_general_ci' AFTER `max_users`;
ALTER TABLE `tc_servers`
	DROP INDEX `idx_tc_servers`;
ALTER TABLE `tc_servers`
	CHANGE COLUMN `port` `port` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `ip`;
ALTER TABLE `tc_servers`
	CHANGE COLUMN `ip` `ip` VARCHAR(255) NULL DEFAULT NULL AFTER `hostname`;
ALTER TABLE `tc_servers`
	DROP COLUMN `hostname`,
	DROP COLUMN `ip`,
	DROP COLUMN `port`;
*/