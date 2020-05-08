<?php
require_once "TcServer.php";
require_once "TcApi.php";

class TcSessionHelper
{

    private $course_id;

    private $course_code;

    private $tc_types;

    // types cache - only stores available ones
    private static $tc_types_available = null;

    const MAX_USERS = 80;

    // recommends USERS/RATIO to user
    const MAX_USERS_RECOMMENDATION_RATIO = 2;


    /**
     *
     * @brief checks if tc server is configured
     * @return string|boolean
     */
    public function __construct($course_id, $course_code)
    {
        $this->course_code = $course_code;
        $this->course_id = $course_id;

        if (! self::$tc_types_available)
            self::$tc_types_available = array_keys(TcApi::AVAILABLE_APIS);

/*        $c = Database::get()->querySingle("SELECT * FROM tc_course_info WHERE course_id=?d", $this->course_id);
        if ($c === null)
            throw new RuntimeException('Query failed for info table query: ' . $this->course_id);
        if (!$c)
            $this->tc_types = self::$tc_types_available;
        else
            $this->tc_types = $c->types;*/
        $this->tc_types = self::$tc_types_available;
    }

    public function getApi(array $params = [])
    {
        $apiclassname = TcApi::AVAILABLE_APIS[$this->tc_type];
        require_once $this->tc_type . '-api.php';
        return new $apiclassname($params);
    }

    //TODO: Fix this to make more sense and avoid double DB query
    public function getSessionById($id) {
        $q = Database::get()->querySingle("SELECT type from tc_servers 
                        INNER JOIN tc_session ON tc_session.running_at=tc_servers.id 
                        WHERE tc_session.id=?d",$id);
        if ( $q ) {
            $classname = TcApi::AVAILABLE_APIS[$q->type];
            require_once $q->type . '-api.php';
        }
        else { //probably null running_at field
            throw new RuntimeException('Failed to get session '.$id);
        }
        $classname = 'Tc' . $classname . 'Session';
        $obj = new $classname();
        return $obj->LoadById($id);
    }
    

    /**
     * Returns view data for the add/edit session form
     * @param TcSession $session
     * @return array
     */
    public function form($session = null)
    {
        global $uid,$start_session;
        global $langAdd,$BBBEndDate,$langBBBSessionSuggestedUsers2,$langModify,$langAllUsers;

        $BBBEndDate = Session::has('BBBEndDate') ? Session::get('BBBEndDate') : "";
        $enableEndDate = Session::has('enableEndDate') ? Session::get('enableEndDate') : ($BBBEndDate ? 1 : 0);

        $usercount = Database::get()->querySingle("SELECT COUNT(*) AS count FROM course_user WHERE course_id=?d", $this->course_id)->count;
        if ($usercount > self::MAX_USERS) {
            $usercount = floor($usercount / self::MAX_USERS_RECOMMENDATION_RATIO); // If more than 80 course users, we suggest 50% of them
        }
        $found_selected = false;

        if ($session) { // edit session details
            $row = $session;
            $status = ($row->active == 1 ? 1 : 0);
            $record = ($row->record == "true" ? true : false);
            // $running_at = $row->running_at; -- UNUSED
            $unlock_interval = $row->unlock_interval;
            $r_group = explode(",", $row->participants);

            $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $row->start_date);
            if ( $row->start_date == '0000-00-00 00:00:00' || $start_date === FALSE)
                $start_session = NULL;
            else
                $start_session = q($start_date->format('d-m-Y H:i'));
            
            
            $end_date = DateTime::createFromFormat('Y-m-d H:i:s', $row->end_date);
            if ( $row->end_date == '0000-00-00 00:00:00' || $end_date === FALSE)
                $BBBEndDate = NULL;
            else
                $BBBEndDate = $end_date->format('d-m-Y H:i');
            
            $enableEndDate = Session::has('BBBEndDate') ? Session::get('BBBEndDate') : ($BBBEndDate ? 1 : 0);

            $textarea = rich_text_editor('desc', 4, 20, $row->description);
            $value_title = q($row->title);
            $value_session_users = $row->sessionUsers;
            $data_external_users = trim($row->external_users);
            if ($data_external_users) {
                $init_external_users = json_encode(array_map(function ($item) {
                        $item = trim($item);
                        return array(
                            'id' => $item,
                            'text' => $item,
                            'selected' => true
                        );
                    }, explode(',', $data_external_users))
                );
            } else {
                $init_external_users = '';
            }
            $submit_name = 'update_bbb_session';
            $value_message = $langModify;
            $server = TcServer::LoadOneByCourse($this->course_id); // Find the server for this course as previously assigned. This may return false
        } else { // creating new session: set defaults
            $record = false;
            $status = 1;
            $unlock_interval = '10';
            $r_group = array();
            $start_date = new DateTime();
            $start_session = $start_date->format('d-m-Y H:i');
            $end_date = new DateTime();
            $BBBEndDate = $end_date->format('d-m-Y H:i');
            $textarea = rich_text_editor('desc', 4, 20, '');
            $value_title = '';
            $init_external_users = '';
            $value_session_users = $usercount;
            $submit_name = 'new_bbb_session';
            $value_message = $langAdd;

            // Pick a server for the course
            $server = TcServer::LoadOneByTypes($this->tc_types, true);
            if (! $server) {
                debug_print_backtrace();
                die('[TcSessionHelper] No servers enabled for types ' . implode(',', $this->tc_types));
            }
        }

        $options = [];
        
        // select available course groups (if exist)
        $res = Database::get()->queryArray("SELECT `group`.`id`,`group`.`name` FROM `group`
                                                        RIGHT JOIN course ON group.course_id=course.id
                                                        WHERE course.id=?d ORDER BY UPPER(NAME)", $this->course_id);
        foreach ($res as $r) {
            if (isset($r->id)) {
                $option['value'] = '_'.$r->id;
                //$tool_content .= "<option value= '_{$r->id}'";
                if (in_array(("_{$r->id}"), $r_group)) {
                    $found_selected = true;
                    $option['selected'] = true;
                    //$tool_content .= ' selected';
                }
                $option['text'] = $r->name;
                //$tool_content .= ">" . q($r->name) . "</option>";
                $options[] = $option;
            }
        }
        // select all users from this course except yourself
        $sql = "SELECT u.id user_id, CONCAT(u.surname,' ', u.givenname) AS name, u.username
                                FROM user u, course_user cu
                                WHERE cu.course_id = ?d
                                AND cu.user_id = u.id
                                AND cu.status != ?d
                                AND u.id != ?d
                                GROUP BY u.id, name, u.username
                                ORDER BY UPPER(u.surname), UPPER(u.givenname)";
        $res = Database::get()->queryArray($sql, $this->course_id, USER_GUEST, $uid);
        foreach ($res as $r) {
            if (isset($r->user_id)) {
                $option['value'] = $r->user_id;
                //$tool_content .= "<option value='{$r->user_id}'";
                if (in_array(("$r->user_id"), $r_group)) {
                    $found_selected = true;
                    $option['selected'] = true;
                    //$tool_content .= ' selected';
                }
                $option['text'] = $r->name. '('.$r->username.')';
                //$tool_content .= ">" . q($r->name) . " (" . q($r->username) . ")</option>";
                $options[] = $option;
            }
        }
        $options[] = [
            'value'=>0, 'text' => $langAllUsers, 'selected' => !$found_selected
        ];
        
        //$tool_content .= "<option value='0'><h2>$langAllUsers</h2></option>";
        
        $data = [
            'types' => $this->tc_types,
            'title' => $value_title,
            'desc' => $textarea,
            'start' => $start_session,
            'end' => $BBBEndDate,
            'enableEndDate' => $enableEndDate,
            'server' => $server,
            'record'=> $record,
            'status'=>$status,
            'unlock_interval'=>$unlock_interval,
            'session_users'=>$value_session_users,
            'usercount'=>$usercount,
            'ratio'=>str_replace("{{RATIO}}", self::MAX_USERS_RECOMMENDATION_RATIO, str_replace("{{MAX}}", self::MAX_USERS, $langBBBSessionSuggestedUsers2)),
            'participantoptions' => $options,
            'value_message'=>$value_message,
            'submit_name'=>$submit_name,
            'init_external_users'=>$init_external_users
        ];
        if ( $session )
            $data['id'] = getIndirectReference($session->session_id);
        
        return $data;
    }

    /*
     * @brief Process incoming session edit/add form
     * @return bool
     */
    public function process_form($session_id = 0)
    {
        global $langBBBScheduledSession, $langBBBScheduleSessionInfo, $langBBBScheduleSessionInfo2, $langBBBScheduleSessionInfoJoin,
        $langAvailableBBBServers, $langDescription, $urlServer;
        
        if (isset($_POST['enableEndDate']) and ($_POST['enableEndDate'])) {
            $endDate_obj = DateTime::createFromFormat('d-m-Y H:i', $_POST['BBBEndDate']);
            $end = $endDate_obj->format('Y-m-d H:i:s');
        } else {
            $end = NULL;
        }

        $startDate_obj = DateTime::createFromFormat('d-m-Y H:i', $_POST['start_session']);
        $start = $startDate_obj->format('Y-m-d H:i:s');
        $notifyUsers = $addAnnouncement = $notifyExternalUsers = 0;
        if (isset($_POST['notifyUsers']) and $_POST['notifyUsers']) {
            $notifyUsers = 1;
        }
        if (isset($_POST['notifyExternalUsers']) and $_POST['notifyExternalUsers']) {
            $notifyExternalUsers = 1;
        }
        if (isset($_POST['addAnnouncement']) and $_POST['addAnnouncement']) {
            $addAnnouncement = 1;
        }
        $record = 'false';
        if (isset($_POST['record'])) {
            $record = $_POST['record'];
        }
        if (isset($_POST['external_users'])) {
            $ext_users = implode(',', $_POST['external_users']);
        } else {
            $ext_users = null;
        }

        if (isset($_POST['groups'])) {
            $r_group = $_POST['groups'];
            if (is_array($r_group) && count($r_group) > 0) {
                $r_group = implode(',', $r_group);
            } else {
                $r_group = '0';
            }
        } else {
            $r_group = '0';
        }
        
        if ( isset($_POST['type']) ) {
            $this->tc_types = [];
            foreach($_POST['type'] as $t) {
                $t = strtolower(trim($t));
                if ( !array_key_exists($t,TcApi::AVAILABLE_APIS) )
                    die('Invalid form data');
                $this->tc_types[] = $t;
            }
        }
        

        $data = [
            'sessionId' => $session_id,
            
            
            'course_id' => $this->course_id,
            //'meeting_id' => , //this should be loaded if session_id is valid, otherwise a new one should be generated internally later
            
            'title'=>trim($_POST['title']),
            'description'=>trim($_POST['desc']),
            'start_date'=>$start,
            'end_date'=>$end,
            'public'=>true, //FIXME: WHY?
            'active'=>$_POST['status']=='1',
            //'running_at'=>????,
            //'mod_pw'=>???,
            //'att_pw'=>???,
            'unlock_interval'=>$_POST['minutes_before'],
            'external_users'=>$ext_users,
            'participants'=>$r_group,
            'record'=>$record=='true',
            'sessionUsers'=>(int) $_POST['sessionUsers'],
        ];
        //echo "<hr><pre>POST:\n".var_export($_POST,true).'</pre>';
        //echo "<hr><pre>DATA:\n".var_export($data,true).'</pre>';

        //Now (re-)/select a server. The type may have changed, so your current server is now invalid.
        //This is done *specifically* by TcDBSession, so we get a server type to use for class instantiation
        $server = TcDBSession::pickServer($this->tc_types,$this->course_id); 
        if ( !$server ) {
            Session::Messages($langAvailableBBBServers, 'alert-danger');
            return false;
        }
        
        //now we have a server and therefore a type so convert to proper class
        require_once $server->type.'-api.php';
        $classname = 'Tc'. TcApi::AVAILABLE_APIS[$server->type] .'Session';
        $tc_session = new $classname($data);
        
        //echo "<hr><pre>Actual session:\n".var_export($tc_session->data,true).'</pre>';
        
        //TODO: This should update the remote side as well
        $tc_session->save(); 
        
        if ($session_id != 0) { // updating/editing session
            // logging
            Log::record($this->course_id, MODULE_ID_TC, LOG_MODIFY, array(
                'id' => $session_id,
                'title' => $tc_session->title,
                'desc' => html2text($tc_session->description)
            ));

            $q = Database::get()->querySingle("SELECT meeting_id, title, mod_pw, att_pw FROM tc_session WHERE id = ?d", $session_id);
        } else { // adding new session
            //echo "<pre>Actual session:\n".var_export($tc_session,true).'</pre>';
            die();
            if (! $tc_session->create_meeting([
                'meetingId'=>$tc_session->meeting_id,
                'meetingName'=>$tc_session->title,
                'attendeePw'=>$tc_session->att_pw,
                'moderatorPw'=>$tc_session->mod_pw,
                'maxParticipants'=>$tc_session->sessionUsers,
                'record'=>$tc_session->record,
                //'duration'=>$tc_session->???,
            ]))
                die('Failed to create/schedule the meeting.');

            // logging
            Log::record($this->course_id, MODULE_ID_TC, LOG_INSERT, array(
                'id' => $q->lastInsertID,
                'title' => $_POST['title'],
                'desc' => html2text($_POST['desc']),
                'tc_type' => implode(',',$this->tc_types)
            ));

            $q = Database::get()->querySingle("SELECT meeting_id, title, mod_pw, att_pw FROM tc_session WHERE id = ?d", $q->lastInsertID);
        }
        
        //TIME FOR NOTIFICATIONS
        
        $new_title = $q->title;
        $new_att_pw = $q->att_pw;
        // if we have to notify users for new session
        if ($notifyUsers == "1" && is_array($r_group) and count($r_group) > 0) {
            $recipients = array();
            if (in_array(0, $r_group)) { // all users
                $result = Database::get()->queryArray("SELECT cu.user_id, u.email FROM course_user cu
                                                    JOIN user u ON cu.user_id=u.id
                                                WHERE cu.course_id = ?d
                                                AND u.email <> ''
                                                AND u.email IS NOT NULL", $this->course_id);
            } else {
                $r_group2 = '';
                foreach ($r_group as $group) {
                    if ($group[0] == '_') { // find group users (if any) - groups start with _
                        $g_id = intval((substr($group, 1, strlen($group))));
                        $q = Database::get()->queryArray("SELECT user_id FROM group_members WHERE group_id = ?d", $g_id);
                        if ($q) {
                            foreach ($q as $row) {
                                $r_group2 .= "'$row->user_id'" . ',';
                            }
                        }
                    } else {
                        $r_group2 .= "'$group'" . ',';
                    }
                }
                $r_group2 = rtrim($r_group2, ',');
                $result = Database::get()->queryArray("SELECT course_user.user_id, user.email
                                                        FROM course_user, user
                                                   WHERE course_id = ?d AND user.id IN ($r_group) AND
                                                         course_user.user_id = user.id", $this->course_id);
            }
            foreach ($result as $row) {
                $emailTo = $row->email;
                $user_id = $row->user_id;
                // we check if email notification are enabled for each user
                if (get_user_email_notification($user_id)) {
                    // and add user to recipients
                    array_push($recipients, $emailTo);
                }
            }
            if (count($recipients) > 0) {
                $emailsubject = $langBBBScheduledSession;
                //return $urlServer . "modules/tc/index.php?course=$course_code&amp;choice=do_join&amp;meeting_id=$new_meeting_id&amp;title=" . urlencode($new_title) . "&amp;att_pw=$new_att_pw";
                $bbblink = $this->get_join_link($urlServer.'modules/tc/index.php',$session_id,['att_pw'=>$new_att_pw,'title'=>urlencode($new_title)]);
                $emailheader = "
                <div id='mail-header'>
                    <div>
                        <div id='header-title'>$langBBBScheduleSessionInfo" . q($tc_session->title) . " $langBBBScheduleSessionInfo2" . q($tc_session->start_date) . "</div>
                    </div>
                </div>
            ";

                $emailmain = "
            <div id='mail-body'>
                <div><b>$langDescription:</b></div>
                <div id='mail-body-inner'>
                    $tc_session->description
                    <br><br>$langBBBScheduleSessionInfoJoin:<br><a href='$bbblink'>$bbblink</a>
                </div>
            </div>
            ";

                $emailcontent = $emailheader . $emailmain;
                $emailbody = html2text($emailcontent);
                // Notify course users for new bbb session
                send_mail_multipart('', '', '', $recipients, $emailsubject, $emailbody, $emailcontent);
            }
        }

        // Notify external users for new bbb session
        if ($notifyExternalUsers == "1") {
            if (isset($ext_users)) {
                $recipients = explode(',', $ext_users);
                $emailsubject = $langBBBScheduledSession;
                $emailheader = "
                    <div id='mail-header'>
                        <div>
                            <div id='header-title'>$langBBBScheduleSessionInfo" . q($tc_session->title) . " $langBBBScheduleSessionInfo2" . q($tc_session->start_date) . "</div>
                        </div>
                    </div>
                ";
                foreach ($recipients as $row) {
                    $bbblink = $this->get_join_link($urlServer.'modules/tc/ext.php',$session_id,['att_pw'=>$new_att_pw,'username'=>urlencode($row)]);
                    //$bbblink = $urlServer . "modules/tc/ext.php?course=$course_code&amp;meeting_id=$new_meeting_id&amp;username=" . urlencode($row);

                    $emailmain = "
                <div id='mail-body'>
                    <div><b>$langDescription:</b></div>
                    <div id='mail-body-inner'>
                        $tc_session->description
                        <br><br>$langBBBScheduleSessionInfoJoin:<br><a href='$bbblink'>$bbblink</a>
                    </div>
                </div>
                ";
                    $emailcontent = $emailheader . $emailmain;
                    $emailbody = html2text($emailcontent);
                    send_mail_multipart('', '', '', $row, $emailsubject, $emailbody, $emailcontent);
                }
            }
        }

        if ($addAnnouncement == '1') { // add announcement
            $orderMax = Database::get()->querySingle("SELECT MAX(`order`) AS maxorder FROM announcement
                                                   WHERE course_id = ?d", $this->course_id)->maxorder;
            $order = $orderMax + 1;
            Database::get()->querySingle("INSERT INTO announcement (content,title,`date`,course_id,`order`,visible)
                                    VALUES ('" . $langBBBScheduleSessionInfo . " \"" . q($tc_session->title) . "\" " . $langBBBScheduleSessionInfo2 . " " . 
                                    $tc_session->start_date . "','$langBBBScheduledSession', " . DBHelper::timeAfter() . ", ?d, ?d, '1')", $this->course_id, $order);
        }

        return true; // think positive
    }

    /**
     * Returns the view data for the session listing
     * @return array
     */
    function tc_session_details()
    {
        global $is_editor, $uid, $langHasExpiredS, 
            $langNote, $langBBBNoteEnableJoin,$langDaysLeft,$langAllUsers, $langBBBNoServerForRecording,$langPublicAccess;

        $viewdata['is_editor'] = $is_editor;
            
        $isActiveTcServer = $this->is_active_tc_server(); // cache this since it involves DB queries
        $viewdata['active_server_exists'] = $isActiveTcServer;
        $viewdata['sessions'] = [];
        
        //FIXME: this should go into the template globally
        load_js('trunk8');

        $myGroups = Database::get()->queryArray("SELECT group_id FROM group_members WHERE user_id=?d", $_SESSION['uid']);
        $activeClause = $is_editor ? '' : "AND active = '1'";
        $result = Database::get()->queryArray("SELECT tc_session.*,tc_servers.id as serverid,type FROM tc_session
                                                    INNER JOIN tc_servers ON tc_session.running_at=tc_servers.id
                                                    WHERE course_id = ?d $activeClause
                                                    ORDER BY start_date DESC", $this->course_id);
        if ($result) {
            if ((! $is_editor) and $isActiveTcServer) {
                $tool_content .= "<div class='alert alert-info'><label>$langNote</label>: $langBBBNoteEnableJoin</div>";
            }
            foreach ($result as $row) {
                // Allow access to admin
                $access = $is_editor;
                
                // Allow access to session if user is in participant group or session is scheduled for everyone
                if ( !$access ) {
                    $r_group = explode(",", $row->participants);
                    if (in_array('0', $r_group)) { // all users
                        $access = TRUE;
                    } else {
                        if (in_array("$uid", $r_group)) { // user search
                            $access = TRUE;
                        } else {
                            foreach ($myGroups as $user_gid) { // group search
                                if (in_array("_$user_gid->group_id", $r_group)) {
                                    $access = TRUE;
                                }
                            }
                        }
                    }
                }
                
                // Allow access to editor switched to student view
                // FIXME: Should probably lock student_view to course_id and not course_code, there could be duplicates
                if ( !$access )
                    $access = isset($_SESSION['student_view']) and $_SESSION['student_view'] == $this->course_code;

                if ( !$access )
                    continue; //move on to the next one

                $s = [
                    'id'=>$row->id
                ];
                    
                //Dates: start, end and duration
                $start_date = $row->start_date;
                $end_date = $row->end_date;
                if ($end_date) {
                    $timeLeft = date_diff_in_minutes($end_date, date('Y-m-d H:i:s'));
                    $s['timeLabel'] = nice_format($end_date, TRUE);
                } else {
                    $timeLeft = date_diff_in_minutes($start_date, date('Y-m-d H:i:s'));
                    $s['timeLabel'] = '&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;';
                }
                if ($timeLeft > 0) {
                    $s['timeLabel'] .= "<br><span class='label label-warning'><small>$langDaysLeft " . format_time_duration($timeLeft * 60) . "</small></span>";
                } elseif (isset($end_date) and ($timeLeft < 0)) {
                    $s['timeLabel'] .= "<br><span class='label label-danger'><small>$langHasExpiredS</small></span>";
                }
                $s['start_date'] = $start_date;
                
                //Check is we can join the session
                if (isset($end_date) and ($timeLeft < 0)) {
                    $canJoin = FALSE;
                } elseif (($row->active == '1') and (date_diff_in_minutes($start_date, date('Y-m-d H:i:s')) < $row->unlock_interval) and $isActiveTcServer) {
                    $canJoin = TRUE;
                } else
                    $canJoin = FALSE;
                $s['canJoin'] = $canJoin;
                if ($canJoin) {
                    $s['joinLink'] = '<a href="'.$this->get_join_link('',$row->id).'">'.q($row->title).'</a>';
                } else {
                    $s['joinLink'] = $row->title;
                }

                //Check if recording is available
                $record = $row->record;
                if ($row->running_at)
                    $course_server = TcServer::LoadById($row->running_at);
                else
                    $record = 'false';
                if ($record == 'false' or !$course_server->recording()) {
                    $s['warning_message_record'] = "<span class='fa fa-info-circle' data-toggle='tooltip' data-placement='right' title='$langBBBNoServerForRecording".
                        ($course_server->recording()?'':' (server)').
                    "'></span>";
                }
                
                //description
                $s['desc'] = isset($row->description) ? $row->description : '';
                
                $s['desc'] .= $row->public?'['.$langPublicAccess.']':'';
                
                // Get participants
                $participants = '';
                $r_group = explode(",", $row->participants);
                foreach ($r_group as $participant_uid) {
                    if ($participants) {
                        $participants .= ', ';
                    }
                    $participant_uid = str_replace("'", '', $participant_uid);
                    if (preg_match('/^_/', $participant_uid)) {
                        $participants .= gid_to_name(str_replace("_", '', $participant_uid));
                    } else {
                        if ($participant_uid == 0) {
                            $participants .= $langAllUsers;
                        } else {
                            $participants .= uid_to_name($participant_uid, 'fullname');
                        }
                    }
                }
                $s['participants'] = $participants;

                //Server information
                $s['serverinfo'] = [
                    'id'=>$row->serverid,
                    'type'=>$row->type
                ];
                
                $s['active'] = $row->active;
                
                $viewdata['sessions'][] = $s;
            }
        } 
        return $viewdata;
    }

    /**
     *
     * @brief find enabled tc server for this course
     * @return boolean
     */
    function is_active_tc_server()
    {
        $s = TcServer::LoadAllByTypes($this->tc_types, true); // only get enabled servers
        if (! $s || count($s) == 0)
            return false;

        if (count($s) > 0) {
            foreach ($s as $data) {
                if ($data->all_courses == 1) { // tc_server is enabled for all courses
                    return true;
                } else { // check if tc_server is enabled for specific course
                    $q = Database::get()->querySingle("SELECT * FROM course_external_server
                                    WHERE course_id = ?d AND external_server = ?d", $this->course_id, $data->id);
                    if ($q) {
                        return true;
                    }
                }
            }
            return false;
        } else { // no active tc_servers
            return false;
        }
    }
    
    private function get_join_link($url,$session_id, $additionalParams=[]) {
        $params = [ 'choice'=>'do_join', 'session_id'=>$session_id];
        $params = array_merge($params,$additionalParams);
        return $url.'?'.http_build_query($params);
    }
    
    
    /**
     *
     * @brief display video recordings in multimedia
     * @param int $session_id
     * @return string
     */
    function publish_video_recordings($session_id)
    {
        global $langBBBImportRecordingsOK, $langBBBImportRecordingsNo, $langBBBImportRecordingsNoNew;
        
        //FIXME: This is a problem, if the session was moved to another server or server config changed after a recording was made, it may be irretrievable
        $sessions = Database::get()->queryArray("
            SELECT tc_session.id, tc_session.course_id AS course_id,tc_session.title, tc_session.description, tc_session.start_date,
            tc_session.meeting_id, course.prof_names 
            FROM tc_session
            LEFT JOIN course ON tc_session.course_id=course.id 
            WHERE course.code=?s AND tc_session.id=?d", $this->course_id, $session_id);
        
        $servers = TcServer::LoadAllByTypes($this->tc_types,true); 
        
        $perServerResult = array(); /* AYTO THA EINAI TO ID THS KATASTASHS GIA KATHE SERVER */
        
        $tool_content = '';
        if (($sessions) && ($servers)) {
            $msgID = array();
            foreach ($servers as $server) {
                $api = $this->getApi(['server'=>$server]);
                
                $sessionsCounter = 0;
                foreach ($sessions as $session) {
                    $xml = $api->getRecordings(['meetingId' => $session->meeting_id]);
                    // If not set, it means that there is no video recording.
                    // Skip and search for next one
                    if ($xml && is_array($xml) && count($xml)>0 ) {
                        foreach ($xml as $recording) {
                            $url = $recording['playbackFormatUrl'];
                            // Check if recording already in videolinks and if not insert
                            $c = Database::get()->querySingle("SELECT COUNT(*) AS cnt FROM videolink WHERE url = ?s", $url);
                            if ($c->cnt == 0) {
                                Database::get()->querySingle("
                                    INSERT INTO videolink (course_id,url,title,description,creator,publisher,date,visible,public)
                                    VALUES (?s,?s,?s,IFNULL(?s,'-'),?s,?s,?t,?d,?d)",$session->course_id, $url, $session->title,
                                    strip_tags($session->description), $session->prof_names, $session->prof_names, $session->start_date, 1, 1);
                                $msgID[$sessionsCounter] = 2; /* AN EGINE TO INSERT SWSTA PAIRNEI 2 */
                            } else {
                                if (isset($msgID[$sessionsCounter])) {
                                    if ($msgID[$sessionsCounter] <= 1)
                                        $msgID[$sessionsCounter] = 1; /* AN DEN EXEI GINEI KANENA INSERT MEXRI EKEINH TH STIGMH PAIRNEI 1 */
                                } else
                                    $msgID[$sessionsCounter] = 1;
                            }
                        }
                    } else {
                        $msgID[$sessionsCounter] = 0; /* AN DEN YPARXOUN KAN RECORDINGS PAIRNEI 0 */
                    }
                    $sessionsCounter ++;
                }
                $finalMsgPerSession = max($msgID);
                array_push($perServerResult, $finalMsgPerSession);
            }
            $finalMsg = max($perServerResult);
            switch ($finalMsg) {
                case 0:
                    $tool_content .= "<div class='alert alert-warning'>$langBBBImportRecordingsNo</div>";
                    break;
                case 1:
                    $tool_content .= "<div class='alert alert-warning'>$langBBBImportRecordingsNoNew</div>";
                    break;
                case 2:
                    $tool_content .= "<div class='alert alert-success'>$langBBBImportRecordingsOK</div>";
                    break;
            }
        }
        return $tool_content;
    }
    
}

/**
 *
 * @brief function to calculate date diff in minutes in order to enable join link
 * @param string $start_date
 * @param string $current_date
 * @return int
 */
//FIXME: This is used in ext.php, fix that
function date_diff_in_minutes($start_date, $current_date)
{
    return round((strtotime($start_date) - strtotime($current_date)) / 60);
}
