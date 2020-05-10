
@extends('layouts.default')

@section('content')
    {!! isset($action_bar) ?  $action_bar : '' !!}
    @if (! $active_server_exists )
        <div class='alert alert-danger'>
        @if ($is_editor)
            {{ trans('langBBBNotServerAvailableTeacher') }}
        @else
            {{ trans('langBBBNotServerAvailableStudent') }}
        @endif
        </div>
    @else
        @if (!$is_editor )
            <div class='alert alert-info'><label>{{ trans('langNote') }}</label>: {{ trans('langBBBNoteEnableJoin') }}</div>";
        @endif
    @endif
    
    @if ( count($sessions) == 0 )
    	<div class='alert alert-warning'>{{ trans('langNoBBBSesssions') }}</div>
    @else
        @if (!$is_editor and $active_server_exists) {
            <div class='alert alert-info'><label>{{ trans('langNote') }}</label>: trans('langBBBNoteEnableJoin') }}</div>
        @else
		<div class='row'>
           <div class='col-md-12'>
           @if ( count($sessions) == 0 )
           		<div class='alert alert-warning'>{{ trans('langNoBBBSesssions') }}</div>
           @else
             <div class='table-responsive'>
               <table class='table-default'>
                 <tr class='list-header'>
                   <th style='width: 50%'>{{ trans('langTitle') }}</th>
                   <th class='text-center'>{{ trans('langDate') }}</th>
                   <th class='text-center'>{{ trans('langParticipants') }}</th>
                   <th class='text-center'>{{ trans('langBBBServer') }}</th>
                   <th class='text-center'>{!! icon('fa-gears') !!}</th>
                 </tr>
                 @foreach($sessions as $s)
                	<tr
                	@if ( $is_editor )
                		{{ $s['active']?'':' class="not_visible"' }}
                	@endif
                    ><td>
                        <div class='table_td'>
                            <div class='table_td_header clearfix'>{!! $s['joinLink'] !!}
                            	{!! array_key_exists('warning_message_record',$s)?$s['warning_message_record']:'' !!}</div> 
                            <div class='table_td_body'>
                                {!! $s['desc'] !!}
                            </div>
                        </div>
                    </td>
                    <td class='text-center'>
                        <div style='padding-top: 7px;'>  
                            <span class='text-success'>{{ trans('langNewBBBSessionStart') }}</span>: {{ nice_format($s['start_date'], TRUE) }}<br/>
                        </div>
                        <div style='padding-top: 7px;'>
                            <span class='text-danger'>{{ trans('langNewBBBSessionEnd') }}</span>: {!! $s['timeLabel'] !!}</br></br>
                        </div>
                    </td>
                    <td style='width: 20%'><span class='trunk8'>{{ $s['participants'] }}</span></td>
                    <td>#{{ $s['serverinfo']['id'] }} <br> {{ $s['serverinfo']['type'] }}</td>
                 	@if( $is_editor )
                        <td class='option-btn-cell'>
                        	{!! action_button(array(
                                array(
                                    'title' => trans('langEditChange'),
                                    'url' => "$_SERVER[SCRIPT_NAME]?id=" . getIndirectReference($s['id']) . "&amp;choice=edit",
                                    'icon' => 'fa-edit'
                                ),
                                array(
                                    'title' => trans('langBBBImportRecordings'),
                                    'url' => "$_SERVER[SCRIPT_NAME]?id=" . getIndirectReference($s['id']) . "&amp;choice=import_video",
                                    'icon' => "fa-edit",
                                    //'show' => in_array('bbb', $this->tc_types)
                                ),
                                array(
                                    'title' => trans('langParticipate'),
                                    'url' => "tcuserduration.php?id=".$s['id'],
                                    'icon' => "fa-clock-o",
                                    //'show' => in_array('bbb', $this->tc_types)
                                ),
                                array(
                                    'title' => $s['active'] ? trans('langDeactivate') : trans('langActivate'),
                                    'url' => "$_SERVER[SCRIPT_NAME]?id=" . getIndirectReference($s['id']) . 
                                    		"&amp;choice=do_" . ($s['active'] ? 'disable' : 'enable'),
                                    'icon' => $s['active'] ? 'fa-eye' : 'fa-eye-slash'
                                ),
                                array(
                                    'title' => trans('langBBBForget'),
                                    'url' => "$_SERVER[SCRIPT_NAME]?id=" . getIndirectReference($s['id']) . "&amp;choice=do_forget",
                                    'icon' => 'fa-times',
                                    'class' => 'delete',
                                    'confirm' => trans('langBBBForgetConfirm'),
                                    'confirm_title' => trans('langBBBForget'),
                                    'confirm_button' => trans('langBBBForget'),
                                    //'show' => $s['identifiable']
                            	),
                                array(
                                    'title' => trans('langDelete'),
                                    'url' => "$_SERVER[SCRIPT_NAME]?id=" . getIndirectReference($s['id']) . "&amp;choice=do_delete",
                                    'icon' => 'fa-times',
                                    'class' => 'delete',
                                    'confirm' => trans('langConfirmDelete'),
                                    'confirm_title' => trans('langDelete'),
                                    'confirm_button' => trans('langDelete'),
                                    'show' => $s['identifiable']
                            	),
                            )) !!}
                    	</td>
                 	@else
                        <td class='text-center'>
                        @if ( $s['canJoin'] )
                            {!! icon('fa-sign-in', trans('langBBBSessionJoin'), $joinLink) !!}
                        @else
                            -</td>
                        @endif
                 	@endif
                 	</tr>
                 @endforeach
               </table>
            @endif
        	</div>
        @endif
        </div> <!--  col-md-12 -->
    </div> <!--  row  -->
    @endif
@endsection



@push('head_scripts')
<script type="text/javascript">
    //<![CDATA[
        $(document).ready(function () {
        $('#popupattendance1').click(function() {
            window.open($(this).prop('href'), '', 'height=200,width=500,scrollbars=no,status=no');
            return false;
        });
    });
</script>
@endpush

