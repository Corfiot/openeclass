<?php

require_once 'TcApi.php';
require_once 'paramsTrait.php';
require_once 'zoom-data.php';


class Zoom extends TcApi
{

    private static $syntax = [
        'createmeeting' => [
            'request' => [
                'method' => 'POST',
                'url' => 'users/{userId}/meetings',
                'path' => [
                    // The user ID or email address of the user. For user-level apps, pass "me" as the value for user id.
                    'userId:string' // required
                ],
                'body' => 'ZoomMeeting'
            ],
            'response' => [
                '300' => [
                    'description' => 'Invalid enforce_login_domains, separate multiple domains by semicolon.
                        A maximum of {rateLimitNumber} meetings can be created/updated for a single user in one day.'
                ],
                '404' => [
                    'description' => 'User not found - If Error Code: 1001 User {userId} not exist or not belong to this account.'
                ],
                '201' => [
                    'description' => 'Meeting created',
                    'headers' => [
                        'Content-Location:string' // Location of created meeting
                    ],
                    'body' => 'ZoomMeeting'
                ]
            ]
        ],
        'listmeetings' => [
            'request' => [
                'method' => 'GET',
                'url' => 'users/{userId}/meetings',
                'path' => [
                    // The user ID or email address of the user. For user-level apps, pass "me" as the value for user id.
                    'userId:string' // required
                ],
                'query' => [
                    /*
                     * The meeting types: <br>`scheduled` - This includes all valid past meetings (unexpired), live meetings
                     * and upcoming scheduled meetings. It is equivalent to the combined list of \"Previous Meetings\" and
                     * \"Upcoming Meetings\" displayed in the user's [Meetings page](https://zoom.us/meeting) on the Zoom
                     * Web Portal.<br>`live` - All the ongoing meetings.<br>`upcoming` - All upcoming meetings including live meetings.
                     */
                    'type:enum(scheduled,live,upcoming)',
                    
                    /* The number of records returned within a single API call. default:30, max:300 */
                    'page_size:integer',

                    // The current page number of returned records. Default 1
                    'page_number:integer'
                ]
            ],
            'response' => [
                '404' => [
                    'description' => 'User ID not found. Error Code: 1001: User {userId} not exist or not belong to this account.'
                ],
                '200' => [
                    'description' => 'List of meeting objects returned.',
                    'body' => [
                        'page_count:integer',

                        // Default 1
                        'page_number:integer',

                        // default 30, max 300
                        'page_size:integer',
                        'total_records:integer',
                        'meetings:ZoomMeeting[]'
                    ]
                ]
            ]
        ],
        'getmeeting' => [
            'request' => [
                'method' => 'GET',
                'url' => 'meetings/{meetingId}',
                'path' => [
                    'meetingId:string' // required
                ],
                'query' => [
                    'occurence_id:string' // Meeting occurence id
                ]
            ],
            'response' => [
                '400' => [
                    'description' => 'Error Code: 1010: User not found on this account: {accountId}. 
                                    Error Code: 3000: Cannot access webinar info.'
                ],
                '404' => [
                    'description' => 'Meeting not found.
                                    Error Code: 1001: User not exist: {userId}.
                                    Error Code: 3001: Meeting {meetingId} is not found or has expired.'
                ],
                '200' => [
                    'description' => 'Meeting object returned.',
                    'body' => 'ZoomMeeting'
                ]
            ]
        ],
        'deletemeeting' => [
            'request' => [
                'method' => 'DELETE',
                'url' => 'meetings/{meetingId}',
                'path' => [
                    'meetingId:string' //required
                ],
                'query' => [
                    'occurence_id:string',
                    'schedule_for_reminder:enum(true,false)',
                ],
            ],
            'response' => [
                '204' => [ 'description' => 'Meeting deleted' ],
                '400' => [ 'description' => '
                                Error Code: 1010
                                User does not belong to this account: {accountId}.
                                Error Code: 3000
                                Cannot access meeting information.
                                Invalid occurrence_id.
                                Error Code: 3002
                                Sorry, you cannot delete this meeting since it is in progress.
                                Error Code: 3003
                                You are not the meeting host.
                                Error Code: 3007
                                Sorry, you cannot delete this meeting since it has ended.
                                Error Code: 3018
                                Not allowed to delete PMI.
                                Error Code: 3037
                                Not allowed to delete PAC.
                '],
                '404' => [ 'description' => '
                                Meeting not found. Error Code: 1001
                                User does not exist: {userId}.
                                Error Code: 3001
                                Meeting with this {meetingId} is not found or has expired.
                ']
            ]
            
        ],
    ];

    private $_ApiUrl;

    private $_ApiKey;

    private $_ApiSecret;

    private $_jwt;
	
    public function __construct($params = [])
    {	
        if (is_array($params) && count($params) > 0) {
            if (array_key_exists('server', $params)) {
                $this->_ApiUrl = $params['server']->api_url;
				list($this->_ApiKey,$this->_ApiSecret)=explode(',',$params['server']->server_key);
			}
            if (array_key_exists('url', $params))
                $this->_ApiUrl = $params['url'];
            if (array_key_exists('key', $params))
                $this->_ApiKey = $params['key'];
            if (array_key_exists('secret', $params)) {
                $x = explode(',', $params['secret']);
                $this->_ApiKey = $x[0];
                $this->_ApiSecret = $x[1];
            }
        }
    }

    private function generateJWT()
    {
		
        if ($this->_ApiUrl && $this->_ApiKey && $this->_ApiSecret) {
			
			$meeting_duration=3600;
			$token_start=time();
			$token_end=time()+$meeting_duration;
			
			$header_array=array('typ'=>'JWT','alg'=>'HS256');
			$payload_array=array('aud'=>null, 'iss'=>$this->_ApiKey, 'exp'=>$token_end, 'iat'=>$token_start);
			$header_json=json_encode($header_array);
			$payload_json=json_encode($payload_array);
			$header_json64=str_replace('=', '', strtr(base64_encode($header_json), '+/', '-_'));
			$payload_json64=str_replace('=', '', strtr(base64_encode($payload_json), '+/', '-_'));
			
			$signature=hash_hmac('SHA256',$header_json64.'.'.$payload_json64,$this->_ApiSecret, true);
			$signature64=str_replace('=', '', strtr(base64_encode($signature), '+/', '-_'));
			
			$this->_jwt = $header_json64.'.'.$payload_json64.'.'.$signature64;
			return;
        } else
            die('[' . __METHOD__ . '] Missing info to generate JWT');
    }

    /**
     *
     * @brief Process a syntax fragment with a data fragment
     * @param string $syntax
     * @param mixed $response
     * @return mixed - the data processed per syntax
     * @throws RuntimeException
     */
    private function _processResponse($syntax, $response)
    {
        //echo '<pre>' . __METHOD__ . ': SYNTAX:' . var_export($syntax, true) . ' RESP: ' . var_export($response, true) . '</pre>';
        if (is_array($syntax)) { // array of items
            $items = [];
            foreach ($syntax as $syntaxitem) {
                $x = explode(':', $syntaxitem);
                if (! isset($response->{$x[0]}))
                    continue;
                $typespec = $x[1];
                echo 'Field ' . $x[0] . ' - type:' . $typespec . ' - val in object: ' . var_export($response->{$x[0]}, true) . "\n";
                $items[$x[0]] = $this->_processResponse($syntaxitem, $response->{$x[0]});
            }
            return $items;
        } else { // Single item
            $x = explode(':', $syntax);
            if (count($x) > 1)
                $typespec = $x[1];
            else
                $typespec = $x[0];
            if (in_array($typespec, [
                'number',
                'bool',
                'boolean',
                'boolstr',
                'integer'
            ])) {
                return $response;
            } elseif (substr($typespec, 0, 4) == 'enum') {
                $options = explode('|', substr($typespec, 5, strlen($typespec) - 6));
                if (count($options) < 2)
                    die('Invalid typespec ' . $typespec);
                return $response;
            } elseif (substr($typespec, - 2) == '[]') { // This is an array of things
                $items = [];
                foreach ($response as $data) {
                    $items[] = $this->_processResponse(substr($typespec, 0, - 2), $data);
                }
                return $items; // replace previous data with proper object
            } else {
                // Not a known type, so assume it's a class => try to instantiate
                //echo '**INSTANTIATING ' . $typespec;
                $obj = new $typespec($response);
                return $obj; // replace previous data with proper object
            }
        }
        die('WUT?');
    }

    public function SendApiCall($operation, $params)
    {
        //echo '<pre>'.__METHOD__.' operation '.$operation.' params:'.var_export($params,true).'</pre>';

        $cachekey = $operation.'_'.md5(serialize($params));
        $x = parent::cacheLoad($cachekey);
        if ( $x )
            return $x;
            
        $syntax = self::$syntax[$operation];
        $curl = curl_init();

        $CURLOPT_URL = $syntax['request']['url'];
        foreach ($syntax['request']['path'] as $pathitem) {
            echo 'path: '.var_export($pathitem,true);
            
            $x = explode(':', $pathitem);
            if (is_array($params))
                $pp = $params[$x[0]];
            elseif (is_object($params)) {
                $pp = $params->{$x[0]};
                unset($params->{$x[0]}); //get rid of this field
            }
            $CURLOPT_URL = str_replace('{' . $x[0] . '}', $pp, $CURLOPT_URL);
        }
        $CURLOPT_URL = $this->_ApiUrl . '/' . $CURLOPT_URL;

        
//         echo "<pre>Request\n";
//         print_r($syntax['request']);
//         echo $syntax['request']['method'] . ' URL: ' . $CURLOPT_URL . "\n";
//         echo '</pre>';

        if ( array_key_exists('body',$syntax['request'])) {
            //$request = $this->_processResponse($syntax['request']['body'], $params);
            $request = $params;
            //unset($request->settings);
            //unset($request->timezone);
            $request = json_encode($request);
            $request = preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $request);

            echo '<pre>REQUEST TO '.$CURLOPT_URL."\n\n".var_export($request,true)."\n\n</pre>";
            //die();
            if ($syntax['request']['method'] == 'POST') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            }
        }
        
        if (! $this->_jwt)
            $this->generateJWT();
            
        curl_setopt_array($curl, array(
            CURLOPT_URL => $CURLOPT_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $syntax['request']['method'],
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->_jwt,
                "content-type: application/json"
            )
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        
        // $response = '{"page_count":1,"page_number":1,"page_size":30,"total_records":3,"meetings":[{"uuid":"W5kBJogRSsm9VVRWT70h9A==","id":378790032,"host_id":"KozSavKkS8GnZyINEabHww","topic":"TEC210 Πέμπτη","type":8,"start_time":"2020-05-14T13:00:00Z","duration":120,"timezone":"Europe/Athens","created_at":"2020-04-01T22:01:22Z","join_url":"https://us04web.zoom.us/j/378790032"},{"uuid":"3CkVWN4iSReFYdSET/AC+Q==","id":674827602,"host_id":"KozSavKkS8GnZyINEabHww","topic":"Συνέλευση ΤΤΗΕ 24/3/2020","type":2,"start_time":"2020-03-24T09:00:00Z","duration":60,"timezone":"Europe/Athens","created_at":"2020-03-23T11:34:32Z","join_url":"https://us04web.zoom.us/j/674827602"},{"uuid":"rFuBzSx5QNiQfaEBFRN8oA==","id":73699571476,"host_id":"KozSavKkS8GnZyINEabHww","topic":"Συνέλευση ΤΤΗΕ 27/4/2020","type":2,"start_time":"2020-04-27T08:00:00Z","duration":180,"timezone":"Europe/Athens","created_at":"2020-04-26T15:46:51Z","join_url":"https://us04web.zoom.us/j/73699571476?pwd=ZnNuY1lnRFlRcjFNbWdabjF4anhydz09"}]}';
        //$response = '{"uuid":"9PK7UyCnSj+lQUMG7x4b3A==","id":75811066704,"host_id":"KozSavKkS8GnZyINEabHww","topic":"6666666666666","type":2,"status":"waiting","start_time":"2020-04-23T00:55:00Z","duration":60,"timezone":"Europe/Athens","agenda":"Welcome to Teleconference!","created_at":"2020-04-27T01:05:49Z","start_url":"https://us04web.zoom.us/s/75811066704?zak=eyJ6bV9za20iOiJ6bV9vMm0iLCJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJjbGllbnQiLCJ1aWQiOiJLb3pTYXZLa1M4R25aeUlORWFiSHd3IiwiaXNzIjoid2ViIiwic3R5IjoxMDAsIndjZCI6InVzMDQiLCJjbHQiOjAsInN0ayI6Il9TdzZCZDRocTZpa04zRlk3T09iMzlhMF9KdG55enhfc3ZvZ0dSVnc0MVkuQmdVZ016QkNOa05yYWpCcVRHOTJlSEk1TlRWMVEyOWxVbWRSV0ZscVlUQlRkVkpBTXprMU5UVmxPREV4TkRaaVkyWmhNVFk1WmpSaU4yRXdZVFkyWmpOaVpETTJaR00wTW1aa09EVXpOVEppTldNNFpXWTBPV000T0dJM09UWmpOelJrT1FBTU0wTkNRWFZ2YVZsVE0zTTlBQVIxY3pBMCIsImV4cCI6MTU4Nzk1Njc1MCwiaWF0IjoxNTg3OTQ5NTUwLCJhaWQiOiJCbFJEMXRQN1JPV2paVFc3VGpyTWpnIiwiY2lkIjoiIn0.bBB8bVAWhtsqyit6nxFYTG0RU7FGE5sru_uzHdq4ibc","join_url":"https://us04web.zoom.us/j/75811066704?pwd=a1lvQzBrVXo0SFpzbElPMVVneGtzQT09","password":"ENof6Tr7aq","h323_password":"868374","pstn_password":"868374","encrypted_password":"a1lvQzBrVXo0SFpzbElPMVVneGtzQT09","settings":{"host_video":false,"participant_video":true,"cn_meeting":false,"in_meeting":false,"join_before_host":true,"mute_upon_entry":true,"watermark":false,"use_pmi":false,"approval_type":2,"audio":"both","auto_recording":"none","enforce_login":false,"enforce_login_domains":"","alternative_hosts":"","close_registration":false,"registrants_confirmation_email":true,"waiting_room":true,"registrants_email_notification":true,"meeting_authentication":false}}';
        //$err = null;
        //$code = 201;
        

        if ($err) { // something went wrong with the request
            echo "cURL Error:" . $err;
            echo 'CODE: ' . $code;
            echo 'response: ' . $response;
        /*} elseif ($response == '') { // empty response
            var_dump($response);
            curl_close($curl);
            throw new \RuntimeException('Empty response: HTTP CODE:' . $code);*/
        } else {
            echo $operation . ' - code:' . $code . ':' . $response;
            if (! array_key_exists($code, $syntax['response'])) {
                //die('Unknown response ' . $response);
                //echo '<pre>'.$response.'</pre>';
                echo "cURL Error:" . $err;
                die("Unknown response code: $code - response:".htmlentities($response));
            }
            $response = json_decode($response,true);

//             echo "<pre>Response\n";
//             print_r($syntax['response']);
//             print_r($response);
//             echo '</pre>';
//             die();

            if (array_key_exists('body', $syntax['response'][$code]) || array_key_exists('headers', $syntax['response'][$code])) {
                // TODO: Do headers, too
                $x=$this->_processResponse($syntax['response'][$code]['body'], $response);
                parent::cacheStore($cachekey,$x);
                return $x;
            }
            elseif ( $response == '')
            {
                return true; //nothing returned and nothing expected -> all good
            } else {
                return false; //nothing expected but something returned -> oops
            }
        }
        curl_close($curl);
    }

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
     * 'logoutUrl' => '', -- Default in Zoom.properties. Optional.
     * 'maxParticipants' => '-1', -- Optional. -1 = unlimitted. Not supported in BBB. [number]
     * 'record' => 'false', -- New. 'true' will tell BBB to record the meeting.
     * 'duration' => '0', -- Default = 0 which means no set duration in minutes. [number]
     * 'meta_category' => '', -- Use to pass additional info to BBB server. See API docs to enable.
     * );
     */
    public function createMeeting($creationParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';

        //echo '<hr><pre>PARAMS:'.var_export($creationParams,true).'</pre>';
        $newmeeting = new ZoomMeeting($creationParams);
        //echo '<hr><pre>NEWMEETING:'.var_export($newmeeting,true).'</pre>';
        
        $newmeeting->userId = 'me';
        $x = $this->SendApiCall('createmeeting', $newmeeting);
        return $x ? $x : false;
    }

    /*
     * USAGE:
     * $joinParams = array(
     * 'meetingId' => '1234', -- REQUIRED - A unique id for the meeting
     * 'host' => boolean
     * 'password' => 'ap', -- REQUIRED - The attendee or moderator password, depending on what's passed here
     * );
     */
    public function getJoinMeetingURL($joinParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        print_r($joinParams);
        $x = $this->getMeetingInfo(['meetingId'=>$joinParams['meetingId']]);
        
        if ( !$x )
            return false;

        //TODO: This needs a bit of looking into
        if ( $joinParams['host'] )
            return $x->start_url;
        else
            return $x->join_url;
    }

    public function endMeeting($endParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function deleteMeeting($deleteParams) {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        
        $x = $this->SendApiCall('deletemeeting', $deleteParams);
        if ( !$x )
            return false;
        return true;
    }
    
    /**
     *
     * @param string $meetingId
     * @return boolean
     */
    public function isMeetingRunning($meetingId)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $x = $this->getMeetingInfo(['meetingId'=>$meetingId]);
        
        if ( !$x )
            return false;
        
        return $x->status=='started'; //waiting=not started, finished=started and finished
    }


    public function getMeetings()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $x = $this->SendApiCall([
            'operation' => 'listmeetings',
            'userId' => 'me',
            'page_size' => 300 // TODO: This is the max allowed, support paging in future
        ]);
        return $x ? $x : false;
        // die('unimplemented');
        return true;
    }

    public function getMeetingInfo($infoParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        
        $x = $this->SendApiCall('getmeeting', [
            'meetingId' => $infoParams['meetingId']
        ]);
        
        return $x ? $x : false;
    }


    public function getRecordings($recordingParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }


    public function publishRecordings($recordingParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function deleteRecordings($recordingParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function clearCaches()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }
    
    public static function generatePassword() {
        $length = 10; //max password length in zoom is 10
        return substr(str_shuffle(implode(array_merge(range(0, 9), range('A', 'Z'), range('a', 'z')))), 0, $length);
    }
    
    public static function generateMeetingId() {
        return NULL; //Zoom doesn't allow you to specify meeting IDs
    }
    
    public function getServerUsers(TcServer $server) {
        return 0; //You need dashboard information for this
    }
    
    
}

class TcZoomSession extends TcDbSession
{
    use paramsTrait;

    private $params = [
        'required' => [],
        'optional' => [
            'id:number',
            'join_url',
            'host_id:number',
            'timezone',
            'created_at',
            'type:number',
            'uuid',

            'topic',
            'duration:number',
            'agenda',
            'start_time'
        ]
    ];

    // public $uuid, $id, $host_id, $topic, $duration, $timezone, $join_url, $agenda;

    /**
     *
     * @var format: date-time
     */
    public $start_time;

    /**
     *
     * @var format: date-time
     */
    public $created_at;

    /**
     *
     * @var 1-instant meeting, 2-scheduled meeting, 3-recurring meeting with no fixed time, 8-recurring meeting with fixed time
     */
    public $type;

    function __construct(array $cParams = [])
    {
        parent::__construct($cParams);
        $validparams = $this->_checkParams($this->params, $cParams);
        foreach ($validparams as $n => $v) {
            $this->{$n} = $v;
        }
    }

    /**
     *
     * @brief Disable bbb session (locally)
     * @return bool
     */
    function disable()
    {
        // TODO:attempt to disable on server
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return parent::disable(); // disable locally
    }

    /**
     *
     * @brief enable bbb session (locally)
     * @param int $session_id
     * @return bool
     */
    function enable()
    {
        // TODO:attempt to enable on server
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return parent::enable();
    }

    /**
     *
     * @brief delete bbb sessions (locally)
     * @param int $session_id
     * @return bool
     */
    function delete()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        // TODO:check if it's running and if so, KILL IT NOW
        $api = new Zoom([
            'server' => $this->server
        ]);
        
        $x = $api->deleteMeeting(['meetingId'=>$this->meeting_id]);
/*        if ( !$x )
            return false;*/
if ( !$x ) {
    echo __METHOD__.' deletemeeting returned zero/false.';
    var_dump($x);
    die();
}

        return parent::delete(); // delete from DB
    }
    
    function forget() {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return parent::delete();
    }

    /**
     *
     * @brief get number of meeting users
     * @global type $langBBBGetUsersError
     * @global type $langBBBConnectionError
     * @global type $course_code
     * @param string $salt
     * @param string $bbb_url
     * @param string $meeting_id
     * @param string $pw
     * @return int
     */
    function get_meeting_users($pw)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return 0; //NEEDS DASHBOARD ACCESS: Business accounts and up, or implement webhooks
    }

    public function isFull()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return false; //NEEDS DASHBOARD ACCESS: Business accounts and up, or implement webhooks
    }

    /**
     *
     * @brief Join a user to the session
     * @param array $joinParams
     * @return boolean
     */
    public function join_user(array $joinParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $this->getRunningServer();

        $fullname = $this->_requiredParams([
            'username',
            'name'
        ], $joinParams);
        $pw = $this->_requiredParam('pw', $joinParams);
        $uid = $this->_requiredParam('uid', $joinParams);

        if ( isset($this->mod_pw) && $pw != $this->mod_pw || isset($this->mod_att) && $pw != $this->att_pw )
            return false; // die('Invalid password');

        if ($this->isFull())
            return false;

        $joinParams = array(
            'meetingId' => $this->meeting_id, // REQUIRED - We have to know which meeting to join.
            'fullName' => $fullname, // REQUIRED - The user display name that will show in the BBB meeting.
            'password' => $pw, // REQUIRED - Must match either attendee or moderator pass for meeting.
            'userID' => $uid // OPTIONAL - string
        );

        $api = new Zoom([
            'server' => $this->server
        ]);
        $uri = $api->getJoinMeetingURL($joinParams);
        redirect($uri);
        // exit; //FIXME: Probably need to check flow in callers to enforce this there, some plugins may need to continue?
        return true;
    }

    /**
     *
     * @brief Check is this session is known to server (scheduled)
     * @return boolean
     */
    public function IsKnownToServer()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $api = new Zoom([
            'server' => $this->server
        ]);

        if (! $api)
            die('Api creation failed for ' . __METHOD__);
            
        $x = $api->getMeetingInfo([
            'meetingId' => $this->meeting_id
        ]);
        return $x && $x->id;
    }

    /**
     *
     * @brief check if session is running
     * @param string $meeting_id
     * @return boolean
     */
    function IsRunning()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        // First check if it's flagged as running in the database
        if (! parent::IsRunning())
            return false;

        $api = new Zoom([
            'server' => $this->server
        ]);

        if (! $api)
            die('Api creation failed for ' . __METHOD__);
            
        return $api->isMeetingRunning($this->meeting_id);
    }

    /**
     *
     * @brief BBB does not really use the schedule->start flow. Sessions are created/started when people join. Empty sessions are purged quickly.
     * @return boolean
     */
    function create_meeting()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        global $langBBBWelcomeMsg;

        // If a maximum limit of simultaneous meeting users has been set, use it
        if (! $this->sessionUsers || $this->sessionUsers <= 0) {
            $users_to_join = $this->sessionUsers;
        } else { // otherwise just count participants
            $users_to_join = $this->usersToBeJoined(); // this is DB-expensive so call before the loop
        }

        $start_date = substr($this->start_date, 0, 10) . 'T' . substr($this->start_date, 11);

        $duration = 0;
        if (($this->start_date != null) and ($this->end_date != null)) {
            $date_start = new DateTime($this->start_date);
            $date_end = new DateTime($this->end_date);
            $hour_duration = $date_end->diff($date_start)->h; // hour
            $min_duration = $date_end->diff($date_start)->i; // minutes
            $duration = $hour_duration * 60 + $min_duration;
        }
        if ($duration == 0) {
            echo __METHOD__ . ' Zero duration meetings not implemented for zoom - defaulting to 1 hour';
            $duration = 60;
        }

        $server = TcServer::LoadById($this->running_at);
        $zoom = new Zoom([
            'server' => $server
        ]);

        $creationParams = array(
            // 'meetingId' => $this->meeting_id, // REQUIRED - given by API on creation, you don't get to chose this with ZOOM
            'timezone' => date_default_timezone_get(),
            'start_time' => $start_date, // FORMAT yyyy-MM-ddTHH:mm:ss (no Z at the end, we're not using GMT)
            'topic' => $this->title,
            'password' => $this->att_pw, // Match this value in getJoinMeetingURL() to join as attendee.
                                          // 'moderatorPw' => $this->mod_pw, // Match this value in getJoinMeetingURL() to join as moderator. -- no moderator password in zoom
            'agenda' => $langBBBWelcomeMsg, // ''= use default. Change to customize.
                                             // 'logoutUrl' => '', // not implemented in ZOOM
                                             // 'maxParticipants' => $this->sessionUsers, // not implemented in ZOOM
            'duration' => $duration, // REQUIRED in zoom
                                      // 'meta_category' => '', // Use to pass additional info to BBB server. See API docs.
            'settings' => [
                'host_video' => false,
                'participant_video' => true,
                'join_before_host' => true,
                'mute_upon_entry' => true,
                'use_pmi' => false,
                'auto_recording' => ($this->record ? 'cloud' : 'none'), // this can have 'local', too but it's a dangerous default, so skip it
                'enforce_login' => false,
                'waiting_room' => true
                // 'contact_name'=>$username
                // 'contact_email'=>$useremail
            ]
        );

        $result = $zoom->createMeeting($creationParams);
        
        if (! $result) {
            return false;
        }
        
        //$result = '{"uuid":"jF8pH9uzRZONBUUcFfVP0g==","id":78856799994,"host_id":"KozSavKkS8GnZyINEabHww","topic":"777777777","type":2,"status":"waiting","start_time":"2020-05-09T20:27:00Z","duration":60,"timezone":"Europe/Athens","agenda":"Welcome to Teleconference!","created_at":"2020-05-09T20:37:29Z","start_url":"https://us04web.zoom.us/s/78856799994?zak=eyJ6bV9za20iOiJ6bV9vMm0iLCJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJjbGllbnQiLCJ1aWQiOiJLb3pTYXZLa1M4R25aeUlORWFiSHd3IiwiaXNzIjoid2ViIiwic3R5IjoxMDAsIndjZCI6InVzMDQiLCJjbHQiOjAsInN0ayI6Il9TdzZCZDRocTZpa04zRlk3T09iMzlhMF9KdG55enhfc3ZvZ0dSVnc0MVkuQmdVZ016QkNOa05yYWpCcVRHOTJlSEk1TlRWMVEyOWxVbWRSV0ZscVlUQlRkVkpBTXprMU5UVmxPREV4TkRaaVkyWmhNVFk1WmpSaU4yRXdZVFkyWmpOaVpETTJaR00wTW1aa09EVXpOVEppTldNNFpXWTBPV000T0dJM09UWmpOelJrT1FBTU0wTkNRWFZ2YVZsVE0zTTlBQVIxY3pBMCIsImV4cCI6MTU4OTA2Mzg1MCwiaWF0IjoxNTg5MDU2NjUwLCJhaWQiOiJCbFJEMXRQN1JPV2paVFc3VGpyTWpnIiwiY2lkIjoiIn0.I65dDHUG4Y4ReNLd_W7TAvoxr5E0lHhFtnnq2ygKnSA","join_url":"https://us04web.zoom.us/j/78856799994?pwd=TGpqWXZMZVIwcVlMVUEzV0ZPZzFhQT09","password":"5RSWWn","h323_password":"324434","pstn_password":"324434","encrypted_password":"TGpqWXZMZVIwcVlMVUEzV0ZPZzFhQT09","settings":{"host_video":false,"participant_video":true,"cn_meeting":false,"in_meeting":false,"join_before_host":true,"mute_upon_entry":true,"watermark":false,"use_pmi":false,"approval_type":2,"audio":"voip","auto_recording":"none","enforce_login":false,"enforce_login_domains":"","alternative_hosts":"","close_registration":false,"registrants_confirmation_email":true,"waiting_room":true,"registrants_email_notification":true,"meeting_authentication":false}}';
        //$result = json_decode($result);
        //echo "<hr>";
        //print_r($result);
        
        $meeting = new ZoomMeeting($result);
        //echo "<hr>FINAL MEETING:";print_r($meeting);
        
        $this->meeting_id = strval($meeting->id); //api returns meetingid as Long, get rid of ".0" at end
        $this->mod_pw = NULL;
        $this->att_pw = $meeting->password;
        $this->record = $meeting->settings->isRecording();
        
        $x = $this->save();  
        if ( !$x  )
            die('['.__METHOD__.'] DB SESSION INSERT FAILED');

        return true;
    }

    /**
     *
     * @global type $course_code
     * @global type $langBBBCreationRoomError
     * @global type $langBBBConnectionError
     * @global type $langBBBConnectionErrorOverload
     * @global type $langBBBWelcomeMsg
     * @param string $title
     * @param string $meeting_id
     * @param string $mod_pw
     * @param string $att_pw
     * @param string $record
     *            'true' or 'false'
     */
    function start_meeting()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return true; //You don't "START" a meeting remotely, you must join it to do this.
    }

    public function clearCaches()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $bbb = new Zoom([
            'server' => $server
        ]);
        $bbb->clearCaches();
    }
    
    public function isIdentifiableToRemote() {
        return $this->meeting_id ; //if we have a meeting id, we can manipulate this session
    }
    
}