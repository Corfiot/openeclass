@extends('layouts.default')

@section('content')
    {!! isset($action_bar) ?  $action_bar : '' !!}
    <div class='form-wrapper'>
		<form class='form-horizontal' role='form' name='sessionForm' action='{!! $_SERVER['SCRIPT_NAME'] !!}' method='post' >
            <fieldset>
    	        <div class='form-group'>
        	        <label class='col-sm-2 control-label'>{{ trans('langType') }}:</label>
                	<div class='col-sm-10'>
                        <div class='radio'>
                        @foreach ($types as $at)
                            <label><input type="checkbox" id="type_{{ $at }}'_button" name="type[]"
                            	value="{{ $at }}" {{ (in_array($at, $types) ? " checked " : '') }}>{{$at}}
                           	</label>
                        @endforeach
                        </div>
                	</div>
                </div>
                <div class='form-group'>
                    <label for='title' class='col-sm-2 control-label'>{{ trans('langTitle') }}:</label>
                    <div class='col-sm-10'>
                        <input class='form-control' type='text' name='title' id='title' value='{{ $title }}' placeholder='{{ trans('langTitle') }}' size='50'>
                    </div>
                </div>
                <div class='form-group'>
                    <label for='desc' class='col-sm-2 control-label'>{{ trans('langUnitDescr') }}:</label>
                    <div class='col-sm-10'>{!! $desc !!}</div>
                </div>
                <div class='form-group'>
                    <label for='start_session' class='col-sm-2 control-label'>{{ trans('langNewBBBSessionStart') }}:</label>
                    <div class='col-sm-10'>
                        <input class='form-control' type='text' name='start_session' id='start_session' value='{{ $start }}'>
                    </div>
                </div>
                <div class='input-append date form-group {{  (Session::getError('BBBEndDate') ? " has-error" : "") }}' id='enddatepicker' data-date='{{ $end }}' data-date-format='dd-mm-yyyy'>
                    <label for='BBBEndDate' class='col-sm-2 control-label'>{{ trans('langEnd') }}:</label>
                    <div class='col-sm-10'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <input style='cursor:pointer;' type='checkbox' id='enableEndDate' name='enableEndDate' 
                                	value='1' {{ ($enableEndDate ? ' checked' : '') }}>
                            </span>
                            <input class='form-control' name='BBBEndDate' id='BBBEndDate' type='text' value='{{ $end }}' {{ ($enableEndDate ? '' : ' disabled')}} >
                        </div>
                        <span class='help-block'>
                        	@if (Session::hasError('BBBEndDate'))
                        		{{ Session::getError('BBBEndDate') }}
                        	@else
                        		&nbsp;&nbsp;&nbsp;<i class='fa fa-share fa-rotate-270'></i> {{ trans('langBBBEndHelpBlock') }}
                        	@endif
                        </span>
                    </div>
                </div>
                <div class='form-group'>
                @if (! $server || $server->recording() )
                    <label for='group_button' class='col-sm-2 control-label'>{{ trans('langBBBRecord') }}:</label>
                    <div class='col-sm-10'>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='user_button' name='record' value='true' {{ $record == true ? 'checked' : '' }}>
                            {{ trans('langBBBRecordTrue') }}
                          </label>
                        </div>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='group_button' name='record' value='false' {{ $record == false ? 'checked' : '' }}>
                            {{ trans('langBBBRecordFalse') }}
                          </label>
                        </div>
                    </div>
                    @if (!$server)
                        <div>{{ trans('langBBBRecordingMayNotBeAvailable') }}</div>
                    @endif
                @else
                    <div> {{ trans('langBBBRecordingNotAvailable') }}</div>
                @endif
            	</div>
                <div class='form-group'>
                    <label for='active_button' class='col-sm-2 control-label'>{{ trans('langNewBBBSessionStatus') }}:</label>
                    <div class='col-sm-10'>
                        <div class='radio'>
                        	<label>
                                <input type='radio' id='active_button' name='status' value='1' {{ $status == 1 ? "checked" : "" }}>
                                {{trans('langVisible') }}
                            </label>
                            <label style='margin-left: 10px;'>
                            	<input type='radio' id='inactive_button' name='status' value='0' {{ $status == 0 ? "checked" : "" }}>
                                {{ trans('langInvisible') }}
                            </label>
                        </div>
                    </div>
                </div>
            <div class='form-group'>
            	<label for='active_button' class='col-sm-2 control-label'>{{ trans('langAnnouncements') }}:</label>
                <div class='col-sm-10'>
                 <div class='checkbox'>
                  <label><input type='checkbox' name='addAnnouncement' value='1'>{{ trans('langBBBAnnDisplay') }}</label>
                </div>
                </div>
            </div>
            <div class='form-group'>
                <label for='minutes_before' class='col-sm-2 control-label'>{{ trans('langBBBSessionAvailable') }}:</label>
                <div class='col-sm-10'>
                	{!! selection(array(10 => '10',15 => '15',30 => '30'), 'minutes_before', $unlock_interval, "id='minutes_before'") !!}
                    {{ trans('langBBBMinutesBefore') }} 
                </div>
            </div>
            <div class='form-group'>
                <label for='sessionUsers' class='col-sm-2 control-label'>{{ trans('langBBBSessionMaxUsers') }}:</label>
                <div class='col-sm-10'>
                    <input class='form-control' type='text' name='sessionUsers' id='sessionUsers' value='{{ $session_users }}'>
                    {{ trans('langBBBSessionSuggestedUsers') }}:
                    <strong>{{ $usercount }}</strong> ( {{ $ratio }})
                </div>
            </div>
            <div class='form-group'>
                <label for='select-groups' class='col-sm-2 control-label'>{{ trans('langParticipants') }}:</label>
                <div class='col-sm-10'>
                	<select name='groups[]' multiple='multiple' class='form-control' id='select-groups'>
					@foreach ($participantoptions as $o)
						<option value="{{ $o['value'] }}" {{ (isset($o['selected']) && $o['selected'])?' checked ':'' }}>{{ $o['text'] }}<option> 
					@endforeach
            		</select><a href='#' id='selectAll'>{{ trans('langJQCheckAll') }}</a> | <a href='#' id='removeAll'>{{ trans('langJQUncheckAll') }}</a>
                </div>
            </div>
            <div class='form-group'>
                <div class='col-sm-10 col-sm-offset-2'>
                    <div class='checkbox'><label><input type='checkbox' name='notifyUsers' value='1'>{{ trans('langBBBNotifyUsers') }}</label></div>
                </div>
            </div>
            <div class='form-group'>
                <label for='tags_1' class='col-sm-2 control-label'>{{ trans('langBBBExternalUsers') }}:</label>
                <div class='col-sm-10'>
                    <select id='tags_1' class='form-control' name='external_users[]' multiple></select>
                    <span class='help-block'>&nbsp;&nbsp;&nbsp;<i class='fa fa-share fa-rotate-270'></i>
                    	{{ trans('langBBBNotifyExternalUsersHelpBlock') }}</span>
                </div>
            </div>
            <div class='form-group'>
                <div class='col-sm-10 col-sm-offset-2'>
                    <div class='checkbox'>
                      <label><input type='checkbox' name='notifyExternalUsers' value='1'>{{ trans('langBBBNotifyExternalUsers') }}</label></div>
                </div>
            </div>
            @if ( isset($id) )
            	<input type="hidden" name='id' value="{{ $id }}">
            @endif
            <div class='form-group'>
                <div class='col-sm-10 col-sm-offset-2'>
                    <input class='btn btn-primary' type='submit' name='{{ $submit_name }}' value='{{ $value_message }}'>
                </div>
            </div>
            </fieldset>
             {!! generate_csrf_token_form_field() !!}
        </form>
    </div>
@endsection

@push('head_scripts')    
    <script type="text/javascript">
        //<![CDATA[
        $(function () {
            $('#tags_1').select2({
                @if ( $init_external_users )
                data: {!! $init_external_users !!},
                @endif
                tags: true,
                tokenSeparators: [',', ' '],
                width: '100%',
                selectOnClose: true
            });
        	$('input#start_session').datetimepicker({
                format: 'dd-mm-yyyy hh:ii',
                pickerPosition: 'bottom-right',
                //language: '" . $language . "',
                autoclose: true
            });
            $('#BBBEndDate').datetimepicker({
                format: 'dd-mm-yyyy hh:ii',
                pickerPosition: 'bottom-right',
                //language: '" . $language . "',
                autoclose: true
            }).on('changeDate', function(ev){
                if($(this).attr('id') === 'BBBEndDate') {
                    $('#answersDispEndDate, #scoreDispEndDate').removeClass('hidden');
                }
            }).on('blur', function(ev){
                if($(this).attr('id') === 'BBBEndDate') {
                    var end_date = $(this).val();
                    if (end_date === '') {
                        if ($('input[name="dispresults"]:checked').val() == 4) {
                            $('input[name="dispresults"][value="1"]').prop('checked', true);
                        }
                        $('#answersDispEndDate, #scoreDispEndDate').addClass('hidden');
                    }
                }
            });
            $('#enableEndDate').change(function() {
                var dateType = $(this).prop('id').replace('enable', '');
                if($(this).prop('checked')) {
                    $('input#BBB'+dateType).prop('disabled', false);
                    if (dateType === 'EndDate' && $('input#BBBEndDate').val() !== '') {
                        $('#answersDispEndDate, #scoreDispEndDate').removeClass('hidden');
                    }
                } else {
                    $('input#BBB'+dateType).prop('disabled', true);
                    if ($('input[name="dispresults"]:checked').val() == 4) {
                        $('input[name="dispresults"][value="1"]').prop('checked', true);
                    }
                    $('#answersDispEndDate, #scoreDispEndDate').addClass('hidden');
                }
            });
        });

        $(document).ready(function () {
            $('#select-groups').select2();
            $('#selectAll').click(function(e) {
                e.preventDefault();
                var stringVal = [];
                $('#select-groups').find('option').each(function(){
                    stringVal.push($(this).val());
                });
                $('#select-groups').val(stringVal).trigger('change');
            });
            $('#removeAll').click(function(e) {
                e.preventDefault();
                var stringVal = [];
                $('#select-groups').val(stringVal).trigger('change');
            });

            var chkValidator  = new Validator('sessionForm');
            chkValidator.addValidation("title", "req", "{{ trans('langBBBAlertTitle') }}");
            chkValidator.addValidation("sessionUsers", "req", "{{trans('langBBBAlertMaxParticipants') }}");
            chkValidator.addValidation("sessionUsers", "numeric", "{{trans('langBBBAlertMaxParticipants') }}");
        });



        //]]>
    </script>  
@endpush