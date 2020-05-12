<?php
require_once "paramsTrait.php";

abstract class TcApi
{
    //TODO: Set this up in config, needs admin UI additions
    const AVAILABLE_APIS = [
        'bbb' => 'BigBlueButton',
        'om' => 'OpenMeetings',
        'webconf' => 'WebConf',
        'zoom' => 'Zoom'
    ];

    protected static $_cache;

    protected static function cacheStore($key, $data)
    {
        if (is_array($key))
            $key = md5(implode('_', $key));
        self::$_cache[$key] = $data;
    }

    protected static function cacheLoad($key)
    {
        if (is_array($key))
            $key = md5(implode('_', $key));
        if (self::$_cache && array_key_exists($key, self::$_cache))
            return self::$_cache[$key];
        else
            return null;
    }

    protected function cacheClear($key = null)
    {
        if (is_array($key))
            $key = md5(implode('_', $key));
        unset(self::$_cache[$key]);
    }

    public abstract function __construct($params = []);

    /*
     * USAGE:
     * $creationParams = array(
     * 'name' => 'Meeting Name', -- A name for the meeting (or username)
     * 'meetingId' => '1234', -- A unique id for the meeting
     * 'attendeePw' => 'ap', -- Set to 'ap' and use 'ap' to join = no user pass required.
     * 'moderatorPw' => 'mp', -- Set to 'mp' and use 'mp' to join = no user pass required.
     * 'welcomeMsg' => '', -- ''= use default. Change to customize.
     * 'dialNumber' => '', -- The main number to call into. Optional.
     * 'voiceBridge' => '', -- PIN to join voice. Optional.
     * 'webVoice' => '', -- Alphanumeric to join voice. Optional.
     * 'logoutUrl' => '', -- Default in bigbluebutton.properties. Optional.
     * 'maxParticipants' => '-1', -- Optional. -1 = unlimitted. Not supported in BBB. [number]
     * 'record' => 'false', -- New. 'true' will tell BBB to record the meeting.
     * 'duration' => '0', -- Default = 0 which means no set duration in minutes. [number]
     * 'meta_category' => '', -- Use to pass additional info to BBB server. See API docs to enable.
     * );
     */
    public abstract function createMeeting($creationParams);

    /*
     * USAGE:
     * $endParams = array (
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public abstract function endMeeting($endParams);

    
    /**
     * Delete a meeting, this usually means delete a scheduled meeting. Service may forcefully end a live meeting.
     * @param mixed $deleteParams
     */
    public abstract function deleteMeeting($deleteParams);
    
    /*
     *
     * @param string $meetingId
     * @return boolean
     */
    public abstract function isMeetingRunning($meetingId);

    /*
     * Simply formulate the getMeetings URL
     * We do this in a separate function so we have the option to just get this
     * URL and print it if we want for some reason.
     */
    public abstract function getMeetings();

    /*
     * USAGE:
     * $infoParams = array(
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public abstract function getMeetingInfo($infoParams);

    /*
     * $result = array(
     * 'returncode' => $xml->returncode,
     * 'meetingName' => $xml->meetingName,
     * 'meetingId' => $xml->meetingID,
     * 'createTime' => $xml->createTime,
     * 'voiceBridge' => $xml->voiceBridge,
     * 'attendeePw' => $xml->attendeePW,
     * 'moderatorPw' => $xml->moderatorPW,
     * 'running' => $xml->running,
     * 'recording' => $xml->recording,
     * 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded,
     * 'startTime' => $xml->startTime,
     * 'endTime' => $xml->endTime,
     * 'participantCount' => $xml->participantCount,
     * 'maxUsers' => $xml->maxUsers,
     * 'moderatorCount' => $xml->moderatorCount,
     * );
     * // Then interate through attendee results and return them as part of the array:
     * foreach ($xml->attendees->attendee as $a) {
     * $result[] = array(
     * 'userId' => $a->userID,
     * 'fullName' => $a->fullName,
     * 'role' => $a->role
     * );
     * }
     */

    /*
     * USAGE:
     * $recordingParams = array(
     * 'meetingId' => '1234', -- OPTIONAL - comma separate if multiple ids
     * );
     */
    public abstract function getRecordings($recordingParams);

    /*
     * foreach ($xml->recordings->recording as $r) {
     * $result[] = array(
     * 'recordId' => $r->recordID,
     * 'meetingId' => $r->meetingID,
     * 'name' => $r->name,
     * 'published' => $r->published,
     * 'startTime' => $r->startTime,
     * 'endTime' => $r->endTime,
     * 'playbackFormatType' => $r->playback->format->type,
     * 'playbackFormatUrl' => $r->playback->format->url,
     * 'playbackFormatLength' => $r->playback->format->length,
     * 'metadataTitle' => $r->metadata->title,
     * 'metadataSubject' => $r->metadata->subject,
     * 'metadataDescription' => $r->metadata->description,
     * 'metadataCreator' => $r->metadata->creator,
     * 'metadataContributor' => $r->metadata->contributor,
     * 'metadataLanguage' => $r->metadata->language,
     * // Add more here as needed for your app depending on your
     * // use of metadata when creating recordings.
     * );
     * }
     */

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * 'publish' => 'true', -- REQUIRED - boolean: true/false
     * );
     */
    public abstract function publishRecordings($recordingParams);

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * );
     */
    public abstract function deleteRecordings($recordingParams);

    public static abstract function generatePassword();

    public static abstract function generateMeetingId();

    public abstract function getServerUsers(TcServer $server);
}

/**
 *
 * @author User
 *        
 */
abstract class TcSession
{

    public $id;

    private $is_new = true;

    public function __construct($params = [])
    {
        $this->is_new = true;
        if (array_key_exists('id', $params)) {
            $this->id = $params['id'];
        }
    }

    /*
     * public function LoadById($id) {
     * $this->is_new = false;
     * return $this;
     * }
     */
    public abstract function disable();

    public abstract function enable();

    public function delete()
    {
        $this->is_new = false;
        return true;
    }

    public abstract function IsKnownToServer();

    public abstract function IsRunning();

    public abstract function join_user(array $joinParams);

    /**
     * Create the meeting on the service. This should also eventually update a local record if one is needed (e.g. a database)
     */
    public abstract function createMeeting();

    public abstract function startMeeting();

    /**
     * This function should update both local and remote
     */
    public function save()
    {
        $this->is_new = false;
        return true;
    }

    /**
     * This function "loads" based on the session_id from disk, db, remote, etc
     */
    public function load()
    {
        $this->is_new = false;
        return true;
    }
    
    public abstract function isIdentifiableToRemote();
}

/**
 *
 * @author User
 *        
 */
class TcDbSession extends TcSession
{
    use paramsTrait;

    
    public $id,$course_id,$meeting_id;
    public $title,$description,$start_date,$end_date,$public,$active,$running_at,$mod_pw,$att_pw,$unlock_interval;
    public $external_users,$participants,$record,$sessionUsers;
    
    private $params = [
        'required' => [],
        'optional' => [
            'id',
            'course_id',
            'meeting_id',

            'title',
            'description',
            'start_date',
            'end_date',
            'public:bool',
            'active:bool',
            'running_at:integer',
            'mod_pw',
            'att_pw',
            'unlock_interval',
            'external_users',
            'participants',
            'record:bool',
            'sessionUsers:integer'
        ]
    ];

    // TcServer data cache
    public $server;

    public function LoadById($id = null)
    {
        if (! $id) {
            if (! $this->id)
                throw new RuntimeException('[TC API] Unable to load session without session id.');
        } else {
            $this->id = $id;
        }
        if ($this->load() )
            return $this;
        return false;
    }

    // FIXME: meeting_id is not a unique identifier across types
    public function LoadByMeetingId($id)
    {
        $this->meeting_id = $id;
        $data = $this->_loadFromDB("SELECT * FROM tc_session WHERE meeting_id = ?d", $this->meeting_id,$this->params);
        if ($data) {
            //TODO: Sigh
            $this->public = $this->public == '1';
            $this->active = $this->active == '1';
            $this->record = $this->record == 'true';
            $this->sessionUsers = (int) $this->sessionUsers;
            
            $this->server = TcServer::LoadById($this->running_at);
            if ( parent::load() )
                return $this;
        }
        return false;
    }

    function __construct(array $params = [])
    {
        parent::__construct($params);

        if ($this->id) {
            if (! $this->load() ) // This fills in $this->data->id (same as session_id)
                throw new RuntimeException('Failed to load session with id '.$this->id);
        }

        if (count($params) > 0) {
            $validparams = $this->_checkParams($this->params, $params);
            foreach ($validparams as $n => $v) {
                $this->{$n} = $v;
            }
        }
    }

    /**
     *
     * @throws Exception
     * @return int|boolean
     */
    private function getRunningServerId()
    {
        if ($this->id)
            $res = Database::get()->querySingle("SELECT running_at FROM tc_session WHERE id = ?s", $this->id);
        elseif ($this->meeting_id)
            $res = Database::get()->querySingle("SELECT running_at FROM tc_session WHERE meeting_id = ?s", $this->meeting_id);
        if ($res) {
            return $res->running_at;
        } else {
            throw new Exception("Failed to get running server!");
        }
        return false;
    }

    /**
     *
     * @brief Disable bbb session (locally)
     * @return bool
     */
    function disable()
    {
        $x = Database::get()->querySingle("UPDATE tc_session set active='0' WHERE id=?d", $this->id);
        return $x !== NULL;
    }

    /**
     *
     * @brief enable bbb session (locally)
     * @return bool
     */
    function enable()
    {
        $x = Database::get()->querySingle("UPDATE tc_session SET active='1' WHERE id=?d", $this->id);
        return $x !== NULL;
    }

    /**
     *
     * @brief delete bbb sessions (locally)
     * @return bool
     */
    function delete()
    {
        $q = Database::get()->querySingle("DELETE FROM tc_session WHERE id = ?d", $this->id);
        if ($q === null) // false is returned when deletion is successful
            return false;
        Log::record($this->course_id, MODULE_ID_TC, LOG_DELETE, array(
            'id' => $this->id,
            'title' => $this->title
        ));
        unset($this->id); //ensure we're totally gone in case an idiot reuses this object
        return true;
    }
    
    function forget() {
        return $this->delete();
    }

    /**
     *
     * @brief check if session is running (locally)
     * @return boolean
     */
    function IsRunningInDB()
    {
        $server = TcServer::LoadById($this->running_at);

        if (! $server)
            die('Server not found for meeting id  ' . $this->meeting_id);

        if (! $this->running_at)
            return false;

        /*
         * if ($server->type != $this->tc_type)
         * die('Error: mismatched session and server type for meeting id ' . $meeting_id);
         */

        return $server->enabled;
    }

    /**
     *
     * @brief Check is this session is known to server (scheduled)
     * @return boolean
     */
    public function IsKnownToServer()
    {
        return false;
    }

    /**
     *
     * @brief check if session is running (locally)
     * @return boolean
     */
    function IsRunning()
    {
        return $this->IsRunningInDB();
    }

    /**
     * {@inheritDoc}
     * @see TcSession::createMeeting()
     */
    public function createMeeting()
    {
        return $this->save();
    }

    public function startMeeting()
    {
        return true;
    }

    //
    /**
     *
     * @brief Return count of actual participants in this session. if groups are specified, we can't include all users of this course
     * @return number
     */
    public function usersToBeJoined()
    {
        $participants = explode(',', $this->participants);

        // If participants includes "all users" (of this course) get them
        if ($this->participants == '0' || in_array("0", $participants)) {
            $q = Database::get()->querySingle("SELECT COUNT(*) AS count FROM course_user, user
                            WHERE course_user.course_id = ?d AND course_user.user_id = user.id", $this->course_id)->count;
            if ($q === null)
                die('Failed to get user count for course ' . $this->course_id);
            $total = $q;
            $total += $this->external_users ? count(explode(',', $this->external_users)) : 0;
        } else { // There are special entries, could be groups or users of this course
            $group_ids = [];
            $user_ids = [];
            foreach ($participants as $p) {
                $p = trim($p);
                if ($p[0] == '_') { // this is a group
                    $gid = (int) substr($p, 1);
                    if (! in_array($gid, $group_ids, true))
                        $group_ids[] = $gid;
                } else { // this is a user id
                    $uid = (int) $p;
                    if (! in_array($uid, $user_ids))
                        $user_ids[] = $uid;
                }
            }
            $total = count($user_ids);

            // Get the users for the groups
            $q = Database::get()->querySingle("SELECT COUNT(DISTINCT u.id) as `count` FROM user u
                INNER JOIN group_members gm ON u.id=gm.user_id
                WHERE gm.group_id IN (?s)", implode(',', $group_ids));
            if ($q === null)
                die('Failed to get users count for groups ' . implode(',', $group_ids));

            $total += $q->count;

            // must re-count the external users
            $total += $this->external_users ? count(explode(',', $this->external_users)) : 0;
        }

        return $total;
    }

    /**
     *
     *  Pick a server for a session based on all available information for the course. This is specifically a static and used to instantiate descendants
     *  By default, we only get active servers
     */
    public static function pickServer($types, $course_id, $users_needed=null)
    {
        $qtypes = $types;
        array_walk($qtypes, function (&$value) {
            $value = '"' . $value . '"';
        });
        $qtypes = implode(',', $qtypes);
        $t = Database::get()->querySingle("SELECT tcs.* FROM course_external_server ces
                INNER JOIN tc_servers tcs ON tcs.id=ces.external_server
                WHERE ces.course_id = ?d AND tcs.type IN(" . $qtypes . ") AND enabled='true'
                ORDER BY tcs.weight ASC", $course_id);
        if ($t) { // course uses specific tc_servers
            $server = new TcServer($t);
        } else { // will use default tc_server
            // Check each available server of these types
            $r = TcServer::LoadAllByTypes($types, true);
            if (($r) and count($r) > 0) {
                foreach ($r as $server) {
                    //echo 'Checking space for ' . $users_needed. ' users on server ' . $server->id . '/' . $server->api_url . '....<br>';
                    if ($server->available($users_needed)) { // careful, this is probably an API request on each server
                        //echo 'Server ' . $server->id . ' is AVAILABLE.' . "\n";
                        break;
                    }
                }
            } else {
                //Session::Messages($langBBBConnectionErrorOverload, 'alert-danger');
                return false;
            }
        }
        return $server;
    }

    public function save()
    {
        debug_print_backtrace();
        if ($this->id) { // updating/editing session
            $q = Database::get()->querySingle("UPDATE tc_session SET title=?s, description=?s, start_date=?t, end_date=?t,
                                        public=?b, active=?b, running_at=?d, unlock_interval=?d, external_users=?s,running_at=?d,
                                        participants=?s, record=?b, sessionUsers=?d WHERE id=?d", 
                $this->title, $this->description, $this->start_date, $this->end_date, $this->public, $this->active, 
                $this->running_at, $this->unlock_interval, $this->external_users, $this->running_at, $this->participants, $this->record,
                $this->sessionUsers, $this->id);
            
            if ($q === NULL )
                return false;
        } else { // adding new session
            $q = Database::get()->query("INSERT INTO tc_session SET course_id = ?d,
                                                            title = ?s,
                                                            description = ?s,
                                                            start_date = ?t,
                                                            end_date = ?t,
                                                            public = ?b,
                                                            active = ?b,
                                                            running_at = ?d,
                                                            meeting_id = ?s,
                                                            mod_pw = ?s,
                                                            att_pw = ?s,
                                                            unlock_interval = ?d,
                                                            external_users = ?s,
                                                            participants = ?s,
                                                            record = ?b,
                                                            sessionUsers = ?d", 
                $this->course_id, $this->title, $this->description, $this->start_date, $this->end_date, $this->public, $this->active, 
                $this->running_at, $this->meeting_id, $this->mod_pw, $this->att_pw, $this->unlock_interval, $this->external_users, 
                $this->participants, $this->record, $this->sessionUsers);

            if (! $q)
                return false;
            $this->id = $q->lastInsertID;
        }
        return parent::save();
    }

    public function load()
    {
        if ($this->id) {
            $q = $this->_loadFromDB("SELECT * FROM tc_session WHERE id=?s", $this->id, 
                array_merge_recursive($this->params['required'],$this->params['optional'])
             );
            if ( $q  ) {
                $this->server = TcServer::LoadById($this->running_at);
                return parent::load();
            }
            else 
                return false;
        }
    }
    
    public function isIdentifiableToRemote() {
        return !empty($this->meeting_id); //if we have a meeting id, we can manipulate this session
    }
    
    public function join_user(array $joinParams)
    {
        return false; //Can't join a user to database session
    }
    
}




