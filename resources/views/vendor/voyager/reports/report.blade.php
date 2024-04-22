@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop
@section('page_title', 'Reports')

@section('page_header')
    <h1 class="page-title">
        <i class="icon voyager-people"></i>
        Reports
    </h1>
    @include('voyager::multilingual.language-selector')
@stop
@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-bordered pt-12">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="display-flex-column card1">
                                    <i class="icon voyager-people"></i>
                                    <h1>User Count</h1>
                                    <p>{{ $userCount }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="display-flex-column card2">
                                    <i class="icon voyager-check"></i>
                                    <h1>Advertisment Clicks</h1>
                                    <p>{{ $userCount }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="display-flex-column card3">
                                    <i class="icon voyager-bolt"></i>
                                    <h1>Advertisment Reach</h1>
                                    <p>{{ $userCount }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
