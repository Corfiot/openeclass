<?php

require_once 'ArrayObjectInitable.php';

/**
 * ===========================================================================================================================
 *
 * @author User
 *
 */
class ZoomTrackingField
{
    use ArrayObjectInitable;
    
    public function __construct($data)
    {
        $this->init($data);
    }
    
    /**
     *
     * @var string - required
     */
    public $field;
    
    /**
     *
     * @var string
     */
    public $value;
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *         Recurrence object. Use this object only for a meeting with type 8 i.e., a recurring meeting with fixed time.
 *         ONLY IN REQUESTS
 */
class ZoomMeetingRecurrence
{
    use ArrayObjectInitable;
    
    public function __construct($data)
    {
        $this->init($data);
    }
    
    /**
     *
     * @var integer Recurrence meeting types:
     *      1 - Daily
     *      2 - Weekly
     *      3 - Monthly
     */
    public $type;
    
    /**
     *
     * @var integer Define the interval at which the meeting should recur. For instance, if you would like to schedule a meeting
     *      that recurs every two months, you must set the value of this field as 2 and the value of the type parameter as 3.
     *      For a daily meeting, the maximum interval you can set is 90 days. For a weekly meeting the maximum interval that you
     *      can set is of 12 weeks. For a monthly meeting, there is a maximum of 3 months.
     */
    public $repeat_interval;
    
    /**
     *
     * @var string Use this field only if you're scheduling a recurring meeting of type 2 to state which day(s) of the week your
     *      meeting should repeat.
     *      Note: if you would like the meeting to occur on multiple days of a week, you should provide comma separated
     *      values for this field.
     *      1-7 - Sunday-Saturday
     */
    public $weekly_days;
    
    /**
     *
     * @var integer Use this field only if you're scheduling a recurring meeting of type 3 to state which day in a month the
     *      meeting should recur. The value ranges from 1 to 31.
     *      For instance, if you would like the meeting to recur on the 23rd of each month, provide 23 as the value of This
     *      field and 1 as the value of the repeat_interval field. Instead, if you would like the meeting to recur every three
     *      months, on 23rd of the months, change the value of the repeat_interval field to 3.
     */
    public $monthly_day;
    
    /**
     *
     * @var integer Use this field only if you're scheduling a recurring meeting of type 3 to state the week of the month
     *      when the meeting should recur. If you use this field you must also use the monthly_week_day field to
     *      state the day of the week when the meeting should recur.
     *      -1 - Last week of the month
     *      1 - First week of the month
     *      2 - Second week of the month
     *      3 - Third week of the month
     *      4 - Fourth week of the month
     */
    public $monthly_week;
    
    /**
     *
     * @var integer Use this field only if you're scheduling a recurring meeting of type 3 to state a specific day in a week
     *      when the monthly meeting should recur. To use this field, you must also use the month_week field.
     *      1-7 - Sunday-Saturday
     */
    public $monthly_week_day;
    
    /**
     *
     * @var integer (default 1, maximum 50)
     *      Select how many times the meeting should recur before it is cancelled. (Cannot be used with end_date_time)
     */
    public $end_times;
    
    /**
     *
     * @var string Select the final date on which the meeting will recur before it is cancelled. Should be in UTC time, such as
     *      2017-11-25T12:00:00Z. (Cannot be used with end_times)
     */
    public $end_date_time;
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *         Occurence object. This object is only returned for Recurring Webinars.
 *         ONLY IN RESPONSES
 */
class ZoomWebinarOccurence
{
    use ArrayObjectInitable;
    
    public function __construct($data)
    {
        $this->init($data);
    }
    
    /**
     *
     * @var string Occurrence ID: Unique Identifier that identifies an occurrence of a recurring webinar. [Recurring webinars]
     *      (https://support.zoom.us/hc/en-us/articles/216354763-How-to-Schedule-A-Recurring-Webinar) can have
     *      a maximum of 50 occurrences.
     */
    public $occurrence_id;
    
    /**
     *
     * @var string format: date-time
     */
    public $start_time;
    
    /**
     *
     * @var integer
     */
    public $duration;
    
    /**
     *
     * @var string Occurrence status.
     */
    public $status;
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *         ONLY IN RESPONSES
 */
class ZoomDialinNumber
{
    
    use ArrayObjectInitable;
    
    public function __construct($data)
    {
        $this->init($data);
    }
    
    /**
     *
     * @var string - Country code. For example, BR.
     */
    public $country;
    
    /**
     *
     * @var string - Full name of country. For example, Brazil.
     */
    public $country_name;
    
    /**
     *
     * @var string - City of the number, if any. For example, Chicago.
     */
    public $city;
    
    /**
     *
     * @var string - Phone number. For example, +1 2332357613.
     */
    public $number;
    
    /**
     *
     * @var enum(toll|tollfree) - "Type of number
     */
    public $type;
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *         Meeting settings.
 *
 */
class ZoomMeetingSettings
{
    use ArrayObjectInitable;
    
    public function __construct($data)
    {
        $this->init($data);
    }
    
    public function isRecording() {
        return $this->auto_recording != 'none';
    }
    
    /**
     *
     * @var boolean - Start video when the host joins the meeting.
     */
    public $host_video;
    
    /**
     *
     * @var boolean - Start video when participants join the meeting.
     */
    public $participant_video;
    
    /**
     *
     * @var boolean - Host meeting in China.
     */
    public $cn_meeting;
    
    /**
     *
     * @var boolean - Host meeting in India. (Default false)
     */
    public $in_meeting;
    
    /**
     *
     * @var boolean - Allow participants to join the meeting before the host starts the meeting. Only used for scheduled
     *      or recurring meetings. Default false;
     */
    public $join_before_host;
    
    /**
     *
     * @var boolean - Mute participants upon entry. Default false
     */
    public $mute_upon_entry;
    
    /**
     *
     * @var boolean - Add watermark when viewing a shared screen.
     */
    public $watermark;
    
    /**
     *
     * @var boolean Use Personal Meeting ID instead of an automatically generated meeting ID. It can only be used for scheduled
     *      meetings, instant meetings and recurring meetings with no fixed time. Default False
     */
    public $use_pmi;
    
    /**
     *
     * @var integer 0 - Automatically approve.
     *      1 - Manually approve.
     *      2 - No registration required. (default)
     */
    public $approval_type;
    
    /**
     *
     * @var integer Registration type. Used for recurring meeting with fixed time only.
     *      1 Attendees register once and can attend any of the occurrences. (default)
     *      2 Attendees need to register for each occurrence to attend.
     *      3 Attendees register once and can choose one or more occurrences to attend.
     */
    public $registration_type;
    
    /**
     *
     * @var string Determine how participants can join the audio portion of the meeting.
     *      both - Both Telephony and VoIP. (default)
     *      telephony - Telephony only.
     *      voip - VoIP only.
     */
    public $audio;
    
    /**
     *
     * @var string Automatic recording:
     *      local - Record on local.
     *      cloud - Record on cloud.
     *      none - Disabled. (default)
     */
    public $auto_recording;
    
    /**
     *
     * @var boolean - Only signed in users can join this meeting.
     */
    public $enforce_login;
    
    /**
     *
     * @var string - Only signed in users with specified domains can join meetings.
     */
    public $enforce_login_domains;
    
    /**
     *
     * @var string - Alternative host’s emails or IDs: multiple values separated by a comma.
     */
    public $alternative_hosts;
    
    /**
     *
     * @var boolean - Close registration after event date. Default false
     */
    public $close_registration;
    
    /**
     *
     * @var boolean - Enable waiting room. Default:false
     */
    public $waiting_room;
    
    /**
     *
     * @var string[] - List of global dial-in countries
     */
    public $global_dial_in_countries;
    
    /**
     *
     * @var ZoomDialinNumber[] Global Dial-in Countries/Regions
     *      ONLY IN RESPONSE
     */
    public $global_dial_in_numbers;
    
    /**
     *
     * @var string - Contact name for registration
     */
    public $contact_name;
    
    /**
     *
     * @var string - Contact email for registration
     */
    public $contact_email;
    
    /**
     *
     * @var boolean Send confirmation email to registrants upon successful registration
     */
    public $registrants_confirmation_email;
    
    /**
     *
     * @var boolean Send email notifications to registrants about approval, cancellation, denial of the registration.
     *      The value of this field must be set to true in order to use the registrants_confirmation_email field.
     */
    public $registrants_email_notification;
    
    /**
     *
     * @var boolean - Only authenticated users can join meeting if the value of this field is set to true.
     */
    public $meeting_authentication;
    
    /**
     *
     * @var string IN REQUESTS:
     *      Specify the authentication type for users to join a meeting withmeeting_authentication setting set to true.
     *      The value of this field can be retrieved from the id field within authentication_options array in the response of Get User Settings API.
     *      IN RESPONSES:
     *      Meeting authentication option id.
     */
    public $authentication_option;
    
    /**
     *
     * @var string IN REQUESTS:
     *      Meeting authentication domains. This option, allows you to specify the rule so that Zoom users, whose email address contains
     *      a certain domain, can join the meeting. You can either provide multiple domains, using a comma in between and/or use a
     *      wildcard for listing domains.
     *      IN RESPONSES:
     *      If user has configured [\"Sign Into Zoom with Specified Domains\"]
     *      (https://support.zoom.us/hc/en-us/articles/360037117472-Authentication-Profiles-for-Meetings-and-Webinars#h_5c0df2e1-cfd2-469f-bb4a-c77d7c0cca6f)
     *      option, this will list the domains that are authenticated.
     */
    public $authentication_domains;
    
    /**
     *
     * @var string Authentication name set in the [authentication profile]
     *      (https://support.zoom.us/hc/en-us/articles/360037117472-Authentication-Profiles-for-Meetings-and-Webinars#h_5c0df2e1-cfd2-469f-bb4a-c77d7c0cca6f).
     */
    public $authentication_name;
}




/**
 * ===========================================================================================================================
 *
 * @author User
 *
 */
class ZoomMeeting
{
    use ArrayObjectInitable;
    
    public function __construct($data)
    {
        //echo "\nCONSTRUCT MEETING\n";
        //print_r($data);
        
        $this->init($data, [
            'settings' => 'ZoomMeetingSettings',
            'tracking_fields' => 'ZoomTrackingField[]',
            'recurrence' => 'ZoomMeetingRecurrence',
        ]);
        //         echo '<pre>CONSTRUCTED MEETING:'."\n".var_export($this,true).'</pre>';
        //         die();
    }
    
    /**
     *
     * @var string Unique meeting ID. Each meeting instance will generate its own Meeting UUID. Please double encode your UUID when
     *      using it for API calls if the UUID begins with a '/'or contains '//' in it.
     *      IN RESPONSE ONLY: getmeeting
     */
    public $uuid;
    
    /**
     *
     * @var string [Meeting ID](https://support.zoom.us/hc/en-us/articles/201362373-What-is-a-Meeting-ID-): Unique identifier of the meeting in
     *      "**long**" format(represented as int64 data type in JSON), also known as the meeting number.
     *      IN RESPONSE ONLY: getmeeting, createmeeting
     */
    public $id;
    
    /**
     *
     * @var string ID of the user who is set as host of meeting.
     *      IN RESPONSE ONLY: getmeeting
     */
    public $host_id;
    
    /**
     *
     * @var String Meeting status
     *      "waiting",
     *      "started",
     *      "finished"
     *      ONLY IN RESPONSE: getmeeting
     */
    public $status;
    
    /**
     *
     * @var string URL to start the meeting. This URL should only be used by the host of the meeting and **should not be shared with anyone
     *      other than the host** of the meeting as anyone with this URL will be able to login to the Zoom Client as the host of the meeting.
     *      IN RESPONSE ONLY: getmeeting, createmeeting
     */
    public $start_url;
    
    /**
     *
     * @var string URL for participants to join the meeting. This URL should only be shared with users that you would like to invite for the meeting.
     *      ONLY IN RESPONSE: getmeeting, createmeeting
     */
    public $join_url;
    
    /**
     *
     * @var string H.323/SIP room system password
     *      IN RESPONSE ONLY: createmeeting, getmeeting
     */
    public $h323_password;
    
    /**
     * @var string NOT IN SPEC 20200429 - so private
     */
    private $pstn_password;
    
    /**
     *
     * @var string Encrypted password for third party endpoints (H323/SIP).
     *      ALSO IN RESPONSE: getmeeting
     */
    public $encrypted_password;
    
    /**
     *
     * @var string Personal Meeting Id. Only used for scheduled meetings and recurring meetings with no fixed time.
     *      IN RESPONSE ONLY: createmeeting, getmeeting
     */
    public $pmi;
    
    /**
     *
     * @var ZoomWebinarOccurence[] - Array of occurrence objects.
     *      IN RESPONSE ONLY: createmeeting, getmeeting
     */
    public $occurrences;
    
    /**
     *
     * @var string Meeting topic.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $topic;
    
    /**
     *
     * @var integer Meeting Type:
     *      1 - Instant meeting.
     *      2 - Scheduled meeting. (default)
     *      3 - Recurring meeting with no fixed time.
     *      8 - Recurring meeting with fixed time.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $type;
    
    /**
     *
     * @var string Meeting start time. We support two formats for start_time - local time and GMT.
     *      To set time as GMT the format should be yyyy-MM-ddTHH:mm:ssZ. Example: “2020-03-31T12:02:00Z”
     *      To set time using a specific timezone, use yyyy-MM-ddTHH:mm:ss format and specify the timezone ID in the timezone field OR leave it blank and the timezone set on your Zoom account will be used. You can also set the time as UTC as the timezone field.
     *      The start_time should only be used for scheduled and / or recurring webinars with fixed time.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $start_time;
    
    /**
     *
     * @var Integer Meeting duration (minutes). Used for scheduled meetings only.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $duration;
    
    /**
     *
     * @var string Time zone to format start_time. For example, “America/Los_Angeles”. For scheduled meetings only. Please reference our time
     *      zone list for supported time zones and their formats.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $timezone;
    
    /**
     *
     * @var string - The date and time at which this meeting was created.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     *
     */
    public $created_at;
    
    /**
     *
     * @var string Password to join the meeting. Password may only contain the following characters: [a-z A-Z 0-9 @ - _ *]. Max of 10 characters.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     *      If "Require a password when scheduling new meetings" setting has been **enabled** **and** [locked]
     *      (https://support.zoom.us/hc/en-us/articles/115005269866-Using-Tiered-Settings#locked) for the user, the password field will be
     *      autogenerated in the response even if it is not provided in the API request.
     */
    public $password;
    
    /**
     *
     * @var string Meeting description. Maxlength 2000
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $agenda;
    
    /**
     *
     * @var ZoomTrackingField[] - Tracking Fields (metadata)
     *      ALSO IN RESPONSE getmeeting, createmeeting
     */
    public $tracking_fields;
    
    /**
     *
     * @var ZoomMeetingRecurrence ALSO IN RESPONSE getmeeting, createmeeting
     */
    public $recurrence;
    
    /**
     *
     * @var ZoomMeetingSettings ALSO IN RESPONSE
     *      ALSO IN RESPONSE getmeeting, createmeeting
     */
    public $settings;
}
