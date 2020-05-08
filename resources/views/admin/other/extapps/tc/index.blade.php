@extends('layouts.default')

@section('content')
    {!! isset($action_bar) ?  $action_bar : '' !!}
    @if (count($servers) > 0)
        <div class='table-responsive'>
            <table class='table-default'>
                <thead>
                <tr>
                    <th class = "text-center">Type</th>
                    <th class = 'text-center'>API URL</th>
                    <th class = 'text-center'>{{ trans('langBBBEnabled') }}</th>
                    <th class = 'text-center'>{{ trans('langOnlineUsers') }}</th>
                    <th class = 'text-center'>{{ trans('langMaxRooms') }}</th>
                    <th class = 'text-center'>{{ trans('langBBBServerOrderP') }}</th>
                    <th class = 'text-center'>{!! icon('fa-gears') !!}</th>
                </tr>
                </thead>
        @foreach ($servers as $server)
            <tr>
                <td>{{ $server->type }} ({{ $server->id }})</td>
                <td>{{ $server->api_url }}</td>
                <td class='text-center'>{{ $server->enabled ? trans('langYes') : trans('langNo') }}</td>
                <td class='text-center'>{{ $server->get_connected_users() }}</td>
                <td class='text-center'>{{ $server->max_rooms }}</td>
                <td class='text-center'>{{ $server->weight }}</td>
                <td class='option-btn-cell'>
                {!! action_button([
                    [
                        'title' => trans('langEditChange'),
                        'url' => "$_SERVER[SCRIPT_NAME]?edit_server=" . getIndirectReference($server->id),
                        'icon' => 'fa-edit'
                    ],
                    [
                        'title' => trans('langDelete'),
                        'url' => "$_SERVER[SCRIPT_NAME]?delete_server=" . getIndirectReference($server->id),
                        'icon' => 'fa-times',
                        'class' => 'delete',
                        'confirm' => trans('langConfirmDelete')
                    ]
                ]) !!}
                </td>
            </tr>
        @endforeach            	
        </table>
    </div>
    @else
        <div class='alert alert-warning'>{{ trans('langNoAvailableBBBServers') }}</div>
    @endif   
@endsection